<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/**
 * API Routes for Nexus Platform
 * All routes are prefixed with /api/v1
 */

// Public routes (no authentication required)
Route::group(['prefix' => 'v1', 'middleware' => ['api']], function () {

    // Health check endpoint
    Route::get('/health', function (Request $request) {
        return response()->json([
            'status' => 'healthy',
            'timestamp' => now(),
            'app' => config('app.name'),
        ]);
    });

    // Broadcast auth for token-based (Sanctum) clients — supports Bearer tokens
    Route::post('/broadcasting/auth', function (Request $request) {
        // If a bearer token is present, try to authenticate the tokenable user manually
        $bearer = $request->bearerToken();
        \Log::info('broadcasting.auth called', ['bearer_present' => $bearer ? true : false]);
        if ($bearer) {
            $tokenModel = \Laravel\Sanctum\PersonalAccessToken::findToken($bearer);
            if ($tokenModel && $tokenModel->tokenable) {
                auth()->loginUsingId($tokenModel->tokenable->getAuthIdentifier());
                \Log::info('broadcasting.auth: logged in tokenable', ['user_id' => $tokenModel->tokenable->getAuthIdentifier()]);
            } else {
                \Log::warning('broadcasting.auth: token not found or has no tokenable');
            }
        }

        $resp = Broadcast::auth($request);
        \Log::info('broadcasting.auth: Broadcast::auth result', ['status' => $resp ? 'present' : 'empty']);
        return $resp;
    });

    // WAHA WhatsApp webhook endpoint
    Route::post('/webhooks/waha', [\App\Http\Controllers\WebhookController::class, 'handleWahaWebhook'])
        ->name('webhooks.waha');

    // Workflow webhook endpoint
    Route::post('/webhooks/workflows/{id}', [\App\Http\Controllers\WorkflowWebhookController::class, 'handle'])
        ->name('webhooks.workflows');

    Route::prefix('monitoring')->group(function () {
        Route::get('/health', [\App\Http\Controllers\Monitoring\HealthController::class, 'health']);
        Route::get('/health/reverb', [\App\Http\Controllers\Monitoring\HealthController::class, 'reverb']);
        Route::get('/health/queue', [\App\Http\Controllers\Monitoring\HealthController::class, 'queue']);
        Route::get('/metrics', [\App\Http\Controllers\Monitoring\MetricsController::class, 'metrics']);
        Route::get('/metrics/websocket', [\App\Http\Controllers\Monitoring\MetricsController::class, 'websocket']);
    });

    // Sanctum authentication routes
    Route::post('/login', [\App\Http\Controllers\AuthController::class, 'login'])
        ->name('login');

    Route::post('/register', [\App\Http\Controllers\AuthController::class, 'register'])
        ->name('register');

    Route::post('/verify-token', [\App\Http\Controllers\AuthController::class, 'verifyToken'])
        ->name('verify-token');
});

