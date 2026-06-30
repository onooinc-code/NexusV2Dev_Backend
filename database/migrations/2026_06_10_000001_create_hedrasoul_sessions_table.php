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
        Schema::create('hedrasoul_sessions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('title');
            $table->enum('status', ['active', 'archived', 'closed'])->default('active');
            $table->string('topic')->nullable();
            $table->unsignedInteger('task_count')->default(0);
            $table->unsignedInteger('approval_count')->default(0);
            $table->unsignedBigInteger('instruction_version_id')->nullable();
            $table->string('last_autonomy_mode')->nullable();
            $table->timestamp('opened_at')->nullable();
            $table->timestamp('closed_at')->nullable();
            $table->text('summary')->nullable();
            $table->timestamps();
            // FK to souly_instruction_versions added in migration 000015 (after that table exists)
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedrasoul_sessions');
    }
};
