# Mem0 Integration Documentation

## Overview

The **Mem0** integration in the Nexus platform is designed to provide long-term semantic memory management for user contacts. While still in its early stages of structural implementation within the `App\Integrations\Mem0Integration` class, Mem0 acts as a core semantic intelligence component. It is closely tied to the `SemanticMemoryService`, orchestrating the long-term context retention of interactions and agent insights.

Mem0 fundamentally tackles the problem of managing memory context over vast periods. As conversational history scales, it is crucial to isolate relevant semantic meaning rather than querying raw interaction records linearly. This integration manages storing insights, finding relevant context for new conversational prompts, and pruning outdated cognitive traces.

## Core Integration Architecture

### `App\Integrations\Mem0Integration`

The primary interface for this service is currently structured around three fundamental operations:

#### 1. `store(array $data): bool`
This method is responsible for persisting semantic memories. The `$data` payload typically encapsulates:
- The raw memory content or conversational insight.
- The associated `contact_id`.
- The temporal metadata marking when the memory was captured.
- Contextual tags or vectorized parameters generated during memory synthesis.

#### 2. `search(string $query, int $contactId, int $limit = 20): array`
Retrieving memories requires context-aware search mechanisms. The `search` method takes a text `$query` (often derived from a recent conversational turn) and finds semantically related memory fragments for the specified `contactId`. The `$limit` ensures that the context window doesn't overflow during large prompt reconstructions. This allows proactive AI systems to append past context dynamically before sending a prompt to language models.

#### 3. `delete(int $memoryId): bool`
Manages the lifecycle of memories. Often, memories become irrelevant, duplicate, or mathematically stale. The `delete` method enables precise removal of these entries from the Mem0 semantic store.

## Interaction with the Memory Maintenance Pipeline

The Mem0 integration is heavily utilized via the `ContactMemoryMaintenancePipeline` and `MemoryMaintenanceService`. This pipeline is executed via background jobs to keep the contact memory optimized.

### Pruning Stale Data
When a `ContactMemoryMaintenanceRun` triggers a `prune_stale` operation, the pipeline queries the local database for memories older than a designated threshold (e.g., 1 year). It then invokes the underlying `SemanticMemoryService` (which acts as a bridge to Mem0 implementations) to delete the stale records dynamically via the `delete` method. If the semantic engine fails, it gracefully falls back to deleting the raw database record, logging a warning to ensure data consistency is maintained.

### Resolving Duplicates
During a `resolve_duplicates` operation, the maintenance engine iterates over the contact's memories. If a memory lacks a vector embedding, it invokes `VectorizeMemoryJob`. If the memory is properly vectorized, it prepares the data for upsertion, ensuring that the semantic store (be it Mem0 or Pinecone) accurately reflects the deduplicated database state.

### Erasure Requests
For privacy and data compliance, an `erase_data` operation triggers an extensive cascading deletion. The pipeline ensures the semantic engine flushes all vectors associated with a specific `contact_id`, followed by the purging of interaction data, notification logs, and analysis findings from the relational database.

## System Configuration and Scalability

While the Mem0 integration provides the logical API structure for semantic operations, it works alongside the application's overall `services.php` configurations and background queue workers to handle high-latency semantic tasks asynchronously. This ensures that the primary synchronous flow (like webhook ingestions or user-facing controllers) is not delayed by vectorization or external memory store API calls. Future expansions of the `Mem0Integration` class will map directly to Mem0's external HTTP or gRPC interfaces.
