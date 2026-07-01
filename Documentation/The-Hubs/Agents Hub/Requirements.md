# Agents Hub — Requirements

## 1. Overview
The Agents Hub provides a **multi-agent orchestration framework** within Nexus. It enables the creation, configuration, monitoring, and lifecycle management of AI agents. These agents can perform autonomous tasks, process contacts, interact with external services via MCP, and collaborate in team configurations.

---

## 2. Functional Requirements

### 2.1 Agent Management
- Users MUST be able to create, view, update, and delete AI agents.
- Each agent MUST have:
  - A unique `key` identifier for programmatic reference.
  - A `type` (reflection, team, autonomous, specialized, supervisor).
  - A `provider` indicating which AI backend to use.
  - `settings` (JSON) and `metadata` (JSON) for flexible configuration.
- Users MUST be able to assign a **Persona** to an agent (system prompt, tone, role).
- Users MUST be able to assign **Tools** to an agent.
- Users MUST be able to assign **Skills** to an agent.
- Users MUST be able to link agents to **MCP Servers**.

### 2.2 Agent Execution
- Users MUST be able to execute an agent via `POST /agents/{id}/run` with a payload.
- The system MUST enforce **rate limits** per agent (default: 60 req/minute via `AgentRateLimiter`).
- Execution MUST increment the `execution_count` counter on the agent.
- On success, MUST increment `success_count` and update `last_executed_at`.
- On failure, MUST increment `error_count` and set status to `error`.

### 2.3 Agent Simulation
- The system MUST support a **simulation mode** (`/agents/{id}/simulate`) that runs the agent's full logic without persisting side effects.
- Simulation results MUST be returned synchronously.

### 2.4 Agent Quarantine
- The system MUST support **quarantine** for misbehaving agents.
- A quarantined agent MUST NOT accept new task assignments.
- Authorized users MUST be able to manually quarantine or unquarantine any agent.
- Supervisor agents SHOULD be able to automatically quarantine subordinate agents.

### 2.5 Agent Status & Monitoring
- The system MUST expose a `GET /agents/{id}/status` endpoint returning the current agent state, execution counters, and recent activity.
- The system MUST expose a `GET /agents/{id}/logs` endpoint returning `AgentRuntimeLog` entries.
- The `Agent.getSuccessRate()` method MUST return the correct percentage: `(success_count / execution_count) * 100`.

### 2.6 Persona Management
- The system MUST support creating and managing Agent Personas.
- A Persona MUST contain: `name`, `role`, `tone`, `system_prompt`.
- Any agent MAY have one Persona (`persona_id`). The persona's `system_prompt` MUST be prepended to every AI request made by that agent.

### 2.7 MCP Server Management
- The system MUST allow registering MCP servers via `POST /api/v1/mcp-servers`.
- The system MUST allow connecting / disconnecting MCP servers (`/connect`, `/disconnect` endpoints).
- Many-to-many associations between agents and MCP servers MUST be managed via the `agent_mcp_servers` pivot.

---

## 3. Non-Functional Requirements

### 3.1 Performance
- Agent execution MUST complete within a reasonable timeout (configurable per agent).
- Rate limiting MUST use Redis for distributed enforcement.

### 3.2 Security
- All agent endpoints require `auth:sanctum`.
- System agents (`is_system = true`) MUST NOT be deletable by regular users.
- Quarantine/unquarantine actions SHOULD require elevated permissions (Future: ABAC policy).

### 3.3 Reliability
- If tool execution fails, the agent MUST record the failure and continue the current turn.
- Circuit breakers on the AI provider side prevent cascading failures.
