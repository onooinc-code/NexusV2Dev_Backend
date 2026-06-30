<?php

namespace App\Http\Controllers\Api\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedraCloneSource;
use App\Services\HedraSoul\HedraCloneProfileService;
use Illuminate\Http\Request;

class HedraCloneProfileController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HedraCloneSource::active()->orderBy('created_at', 'desc')->get()]);
    }

    public function store(Request $request, HedraCloneProfileService $service)
    {
        $validated = $request->validate([
            'source_type' => 'required|string',
            'content' => 'required|string',
        ]);

        $validated['confidence'] = 1.0;
        $validated['freshness_score'] = 1.0;

        $source = $service->create($validated);

        return response()->json(['data' => $source], 201);
    }
}
