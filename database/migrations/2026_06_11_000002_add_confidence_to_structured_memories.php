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
        Schema::table('structured_memories', function (Blueprint $table) {
            $table->decimal('confidence', 5, 2)->default(0.80)->after('metadata');
            $table->string('status', 30)->default('active')->after('confidence');
            $table->timestamp('last_reinforced_at')->nullable()->after('status');
            $table->softDeletes();

            $table->index(['contact_id', 'confidence']);
            $table->index(['contact_id', 'fact_type', 'status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('structured_memories', function (Blueprint $table) {
            $table->dropIndex(['contact_id', 'confidence']);
            $table->dropIndex(['contact_id', 'fact_type', 'status']);

            $table->dropSoftDeletes();
            $table->dropColumn('last_reinforced_at');
            $table->dropColumn('status');
            $table->dropColumn('confidence');
        });
    }
};
