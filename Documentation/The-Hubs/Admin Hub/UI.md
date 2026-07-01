# Admin Hub UI Documentation

## Layout Structure
The Admin Hub UI is rendered by `resources/views/hubs/admin.blade.php`, extending the `layouts.app` master layout. It employs a "Glassmorphism" aesthetic suitable for dark mode, utilizing backdrop filters and semi-transparent backgrounds.

## Header Section
- Contains the main title: "System Control Panel" with a wrench icon (`fa-screwdriver-wrench`).
- Includes an "Auto-Refresh (5s)" toggle switch bound to JavaScript polling logic.
- Features a "Restart Core Services" red danger button for emergency restarts.

## Navigation Pills
The UI uses a custom Bootstrap pill navigation (`.nav-pills-custom`) to split the dense technical information into four distinct panes:
1. **Overview**: High-level system metrics and build controls.
2. **Services**: Detailed list of background daemons and their PIDs/uptime.
3. **Dead Letter Queue (DLQ)**: Interface for managing failed Laravel Horizon/Queue jobs.
4. **Raw Logs**: A pseudo-terminal interface for tailing `.log` files.

## Tab: Overview
Displays four main KPI cards (`.system-card`):
- **CPU Load** (e.g., "12%")
- **Memory Usage** (e.g., "1.4 GB")
- **Queue Backlog** (e.g., "0" - styled success green)
- **DLQ Items** (e.g., "3" - styled danger red)

Below the KPI cards is the **Build Control** panel, offering:
- **Trigger Build**: Button executing `runBuild()`, wrapping API calls to dispatch `npm run build`.
- **Clear Cache**: Button executing `clearCache()`, wrapping API calls for `artisan optimize:clear`.

## Tab: Services
Renders a dark-themed table (`.table-dark.table-hover`) detailing:
- **Service Name** (e.g., Horizon Queue Worker, Reverb WebSocket Server)
- **Type** (Background, Daemon)
- **Status** (Rendered as Badges, e.g., `<span class="badge bg-success bg-opacity-10 text-success">RUNNING</span>`)
- **Uptime**
- **Actions** (Restart/Stop icon buttons)

## Tab: Dead Letter Queue (DLQ)
A warning-themed panel (red border opacity) designed to highlight broken jobs:
- Features a "Retry All" bulk action button.
- A table listing `Job ID`, `Exception` class (e.g., `Stripe\Exception\ApiErrorException`), and `Failed At` timestamp.
- Each row contains a specific "Retry" action button.

## Tab: Raw Logs
Implements a custom CSS `.terminal-box` to mimic a command-line interface:
- Fixed height (300px) with `overflow-y: auto`.
- Monospace font styling.
- Line-specific coloring based on log severity:
  - `.error` (color: `#f87171`)
  - `.warn` (color: `#fbbf24`)
  - `.info` (color: `#60a5fa`)

## JavaScript Interaction
The Blade file includes inline `<script>` tags that hook into the Nexus global UI components:
- `Nexus.showTaskLoader('Message')` and `Nexus.hideTaskLoader()` are utilized during long-running operations like clearing caches or compiling assets to block the UI and provide visual feedback to the administrator.
- Modals and alerts are triggered post-execution to confirm success or bubble up errors.
