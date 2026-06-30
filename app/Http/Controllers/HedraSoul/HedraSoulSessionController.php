<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulSession;
use App\Models\HedrasoulMessage;
use App\Services\HedraSoul\HedraSoulSessionService;
use App\Services\HedraSoul\HedraSoulMessageService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class HedraSoulSessionController extends Controller
{
    public function __construct(
        protected HedraSoulSessionService $sessionService,
        protected HedraSoulMessageService $messageService
    ) {}

    /**
     * List sessions for authenticated user, paginated
     * GET /hedrasoul/sessions
     */
    public function index(Request $request)
    {
        $user = Auth::user();
        
        $query = HedrasoulSession::query()
            ->where('user_id', $user->id);

        if ($request->has('status')) {
            $query->where('status', $request->status);
        }

        $sessions = $query
            ->orderBy('created_at', 'desc')
            ->paginate($request->per_page ?? 20);

        return response()->json($sessions);
    }

    /**
     * Create a new named session
     * POST /hedrasoul/sessions
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'title' => 'required|string|max:255',
        ]);

        $session = $this->sessionService->createNamed($validated['title']);

        return response()->json($session, 201);
    }

    /**
     * Get a specific session
     * GET /hedrasoul/sessions/{id}
     */
    public function show(HedrasoulSession $session)
    {
        $this->authorize('view', $session);

        return response()->json($session);
    }

    /**
     * Update session title/topic
     * PATCH /hedrasoul/sessions/{id}
     */
    public function update(Request $request, HedrasoulSession $session)
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'title' => 'sometimes|string|max:255',
            'topic' => 'sometimes|string|max:255',
        ]);

        $session->update($validated);

        return response()->json($session);
    }

    /**
     * Archive a session
     * POST /hedrasoul/sessions/{id}/archive
     */
    public function archive(HedrasoulSession $session)
    {
        $this->authorize('update', $session);

        $this->sessionService->archive($session);

        return response()->json($session);
    }

    /**
     * List paginated messages for a session
     * GET /hedrasoul/sessions/{id}/messages
     */
    public function messages(Request $request, HedrasoulSession $session)
    {
        $this->authorize('view', $session);

        $messages = HedrasoulMessage::where('session_id', $session->id)
            ->orderBy('created_at', 'asc')
            ->paginate($request->per_page ?? 50);

        return response()->json($messages);
    }

    /**
     * Send a message to Souly (async processing)
     * POST /hedrasoul/sessions/{id}/messages
     * Returns 202 Accepted
     */
    public function sendMessage(Request $request, HedrasoulSession $session)
    {
        $this->authorize('update', $session);

        $validated = $request->validate([
            'body' => 'required|string',
            'body_format' => 'sometimes|in:text,markdown',
            'model_override' => 'sometimes|integer|exists:ai_instances,id',
            'dry_run' => 'sometimes|boolean',
        ]);

        $data = array_merge($validated, [
            'sender_type' => 'user',
            'sender_id' => Auth::id(),
            'status' => 'pending',
        ]);

        $message = $this->messageService->save($data, $session);

        return response()->json([
            'message_id' => $message->id,
            'session_id' => $session->id,
            'status' => 'processing',
        ], 202);
    }
}
