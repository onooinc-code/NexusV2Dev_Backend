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
        if (Schema::hasTable('contact_memory_versions')) {
            Schema::dropIfExists('contact_memory_versions');
        }

        Schema::create('contact_memory_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('memory_id');
            $table->string('memory_type', 50)->default('structured');
            $table->unsignedBigInteger('contact_id')->nullable();
            $table->integer('version')->default(1);

            $table->json('previous_content')->nullable();
            $table->json('new_content')->nullable();
            $table->json('diff')->nullable();

            $table->decimal('old_confidence', 5, 2)->nullable();
            $table->decimal('new_confidence', 5, 2)->nullable();

            $table->string('source', 50)->nullable();
            $table->unsignedBigInteger('actor_id')->nullable();

            $table->timestamp('created_at')->useCurrent();
            // Note: no updated_at since versions are immutable

            $table->index(['memory_id', 'memory_type']);
            $table->index(['contact_id', 'created_at']);
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('contact_memory_versions');
    }
};
