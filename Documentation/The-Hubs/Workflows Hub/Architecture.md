# Workflows Hub — Architecture

## 1. Overview

The Workflows Hub is the **automation engine** of Nexus. It enables the creation of multi-step workflows that can be triggered manually, via schedule, via events, or via webhooks. Each workflow execution is tracked step-by-step in real time.

---

## 2. Architecture Diagram

```mermaid
graph TD
    subgraph Triggers
        T1[Manual: POST /workflows/execute]
        T2[Scheduler: WorkflowSchedule cron]
        T3[Event: WorkflowEventTrigger listener]
        T4[Webhook: POST /webhooks/workflows/id]
    end

    subgraph Core
        WC[WorkflowController]
        WES[WorkflowExecutionService]
        WE[WorkflowExecutor]
        WVS[WorkflowValidationService]
        WEH[WorkflowErrorHandler]
    end

    subgraph Data
        W[(Workflow)]
        WX[(WorkflowExecution)]
        WSL[(WorkflowStepLog)]
        WS[(WorkflowSchedule)]
        WET[(WorkflowEventTrigger)]
        WH[(WorkflowWebhook)]
    end

    T1 --> WC
    T2 --> WES
    T3 --> WES
    T4 --> WorkflowWebhookController --> WES

    WC --> WVS
    WC --> WES
    WES --> WE
    WE --> WSL
    WE --> AIModelsHub
    WE --> AgentExecutionService
    WES --> WEH
    
    WES --> W
    WES --> WX
    WE --> WSL
```

---

## 3. Workflow Step Execution Model

A Workflow's `steps` field is a JSON array of step definitions. Each step has a `type` that determines what the executor does.

```json
{
  "steps": [
    {
      "id": "step_1",
      "type": "ai_completion",
      "config": { "intent": "contact_analysis", "input": "{{trigger.contact_id}}" }
    },
    {
      "id": "step_2",
      "type": "run_agent",
      "config": { "agent_key": "memory_updater", "depends_on": "step_1" }
    },
    {
      "id": "step_3",
      "type": "send_notification",
      "config": { "channel": "email", "template_id": 5 }
    }
  ]
}
```

---

## 4. Execution Lifecycle

```mermaid
stateDiagram-v2
    [*] --> pending : Triggered
    pending --> running : Executor picks up
    running --> paused : Awaiting user approval
    paused --> running : User approves
    running --> completed : All steps done
    running --> failed : Step fails, no retry
    running --> cancelled : Manual cancel
```

---

## 5. Key Services

### `WorkflowExecutionService` (3KB)
Creates `WorkflowExecution` records, validates the workflow, dispatches the execution job.

### `WorkflowExecutor` (2.1KB)
Iterates through workflow steps, delegating each step to the correct engine (AI, Agent, Notification, HTTP call, etc.).

### `WorkflowValidationService` (3.2KB)
Pre-flight validation before execution — checks required fields, step dependencies, circular references.

### `WorkflowErrorHandler` (5.3KB)
Handles step failures: retries, fallback steps, and dead-letter routing.

---

## 6. Trigger Types

| Type | Model | How it Works |
|---|---|---|
| Manual | N/A | `POST /api/v1/workflows/{id}/execute` |
| Scheduled | `WorkflowSchedule` | Cron expression stored in DB, evaluated by scheduler |
| Event-based | `WorkflowEventTrigger` | Laravel event listener maps events to workflow IDs |
| Webhook | `WorkflowWebhook` | Unique URL per workflow, `POST /api/v1/webhooks/workflows/{id}` |
