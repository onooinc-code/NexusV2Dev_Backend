<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. peopleconnect_conversations
        Schema::create('peopleconnect_conversations', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('channel', 50)->default('whatsapp');
            $table->string('provider', 50)->default('waha');
            $table->string('provider_conversation_id', 255)->nullable();
            $table->string('status', 30)->default('active');
            $table->timestamp('last_message_at')->nullable();
            $table->string('last_message_preview', 255)->nullable();
            $table->unsignedInteger('unread_count')->default(0);
            $table->string('reply_mode_effective', 20)->default('manual');
            $table->string('agent_status', 30)->nullable();
            $table->timestamps();

            $table->unique(['contact_id', 'channel', 'provider']);
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        // 2. peopleconnect_sessions
        Schema::create('peopleconnect_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('contact_id');
            $table->string('status', 20)->default('open');
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->string('closed_reason', 50)->nullable();
            $table->unsignedInteger('message_count')->default(0);
            $table->text('summary')->nullable();

            $table->foreign('conversation_id')->references('id')->on('peopleconnect_conversations')->onDelete('cascade');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });

        // 3. peopleconnect_context_snapshots
        Schema::create('peopleconnect_context_snapshots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->json('payload');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('model_id')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('peopleconnect_conversations')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('peopleconnect_sessions')->onDelete('cascade');
        });

        // 4. peopleconnect_messages
        Schema::create('peopleconnect_messages', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('session_id')->nullable();
            $table->unsignedBigInteger('contact_id');
            $table->string('sender_type', 20); // user, contact, agent, system
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->string('direction', 10); // inbound, outbound
            $table->text('body');
            $table->string('body_format', 20)->default('text');
            $table->string('status', 20)->default('queued');
            $table->string('provider', 50)->default('waha');
            $table->string('waha_message_id', 255)->nullable();
            $table->string('provider_payload_hash', 64)->nullable();
            $table->unsignedBigInteger('topic_id')->nullable();
            $table->string('intent', 100)->nullable();
            $table->string('tone', 100)->nullable();
            $table->string('sentiment', 50)->nullable();
            $table->json('emotional_baseline_snapshot')->nullable();
            $table->json('tone_mirroring_snapshot')->nullable();
            $table->unsignedBigInteger('context_snapshot_id')->nullable();
            $table->string('trace_id', 100)->nullable();
            
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('read_at')->nullable();
            $table->timestamp('failed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'waha_message_id']);
            $table->index('provider_payload_hash');
            $table->foreign('conversation_id')->references('id')->on('peopleconnect_conversations')->onDelete('cascade');
            $table->foreign('session_id')->references('id')->on('peopleconnect_sessions')->onDelete('set null');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
            $table->foreign('context_snapshot_id')->references('id')->on('peopleconnect_context_snapshots')->onDelete('set null');
        });

        // 5. peopleconnect_message_analyses
        Schema::create('peopleconnect_message_analyses', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('topic', 100)->nullable();
            $table->string('intent', 100)->nullable();
            $table->string('tone', 100)->nullable();
            $table->string('sentiment', 50)->nullable();
            $table->string('language', 10)->nullable();
            $table->string('urgency', 20)->nullable();
            $table->json('safety_flags')->nullable();
            $table->boolean('reply_needed')->default(false);
            $table->timestamp('analyzed_at')->nullable();
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('peopleconnect_messages')->onDelete('cascade');
        });

        // 6. peopleconnect_message_tags
        Schema::create('peopleconnect_message_tags', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->string('tag', 50);
            $table->timestamps();

            $table->unique(['message_id', 'tag']);
            $table->foreign('message_id')->references('id')->on('peopleconnect_messages')->onDelete('cascade');
        });

        // 7. peopleconnect_reply_drafts
        Schema::create('peopleconnect_reply_drafts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->text('body');
            $table->string('status', 20)->default('pending'); // pending, approved, rejected, sent, failed
            $table->unsignedBigInteger('context_snapshot_id')->nullable();
            $table->string('trace_id', 100)->nullable();
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->timestamp('approved_at')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamp('rejected_at')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('peopleconnect_conversations')->onDelete('cascade');
            $table->foreign('message_id')->references('id')->on('peopleconnect_messages')->onDelete('cascade');
            $table->foreign('context_snapshot_id')->references('id')->on('peopleconnect_context_snapshots')->onDelete('set null');
        });

        // 8. peopleconnect_delivery_attempts
        Schema::create('peopleconnect_delivery_attempts', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id');
            $table->unsignedInteger('attempt_number');
            $table->string('status', 20); // success, failed
            $table->json('waha_response')->nullable();
            $table->timestamp('attempted_at')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamps();

            $table->foreign('message_id')->references('id')->on('peopleconnect_messages')->onDelete('cascade');
        });

        // 9. peopleconnect_sync_runs
        Schema::create('peopleconnect_sync_runs', function (Blueprint $table) {
            $table->id();
            $table->string('type', 30); // contacts, conversations, messages
            $table->string('status', 20)->default('running'); // running, completed, failed
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedInteger('contacts_found')->default(0);
            $table->unsignedInteger('conversations_found')->default(0);
            $table->unsignedInteger('messages_found')->default(0);
            $table->json('errors')->nullable();
            $table->string('triggered_by', 50)->nullable(); // scheduler, manual
            $table->timestamps();
        });

        // 10. peopleconnect_raw_provider_events
        Schema::create('peopleconnect_raw_provider_events', function (Blueprint $table) {
            $table->id();
            $table->string('event_type', 50);
            $table->json('payload');
            $table->string('session_name', 100)->nullable();
            $table->timestamp('received_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->string('processing_status', 20)->default('pending'); // pending, processed, error
            $table->timestamps();
        });

        // 11. peopleconnect_processing_logs
        Schema::create('peopleconnect_processing_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id')->nullable();
            $table->string('event_type', 50);
            $table->text('description');
            $table->json('payload')->nullable();
            $table->timestamps();

            $table->foreign('conversation_id')->references('id')->on('peopleconnect_conversations')->onDelete('cascade');
        });

        // 12. peopleconnect_conversation_topics
        Schema::create('peopleconnect_conversation_topics', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('conversation_id');
            $table->string('name', 100);
            $table->unsignedBigInteger('first_message_id')->nullable();
            $table->unsignedBigInteger('last_message_id')->nullable();
            $table->unsignedInteger('message_count')->default(1);
            $table->timestamp('first_seen_at')->nullable();
            $table->timestamp('last_seen_at')->nullable();
            $table->timestamps();

            $table->unique(['conversation_id', 'name']);
            $table->foreign('conversation_id')->references('id')->on('peopleconnect_conversations')->onDelete('cascade');
            $table->foreign('first_message_id')->references('id')->on('peopleconnect_messages')->onDelete('set null');
            $table->foreign('last_message_id')->references('id')->on('peopleconnect_messages')->onDelete('set null');
        });

        // 13. peopleconnect_reply_mode_overrides
        Schema::create('peopleconnect_reply_mode_overrides', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('contact_id');
            $table->string('reply_mode', 20); // manual, copilot, autopilot
            $table->string('set_by', 50)->nullable();
            $table->string('reason', 255)->nullable();
            $table->timestamps();

            $table->unique('contact_id');
            $table->foreign('contact_id')->references('id')->on('contacts')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('peopleconnect_reply_mode_overrides');
        Schema::dropIfExists('peopleconnect_conversation_topics');
        Schema::dropIfExists('peopleconnect_processing_logs');
        Schema::dropIfExists('peopleconnect_raw_provider_events');
        Schema::dropIfExists('peopleconnect_sync_runs');
        Schema::dropIfExists('peopleconnect_delivery_attempts');
        Schema::dropIfExists('peopleconnect_reply_drafts');
        Schema::dropIfExists('peopleconnect_message_tags');
        Schema::dropIfExists('peopleconnect_message_analyses');
        Schema::dropIfExists('peopleconnect_messages');
        Schema::dropIfExists('peopleconnect_context_snapshots');
        Schema::dropIfExists('peopleconnect_sessions');
        Schema::dropIfExists('peopleconnect_conversations');
    }
};
