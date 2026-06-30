<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Models\Contact;
use App\Models\AgentTask;
use App\Models\Workflow;
use App\Services\HedraSoul\SoulyContextAssembler;
use App\Services\HedraSoul\HedraMemoryService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HedraSoulMiscController extends Controller
{
    public function __construct(
        protected SoulyContextAssembler $contextAssembler,
        protected HedraMemoryService $memoryService
    ) {}

    /**
     * Search for mentions (contacts, tasks, workflows, etc.)
     * GET /hedrasoul/mentions/search
     */
    public function mentionsSearch(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:100',
            'type' => 'sometimes|in:contact,task,workflow,agent',
        ]);

        $results = [];

        // Search contacts
        if (!$request->has('type') || $request->type === 'contact') {
            $contacts = Contact::where('name', 'like', "%{$validated['q']}%")
                ->limit(5)
                ->get()
                ->map(fn($c) => [
                    'id' => $c->id,
                    'type' => 'contact',
                    'display_name' => $c->name,
                    'meta' => ['phone' => $c->phone ?? null],
                ]);
            $results = array_merge($results, $contacts->toArray());
        }

        // Search tasks
        if (!$request->has('type') || $request->type === 'task') {
            $tasks = AgentTask::where('title', 'like', "%{$validated['q']}%")
                ->limit(5)
                ->get()
                ->map(fn($t) => [
                    'id' => $t->id,
                    'type' => 'task',
                    'display_name' => $t->title,
                    'meta' => ['status' => $t->status],
                ]);
            $results = array_merge($results, $tasks->toArray());
        }

        // Search workflows
        if (!$request->has('type') || $request->type === 'workflow') {
            $workflows = Workflow::where('name', 'like', "%{$validated['q']}%")
                ->limit(5)
                ->get()
                ->map(fn($w) => [
                    'id' => $w->id,
                    'type' => 'workflow',
                    'display_name' => $w->name,
                    'meta' => ['status' => $w->status ?? null],
                ]);
            $results = array_merge($results, $workflows->toArray());
        }

        return response()->json(['results' => $results]);
    }

    /**
     * Preview context snapshot without persisting
     * POST /hedrasoul/context/preview
     */
    public function contextPreview(Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'required|integer|exists:hedrasoul_sessions,id',
            'message_body' => 'required|string',
            'mentions' => 'sometimes|array',
        ]);

        $session = HedrasoulSession::findOrFail($validated['session_id']);

        // Create a temporary message for context assembly
        $tempMessage = new HedrasoulMessage([
            'body' => $validated['message_body'],
            'session_id' => $session->id,
        ]);

        $snapshot = $this->contextAssembler->assemble($session, $tempMessage);

        return response()->json([
            'snapshot_preview' => [
                'token_estimate' => $snapshot->token_estimate,
                'risk_classification' => $snapshot->risk_classification,
                'excluded_items' => $snapshot->excluded_items,
                'messages_included' => $snapshot->payload['message_count'] ?? 0,
            ],
        ]);
    }

    /**
     * Full-text search across sessions and messages
     * GET /hedrasoul/search
     */
    public function search(Request $request)
    {
        $validated = $request->validate([
            'q' => 'required|string|min:2|max:200',
        ]);

        $query = $validated['q'];
        $user = Auth::user();

        $sessions = HedrasoulSession::where('user_id', $user->id)
            ->where(function ($q) use ($query) {
                $q->where('title', 'like', "%{$query}%")
                  ->orWhere('topic', 'like', "%{$query}%")
                  ->orWhere('summary', 'like', "%{$query}%");
            })
            ->limit(10)
            ->get();

        $messages = HedrasoulMessage::whereIn('session_id', function ($q) use ($user) {
            $q->select('id')->from('hedrasoul_sessions')->where('user_id', $user->id);
        })
            ->where('body', 'like', "%{$query}%")
            ->limit(20)
            ->get();

        return response()->json([
            'sessions' => $sessions,
            'messages' => $messages,
        ]);
    }

    /**
     * Get analytics: session count, task commands, approval stats, etc.
     * GET /hedrasoul/analytics
     */
    public function analytics(Request $request)
    {
        $user = Auth::user();

        $sessionCount = HedrasoulSession::where('user_id', $user->id)->count();
        $activeSessionCount = HedrasoulSession::where('user_id', $user->id)
            ->where('status', 'active')->count();
        $messageCount = HedrasoulMessage::whereIn('session_id', function ($q) use ($user) {
            $q->select('id')->from('hedrasoul_sessions')->where('user_id', $user->id);
        })->count();

        $taskCommands = HedrasoulMessage::whereIn('session_id', function ($q) use ($user) {
            $q->select('id')->from('hedrasoul_sessions')->where('user_id', $user->id);
        })
            ->where('intent', 'create_task')
            ->count();

        $approvalRequests = \App\Models\HedrasoulApprovalRequest::whereIn('context_snapshot_id', function ($q) use ($user) {
            $q->select('id')->from('hedrasoul_context_snapshots')
              ->whereIn('session_id', function ($sq) use ($user) {
                  $sq->select('id')->from('hedrasoul_sessions')->where('user_id', $user->id);
              });
        })->selectRaw('status, COUNT(*) as count')->groupBy('status')->get();

        return response()->json([
            'sessions' => [
                'total' => $sessionCount,
                'active' => $activeSessionCount,
            ],
            'messages' => [
                'total' => $messageCount,
            ],
            'commands' => [
                'task_creation' => $taskCommands,
            ],
            'approvals' => $approvalRequests->mapWithKeys(fn($r) => [$r->status => $r->count]),
        ]);
    }

    /**
     * Get token/cost usage summary for the authenticated user
     * GET /hedrasoul/usage
     */
    public function usage()
    {
        $user = Auth::user();

        $messages = HedrasoulMessage::whereIn('session_id', function ($q) use ($user) {
            $q->select('id')->from('hedrasoul_sessions')->where('user_id', $user->id);
        })->get();

        $totalTokens = $messages->sum('token_count');
        $totalCost = $messages->sum('cost_usd');

        return response()->json([
            'total_tokens' => $totalTokens,
            'total_cost_usd' => $totalCost,
            'message_count' => $messages->count(),
            'average_cost_per_message' => $messages->count() > 0 
                ? $totalCost / $messages->count() 
                : 0,
        ]);
    }
}
