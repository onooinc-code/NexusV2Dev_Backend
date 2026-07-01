# Admin Hub Design System

## Core Aesthetic
The Admin Hub is built upon the broader Nexus dark-mode design system but introduces specific visual tokens tailored for a "DevOps" or "SysAdmin" persona. The interface prioritizes high contrast, monospace typography for technical data, and clear visual hierarchy for system health states.

## Color Palette Tokens

### Backgrounds & Borders
- **Glass Panel Background**: `rgba(22, 27, 34, 0.5)` - Used for the main `.system-card` components to give a frosted glass (glassmorphism) effect against the darker app background.
- **Glass Border**: `var(--nexus-border)` - Used to define the edges of panels and terminal boxes, providing subtle separation.
- **Terminal Background**: `#000` (Pure Black) - Used exclusively in the `.terminal-box` to mimic a true console environment.

### Status & Severity Indicators
The Admin Hub relies heavily on color to communicate system state instantly:
- **Success (Running/Empty DLQ)**: Utilizes Laravel/Bootstrap success green (`text-success`), often paired with a low-opacity background (`bg-success bg-opacity-10`) for badges.
- **Warning (Busy/Threshold near)**: `var(--amber)` or `#fbbf24`. Used for warning-level logs or elevated resource usage.
- **Danger (Failed Jobs/Offline Services)**: `var(--error)` or `#f87171`. Extensively used in the DLQ tab (e.g., red border around the DLQ card) to draw immediate attention to system failures.
- **Info (Standard Operations)**: `var(--nexus-blue)` or `#60a5fa`. Used for standard operational buttons and informational log lines.

## Typography
- **Primary Font**: Inherited from the Nexus standard stack (typically Inter or system sans-serif).
- **Technical Font**: `font-family: monospace;` or `'JetBrains Mono', monospace;`. Applied to log lines, Exception class names, Job IDs, and any raw data output.
- **Metric Headings**: `.h2` with `.text-light` for large, readable KPI numbers (e.g., "12%", "1.4 GB"). Sub-labels use `.text-uppercase` with heavy letter-spacing (`1px`) for technical precision.

## Custom Components

### `.system-card`
A reusable KPI card designed for dashboard metrics.
```css
.system-card {
    background: rgba(22, 27, 34, 0.5);
    backdrop-filter: blur(12px);
    -webkit-backdrop-filter: blur(12px);
    border: 1px solid var(--nexus-border);
    border-radius: 12px;
    padding: 20px;
    transition: transform 0.2s;
}
.system-card:hover { transform: translateY(-2px); }
```

### `.nav-pills-custom`
A pill-based navigation menu that looks like a segmented control, sitting cleanly within the dark UI.
- Background: `rgba(255, 255, 255, 0.05)`
- Active State: `rgba(22, 27, 34, 0.8)` with a subtle box-shadow.

### `.terminal-box`
A scrollable, syntax-colored container for raw text output.
- Fixed height of `300px` to prevent layout shifting when massive logs are loaded.
- Color-coded children elements (`.error`, `.warn`, `.info`) to parse log severity instantly.

## Motion & Animation
- **Hover States**: Cards elevate (`translateY(-2px)`) on hover to indicate interactivity.
- **Staggered Entry**: Views utilize `.animate-fade-in` and `.stagger-1`, `.stagger-2` utility classes to load UI elements sequentially, making the dashboard feel snappy and engineered.
