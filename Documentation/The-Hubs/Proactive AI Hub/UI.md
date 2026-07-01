# Proactive AI Hub: User Interface Documentation

## 1. UI Overview
The Proactive AI Hub UI, defined in `resources/views/hubs/proactive-ai.blade.php`, presents a modern, dark-themed dashboard. It is designed to look like a high-tech control center, utilizing glassmorphism (backdrop-blur), neon accents, and smooth transitions to convey the "AI" and "Automation" themes.

## 2. Key UI Sections

### 2.1 The Header & Actions
- **Title:** "Proactive AI Engine" with a lightning bolt icon (`fa-bolt`), immediately signaling action and energy.
- **Action Button:** A prominent "New Rule" button that triggers a Bootstrap modal (`#newRuleModal`). This is the primary entry point for user interaction.

### 2.2 Statistics Grid
A responsive 4-column grid displaying key metrics:
- **Total Rules:** Total number of ECA rules defined.
- **Active Rules:** Rules currently running and parsing events.
- **Pending Triggers:** Actions queued for the future.
- **Actions Taken:** Total historical autonomous actions executed.
- *Styling Note:* Each card uses `.stat-card` with `rgba(22, 27, 34, 0.5)` background and `backdrop-filter: blur(12px)`. The icons are wrapped in circular badges with low-opacity colored backgrounds (e.g., `bg-primary bg-opacity-10`) to create a glowing effect.

### 2.3 Navigation Tabs
A pill-based navigation system (`nav-pills-custom`) allows the user to switch between three primary views without reloading the page:
- **ECA Rules:** The default view showing defined rules.
- **Scheduled Triggers:** The queue of upcoming actions.
- **Action Logs:** The audit trail of past actions.

### 2.4 ECA Rules Tab Content
- Displays a list of `.rule-card` elements.
- Each card shows the natural language rule as the primary title (e.g., "When I receive an email from...").
- A pulsing dot (`animate-pulse`) indicates if the rule is active.
- **Interactions:** Users can pause/play rules or delete them via small icon buttons on the right. The UI script includes a `toggleRule(id)` function that visually toggles the "paused" state (dimming the card and swapping the play/pause icon).

### 2.5 Scheduled Triggers Tab Content
- Displays triggers as functional cards (`.trigger-card`).
- Shows the Trigger Type, Context/Payload, Status, and a human-readable countdown (`next_run_at`).
- Includes a fallback UI ("No Pending Triggers") using an empty state illustration (a large, muted calendar icon) to maintain visual balance when the queue is empty.

### 2.6 Action Logs Tab Content
- A dark-themed table (`table-dark table-hover`) displaying the `AutonomousLog` entries.
- Columns: Time, Subject, Action Taken, and Status.
- Statuses are color-coded using badges (Success = Green, Warning = Yellow).

### 2.7 The "New Rule" Modal
- A centered modal (`modal-dialog-centered`) providing a clean input area.
- Contains a large `<textarea>` for the natural language input.
- Includes an innovative UI element: a toggle switch for "Connect Global Memory", hinting at advanced AI capabilities.
- Provides "Quick Examples" as clickable badges to help users understand the expected input format and reduce the cold-start problem.

## 3. JavaScript & Interaction Logic
The view includes lightweight JavaScript to handle immediate UI feedback:
- `toggleRule(id)`: Manipulates DOM classes to visually represent a paused rule, changing opacities and icons instantaneously before the backend request would complete.
- `createRule()`: Mocks the creation process by calling a global `Nexus.showTaskLoader()` function, simulating a brief compilation delay ("Compiling natural language to ECA Graph..."), before alerting success. This provides a satisfying, "heavy computation" feel to the AI feature.

## 4. CSS Architecture
The file leverages custom `<style>` blocks alongside Bootstrap 5 classes and Tailwind-like utility concepts:
- **Glassmorphism:** Heavy use of `rgba` backgrounds and `-webkit-backdrop-filter`.
- **Hover States:** Cards translate upwards (`transform: translateY(-2px)`) and change border colors (`rgba(99, 102, 241, 0.4)`) to provide tactile feedback.
- **Animations:** Custom classes like `animate-fade-in stagger-1` indicate an underlying animation framework (likely defined in `layouts.app`) that cascades elements into view on page load.
