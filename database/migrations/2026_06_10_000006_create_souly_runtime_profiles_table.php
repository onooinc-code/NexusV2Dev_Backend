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
        Schema::create('souly_runtime_profiles', function (Blueprint $table) {
            $table->id();
            $table->enum('autonomy_mode', [
                'chat_only',
                'copilot',
                'operator',
                'autopilot_limited',
                'emergency_paused',
            ])->default('chat_only');
            $table->unsignedBigInteger('active_model_instance_id')->nullable();
            $table->unsignedBigInteger('active_instruction_version_id')->nullable();
            $table->unsignedBigInteger('active_persona_id')->nullable();
            $table->json('tool_permissions')->nullable();
            $table->boolean('memory_access')->default(false);
            $table->boolean('contact_access')->default(false);
            $table->boolean('task_execution_access')->default(false);
            $table->boolean('workflow_execution_access')->default(false);
            $table->boolean('external_messaging_access')->default(false);
            $table->boolean('is_quarantined')->default(false);
            $table->timestamps();

            $table->foreign('active_model_instance_id')
                ->references('id')->on('ai_instances')
                ->nullOnDelete();

            $table->foreign('active_instruction_version_id')
                ->references('id')->on('souly_instruction_versions')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souly_runtime_profiles');
    }
};
