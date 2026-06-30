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
        Schema::create('souly_action_policies', function (Blueprint $table) {
            $table->id();
            $table->string('policy_type');
            $table->string('rule_key');
            $table->string('rule_value');
            $table->string('applies_to_mode');
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('souly_action_policies');
    }
};
