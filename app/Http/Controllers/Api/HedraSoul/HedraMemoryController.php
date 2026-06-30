<?php

namespace App\Http\Controllers\Api\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedraProfileFact;
use App\Services\HedraSoul\HedraMemoryService;
use Illuminate\Http\Request;

class HedraMemoryController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HedraProfileFact::orderBy('created_at', 'desc')->get()]);
    }

    public function store(Request $request, HedraMemoryService $service)
    {
        $validated = $request->validate([
            'content' => 'required|string',
            'memory_type' => 'required|string',
        ]);

        $validated['confidence'] = 1.0;
        $validated['is_approved'] = true;
        $validated['approved_at'] = now();
        $validated['version'] = 1;

        $fact = $service->createFact($validated);

        return response()->json(['data' => $fact], 201);
    }
}
