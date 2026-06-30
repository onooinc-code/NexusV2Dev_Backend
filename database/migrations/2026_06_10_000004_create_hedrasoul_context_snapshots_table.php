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
        Schema::create('hedrasoul_context_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('session_id')->constrained('hedrasoul_sessions')->cascadeOnDelete();
            $table->unsignedBigInteger('message_id')->nullable();
            $table->unsignedBigInteger('instruction_version_id')->nullable();
            $table->unsignedBigInteger('persona_id')->nullable();
            $table->unsignedBigInteger('model_instance_id')->nullable();
            $table->json('payload');
            $table->unsignedInteger('token_estimate')->default(0);
            $table->string('risk_classification')->nullable();
            $table->json('excluded_items')->nullable();
            $table->timestamp('created_at')->nullable();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedrasoul_context_snapshots');
    }
};
