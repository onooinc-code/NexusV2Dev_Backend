<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\WorkflowSchedule;
use App\Models\ProactiveTrigger;
use App\Models\NotificationLog;
use App\Models\Workflow;
use App\Models\Contact;

class HubTestSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // 1. Hedrasoul
        $session = HedrasoulSession::create([
            'title' => 'System Health Diagnostic',
            'status' => 'active',
            'topic' => 'diagnostics',
            'task_count' => 3,
            'approval_count' => 0,
            'opened_at' => now(),
            'summary' => 'Running deep system diagnostics and health checks on all upstream services.'
        ]);

        HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'user',
            'body' => 'Can you check the current status of the database and web servers?',
            'status' => 'sent',
            'intent' => 'query',
            'created_at' => now()->subMinutes(5)
        ]);

        HedrasoulMessage::create([
            'session_id' => $session->id,
            'sender_type' => 'agent',
            'body' => 'I have initiated a diagnostic check. Database latency is currently 15ms and web servers are operating within normal parameters. No bottlenecks detected.',
            'status' => 'delivered',
            'intent' => 'inform',
            'created_at' => now()->subMinutes(4)
        ]);

        // 2. Scheduler
        // Need a workflow first if none exists
        $workflow = Workflow::firstOrCreate(
            ['key' => 'nightly_backup', 'name' => 'Nightly Backup'],
            ['description' => 'Backup all critical data tables', 'status' => 'active']
        );

        WorkflowSchedule::create([
            'workflow_id' => $workflow->id,
            'cron_expression' => '0 2 * * *',
            'is_active' => true,
            'last_run_at' => now()->subHours(10),
            'next_run_at' => now()->addHours(14),
        ]);

        // 3. Proactive AI
        ProactiveTrigger::create([
            'trigger_type' => 'sentiment_drop',
            'next_run_at' => now()->addMinutes(30),
            'context_payload' => json_encode(['contact_id' => 1, 'reason' => 'Multiple negative messages detected']),
            'status' => 'pending'
        ]);

        $contact = Contact::first();
        if ($contact) {
            NotificationLog::create([
                'contact_id' => $contact->id,
                'channel' => 'waha',
                'recipient' => $contact->phone ?? '1234567890',
                'subject' => 'Follow up on support ticket',
                'body' => 'Hi, we noticed you had some issues earlier. Is everything resolved?',
                'status' => 'sent'
            ]);
        }
    }
}
