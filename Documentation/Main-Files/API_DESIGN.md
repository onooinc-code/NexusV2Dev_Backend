# NexusV3 — API Design

> All API endpoints are versioned under `/api/v1/`. Authentication uses Laravel Sanctum (Bearer tokens).

---

## 1. API Conventions

### Base URL
```
http://your-domain.com/api/v1/
```

### Authentication
All protected routes require:
```http
Authorization: Bearer {sanctum_token}
Content-Type: application/json
Accept: application/json
```

### Response Envelope
**Success:**
```json
{
  "data": { ... },
  "meta": { "page": 1, "total": 100 }
}
```
**Error:**
```json
{
  "message": "Validation failed.",
  "errors": { "field": ["Error description"] }
}
```

---

## 2. Authentication Endpoints

| Method | Endpoint | Auth | Description |
|---|---|---|---|
| `POST` | `/login` | No | Login with email + password, returns Sanctum token |
| `POST` | `/register` | No | Register a new user |
| `POST` | `/verify-token` | No | Verify a Sanctum token |
| `POST` | `/logout` | Yes | Revoke current token |

**Login Request:**
```json
{ "email": "user@example.com", "password": "secret" }
```
**Login Response:**
```json
{ "token": "1|abc123...", "user": { "id": 1, "name": "...", "email": "..." } }
```

---

## 3. Contacts Hub API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/contacts` | List contacts (paginated) |
| `POST` | `/contacts` | Create contact |
| `GET` | `/contacts/{id}` | Get contact detail |
| `PUT` | `/contacts/{id}` | Update contact |
| `DELETE` | `/contacts/{id}` | Delete contact |
| `GET` | `/contacts/stats` | Aggregated contact statistics |
| `GET` | `/contacts/{id}/messages` | Get contact's messages |
| `GET` | `/contacts/{id}/threads` | Get message threads |
| `GET` | `/contacts/{id}/memory` | Get associated memories |
| `GET` | `/contacts/{id}/intelligence` | AI-generated intelligence profile |
| `GET` | `/contacts/{id}/timeline` | Activity timeline |
| `GET` | `/contacts/{id}/analytics` | Per-contact analytics |
| `POST` | `/contacts/{id}/analysis-runs` | Trigger AI analysis |
| `POST` | `/contacts/{id}/memory-maintenance` | Trigger memory maintenance |
| `POST` | `/contacts/{id}/merge` | Merge duplicate contacts |
| `POST` | `/contacts/{id}/enrich` | Enrich contact data via AI |
| `POST` | `/contacts/{id}/erase` | GDPR: erase contact data |
| `POST` | `/contacts/import` | Bulk import contacts |
| `GET` | `/contacts/export` | Export contacts |

---

## 4. Agents Hub API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/agents` | List all agents |
| `POST` | `/agents` | Create a new agent |
| `GET` | `/agents/{id}` | Get agent detail |
| `PUT` | `/agents/{id}` | Update agent |
| `DELETE` | `/agents/{id}` | Delete agent |
| `POST` | `/agents/{id}/run` | Execute agent immediately |
| `POST` | `/agents/{id}/simulate` | Simulate agent execution (dry run) |
| `POST` | `/agents/{id}/quarantine` | Quarantine misbehaving agent |
| `POST` | `/agents/{id}/unquarantine` | Restore quarantined agent |
| `GET` | `/agents/{id}/status` | Get real-time agent status |
| `GET` | `/agents/{id}/logs` | Get agent runtime logs |
| `GET` | `/agent-tools` | List available tools library |
| `GET/POST` | `/agent-personas` | Manage agent personas |
| `GET/POST` | `/mcp-servers` | Manage MCP server connections |

---

## 5. Workflows Hub API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/workflows` | List workflows |
| `POST` | `/workflows` | Create workflow |
| `GET` | `/workflows/templates` | Get workflow templates |
| `POST` | `/workflows/{id}/execute` | Execute workflow |
| `GET` | `/workflows/{id}/progress` | Track execution progress |
| `GET` | `/workflows/executions/{id}` | Get specific execution |
| `POST` | `/workflows/executions/{id}/resume` | Resume paused execution |
| `POST` | `/workflows/executions/{id}/cancel` | Cancel running execution |

---

