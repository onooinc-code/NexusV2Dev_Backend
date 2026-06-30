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
        Schema::table('contact_analysis_runs', function (Blueprint $table) {
            // Add error_message without ->after() since completed_at may not exist in all environments
            if (! \Illuminate\Support\Facades\Schema::hasColumn('contact_analysis_runs', 'error_message')) {
                $table->text('error_message')->nullable();
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_analysis_runs', function (Blueprint $table) {
            $table->dropColumn('error_message');
        });
    }
};
