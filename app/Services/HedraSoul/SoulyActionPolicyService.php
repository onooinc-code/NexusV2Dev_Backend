<?php

namespace App\Services\HedraSoul;

use App\Models\SoulyRuntimeProfile;
use App\Models\SoulyActionPolicy;

/**
 * Value object representing an authorization policy decision.
 */
class PolicyResult
{
    public bool $allowed;
    public string $explanation;

    public function __construct(bool $allowed, string $explanation = '')
    {
        $this->allowed = $allowed;
        $this->explanation = $explanation;
    }
}

/**
 * SoulyActionPolicyService: Checks if an action is allowed based on autonomy mode and policy rules.
 * Enforces the 5 autonomy modes: chat_only, copilot, operator, autopilot_limited, emergency_paused.
 */
class SoulyActionPolicyService
{
    /**
     * Check if an action can execute given current autonomy mode and risk level.
     */
    public function canExecute(string $intent, string $riskLevel): PolicyResult
    {
        $profile = app(SoulyRuntimeProfileService::class)->getCurrent();

        // Emergency pause blocks all actions
        if ($profile->is_quarantined) {
            return new PolicyResult(false, 'Action blocked: Souly is quarantined');
        }

        if ($profile->autonomy_mode === 'emergency_paused') {
            return new PolicyResult(false, 'Action blocked: emergency_paused mode');
        }

        // Risk-level matrix: each mode declares what risk levels it permits.
        // This is the single source of truth — any risk outside the allowed set is blocked.
        $allowedRisks = match ($profile->autonomy_mode) {
            'chat_only'         => ['read', 'draft'],
            'copilot'           => ['read', 'draft', 'write_low'],
            'operator'          => ['read', 'draft', 'write_low'],
            'autopilot_limited' => ['read', 'draft', 'write_low'],
            'emergency_paused'  => [],
            default             => [],
        };

        if (!in_array($riskLevel, $allowedRisks)) {
            return new PolicyResult(
                false,
                "Action blocked: {$profile->autonomy_mode} mode does not permit risk level '{$riskLevel}'"
            );
        }

        // Check for per-intent policy rule overrides stored in the DB
        $policy = SoulyActionPolicy::where('applies_to_mode', $profile->autonomy_mode)
            ->where('rule_key', $intent)
            ->first();

        if ($policy && $policy->rule_value === 'block') {
            return new PolicyResult(
                false,
                "Action blocked by policy rule for {$profile->autonomy_mode} mode"
            );
        }

        return new PolicyResult(true, 'Action permitted');
    }

    /**
     * Get current autonomy mode for introspection.
     */
    public function getCurrentMode(): string
    {
        return app(SoulyRuntimeProfileService::class)->getCurrent()->autonomy_mode;
    }

    /**
     * Check if Souly is quarantined.
     */
    public function isQuarantined(): bool
    {
        return app(SoulyRuntimeProfileService::class)->getCurrent()->is_quarantined;
    }
}
