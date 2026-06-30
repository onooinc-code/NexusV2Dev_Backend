<?php

namespace App\Services\Memory;

use App\Models\Contact;
use App\Models\Memory;
use Illuminate\Support\Facades\Log;

class EpisodicMemoryService
{
    /**
     * Store an event as episodic memory
     *
     * @param int $contactId
     * @param string $eventType
     * @param array $data
     * @return bool
     */
    public function storeEvent(int $contactId, string $eventType, array $data): bool
    {
        try {
            // Validate contact exists
            $contact = Contact::find($contactId);
            if (!$contact) {
                Log::warning('EpisodicMemoryService::storeEvent - Contact not found', [
                    'contactId' => $contactId
                ]);
                return false;
            }

            // Store episodic memory
            $memory = Memory::create([
                'contact_id' => $contactId,
                'type' => 'episodic',
                'source' => 'system',
                'content' => json_encode([
                    'event_type' => $eventType,
                    'data' => $data,
                    'timestamp' => now()->toDateTimeString()
                ]),
                'metadata' => [
                    'event_type' => $eventType
                ]
            ]);

            return $memory !== null;
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::storeEvent failed', [
                'contactId' => $contactId,
                'eventType' => $eventType,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Store a message as episodic memory
     *
     * @param int $contactId
     * @param string $content
     * @param string $sender
     * @param array $metadata
     * @return bool
     */
    public function storeMessage(int $contactId, string $content, string $sender = 'user', array $metadata = []): bool
    {
        try {
            // Validate contact exists
            $contact = Contact::find($contactId);
            if (!$contact) {
                Log::warning('EpisodicMemoryService::storeMessage - Contact not found', [
                    'contactId' => $contactId
                ]);
                return false;
            }

            // Store message as episodic memory
            $memory = Memory::create([
                'contact_id' => $contactId,
                'type' => 'episodic',
                'source' => $sender,
                'content' => $content,
                'metadata' => array_merge([
                    'stored_at' => now()->toDateTimeString()
                ], $metadata)
            ]);

            return $memory !== null;
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::storeMessage failed', [
                'contactId' => $contactId,
                'sender' => $sender,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Retrieve episodic memories for a contact
     *
     * @param int $contactId
     * @param int $limit
     * @param int $offset
     * @return \Illuminate\Support\Collection
     */
    public function retrieve(int $contactId, int $limit = 50, int $offset = 0)
    {
        try {
            return Memory::where('contact_id', $contactId)
                ->where('type', 'episodic')
                ->orderBy('created_at', 'desc')
                ->offset($offset)
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::retrieve failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Retrieve episodic memories by event type
     *
     * @param int $contactId
     * @param string $eventType
     * @param int $limit
     * @return \Illuminate\Support\Collection
     */
    public function retrieveByEventType(int $contactId, string $eventType, int $limit = 50)
    {
        try {
            return Memory::where('contact_id', $contactId)
                ->where('type', 'episodic')
                ->whereJsonContains('metadata->event_type', $eventType)
                ->orderBy('created_at', 'desc')
                ->limit($limit)
                ->get();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::retrieveByEventType failed', [
                'contactId' => $contactId,
                'eventType' => $eventType,
                'error' => $e->getMessage()
            ]);
            return collect();
        }
    }

    /**
     * Delete episodic memory
     *
     * @param int $memoryId
     * @return bool
     */
    public function delete(int $memoryId): bool
    {
        try {
            $memory = Memory::find($memoryId);
            if (!$memory) {
                return false;
            }

            // Verify it's an episodic memory before deleting
            if ($memory->type !== 'episodic') {
                Log::warning('EpisodicMemoryService::delete - Attempted to delete non-episodic memory', [
                    'memoryId' => $memoryId
                ]);
                return false;
            }

            return $memory->delete();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::delete failed', [
                'memoryId' => $memoryId,
                'error' => $e->getMessage()
            ]);
            return false;
        }
    }

    /**
     * Count episodic memories for a contact
     *
     * @param int $contactId
     * @return int
     */
    public function count(int $contactId): int
    {
        try {
            return Memory::where('contact_id', $contactId)
                ->where('type', 'episodic')
                ->count();
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::count failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
            return 0;
        }
    }

    /**
     * Paginate episodic memories
     *
     * @param int|null $contactId
     * @param int $perPage
     * @param string $sort
     * @return array
     */
    public function paginate(int $contactId = null, int $perPage = 25, string $sort = 'created_at'): array
    {
        try {
            $query = Memory::where('type', 'episodic');

            if ($contactId !== null) {
                $query->where('contact_id', $contactId);
            }

            $query->where(function($q) {
                $q->whereNull('expires_at')
                  ->orWhere('expires_at', '>', now());
            });

            $paginator = $query->orderBy($sort, 'desc')->paginate($perPage);

            return [
                'data' => $paginator->items(),
                'current_page' => $paginator->currentPage(),
                'total' => $paginator->total(),
                'per_page' => $paginator->perPage(),
                'last_page' => $paginator->lastPage(),
            ];
        } catch (\Exception $e) {
            Log::error('EpisodicMemoryService::paginate failed', [
                'contactId' => $contactId,
                'error' => $e->getMessage()
            ]);
            return [
                'data' => [],
                'current_page' => 1,
                'total' => 0,
                'per_page' => $perPage,
                'last_page' => 1,
            ];
        }
    }
}
