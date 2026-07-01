# Admin Hub Roadmap

## Near-Term Goals (Q3 2026)

### 1. Advanced Log Parsing and Search
Currently, the `SystemController@getServiceLogs` method simply reads the last 100 lines of a `.log` file and dumps them to the frontend.
- **Milestone**: Implement a regex-based log parser that structures logs into JSON objects (Timestamp, Level, Context, Message) before sending them to the UI.
- **Milestone**: Add a search bar to the "Raw Logs" tab that allows filtering by Log Level (Error only) or specific keywords without requiring a full page refresh.

### 2. Deeper Queue Analytics
The current Overview tab shows the total "Queue Backlog" and "DLQ Items".
- **Milestone**: Implement a "Queue Health" graph utilizing Chart.js or ApexCharts to show queue throughput over the last 60 minutes.
- **Milestone**: Differentiate backlog by queue name (e.g., `default`, `webhooks`, `ai_tasks`) rather than an aggregate number, providing better visibility into specific bottlenecks.

## Mid-Term Goals (Q4 2026)

### 3. Real-Time Service Status over WebSockets
Currently, `admin.blade.php` relies on a 5-second JavaScript `setInterval` to fetch the status payload.
- **Milestone**: Migrate the system health metrics from HTTP polling to Laravel Echo / Reverb WebSockets. The server should push metrics on a cron schedule directly to the `admin.system` private channel, reducing HTTP overhead.

### 4. Interactive DLQ Payload Editing
When a job fails due to malformed data, simply hitting "Retry" will result in another failure.
- **Milestone**: Allow administrators to click into a DLQ item, view the serialized JSON payload, manually edit the JSON string, and *then* dispatch the retry.

## Long-Term Vision (2027+)

### 5. Docker & Container Orchestration Integration
As Nexus scales, running bare-metal PHP processes (ProcessManager) will transition to containerized deployments.
- **Milestone**: Refactor `ProcessManager` to interface with the Docker API or Kubernetes API. Instead of running `tasklist` or `posix_kill`, the Admin Hub will check container health, restart containers, and tail container stdout directly.

### 6. "DevOps Agent" Integration
Leveraging the Agents Hub architecture, introduce an autonomous Agent dedicated to infrastructure.
- **Milestone**: The DevOps Agent will monitor the DLQ in real-time. When an exception like `Stripe\Exception\ApiErrorException` occurs, the Agent will cross-reference the Stripe API documentation and append a suggested fix to the DLQ item in the UI.
