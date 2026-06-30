<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectSyncRun;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class LiveMsgsSyncService
{
    public function __construct(
        protected PeopleConnectContactResolver $contactResolver,
        protected PeopleConnectConversationService $conversationService,
        protected PeopleConnectSessionService $sessionService,
        protected PeopleConnectMessageService $messageService
    ) {
    }

    protected function getWahaUrl(): string
    {
        $url = app(\App\Services\SettingCacheService::class)->get('waha_url')
            ?? config('services.waha.url') 
            ?? config('services.waha.api_url') 
            ?? 'http://localhost:3333';
            
        $url = rtrim($url, '/');
        if (!preg_match('#^https?://#i', $url)) {
            $url = 'http://' . $url;
        }
        return $url;
    }

    protected function getWahaSecret(): string
    {
        return app(\App\Services\SettingCacheService::class)->get('waha_api_key')
            ?? config('services.waha.api_key') 
            ?? config('services.waha.api_token')
            ?? '666';
    }

    public function syncContacts($processId = null): void
    {
        $process = $processId ? \App\Models\WahaSyncProcess::find($processId) : null;
        if ($process) {
            $process->update(['status' => 'running']);
        }

        try {
            $headers = [
                'Authorization' => "Bearer {$this->getWahaSecret()}",
                'X-Api-Key' => $this->getWahaSecret(),
                'Accept' => 'application/json'
            ];
            $url = "{$this->getWahaUrl()}/api/contacts/all?session=default";
            
            Log::info('WAHA Sync Contacts Request', ['url' => $url]);
            
            $response = Http::withHeaders($headers)->get($url);

            if ($response->successful()) {
                $contacts = $response->json();
                $totalWaha = count($contacts);
                
                // Get existing waha_contact_ids from DB
                $existingIds = \App\Models\Contact::whereNotNull('waha_contact_id')->pluck('waha_contact_id')->toArray();
                
                // Filter only new contacts
                $unsyncedContacts = array_filter($contacts, function($c) use ($existingIds) {
                    $id = $c['id'] ?? null;
                    return $id && str_ends_with($id, '@c.us') && !in_array($id, $existingIds);
                });
                
                $unsyncedCount = count($unsyncedContacts);

                if ($process) {
                    $process->update([
                        'total_items' => $unsyncedCount,
                        'config' => array_merge($process->config ?? [], [
                            'waha_total_contacts' => $totalWaha,
                            'nexus_unsynced_contacts' => $unsyncedCount
                        ])
                    ]);
                }
                
                $count = 0;
                foreach ($unsyncedContacts as $wahaContact) {
                    if ($process) {
                        $process->refresh();
                        if ($process->status === 'paused') {
                            return; // Stop processing
                        }
                    }

                    $id = $wahaContact['id'];
                    $phone = str_replace('@c.us', '', $id);
                    $name = $wahaContact['name'] ?? $wahaContact['pushname'] ?? '';
                    
                    $contact = $this->contactResolver->resolve($id, $phone, $name);
                    $contact->update(['waha_contact_id' => $id]);
                    $count++;
                    
                    // Fire an event log if needed
                    Log::info("Synced new WAHA contact: {$name} ({$phone})");
                    
                    if ($process && $count % 5 === 0) {
                        $process->update([
                            'processed_items' => $count,
                            'progress' => $unsyncedCount > 0 ? round(($count / $unsyncedCount) * 100) : 0
                        ]);
                    }
                }
                
                if ($process) {
                    $process->update(['status' => 'completed', 'completed_at' => now(), 'processed_items' => $count, 'progress' => 100]);
                }
            } else {
                throw new \Exception('Failed to fetch contacts from WAHA: ' . $response->body());
            }
        } catch (\Throwable $e) {
            if ($process) {
                $process->update(['status' => 'failed', 'completed_at' => now(), 'errors' => ['message' => $e->getMessage()]]);
            } else {
                Log::error('Waha contacts sync failed: ' . $e->getMessage());
            }
        }
    }

    public function syncMessages($processId = null): void
    {
        $process = $processId ? \App\Models\WahaSyncProcess::find($processId) : null;
        if ($process) {
            $process->update(['status' => 'running']);
        }

        try {
            $contacts = \App\Models\Contact::whereNotNull('waha_contact_id')->get();
            $totalContacts = count($contacts);
            if ($process) {
                $process->update(['total_items' => $totalContacts]);
            }

            $processedContacts = 0;
            $totalMessagesFetched = 0;
            $totalMessagesInserted = 0;

            foreach ($contacts as $contact) {
                if ($process) {
                    $process->refresh();
                    if ($process->status === 'paused') {
                        return;
                    }
                }

                $chatId = $contact->waha_contact_id;
                
                // Fetch messages for this chat
                $response = Http::withHeaders([
                    'Authorization' => "Bearer {$this->getWahaSecret()}",
                    'X-Api-Key' => $this->getWahaSecret(),
                    'Accept' => 'application/json'
                ])->get("{$this->getWahaUrl()}/api/chats/{$chatId}/messages?session=default&limit=100");

                if ($response->successful()) {
                    $messages = $response->json();
                    $totalMessagesFetched += count($messages);
                    
                    foreach ($messages as $msg) {
                        $msgId = $msg['id'] ?? null;
                        if ($msgId) {
                            $model = \App\Models\ContactMessage::firstOrCreate(
                                ['waha_message_id' => $msgId],
                                [
                                    'contact_id' => $contact->id,
                                    'direction' => ($msg['fromMe'] ?? false) ? 'outbound' : 'inbound',
                                    'content' => $msg['body'] ?? '',
                                    'channel' => 'whatsapp',
                                    'status' => 'delivered'
                                ]
                            );
                            if ($model->wasRecentlyCreated) {
                                $totalMessagesInserted++;
                            }
                        }
                    }
                    Log::info("Synced messages for {$chatId}: " . count($messages) . " fetched.");
                } else {
                    $statusCode = $response->status();
                    if ($statusCode === 404) {
                        Log::warning("WAHA Plus is required to sync historical messages. Core edition returned 404 for {$chatId}.");
                        continue; // Skip instead of throwing to allow next contact
                    }
                    Log::error("Failed to fetch messages for {$chatId}. Status: {$statusCode} Body: {$response->body()}");
                    continue; // Skip failed contact
                }
                
                $processedContacts++;
                if ($process) {
                    $process->update([
                        'processed_items' => $processedContacts,
                        'progress' => $totalContacts > 0 ? round(($processedContacts / $totalContacts) * 100) : 0,
                        'config' => array_merge($process->config ?? [], [
                            'waha_messages_fetched' => $totalMessagesFetched,
                            'nexus_messages_inserted' => $totalMessagesInserted
                        ])
                    ]);
                }
            }

            if ($process) {
                $process->update(['status' => 'completed', 'completed_at' => now(), 'progress' => 100]);
            }
        } catch (\Throwable $e) {
            if ($process) {
                $process->update(['status' => 'failed', 'completed_at' => now(), 'errors' => ['message' => $e->getMessage()]]);
            } else {
                Log::error('Waha messages sync failed: ' . $e->getMessage());
            }
        }
    }

    public function syncSingleContactMessages($contactId, $processId = null): void
    {
        $process = $processId ? \App\Models\WahaSyncProcess::find($processId) : null;
        if ($process) {
            $process->update(['status' => 'running', 'total_items' => 1]);
        }

        try {
            $contact = \App\Models\Contact::whereNotNull('waha_contact_id')->findOrFail($contactId);
            $chatId = $contact->waha_contact_id;
            
            $url = "{$this->getWahaUrl()}/api/default/chats/{$chatId}/messages?sortBy=timestamp&downloadMedia=false&merge=true&limit=9999999";
            Log::info("Fetching messages from WAHA: " . $url);

            $response = Http::withHeaders([
                'Authorization' => "Bearer {$this->getWahaSecret()}",
                'X-Api-Key' => $this->getWahaSecret(),
                'Accept' => 'application/json'
            ])->timeout(300)->get($url);

            if ($response->successful()) {
                $messages = $response->json();
                $totalMessages = is_array($messages) ? count($messages) : 0;
                
                if ($process) {
                    $process->update([
                        'total_items' => $totalMessages,
                        'processed_items' => 0,
                        'progress' => 0,
                        'config' => array_merge($process->config ?? [], [
                            'waha_messages_fetched' => $totalMessages,
                            'nexus_messages_inserted' => 0
                        ])
                    ]);
                    broadcast(new \App\Events\JobProgressUpdated($process->id, 'sync_messages', 0, 0, $totalMessages, 'running', "Fetched {$totalMessages} messages, queuing chunks..."));
                }

                if ($totalMessages > 0) {
                    $chunks = array_chunk($messages, 500);
                    foreach ($chunks as $chunk) {
                        \App\Jobs\ProcessWahaMessageChunkJob::dispatch($contact->id, $chunk, $processId);
                    }
                    Log::info("Synced single contact {$chatId}: dispatched " . count($chunks) . " chunks for {$totalMessages} messages.");
                } else {
                    if ($process) {
                        $process->update(['status' => 'completed', 'completed_at' => now(), 'progress' => 100]);
                        broadcast(new \App\Events\JobProgressUpdated($process->id, 'sync_messages', 100, 0, 0, 'completed', 'No messages found.'));
                    }
                }
                
            } else {
                $statusCode = $response->status();
                if ($statusCode === 404) {
                    throw new \Exception("WAHA Plus edition is required to sync historical messages. The WAHA Core edition does not support this feature.");
                }
                throw new \Exception("Failed to fetch messages for {$chatId}: " . $statusCode);
            }
        } catch (\Throwable $e) {
            if ($process) {
                $process->update(['status' => 'failed', 'completed_at' => now(), 'errors' => ['message' => $e->getMessage()]]);
            } else {
                Log::error("Waha single contact messages sync failed: " . $e->getMessage());
            }
        }
    }
}
