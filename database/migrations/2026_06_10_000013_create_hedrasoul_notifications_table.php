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
        Schema::create('hedrasoul_notifications', function (Blueprint $table) {
            $table->id();
            $table->string('notification_type');
            $table->string('priority')->default('normal');
            $table->string('title');
            $table->text('body');
            $table->string('related_type')->nullable();
            $table->unsignedBigInteger('related_id')->nullable();
            $table->json('action_buttons')->nullable();
            $table->boolean('is_read')->default(false);
            $table->timestamp('snoozed_until')->nullable();
            $table->boolean('is_dismissed')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedrasoul_notifications');
    }
};
