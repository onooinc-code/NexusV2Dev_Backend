# AI Models Hub — Requirements

## 1. Overview
The AI Models Hub is a dynamic, database-driven infrastructure layer that enables Nexus to communicate with any AI provider without code changes. It provides centralized key management, cost tracking, health monitoring, and intelligent request routing.

---

## 2. Functional Requirements

### 2.1 Provider Registry
- Users MUST be able to register any AI provider with: `name`, `slug`, `type` (cloud/local/aggregator), `base_url`, `test_endpoint`.
- Providers MUST support activation/deactivation (`is_active` toggle) without redeployment.
- The system MUST auto-sync available models from the provider's API upon request.

### 2.2 API Key Management
- Each provider MUST support multiple API keys (for rotation).
- API keys MUST be stored AES-256 encrypted (never plaintext).
- Keys MUST track: `is_active`, `rotation_scheduled_at`, `last_rotated_at`, `last_used_at`.
- Only active keys MUST be used for outbound requests.

### 2.3 Connectivity Testing
- The system MUST allow testing provider connectivity via `POST /ai/providers/{id}/test`.
- The test MUST send a minimal prompt and validate a valid response is received.
- The result MUST update `last_synced_at` on the provider.

### 2.4 Intent Routing
- The system MUST maintain an `IntentRouting` table mapping intent names to providers and models.
- The routing matrix MUST support a fallback provider for each intent.
- Routing MUST check `conditions` (e.g., max cost, token limits) before dispatching.
- Users MUST be able to view and update routing rules via the API.

### 2.5 Circuit Breaker
- Each provider MUST have an independent circuit breaker.
- The circuit MUST open after N consecutive failures (configurable).
- The circuit MUST auto-reset after a configurable timeout.
- An open circuit MUST redirect requests to the fallback provider.

### 2.6 Usage & Cost Tracking
- Every AI request MUST be logged to `AiAuditTrail` with: `input_tokens`, `output_tokens`, `cost_usd`, `duration_ms`, `status`.
- The system MUST expose a cost forecast endpoint (`GET /ai/cost/forecast`).
- Administrators MUST be able to set a spending budget (`POST /ai/cost/budget`).

### 2.7 Telemetry Dashboard
- The system MUST expose an aggregated telemetry endpoint (`GET /ai-hub/telemetry`) returning:
  - Total requests, total tokens, total cost
  - Requests by provider, requests by model
  - Error rates
  - Average response time

### 2.8 AI Instances
- The system MUST support `AiInstance` entities: named, reusable combinations of a provider + model + settings.
- AI Instances are referenced by agents and other services.

---

## 3. Non-Functional Requirements

### 3.1 Security
- API keys MUST never be returned in plaintext via any API endpoint.
- Only admin-level users SHOULD manage provider configurations.

### 3.2 Reliability
- The circuit breaker MUST prevent cascading failures from a single provider outage.
- Fallback chains MUST be configurable per intent.

### 3.3 Extensibility
- Adding a new provider MUST require only a database record — no PHP code changes.
- The `DynamicRestProvider` universal adapter handles any OpenAI-compatible API.
