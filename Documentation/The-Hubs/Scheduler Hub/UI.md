# Scheduler Hub: User Interface Documentation

## 1. UI Overview
The Scheduler Hub UI (`resources/views/hubs/scheduler.blade.php`) is built to manage complex backend configurations with a clean, visual, and highly interactive interface. It shares the dark-theme "Nexus" styling but focuses heavily on data visualization and state indication, making cron strings readable and statuses obvious at a glance.

## 2. Key UI Components

### 2.1 The Job Card (`.job-card`)
The fundamental building block of the interface. Each scheduled task is represented as a card.
- **Glassmorphism:** Uses `background: rgba(22, 27, 34, 0.5)` with `backdrop-filter: blur(12px)`.
- **Hover Effects:** Sophisticated hover states where the card elevates (`translateY(-4px)`), gains a deeper shadow, and reveals an action bar (`opacity: 1`).
- **The Pulse Bar (`.pulse-bar`):** A continuous, animated gradient line at the very top of active cards. It uses keyframe animation (`@keyframes pulseBar`) to cycle opacity, visually communicating that the task is "alive" and polling in the background.

### 2.2 Typographic Hierarchy
- **Title:** High contrast, modern sans-serif (`<h5 class="text-light">`).
- **Job Type:** Small, uppercase, monospaced font (`font-family: monospace; letter-spacing: 1px;`) to distinctly separate technical metadata from the human-readable title. (e.g., `COMMAND`, `JOB`, `WEBHOOK`).
- **Cron Badge:** A specialized container (`.cron-badge`) that displays the raw cron string (e.g., `0 * * * *`). It uses a darker background and monospaced font to resemble terminal output, instantly recognizable to developers.

### 2.3 Status Badges
State is communicated via explicit color coding:
- **Active:** Green text and background opacity (`bg-success bg-opacity-10 text-success`).
- **Paused:** Yellow/Amber text and background opacity (`bg-warning bg-opacity-10 text-warning`).
This ensures that an administrator scanning the page can immediately identify stopped services.

### 2.4 The Action Bar
Hidden by default to reduce visual clutter, the action bar appears on hover (`.job-card:hover .action-bar`). It provides immediate access to context actions:
- **Pause/Play:** Toggles the `status` of the job.
- **Edit:** Opens the modal with pre-filled data.
- **Delete:** Prompts for destructive action.

### 2.5 Upcoming Executions Timeline
Occupying a larger column width (`col-md-8`), this section visualizes the future.
- It translates abstract cron strings into a concrete vertical timeline.
- Each `.timeline-item` displays the computed `H:i` of the next execution.
- It groups jobs visually, providing a narrative of what the system will do over the next few hours.
- *Note:* The blade template references `$schedule->workflow->name`, indicating an anticipated architectural link between raw scheduled jobs and higher-level "Workflows".

### 2.6 The Job Modal (`#jobModal`)
A clean, centered form for creating and editing jobs.
- **Inputs:** Job Name, Type Dropdown, Cron Expression input.
- **Payload Area:** A `<textarea>` explicitly styled with a monospaced font (`font-monospace`) to encourage JSON structure input.

## 3. Interaction & JavaScript Integration
- **Loading State Simulation:** The `saveJob()` function currently simulates an asynchronous save by triggering a global loader (`Nexus.showTaskLoader`), demonstrating the intended UX pattern where modal interactions provide immediate, non-blocking feedback before refreshing the UI.
- **Animations:** The initial load utilizes `.animate-fade-in stagger-1` to waterfall the cards into view, a signature Nexus micro-interaction that makes the application feel responsive and high-performance.

## 4. CSS Architecture
- **CSS Variables:** Heavy reliance on global variables like `var(--nexus-panel)` and `var(--nexus-border)` ensures the Hub remains visually consistent with the rest of the application and supports potential theme switching.
- **Utility Classes:** The markup is a hybrid of custom component classes (`.job-card`, `.cron-badge`) and Bootstrap 5 / Tailwind-style utilities (`d-flex`, `justify-content-between`, `mb-3`, `opacity-10`), providing rapid layout structuring while maintaining bespoke aesthetic components.
