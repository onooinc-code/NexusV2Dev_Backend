# Admin Hub Requirements

## 1. Executive Summary
The Admin Hub (System Control Panel) is designed to provide administrative users and system operators with a comprehensive, centralized dashboard for monitoring and managing the Nexus backend architecture. It allows real-time visibility into system health, process statuses, Dead Letter Queues (DLQ), and application logs, while offering actionable controls to clear caches, compile assets, and restart critical services.

## 2. Functional Requirements

### 2.1 System Metrics and Health Monitoring
- **Requirement 2.1.1**: The system MUST display real-time (or near real-time, cached up to 30 seconds) metrics including:
  - CPU Load (derived from `sys_getloadavg()`).
  - Memory Usage (using `memory_get_usage()`).
  - Disk Space (Total and Free GB).
  - Server Uptime and OS version.
- **Requirement 2.1.2**: The metrics payload MUST be served via `SystemController@status` and should gracefully degrade to default values ("unknown" or "0") if underlying OS commands fail.

### 2.2 Process and Service Management
- **Requirement 2.2.1**: The hub MUST enumerate critical application services, specifically the API, Reverb (WebSocket), Next.js frontend, Queue workers, and Vite dev servers.
- **Requirement 2.2.2**: The hub MUST allow administrators to trigger Start, Stop, and Restart actions for each service through the `ProcessManager`.
- **Requirement 2.2.3**: Actions on services MUST clear the `admin:system:status` cache immediately to reflect the updated state on the next poll.

### 2.3 Build and Cache Control
- **Requirement 2.3.1**: Administrators MUST be able to trigger a full or partial frontend/backend build via a web interface. The system will execute the `build.ps1` (Windows) or `build.sh` (Linux/Mac) script.
- **Requirement 2.3.2**: Administrators MUST be able to execute `php artisan optimize:clear` equivalent functions to flush route, view, and config caches.

### 2.4 Dead Letter Queue (DLQ) Management
- **Requirement 2.4.1**: The system MUST provide an interface to view failed background jobs.
- **Requirement 2.4.2**: Supported actions for failed jobs MUST include:
  - Single Job Retry (`DlqController@retry`)
  - Batch Retry (`DlqController@batchRetry`)
  - Discard/Delete Job (`DlqController@destroy`)
- **Requirement 2.4.3**: The interface MUST show the Job ID, the specific Exception thrown, and the timestamp of failure.

### 2.5 Log Viewing
- **Requirement 2.5.1**: The system MUST read and display the trailing lines (default 100) of specific service log files from the `logs/` directory.
- **Requirement 2.5.2**: The UI MUST render logs in a specialized terminal emulator view (`.terminal-box`), parsing standard log levels (INFO, WARNING, ERROR) and applying corresponding CSS colorization.

## 3. Non-Functional Requirements

### 3.1 Performance
- **Requirement 3.1.1**: Status endpoint polling (e.g., every 5 seconds) MUST NOT overwhelm the server. The `SystemController` MUST cache the expensive metric calculations for at least 30 seconds.

### 3.2 Security
- **Requirement 3.2.1**: All API endpoints under the Admin Hub (`/admin/*` and `/hub/admin`) MUST require an authenticated session with Administrator privileges.
- **Requirement 3.2.2**: OS-level command execution (like triggering builds or fetching process IDs) MUST be strictly sanitized to prevent command injection vulnerabilities.

### 3.3 Platform Agnosticism
- **Requirement 3.3.1**: The `SystemController` MUST implement branching logic to support both Windows (`tasklist`, `wmic`, `powershell`) and Unix/Linux (`posix_kill`, `uptime`, `bash`) environments.
