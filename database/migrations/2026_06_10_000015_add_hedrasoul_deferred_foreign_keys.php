<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Adds foreign keys that could not be declared at table-creation time due to
 * forward references between HedraSoulHub tables:
 *
 *  - hedrasoul_sessions.instruction_version_id  → souly_instruction_versions
 *  - hedrasoul_messages.context_snapshot_id     → hedrasoul_context_snapshots
 */
return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('hedrasoul_sessions', function (Blueprint $table) {
            $table->foreign('instruction_version_id')
                ->references('id')->on('souly_instruction_versions')
                ->nullOnDelete();
        });

        Schema::table('hedrasoul_messages', function (Blueprint $table) {
            $table->foreign('context_snapshot_id')
                ->references('id')->on('hedrasoul_context_snapshots')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('hedrasoul_messages', function (Blueprint $table) {
            $table->dropForeign(['context_snapshot_id']);
        });

        Schema::table('hedrasoul_sessions', function (Blueprint $table) {
            $table->dropForeign(['instruction_version_id']);
        });
    }
};
