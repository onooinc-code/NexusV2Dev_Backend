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
        Schema::create('souly_instruction_versions', function (Blueprint $table) {
            $table->id();
            $table->unsignedInteger('version_number');
            $table->enum('status', ['draft', 'active', 'archived'])->default('draft');
            $table->json('content');
            $table->text('change_reason')->nullable();
            $table->timestamp('activated_at')->nullable();
            $table->unsignedBigInteger('activated_by')->nullable();
            $table->timestamps();

            $table->foreign('activated_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souly_instruction_versions');
    }
};
