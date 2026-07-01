# Admin Hub Bugs & Known Issues

## 1. ProcessManager Race Conditions & Platform Discrepancies
### Issue Description
The `SystemController` relies heavily on OS-level commands (`tasklist` on Windows, `posix_kill` on Unix) to determine if a process is running.
- **Bug**: On Windows, `tasklist` checks can be incredibly slow, sometimes taking up to 2 seconds to execute. This blocks the PHP thread.
- **Bug**: If a service (like Vite) crashes and immediately restarts, the PID changes. The ProcessManager might hold a stale PID in its configuration, falsely reporting the service as "Offline" because the old PID is dead, even though the service is running on a new PID.
### Proposed Fix
Move process monitoring to a persistent daemon (like Supervisor on Linux or NSSM on Windows) and have the `SystemController` query the daemon's API rather than running raw shell commands.

## 2. Metric Caching Stampede
### Issue Description
The `admin:system:status` cache is set to expire every 30 seconds.
- **Bug**: If multiple administrators have the Admin Hub open simultaneously, and the cache expires, all of their 5-second polling intervals might hit the server at the exact same millisecond. This causes a "Cache Stampede," where the heavy OS metric commands (`wmic`, `disk_free_space`) are executed multiple times concurrently before the first request can write the result back to the cache.
### Proposed Fix
Implement Laravel's `Cache::lock` to ensure only one thread generates the metrics while other threads wait for the lock to release, or shift metric generation to a background scheduled task that runs every minute and simply writes to the cache.

## 3. Build Script Detachment (Windows)
### Issue Description
When triggering a build via `SystemController@triggerBuild`, the system executes:
`powershell -ExecutionPolicy Bypass -Command "& 'build.ps1' ..."`
- **Bug**: Occasionally, the PHP `shell_exec` command on Windows fails to fully detach the background process. This causes the HTTP request to hang until the Vite build completes (which can take 30+ seconds), leading to a 504 Gateway Timeout error on the frontend.
### Proposed Fix
Refactor the Windows command execution to use `pclose(popen("start /B ...", "r"));` or utilize Laravel's queued jobs to execute the shell command instead of executing it directly within the HTTP request lifecycle.

## 4. Unpaginated DLQ Payloads
### Issue Description
The `DlqController@index` fetches failed jobs.
- **Bug**: The actual serialized payload of a failed job can be massively large (e.g., if it contains binary file data or large Eloquent models). Fetching 20 DLQ items at once can exceed PHP's memory limit if the payloads are exceptionally large, resulting in a blank page or 500 Error.
### Proposed Fix
Modify the query in `DeadLetterQueueService` to select only the `id`, `connection`, `queue`, `exception`, and `failed_at` columns for the index view. Only fetch the heavy `payload` column when a specific job is clicked for detailed inspection.
