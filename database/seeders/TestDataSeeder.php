<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use App\Models\Contact;
use App\Models\Agent;
use App\Models\Workflow;
use App\Models\Log;
use App\Models\Message;
use App\Models\Conversation;
use App\Models\ConversationSession;
use App\Models\Topic;
use App\Models\Memory;
use App\Models\AgentTask;
use App\Models\ContactCustomField;
use App\Models\ContactNote;
use App\Models\ContactTag;
use App\Models\AgentSkill;
use App\Models\AgentTool;
use App\Models\ContactRule;

class TestDataSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $this->command->info('Starting Test Data Generation...');
        
        DB::beginTransaction();
        try {

        // 1. Users
        $this->command->info('Creating Users...');
        $users = collect();
        for ($i = 0; $i < 10; $i++) {
            $users->push(User::factory()->create(['email' => 'user_' . uniqid() . '@example.com']));
        }

        $userIds = $users->pluck('id')->toArray();

        // 2. Contacts & Related
        $this->command->info('Creating Contacts...');
        $contacts = collect();
        $this->command->info('Starting contact generation loop...');
        for ($i = 0; $i < 50; $i++) {
            $contacts->push(Contact::factory()->create([
                'email' => 'contact_' . uniqid() . '@example.com',
                'user_id' => !empty($userIds) ? $userIds[array_rand($userIds)] : null
            ]));
        }
        
        foreach ($contacts as $contact) {
            $numFields = rand(1, 3);
            for ($i = 0; $i < $numFields; $i++) {
                ContactCustomField::factory()->create([
                    'contact_id' => $contact->id,
                    'field_key' => 'custom_field_' . $i . '_' . uniqid()
                ]);
            }
            ContactNote::factory(rand(0, 5))->create([
                'contact_id' => $contact->id,
                'user_id' => !empty($userIds) ? $userIds[array_rand($userIds)] : null
            ]);
            $numTags = rand(1, 4);
            for ($i = 0; $i < $numTags; $i++) {
                ContactTag::factory()->create([
                    'contact_id' => $contact->id,
                    'name' => 'tag_' . $i . '_' . uniqid()
                ]);
            }
            ContactRule::factory(rand(0, 2))->create(['contact_id' => $contact->id]);
            $this->command->info("Created Contact ID: {$contact->id} with related entities.");
        }
        $this->command->info('Finished Creating Contacts.');

        // 3. Agents & Related
        $this->command->info('Creating Agents...');
        $agents = Agent::factory(20)->create();
        
        foreach ($agents as $agent) {
            $numSkills = rand(2, 5);
            for ($i = 0; $i < $numSkills; $i++) {
                AgentSkill::factory()->create([
                    'agent_id' => $agent->id,
                    'name' => 'skill_' . $i . '_' . uniqid()
                ]);
            }
            $numTools = rand(1, 4);
            for ($i = 0; $i < $numTools; $i++) {
                AgentTool::factory()->create([
                    'agent_id' => $agent->id,
                    'name' => 'tool_' . $i . '_' . uniqid()
                ]);
            }
        }

        // 4. Workflows
        $this->command->info('Creating Workflows...');
        $workflows = Workflow::factory(20)->create();

        // 5. Agent Tasks
        $this->command->info('Creating Tasks...');
        foreach ($agents->random(10) as $agent) {
            AgentTask::factory(rand(3, 8))->create([
                'agent_id' => $agent->id,
            ]);
        }

        // 6. Topics & Memories
        $this->command->info('Creating Topics & Memories...');
        $topics = collect();
        for ($i = 0; $i < 15; $i++) {
            $topics->push(Topic::factory()->create([
                'slug' => 'topic_' . uniqid()
            ]));
        }


        // 7. Conversations & Messages
        $this->command->info('Creating Conversations...');
        $conversations = collect();
        $topicIds = $topics->pluck('id')->toArray();
        $contactIds = $contacts->pluck('id')->toArray();
        
        for ($i = 0; $i < 20; $i++) {
            $conversations->push(Conversation::factory()->create([
                'topic_id' => !empty($topicIds) ? $topicIds[array_rand($topicIds)] : null,
                'contact_id' => !empty($contactIds) ? $contactIds[array_rand($contactIds)] : null
            ]));
        }
        
        foreach ($conversations as $conversation) {
            $session = ConversationSession::factory()->create([
                'conversation_id' => $conversation->id,
            ]);
            
            // Reassign Memory factory for this conversation to prevent duplicate conversations
            Memory::factory(rand(0, 2))->create([
                'contact_id' => $conversation->contact_id,
                'conversation_id' => $conversation->id,
            ]);
            
            Message::factory(rand(5, 20))->create([
                'conversation_id' => $conversation->id,
            ]);
        }

        // 8. Logs
        $this->command->info('Logs will not be seeded via factory as Log does not use HasFactory.');

        DB::commit();
        $this->command->info('Test Data Generation Completed Successfully!');
        } catch (\Exception $e) {
            DB::rollBack();
            throw $e;
        }
    }
}
