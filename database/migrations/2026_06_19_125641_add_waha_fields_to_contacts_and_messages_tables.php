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
        Schema::table('contacts', function (Blueprint $table) {
            $table->string('waha_contact_id')->nullable()->index();
            $table->json('waha_sync_metadata')->nullable();
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->string('waha_message_id')->nullable()->index();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('contacts', function (Blueprint $table) {
            $table->dropColumn(['waha_contact_id', 'waha_sync_metadata']);
        });

        Schema::table('contact_messages', function (Blueprint $table) {
            $table->dropColumn('waha_message_id');
        });
    }
};
