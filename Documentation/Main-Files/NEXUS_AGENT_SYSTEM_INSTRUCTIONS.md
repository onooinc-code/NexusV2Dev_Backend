# Nexus Agent System Instructions

The Nexus Agent System represents the core intelligent layer of the platform, defining how autonomous and specialized AI agents behave, interact, and execute tasks. This document outlines the instructions, configurations, and state machines governing the AI agents within the Laravel backend environment.

## 1. Agent Classification and Types

Agents within Nexus are categorized by their operational paradigm, as defined in `App\Models\Agent`:

- **Autonomous Agent** (`autonomous`): Capable of making independent decisions and chaining tools without direct supervision.
- **Specialized Agent** (`specialized`): Highly focused agents built for specific tasks (e.g., data validation, performance monitoring).
- **Team Agent** (`team`): Agents designed to collaborate with other agents, sharing context and delegating sub-tasks.
- **Supervisor Agent** (`supervisor`): Agents that oversee other agents, evaluating their outputs and managing workflow execution.
- **Reflection Agent** (`reflection`): Agents that analyze past executions and suggest improvements or highlight potential errors.

## 2. Core System Agents

The system relies on several protected "System Agents" seeded by `SystemAgentSeeder`. These agents cannot be deleted and handle crucial infrastructure tasks:

1. **System Router** (`system-router`):
   - **Role**: Core system agent for routing tasks to appropriate handlers.
   - **Type**: Autonomous
   - **Settings**: Priority: Critical, Retries: 5, Timeout: 60s.

2. **Error Handler** (`system-error-handler`):
   - **Role**: Handling errors, exceptions, and dead-letter tasks.
   - **Type**: Specialized
   - **Settings**: Priority: High, Retries: 3, Timeout: 30s.

3. **Event Coordinator** (`system-event-coordinator`):
   - **Role**: Coordinating events and pushing notifications across the system.
   - **Type**: Team
   - **Settings**: Priority: High, Retries: 4, Timeout: 45s.

4. **Performance Monitor** (`system-performance-monitor`):
   - **Role**: Monitoring and reporting performance metrics from the server and database.
   - **Type**: Specialized
   - **Settings**: Priority: Medium, Retries: 2, Timeout: 120s.

5. **Data Validator** (`system-data-validator`):
   - **Role**: Validating data integrity, payload schemas, and quality checks.
   - **Type**: Specialized
   - **Settings**: Priority: Medium, Retries: 3, Timeout: 90s.

## 3. Agent Personas and System Prompts

Agents are assigned a specific persona (seeded via `AgentPersonaSeeder`) which dictates their tone, brevity, and system prompt instructions:

- **Professional Assistant**: "You are a professional business assistant. Communicate in a formal, clear, and concise manner." (Formal, concise, moderate enthusiasm).
- **Creative Writer**: "You are a creative writer. Generate engaging, original, and imaginative content." (Casual, elaborate, high enthusiasm).
- **Technical Expert**: "You are a senior technical expert with deep knowledge of software development, architecture, and best practices." (Formal, detailed, patient empathy).
- **Friendly Helper**: "You are a warm, friendly, and approachable customer support agent." (Casual, conversational, high empathy).
- **Data Analyst**: "You are a data analyst focused on providing insights, trends, and actionable recommendations based on data." (Formal, structured, objective).
- **Compliance Officer**: "You are a compliance officer with expertise in regulations, legal requirements, and best practices." (Very formal, thorough, neutral empathy).
- **Quick Responder**: "You are a quick responder optimized for speed and efficiency. Provide direct, concise answers without unnecessary elaboration." (Casual, extremely concise).

## 4. Agent State Machine

Agents follow a strict state machine to prevent race conditions and ensure safe execution:

- **Idle** (`idle`): Ready to receive a task.
- **Running** (`running`): Currently executing a prompt or task.
- **Paused** (`paused`): Execution temporarily halted (e.g., waiting for human approval).
- **Completed** (`completed`): Task finished successfully.
- **Error** (`error`): Task failed or an exception was thrown.
- **Quarantined** (`quarantined`): Agent is locked out of the system due to anomalous behavior, high error rates, or security concerns.

## 5. Execution and Rate Limiting

- **Rate Limits**: Agents have a `rate_limit_per_minute` attribute (defaulting to 60) to prevent API abuse against upstream LLM providers (OpenAI, Gemini).
- **Metrics Tracking**: Each agent tracks its `execution_count`, `success_count`, and `error_count` allowing the system to calculate a real-time success rate (`getSuccessRate()`).
- **Tool Access**: Agents are explicitly granted access to tools (`activeTools()`) and skills (`activeSkills()`), which are verified at runtime before the tool is executed on behalf of the agent.

These instructions and rules form the bedrock of the AI functionality within the Nexus environment, ensuring safe, predictable, and robust agent behaviors.
