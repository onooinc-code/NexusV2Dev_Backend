<?php

namespace App\Http\Controllers\PeopleConnect;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
use App\Models\PeopleConnect\PeopleConnectMessage;
use App\Models\PeopleConnect\PeopleConnectConversation;
use App\Jobs\PeopleConnect\SyncWahaContactsJob;
use App\Jobs\PeopleConnect\SyncWahaConversationsJob;

class LiveMsgsController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $perPage = $request->input('per_page', 50);
        
        $messages = PeopleConnectMessage::with('conversation.contact')
            ->orderBy('created_at', 'desc')
            ->paginate($perPage);
            
        return response()->json($messages);
    }
    
    public function triggerSync(Request $request): JsonResponse
    {
        $type = $request->input('type', 'all');
        
        if ($type === 'contacts' || $type === 'all') {
            SyncWahaContactsJob::dispatch();
        }
        
        if ($type === 'conversations' || $type === 'all') {
            SyncWahaConversationsJob::dispatch();
        }
        
        return response()->json([
            'message' => 'Sync triggered successfully',
            'type' => $type
        ]);
    }
}
