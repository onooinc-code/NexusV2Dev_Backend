<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redis;

class NexusDashboardService
{
    private const STATS_CACHE_TTL = 55;  // seconds
    private const HEALTH_PROBE_TIMEOUT = 3; // seconds

    public function aggregateStats(User $user): array
    {
        $cacheKey = "dashboard_stats_{$user->id}";

        return Cache::remember($cacheKey, self::STATS_CACHE_TTL, function () use ($user) {
            return $this->buildStats($user);
        });
    }

    private function buildStats(User $user): array
    {
        // Run all aggregations — wrap each in try/catch for resilience
        $totalContacts      = $this->safeCount(fn() => DB::table('contacts')->where('user_id', $user->id)->count(), 0);
        $totalContactsYest  = $this->safeCount(fn() => DB::table('contacts')->where('user_id', $user->id)->where('created_at', '<', now()->subDay())->count(), 0);

        $activeConversations     = $this->safeCount(fn() => DB::table('conversations')->join('contacts', 'conversations.contact_id', '=', 'contacts.id')->where('contacts.user_id', $user->id)->where('conversations.status', 'active')->count(), 0);
        $activeConversationsYest = $this->safeCount(fn() => DB::table('conversations')->join('contacts', 'conversations.contact_id', '=', 'contacts.id')->where('contacts.user_id', $user->id)->where('conversations.status', 'active')->where('conversations.created_at', '<', now()->subDay())->count(), 0);

        $memoriesStored     = $this->safeCount(fn() => DB::table('memories')->count(), 0);
        $memoriesStoredYest = $this->safeCount(fn() => DB::table('memories')->where('created_at', '<', now()->subDay())->count(), 0);

        $pendingTasks     = $this->safeCount(fn() => DB::table('agent_tasks')->where('status', 'pending')->count(), 0);
        $pendingTasksYest = $this->safeCount(fn() => DB::table('agent_tasks')->where('status', 'pending')->where('created_at', '<', now()->subDay())->count(), 0);

        $runningAgents     = $this->safeCount(fn() => DB::table('agents')->where('owner_id', $user->id)->whereIn('status', ['active', 'busy'])->count(), 0);
        $runningAgentsYest = $this->safeCount(fn() => DB::table('agents')->where('owner_id', $user->id)->whereIn('status', ['active', 'busy'])->where('created_at', '<', now()->subDay())->count(), 0);

        $queuedJobs     = $this->safeCount(fn() => DB::table('jobs')->count(), 0);
        $queuedJobsYest = $this->safeCount(fn() => DB::table('jobs')->where('created_at', '<', now()->subDay()->timestamp)->count(), 0);

        $agents      = $this->getActiveAgents($user);
        $jobs        = $this->getRecentJobs();
        $contacts    = $this->getRecentContacts($user);
        $memory      = $this->getMemoryHealth($user);
        $aiUsage     = $this->getAiUsage($user);
        $scheduled   = $this->getUpcomingScheduled($user);
        $suggestions = $this->getProactiveSuggestions($user);

        return [
            'total_contacts'       => $totalContacts,
            'active_conversations' => $activeConversations,
            'memories_stored'      => $memoriesStored,
            'pending_tasks'        => $pendingTasks,
            'running_agents'       => $runningAgents,
            'queued_jobs'          => $queuedJobs,
            'trends' => [
                'total_contacts'       => $this->trend($totalContacts, $totalContactsYest),
                'active_conversations' => $this->trend($activeConversations, $activeConversationsYest),
                'memories_stored'      => $this->trend($memoriesStored, $memoriesStoredYest),
                'pending_tasks'        => $this->trend($pendingTasks, $pendingTasksYest),
                'running_agents'       => $this->trend($runningAgents, $runningAgentsYest),
                'queued_jobs'          => $this->trend($queuedJobs, $queuedJobsYest),
            ],
            'ai_usage'              => $aiUsage,
            'agents'                => $agents,
            'jobs'                  => $jobs,
            'recent_contacts'       => $contacts,
            'memory_health'         => $memory,
            'proactive_suggestions' => $suggestions,
            'upcoming_scheduled'    => $scheduled,
        ];
    }

