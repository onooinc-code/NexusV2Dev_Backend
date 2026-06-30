<?php

namespace App\Http\Controllers\Web;

use App\Http\Controllers\Controller;
use App\Models\Contact;
use App\Models\Agent;
use App\Models\AIProvider;
use App\Models\AIModel;
use App\Models\Setting;
use App\Models\WorkflowExecution;
use Illuminate\Http\Request;

class HubController extends Controller
{
    public function dashboard()
    {
        $totalContacts      = Contact::count();
        $contactDelta       = Contact::where('created_at', '>=', now()->startOfDay())->count();

        $activeExecutes     = WorkflowExecution::whereIn('status', ['running', 'pending'])->count();
        $activeTasksCount   = 0;
        try {
            $activeTasksCount = \App\Models\AgentTask::whereIn('status', ['running', 'in_progress', 'queued', 'pending'])->count();
        } catch (\Exception $e) {}

        $agentCount         = Agent::count();
        $onlineAgentsCount  = Agent::where('status', 'active')->count();
        $totalAgentsCount   = $agentCount;
        
        $activeAgent = Agent::where('status', 'active')->first() ?: Agent::first();
        $activeAgentModel = $activeAgent ? strtoupper($activeAgent->model) : 'GEMINI';

        $memoryCount        = 0;
        $memoryDelta        = 0;
        try {
            $memoryCount = \DB::table('memories')->count();
            $memoryDelta = \DB::table('memories')->where('created_at', '>=', now()->startOfDay())->count();
        } catch (\Exception $e) {}

        // Recent contacts for dashboard panel
        $recentContacts = Contact::orderBy('updated_at', 'desc')->take(6)->get();

        // Agents for status panel
        $agents = Agent::orderBy('status', 'asc')->take(6)->get();

        // Upcoming schedules
        $upcomingSchedules = [];
        try {
            $upcomingSchedules = \App\Models\WorkflowSchedule::where('is_active', true)
                ->orderBy('next_run_at', 'asc')
                ->take(5)
                ->get();
        } catch (\Exception $e) {}

        // Recent activity logs for telemetry (using logs table)
        $recentLogs = [];
        try {
            $recentLogs = \DB::table('logs')
                ->orderBy('created_at', 'desc')
                ->take(20)
                ->get()
                ->reverse()
                ->values();
        } catch (\Exception $e) {}

        return view('hubs.dashboard', compact(
            'totalContacts', 'contactDelta', 'activeExecutes', 'activeTasksCount',
            'agentCount', 'onlineAgentsCount', 'totalAgentsCount', 'activeAgentModel',
            'memoryCount', 'memoryDelta', 'recentContacts', 'agents',
            'upcomingSchedules', 'recentLogs'
        ));
    }
    public function contacts(\Illuminate\Http\Request $request)
    {
        $totalContacts = Contact::count();
        $wahaContacts = Contact::whereNotNull('waha_contact_id')->count();
        $autopilotCount = Contact::where('reply_mode_override', 'autopilot')->count();
        $copilotCount = Contact::where('reply_mode_override', 'copilot')->count();

        $query = Contact::query();

        if ($request->filled('search')) {
            $search = $request->search;
            $query->where(function($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('phone', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%")
                  ->orWhere('company', 'like', "%{$search}%")
                  ->orWhere('title', 'like', "%{$search}%");
            });
        }

        if ($request->filled('mode') && $request->mode !== 'all') {
            if ($request->mode === 'manual') {
                $query->where(function($q) {
                    $q->where('reply_mode_override', 'manual')->orWhereNull('reply_mode_override');
                });
            } else {
                $query->where('reply_mode_override', $request->mode);
            }
        }

        if ($request->filled('waha') && $request->waha == '1') {
            $query->whereNotNull('waha_contact_id');
        }

        if ($request->filled('favorites') && $request->favorites == '1') {
            $user = $request->user();
            if ($user) {
                $query->whereHas('favoritedBy', function($q) use ($user) {
                    $q->where('users.id', $user->id);
                });
            }
        }

        $contacts = $query->orderBy('created_at', 'desc')->paginate(24)->withQueryString();

        return view('hubs.contacts', compact('contacts', 'totalContacts', 'wahaContacts', 'autopilotCount', 'copilotCount'));
    }
    public function contactProfile($id)
    {
        $contact = Contact::findOrFail($id);
        
        $auditEvents = \DB::table('contact_audit_events')
            ->where('contact_id', $contact->id)
            ->orderBy('created_at', 'desc')
            ->take(50)
            ->get();
            
        $stats = [
            'total_messages' => \App\Models\ContactMessage::where('contact_id', $contact->id)->count(),
            'inbound' => \App\Models\ContactMessage::where('contact_id', $contact->id)->where('direction', 'inbound')->count(),
            'outbound' => \App\Models\ContactMessage::where('contact_id', $contact->id)->where('direction', 'outbound')->count(),
            'has_media' => \App\Models\ContactMessage::where('contact_id', $contact->id)->whereNotNull('attachments_metadata')->count(),
        ];

        $messages = \App\Models\ContactMessage::where('contact_id', $contact->id)
            ->orderBy('source_timestamp', 'desc')
            ->orderBy('created_at', 'desc')
            ->paginate(100, ['*'], 'msg_page');

        return view('hubs.contact-profile', compact('contact', 'auditEvents', 'stats', 'messages'));
    }

    public function storeContact(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string|max:255',
            'email' => 'nullable|email',
            'phone' => 'nullable|string',
            'role' => 'nullable|string',
            'company' => 'nullable|string',
        ]);

