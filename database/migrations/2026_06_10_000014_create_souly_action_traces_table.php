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
        Schema::create('souly_action_traces', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->string('trace_id')->unique();
            $table->string('parsed_intent')->nullable();
            $table->string('selected_action')->nullable();
            $table->unsignedBigInteger('model_instance_id')->nullable();
            $table->unsignedBigInteger('agent_id')->nullable();
            $table->unsignedBigInteger('instruction_version_id')->nullable();
            $table->unsignedBigInteger('context_snapshot_id')->nullable();
            $table->json('tools_invoked')->nullable();
            $table->json('tasks_created')->nullable();
            $table->json('workflows_triggered')->nullable();
            $table->string('approval_decision')->nullable();
            $table->text('final_output')->nullable();
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->json('errors')->nullable();
            $table->timestamps();

            $table->foreign('message_id')
                ->references('id')->on('hedrasoul_messages')
                ->nullOnDelete();

            $table->foreign('model_instance_id')
                ->references('id')->on('ai_instances')
                ->nullOnDelete();

            $table->foreign('instruction_version_id')
                ->references('id')->on('souly_instruction_versions')
                ->nullOnDelete();

            $table->foreign('context_snapshot_id')
                ->references('id')->on('hedrasoul_context_snapshots')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souly_action_traces');
    }
};
