<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulMessage;
use App\Models\SoulyActionTrace;
use App\Models\HedrasoulContextSnapshot;
use App\Jobs\HedraSoul\ProcessHedraSoulMessageJob;
use Illuminate\Http\Request;

class HedraSoulMessageController extends Controller
{
    /**
     * Regenerate/reprocess a message
     * POST /hedrasoul/messages/{id}/regenerate
     * Returns 202 Accepted
     */
    public function regenerate(HedrasoulMessage $message)
    {
        $this->authorize('update', $message);

        dispatch(new ProcessHedraSoulMessageJob($message));

        return response()->json([
            'message_id' => $message->id,
            'status' => 'reprocessing',
        ], 202);
    }

    /**
     * Get the action trace for a message
     * GET /hedrasoul/messages/{id}/trace
     */
    public function trace(HedrasoulMessage $message)
    {
        $this->authorize('view', $message);

        $trace = SoulyActionTrace::where('message_id', $message->id)
            ->orWhere('trace_id', $message->trace_id)
            ->first();

        if (!$trace) {
            return response()->json(['error' => 'Trace not found'], 404);
        }

        return response()->json($trace);
    }

    /**
     * Get the context snapshot for a message
     * GET /hedrasoul/messages/{id}/context
     */
    public function context(HedrasoulMessage $message)
    {
        $this->authorize('view', $message);

        if (!$message->context_snapshot_id) {
            return response()->json(['error' => 'Context not found'], 404);
        }

        $context = HedrasoulContextSnapshot::find($message->context_snapshot_id);

        if (!$context) {
            return response()->json(['error' => 'Context not found'], 404);
        }

        return response()->json($context);
    }
}
