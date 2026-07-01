# NexusV3 — Bugs, Missing Requirements & Feature Gaps

> A living document. Update this as bugs are found and features are identified.

---

## 🔴 Critical Bugs

### B-001: Mem0Integration is a stub
**File:** `app/Integrations/Mem0Integration.php`  
**Status:** Open  
**Description:** All three methods (`store`, `search`, `delete`) return hardcoded `true` and `[]`. The real Mem0 HTTP API is never called. Any feature relying on Mem0 for memory storage or retrieval silently fails.  
**Fix Required:** Implement actual HTTP client calls to the Mem0 API with proper error handling.

---

### B-002: No automatic log pruning scheduled
**File:** `routes/console.php` / `bootstrap/app.php`  
**Status:** Open  
**Description:** `LogController@clear` and `LogService::clearOldLogs()` exist but are never scheduled via the Laravel Task Scheduler. The `logs` table will grow indefinitely.  
**Fix Required:** Add a scheduled command in `routes/console.php` to prune logs older than 30 days:
```php
Schedule::call(fn() => app(LogService::class)->clearOldLogs(30))->daily();
```

---

### B-003: Logs Hub UI uses mock data
**File:** `resources/views/hubs/logs.blade.php`  
**Status:** Open  
**Description:** The Blade view has a `setInterval` that injects hardcoded mock log lines into the DOM. It does not call `LogController@index`. The logs console shows fake data.  
**Fix Required:** Wire the UI to the actual `GET /api/v1/logs` endpoint and replace the mock injection with real AJAX polling or WebSocket subscriptions.

---

### B-004: Circuit breaker missing fallback chains
**File:** `app/Hubs/AIModelsHub.php` line 105  
**Status:** Open  
**Description:** The circuit breaker call passes an empty array `[]` for fallback providers. If the primary provider fails, there is no automatic failover.  
**Fix Required:** Implement the fallback provider resolution logic using the `fallback_provider_id` field on the `IntentRouting` model.

---

### B-005: NLP Parser AM/PM edge case
**File:** `app/Services/Proactive/NlpParserService.php`  
**Status:** Open  
**Description:** The regex `/at (\d+)\s*(am|pm)?/i` makes AM/PM optional and defaults to `am` if absent. A user typing "at 15" (24-hour format) will have AM incorrectly assumed, leading to an incorrect `next_run_at` timestamp.  
**Fix Required:** Add 24-hour detection logic before defaulting to AM.

---

## 🟡 Missing Requirements

### MR-001: WebSocket events not broadcast for Hedra Soul messages
**Description:** The `ProcessHedraSoulMessageJob` updates the message in the DB when complete, but does not broadcast a Laravel Echo event. The frontend must poll instead of receiving real-time updates.  
**Required:** Broadcast a `HedrasoulMessageCompleted` event on the `private-hedrasoul.{session}` channel after the job completes.

---

### MR-002: No Automatic Pruning for dead letter tasks
**Description:** `DeadLetterTask` entries accumulate but there's no scheduled job to prune or alert on them.  
**Required:** Scheduled command to flag old dead-letter tasks and optionally retry or purge them.

---

### MR-003: Missing composite database indexes
**Description:** Several high-traffic query patterns lack composite indexes:
- `logs` table: missing `(level, created_at)`
- `proactive_triggers` table: missing `(status, next_run_at)` 
- `agent_tasks` table: missing `(agent_id, status)`
**Required:** Add a new migration with these composite indexes.

---

### MR-004: Hedra Soul UI hardcodes model info
**File:** `resources/views/hubs/hedra-soul.blade.php`  
**Description:** The right-hand controls panel hardcodes "gpt-4o-2024-05-13" and "128k". Should dynamically fetch from `SoulyRuntimeProfile` or `AiInstance`.  
**Required:** AJAX call to `/api/v1/hedrasoul/souly/status` to populate model info.

---

### MR-005: Settings Hub missing API key masking on read
**Description:** When listing settings via `GET /api/v1/settings`, encrypted settings (API keys) should return a masked value (e.g., `sk-...***`), not the raw decrypted value.  
**Required:** Add a `mask` accessor/transformer in the settings Resource class.

---

### MR-006: GDPR erasure does not clear memories
**File:** `app/Http/Controllers/ContactController::erase()`  
**Description:** The contact erase endpoint likely deletes the contact record but may not cascade-delete associated `ContactMemory`, `ContactMessage`, `ContactAnalysisFinding` records if foreign key cascades are not set up.  
**Required:** Audit the `erase()` method and confirm all GDPR-relevant data is purged.

---

## 🟢 Planned Features (Not Yet Implemented)

### PF-001: Visual Workflow Builder
**Priority:** High  
**Description:** The current Workflow Hub creates workflows via raw JSON step definitions. A visual node-based drag-and-drop builder is planned.

### PF-002: Hedra Soul Approval Inbox in Chat UI
**Priority:** Medium  
**Description:** When Souly requests a destructive action in "Copilot" mode, an interactive approval card should appear inline in the chat thread, not on a separate page.

### PF-003: Elasticsearch Migration for Logs
**Priority:** Low  
**Description:** As the `logs` table grows to millions of rows, MySQL full-text search will degrade. Migrate the read path to Elasticsearch.

### PF-004: Cost Analytics Charts
**Priority:** Medium  
**Description:** Visualize `cost_usd` + `token_count` over time per provider using Chart.js in the AI Models Hub.

### PF-005: NLP Upgrade to LLM-based Parsing
**Priority:** High  
**Description:** Replace the regex-based `NlpParserService` in the Proactive AI Hub with a real LLM call to extract ECA components accurately.

### PF-006: Automated Memory Decay Scheduler
**Priority:** Medium  
**Description:** Schedule `MemoryController::applyDecay()` to run nightly, reducing confidence scores on unused memories.

### PF-007: Contact Relationship Graph Visualization
**Priority:** Low  
**Description:** `RelationshipGraphService` exists but has no UI. Build a visual force-directed graph showing contact relationships.

### PF-008: Multi-Workspace Support
**Priority:** High  
**Description:** The `Workspace` model and multi-tenant settings columns exist but are not fully wired into authorization. Implement workspace isolation.
