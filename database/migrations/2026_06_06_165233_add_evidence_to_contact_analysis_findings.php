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
        Schema::table('contact_analysis_findings', function (Blueprint $table) {
            if (! \Illuminate\Support\Facades\Schema::hasColumn('contact_analysis_findings', 'evidence_references')) {
                $table->json('evidence_references')->nullable();
            }
            if (! \Illuminate\Support\Facades\Schema::hasColumn('contact_analysis_findings', 'source_message_ids')) {
                $table->json('source_message_ids')->nullable();
            }
        });

    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contact_analysis_findings', function (Blueprint $table) {
            $table->dropColumn(['evidence_references', 'source_message_ids']);
        });
    }
};
