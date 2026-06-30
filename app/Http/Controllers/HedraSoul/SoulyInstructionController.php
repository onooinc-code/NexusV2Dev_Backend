<?php

namespace App\Http\Controllers\HedraSoul;

use App\Http\Controllers\Controller;
use App\Models\SoulyInstructionVersion;
use App\Services\HedraSoul\SoulyInstructionVersionService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class SoulyInstructionController extends Controller
{
    public function __construct(
        protected SoulyInstructionVersionService $instructionService
    ) {}

    /**
     * List all instruction versions
     * GET /hedrasoul/instructions
     */
    public function index(Request $request)
    {
        $versions = SoulyInstructionVersion::orderBy('version_number', 'desc')
            ->paginate($request->per_page ?? 50);

        return response()->json($versions);
    }

    /**
     * Create a new draft instruction version
     * POST /hedrasoul/instructions
     */
    public function store(Request $request)
    {
        $validated = $request->validate([
            'content' => 'required|array',
            'change_reason' => 'required|string|max:500',
        ]);

        $version = $this->instructionService->createDraft(
            $validated['content'],
            $validated['change_reason']
        );

        return response()->json($version, 201);
    }

    /**
     * Get instruction version with diff
     * GET /hedrasoul/instructions/{id}
     */
    public function show(SoulyInstructionVersion $version)
    {
        $diff = $this->instructionService->diff($version->id);

        return response()->json([
            'version' => $version,
            'diff' => $diff,
        ]);
    }

    /**
     * Update a draft instruction version
     * PATCH /hedrasoul/instructions/{id}
     */
    public function update(Request $request, SoulyInstructionVersion $version)
    {
        if ($version->status !== 'draft') {
            return response()->json([
                'error' => 'Can only update draft versions',
            ], 422);
        }

        $validated = $request->validate([
            'content' => 'sometimes|array',
            'change_reason' => 'sometimes|string|max:500',
        ]);

        $version->update($validated);

        return response()->json($version);
    }

    /**
     * Activate an instruction version
     * POST /hedrasoul/instructions/{id}/activate
     */
    public function activate(Request $request, SoulyInstructionVersion $version)
    {
        $validated = $request->validate([
            'notes' => 'sometimes|string|max:1000',
        ]);

        $this->instructionService->activate($version, Auth::id());

        $version->refresh();

        // May require approval if permissions expanded
        return response()->json($version, 200);
    }

    /**
     * Rollback to previous instruction version
     * POST /hedrasoul/instructions/{id}/rollback
     */
    public function rollback(SoulyInstructionVersion $version)
    {
        $this->instructionService->rollback($version);

        $version->refresh();

        return response()->json($version);
    }

    /**
     * Test instruction in sandbox
     * POST /hedrasoul/instructions/{id}/test
     */
    public function test(Request $request, SoulyInstructionVersion $version)
    {
        $validated = $request->validate([
            'test_prompt' => 'required|string|max:2000',
        ]);

        $response = $this->instructionService->testSandbox(
            $version,
            $validated['test_prompt']
        );

        return response()->json([
            'test_prompt' => $validated['test_prompt'],
            'response' => $response,
        ]);
    }
}
