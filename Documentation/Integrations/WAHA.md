# WAHA (WhatsApp HTTP API) Integration Documentation

## Overview

The WAHA integration within Nexus serves as the primary conduit for bidirectional WhatsApp communication. It is a massive, multi-layered integration consisting of background queues, webhook ingestion systems, API controllers, and contact synchronization pipelines. The integration allows Nexus to ingest incoming WhatsApp messages, track message delivery statuses, dispatch outbound AI-generated or manual responses, and synchronize massive lists of contacts seamlessly.

## Configuration & Setup

WAHA credentials and URLs are mapped in `config/services.php` under the `waha` key. This requires the `WAHA_API_URL`, `WAHA_API_KEY`/`TOKEN`, the `WAHA_WEBHOOK_SECRET` for secure payload ingestion, and the default `WAHA_SESSION` identifier.

## Core Architectural Components

### 1. Webhook Ingestion & Processing

The lifecycle of an incoming WhatsApp message begins at the webhook.
- **`WahaWebhookIngestionService`**: Receives raw HTTP payloads from WAHA. It extracts the `session` and `messageId` and performs critical deduplication using the `PeopleConnectRawProviderEvent` database model. If the payload is novel, it stores the raw event with a `pending` status and dispatches the payload to the queue.
- **`ProcessWahaWebhookJob`**: Asynchronously processes the ingested webhook payload, parsing contact information, text bodies, and multimedia attachments.
- **`ProcessWahaMessageChunkJob`**: Handles fragmented or heavily chunked data streams coming from WAHA, ensuring ordered reconstruction before processing.

### 2. Message Dispatching

Outbound communication uses specialized services and jobs to ensure high deliverability and accurate status tracking.
- **`WahaMessageDispatcher`**: The core service responsible for constructing outbound payloads. It formats text, links, and media according to WAHA API specifications.
- **`DispatchWahaMessageJob`**: An asynchronous job that executes the HTTP requests to the WAHA instance. This decoupling ensures that transient WAHA connectivity issues do not block the main application thread.
- **`ReconcileWahaDeliveryStatusJob`**: Constantly monitors delivery receipts (Sent, Delivered, Read). It updates the `ContactMessage` records in Nexus so the UI can reflect accurate communication states.

### 3. Contact and Data Synchronization

Large-scale WhatsApp instances hold thousands of contacts. Nexus utilizes specialized sync jobs to ingest and update this data without overwhelming the database.
- **`WahaManageController`**: Provides HTTP endpoints to check the status of WAHA syncs, offering deep statistical insights into total WAHA contacts versus synced Nexus contacts, tracking active background processes.
- **`WahaSyncProcess`**: A database model that tracks the state of long-running synchronization tasks (`pending`, `running`, `paused`, `completed`).
- **`SyncWahaContactsJob` & `WahaImportService`**: Pull contact lists from the WAHA API, mapping WAHA profile names and numbers to local `Contact` models. The `WahaImportService` implements robust `updateOrCreate` logic to prevent duplication.
- **`SyncWahaMessagesJob` & `SyncWahaConversationsJob`**: Historical import tools. These pull old conversation threads from WAHA into Nexus, allowing the AI to have historical context even for conversations that occurred before Nexus was deployed.

### 4. Advanced Analysis

- **`WahaBatchAnalyzeJob` & `WahaAnalysisService`**: These components hook into the AI systems. When batches of messages are ingested from WAHA, the analysis service runs semantic evaluations, updates contact sentiment scores, and determines if a proactive AI response should be triggered based on the conversation's trajectory.

## Error Handling and Idempotency

WAHA webhooks can fire multiple times for the same event due to network retries. The integration strictly enforces idempotency at the `WahaWebhookIngestionService` level by querying `PeopleConnectRawProviderEvent`. 

Similarly, queue jobs like `ProcessWahaWebhookJob` use Laravel's `$idempotencyKey` properties to prevent duplicate processing if a queue worker restarts mid-execution. Delivery status reconciliations gracefully handle race conditions where a "Read" receipt might arrive before the local "Sent" transaction commits.

By separating ingestion, synchronization, and outbound dispatching into discrete queued processes, the WAHA integration provides an enterprise-grade, highly resilient WhatsApp communication backbone for the Nexus platform.