    private function trend(int $current, int $previous): array
    {
        $delta = $current - $previous;
        return [
            'direction' => $delta > 0 ? 'up' : ($delta < 0 ? 'down' : 'neutral'),
            'delta'     => abs($delta),
        ];
    }

    private function safeCount(callable $fn, mixed $default = 0): mixed
    {
        try {
            return $fn();
        } catch (\Throwable) {
            return $default;
        }
    }

    private function getActiveAgents(User $user): array
    {
        try {
            return DB::table('agents')
                ->where('owner_id', $user->id)
                ->whereIn('status', ['active', 'busy', 'online'])
                ->select('id', 'name', 'role', 'status', 'current_task')
                ->limit(10)
                ->get()
                ->map(fn($a) => [
                    'id'           => (string) $a->id,
                    'name'         => $a->name,
                    'role'         => $a->role ?? 'Agent',
                    'status'       => $a->status === 'active' ? 'online' : $a->status,
                    'token_usage'  => $this->safeCount(fn() => DB::table('usage_logs')->where('intent_name', 'like', "%agent%{$a->id}%")->sum(DB::raw('input_tokens + output_tokens')) ?? 0, 0),
                    'current_task' => $a->current_task ?? null,
                ])
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getRecentJobs(): array
    {
        try {
            $pending = DB::table('jobs')
                ->select('id', 'queue', 'payload', 'attempts')
                ->orderBy('created_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($j) => [
                    'id'           => (string) $j->id,
                    'name'         => json_decode($j->payload, true)['displayName'] ?? 'Queue Job',
                    'queue'        => $j->queue,
                    'status'       => 'pending',
                    'progress_pct' => 0,
                    'started_at'   => null,
                    'failed_count' => $j->attempts ?? 0,
                ]);

            $failed = DB::table('failed_jobs')
                ->select('id', 'queue', 'payload', 'failed_at')
                ->orderBy('failed_at', 'desc')
                ->limit(5)
                ->get()
                ->map(fn($j) => [
                    'id'           => (string) $j->id,
                    'name'         => json_decode($j->payload, true)['displayName'] ?? 'Failed Job',
                    'queue'        => $j->queue,
                    'status'       => 'failed',
                    'progress_pct' => 0,
                    'started_at'   => $j->failed_at,
                    'failed_count' => 1,
                ]);

            return $pending->merge($failed)->values()->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getRecentContacts(User $user): array
    {
        try {
            return DB::table('contacts')
                ->where('user_id', $user->id)
                ->whereNotNull('last_interaction_at')
                ->orderBy('last_interaction_at', 'desc')
                ->select('id', 'name', 'avatar_url', 'last_interaction_at', 'reply_mode_override')
                ->limit(10)
                ->get()
                ->map(function($c) {
                    $lastMsg = DB::table('contact_messages')
                        ->where('contact_id', $c->id)
                        ->orderBy('created_at', 'desc')
                        ->first();
                    return [
                        'id'                   => (string) $c->id,
                        'name'                 => $c->name,
                        'avatar_url'           => $c->avatar_url,
                        'last_message_snippet' => $lastMsg ? \Illuminate\Support\Str::limit($lastMsg->content, 60) : 'No interactions recorded.',
                        'channel'              => $lastMsg ? ($lastMsg->channel ?? 'whatsapp') : 'other',
                        'last_interaction_at'  => $c->last_interaction_at,
                        'reply_mode'           => $c->reply_mode_override ?? 'manual',
                    ];
                })
                ->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getMemoryHealth(User $user): array
    {
        try {
            $total            = DB::table('memories')->count();
            $lowConfidence    = DB::table('memories')->where('metadata->confidence', '<', 0.4)->count();
            $expired          = DB::table('memories')->where('expires_at', '<', now())->count();
            $lastConsolidated = DB::table('memories')->max('updated_at');

            $high   = DB::table('memories')->where('metadata->confidence', '>=', 0.7)->count();
            $medium = DB::table('memories')->whereBetween('metadata->confidence', [0.2, 0.7])->count();
            $low    = DB::table('memories')->where('metadata->confidence', '<', 0.2)->count();

            return [
                'total_records'           => $total,
                'low_confidence_count'    => $lowConfidence,
                'expired_count'           => $expired,
                'last_consolidation_at'   => $lastConsolidated,
                'confidence_distribution' => compact('high', 'medium', 'low'),
            ];
        } catch (\Throwable $e) {
            return [
                'total_records'           => 0,
                'low_confidence_count'    => 0,
                'expired_count'           => 0,
                'last_consolidation_at'   => null,
                'confidence_distribution' => ['high' => 0, 'medium' => 0, 'low' => 0],
            ];
        }
    }

    private function getAiUsage(User $user): array
    {
        try {
            $today      = now()->startOfDay();
            $monthStart = now()->startOfMonth();

            $todayTokens = $this->safeCount(fn() =>
                DB::table('usage_logs')
                    ->where('timestamp', '>=', $today)
                    ->sum(DB::raw('input_tokens + output_tokens')) ?? 0, 0);

            $monthTokens = $this->safeCount(fn() =>
                DB::table('usage_logs')
                    ->where('timestamp', '>=', $monthStart)
                    ->sum(DB::raw('input_tokens + output_tokens')) ?? 0, 0);

            $todayCost = $this->safeCount(fn() =>
                DB::table('usage_logs')
                    ->where('timestamp', '>=', $today)
                    ->sum('total_cost') ?? 0.0, 0.0);

            $monthCost = $this->safeCount(fn() =>
                DB::table('usage_logs')
                    ->where('timestamp', '>=', $monthStart)
                    ->sum('total_cost') ?? 0.0, 0.0);

            $providerBreakdown = [];
            try {
                $providerBreakdown = DB::table('usage_logs')
                    ->join('ai_providers', 'usage_logs.provider_id', '=', 'ai_providers.id')
                    ->select('ai_providers.name', DB::raw('SUM(input_tokens + output_tokens) as tokens'))
                    ->groupBy('ai_providers.name')
                    ->get()
                    ->pluck('tokens', 'name')
                    ->toArray();
            } catch (\Exception $e) {}

            $topModel = 'N/A';
            try {
                $topModelRow = DB::table('usage_logs')
                    ->join('ai_models', 'usage_logs.model_id', '=', 'ai_models.id')
                    ->select('ai_models.name', DB::raw('SUM(input_tokens + output_tokens) as tokens'))
                    ->groupBy('ai_models.name')
                    ->orderBy('tokens', 'desc')
                    ->first();
                if ($topModelRow) {
                    $topModel = $topModelRow->name;
                }
            } catch (\Exception $e) {}

            return [
                'tokens_today'        => (int) $todayTokens,
                'tokens_this_month'   => (int) $monthTokens,
                'cost_today_usd'      => (float) round($todayCost, 4),
                'cost_this_month_usd' => (float) round($monthCost, 4),
                'provider_breakdown'  => $providerBreakdown,
                'top_model'           => $topModel,
                'tokens_history'      => $this->getTokensHistory($user),
            ];
        } catch (\Throwable) {
            return [
                'tokens_today'        => 0,
                'tokens_this_month'   => 0,
                'cost_today_usd'      => 0.0,
                'cost_this_month_usd' => 0.0,
                'provider_breakdown'  => [],
                'top_model'           => 'N/A',
                'tokens_history'      => [],
            ];
        }
    }

    private function getTokensHistory(User $user): array
    {
        $history = [];
        for ($i = 6; $i >= 0; $i--) {
            $date  = now()->subDays($i);
            $start = $date->copy()->startOfDay();
            $end   = $date->copy()->endOfDay();
            
            $tokens = 0;
            try {
                $tokens = DB::table('usage_logs')
                    ->whereBetween('timestamp', [$start, $end])
                    ->sum(DB::raw('input_tokens + output_tokens')) ?? 0;
            } catch (\Exception $e) {}

            $history[] = [
                'name'  => $date->format('M d'),
                'value' => (int) $tokens,
            ];
        }
        return $history;
    }

    private function getUpcomingScheduled(User $user): array
    {
        try {
            // Try scheduler_jobs first, then scheduled_jobs
            $tableName = 'scheduler_jobs';
            if (!\Schema::hasTable('scheduler_jobs') && \Schema::hasTable('scheduled_jobs')) {
                $tableName = 'scheduled_jobs';
            }

            if (!\Schema::hasTable($tableName)) {
                return [];
            }

            $userCol = \Schema::hasColumn($tableName, 'user_id') ? 'user_id' : null;

            $query = DB::table($tableName)
                ->where('status', '!=', 'paused')
                ->where(function ($q) {
                    $q->whereNull('next_run_at')->orWhere('next_run_at', '>=', now());
                })
                ->orderBy('next_run_at', 'asc')
                ->limit(3);

            if ($userCol) {
                $query->where('user_id', $user->id);
            }

            return $query->get()->map(fn($j) => [
                'id'       => (string) $j->id,
                'name'     => $j->name ?? 'Scheduled Job',
                'fires_at' => $j->next_run_at ?? now()->addHour()->toISOString(),
                'type'     => $j->type ?? 'job',
                'status'   => $j->status ?? 'scheduled',
            ])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    private function getProactiveSuggestions(User $user): array
    {
        try {
            $table = \Schema::hasTable('proactive_suggestions') ? 'proactive_suggestions'
                   : (\Schema::hasTable('proactive_logs') ? 'proactive_logs' : null);

            if (!$table) {
                return [];
            }

            $userCol = \Schema::hasColumn($table, 'user_id') ? 'user_id' : null;

            $query = DB::table($table)
                ->where('status', 'pending')
                ->orderBy('created_at', 'desc')
                ->limit(5);

            if ($userCol) {
                $query->where('user_id', $user->id);
            }

            return $query->get()->map(fn($s) => [
                'id'         => (string) $s->id,
                'title'      => $s->title ?? 'AI Suggestion',
                'body'       => $s->body ?? $s->summary ?? '',
                'category'   => $s->category ?? 'contact_insight',
                'priority'   => $s->priority ?? 'medium',
                'created_at' => $s->created_at,
            ])->toArray();
        } catch (\Throwable) {
            return [];
        }
    }

    // -------------------------------------------------------------------------
    // Health Probing
    // -------------------------------------------------------------------------

    public function getHealthStatus(): array
    {
        $services = [];

        // Database
        $services[] = $this->probeService('Database', fn() => [
            'latency'    => $this->measureLatency(fn() => DB::select('SELECT 1')),
            'error_rate' => 0.0,
        ]);

        // Redis / Cache
        $services[] = $this->probeService('Redis Cache', function () {
            $start = microtime(true);
            Cache::put('_health_check', 1, 5);
            Cache::get('_health_check');
            return [
                'latency'    => round((microtime(true) - $start) * 1000),
                'error_rate' => 0.0,
            ];
        });

        // Queue
        $services[] = $this->probeService('Queue Worker', function () {
            $pending = DB::table('jobs')->count();
            $failed  = DB::table('failed_jobs')->count();
            $total   = max(1, $pending + $failed);
            return [
                'latency'    => 0,
                'error_rate' => round($failed / $total, 3),
            ];
        });

        // Memory Hub
        $services[] = $this->probeService('MemoryHub', fn() => [
            'latency'    => $this->measureLatency(fn() => DB::table('memories')->limit(1)->get()),
            'error_rate' => 0.0,
        ]);

        // Contacts Hub
        $services[] = $this->probeService('ContactsHub', fn() => [
            'latency'    => $this->measureLatency(fn() => DB::table('contacts')->limit(1)->get()),
            'error_rate' => 0.0,
        ]);

        // Agents Hub
        $services[] = $this->probeService('AgentsHub', fn() => [
            'latency'    => $this->measureLatency(fn() => DB::table('agents')->limit(1)->get()),
            'error_rate' => 0.0,
        ]);

        // WAHA
        $services[] = $this->probeService('WAHA', function () {
            $apiUrl = app(\App\Services\SettingCacheService::class)->get('waha_url', config('services.waha.api_url', config('services.waha.url', 'http://localhost:3000')));
            $apiToken = app(\App\Services\SettingCacheService::class)->get('waha_api_key', config('services.waha.api_token', config('services.waha.api_key')));
            if (!$apiToken) {
                return ['latency' => 0, 'error_rate' => 1.0];
            }
            $start = microtime(true);
            $response = \Illuminate\Support\Facades\Http::timeout(2)->withHeaders([
                'Authorization' => "Bearer {$apiToken}",
            ])->get("{$apiUrl}/health");
            return [
                'latency' => round((microtime(true) - $start) * 1000),
                'error_rate' => $response->successful() ? 0.0 : 1.0,
            ];
        });

        // AI Models
        $services[] = $this->probeService('AI Models', function () {
            $geminiKey = env('GEMINI_API_KEY') ?: app(\App\Services\SettingCacheService::class)->get('gemini_api_key');
            $openaiKey = env('OPENAI_API_KEY') ?: app(\App\Services\SettingCacheService::class)->get('openai_api_key');
            $anthropicKey = env('ANTHROPIC_API_KEY') ?: app(\App\Services\SettingCacheService::class)->get('anthropic_api_key');
            
            $ok = false;
            $start = microtime(true);
            if ($geminiKey) {
                $response = \Illuminate\Support\Facades\Http::timeout(2)->get("https://generativelanguage.googleapis.com/v1beta/models?key={$geminiKey}");
                $ok = $response->successful();
            } elseif ($openaiKey) {
                $response = \Illuminate\Support\Facades\Http::timeout(2)->withHeaders(['Authorization' => "Bearer {$openaiKey}"])->get('https://api.openai.com/v1/models');
                $ok = $response->successful();
            } elseif ($anthropicKey) {
                $response = \Illuminate\Support\Facades\Http::timeout(2)->withHeaders(['x-api-key' => $anthropicKey])->get('https://api.anthropic.com/v1/models');
                $ok = $response->successful();
            }
            return [
                'latency' => round((microtime(true) - $start) * 1000),
                'error_rate' => $ok ? 0.0 : 1.0,
            ];
        });

        return ['services' => $services];
    }

    private function probeService(string $name, callable $probe): array
    {
        try {
            $result    = $probe();
            $latency   = $result['latency'] ?? 0;
            $errorRate = $result['error_rate'] ?? 0.0;
            $status    = $errorRate > 0.1 ? 'degraded' : 'online';

            return [
                'name'       => $name,
                'status'     => $status,
                'latency_ms' => (int) $latency,
                'error_rate' => (float) $errorRate,
            ];
        } catch (\Throwable $e) {
            return [
                'name'       => $name,
                'status'     => 'offline',
                'latency_ms' => null,
                'error_rate' => null,
            ];
        }
    }

    private function measureLatency(callable $fn): int
    {
        $start = microtime(true);
        $fn();
        return (int) round((microtime(true) - $start) * 1000);
    }

    // -------------------------------------------------------------------------
    // Activity Feed
    // -------------------------------------------------------------------------

    public function getActivityFeed(int $limit = 20, ?string $before = null): array
    {
        try {
            $query = DB::table('logs')
                ->select('id', 'level', 'message', 'channel', 'created_at')
                ->whereIn('level', ['info', 'warning', 'error'])
                ->orderBy('created_at', 'desc');

            if ($before) {
                $query->where('created_at', '<', $before);
            }

            $items      = $query->limit($limit)->get();
            $nextCursor = $items->last()?->created_at;

            $data = $items->map(fn($log) => [
                'id'         => (string) $log->id,
                'hub'        => $log->channel ?? 'System',
                'message'    => $log->message,
                'severity'   => match ($log->level) {
                    'error', 'critical', 'emergency', 'alert' => 'error',
                    'warning' => 'warning',
                    default   => 'info',
                },
                'created_at' => $log->created_at,
            ])->toArray();

            return [
                'data'        => $data,
                'next_cursor' => count($data) >= $limit ? $nextCursor : null,
            ];
        } catch (\Throwable) {
            return ['data' => [], 'next_cursor' => null];
        }
    }
}
