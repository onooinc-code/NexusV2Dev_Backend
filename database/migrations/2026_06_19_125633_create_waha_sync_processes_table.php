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
        Schema::create('waha_sync_processes', function (Blueprint $table) {
            $table->id();
            $table->string('type'); // sync_contacts, sync_messages, analyze_messages
            $table->string('status')->default('pending'); // pending, running, paused, failed, completed
            $table->integer('progress')->default(0);
            $table->integer('total_items')->default(0);
            $table->integer('processed_items')->default(0);
            $table->integer('failed_items')->default(0);
            $table->string('last_cursor_id')->nullable();
            $table->json('config')->nullable();
            $table->json('errors')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('waha_sync_processes');
    }
};
