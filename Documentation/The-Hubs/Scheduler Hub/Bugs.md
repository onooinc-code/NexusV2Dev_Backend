# Scheduler Hub: Bug Report & Known Issues

## 1. Overview
This document outlines critical bugs, logical flaws, and incomplete implementations within the Scheduler Hub architecture. Addressing the execution simulation and atomic lock edge cases is paramount before deploying this system to a production environment.

## 2. High Priority / Critical Bugs

### 2.1 Simulated Execution Block
- **Location:** `app/Console/Commands/SchedulerWorker.php` (Lines 73-74)
- **Description:** The worker currently simulates execution: `// Simulate execution payload`. It retrieves the job, updates the lock, calculates the next run time, and closes the transaction, but it *never actually performs the task*.
- **Impact:** Critical. The scheduling engine is essentially a dummy loop that updates database timestamps but produces zero real-world action. Commands are not run, and webhooks are not fired.
- **Proposed Fix:** Implement the payload parsing and execution logic. Use `Artisan::call()` for commands, `Http::post()` for webhooks, and the dispatcher for queued jobs based on the `$job->type` property.

### 2.2 Unbounded Payload Data Type
- **Location:** `app/Http/Controllers/SchedulerController.php` (Line 32)
- **Description:** The validation rules state `'payload' => 'nullable|array'`. However, there is no schema validation for the contents of the payload based on the job `type`. A user could create a `webhook` job without providing a URL in the payload, which would crash the worker when it attempts to execute it.
- **Impact:** High. User error can easily cause unhandled exceptions in the worker daemon.
- **Proposed Fix:** Implement conditional validation logic. If `type == 'webhook'`, require `payload.url`. If `type == 'command'`, require `payload.command`.

## 3. Medium Priority Bugs

### 3.1 Zombie Lock Potential
- **Location:** `app/Console/Commands/SchedulerWorker.php` (Lines 68-80)
- **Description:** The worker sets `$job->is_running = true` and saves it. It then supposedly executes a task. If the execution causes a fatal error (e.g., Out of Memory, or the server is forcibly restarted `SIGKILL` during execution), the catch block will never be reached. The job will remain stuck with `is_running = true` forever in the database, and no worker will ever pick it up again.
- **Impact:** Medium. Rare events can permanently disable critical cron jobs until an administrator manually intervenes in the database.
- **Proposed Fix:** Implement a "stale lock" release mechanism. When querying for jobs, include a condition that picks up jobs where `is_running = true` AND `last_run_at` is older than a specific threshold (e.g., 1 hour), resetting them to a safe state.

### 3.2 Sleep Loop Drifting
- **Location:** `app/Console/Commands/SchedulerWorker.php` (Line 44)
- **Description:** The loop uses `sleep(60)`. If `processDueJobs()` takes 15 seconds to run, the total loop time becomes 75 seconds. Over time, the polling cycle will drift significantly out of alignment with the top of the minute, potentially causing jobs scheduled for exactly `12:00:00` to be executed at `12:00:55` or later.
- **Impact:** Medium. Timing inaccuracy for precise cron schedules.
- **Proposed Fix:** Calculate the exact number of seconds remaining until the start of the *next* minute and sleep for that exact duration, rather than a flat 60 seconds.

## 4. Low Priority / UI Bugs

### 4.1 Missing View Variables
- **Location:** `resources/views/hubs/scheduler.blade.php` (Line 172)
- **Description:** The view attempts to loop over a variable `$schedules` (`@forelse($schedules as $schedule)`), but the `SchedulerController::index()` method only returns `$jobs`. There is a variable mismatch. Furthermore, it references `$schedule->workflow->name`, implying a relationship (`workflow()`) that does not exist on the `SchedulerJob` model provided.
- **Impact:** Low. The view will throw an `Undefined variable $schedules` ErrorException when rendered if not suppressed.
- **Proposed Fix:** Update the controller to pass the `$schedules` variable, or update the view to loop over the correct data array provided by the API response. Also, clarify or remove the `workflow` relationship assumption.

### 4.2 Modal Save Simulation
- **Location:** `resources/views/hubs/scheduler.blade.php` (Line 235)
- **Description:** The `saveJob()` JavaScript function inside the blade template only simulates a save using `setTimeout` and a loader. It does not extract data from the modal form fields, nor does it perform an AJAX `POST` to the `SchedulerController`.
- **Impact:** Low (Development stub). The UI form cannot actually create jobs.
- **Proposed Fix:** Bind the input fields to JS variables, construct a JSON payload, and use `axios` or `fetch` to submit the data to the API, then dynamically add the new job card to the DOM on success.
