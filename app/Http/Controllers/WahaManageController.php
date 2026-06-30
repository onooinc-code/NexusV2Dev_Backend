<?php

namespace App\Http\Controllers;

use App\Models\Contact;
use App\Models\ContactMessage;
use App\Models\WahaSyncProcess;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;

class WahaManageController extends Controller
{
    public function status(): JsonResponse
    {
        $totalWahaContacts = Contact::whereNotNull('waha_contact_id')->count();
        $totalMessages = ContactMessage::whereNotNull('waha_message_id')->count();

        $activeProcesses = WahaSyncProcess::whereIn('status', ['pending', 'running', 'paused'])
            ->orderBy('created_at', 'desc')
            ->get();
            
        $lastSync = WahaSyncProcess::where('type', 'sync_contacts')
            ->whereNotNull('config')
            ->orderBy('created_at', 'desc')
            ->first();
            
        $wahaTotal = $lastSync && isset($lastSync->config['waha_total_contacts']) 
            ? $lastSync->config['waha_total_contacts'] 
            : $totalWahaContacts;
            
        $unsynced = $lastSync && isset($lastSync->config['nexus_unsynced_contacts']) 
            ? $lastSync->config['nexus_unsynced_contacts'] 
            : 0;

        return response()->json([
            'stats' => [
                'total_waha_contacts' => $wahaTotal,
                'synced_contacts' => $totalWahaContacts,
                'unsynced_contacts' => $unsynced,
                'total_messages' => $totalMessages,
            ],
            'active_processes' => $activeProcesses,
        ]);
    }

    public function contacts(Request $request): JsonResponse
    {
        $limit = $request->input('limit', 50);
        $offset = $request->input('offset', 0);

        $contacts = Contact::whereNotNull('waha_contact_id')
            ->withCount('messages')
            ->orderBy('created_at', 'desc')
            ->skip($offset)
            ->take($limit)
            ->get();

        $total = Contact::whereNotNull('waha_contact_id')->count();

        return response()->json([
            'data' => $contacts,
            'total' => $total,
            'limit' => $limit,
            'offset' => $offset,
        ]);
    }

    public function startSync(Request $request): JsonResponse
    {
        $type = $request->input('type', 'sync_contacts'); // sync_contacts or sync_messages
        
        // Find existing paused process or create new
        $process = WahaSyncProcess::where('type', $type)
            ->whereIn('status', ['paused', 'failed'])
            ->first();

        if (!$process) {
            // Cancel any pending/running of same type
            WahaSyncProcess::where('type', $type)
                ->whereIn('status', ['pending', 'running'])
                ->update(['status' => 'failed', 'errors' => ['message' => 'Superseded by new process']]);

            $process = WahaSyncProcess::create([
                'type' => $type,
                'status' => 'pending',
                'started_at' => now(),
            ]);
        } else {
            $process->update(['status' => 'pending']);
        }

        if ($type === 'sync_contacts') {
            \App\Jobs\PeopleConnect\SyncWahaContactsJob::dispatch($process->id);
        } else {
            \App\Jobs\PeopleConnect\SyncWahaMessagesJob::dispatch($process->id);
        }

        return response()->json([
            'message' => 'Sync process started',
            'process' => $process,
        ]);
    }

    public function startContactMessageSync(Request $request, $id): JsonResponse
    {
        $contact = Contact::findOrFail($id);
        
        $process = WahaSyncProcess::create([
            'type' => 'sync_messages',
            'status' => 'pending',
            'started_at' => now(),
            'config' => ['target_contact_id' => $id]
        ]);

        \App\Jobs\PeopleConnect\SyncSingleContactMessagesJob::dispatch($id, $process->id);

        return response()->json([
            'message' => 'Contact message sync started',
            'process' => $process,
        ]);
    }

    public function pauseSync(Request $request, $id): JsonResponse
    {
        $process = WahaSyncProcess::findOrFail($id);
        if (in_array($process->status, ['running', 'pending'])) {
            $process->update(['status' => 'paused']);
            return response()->json(['message' => 'Process paused successfully. It will stop after the current batch.']);
        }
        return response()->json(['message' => 'Process is not running'], 400);
    }

    public function startAnalysis(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'agent_id' => 'required|exists:agents,id',
            'model_id' => 'required',
            'message_limit' => 'required|integer|min:1',
            'contact_ids' => 'array',
            'extract_preferences' => 'boolean',
            'extract_personality' => 'boolean',
            'extract_topics' => 'boolean',
        ]);

        $process = WahaSyncProcess::create([
            'type' => 'analyze_messages',
            'status' => 'pending',
            'started_at' => now(),
            'config' => $validated,
        ]);

        \App\Jobs\PeopleConnect\WahaBatchAnalyzeJob::dispatch($process->id);

        return response()->json([
            'message' => 'Analysis process started',
            'process' => $process,
        ]);
    }
}
