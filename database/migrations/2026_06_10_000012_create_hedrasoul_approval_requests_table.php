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
        Schema::create('hedrasoul_approval_requests', function (Blueprint $table) {
            $table->id();
            $table->enum('source_type', ['task', 'workflow', 'command', 'agent']);
            $table->string('source_id')->nullable();
            $table->text('action_description');
            $table->json('inputs');
            $table->text('expected_side_effects')->nullable();
            $table->string('risk_level')->default('low');
            $table->decimal('cost_estimate', 10, 6)->nullable();
            $table->unsignedBigInteger('context_snapshot_id')->nullable();
            $table->text('agent_reasoning')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'deferred'])->default('pending');
            $table->unsignedBigInteger('decided_by')->nullable();
            $table->timestamp('decided_at')->nullable();
            $table->text('decision_notes')->nullable();
            $table->timestamps();

            $table->foreign('context_snapshot_id')
                ->references('id')->on('hedrasoul_context_snapshots')
                ->nullOnDelete();

            $table->foreign('decided_by')
                ->references('id')->on('users')
                ->nullOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('hedrasoul_approval_requests');
    }
};