## 6. AI Models Hub API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/ai/providers` | List all AI providers |
| `POST` | `/ai/providers` | Add new AI provider |
| `POST` | `/ai/providers/{id}/test` | Test provider connectivity |
| `POST` | `/ai/providers/{id}/sync-models` | Sync models from provider API |
| `PATCH` | `/ai/providers/{id}/toggle-active` | Enable/disable provider |
| `GET` | `/ai/providers/health` | Provider health dashboard |
| `GET` | `/ai/intents/routing` | View intent routing matrix |
| `PUT` | `/ai/intents/routing` | Update intent routing |
| `POST` | `/ai/request` | Make a routed AI request |
| `GET` | `/ai/audit-trail` | AI request audit trail |
| `GET` | `/ai-hub/telemetry` | AI usage telemetry |
| `GET` | `/ai/cost/forecast` | Cost forecast |
| `POST` | `/ai/cost/budget` | Set spending budget |

---

## 7. Hedra Soul Hub API (50+ Endpoints)

### Sessions
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/hedrasoul/sessions` | List all sessions |
| `POST` | `/hedrasoul/sessions` | Start new session |
| `GET` | `/hedrasoul/sessions/{id}` | Get session detail |
| `PATCH` | `/hedrasoul/sessions/{id}` | Update session title |
| `POST` | `/hedrasoul/sessions/{id}/archive` | Archive session |
| `GET` | `/hedrasoul/sessions/{id}/messages` | Get session messages |
| `POST` | `/hedrasoul/sessions/{id}/messages` | Send message to Souly |

### Messages
| Method | Endpoint | Description |
|---|---|---|
| `POST` | `/hedrasoul/messages/{id}/regenerate` | Regenerate AI response |
| `GET` | `/hedrasoul/messages/{id}/trace` | View action trace |
| `GET` | `/hedrasoul/messages/{id}/context` | View context snapshot |

### Souly Control
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/hedrasoul/souly/status` | Get Souly status |
| `PATCH` | `/hedrasoul/souly/autonomy` | Set autonomy mode |
| `POST` | `/hedrasoul/souly/quarantine` | Emergency quarantine |
| `POST` | `/hedrasoul/souly/resume` | Resume from quarantine |

### Approvals
| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/hedrasoul/approvals` | List pending approvals |
| `POST` | `/hedrasoul/approvals/{id}/approve` | Approve action |
| `POST` | `/hedrasoul/approvals/{id}/reject` | Reject action |
| `POST` | `/hedrasoul/approvals/{id}/defer` | Defer for later |

---

## 8. Memory Hub API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/memories` | List all memories |
| `POST` | `/memories` | Create memory |
| `GET` | `/memories/search` | Semantic search |
| `POST` | `/memories/{id}/reinforce` | Reinforce confidence |
| `POST` | `/memories/decay` | Apply confidence decay |
| `GET` | `/memories/{id}/versions` | Memory version history |
| `GET` | `/contacts/{id}/memories` | Memories for a contact |
| `POST` | `/contacts/{id}/memories/extract` | AI-extract new memories |

---

## 9. Settings Hub API

| Method | Endpoint | Description |
|---|---|---|
| `GET` | `/settings` | List all settings |
| `GET` | `/settings/grouped` | Settings grouped by category |
| `PUT` | `/settings/bulk` | Bulk update settings |
| `POST` | `/settings/factory-reset` | Reset to factory defaults |
| `GET` | `/settings/health` | Settings health check |
| `POST` | `/settings/credentials/validate` | Validate specific credential |
| `GET` | `/settings/seeds` | List available seeders |
| `POST` | `/settings/seeds/{id}/run` | Run a specific seeder |

---

## 10. Rate Limiting

| Endpoint Group | Rate Limit |
|---|---|
| Contact imports | 5 per minute |
| Contact analysis runs | throttle:analysis (custom) |
| Dashboard stats | 60 per minute |
| General API | Default Laravel throttle |

---

## 11. Webhooks (Inbound)

| Endpoint | Description |
|---|---|
| `POST /api/v1/webhooks/waha` | Receive WhatsApp messages from WAHA |
| `POST /api/v1/webhooks/workflows/{id}` | Trigger workflow via external webhook |

### WAHA Webhook Payload Example:
```json
{
  "event": "message",
  "session": "default",
  "payload": {
    "id": "...",
    "from": "1234567890@s.whatsapp.net",
    "body": "Hello!",
    "timestamp": 1700000000
  }
}
```