        $contact = Contact::create($validated);
        
        return response()->json(['success' => true, 'contact' => $contact]);
    }

    public function agents()
    {
        $agents = Agent::all();
        return view('hubs.agents', compact('agents'));
    }

    public function storeAgent(Request $request)
    {
        $validated = $request->validate([
            'name' => 'required|string',
            'role' => 'required|string',
            'model' => 'required|string',
            'system_prompt' => 'nullable|string',
        ]);
        
        $validated['status'] = 'draft';
        $agent = Agent::create($validated);

        return response()->json(['success' => true, 'agent' => $agent]);
    }

    public function toggleAgent(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        $agent->status = $request->status ?? 'active';
        $agent->save();

        return response()->json(['success' => true]);
    }

    public function workflows()
    {
        $workflows = \App\Models\Workflow::all();
        return view('hubs.workflows', compact('workflows'));
    }

    public function memory()
    {
        $memories = \App\Models\Memory::orderBy('created_at', 'desc')->get();
        return view('hubs.memory', compact('memories'));
    }

    public function storeMemory(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'type' => 'required|string',
            'confidence' => 'nullable|numeric|min:0|max:1',
        ]);

        $memory = new \App\Models\Memory();
        $memory->content = $validated['content'];
        $memory->type = strtolower($validated['type']);
        $memory->source = 'user_injection';
        $memory->title = \Illuminate\Support\Str::limit($validated['content'], 40);
        $memory->metadata = [
            'confidence' => (float)($validated['confidence'] ?? 1.0),
            'injected_by' => 'user',
        ];
        $memory->save();

        return response()->json(['success' => true, 'memory' => $memory]);
    }

    public function logs()
    {
        return view('hubs.logs');
    }

    public function models()
    {
        $providers = AIProvider::all();
        return view('hubs.models', compact('providers'));
    }

    public function toggleModel(Request $request, $id)
    {
        $provider = AIProvider::findOrFail($id);
        $provider->is_active = $request->is_active;
        $provider->save();
        
        return response()->json(['success' => true]);
    }

    public function settings()
    {
        $settings = Setting::pluck('value', 'key')->toArray();
        return view('hubs.settings', compact('settings'));
    }

    public function updateSettings(Request $request)
    {
        foreach ($request->except('_token') as $key => $value) {
            Setting::updateOrCreate(['key' => $key], ['value' => is_array($value) ? json_encode($value) : $value]);
        }
        return response()->json(['success' => true]);
    }
    public function peopleConnect(Request $request)
    {
        $contacts = Contact::withCount('messages')->orderBy('updated_at', 'desc')->get();
        $selectedContactId = $request->query('contact_id');
        $selectedContact = null;
        $messages = [];

        if ($selectedContactId) {
            $selectedContact = Contact::find($selectedContactId);
            if ($selectedContact) {
                // Assuming we use 'ContactMessage' or 'Message' table. Let's use Message for now.
                // Or maybe just the contact's messages relation if it exists.
                // Let's assume Contact has a messages() relation.
                if (method_exists($selectedContact, 'messages')) {
                    $messages = $selectedContact->messages()->orderBy('created_at', 'asc')->get();
                } else {
                    $messages = \App\Models\ContactMessage::where('contact_id', $selectedContactId)->orderBy('created_at', 'asc')->get();
                }
            }
        }

        return view('hubs.people-connect', compact('contacts', 'selectedContact', 'messages'));
    }

    public function hedraSoul(Request $request)
    {
        $sessions = \App\Models\HedrasoulSession::orderBy('updated_at', 'desc')->get();
        $selectedSessionId = $request->query('session_id');
        $selectedSession = null;
        $messages = [];

        if ($selectedSessionId) {
            $selectedSession = \App\Models\HedrasoulSession::find($selectedSessionId);
        } else if ($sessions->count() > 0) {
            $selectedSession = $sessions->first();
        }

        if ($selectedSession) {
            $messages = \App\Models\HedrasoulMessage::where('session_id', $selectedSession->id)
                            ->orderBy('created_at', 'asc')->get();
        }

        return view('hubs.hedra-soul', compact('sessions', 'selectedSession', 'messages'));
    }

    public function proactiveAi()
    {
        $triggers = \App\Models\ProactiveTrigger::orderBy('next_run_at', 'asc')->get();
        $logs = \App\Models\NotificationLog::orderBy('created_at', 'desc')->take(10)->get();

        return view('hubs.proactive-ai', compact('triggers', 'logs'));
    }

    public function tasks()
    {
        $tasks = \App\Models\AgentTask::orderBy('created_at', 'desc')->get();

        $todo = $tasks->filter(function($task) {
            return in_array(strtolower($task->status), ['pending', 'queued', 'todo']);
        });

        $inProgress = $tasks->filter(function($task) {
            return in_array(strtolower($task->status), ['in_progress', 'running', 'active']);
        });

        $completed = $tasks->filter(function($task) {
            return in_array(strtolower($task->status), ['completed', 'done', 'success']);
        });

        $failed = $tasks->filter(function($task) {
            return in_array(strtolower($task->status), ['failed', 'error', 'cancelled']);
        });

        return view('hubs.tasks', compact('todo', 'inProgress', 'completed', 'failed'));
    }

    public function scheduler()
    {
        $schedules = \App\Models\WorkflowSchedule::with('workflow')->orderBy('next_run_at', 'asc')->get();
        return view('hubs.scheduler', compact('schedules'));
    }

    public function apis()
    {
        return view('hubs.apis');
    }

    public function admin()
    {
        return view('hubs.admin');
    }

    public function waha()
    {
        return view('hubs.waha');
    }

    public function triggerWahaSync(\Illuminate\Http\Request $request)
    {
        $type = $request->input('type');

        if ($type === 'Messages') {
            \App\Jobs\SyncWahaMessagesJob::dispatch();
        } else {
            \App\Jobs\SyncWahaContactsJob::dispatch();
        }

        return response()->json(['success' => true, 'message' => "Sync process dispatched for {$type}"]);
    }

    public function sendContactMessage(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'contact_id' => 'required|integer',
            'content' => 'required|string',
        ]);

        $message = new \App\Models\ContactMessage();
        $message->contact_id = $validated['contact_id'];
        $message->body = $validated['content'];
        $message->direction = 'outbound';
        $message->channel = 'whatsapp';
        $message->source = 'web';
        $message->source_timestamp = now();
        $message->save();

        return response()->json(['success' => true, 'message' => $message]);
    }

    public function sendHedraMessage(\Illuminate\Http\Request $request)
    {
        $validated = $request->validate([
            'session_id' => 'nullable|integer',
            'content' => 'nullable|string',
            'message' => 'nullable|string',
            'context' => 'nullable|string'
        ]);

        $body = $validated['content'] ?? $validated['message'] ?? '';
        $sessionId = $validated['session_id'] ?? null;

        // Resolve or create a session if it's missing (e.g. from the dashboard)
        if (!$sessionId) {
            $session = \App\Models\HedrasoulSession::where('status', 'active')
                ->orWhere('status', 'open')
                ->orderBy('updated_at', 'desc')
                ->first();
            if (!$session) {
                $session = \App\Models\HedrasoulSession::create([
                    'title' => 'Dashboard Chat Session',
                    'status' => 'active',
                    'last_autonomy_mode' => 'copilot',
                    'opened_at' => now(),
                ]);
            }
            $sessionId = $session->id;
        }

        // Save User Message
        $message = new \App\Models\HedrasoulMessage();
        $message->session_id = $sessionId;
        $message->sender_type = 'user';
        $message->body = $body;
        $message->status = 'sent';
        $message->save();

        // Call LLM using UniversalAiGatewayService
        $replyText = '';
        $tokensUsed = 0;
        try {
            $aiGateway = app('nexus.ai');
            $agent = \App\Models\Agent::where('status', 'active')->first() ?: \App\Models\Agent::first();
            if (!$agent) {
                $model = \App\Models\AIModel::where('status', 'active')->first();
                $agent = new \App\Models\Agent([
                    'name' => 'Souly',
                    'role' => 'Assistant',
                    'model' => $model ? ($model->external_id ?? $model->name) : 'gemini-1.5-flash',
                    'system_prompt' => 'You are Souly, a helpful AI assistant.',
                    'status' => 'active',
                ]);
            }

            $aiResult = $aiGateway->executeWithAgent($agent, [
                'input' => $body,
                'system_prompt' => $agent->system_prompt,
            ]);

            if (!empty($aiResult['text'])) {
                $replyText = $aiResult['text'];
                $tokensUsed = $aiResult['tokens'] ?? 0;
            } else {
                $replyText = "I processed your request, but received an empty response.";
            }

            // Save actual usage log in usage_logs table
            try {
                \DB::table('usage_logs')->insert([
                    'provider_id' => $agent->ai_provider_id ?? null,
                    'model_id' => $agent->ai_model_id ?? null,
                    'intent_name' => 'agent_execution_' . $agent->id,
                    'input_tokens' => (int)($tokensUsed * 0.4),
                    'output_tokens' => (int)($tokensUsed * 0.6),
                    'total_cost' => round($tokensUsed * 0.000002, 6),
                    'timestamp' => now(),
                    'created_at' => now(),
                    'updated_at' => now(),
                ]);
            } catch (\Exception $ex) {}

        } catch (\Exception $e) {
            \Log::error('AI Console execution failed: ' . $e->getMessage());
            $replyText = "I encountered an issue processing your request: " . $e->getMessage();
        }

        // Save Agent Response
        $reply = new \App\Models\HedrasoulMessage();
        $reply->session_id = $sessionId;
        $reply->sender_type = 'agent';
        $reply->body = $replyText;
        $reply->status = 'delivered';
        $reply->token_count = $tokensUsed;
        $reply->cost_usd = round($tokensUsed * 0.000002, 4);
        $reply->save();

        return response()->json([
            'success' => true,
            'reply' => $reply->body,
            'token_count' => $tokensUsed
        ]);
    }

    public function executeWorkflow(\App\Models\Workflow $workflow, \App\Services\WorkflowExecutor $executor)
    {
        if ($workflow->isRunning()) {
            return response()->json([
                'code' => 'workflow_running',
                'message' => 'Workflow is already running',
            ], 409);
        }

        $result = $executor->execute($workflow, [], 'async', request()->user());

        $execution = \App\Models\WorkflowExecution::with('stepLogs')->find($result['execution_id']);

        return response()->json([
            'success' => true,
            'execution_id' => $execution->id,
            'status' => $execution->status,
            'message' => 'Workflow execution queued',
        ], 202);
    }

    public function showExecution(\App\Models\WorkflowExecution $execution)
    {
        $execution->load(['workflow', 'stepLogs' => fn ($query) => $query->orderBy('created_at')]);
        return response()->json([
            'success' => true,
            'execution' => $execution
        ]);
    }

    public function toggleFavorite(Request $request, $id, \App\Services\LogService $logService)
    {
        $user = $request->user();
        if (!$user) {
            return response()->json(['error' => 'Unauthenticated'], 401);
        }

        $contact = Contact::findOrFail($id);
        $wasFavorite = $contact->isFavoritedBy($user);
        
        $user->favoriteContacts()->toggle($contact->id);
        $isFavorite = !$wasFavorite;

        // Structured audit logging via LogService
        $logService->info('Contact favorite flag changed', [
            'channel' => 'contact',
            'type' => 'favorite_toggle',
            'related_id' => $contact->id,
            'related_type' => Contact::class,
            'user_id' => $user->id,
            'before' => $wasFavorite ? 'favorited' : 'unfavorited',
            'after' => $isFavorite ? 'favorited' : 'unfavorited',
            'actor' => $user->name,
        ]);

        return response()->json([
            'success' => true,
            'is_favorite' => $isFavorite,
            'message' => $isFavorite ? 'Contact added to favorites.' : 'Contact removed from favorites.'
        ]);
    }

    public function logoutWeb(Request $request)
    {
        \Illuminate\Support\Facades\Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('hub.dashboard');
    }

    public function restartAgent(Request $request, $id)
    {
        $agent = Agent::findOrFail($id);
        
        // Set status to active (representing a restarted/re-initialised state)
        $agent->status = 'active';
        $agent->save();
        
        // Log application event in logs table
        try {
            \DB::table('logs')->insert([
                'level' => 'INFO',
                'channel' => 'system',
                'message' => "Agent '{$agent->name}' successfully restarted.",
                'type' => 'application',
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        } catch (\Exception $e) {}
        
        // Broadcast AgentStarted event
        try {
            event(new \App\Events\AgentStarted($agent));
        } catch (\Exception $e) {}
        
        return response()->json([
            'success' => true,
            'message' => "Agent '{$agent->name}' restarted successfully."
        ]);
    }

    public function dashboardHealth(Request $request, \App\Services\NexusDashboardService $service)
    {
        return response()->json($service->getHealthStatus());
    }

    public function dashboardActivityFeed(Request $request, \App\Services\NexusDashboardService $service)
    {
        $limit = $request->query('limit', 20);
        return response()->json($service->getActivityFeed($limit));
    }
}
