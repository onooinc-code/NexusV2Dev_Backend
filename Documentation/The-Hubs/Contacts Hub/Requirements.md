# Contacts Hub — Requirements

## 1. Overview
The Contacts Hub is the **central CRM engine** of the Nexus platform. It is the most complex hub, responsible for managing the full lifecycle of contact entities — from creation and enrichment to AI-assisted intelligence, memory management, and GDPR compliance. Every other hub (People Connect, Workflows, AI agents) ultimately serves the Contacts Hub in some capacity.

---

## 2. Functional Requirements

### 2.1 Core CRUD
- Users MUST be able to create, read, update, and delete contacts.
- Contacts MUST support rich metadata: first name, last name, phone numbers (multiple), email addresses (multiple), WhatsApp ID, source, status, tags.
- Contacts MUST support **multiple identifiers** per contact (phone, email, WhatsApp, Facebook, etc.) via the `ContactIdentifier` sub-model.

### 2.2 AI Intelligence & Profiling
- The system MUST be able to run an **AI Analysis Run** on a contact, scanning all their messages to extract:
  - Topics of discussion
  - Emotional baseline
  - Persona profile
  - Communication preferences
  - Relationship type
- Analysis findings (`ContactAnalysisFinding`) MUST be stored and versioned. Users can **apply** or **rollback** a run.
- The system MUST expose a `/intelligence` endpoint returning the assembled AI intelligence profile.

### 2.3 Memory Management
- The system MUST support structured memory entries linked to contacts (`ContactMemory`).
- Users MUST be able to trigger **Memory Maintenance** runs, which use AI to:
  - Detect stale or redundant memories
  - Suggest consolidations
  - Flag conflicts
- Memory confidence MUST be scored (0.0–1.0) and decay over time if not reinforced.
- Memory versions MUST be tracked for rollback.

### 2.4 Messaging
- Contacts MUST aggregate messages from multiple channels: WhatsApp (via WAHA), Facebook, API.
- Messages MUST be grouped into **Threads**.
- The system MUST support per-contact and global **Reply Mode** (`ai` or `manual`).
- Per-contact **Reply Rules** MUST allow conditional auto-reply logic.

### 2.5 Import / Export
- Bulk CSV import MUST be supported with a preview step.
- WhatsApp contact import via WAHA MUST be supported.
- Facebook contact import MUST be supported.
- Import batches MUST be tracked (`ContactImportBatch`) with rollback support.
- Contact export (full bundle download) MUST be supported.

### 2.6 Relationships & Social Graph
- Contacts MUST be linkable to other contacts via the `ContactRelationship` model (e.g., "Manager of", "Friend of").
- `RelationshipGraphService` provides the underlying graph computation.

### 2.7 GDPR & Privacy
- The `/erase` endpoint MUST permanently delete all contact data (contact, messages, memories, analysis runs, audit events).
- All data deletions MUST be logged in the `ContactAuditEvent` table.
- Contacts MUST have a full **Audit Trail** endpoint (`/audit`).

### 2.8 Conflict Detection
- The system MUST detect **conflicting memories** (e.g., two memories with contradictory facts) and surface them via the `/conflicts` endpoint.
- The system MUST detect **stale memories** (low confidence + old) via the `/stale-memory` endpoint.

---

## 3. Non-Functional Requirements

### 3.1 Performance
- Contact list (`GET /contacts`) MUST use pagination (default 20 per page, max 100).
- AI Analysis runs MUST be async (dispatched to queue) — never blocking the HTTP thread.
- Memory maintenance runs MUST be async.

### 3.2 Security
- All endpoints require `auth:sanctum`.
- Contact import endpoints are rate-limited to 5 requests/minute.
- Analysis run endpoint uses a custom `throttle:analysis` limit.

### 3.3 Data Integrity
- Deleting a contact MUST cascade-delete all sub-resources via database foreign key constraints.
- Import rollback MUST restore the database to its pre-import state.
