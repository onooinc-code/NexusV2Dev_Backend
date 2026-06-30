<?php

namespace App\Services\HedraSoul;

use App\Models\HedrasoulMessage;

/**
 * Value object representing a classified command intent.
 */
class CommandIntent
{
    public string $intent;
    public string $riskLevel;
    public PolicyResult $policyResult;

    public function __construct(string $intent, string $riskLevel, PolicyResult $policyResult)
    {
        $this->intent = $intent;
        $this->riskLevel = $riskLevel;
        $this->policyResult = $policyResult;
    }
}

/**
 * SoulyCommandRouter: Classifies message intent and maps to action type.
 * Determines risk level and delegates to SoulyActionPolicyService for authorization.
 */
class SoulyCommandRouter
{
    protected SoulyActionPolicyService $policyService;

    public function __construct(SoulyActionPolicyService $policyService)
    {
        $this->policyService = $policyService;
    }

    /**
     * Classify a message intent from its body content.
     * Maps to one of 11 intents with corresponding risk levels.
     */
    public function classify(HedrasoulMessage $message): CommandIntent
    {
        $body = strtolower($message->body);
        $intent = 'answer';  // default
        $riskLevel = 'read';  // default

        // Command detection patterns (order matters - more specific patterns first)
        if (preg_match('/\/task\s|create\s+task|new\s+task/i', $body)) {
            $intent = 'create_task';
            $riskLevel = 'write_low';
        } elseif (preg_match('/\/workflow\s|start\s+workflow|new\s+workflow/i', $body)) {
            $intent = 'start_workflow';
            $riskLevel = 'write_medium';
        } elseif (preg_match('/\/agent\s|execute\s+agent|run\s+agent/i', $body)) {
            $intent = 'execute_agent';
            $riskLevel = 'write_medium';
        } elseif (preg_match('/\/draft\s|draft\s+for|draft\s+this/i', $body)) {
            $intent = 'draft';
            $riskLevel = 'draft';
        } elseif (preg_match('/\/schedule|schedule\s+|schedule\s+work/i', $body)) {
            $intent = 'schedule_work';
            $riskLevel = 'write_low';
        } elseif (preg_match('/approve|request\s+approval|needs\s+approval/i', $body)) {
            $intent = 'open_approval';
            $riskLevel = 'read';
        } elseif (preg_match('/\/memory|remember|save\s+memory|remember\s+this/i', $body)) {
            $intent = 'suggest_memory';
            $riskLevel = 'write_low';
        } elseif (preg_match('/\/settings|change\s+setting|update\s+preference/i', $body)) {
            $intent = 'suggest_setting';
            $riskLevel = 'write_low';
        } elseif (preg_match('/\/notify|notify|send\s+notification/i', $body)) {
            $intent = 'notify';
            $riskLevel = 'external_send';
        } elseif (preg_match('/\/profile|update\s+profile|my\s+profile/i', $body)) {
            $intent = 'update_profile';
            $riskLevel = 'write_low';
        } else {
            $intent = 'answer';
            $riskLevel = 'read';
        }

        // Get authorization from policy service
        $policyResult = $this->policyService->canExecute($intent, $riskLevel);

        return new CommandIntent($intent, $riskLevel, $policyResult);
    }
}
