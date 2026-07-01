# NexusV3 — Design Plan and Roadmap

---

## Phase 1: Foundation (COMPLETED ✅)
*Laravel 11 monolith with Blade frontend*

- [x] Laravel project setup with Horizon, Reverb, Sanctum
- [x] Core Models: User, Contact, Agent, Workflow, Task, Memory, Log, Setting
- [x] Contacts Hub — full CRUD + identifiers + notes + aliases
- [x] Agents Hub — types, lifecycle, quarantine
- [x] Workflows Hub — creation + execution engine
- [x] AI Models Hub — provider registry + encrypted key storage
- [x] Logs Hub — LogService + DB logging + API
- [x] Settings Hub — CRUD + grouped + bulk update
- [x] Authentication — Sanctum API tokens
- [x] Blade frontend — all 17 hub views
- [x] Horizon queue dashboard
- [x] Basic WebSocket support (Reverb)

---

## Phase 2: Intelligence Layer (COMPLETED ✅)
*AI-powered CRM, Memory System, Agent Intelligence*

- [x] Contact AI Analysis Runs (`ContactAnalysisJob`)
- [x] Contact Memory Maintenance
- [x] Memory confidence scoring + decay + reinforcement + versioning
- [x] AI Models Hub — Intent Routing + Circuit Breaker
- [x] AI cost tracking (AuditTrail)
- [x] Agent personas + tool registry + MCP servers
- [x] Agent simulation mode
- [x] Hedra Soul Hub — 50+ API endpoints
- [x] Hedra Instruction Versioning
- [x] Hedra Approval Inbox
- [x] WhatsApp integration (WAHA)
- [x] People Connect Hub

---

## Phase 3: Automation & Proactive Intelligence (IN PROGRESS 🔄)
*Full automation layer and proactive system behavior*

- [x] Proactive AI Hub — NLP parsing, ECA rules
- [x] Scheduler Hub — cron-based job scheduling
- [ ] **WebSocket events for Hedra Soul messages** (real-time responses)
- [ ] **Live AJAX wiring for Logs Hub UI**
- [ ] **Memory decay scheduled command**
- [ ] **Automated log pruning scheduler**
- [ ] **NLP upgrade to LLM-based parsing** (ProactiveAI)
- [ ] **Fallback provider chains in AIModelsHub**
- [ ] **Mem0 real HTTP client implementation**
- [ ] **Missing composite DB indexes** (logs, triggers, agent_tasks)
- [ ] Cost analytics charts (Chart.js in AI Models Hub)
- [ ] Hedra Soul Approval Inbox inline in chat UI
- [ ] Visual Trace Explorer (AI reasoning visualization)

---

## Phase 4: Scale & UX Polish (PLANNED 📋)
*Enterprise readiness and UX improvements*

- [ ] Multi-workspace / multi-tenant support
- [ ] GDPR erasure cascade fix + audit
- [ ] Contact Relationship Graph visualization
- [ ] Visual Workflow Builder (node-based drag-and-drop)
- [ ] API key masking on read (Settings Hub)
- [ ] Elasticsearch migration for Logs (at scale)
- [ ] Automated alerting on error thresholds
- [ ] Mobile companion app (using existing /api/v1/ layer)
- [ ] Full test suite coverage (feature tests for all controllers)

---

## Technical Architecture Decisions

| Decision | Chosen Approach | Rationale |
|---|---|---|
| Frontend | Blade (from Next.js) | Reduced complexity, single codebase |
| Queue | Redis + Horizon | Real-time monitoring, priority queues |
| WebSockets | Reverb (self-hosted) | No external service dependency |
| Auth | Sanctum | Simple, Laravel-native, stateless tokens |
| AI Routing | Database-driven intent routing | Configurable without code deploys |
| Memory | DB-first with Mem0 as external layer | Works offline, scales with DB |
| Logging | Dual-write (file + MySQL) | Redundancy + queryability |
| Encryption | AES-256 via Laravel encryption | Native, battle-tested |

---

## Design System Direction

- **Color palette:** Deep space dark backgrounds with high-contrast neon accents
- **Typography:** Sans-serif system fonts + monospace for logs/code areas
- **Motion:** Minimal animations, purposeful transitions (no gratuitous effects)
- **Glassmorphism:** Used in hub cards and overlays for depth without heaviness
- **Icons:** Font Awesome 6 (already in use across all Blade views)
- **Layout:** Fixed sidebar nav + scrollable content area per hub
