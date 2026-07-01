# Nexus Architecture Specification

## 1. Architectural Overview

The Nexus platform employs a modular, domain-driven design built atop the Laravel 13 framework. It strictly adheres to modern architectural principles such as the SOLID design principles, Clean Architecture, and event-driven micro-patterns within a monolithic repository. The architecture is explicitly designed to handle high-throughput, asymmetric background tasks while maintaining a highly responsive user-facing API layer.

## 2. Core Architectural Layers

### 2.1. Routing and Transport Layer
- **API Routes**: Exposes stateless RESTful endpoints secured via Laravel Sanctum. Follows strict JSON API compliance.
- **Webhooks**: Unauthenticated or token-authenticated endpoints designated for external systems (e.g., WAHA, generic workflow triggers).
- **Websockets**: Utilizes Laravel Reverb (via Laravel Echo on the frontend) for low-latency, real-time bi-directional communication, primarily used for UI updates and notifications.

### 2.2. Controller & Request Pipeline
Controllers in Nexus are "thin." They are responsible purely for request validation (via FormRequests or inline Validators) and immediate HTTP response structuring. Business logic is strictly delegated to the Service Layer.

### 2.3. Service Layer (The Hubs)
The application is conceptually divided into "Hubs" representing bounded contexts:
- **AiModelsHub**: Manages dynamic provider registration, payload adaptation, and intent routing. Includes components like `IntentRoutingEngine` and `CircuitBreaker`.
- **PeopleConnect**: The CRM boundary. Handles contact aggregation, relationship mapping, and profile snapshotting.
- **HedraSoul**: The cognitive engine. Encapsulates Agent runtime loops, memory management, and specialized reasoning policies.
- **Workflow Hub**: The orchestration engine. Interprets step-based configuration data and executes it utilizing Laravel's job dispatcher.

### 2.4. Data Access Layer
Data persistence is handled by Laravel's Eloquent ORM. 
- **Fat Models, Thin Controllers**: Models contain scoped queries, relationship definitions, and minimal state-change methods.
- **Repositories**: Where complex querying logic or cross-model aggregations are required, Repository classes abstract the Eloquent complexity away from the Service Layer.

## 3. Asynchronous & Event-Driven Architecture

Given the latency inherent in LLM API calls and complex workflows, Nexus heavily utilizes background processing.

### 3.1. Queue Infrastructure
- **Laravel Horizon**: Powers the background task processing using Redis.
- **Queue Segregation**: Jobs are dispatched to specific queues based on priority and type (e.g., `ai-tasks`, `workflow-steps`, `notifications`, `default`).

### 3.2. Event Bus
Nexus employs Laravel's Event/Listener system to decouple side-effects from primary transactions. For example, when a `ContactMessage` is received via WAHA:
1. The message is stored in the database.
2. A `MessageReceived` event is fired.
3. Listeners handle side-effects: updating the `ContactProfileSnapshot`, dispatching an `AgentTask` to generate a reply, and broadcasting a WebSocket event to the UI.

## 4. Integration Specifications

### 4.1. Universal AI Adapter
To avoid vendor lock-in, Nexus utilizes a Factory pattern (`PayloadAdapterFactory`) to format generic prompt/context arrays into provider-specific schemas (e.g., OpenAI's message arrays vs. Anthropic's block format).

### 4.2. WAHA (WhatsApp HTTP API)
Nexus acts as both a consumer and provider for the WAHA system. It exposes webhook ingestion endpoints and communicates outbound via WAHA's REST API. State synchronization is managed by the `WahaSyncProcess` model.

## 5. Security Architecture

- **Authentication**: Stateful cookie-based authentication for the dashboard; token-based (Sanctum) for API clients.
- **API Key Security**: The `EncryptedApiKeyStorage` service ensures that third-party credentials (OpenAI keys, WAHA secrets) are encrypted using Laravel's native encryption (AES-256-CBC) before persistence.
- **SSRF Protection**: The `SsrfProtectionMiddleware` intercepts outbound HTTP requests directed at user-defined endpoints (e.g., in Workflows or dynamic AI providers) to prevent Server-Side Request Forgery against internal infrastructure.

## 6. Infrastructure & Deployment

The standard deployment topology for Nexus involves:
- **Application Servers**: Running PHP 8.4 via PHP-FPM / Nginx or Laravel Octane.
- **Database Server**: MySQL 8+ or PostgreSQL 15+.
- **Cache & Queue Store**: Redis (Sentinel/Cluster for high availability).
- **Worker Nodes**: Dedicated horizontal scaling for Laravel Horizon worker processes.
- **Websocket Server**: Dedicated Node/PHP process running Laravel Reverb.

By maintaining strict boundaries between these layers, Nexus ensures high maintainability, testability, and massive scalability.
