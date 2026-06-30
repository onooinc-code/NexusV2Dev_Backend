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
        Schema::table('memories', function (Blueprint $table) {
            $table->string('source_type', 50)->nullable()->after('source');
            $table->boolean('is_extracted')->default(false)->after('source_type');

            $table->index(['contact_id', 'is_extracted']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('memories', function (Blueprint $table) {
            $table->dropIndex(['contact_id', 'is_extracted']);
            
            $table->dropColumn('is_extracted');
            $table->dropColumn('source_type');
        });
    }
};
