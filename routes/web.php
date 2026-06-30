<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\DashboardController;

// Dashboard routes
// Dashboard routes (Legacy - kept for backwards compatibility)
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard/data', [DashboardController::class, 'data'])->name('dashboard.data');
Route::post('/dashboard/clear-cache', [DashboardController::class, 'clearCache'])->name('dashboard.clear-cache');
Route::post('/dashboard/restart-queue', [DashboardController::class, 'restartQueue'])->name('dashboard.restart-queue');
Route::post('/dashboard/refresh-metric', [DashboardController::class, 'refreshMetric'])->name('dashboard.refresh-metric');

// All other routes handled by Next.js frontend
// API routes are in routes/api.php

// --- Nexus Monolithic Hubs Routes ---
use App\Http\Controllers\Web\HubController;

Route::prefix('hub')->group(function () {
    Route::get('/dashboard', [HubController::class, 'dashboard'])->name('hub.dashboard');
    
    // Contacts
    Route::get('/contacts', [HubController::class, 'contacts'])->name('hub.contacts');
    Route::post('/contacts', [HubController::class, 'storeContact'])->name('hub.contacts.store');
    Route::get('/contacts/{id}', [HubController::class, 'contactProfile'])->name('hub.contacts.profile');
    
    // Agents
    Route::get('/agents', [HubController::class, 'agents'])->name('hub.agents');
    Route::post('/agents', [HubController::class, 'storeAgent'])->name('hub.agents.store');
    Route::post('/agents/{id}/toggle', [HubController::class, 'toggleAgent'])->name('hub.agents.toggle');
    
    // Workflows
    Route::get('/workflows', [HubController::class, 'workflows'])->name('hub.workflows');
    
    // Memory
    Route::get('/memory', [HubController::class, 'memory'])->name('hub.memory');
    Route::post('/memory', [HubController::class, 'storeMemory'])->name('hub.memory.store');
    
    // Logs, Models, Settings
    Route::get('/logs', [HubController::class, 'logs'])->name('hub.logs');
    Route::get('/models', [HubController::class, 'models'])->name('hub.models');
    Route::post('/models/{id}/toggle', [HubController::class, 'toggleModel'])->name('hub.models.toggle');
    Route::get('/settings', [HubController::class, 'settings'])->name('hub.settings');
    Route::post('/settings', [HubController::class, 'updateSettings'])->name('hub.settings.update');
    // New Hubs
    Route::get('/people-connect', [HubController::class, 'peopleConnect'])->name('hub.people-connect');
    Route::get('/hedra-soul', [HubController::class, 'hedraSoul'])->name('hub.hedra-soul');
    Route::get('/proactive-ai', [HubController::class, 'proactiveAi'])->name('hub.proactive-ai');
    Route::get('/tasks', [HubController::class, 'tasks'])->name('hub.tasks');
    Route::get('/scheduler', [HubController::class, 'scheduler'])->name('hub.scheduler');
    Route::get('/apis', [HubController::class, 'apis'])->name('hub.apis');
    Route::get('/admin', [HubController::class, 'admin'])->name('hub.admin');
    Route::get('/waha', [HubController::class, 'waha'])->name('hub.waha');
    
    // WAHA Realtime Actions
    Route::post('/waha/sync', [HubController::class, 'triggerWahaSync'])->name('hub.waha.sync');
    Route::post('/people-connect/message', [HubController::class, 'sendContactMessage'])->name('hub.people-connect.message');
    Route::post('/hedra-soul/message', [HubController::class, 'sendHedraMessage'])->name('hub.hedra-soul.message');

    // Workflows Realtime Execution
    Route::post('/workflows/{workflow}/execute', [HubController::class, 'executeWorkflow'])->name('hub.workflows.execute');
    Route::get('/workflows/executions/{execution}', [HubController::class, 'showExecution'])->name('hub.workflows.execution');
    
    // Contact Favorites
    Route::post('/contacts/{id}/toggle-favorite', [HubController::class, 'toggleFavorite'])->name('hub.contacts.toggle-favorite');

    // Web Logout
    Route::post('/logout', [HubController::class, 'logoutWeb'])->name('hub.logout');

    // Agent Actions
    Route::post('/agents/{id}/restart', [HubController::class, 'restartAgent'])->name('hub.agents.restart');

    // Dashboard health and activity feed
    Route::get('/dashboard/health', [HubController::class, 'dashboardHealth'])->name('hub.dashboard.health');
    Route::get('/dashboard/activity-feed', [HubController::class, 'dashboardActivityFeed'])->name('hub.dashboard.activity-feed');
    Route::get('/system/telemetry', [\App\Http\Controllers\SystemTelemetryController::class, 'getTelemetry'])->name('hub.system.telemetry');
});
