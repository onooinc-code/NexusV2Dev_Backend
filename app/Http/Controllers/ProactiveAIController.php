<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Log;
use Carbon\Carbon;
use App\Services\Proactive\NlpParserService;
use App\Models\EcaRule;
use App\Models\ProactiveTrigger;
use App\Models\AutonomousLog;

class ProactiveAIController extends Controller
{
    protected NlpParserService $nlpParser;

    public function __construct(NlpParserService $nlpParser)
    {
        $this->nlpParser = $nlpParser;
    }

    // ─── ECA Rules ────────────────────────────────────────────────────────────

    public function indexRules()
    {
        $rules = EcaRule::orderByDesc('created_at')->get();
        return response()->json(['success' => true, 'data' => $rules]);
    }

    public function storeRule(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'natural_language_rule' => 'required|string|max:1000',
            'name' => 'nullable|string|max:255',
        ]);

        if ($validator->fails()) {
            return response()->json(['errors' => $validator->errors()], 422);
        }

        try {
            // Parse NLP rule into structured conditions/actions
            $parsed = $this->nlpParser->parseRule($request->natural_language_rule);

            $rule = EcaRule::create([
                'name' => $request->name ?? substr($request->natural_language_rule, 0, 60),
                'natural_language_rule' => $request->natural_language_rule,
                'event_type' => $parsed['event_type'],
                'conditions' => $parsed['conditions'],
                'actions' => $parsed['actions'],
                'is_active' => true,
            ]);

            $id = $rule->id;

            // If it's time-based, auto-create a trigger
            if ($parsed['type'] === 'time_based' && $parsed['next_run_at']) {
                ProactiveTrigger::create([
                    'eca_rule_id' => $id,
                    'trigger_type' => 'time_based',
                    'next_run_at' => $parsed['next_run_at'],
                    'context_payload' => $parsed['actions'],
                    'status' => 'pending',
                ]);
            }

            return response()->json(['success' => true, 'data' => $rule], 201);
        } catch (\Exception $e) {
            Log::error('Failed to create ECA rule: ' . $e->getMessage());
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function toggleRule(int $id)
    {
        $rule = EcaRule::find($id);
        if (!$rule) {
            return response()->json(['success' => false, 'message' => 'Rule not found'], 404);
        }

        $rule->update([
            'is_active' => !$rule->is_active,
        ]);

        return response()->json(['success' => true, 'is_active' => $rule->is_active]);
    }

    public function destroyRule(int $id)
    {
        ProactiveTrigger::where('eca_rule_id', $id)->delete();
        EcaRule::where('id', $id)->delete();
        return response()->json(['success' => true]);
    }

    // ─── Triggers ─────────────────────────────────────────────────────────────

    public function indexTriggers()
    {
        $triggers = ProactiveTrigger::orderByDesc('created_at')->limit(50)->get();
        return response()->json(['success' => true, 'data' => $triggers]);
    }

    // ─── Autonomous Logs ──────────────────────────────────────────────────────

    public function indexLogs()
    {
        $logs = AutonomousLog::orderByDesc('created_at')->limit(100)->get();
        return response()->json(['success' => true, 'data' => $logs]);
    }
}

