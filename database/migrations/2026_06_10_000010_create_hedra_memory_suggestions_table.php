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
        Schema::create('hedra_memory_suggestions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('source_message_id')->nullable();
            $table->text('content');
            $table->string('memory_type');
            $table->decimal('confidence', 5, 4)->default(1.0);
            $table->enum('status', ['pending', 'approved', 'rejected'])->default('pending');
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->foreign('source_message_id')
                ->references('id')->on('hedrasoul_messages')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedra_memory_suggestions');
    }
};
