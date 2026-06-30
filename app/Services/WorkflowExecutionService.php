<?php

namespace App\Services;

use App\Models\Workflow;
use App\Models\WorkflowExecution;
use App\Jobs\ExecuteWorkflowStep;
use Illuminate\Support\Facades\Log;

class WorkflowExecutionService
{
    /**
     * Start a new workflow execution.
     */
    public function start(Workflow $workflow, array $initialPayload = [])
    {
        // Find the trigger/start node
        $nodes = is_string($workflow->nodes) ? json_decode($workflow->nodes, true) : $workflow->nodes;
        $edges = is_string($workflow->edges) ? json_decode($workflow->edges, true) : $workflow->edges;

        if (!$nodes) {
            throw new \Exception("Workflow has no nodes.");
        }

        $startNode = collect($nodes)->firstWhere('type', 'triggerNode') ?? $nodes[0];

        $execution = WorkflowExecution::create([
            'workflow_id' => $workflow->id,
            'status' => 'running',
            'current_node_id' => $startNode['id'],
            'state' => $initialPayload,
            'started_at' => now(),
        ]);

        ExecuteWorkflowStep::dispatch($execution, $startNode['id']);

        return $execution;
    }

    /**
     * Execute a specific node and transition to the next.
     */
    public function executeNode(WorkflowExecution $execution, string $nodeId)
    {
        $workflow = $execution->workflow;
        $nodes = is_string($workflow->nodes) ? json_decode($workflow->nodes, true) : $workflow->nodes;
        $edges = is_string($workflow->edges) ? json_decode($workflow->edges, true) : $workflow->edges;

        $node = collect($nodes)->firstWhere('id', $nodeId);

        if (!$node) {
            $execution->update(['status' => 'failed', 'error' => "Node $nodeId not found."]);
            return;
        }

        try {
            // Simulate node execution logic based on type (API call, logic, condition, etc)
            $result = $this->runNodeLogic($node, $execution->state);

            // Update execution state
            $newState = array_merge($execution->state ?? [], [$nodeId => $result]);
            $execution->update(['state' => $newState]);

            // Find next nodes
            $nextEdges = collect($edges)->where('source', $nodeId);

            if ($nextEdges->isEmpty()) {
                // Workflow completed
                $execution->update(['status' => 'completed', 'completed_at' => now()]);
                return;
            }

            // Dispatch next steps (handling parallel branches)
            foreach ($nextEdges as $edge) {
                ExecuteWorkflowStep::dispatch($execution, $edge['target']);
            }

        } catch (\Exception $e) {
            Log::error("Workflow execution failed at node {$nodeId}: " . $e->getMessage());
            $execution->update(['status' => 'failed', 'error' => $e->getMessage()]);
        }
    }

    private function runNodeLogic(array $node, ?array $state)
    {
        // Replace with actual handler logic based on $node['type']
        return ['executed_at' => now()->toIso8601String(), 'status' => 'success'];
    }
}
