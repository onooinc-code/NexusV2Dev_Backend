# Proactive AI Hub: Comprehensive Requirements Document

## 1. Introduction & Executive Summary
The Proactive AI Hub is a cornerstone feature of the Nexus ecosystem, designed to provide autonomous Event-Condition-Action (ECA) capabilities. The primary goal is to empower users to define natural language rules that the system automatically translates into actionable, scheduled, or event-driven operations. By abstracting the complexity of rule engines, the Proactive AI Hub brings sophisticated automation to the fingertips of the user. This document outlines the functional and non-functional requirements necessary to implement, maintain, and scale the Proactive AI Hub effectively.

## 2. Functional Requirements

### 2.1 Natural Language Rule Processing
- **NLP Parsing:** The system must accept natural language strings (e.g., "Remind me tomorrow at 3 PM about X") and parse them into structured ECA components (Event, Condition, Action).
- **Time-based Recognition:** The parsing engine must recognize temporal keywords ("tomorrow", "at 3 PM") and correctly calculate the `next_run_at` timestamp using localized or UTC bounds.
- **Event-based Recognition:** The parsing engine must detect event triggers (e.g., "If I receive an email from X") and map them to internal system events (e.g., `ContactMessageReceived`).
- **Action Extraction:** The system must extract the desired action (e.g., "notify me", "reply with") and map it to a specific execution payload.

### 2.2 Rule Management (CRUD Operations)
- **Rule Creation:** Users must be able to submit a natural language string via the UI. The system must process this, create a corresponding `EcaRule` record, and if it's a time-based rule, immediately schedule a `ProactiveTrigger`.
- **Rule Toggling:** Users must be able to temporarily pause (disable) and resume (enable) rules without deleting them. This maps to the `is_active` boolean on the `EcaRule` model.
- **Rule Deletion:** Users must be able to delete a rule. Deleting an `EcaRule` must cascade and delete all associated `ProactiveTrigger` records to prevent orphaned executions.
- **Rule Listing:** The UI must display all rules, showing their parsed components (Event Type, Conditions, Actions) and their active status.

### 2.3 Trigger Execution & Scheduling
- **Trigger Generation:** For time-based rules, a `ProactiveTrigger` must be generated with a `pending` status.
- **Trigger Processing:** A dedicated Artisan command (`proactive:run-scheduler`) must evaluate all `pending` triggers where `next_run_at` is less than or equal to the current time.
- **Autonomous Execution:** The scheduler must execute the actions defined in the trigger's parent `EcaRule`. For instance, if the action is `notify`, the system must integrate with the Notification logs or channels.
- **Status Updates:** Upon execution, the trigger's status must be updated to `completed` or `failed` depending on the outcome.

### 2.4 Autonomous Logging
- **Action Accountability:** Every executed trigger must generate an `AutonomousLog` record.
- **Log Details:** The log must capture the `action_taken`, the `reasoning` (e.g., "Time-based condition met for ECA rule"), and the `status`.

## 3. Non-Functional Requirements

### 3.1 Performance & Scalability
- **Efficient Querying:** The `proactive:run-scheduler` command will run frequently (potentially every minute). Queries against the `proactive_triggers` table must be optimized, utilizing indexes on `status` and `next_run_at`.
- **Execution Isolation:** Trigger execution should ideally be wrapped in try/catch blocks to ensure that a failure in one trigger does not halt the processing of subsequent triggers in the same batch. This is already implemented in `ProactiveSchedulerCommand`.

### 3.2 Security & Access Control
- **Input Validation:** All natural language inputs must be validated to prevent excessively long strings or malicious injections. The current `Validator::make` implementation limits strings to 1000 characters.
- **Authorization:** Only authorized users should be able to create, modify, or delete ECA rules.

### 3.3 Reliability & Fault Tolerance
- **Error Handling:** If NLP parsing fails, the system should gracefully degrade or return a meaningful error message to the user rather than crashing.
- **Retry Mechanisms:** Future iterations should consider retry mechanisms for `failed` triggers, especially if the failure was due to a transient issue (e.g., external API timeout).

## 4. Edge Cases & Constraints
- **Ambiguous Language:** The NLP parser (`NlpParserService`) relies on a simplistic, deterministic implementation (regex and string matching). It may fail to parse highly ambiguous or grammatically incorrect inputs. The fallback mechanism (assigning "Unparsed rule") is a necessary constraint.
- **Timezone Handling:** The system currently relies on the server's default timezone (`Carbon::now()`). It must be constrained or updated to respect user-specific timezones for accurate scheduling.
