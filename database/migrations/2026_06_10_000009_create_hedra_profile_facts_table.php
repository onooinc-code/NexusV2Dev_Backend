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
        Schema::create('hedra_profile_facts', function (Blueprint $table) {
            $table->id();
            $table->enum('memory_type', [
                'working',
                'episodic',
                'semantic',
                'structured',
                'graph',
                'preference',
                'tone_style',
                'decision',
                'boundary',
                'correction',
            ]);
            $table->text('content');
            $table->decimal('confidence', 5, 4)->default(1.0);
            $table->json('evidence')->nullable();
            $table->string('sensitivity')->default('private');
            $table->string('visibility_scope')->default('private');
            $table->boolean('is_approved')->default(false);
            $table->timestamp('approved_at')->nullable();
            $table->unsignedInteger('version')->default(1);
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedra_profile_facts');
    }
};
