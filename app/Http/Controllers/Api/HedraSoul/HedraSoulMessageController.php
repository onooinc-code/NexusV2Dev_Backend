<?php

namespace App\Http\Controllers\Api\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulSession;
use App\Services\HedraSoul\HedraSoulMessageService;
use Illuminate\Http\Request;

class HedraSoulMessageController extends Controller
{
    public function index(HedrasoulSession $session)
    {
        return response()->json(['data' => $session->messages()->orderBy('created_at', 'asc')->get()]);
    }

    public function store(Request $request, HedrasoulSession $session, HedraSoulMessageService $service)
    {
        $validated = $request->validate([
            'body' => 'required|string',
            'sender_type' => 'nullable|string',
            'sender_id' => 'nullable|integer',
        ]);

        $message = $service->save($validated, $session);

        return response()->json(['data' => $message], 201);
    }
}
