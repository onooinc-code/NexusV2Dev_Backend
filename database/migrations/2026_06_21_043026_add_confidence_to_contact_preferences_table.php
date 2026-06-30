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
        Schema::table('contact_preferences', function (Blueprint $table) {
            $table->decimal('confidence', 5, 2)->nullable()->default(1.0);
            $table->integer('inferred_from_count')->nullable()->default(1);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_preferences', function (Blueprint $table) {
            $table->dropColumn(['confidence', 'inferred_from_count']);
        });
    }
};
