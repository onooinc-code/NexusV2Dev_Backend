# Nexus Next.js Architecture - Mobile UI Specifics

The Nexus application relies on responsive design patterns implemented through Tailwind CSS to ensure a seamless experience across devices. While the desktop interface is optimized for multi-monitor setups and large displays (often utilizing three-column grids), the mobile UI requires specific adaptations to handle constrained screen real estate and touch-based interactions.

## 1. Responsive Grid Systems

The core layout shifts dynamically based on viewport width.
- **Mobile First Approach**: By default, all grid containers are stacked vertically using `grid-cols-1`. This ensures that cards and lists take up the full width of the mobile device.
- **Tablet Breakpoint (`md:`)**: At `min-width: 768px`, the layout shifts to `md:grid-cols-2`.
- **Desktop Breakpoint (`lg:`)**: At `min-width: 1024px`, the layout expands to `lg:grid-cols-3`.

This pattern (`grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6`) is consistently applied to the dashboard metrics and agent status views.

## 2. Touch Targets and Padding

On mobile devices, precision clicking is replaced by touch gestures. As such, the UI components are sized to prevent accidental taps.
- **Buttons**: All buttons use generous padding (e.g., `px-3 py-2`, `px-4 py-2`) to ensure the touch target is at least 44x44 pixels.
- **List Items**: Items within cards that require interaction are spaced using `space-y-3` or `space-y-4` instead of dense packing, reducing the likelihood of a user tapping the wrong row.
- **Container Padding**: The main layout container uses `px-4` on mobile devices to prevent content from touching the screen edges, switching to `sm:px-6` and `lg:px-8` on larger screens.

## 3. Navigation Adaptations

While the desktop view features a persistent top navigation bar or sidebar with full labels, the mobile UI condenses these elements.
- The top header (`flex justify-between items-center`) handles the title on the left and actions (like the Refresh button) on the right.
- On extremely narrow screens, text labels on buttons may be hidden in favor of icons, or actions may be moved into a dropdown menu (hamburger menu) to save vertical space.

## 4. Typography Adjustments

Text sizes are carefully managed to remain legible without forcing horizontal scrolling.
- **Truncation**: For long data values (such as log messages or UUIDs), text truncation utilities like `truncate` or `break-words` are used. For example, in the recent logs section, the message container uses `break-words` to ensure long strings wrap naturally on small screens.
- **Scaling**: Headings remain clear but do not overpower the screen. The page title is `text-2xl` on mobile, scaling up if needed on larger screens. Log views drop down to `text-xs` to fit more density on screen.

## 5. Scrolling and Overflow Handling

Mobile screens lack the vertical space to display extensive lists (like system logs or queue details) inline.
- **Max Height Constraints**: The recent logs section is constrained using `max-h-96` with `overflow-y-auto`. This allows the user to scroll within the specific container without losing the context of the rest of the page.
- **Horizontal Scroll Prevention**: Careful use of `w-full` on cards and responsive grids guarantees that no element forces the browser to scroll horizontally, a common UX anti-pattern on mobile.

## 6. Visual Hierarchy Adjustments

On mobile, the user can only see one or two cards at a time. Therefore, the order of elements in the DOM dictates their priority.
- Critical system metrics (Frontend status, Database health) are placed at the top of the grid structure.
- Secondary controls (Cache clearing, Queue restarting) and extensive logs are placed further down.
- Badges (`status-badge`) are kept compact (`text-xs px-2 py-1`) to fit neatly inline with section titles or row headers on mobile screens.

These mobile UI specifics ensure that the Nexus dashboard remains a powerful administration tool even when accessed from a smartphone on the go.
