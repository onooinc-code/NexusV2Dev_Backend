<?php

namespace App\Http\Controllers\Api\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\HedrasoulSession;
use App\Services\HedraSoul\HedraSoulSessionService;
use Illuminate\Http\Request;

class HedraSoulSessionController extends Controller
{
    public function index()
    {
        return response()->json(['data' => HedrasoulSession::orderBy('created_at', 'desc')->get()]);
    }

    public function current(HedraSoulSessionService $service)
    {
        return response()->json(['data' => $service->resolveOrCreate()]);
    }

    public function show(HedrasoulSession $session)
    {
        return response()->json(['data' => $session]);
    }
}
