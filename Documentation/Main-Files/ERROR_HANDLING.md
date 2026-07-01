# Nexus Project Error Handling & Debugging

## 1. Overview

This document defines the standardized error handling, logging, and debugging procedures for the Nexus platform. As an enterprise-grade Laravel 13 application heavily reliant on external AI APIs, asynchronous workflows, and real-time communication, robust error management is a critical pillar of the system's stability. The strategies outlined here ensure that errors are captured, reported, and mitigated with minimal user impact.

## 2. Global Error Handling

### 2.1. Exception Handler
Nexus centralizes exception handling within `bootstrap/app.php` (Laravel 11+ structure). The framework intercepts all unhandled exceptions and processes them through customized rendering and reporting pipelines.
- **Reporting**: Critical exceptions are logged to the configured channels (e.g., daily files, Slack, or Sentry).
- **Rendering**: JSON responses are guaranteed for all API routes, preventing HTML stack traces from leaking to API consumers.

### 2.2. Standardized API Error Responses
All API endpoints must return a predictable error format. When an error occurs (e.g., validation failure, unauthorized access, or internal server error), the response payload must follow this structure:
```json
{
    "success": false,
    "message": "A human-readable description of the error.",
    "errors": {
        "field_name": ["Specific validation or detailed error message."]
    },
    "code": "ERROR_CODE_STRING"
}
```
HTTP status codes must accurately reflect the error type (400 Bad Request, 401 Unauthorized, 403 Forbidden, 404 Not Found, 422 Unprocessable Entity, 500 Internal Server Error).

## 3. Specific Subsystem Error Handling

### 3.1. AI Provider Integration (`AiRequestController`)
The `AiRequestController` and underlying `DynamicProviderRegistry` communicate with external LLMs. Because these networks are inherently unreliable, Nexus employs:
- **Circuit Breakers**: `CircuitBreaker` classes prevent cascading failures when an AI provider experiences downtime.
- **Fallback Mechanisms**: Intent routing (`IntentRoutingEngine`) configures fallback providers and models if the primary provider fails.
- **Timeouts & Retries**: External HTTP requests via Laravel's `Http` facade are configured with strict timeouts and exponential backoff retry logic.

### 3.2. Asynchronous Workflows
The `WorkflowEngine` executes complex, multi-step processes via Laravel Horizon.
- **Dead Letter Queue**: Failed background jobs are captured in a Dead Letter Queue (`DeadLetterTask`), allowing for manual inspection and replay.
- **Job Status Tracking**: Workflow models (`WorkflowExecution`, `AgentTask`) maintain an explicit `status` field (`failed`, `error`). Logs are recorded in `WorkflowStepLog` to trace the exact point of failure.

### 3.3. HedraSoul & Real-Time Sync
Errors during real-time sync (e.g., WAHA WhatsApp sync) or HedraSoul evaluation loops are logged via `SystemLog` and `AgentRuntimeLog`. Crucial failures emit real-time notifications via Reverb/Echo to alert the system administrator dynamically.

## 4. Logging Standards

Nexus utilizes Laravel's built-in logging facilities mapped to various channels.

### 4.1. Log Levels
- **EMERGENCY**: System is unusable (e.g., database down).
- **ALERT**: Action must be taken immediately (e.g., third-party AI provider API key revoked).
- **CRITICAL**: Critical conditions (e.g., Workflow engine crashed).
- **ERROR**: Error conditions (e.g., API request failed after retries).
- **WARNING**: Warning conditions (e.g., rate limit approaching).
- **INFO**: Informational messages (e.g., Workflow started).
- **DEBUG**: Debug-level messages (e.g., Payload traces).

### 4.2. Contextual Logging
Logs must include contextual information to aid in debugging. Do not log just a string.
```php
Log::error('AI Provider request failed', [
    'provider_id' => $provider->id,
    'model' => $modelId,
    'error_message' => $e->getMessage(),
    'trace' => $e->getTraceAsString()
]);
```

## 5. Telemetry and Debugging Tools

- **Laravel Telescope**: Used in local and staging environments to inspect requests, jobs, and database queries.
- **Laravel Debugbar**: Available locally to profile memory usage, N+1 queries, and request execution time.
- **Pail**: Provides real-time log tailing across the application.

By adhering to these error handling and logging protocols, the Nexus platform guarantees high observability, rapid fault diagnosis, and a resilient user experience.
