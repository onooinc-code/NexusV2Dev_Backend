# Scheduler Hub: Comprehensive Requirements Document

## 1. Introduction & Executive Summary
The Scheduler Hub is an advanced task orchestration and chron job management interface within the Nexus application. It provides developers and system administrators with a unified dashboard to create, monitor, pause, and delete recurring tasks. Supporting commands, queued jobs, webhooks, and raw scripts, the Scheduler Hub abstracts the complexities of the underlying OS cron daemon and provides an atomic, scalable, database-driven scheduling engine.

## 2. Functional Requirements

### 2.1 Job Definition & Management
- **Job Creation:** Users must be able to define a new job specifying a Name, Type (command, job, webhook, script), a valid Cron Expression, and an optional JSON Payload.
- **Supported Job Types:**
  - `command`: Executes a predefined Artisan command.
  - `job`: Dispatches a Laravel queued job.
  - `webhook`: Sends an HTTP POST request to a defined URL.
  - `script`: Executes raw shell or bash scripts (subject to strict security boundaries).
- **Job Updating:** Users must be able to modify the attributes of an existing job, including its cron schedule and payload.
- **Job Deletion:** Users must be able to permanently remove a job from the schedule.
- **Status Toggling:** Users must be able to pause (`status = 'paused'`) and resume (`status = 'active'`) jobs without losing the job definition.

### 2.2 Execution Engine (Worker)
- **Continuous Polling:** The system must feature a worker daemon (`scheduler:worker`) that continuously polls the database for due jobs.
- **Cron Evaluation:** The worker must interpret standard cron expressions (e.g., `0 * * * *`) and evaluate them against the current system time to determine if a job is due.
- **Atomic Execution:** To support scaled environments with multiple worker instances, the system must utilize database-level locking (`lockForUpdate()`) to claim a job atomically. This prevents two workers from executing the same job simultaneously.
- **State Management:** The worker must accurately update job states: marking them as `is_running = true` during execution, and updating `last_run_at` and computing the next `next_run_at` upon completion.

### 2.3 User Interface (Dashboard)
- **Job Visualization:** The UI must display all configured jobs clearly, showing their type, status, cron string, and the computed next run time.
- **Timeline View:** The UI must provide a chronological timeline of upcoming executions, sorted by `next_run_at`.
- **Interactive Controls:** The dashboard must provide immediate actions (Play, Pause, Edit, Delete) for each job card.

## 3. Non-Functional Requirements

### 3.1 Concurrency & Scalability
- The scheduler engine must be designed to run in a distributed environment. The use of `SELECT ... FOR UPDATE` (via Laravel's `lockForUpdate`) is a strict requirement to prevent race conditions when multiple workers are active.

### 3.2 Resilience & Error Handling
- **Failure Isolation:** The execution of a single job must be isolated. If a webhook times out or a command throws an exception, it must not crash the worker daemon. The worker must catch the exception, log the error, mark the job status as `failing`, and release the execution lock (`is_running = false`).
- **Zombie Recovery:** If a worker process crashes mid-execution while a job has `is_running = true`, the system must have a mechanism (or administrative capability) to reset the lock after a defined timeout period.

### 3.3 Security Constraints
- **Payload Validation:** The JSON payload must be strictly validated to prevent malformed data from causing execution crashes.
- **Script Execution Security:** The `script` type execution represents a severe remote code execution (RCE) risk. It must be strictly disabled or heavily sandboxed in production environments unless explicitly authorized by high-level administrators.

## 4. API Specifications
The hub relies on a RESTful backend (`SchedulerController`):
- `GET /scheduler`: Returns a listing of all `SchedulerJob` records.
- `POST /scheduler`: Creates a new job. Requires `name`, `type`, `cron_expression`.
- `PUT /scheduler/{id}`: Updates existing job parameters.
- `DELETE /scheduler/{id}`: Removes a job.
All responses must follow a standardized JSON envelope format (`{'success': true, 'data': ...}`).
