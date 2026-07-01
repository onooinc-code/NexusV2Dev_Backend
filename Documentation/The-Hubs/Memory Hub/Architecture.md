# Memory Hub — Architecture

## 1. Overview

The Memory Hub manages **structured, versioned, confidence-scored memories** that give Nexus's AI agents persistent context about contacts, events, and system state. It integrates with the Contacts Hub (contact-specific memories) and the Hedra Soul Hub (AI self-knowledge).

---

## 2. Architecture Diagram

```mermaid
graph TD
    subgraph HTTP Layer
        MC[MemoryController 30KB]
    end

    subgraph Service Layer
        MS[Memory Services in app/Services/Memory/]
    end

    subgraph Data Layer
        Memory[(Memory model)]
        ContactMemory[(ContactMemory)]
        MemoryVersion[(ContactMemoryVersion)]
        HedraMemory[(HedraMemorySuggestion)]
    end

    subgraph External
        Mem0[Mem0Integration stub]
    end

    MC --> MS
    MS --> Memory
    MS --> ContactMemory
    MS --> MemoryVersion
    MS --> Mem0

    MC --> AIModelsHub
```

---

## 3. Memory Confidence Model

```mermaid
graph LR
    A[New Memory - confidence: 0.7] -->|reinforced| B[confidence: 0.85]
    B -->|decayed over time| C[confidence: 0.6]
    C -->|stale detection| D[Flagged for maintenance]
    D -->|memory maintenance run| E[Consolidated or deleted]
```

- **Confidence** is a float between 0.0 and 1.0
- **Reinforcement** (`POST /memories/{id}/reinforce`) increases confidence
- **Decay** (`POST /memories/decay`) reduces confidence for all memories older than N days
- **Stale** memories have low confidence AND are old — surfaced via `/contacts/{id}/stale-memory`

---

## 4. Memory Versioning

Every update to a memory creates a `ContactMemoryVersion` record with the previous value. This enables rollback to any historical state of a memory.

---

## 5. AI Memory Extraction

```mermaid
sequenceDiagram
    User->>POST /contacts/{id}/memories/extract: {}
    MemoryController->>AIModelsHub: route("memory_extraction", messages)
    AIModelsHub->>LLM: Extract structured facts from messages
    LLM-->>AIModelsHub: [{fact, confidence, type}...]
    AIModelsHub-->>MemoryController: extracted memories
    MemoryController->>DB: CREATE Memory records
    MemoryController-->>User: {count: N, memories: [...]}
```

---

## 6. Key Models

### `Memory`
```
Fields: id, contact_id, content(text), confidence(decimal),
        type, source, extraction_method, is_indexed,
        extracted_at, reinforced_at, created_at, updated_at

Relationships:
  - belongsTo: Contact
  - hasMany: ContactMemoryVersion
```

### `ContactMemoryVersion`
```
Fields: id, memory_id, previous_content, changed_at, changed_by_user_id
```

---

## 7. Mem0 Integration (Stub)

The `Mem0Integration` class provides the interface for external semantic memory storage, but is currently a stub. Once wired:
- `store(userId, content, metadata)` — Upserts a memory to the Mem0 cloud
- `search(userId, query)` — Semantic similarity search across stored memories
- `delete(memoryId)` — Removes a memory from Mem0

**Config (.env):**
```env
MEM0_API_KEY=your-key
MEM0_BASE_URL=https://api.mem0.ai
```
