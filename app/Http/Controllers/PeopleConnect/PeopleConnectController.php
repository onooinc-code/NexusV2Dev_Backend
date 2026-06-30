<?php

namespace App\Http\Controllers\PeopleConnect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Models\PeopleConnect\PeopleConnectSession;
use App\Models\Contact;
use Illuminate\Support\Facades\DB;

class PeopleConnectController extends Controller
{
    public function stats(Request $request): JsonResponse
    {
        // Global system health + high level stats
        $totalContacts = Contact::whereHas('peopleConnectConversations')->count();
        $activeSessions = PeopleConnectSession::where('status', 'open')->count();
        $unreadConversations = PeopleConnectConversation::where('unread_count', '>', 0)->count();

        return response()->json([
            'total_contacts' => $totalContacts,
            'active_sessions' => $activeSessions,
            'unread_conversations' => $unreadConversations,
            'status' => 'healthy'
        ]);
    }

    public function search(Request $request): JsonResponse
    {
        $query = $request->input('q');
        
        if (empty($query)) {
            $recent = PeopleConnectConversation::with('contact')
                ->orderBy('last_message_at', 'desc')
                ->take(20)
                ->get();
            return response()->json($recent);
        }

        $contacts = Contact::whereHas('peopleConnectConversations')
            ->where(function ($q) use ($query) {
                $q->where('name', 'like', "%{$query}%")
                  ->orWhere('phone', 'like', "%{$query}%")
                  ->orWhere('whatsapp_number', 'like', "%{$query}%");
            })
            ->with(['peopleConnectConversations' => function ($q) {
                $q->select('id', 'contact_id', 'channel', 'status', 'unread_count', 'last_message_preview', 'last_message_at');
            }])
            ->take(20)
            ->get();

        return response()->json($contacts);
    }

    public function showConversation(int $id): JsonResponse
    {
        $conversation = PeopleConnectConversation::with([
            'contact',
            'sessions' => function ($q) {
                $q->orderBy('created_at', 'desc')->take(5);
            },
            'messages' => function ($q) {
                $q->orderBy('created_at', 'desc')->take(50);
            }
        ])->findOrFail($id);

        return response()->json($conversation);
    }

    public function updateReplyMode(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'reply_mode' => 'required|in:manual,auto,hybrid,ai_only'
        ]);

        $conversation = PeopleConnectConversation::findOrFail($id);
        $conversation->update([
            'reply_mode_effective' => $request->input('reply_mode')
        ]);

        return response()->json([
            'message' => 'Reply mode updated successfully',
            'conversation' => $conversation
        ]);
    }
}
