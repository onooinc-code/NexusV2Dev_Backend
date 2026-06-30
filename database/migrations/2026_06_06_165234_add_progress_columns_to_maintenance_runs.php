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
        Schema::table('contact_memory_maintenance_runs', function (Blueprint $table) {
            $table->integer('processed_count')->default(0);
            $table->integer('error_count')->default(0);
            $table->decimal('completion_percentage', 5, 2)->default(0.00);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_memory_maintenance_runs', function (Blueprint $table) {
            $table->dropColumn(['processed_count', 'error_count', 'completion_percentage']);
        });
    }
};
