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
        Schema::create('hedrasoul_messages', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('hedrasoul_sessions')->cascadeOnDelete();
            $table->enum('sender_type', ['user', 'agent', 'system']);
            $table->unsignedBigInteger('sender_id')->nullable();
            $table->text('body');
            $table->string('body_format')->default('text');
            $table->string('status')->default('pending');
            $table->string('intent')->nullable();
            $table->string('topic')->nullable();
            $table->string('tone')->nullable();
            $table->string('sentiment')->nullable();
            $table->string('risk_level')->nullable();
            $table->unsignedBigInteger('context_snapshot_id')->nullable();
            $table->string('trace_id')->nullable();
            $table->unsignedBigInteger('model_instance_id')->nullable();
            $table->unsignedInteger('token_count')->default(0);
            $table->decimal('cost_usd', 10, 6)->nullable();
            $table->boolean('is_streaming')->default(false);
            $table->timestamps();

            $table->foreign('model_instance_id')
                ->references('id')->on('ai_instances')
                ->nullOnDelete();
            // FK to hedrasoul_context_snapshots added in migration 000015 (after that table exists)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedrasoul_messages');
    }
};
