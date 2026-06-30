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
        Schema::create('hedra_memory_versions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('fact_id')->constrained('hedra_profile_facts')->cascadeOnDelete();
            $table->text('content');
            $table->unsignedInteger('version_number');
            $table->unsignedBigInteger('changed_by')->nullable();
            $table->text('change_reason')->nullable();
            $table->timestamps();

            $table->foreign('changed_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedra_memory_versions');
    }
};
