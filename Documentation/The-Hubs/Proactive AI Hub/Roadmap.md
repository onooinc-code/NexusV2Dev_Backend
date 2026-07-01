# Proactive AI Hub: Development Roadmap

## 1. Executive Summary
The current iteration of the Proactive AI Hub provides a solid deterministic foundation. It successfully implements the core ECA (Event-Condition-Action) loop, a rudimentary regex-based NLP parser, and a reliable cron-driven execution engine. The roadmap focuses on evolving this deterministic base into a highly probabilistic, LLM-powered autonomous agent system, while simultaneously hardening the execution layer for enterprise scale.

## 2. Phase 1: Engine Hardening & Extensibility (Short-Term)
*Goal: Ensure the current architecture can scale and support a wider variety of internal system events.*

- **1.1 Event Listener Integration:** 
  - *Task:* Connect Laravel's internal Event dispatcher to the Proactive Hub. When a system event fires (e.g., `UserRegistered`, `PaymentFailed`), it should notify the Hub to check for matching `event_based` ECA rules.
  - *Impact:* Moves the Hub from just time-based scheduling to genuine reactive automation.
- **1.2 Payload Dynamic Variable Resolution:**
  - *Task:* Allow actions to use variables from the event payload. (e.g., "Reply with: Hello {user.name}").
  - *Impact:* Makes autonomous actions contextual and personalized.
- **1.3 Trigger Retry Mechanism:**
  - *Task:* Update `ProactiveSchedulerCommand` to handle failures gracefully. Add a `retry_count` column to `ProactiveTrigger`. Implement an exponential backoff strategy for failed triggers before marking them as permanently failed.
  - *Impact:* Increases reliability when interfacing with flaky third-party APIs.

## 3. Phase 2: The LLM Integration (Mid-Term)
*Goal: Replace the regex-based `NlpParserService` with a robust Large Language Model integration.*

- **3.1 API Integration:**
  - *Task:* Integrate an external LLM provider (e.g., OpenAI API, Anthropic) or a local model.
  - *Task:* Write specific system prompts to constrain the LLM output strictly to the JSON schema required by `EcaRule` (event_type, conditions, actions).
- **3.2 Intent Disambiguation UI:**
  - *Task:* If the LLM is uncertain about a rule, the UI should prompt the user for clarification before saving. (e.g., "Did you mean every Monday morning, or just this Monday?").
  - *Impact:* Prevents rogue autonomous actions caused by misunderstood prompts.
- **3.3 Global Memory Context:**
  - *Task:* Implement the backend for the "Connect Global Memory" toggle. Pass historical context to the LLM during rule parsing.

## 4. Phase 3: Action Expansion & Marketplace (Long-Term)
*Goal: Allow the Hub to perform vastly more complex actions across external services.*

- **4.1 External Webhooks & API Calling:**
  - *Task:* Expand the action executor to handle arbitrary HTTP requests (GET/POST) to external services.
- **4.2 Pre-packaged AI Skills/Actions:**
  - *Task:* Create specific integrations (e.g., "Create a Jira ticket", "Send a Slack message", "Provision a server on DigitalOcean").
- **4.3 Community Rule Templates:**
  - *Task:* Allow users to share successful natural language prompts/rules in a template marketplace within the application.

## 5. Technical Debt & Refactoring Focus
- **Decoupling Executions:** Currently, `ProactiveSchedulerCommand` hardcodes the notification logic (`if (isset($actions['notify']))`). This must be refactored into an Action Factory pattern. Every action type (Notify, HTTP, Log, Delete) should correspond to a dedicated Action handler class.
- **Timezone Management:** The `parseTime` function and the Scheduler command must be updated to respect the user's localized timezone, storing times in UTC and converting appropriately, rather than relying strictly on the server's local Carbon time.
