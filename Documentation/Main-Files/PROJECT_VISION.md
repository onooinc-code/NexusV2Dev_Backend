# NexusV3 — Project Vision

## 1. The Big Idea

Nexus is being built to close the gap between **"using AI tools"** and **"having an AI-powered operating system for your personal and professional life"**. Most AI platforms today are isolated silos — a chatbot here, an automation tool there. Nexus unifies all of these into a coherent, memory-enabled, agent-driven platform under a single operator interface.

---

## 2. Mission Statement

> **Empower operators to build relationships, automate tasks, and make decisions with the help of an AI that truly knows them and their world.**

---

## 3. The Three Pillars

### 🧠 Pillar 1: AI with Memory
The most fundamental problem with current AI tools is **amnesia**. Every conversation starts from scratch. Nexus solves this through the Memory Hub — a multi-layered memory system where:
- **Contact memories** persist facts about every person you interact with.
- **System memories** (via Hedra Soul's Hedra Memory system) let the AI know about *you*, your preferences, your decisions.
- **Memory confidence** decays and reinforces over time, mimicking human memory dynamics.
- Memories are **versioned** and **auditable** — you can always see what the AI "knows" and why.

### 🤖 Pillar 2: Agents That Work for You
The Agents Hub provides a configurable multi-agent framework where specialized AI workers handle discrete responsibilities. Rather than one overloaded "assistant," Nexus operates a **team** of purpose-built agents:
- A **Contact Analysis Agent** deep-dives into your contacts' messages to extract intelligence.
- A **Memory Updater Agent** keeps memories fresh and relevant.
- A **Workflow Execution Agent** orchestrates multi-step processes.
- Custom agents can be created via the UI and assigned tools, personas, and MCP server access.

### 🔗 Pillar 3: Connected Everything
Nexus connects to where your life actually happens:
- **WhatsApp** via the WAHA integration for real-time messaging.
- **Any AI model** (OpenAI, Anthropic, Gemini, local) via the dynamic AI Models Hub.
- **External tools** via MCP (Model Context Protocol) servers.
- **Your own workflows** via the Webhooks and Scheduler systems.
- **External APIs** via the Settings Hub's API Proxy feature.

---

## 4. The "Souly" / Hedra Soul Vision

The most distinctive component of Nexus is **Souly** — the AI cognitive core of the Hedra Soul Hub. Souly is not just a chatbot. It is designed to be:

1. **An extension of the operator's mind** — It has access to the operator's contacts, memories, and preferences, and uses them to provide contextually aware responses.
2. **Transparent** — Every message Souly sends exposes its cost (in tokens and USD), its reasoning trace, and the context snapshot it used to generate it.
3. **Controllable** — The operator can set Souly's autonomy mode from completely locked-down ("Chat Only") to fully autonomous ("Autopilot"), giving explicit control over when Souly can take unilateral action.
4. **Approvable** — When Souly wants to perform a potentially impactful action, it creates an Approval Request that the operator must explicitly sign off on.

---

## 5. The Nexus Operator Profile

The primary user is an **Operator** — a technically sophisticated individual or small team running Nexus as the central nervous system of their business or personal productivity stack. They are:
- Comfortable with APIs and developer tools.
- Managing hundreds to thousands of contacts across multiple channels.
- Delegating cognitive tasks to AI rather than doing them manually.
- Deeply invested in data privacy (hosting Nexus themselves).

---

## 6. What Makes Nexus Different

| Feature | Traditional CRM | Traditional AI Chatbot | Nexus |
|---|---|---|---|
| Persistent Memory | ✅ (structured) | ❌ | ✅ (structured + semantic + versioned) |
| AI Agents | ❌ | Limited | ✅ (multi-agent, multi-type) |
| Real-time messaging | ❌ | ❌ | ✅ (WhatsApp via WAHA) |
| Dynamic AI Provider | ❌ | ❌ | ✅ (any provider, no code changes) |
| Transparent AI Cost | ❌ | ❌ | ✅ (per-message token + cost tracking) |
| Approval Inbox | ❌ | ❌ | ✅ (Souly requests explicit permissions) |
| Automation Workflows | Limited | ❌ | ✅ (multi-trigger, multi-step) |
| Self-hosted | ❌ | ❌ | ✅ (full local deployment) |

---

## 7. Future Vision

- **Natural Language Database Queries** — Ask questions about your contacts in plain English.
- **Proactive Relationship Intelligence** — Nexus alerts you before important dates (birthdays, follow-ups) without being asked.
- **Cross-Agent Collaboration** — Supervisor agents that coordinate specialist agents to tackle complex projects.
- **Multi-Workspace Support** — Multiple operators sharing a Nexus installation with isolated data.
- **Mobile Native App** — A companion mobile app leveraging the existing `/api/v1/` API.
