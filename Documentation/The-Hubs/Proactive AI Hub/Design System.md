# Proactive AI Hub: Design System & Theming

## 1. Introduction
The Proactive AI Hub utilizes a specialized sub-theme within the broader Nexus application. It leans heavily into a "Cyber-Physical" aesthetic, blending dark mode base layers with vivid, high-contrast utility colors to represent autonomous activity and intelligence. The design system is strictly enforced via inline styles, global CSS variables, and utility classes in the Blade template.

## 2. Color Palette & Semantics

### 2.1 Base Colors (The "Panel" Look)
- **Backgrounds:** `rgba(22, 27, 34, 0.5)` to `0.8`. This is a deep, slightly desaturated blue-grey, highly reminiscent of modern IDEs or command-line interfaces.
- **Borders:** `var(--nexus-border)`. A subtle, low-opacity divider used to structure content without overwhelming the eye.
- **Panels:** `var(--nexus-panel)`. Solid dark backgrounds used for elevated elements like Modals.

### 2.2 Semantic Accent Colors
The hub relies on explicit semantic colors to convey state and action types rapidly:
- **Primary (Indigo/Blue):** Used for standard actions ("New Rule" button) and structural icons.
- **Warning (Yellow/Amber - `text-warning`):** Represents Time, Scheduling, and Pending states. Crucial for indicating that the system is "waiting" to do something.
- **Success (Green - `text-success`):** Indicates completed actions or active, healthy states.
- **Danger (Red - `text-danger`):** Used for destructive actions (Delete buttons) and critical system events (e.g., CPU spikes).
- **Secondary (Grey - `text-secondary`):** Muted actions, inactive states, and structural text.

## 3. Core UI Components

### 3.1 The `.stat-card` Element
Used to display high-level metrics.
- **Visuals:** Features a glassmorphism backdrop (`backdrop-filter: blur(12px)`).
- **Typography:** Muted, uppercase headers with wide letter-spacing (`0.75rem; letter-spacing: 1px`) to give a technical, dashboard feel. Large, high-contrast numbers for the actual metric.
- **Interaction:** Slight Y-axis translation on hover (`transform: translateY(-2px)`) to provide a tactile response.

### 3.2 The `.rule-card` Element
The primary list item for ECA rules.
- **Visuals:** Similar to stat-cards but with a distinct hover effect. On hover, the border color shifts to a glowing indigo (`rgba(99, 102, 241, 0.4)`), mimicking a "selected" or "focused" state in a futuristic UI.
- **State Modifications:** The `.paused` class drops opacity to `0.6`. This visually demotes the rule without removing it, clearly indicating inactivity.
- **Animation:** Active rules feature a pulsing dot (`.animate-pulse`) to signify that the rule is "listening" or actively polling.

### 3.3 The Pill Navigation (`.nav-pills-custom`)
Replaces standard Bootstrap tabs with a smoother, containerized look.
- **Container:** A rounded pill holding all tabs (`border-radius: 50px; background: rgba(255, 255, 255, 0.05)`).
- **Active State:** The active tab adopts a darker background (`rgba(22, 27, 34, 0.8)`) with a drop shadow, making it look like a physical switch that has been depressed.

## 4. Typography Rules
- **Headers:** Light colored (`text-light`), standard weights. The page title uses a subtle `h4`.
- **Descriptions:** Muted colors (`text-muted`), usually small (`.small`), providing context without demanding attention.
- **Badges/Tags:** Frequently use fixed-width, technical, or slightly smaller fonts with accompanying FontAwesome icons to categorize data (e.g., "Email Event", "Time Event").

## 5. Animation Principles
- **Staggered Entry:** The UI uses `.animate-fade-in` combined with `.stagger-1`, `.stagger-2` classes. This ensures that the page loads sequentially (Stats -> Tabs -> Content), guiding the user's eye and preventing overwhelming visual changes.
- **Pulse Indicators:** The `.animate-pulse` class is used sparingly on status indicators to show liveness (e.g., active rules, active clock icons).
- **Micro-interactions:** Hover states on buttons (like the `hover-text-danger` on trash icons) provide immediate feedback before a click occurs.

## 6. Implementation Notes
The design system is currently implemented directly within the `proactive-ai.blade.php` file using `<style>` blocks. For scalability, these classes (`.stat-card`, `.rule-card`) should eventually be abstracted into a central SCSS or Tailwind configuration file under the Nexus frontend asset compilation pipeline.
