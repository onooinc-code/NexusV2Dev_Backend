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
        Schema::create('hedrasoul_message_mentions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('message_id')->constrained('hedrasoul_messages')->cascadeOnDelete();
            $table->string('mention_type');
            $table->string('object_id');
            $table->string('object_type');
            $table->string('display_name');
            $table->string('sensitivity')->default('public');
            $table->timestamp('resolved_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedrasoul_message_mentions');
    }
};
