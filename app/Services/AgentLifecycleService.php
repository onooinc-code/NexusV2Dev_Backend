<?php

namespace App\Services;

use App\Models\Agent;
use App\Events\AgentRegistered;
use Illuminate\Support\Facades\Log;

class AgentLifecycleService
{
    /**
     * Valid state transitions: current → allowed next states.
     */
    protected array $stateTransitions = [
        Agent::STATUS_IDLE      => [Agent::STATUS_RUNNING],
        Agent::STATUS_RUNNING   => [Agent::STATUS_IDLE, Agent::STATUS_PAUSED, Agent::STATUS_ERROR, Agent::STATUS_COMPLETED],
        Agent::STATUS_PAUSED    => [Agent::STATUS_RUNNING, Agent::STATUS_IDLE, Agent::STATUS_ERROR],
        Agent::STATUS_ERROR     => [Agent::STATUS_IDLE, Agent::STATUS_RUNNING],
        Agent::STATUS_COMPLETED => [Agent::STATUS_IDLE],
        // Legacy operational statuses
        Agent::STATUS_ACTIVE      => [Agent::STATUS_INACTIVE, Agent::STATUS_QUARANTINED],
        Agent::STATUS_INACTIVE    => [Agent::STATUS_ACTIVE],
        Agent::STATUS_QUARANTINED => [Agent::STATUS_ACTIVE],
    ];

    /**
     * Optional LogService — resolved from container if available, silent otherwise.
     */
    protected ?LogService $logService;

    public function __construct(?LogService $logService = null)
    {
        $this->logService = $logService ?? app(LogService::class);
    }

    // ─── Lifecycle Transitions ─────────────────────────────────────────────

    /**
     * Initialize an agent for execution: status → running, increment counters.
     */
    public function initialize(Agent $agent): Agent
    {
        $agent->update([
            'status'           => Agent::STATUS_RUNNING,
            'last_executed_at' => now(),
        ]);
        $agent->increment('execution_count');

        $this->log('info', "Agent initialized: {$agent->name} (ID: {$agent->id})", $agent);

        return $agent->fresh();
    }

    /**
     * Transition a running agent back to idle.
     */
    public function idle(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_IDLE);
    }

    /**
     * Pause a running agent.
     */
    public function pause(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_PAUSED);
    }

    /**
     * Resume a paused agent.
     */
    public function resume(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_RUNNING);
    }

    /**
     * Mark agent as successfully completed: increments success_count, status → idle.
     */
    public function complete(Agent $agent): Agent
    {
        $agent->increment('success_count');
        $agent->update(['status' => Agent::STATUS_IDLE]);

        $this->log('info', "Agent completed: {$agent->name}", $agent);

        return $agent->fresh();
    }

    /**
     * Mark agent as failed: increments error_count, status → error.
     */
    public function fail(Agent $agent, ?string $errorMessage = null): Agent
    {
        $agent->increment('error_count');
        $agent->update(['status' => Agent::STATUS_ERROR]);

        $this->log('error', "Agent failed: {$agent->name}" . ($errorMessage ? " — {$errorMessage}" : ''), $agent);

        return $agent->fresh();
    }

    /**
     * Activate an agent (operational status).
     */
    public function activate(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_ACTIVE);
    }

    /**
     * Deactivate an agent.
     */
    public function deactivate(Agent $agent): Agent
    {
        return $this->transition($agent, Agent::STATUS_INACTIVE);
    }

    /**
     * Register a brand-new agent and emit AgentRegistered event.
     */
    public function register(Agent $agent): Agent
    {
        try {
            event(new AgentRegistered($agent));
        } catch (\Throwable $e) {
            Log::warning("Could not emit AgentRegistered event: {$e->getMessage()}");
        }

        $this->log('info', "Agent registered: {$agent->name}", $agent);

        return $agent;
    }

    /**
     * Generic state transition with guard.
     */
    public function transition(Agent $agent, string $newStatus): Agent
    {
        $currentStatus = $agent->status;

        if (!isset($this->stateTransitions[$currentStatus])) {
            throw new \InvalidArgumentException("Invalid current status: {$currentStatus}");
        }

        if (!in_array($newStatus, $this->stateTransitions[$currentStatus])) {
            throw new \InvalidArgumentException(
                "Invalid state transition from {$currentStatus} to {$newStatus}"
            );
        }

        $agent->update(['status' => $newStatus]);

        $this->log('info', "Agent transition: {$agent->name} [{$currentStatus} → {$newStatus}]", $agent);

        return $agent->fresh();
    }

    public function canTransition(Agent $agent, string $newStatus): bool
    {
        return isset($this->stateTransitions[$agent->status]) &&
               in_array($newStatus, $this->stateTransitions[$agent->status]);
    }

    public function getAvailableTransitions(Agent $agent): array
    {
        return $this->stateTransitions[$agent->status] ?? [];
    }

    public function getLifecycleState(?Agent $agent = null): array
    {
        if ($agent === null) {
            // Return the full state transition map (used by tests / introspection)
            return $this->stateTransitions;
        }

        return [
            'status'                => $agent->status,
            'available_transitions' => $this->getAvailableTransitions($agent),
            'execution_count'       => $agent->execution_count,
            'success_count'         => $agent->success_count,
            'error_count'           => $agent->error_count,
            'last_executed_at'      => $agent->last_executed_at?->toISOString(),
        ];
    }

    // ─── Internal ─────────────────────────────────────────────────────────

    private function log(string $level, string $message, Agent $agent): void
    {
        try {
            $this->logService?->{$level}($message, [
                'channel'      => 'agent',
                'type'         => 'lifecycle',
                'related_id'   => $agent->id,
                'related_type' => Agent::class,
            ]);
        } catch (\Throwable) {
            // Never fail a lifecycle transition due to logging errors
        }
    }
}