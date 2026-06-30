<?php

namespace App\Services\PeopleConnect;

use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\WahaSyncProcess;
use App\Models\Agent;
use App\Models\ContactTag;
use App\Models\ContactPreference;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\DB;
use Exception;

class WahaAnalysisService
{
    public function analyzeContacts(WahaSyncProcess $process): void
    {
        $config = $process->config ?? [];
        $contactIds = $config['contact_ids'] ?? [];
        $messageLimit = $config['message_limit'] ?? 50;

        $query = Contact::whereNotNull('waha_contact_id');
        if (!empty($contactIds)) {
            $query->whereIn('id', $contactIds);
        }

        $total = $query->count();
        $process->update([
            'status' => 'running',
            'total_items' => $total,
        ]);

        $contacts = $query->get();

        foreach ($contacts as $contact) {
            // Check for pause
            $process->refresh();
            if ($process->status === 'paused') {
                return;
            }

            try {
                $this->analyzeContact($contact, $config, $messageLimit);
                
                $process->increment('processed_items');
                $process->update(['progress' => round(($process->processed_items / $total) * 100)]);
            } catch (Exception $e) {
                Log::error("WahaAnalysisService Error on contact {$contact->id}: " . $e->getMessage());
                $process->increment('failed_items');
            }
        }

        $process->update([
            'status' => 'completed',
            'completed_at' => now(),
            'progress' => 100
        ]);
    }

    protected function analyzeContact(Contact $contact, array $config, int $limit): void
    {
        $messages = ContactMessage::where('contact_id', $contact->id)
            ->whereNotNull('waha_message_id')
            ->orderBy('created_at', 'desc')
            ->take($limit)
            ->get()
            ->reverse();

        if ($messages->isEmpty()) {
            return;
        }

        $agentId = $config['agent_id'] ?? null;
        $extractPreferences = $config['extract_preferences'] ?? false;
        $extractPersonality = $config['extract_personality'] ?? false;
        $extractTopics = $config['extract_topics'] ?? false;

        // Stubbed AI call
        // In real implementation, we would pass $messages to the Agent (via AgentRuntimeService or similar)
        // Here we simulate the extraction for demonstration of the flow.

        DB::transaction(function () use ($contact, $extractPreferences, $extractPersonality, $extractTopics) {
            if ($extractPreferences) {
                ContactPreference::updateOrCreate(
                    ['contact_id' => $contact->id, 'key' => 'language'],
                    ['value' => 'Arabic', 'confidence_score' => 0.9]
                );
            }
            if ($extractPersonality) {
                ContactTag::firstOrCreate([
                    'contact_id' => $contact->id,
                    'tag' => 'Friendly',
                    'source' => 'ai_analysis'
                ]);
            }
        });
    }
}
