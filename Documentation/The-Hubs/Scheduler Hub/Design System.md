# Scheduler Hub: Design System & Theming

## 1. Introduction
The Scheduler Hub UI relies on a technical, data-dense design language. Because the hub is primarily used by developers and system administrators, the design prioritizes information density, state visibility, and precise typography over large imagery or empty space. The aesthetic merges "Server Room Dashboard" with modern web glassmorphism.

## 2. Color Palette & State Mapping

### 2.1 Environmental Colors
- **Background Layer:** Dark and desaturated. Deep charcoal greys (`#161B22` equivalent in `rgba`) provide low eye strain for extended monitoring sessions.
- **Panel Overlays:** Glassmorphic (`backdrop-filter: blur(12px)`) layers create depth without requiring stark borders.

### 2.2 Operational Semantics
Colors are strictly mapped to operational states:
- **Green (`var(--nexus-success)`):** Indicates an `ACTIVE` job that is healthy and polling.
- **Yellow/Amber (`var(--nexus-warning)`):** Indicates a `PAUSED` job. It draws the eye because a paused job is an anomaly.
- **Red (`var(--nexus-danger)`):** Indicates failure. Used for delete actions and would be used to highlight a `FAILING` job status.
- **Primary/Indigo (`var(--nexus-primary)`):** Used for structural accents, like the animated pulse bar and system icons (clocks, calendars).

## 3. Core UI Elements

### 3.1 The Animated Pulse Bar (`.pulse-bar`)
This is a signature design element of the Hub. A 3px tall gradient bar at the top of active job cards that constantly pulses via CSS animation (`@keyframes pulseBar`).
- **Design Intent:** It mimics the blinking lights on a physical server rack, providing immediate, pre-attentive visual confirmation that a process is "alive" and running in the background.

### 3.2 The Cron Badge (`.cron-badge`)
Cron expressions are notoriously difficult to read. The design system isolates them to improve legibility.
- **Styling:** It uses a deeply dark background (`rgba(0, 0, 0, 0.4)`), a subtle white border (`rgba(255, 255, 255, 0.05)`), and rounded corners.
- **Typography:** Strictly monospaced (`font-family: monospace`) so characters align perfectly, making asterisks and numbers distinct.
- **Iconography:** Preceded by a calendar icon to immediately provide context for the string of symbols.

### 3.3 The Job Card (`.job-card`)
A unified container for task data.
- **Typography Hierarchy:** 
  1. The Job Name (`<h5>`) is the largest element, providing the human-readable identifier.
  2. The Job Type (`COMMAND`, `WEBHOOK`) sits directly below it in a smaller, monospaced, uppercase font, acting as technical metadata.
  3. The Timestamps (`NEXT:` and `LAST:`) are grouped together at the bottom in the smallest font (`0.65rem`), formatted as monospace raw datetimes for precision.

## 4. Layout Architecture
- **Grid System:** The active and paused jobs utilize a modular grid (`col-md-4`), allowing them to wrap neatly on smaller screens. This card-based layout ensures that adding new jobs expands the grid predictably without breaking the layout.
- **Timeline Column:** The "Upcoming Executions" takes up a wider column (`col-md-8`), providing horizontal breathing room for descriptive text and workflow names associated with future events.

## 5. Interaction Paradigms
- **Progressive Disclosure:** To keep the UI clean, the action buttons (Pause, Edit, Delete) are hidden by default and only revealed on hover (`.job-card:hover .action-bar`). This reduces visual noise when scanning the dashboard, while ensuring controls are instantly available when investigating a specific job.
- **Hover Transitions:** The card elevates smoothly (`transition: transform 0.2s, box-shadow 0.2s`) and its border glows with the primary indigo color, confirming to the user that this specific element is interactive and focused.

## 6. Implementation Constraints
The design relies on inline styles and specific Bootstrap/Tailwind utility classes. For long-term maintainability, the `.job-card` definition should be extracted into a reusable CSS component, as variations of this "Task Card" pattern are likely to appear in other areas of the application (like queue monitoring or server health dashboards).