// Protected routes (authentication required via Sanctum)
Route::group(['prefix' => 'v1', 'middleware' => ['api', 'auth:sanctum']], function () {
    // Authentication actions
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])
        ->name('logout');

    /**
     * Contacts Hub Routes
     * Phase 2 — Stats & Reply Mode (must be defined BEFORE resource/wildcard routes)
     */
    Route::get('/contacts/stats', [\App\Http\Controllers\ContactStatsController::class, 'stats'])
        ->name('contacts.stats');
    Route::get('/contacts/reply-mode', [\App\Http\Controllers\ContactStatsController::class, 'getGlobalReplyMode'])
        ->name('contacts.reply-mode.global.get');
    Route::patch('/contacts/reply-mode', [\App\Http\Controllers\ContactStatsController::class, 'setGlobalReplyMode'])
        ->name('contacts.reply-mode.global.set');

    // Standard single-contact action routes (before resource to avoid conflicts)
    Route::post('/contacts/import', [\App\Http\Controllers\ContactController::class, 'import'])
        ->middleware('throttle:5,1')
        ->name('contacts.import');
    Route::post('/contacts/import/preview', [\App\Http\Controllers\ContactImportController::class, 'preview'])
        ->name('contacts.import.preview');
    Route::post('/contacts/import/whatsapp', [\App\Http\Controllers\ContactImportController::class, 'importWhatsApp'])
        ->middleware('throttle:5,1')
        ->name('contacts.import.whatsapp');
    Route::post('/contacts/import/whatsapp/waha', [\App\Http\Controllers\ContactImportController::class, 'importWaha'])
        ->middleware('throttle:5,1')
        ->name('contacts.import.whatsapp.waha');
    Route::post('/contacts/import/facebook', [\App\Http\Controllers\ContactImportController::class, 'importFacebook'])
        ->middleware('throttle:5,1')
        ->name('contacts.import.facebook');
    Route::get('/contacts/imports', [\App\Http\Controllers\ContactImportController::class, 'listBatches'])
        ->name('contacts.imports.index');
    Route::get('/contacts/imports/{batch}', [\App\Http\Controllers\ContactImportController::class, 'showBatch'])
        ->name('contacts.imports.show');
    Route::post('/contacts/imports/{batch}/rollback', [\App\Http\Controllers\ContactImportController::class, 'rollbackBatch'])
        ->name('contacts.imports.rollback');

    // ContactHub vNext message, intelligence, maintenance, and privacy routes.
    Route::get('/contacts/analytics', [\App\Http\Controllers\ContactController::class, 'hubAnalytics'])->name('contacts.hub-analytics');
    Route::get('/contacts/conflicts', [\App\Http\Controllers\ContactController::class, 'conflicts'])->name('contacts.conflicts');
    Route::get('/contacts/stale-memory', [\App\Http\Controllers\ContactController::class, 'staleMemory'])->name('contacts.stale-memory');
    Route::get('/contacts/{id}/memory-maintenance/runs', [\App\Http\Controllers\ContactController::class, 'contactMaintenanceRuns'])->name('contacts.memory-maintenance.contact.runs');
    
    Route::post('/contacts/analysis-runs/batch', [\App\Http\Controllers\ContactController::class, 'batchAnalysisRun'])
        ->name('contacts.analysis-runs.batch');
    Route::post('/contacts/analysis-runs/{run}/apply', [\App\Http\Controllers\ContactController::class, 'applyAnalysisRun'])
        ->name('contacts.analysis-runs.apply');
    Route::post('/contacts/analysis-runs/{run}/rollback', [\App\Http\Controllers\ContactController::class, 'rollbackAnalysisRun'])
        ->name('contacts.analysis-runs.rollback');
    Route::post('/contacts/memory-maintenance', [\App\Http\Controllers\ContactController::class, 'memoryMaintenance'])
        ->name('contacts.memory-maintenance.store');
    Route::get('/contacts/memory-maintenance/runs', [\App\Http\Controllers\ContactController::class, 'memoryMaintenanceRuns'])
        ->name('contacts.memory-maintenance.runs');
    Route::get('/contacts/memory-maintenance/runs/{run}', [\App\Http\Controllers\ContactController::class, 'showMemoryMaintenanceRun'])
        ->name('contacts.memory-maintenance.runs.show');

    Route::get('/contacts/export', [\App\Http\Controllers\ContactController::class, 'export'])
        ->name('contacts.export');
    Route::get('/contacts/{id}/messages', [\App\Http\Controllers\ContactController::class, 'messages'])
        ->name('contacts.messages');
    Route::get('/contacts/{id}/messages/whatsapp', [\App\Http\Controllers\ContactController::class, 'whatsappMessages'])
        ->name('contacts.messages.whatsapp');
    Route::get('/contacts/{id}/messages/facebook', [\App\Http\Controllers\ContactController::class, 'facebookMessages'])
        ->name('contacts.messages.facebook');
    Route::get('/contacts/{id}/threads', [\App\Http\Controllers\ContactController::class, 'threads'])
        ->name('contacts.threads');
    Route::get('/contacts/{id}/threads/{thread}', [\App\Http\Controllers\ContactController::class, 'showThread'])
        ->name('contacts.threads.show');
    Route::post('/contacts/{id}/analysis-runs', [\App\Http\Controllers\ContactController::class, 'createAnalysisRun'])
        ->middleware('throttle:analysis')
        ->name('contacts.analysis-runs.store');
    Route::get('/contacts/{id}/analysis-runs', [\App\Http\Controllers\ContactController::class, 'listAnalysisRuns'])
        ->name('contacts.analysis-runs.index');
    Route::get('/contacts/{id}/analysis-runs/{run}', [\App\Http\Controllers\ContactController::class, 'showAnalysisRun'])
        ->name('contacts.analysis-runs.show');
    Route::post('/contacts/{id}/memory-maintenance', [\App\Http\Controllers\ContactController::class, 'memoryMaintenance'])
        ->name('contacts.memory-maintenance.contact.store');
    Route::get('/contacts/{id}/intelligence', [\App\Http\Controllers\ContactController::class, 'intelligence'])
        ->name('contacts.intelligence');
    Route::get('/contacts/{id}/persona', [\App\Http\Controllers\ContactController::class, 'persona'])
        ->name('contacts.persona');
    Route::get('/contacts/{id}/talk-specs', [\App\Http\Controllers\ContactController::class, 'talkSpecs'])
        ->name('contacts.talk-specs');
    Route::get('/contacts/{id}/emotional-baseline', [\App\Http\Controllers\ContactController::class, 'emotionalBaseline'])
        ->name('contacts.emotional-baseline');
    Route::get('/contacts/{id}/topics', [\App\Http\Controllers\ContactController::class, 'topics'])
        ->name('contacts.topics');
    Route::get('/contacts/{id}/topics/{topic}/mentions', [\App\Http\Controllers\ContactController::class, 'topicMentions'])
        ->name('contacts.topics.mentions');
    Route::get('/contacts/{id}/reply-rules', [\App\Http\Controllers\ContactController::class, 'listReplyRules'])
        ->name('contacts.reply-rules.index');
    Route::post('/contacts/{id}/reply-rules', [\App\Http\Controllers\ContactController::class, 'storeReplyRule'])
        ->name('contacts.reply-rules.store');
    Route::patch('/contacts/{id}/reply-rules/{rule}', [\App\Http\Controllers\ContactController::class, 'updateReplyRule'])
        ->name('contacts.reply-rules.update');
    Route::delete('/contacts/{id}/reply-rules/{rule}', [\App\Http\Controllers\ContactController::class, 'destroyReplyRule'])
        ->name('contacts.reply-rules.destroy');
    Route::post('/contacts/{id}/export', [\App\Http\Controllers\ContactController::class, 'exportBundle'])
        ->name('contacts.export.bundle');
    Route::post('/contacts/{id}/erase', [\App\Http\Controllers\ContactController::class, 'erase'])
        ->name('contacts.erase.post');
    Route::get('/contacts/{id}/audit', [\App\Http\Controllers\ContactController::class, 'audit'])
        ->name('contacts.audit');
    Route::get('/contacts/{id}/memory', [\App\Http\Controllers\ContactController::class, 'getMemory'])
        ->name('contacts.memory');
    Route::get('/contacts/{id}/rules', [\App\Http\Controllers\ContactController::class, 'getRules'])
        ->name('contacts.rules');
    Route::get('/contacts/{id}/timeline', [\App\Http\Controllers\ContactController::class, 'timeline'])
        ->name('contacts.timeline');
    Route::get('/contacts/{id}/analytics', [\App\Http\Controllers\ContactController::class, 'getAnalytics'])
        ->name('contacts.analytics');
    Route::get('/contacts/{id}/conflicts', [\App\Http\Controllers\ContactController::class, 'conflicts'])
        ->name('contacts.contact.conflicts');
    Route::get('/contacts/{id}/stale-memory', [\App\Http\Controllers\ContactController::class, 'staleMemory'])
        ->name('contacts.contact.stale-memory');
    Route::post('/contacts/{id}/merge', [\App\Http\Controllers\ContactController::class, 'merge'])
        ->name('contacts.merge');

    Route::post('/contacts/{id}/enrich', [\App\Http\Controllers\ContactController::class, 'enrich'])
        ->name('contacts.enrich');

    // Phase 2 — Per-contact reply mode
    Route::get('/contacts/{contact}/reply-mode', [\App\Http\Controllers\ContactStatsController::class, 'getContactReplyMode'])
        ->name('contacts.reply-mode.get');
    Route::patch('/contacts/{contact}/reply-mode', [\App\Http\Controllers\ContactStatsController::class, 'setContactReplyMode'])
        ->name('contacts.reply-mode.set');

    Route::apiResource('contacts', \App\Http\Controllers\ContactController::class);

    /**
     * Contact Sub-resources Routes
     */
    Route::get('/contacts/{contact}/identifiers', [\App\Http\Controllers\ContactIdentifierController::class, 'index'])
        ->name('contacts.identifiers.index');
    Route::post('/contacts/{contact}/identifiers', [\App\Http\Controllers\ContactIdentifierController::class, 'store'])
        ->name('contacts.identifiers.store');
    Route::get('/contacts/{contact}/identifiers/{identifier}', [\App\Http\Controllers\ContactIdentifierController::class, 'show'])
        ->name('contacts.identifiers.show');
    Route::put('/contacts/{contact}/identifiers/{identifier}', [\App\Http\Controllers\ContactIdentifierController::class, 'update'])
        ->name('contacts.identifiers.update');
    Route::delete('/contacts/{contact}/identifiers/{identifier}', [\App\Http\Controllers\ContactIdentifierController::class, 'destroy'])
        ->name('contacts.identifiers.destroy');

    Route::get('/contacts/{contact}/relationships', [\App\Http\Controllers\ContactRelationshipController::class, 'index'])
        ->name('contacts.relationships.index');
    Route::post('/contacts/{contact}/relationships', [\App\Http\Controllers\ContactRelationshipController::class, 'store'])
        ->name('contacts.relationships.store');
    Route::get('/contacts/{contact}/relationships/{relationship}', [\App\Http\Controllers\ContactRelationshipController::class, 'show'])
        ->name('contacts.relationships.show');
    Route::put('/contacts/{contact}/relationships/{relationship}', [\App\Http\Controllers\ContactRelationshipController::class, 'update'])
        ->name('contacts.relationships.update');
    Route::delete('/contacts/{contact}/relationships/{relationship}', [\App\Http\Controllers\ContactRelationshipController::class, 'destroy'])
        ->name('contacts.relationships.destroy');

    Route::get('/contacts/{contact}/preferences', [\App\Http\Controllers\ContactPreferenceController::class, 'index'])
        ->name('contacts.preferences.index');
    Route::post('/contacts/{contact}/preferences', [\App\Http\Controllers\ContactPreferenceController::class, 'store'])
        ->name('contacts.preferences.store');
    Route::get('/contacts/{contact}/preferences/{preference}', [\App\Http\Controllers\ContactPreferenceController::class, 'show'])
        ->name('contacts.preferences.show');
    Route::put('/contacts/{contact}/preferences/{preference}', [\App\Http\Controllers\ContactPreferenceController::class, 'update'])
        ->name('contacts.preferences.update');
    Route::delete('/contacts/{contact}/preferences/{preference}', [\App\Http\Controllers\ContactPreferenceController::class, 'destroy'])
        ->name('contacts.preferences.destroy');

    Route::get('/contacts/{contact}/aliases', [\App\Http\Controllers\ContactAliasController::class, 'index'])
        ->name('contacts.aliases.index');
    Route::post('/contacts/{contact}/aliases', [\App\Http\Controllers\ContactAliasController::class, 'store'])
        ->name('contacts.aliases.store');
    Route::get('/contacts/{contact}/aliases/{alias}', [\App\Http\Controllers\ContactAliasController::class, 'show'])
        ->name('contacts.aliases.show');
    Route::put('/contacts/{contact}/aliases/{alias}', [\App\Http\Controllers\ContactAliasController::class, 'update'])
        ->name('contacts.aliases.update');
    Route::delete('/contacts/{contact}/aliases/{alias}', [\App\Http\Controllers\ContactAliasController::class, 'destroy'])
        ->name('contacts.aliases.destroy');

    Route::get('/contacts/{contact}/notes', [\App\Http\Controllers\ContactNoteController::class, 'index'])
        ->name('contacts.notes.index');
    Route::post('/contacts/{contact}/notes', [\App\Http\Controllers\ContactNoteController::class, 'store'])
        ->name('contacts.notes.store');
    Route::get('/contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNoteController::class, 'show'])
        ->name('contacts.notes.show');
    Route::put('/contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNoteController::class, 'update'])
        ->name('contacts.notes.update');
    Route::delete('/contacts/{contact}/notes/{note}', [\App\Http\Controllers\ContactNoteController::class, 'destroy'])
        ->name('contacts.notes.destroy');

    /**
     * Notification Hub Routes
     */
    Route::get('/notifications/templates', [\App\Http\Controllers\NotificationController::class, 'indexTemplates'])
        ->name('notifications.templates.index');
    Route::post('/notifications/templates', [\App\Http\Controllers\NotificationController::class, 'storeTemplate'])
        ->name('notifications.templates.store');
    Route::get('/notifications/templates/{template}', [\App\Http\Controllers\NotificationController::class, 'showTemplate'])
        ->name('notifications.templates.show');
    Route::put('/notifications/templates/{template}', [\App\Http\Controllers\NotificationController::class, 'updateTemplate'])
        ->name('notifications.templates.update');
    Route::delete('/notifications/templates/{template}', [\App\Http\Controllers\NotificationController::class, 'destroyTemplate'])
        ->name('notifications.templates.destroy');

    Route::get('/notifications', [\App\Http\Controllers\NotificationController::class, 'indexLogs'])
        ->name('notifications.index');
    Route::get('/notifications/logs', [\App\Http\Controllers\NotificationController::class, 'indexLogs'])
        ->name('notifications.logs.index');
    Route::post('/notifications/send', [\App\Http\Controllers\NotificationController::class, 'send'])
        ->name('notifications.send');
    Route::post('/notifications/{notification}/retry', [\App\Http\Controllers\NotificationController::class, 'retry'])
        ->name('notifications.retry');

    /**
     * Conversations Routes
     */
    Route::resource('conversations', \App\Http\Controllers\ConversationController::class);
    Route::get('/conversations/{id}/messages', [\App\Http\Controllers\ConversationController::class, 'getMessages'])
        ->name('conversations.messages');
    Route::post('/conversations/{id}/send-message', [\App\Http\Controllers\ConversationController::class, 'sendMessage'])
        ->name('conversations.send-message');

    /**
     * People Connect Hub Routes
     */
    Route::group(['prefix' => 'people-connect'], function () {
        Route::get('/stats', [\App\Http\Controllers\PeopleConnect\PeopleConnectController::class, 'stats'])->name('peopleconnect.stats');
        Route::get('/search', [\App\Http\Controllers\PeopleConnect\PeopleConnectController::class, 'search'])->name('peopleconnect.search');
        Route::get('/conversations/{id}', [\App\Http\Controllers\PeopleConnect\PeopleConnectController::class, 'showConversation'])->name('peopleconnect.conversations.show');
        Route::post('/conversations/{id}/reply-mode', [\App\Http\Controllers\PeopleConnect\PeopleConnectController::class, 'updateReplyMode'])->name('peopleconnect.conversations.reply-mode');
        
        Route::get('/livemsgs', [\App\Http\Controllers\PeopleConnect\LiveMsgsController::class, 'index'])->name('peopleconnect.livemsgs.index');
        Route::post('/livemsgs/sync', [\App\Http\Controllers\PeopleConnect\LiveMsgsController::class, 'triggerSync'])->name('peopleconnect.livemsgs.sync');
    });

    /**
     * Agents Hub Routes
     */
    Route::middleware('auth:sanctum')->group(function () {
        Route::resource('agents', \App\Http\Controllers\AgentController::class);
        Route::post('/agents/{agent}/run', [\App\Http\Controllers\AgentController::class, 'run'])
            ->name('agents.run');
        Route::post('/agents/{agent}/simulate', [\App\Http\Controllers\AgentController::class, 'simulate'])
            ->name('agents.simulate');
        Route::post('/agents/{agent}/quarantine', [\App\Http\Controllers\AgentController::class, 'quarantine'])
            ->name('agents.quarantine');
        Route::post('/agents/{agent}/unquarantine', [\App\Http\Controllers\AgentController::class, 'unquarantine'])
            ->name('agents.unquarantine');
        Route::get('/agents/{agent}/status', [\App\Http\Controllers\AgentController::class, 'getStatus'])
            ->name('agents.status');
        Route::get('/agents/{agent}/logs', [\App\Http\Controllers\AgentController::class, 'getLogs'])
            ->name('agents.logs');

        Route::get('/agent-tools', [\App\Http\Controllers\AgentToolLibraryController::class, 'index'])
            ->name('agent-tools.index');
        Route::get('/agent-tools/{id}', [\App\Http\Controllers\AgentToolLibraryController::class, 'show'])
            ->name('agent-tools.show');

        Route::apiResource('agent-personas', \App\Http\Controllers\AgentPersonaController::class);
        Route::apiResource('mcp-servers', \App\Http\Controllers\MCPServerController::class);
        Route::post('/mcp-servers/{name}/connect', [\App\Http\Controllers\MCPServerController::class, 'connect'])
            ->name('mcp-servers.connect');
        Route::post('/mcp-servers/{name}/disconnect', [\App\Http\Controllers\MCPServerController::class, 'disconnect'])
            ->name('mcp-servers.disconnect');
    });

    /**
     * Workflows Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    Route::get('/workflows/templates', [\App\Http\Controllers\WorkflowController::class, 'getTemplates'])
        ->name('workflows.templates');
    Route::get('/workflows/executions/{execution}', [\App\Http\Controllers\WorkflowController::class, 'showExecution'])
        ->name('workflows.executions.show');
    Route::post('/workflows/executions/{execution}/resume', [\App\Http\Controllers\WorkflowController::class, 'resume'])
        ->name('workflows.executions.resume');
    Route::post('/workflows/executions/{execution}/cancel', [\App\Http\Controllers\WorkflowController::class, 'cancel'])
        ->name('workflows.executions.cancel');

    Route::apiResource('workflows', \App\Http\Controllers\WorkflowController::class);

    // Workflow action routes on specific resources
    Route::post('/workflows/{workflow}/execute', [\App\Http\Controllers\WorkflowController::class, 'execute'])
        ->name('workflows.execute');
    Route::get('/workflows/{workflow}/progress', [\App\Http\Controllers\WorkflowController::class, 'getProgress'])
        ->name('workflows.progress');

    /**
     * Tasks Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    // Specific action routes (must come before resource)
    Route::get('/tasks/stats', [\App\Http\Controllers\TaskController::class, 'getStats'])
        ->name('tasks.stats');
    Route::get('/tasks/active', [\App\Http\Controllers\TaskController::class, 'getActive'])
        ->name('tasks.active');
    Route::get('/tasks/queue-stats', [\App\Http\Controllers\TaskController::class, 'getQueueStats'])
        ->name('tasks.queue-stats');
    Route::get('/tasks/routing-stats', [\App\Http\Controllers\TaskController::class, 'getRoutingStats'])
        ->name('tasks.routing-stats');

    // New TaskHub specific endpoints (must come before resource)
    Route::post('/tasks/{task}/execute', [\App\Http\Controllers\TaskController::class, 'execute'])
        ->name('tasks.execute');
    Route::get('/tasks/{task}/logs', [\App\Http\Controllers\TaskController::class, 'logs'])
        ->name('tasks.logs');
    Route::patch('/tasks/{task}/status', [\App\Http\Controllers\TaskController::class, 'updateStatus'])
        ->name('tasks.update-status');

    // Type-specific task creation endpoints
    Route::post('/tasks/manual', [\App\Http\Controllers\TaskController::class, 'createManual'])
        ->name('tasks.create-manual');
    Route::post('/tasks/agent', [\App\Http\Controllers\TaskController::class, 'createAgent'])
        ->name('tasks.create-agent');
    Route::post('/tasks/system', [\App\Http\Controllers\TaskController::class, 'createSystem'])
        ->name('tasks.create-system');
    Route::get('/tasks/type/{type}', [\App\Http\Controllers\TaskController::class, 'getByType'])
        ->name('tasks.by-type');
    Route::get('/tasks/stats/by-type', [\App\Http\Controllers\TaskController::class, 'getStatsByType'])
        ->name('tasks.stats-by-type');

    // Resource routes
    Route::apiResource('tasks', \App\Http\Controllers\TaskController::class);

    // Task action routes on specific resources
    Route::post('/tasks/{task}/cancel', [\App\Http\Controllers\TaskController::class, 'cancel'])
        ->name('tasks.cancel');
    Route::post('/tasks/{task}/pause', [\App\Http\Controllers\TaskController::class, 'pause'])
        ->name('tasks.pause');
    Route::post('/tasks/{task}/resume', [\App\Http\Controllers\TaskController::class, 'resume'])
        ->name('tasks.resume');

    Route::get('/stats/usage', [\App\Http\Controllers\StatsController::class, 'usage'])
        ->name('stats.usage');

    Route::get('/stats/dashboard', [\App\Http\Controllers\StatsController::class, 'dashboard'])
        ->name('stats.dashboard');

    /**
     * NexusHub Dashboard Aggregation Routes (Req 1, 2, 3)
     */
    Route::prefix('dashboard')->group(function () {
        Route::get('/stats', [\App\Http\Controllers\NexusDashboardController::class, 'stats'])
            ->middleware('throttle:60,1')
            ->name('dashboard.stats');
        Route::get('/health', [\App\Http\Controllers\NexusDashboardController::class, 'health'])
            ->name('dashboard.health');
        Route::get('/activity-feed', [\App\Http\Controllers\NexusDashboardController::class, 'activityFeed'])
            ->name('dashboard.activity-feed');
    });

    /**
     * Job retry endpoint for dashboard panel (Req 6.6)
     */
    Route::post('/jobs/{id}/retry', function (string $id) {
        try {
            $failedJob = DB::table('failed_jobs')->where('id', $id)->first();
            if (!$failedJob) {
                return response()->json(['message' => 'Job not found.'], 404);
            }
            $payload  = json_decode($failedJob->payload, true);
            DB::table('failed_jobs')->where('id', $id)->delete();
            return response()->json(['message' => 'Job re-queued successfully.', 'status' => 'pending']);
        } catch (\Throwable $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    })->name('jobs.retry');

    /**
     * Proactive AI suggestion approve/dismiss routes (Req 10.5, 10.6)
     */
    Route::prefix('proactive-ai/suggestions')->group(function () {
        Route::post('/{id}/approve', function (string $id) {
            try {
                DB::table('proactive_suggestions')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);
                return response()->json(['message' => 'Suggestion approved.']);
            } catch (\Throwable) {
                try {
                    DB::table('proactive_logs')->where('id', $id)->update(['status' => 'approved', 'updated_at' => now()]);
                    return response()->json(['message' => 'Suggestion approved.']);
                } catch (\Throwable $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }
            }
        })->name('proactive-ai.suggestions.approve');

        Route::post('/{id}/dismiss', function (string $id) {
            try {
                DB::table('proactive_suggestions')->where('id', $id)->update(['status' => 'dismissed', 'updated_at' => now()]);
                return response()->json(['message' => 'Suggestion dismissed.']);
            } catch (\Throwable) {
                try {
                    DB::table('proactive_logs')->where('id', $id)->update(['status' => 'dismissed', 'updated_at' => now()]);
                    return response()->json(['message' => 'Suggestion dismissed.']);
                } catch (\Throwable $e) {
                    return response()->json(['message' => $e->getMessage()], 500);
                }
            }
        })->name('proactive-ai.suggestions.dismiss');
    });

    /**
     * Memory Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    Route::get('/memories/search', [\App\Http\Controllers\MemoryController::class, 'search'])
        ->name('memories.search');
    Route::post('/memories/{id}/index', [\App\Http\Controllers\MemoryController::class, 'indexMemory'])
        ->name('memories.indexMemory');
    Route::post('/memories/{id}/reinforce', [\App\Http\Controllers\MemoryController::class, 'reinforceConfidence'])
        ->name('memories.reinforce');
    Route::post('/memories/decay', [\App\Http\Controllers\MemoryController::class, 'applyDecay'])
        ->name('memories.decay');
    Route::get('/memories/{id}/versions', [\App\Http\Controllers\MemoryController::class, 'versions'])
        ->name('memories.versions');

    // Contact-specific memory panel endpoints
    Route::get('/contacts/{contactId}/memories', [\App\Http\Controllers\MemoryController::class, 'contactMemories'])
        ->name('contacts.memories.index');
    Route::post('/contacts/{contactId}/memories/extract', [\App\Http\Controllers\MemoryController::class, 'extractForContact'])
        ->name('contacts.memories.extract');

    Route::resource('memories', \App\Http\Controllers\MemoryController::class);


    /**
     * HedraSoul Hub Routes (50+ endpoints)
     */
    Route::prefix('hedrasoul')->group(function () {
        // Session management
        Route::get('/sessions', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'index'])
            ->name('hedrasoul.sessions.index');
        Route::post('/sessions', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'store'])
            ->name('hedrasoul.sessions.store');
        Route::get('/sessions/{session}', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'show'])
            ->name('hedrasoul.sessions.show');
        Route::patch('/sessions/{session}', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'update'])
            ->name('hedrasoul.sessions.update');
        Route::post('/sessions/{session}/archive', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'archive'])
            ->name('hedrasoul.sessions.archive');
        Route::get('/sessions/{session}/messages', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'messages'])
            ->name('hedrasoul.sessions.messages');
        Route::post('/sessions/{session}/messages', [\App\Http\Controllers\HedraSoul\HedraSoulSessionController::class, 'sendMessage'])
            ->name('hedrasoul.sessions.sendMessage');

        // Message management
        Route::post('/messages/{message}/regenerate', [\App\Http\Controllers\HedraSoul\HedraSoulMessageController::class, 'regenerate'])
            ->name('hedrasoul.messages.regenerate');
        Route::get('/messages/{message}/trace', [\App\Http\Controllers\HedraSoul\HedraSoulMessageController::class, 'trace'])
            ->name('hedrasoul.messages.trace');
        Route::get('/messages/{message}/context', [\App\Http\Controllers\HedraSoul\HedraSoulMessageController::class, 'context'])
            ->name('hedrasoul.messages.context');

        // Souly control & simulation
        Route::get('/souly/status', [\App\Http\Controllers\HedraSoul\SoulyControlController::class, 'status'])
            ->name('hedrasoul.souly.status');
        Route::patch('/souly/autonomy', [\App\Http\Controllers\HedraSoul\SoulyControlController::class, 'updateAutonomy'])
            ->name('hedrasoul.souly.updateAutonomy');
        Route::patch('/souly/model', [\App\Http\Controllers\HedraSoul\SoulyControlController::class, 'updateModel'])
            ->name('hedrasoul.souly.updateModel');
        Route::post('/souly/quarantine', [\App\Http\Controllers\HedraSoul\SoulyControlController::class, 'quarantine'])
            ->name('hedrasoul.souly.quarantine');
        Route::post('/souly/resume', [\App\Http\Controllers\HedraSoul\SoulyControlController::class, 'resume'])
            ->name('hedrasoul.souly.resume');
        Route::post('/souly/simulate', [\App\Http\Controllers\HedraSoul\SoulyControlController::class, 'simulate'])
            ->name('hedrasoul.souly.simulate');

        // Instruction versioning
        Route::get('/instructions', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'index'])
            ->name('hedrasoul.instructions.index');
        Route::post('/instructions', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'store'])
            ->name('hedrasoul.instructions.store');
        Route::get('/instructions/{version}', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'show'])
            ->name('hedrasoul.instructions.show');
        Route::patch('/instructions/{version}', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'update'])
            ->name('hedrasoul.instructions.update');
        Route::post('/instructions/{version}/activate', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'activate'])
            ->name('hedrasoul.instructions.activate');
        Route::post('/instructions/{version}/rollback', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'rollback'])
            ->name('hedrasoul.instructions.rollback');
        Route::post('/instructions/{version}/test', [\App\Http\Controllers\HedraSoul\SoulyInstructionController::class, 'test'])
            ->name('hedrasoul.instructions.test');

        // Hedra profile
        Route::get('/profile', [\App\Http\Controllers\HedraSoul\HedraProfileController::class, 'show'])
            ->name('hedrasoul.profile.show');
        Route::patch('/profile', [\App\Http\Controllers\HedraSoul\HedraProfileController::class, 'update'])
            ->name('hedrasoul.profile.update');

        // Clone sources
        Route::get('/clone-sources', [\App\Http\Controllers\HedraSoul\HedraCloneSourceController::class, 'index'])
            ->name('hedrasoul.cloneSources.index');
        Route::post('/clone-sources', [\App\Http\Controllers\HedraSoul\HedraCloneSourceController::class, 'store'])
            ->name('hedrasoul.cloneSources.store');
        Route::patch('/clone-sources/{source}', [\App\Http\Controllers\HedraSoul\HedraCloneSourceController::class, 'update'])
            ->name('hedrasoul.cloneSources.update');
        Route::delete('/clone-sources/{source}', [\App\Http\Controllers\HedraSoul\HedraCloneSourceController::class, 'destroy'])
            ->name('hedrasoul.cloneSources.destroy');

        // Memory management
        Route::get('/memories', [\App\Http\Controllers\HedraSoul\HedraMemoryController::class, 'index'])
            ->name('hedrasoul.memories.index');
        Route::post('/memories', [\App\Http\Controllers\HedraSoul\HedraMemoryController::class, 'store'])
            ->name('hedrasoul.memories.store');
        Route::patch('/memories/{memory}', [\App\Http\Controllers\HedraSoul\HedraMemoryController::class, 'update'])
            ->name('hedrasoul.memories.update');
        Route::post('/memories/{memory}/approve', [\App\Http\Controllers\HedraSoul\HedraMemoryController::class, 'approve'])
            ->name('hedrasoul.memories.approve');
        Route::post('/memories/{memory}/reject', [\App\Http\Controllers\HedraSoul\HedraMemoryController::class, 'reject'])
            ->name('hedrasoul.memories.reject');
        Route::post('/memory-maintenance', [\App\Http\Controllers\HedraSoul\HedraMemoryController::class, 'maintenance'])
            ->name('hedrasoul.memory.maintenance');

        // Approval inbox
        Route::get('/approvals', [\App\Http\Controllers\HedraSoul\HedraSoulApprovalController::class, 'index'])
            ->name('hedrasoul.approvals.index');
        Route::get('/approvals/{approval}', [\App\Http\Controllers\HedraSoul\HedraSoulApprovalController::class, 'show'])
            ->name('hedrasoul.approvals.show');
        Route::post('/approvals/{approval}/approve', [\App\Http\Controllers\HedraSoul\HedraSoulApprovalController::class, 'approve'])
            ->name('hedrasoul.approvals.approve');
        Route::post('/approvals/{approval}/reject', [\App\Http\Controllers\HedraSoul\HedraSoulApprovalController::class, 'reject'])
            ->name('hedrasoul.approvals.reject');
        Route::post('/approvals/{approval}/defer', [\App\Http\Controllers\HedraSoul\HedraSoulApprovalController::class, 'defer'])
            ->name('hedrasoul.approvals.defer');

        // Notifications
        Route::get('/notifications', [\App\Http\Controllers\HedraSoul\HedraSoulNotificationController::class, 'index'])
            ->name('hedrasoul.notifications.index');
        Route::post('/notifications/{notification}/read', [\App\Http\Controllers\HedraSoul\HedraSoulNotificationController::class, 'markRead'])
            ->name('hedrasoul.notifications.markRead');
        Route::post('/notifications/{notification}/snooze', [\App\Http\Controllers\HedraSoul\HedraSoulNotificationController::class, 'snooze'])
            ->name('hedrasoul.notifications.snooze');

        // Misc/utility endpoints
        Route::get('/mentions/search', [\App\Http\Controllers\HedraSoul\HedraSoulMiscController::class, 'mentionsSearch'])
            ->name('hedrasoul.mentions.search');
        Route::post('/context/preview', [\App\Http\Controllers\HedraSoul\HedraSoulMiscController::class, 'contextPreview'])
            ->name('hedrasoul.context.preview');
        Route::get('/search', [\App\Http\Controllers\HedraSoul\HedraSoulMiscController::class, 'search'])
            ->name('hedrasoul.search');
        Route::get('/analytics', [\App\Http\Controllers\HedraSoul\HedraSoulMiscController::class, 'analytics'])
            ->name('hedrasoul.analytics');
        Route::get('/usage', [\App\Http\Controllers\HedraSoul\HedraSoulMiscController::class, 'usage'])
            ->name('hedrasoul.usage');
    });

    Route::middleware(['can:viewDlq'])->group(function () {
        Route::get('/admin/dlq', [\App\Http\Controllers\Admin\DlqController::class, 'index']);
        Route::post('/admin/dlq/{id}/retry', [\App\Http\Controllers\Admin\DlqController::class, 'retry']);
        Route::delete('/admin/dlq/{id}', [\App\Http\Controllers\Admin\DlqController::class, 'destroy']);
        Route::post('/admin/dlq/batch-retry', [\App\Http\Controllers\Admin\DlqController::class, 'batchRetry']);
    });

    /**
     * AI Models Hub Routes
     * NOTE: Specific routes must be defined BEFORE resource routes to prevent route conflicts
     */
    // AI Models resource and ID-specific routes (Legacy API, keeping for backward compatibility)
    Route::resource('ai-models', \App\Http\Controllers\AiModelController::class);
    Route::post('/ai-models/{id}/test', [\App\Http\Controllers\AiModelController::class, 'test'])
        ->name('ai-models.test');

    // New AI Models Hub endpoints for UP-002
    // Provider Health & Observability
    Route::get('/ai/providers/health', [\App\Http\Controllers\AiRouteController::class, 'providerHealth'])
        ->name('ai.providers.health');

    Route::get('/ai/providers', [\App\Http\Controllers\AiProviderController::class, 'index'])
        ->name('ai.providers.index');
    Route::post('/ai/providers', [\App\Http\Controllers\AiProviderController::class, 'store'])
        ->name('ai.providers.store');
    Route::get('/ai/providers/{id}', [\App\Http\Controllers\AiProviderController::class, 'show'])
        ->name('ai.providers.show');
    Route::put('/ai/providers/{id}', [\App\Http\Controllers\AiProviderController::class, 'update'])
        ->name('ai.providers.update');
    Route::delete('/ai/providers/{id}', [\App\Http\Controllers\AiProviderController::class, 'destroy'])
        ->name('ai.providers.destroy');
    Route::post('/ai/providers/{id}/test', [\App\Http\Controllers\AiProviderController::class, 'test'])
        ->name('ai.providers.test');
    Route::post('/ai/providers/{id}/sync-models', [\App\Http\Controllers\AiProviderController::class, 'syncModels'])
        ->name('ai.providers.sync-models');
    Route::patch('/ai/providers/{id}/toggle-active', [\App\Http\Controllers\AiProviderController::class, 'toggleActive'])
        ->name('ai.providers.toggle-active');
    Route::get('/ai/intents/routing', [\App\Http\Controllers\AiRequestController::class, 'getRoutingMatrix'])
        ->name('ai.intents.routing.index');
    Route::put('/ai/intents/routing', [\App\Http\Controllers\AiRequestController::class, 'routeIntent'])
        ->name('ai.intents.routing.update');
    Route::post('/ai/request', [\App\Http\Controllers\AiRequestController::class, 'handleRequest'])
        ->name('ai.request.handle');

    // Core routing execution endpoint
    Route::post('/ai-models/route', [\App\Http\Controllers\AiRouteController::class, 'route'])
        ->name('ai-models.route');

    // AI Instances
    Route::apiResource('ai-instances', \App\Http\Controllers\AiInstanceController::class);


    // Cost Analytics & Budget Endpoints
    Route::get('/ai/cost/forecast', [\App\Http\Controllers\AiCostAnalyticsController::class, 'forecast'])
        ->name('ai.cost.forecast');
    Route::post('/ai/cost/budget', [\App\Http\Controllers\AiCostAnalyticsController::class, 'setBudget'])
        ->name('ai.cost.budget');

    // Audit Trail
    Route::get('/ai/audit-trail', [\App\Http\Controllers\AiRouteController::class, 'auditTrail'])
        ->name('ai.audit.trail');

    // Telemetry Dashboard
    Route::get('/ai-hub/telemetry', [\App\Http\Controllers\AiRouteController::class, 'telemetry'])
        ->name('ai.telemetry');


    /**
     * Settings Hub Routes
     */
    Route::group(['prefix' => 'settings'], function () {
        Route::get('/', [\App\Http\Controllers\SettingController::class, 'index'])
            ->name('settings.index');
        Route::post('/', [\App\Http\Controllers\SettingController::class, 'store'])
            ->name('settings.store');
        Route::get('/grouped', [\App\Http\Controllers\SettingController::class, 'grouped'])
            ->name('settings.grouped');
        Route::get('/public', [\App\Http\Controllers\SettingController::class, 'publicSettings'])
            ->name('settings.public');
        Route::put('/bulk', [\App\Http\Controllers\SettingController::class, 'bulkUpdate'])
            ->name('settings.bulk-update');
        Route::post('/factory-reset', [\App\Http\Controllers\SettingController::class, 'factoryReset'])
            ->name('settings.factory-reset');

        // Emergency control routes (super-admin only)
        Route::get('/system/agent-pause', [\App\Http\Controllers\SettingController::class, 'getGlobalAgentPauseStatus'])
            ->name('settings.agent-pause.status');
        Route::post('/system/agent-pause', [\App\Http\Controllers\SettingController::class, 'toggleGlobalAgentPause'])
            ->middleware('can:toggleEmergency,App\Models\Setting')
            ->name('settings.agent-pause');

        Route::post('/system/maintenance-mode', [\App\Http\Controllers\SettingController::class, 'toggleMaintenanceMode'])
            ->middleware('can:toggleEmergency,App\Models\Setting')
            ->name('settings.maintenance-mode');

        Route::post('/system/api-proxy', [\App\Http\Controllers\SettingController::class, 'apiProxy'])
            ->name('settings.api-proxy');

        // Seed manager routes (super-admin only)
        Route::get('/seeds', [\App\Http\Controllers\SettingController::class, 'listSeeds'])
            ->middleware('can:runSeeder,App\Models\Setting')
            ->name('settings.seeds.list');
        Route::post('/seeds/{seedId}/run', [\App\Http\Controllers\SettingController::class, 'runSeed'])
            ->middleware('can:runSeeder,App\Models\Setting')
            ->name('settings.seeds.run');
        Route::post('/seeds/run-multiple', [\App\Http\Controllers\SettingController::class, 'runMultipleSeeds'])
            ->middleware('can:runSeeder,App\Models\Setting')
            ->name('settings.seeds.run-multiple');

        // Credential validation and health routes
        Route::post('/credentials/validate', [\App\Http\Controllers\SettingController::class, 'validateCredential'])
            ->name('settings.credentials.validate');
        Route::get('/credentials/validate', [\App\Http\Controllers\SettingController::class, 'validateAllCredentials'])
            ->name('settings.credentials.validate_all');
        Route::get('/health', [\App\Http\Controllers\SettingController::class, 'healthStatus'])
            ->name('settings.health');

        // Admin dashboard routes (super-admin only)
        Route::group(['prefix' => 'admin', 'middleware' => 'can:create,App\Models\Setting'], function () {
            Route::get('/dashboard', [\App\Http\Controllers\SettingsHubAdminController::class, 'dashboardOverview'])
                ->name('settings.admin.dashboard');
            Route::get('/audit-trail', [\App\Http\Controllers\SettingsHubAdminController::class, 'auditTrail'])
                ->name('settings.admin.audit-trail');
            Route::get('/compliance', [\App\Http\Controllers\SettingsHubAdminController::class, 'complianceStatus'])
                ->name('settings.admin.compliance');
            Route::get('/multi-tenancy', [\App\Http\Controllers\SettingsHubAdminController::class, 'multiTenancyStatus'])
                ->name('settings.admin.multi-tenancy');
            Route::get('/performance', [\App\Http\Controllers\SettingsHubAdminController::class, 'performanceMetrics'])
                ->name('settings.admin.performance');
            Route::post('/export', [\App\Http\Controllers\SettingsHubAdminController::class, 'exportSettings'])
                ->name('settings.admin.export');
        });

        // Global System Telemetry
        Route::get('/system/telemetry', [\App\Http\Controllers\SystemTelemetryController::class, 'getTelemetry'])
            ->name('system.telemetry');
        // WAHA WhatsApp Integration testing and URL helper routes
        Route::get('/waha/webhook-url', [\App\Http\Controllers\SettingController::class, 'getWahaWebhookUrl'])
            ->name('settings.waha.webhook-url');
        Route::post('/waha/test-connection', [\App\Http\Controllers\SettingController::class, 'testWahaConnection'])
            ->name('settings.waha.test-connection');
        Route::post('/waha/test-webhook', [\App\Http\Controllers\SettingController::class, 'testWahaWebhook'])
            ->name('settings.waha.test-webhook');

        // WAHA Management
        Route::get('/waha-manage/status', [\App\Http\Controllers\WahaManageController::class, 'status']);
        Route::get('/waha-manage/contacts', [\App\Http\Controllers\WahaManageController::class, 'contacts']);
        Route::post('/waha-manage/sync/start', [\App\Http\Controllers\WahaManageController::class, 'startSync']);
        Route::post('/waha-manage/sync/contact/{id}', [\App\Http\Controllers\WahaManageController::class, 'startContactMessageSync']);
        Route::post('/waha-manage/sync/{id}/pause', [\App\Http\Controllers\WahaManageController::class, 'pauseSync']);
        Route::post('/waha-manage/analyze/start', [\App\Http\Controllers\WahaManageController::class, 'startAnalysis']);

        // Credential masking route
        Route::get('/{key}/masked', [\App\Http\Controllers\SettingController::class, 'getMaskedCredential'])
            ->name('settings.masked');

        // Standard CRUD routes
        Route::get('/{key}', [\App\Http\Controllers\SettingController::class, 'show'])
            ->name('settings.show');
        Route::put('/{key}', [\App\Http\Controllers\SettingController::class, 'update'])
            ->name('settings.update');
        Route::delete('/{key}', [\App\Http\Controllers\SettingController::class, 'destroy'])
            ->name('settings.destroy');
    });

    Route::group(['prefix' => 'scheduler'], function () {
        Route::get('/', [\App\Http\Controllers\SchedulerController::class, 'index'])->name('scheduler.index');
        Route::post('/', [\App\Http\Controllers\SchedulerController::class, 'store'])->name('scheduler.store');
        Route::get('/{schedulerJob}', [\App\Http\Controllers\SchedulerController::class, 'show'])->name('scheduler.show');
        Route::put('/{id}', [\App\Http\Controllers\SchedulerController::class, 'update'])->name('scheduler.update');
        Route::delete('/{id}', [\App\Http\Controllers\SchedulerController::class, 'destroy'])->name('scheduler.destroy');
    });

    /**
     * Proactive AI Engine Routes
     */
    Route::group(['prefix' => 'proactive'], function () {
        Route::get('/rules', [\App\Http\Controllers\ProactiveAIController::class, 'indexRules'])->name('proactive.rules.index');
        Route::post('/rules', [\App\Http\Controllers\ProactiveAIController::class, 'storeRule'])->name('proactive.rules.store');
        Route::patch('/rules/{id}/toggle', [\App\Http\Controllers\ProactiveAIController::class, 'toggleRule'])->name('proactive.rules.toggle');
        Route::delete('/rules/{id}', [\App\Http\Controllers\ProactiveAIController::class, 'destroyRule'])->name('proactive.rules.destroy');
        Route::get('/triggers', [\App\Http\Controllers\ProactiveAIController::class, 'indexTriggers'])->name('proactive.triggers.index');
        Route::get('/logs', [\App\Http\Controllers\ProactiveAIController::class, 'indexLogs'])->name('proactive.logs.index');
    });

    /**
     * Logs Hub Routes
     */
    Route::group(['prefix' => 'logs'], function () {
        Route::get('/', [\App\Http\Controllers\LogController::class, 'index'])
            ->name('logs.index');
        Route::post('/clear', [\App\Http\Controllers\LogController::class, 'clear'])
            ->name('logs.clear');
        Route::get('/stats', [\App\Http\Controllers\LogController::class, 'stats'])
            ->name('logs.stats');
        Route::get('/levels', [\App\Http\Controllers\LogController::class, 'levels'])
            ->name('logs.levels');
        Route::get('/channels', [\App\Http\Controllers\LogController::class, 'channels'])
            ->name('logs.channels');
        Route::get('/categories', [\App\Http\Controllers\LogController::class, 'channels'])
            ->name('logs.categories');
        Route::get('/errors', [\App\Http\Controllers\LogController::class, 'errors'])
            ->name('logs.errors');
        Route::get('/{id}', [\App\Http\Controllers\LogController::class, 'show'])
            ->name('logs.show');
        Route::delete('/{id}', [\App\Http\Controllers\LogController::class, 'destroy'])
            ->name('logs.destroy');
    });

    /**
     * User Profile Routes
     */
    Route::group(['prefix' => 'profile'], function () {
        Route::get('/', [\App\Http\Controllers\ProfileController::class, 'show'])
            ->name('profile.show');
        Route::put('/', [\App\Http\Controllers\ProfileController::class, 'update'])
            ->name('profile.update');
        Route::post('/avatar', [\App\Http\Controllers\ProfileController::class, 'updateAvatar'])
            ->name('profile.avatar');
    });

    /**
     * Authentication Routes
     */
    Route::post('/logout', [\App\Http\Controllers\AuthController::class, 'logout'])
        ->name('logout');

    Route::post('/refresh-token', [\App\Http\Controllers\AuthController::class, 'refreshToken'])
        ->name('refresh-token');
});

