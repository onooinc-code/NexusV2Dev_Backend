<?php

namespace App\Services\PeopleConnect;

use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectMessageAnalysis;
use App\Models\PeopleConnect\PeopleConnectConversationTopic;
use Illuminate\Support\Facades\Log;

class PeopleConnectAnalysisService
{
    /**
     * Runs NLP analysis on a message, stores the analysis record,
     * and updates conversation topics.
     */
    public function analyze(PeopleConnectMessage $message): PeopleConnectMessageAnalysis
    {
        // In a real impl this calls AiModelsHub with Intent_Detection + Contact_Analysis intents.
        // For now, we store a stub record so the pipeline is complete.
        $analysis = PeopleConnectMessageAnalysis::create([
            'message_id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'contact_id' => $message->contact_id,
            'intent' => 'unknown',
            'sentiment' => 'neutral',
            'emotional_tone' => 'neutral',
            'confidence_score' => 0.0,
            'raw_ai_response' => [],
            'status' => 'completed',
        ]);

        // Update conversation topics (upsert)
        // Stub: real implementation extracts topics from AI response
        $this->updateTopics($message, []);

        return $analysis;
    }

    protected function updateTopics(PeopleConnectMessage $message, array $detectedTopics): void
    {
        foreach ($detectedTopics as $topic) {
            PeopleConnectConversationTopic::updateOrCreate(
                [
                    'conversation_id' => $message->conversation_id,
                    'topic' => $topic['name'],
                ],
                [
                    'contact_id' => $message->contact_id,
                    'mention_count' => \DB::raw('mention_count + 1'),
                    'last_mentioned_at' => now(),
                ]
            );
        }
    }
}
