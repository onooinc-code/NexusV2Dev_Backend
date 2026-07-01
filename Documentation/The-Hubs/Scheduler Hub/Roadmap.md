# Scheduler Hub: Development Roadmap

## 1. Executive Summary
The Scheduler Hub currently provides a highly capable, atomic scheduling engine that vastly improves upon standard cron files. However, it currently relies on simulated execution in the worker and lacks deep integration with the UI for real-time updates. The roadmap focuses on fully implementing the execution payload interpreters, adding real-time WebSocket monitoring, and expanding the system into a true DAG (Directed Acyclic Graph) workflow orchestrator.

## 2. Phase 1: Execution Engine Completion (Short-Term)
*Goal: Move from simulated execution to actual, robust system integrations.*

- **1.1 Payload Interpreters:**
  - *Task:* Implement the actual execution logic in `SchedulerWorker::processDueJobs()`. 
  - *Details:* 
    - For `type = 'command'`: Use `Artisan::call($job->payload['command'], $job->payload['args'])`.
    - For `type = 'webhook'`: Use Laravel's `Http::withHeaders()->post()`.
    - For `type = 'job'`: Use `dispatch(new $job->payload['class']())`.
- **1.2 Output & Result Logging:**
  - *Task:* Create a `scheduler_logs` table.
  - *Details:* Every execution must log its output. If a command runs, capture the terminal output. If a webhook fires, capture the HTTP response code and body. This is crucial for debugging failing jobs.
- **1.3 Sandbox for Script Execution:**
  - *Task:* The `script` type is highly dangerous. Implement a secure, restricted environment (e.g., executing within an isolated Docker container or using `shell_exec` with extremely restrictive permissions) or completely deprecate it in favor of predefined commands.

## 3. Phase 2: Real-time Observability (Mid-Term)
*Goal: Make the UI a live, breathing dashboard using WebSockets.*

- **3.1 WebSocket Integration:**
  - *Task:* Integrate Laravel Reverb/Echo.
  - *Details:* When a worker picks up a job (`is_running = true`), it should broadcast an event. The UI should instantly update the card to show a "Running..." spinner. When complete, broadcast another event to update the "LAST" and "NEXT" timestamps dynamically without a page reload.
- **3.2 Execution History UI:**
  - *Task:* Add a new tab or modal to the UI that allows users to view the historical logs (created in 1.2) for a specific job, including success/failure ratios and average execution time charts.
- **3.3 Alerting & Notifications:**
  - *Task:* If a job's status changes to `failing` (e.g., the webhook returns 500 three times in a row), the system should integrate with the Notification Hub to alert administrators via Slack or Email.

## 4. Phase 3: Workflow Orchestration (Long-Term)
*Goal: Evolve from flat, isolated jobs to dependent workflows.*

- **4.1 Job Dependencies:**
  - *Task:* Add a `depends_on_job_id` column to the schema.
  - *Details:* Allow Job B to only trigger upon the successful completion of Job A, effectively creating pipeline chains.
- **4.2 DAG Visualization:**
  - *Task:* Update the UI to visually map these dependencies, perhaps replacing the simple list view with a node-based graph view (using libraries like React Flow or D3.js) for complex workflows.
- **4.3 Parameter Passing:**
  - *Task:* Allow the output of Job A to be passed as dynamic variables into the payload of Job B.

## 5. Technical Debt & Refactoring Focus
- **Cron Library Update:** Ensure the `Cron\CronExpression` dependency is kept up to date, as parsing cron strings accurately across timezone boundaries and daylight saving time shifts is notoriously complex and prone to edge-case bugs.
- **Timezone Support:** Currently, the system assumes server time. Jobs should be allowed to define a specific timezone (e.g., `America/New_York`) so that schedules like `0 9 * * *` reliably execute at 9 AM local time, regardless of where the server is hosted or daylight saving shifts.