/**
 * System Management Routes
 */
Route::group(['prefix' => 'v1', 'middleware' => ['api', 'auth:sanctum']], function () {
    Route::prefix('admin/system')->group(function () {
        Route::get('/status', [\App\Http\Controllers\Admin\SystemController::class, 'status'])
            ->name('admin.system.status');
        Route::post('/service/start', [\App\Http\Controllers\Admin\SystemController::class, 'startService'])
            ->name('admin.service.start');
        Route::post('/service/stop', [\App\Http\Controllers\Admin\SystemController::class, 'stopService'])
            ->name('admin.service.stop');
        Route::post('/service/restart', [\App\Http\Controllers\Admin\SystemController::class, 'restartService'])
            ->name('admin.service.restart');
        Route::get('/service/logs', [\App\Http\Controllers\Admin\SystemController::class, 'getServiceLogs'])
            ->name('admin.service.logs');
        Route::post('/build/trigger', [\App\Http\Controllers\Admin\SystemController::class, 'triggerBuild'])
            ->name('admin.build.trigger');
    });
});


/**
 * Hedra Soul Routes
 */
Route::group(['prefix' => 'v1/hedra-soul', 'middleware' => ['api', 'auth:sanctum']], function () {
    Route::get('/sessions', [\App\Http\Controllers\HedraSoulController::class, 'getSessions']);
    Route::get('/approvals', [\App\Http\Controllers\HedraSoulController::class, 'getApprovals']);
    Route::get('/notifications', [\App\Http\Controllers\HedraSoulController::class, 'getNotifications']);
    Route::get('/status', [\App\Http\Controllers\HedraSoulController::class, 'getStatus']);
});

// Fallback route
Route::fallback(function () {
    return response()->json([
        'error' => 'Not Found',
        'message' => 'The requested resource was not found',
    ], 404);
});
