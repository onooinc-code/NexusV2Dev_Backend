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
        Schema::create('hedra_clone_sources', function (Blueprint $table) {
            $table->id();
            $table->string('source_type');
            $table->text('content');
            $table->decimal('confidence', 5, 4)->default(1.0);
            $table->string('sensitivity')->default('private');
            $table->decimal('freshness_score', 5, 4)->nullable();
            $table->string('visibility_scope')->default('private');
            $table->string('validation_status')->default('pending');
            $table->text('provenance')->nullable();
            $table->boolean('is_archived')->default(false);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedra_clone_sources');
    }
};
