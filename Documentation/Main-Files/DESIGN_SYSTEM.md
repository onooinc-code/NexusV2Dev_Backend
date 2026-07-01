# Nexus Next.js Architecture - Design System

This document outlines the design system implemented in the Nexus project. Although the backend leverages Laravel, the user interface adheres to a strict Tailwind CSS driven design system that provides consistency across both desktop and mobile views. The dashboard interface (`resources/views/dashboard.blade.php`) heavily utilizes these design tokens.

## 1. Color Palette

The color system is derived from Tailwind's default palette, carefully chosen to signify different semantic meanings across the application.

### Background Colors
- **App Background**: `bg-gray-100` - Used as the canvas for the entire application, providing a soft contrast against white cards.
- **Card Background**: `bg-white` - Used for sections and containers to make content pop out from the app background.
- **Header Background**: `bg-white` - Used for the top navigation bar.

### Text Colors
- **Primary Text**: `text-gray-900` - Used for high-emphasis text, such as headers (e.g., "Nexus Dashboard", "System Agents").
- **Secondary Text**: `text-gray-600` - Used for labels and secondary information (e.g., "Disk Space", "Pending").
- **Subtle Text**: `text-gray-500` - Used for empty states (e.g., "No health data").
- **Status/Highlight Text**:
  - `text-blue-600`: Used for log levels and informational highlights.
  - `text-red-600`: Used to indicate errors or debug mode being ON.
  - `text-green-600`: Used to indicate healthy status or debug mode being OFF.

### Action / Button Colors
- **Primary Action**: `bg-blue-600` (hover: `bg-blue-700`) - Used for primary actions like refreshing the dashboard.
- **Warning Action**: `bg-orange-600` (hover: `bg-orange-700`) - Used for cautionary actions like restarting queues.
- **Destructive Action**: `bg-red-600` (hover: `bg-red-700`) - Used for dangerous actions like clearing the cache.

## 2. Typography

The application uses standard web-safe sans-serif fonts provided by Tailwind, focusing on clear hierarchy and readability.

- **Page Title**: `text-2xl font-bold` (Dashboard header)
- **Section Title**: `text-lg font-bold text-gray-900 mb-4`
- **Standard Label**: `text-sm text-gray-600`
- **Data Value**: `text-sm font-medium` or `font-medium`
- **Small Metrics**: `text-xs font-medium`
- **Code/Logs**: `font-mono text-xs` - Used for displaying raw logs or technical paths.

## 3. Layout and Spacing

The layout system is built on flexbox and CSS grids via Tailwind utilities.

### Page Layout
- **Container**: `max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8` - Centers the main content, constrains the maximum width, and adds responsive horizontal padding.
- **Header**: Flex container (`flex justify-between items-center px-4 py-4 sm:px-6 lg:px-8`) with a bottom shadow (`shadow`).

### Component Spacing
- **Grids**: `grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6` - Used to align metric cards uniformly.
- **Card Padding**: `p-6` - Consistent inner padding for all cards.
- **Vertical Spacing within Cards**: `space-y-2`, `space-y-3`, or `space-y-6` - Used to separate list items or subsections without relying on custom margins.
- **Dividers**: `border-b border-gray-200 pb-2` - Used to separate rows within a list or card.

## 4. UI Components

### Cards
Cards are the primary container for grouping related information.
- **Base Classes**: `bg-white rounded-lg shadow p-6`

### Badges
Status badges are used to communicate the state of services, queues, and health checks.
- **Base Class Structure**: `px-2 py-1 text-xs rounded-full font-medium inline-block`
- **Healthy/Active**: `bg-green-100 text-green-800`
- **Warning/Pending**: `bg-yellow-100 text-yellow-800`
- **Error/Offline**: `bg-red-100 text-red-800`

### Loading Skeletons
To improve perceived performance, loading skeletons are used while data is being fetched.
- **Base Skeleton Class**: `loading-skeleton inline-block rounded bg-gray-200 animate-pulse`
- **Dimensions**: `w-20 h-5`, `w-12 h-5`, `w-full h-4 mb-2` (depending on the context of the data being loaded).

### Progress Bars
Used for displaying storage usage or capacity.
- **Track**: `w-full bg-gray-200 rounded-full h-2`
- **Fill**: `h-2 rounded-full` combined with a color like `bg-blue-600` or `bg-green-600`.

## 5. Interaction States

- **Hover Effects**: All buttons feature a background color transition on hover to provide immediate visual feedback (e.g., `hover:bg-blue-700 transition`).
- **Focus Rings**: Standard Tailwind focus rings are expected for keyboard navigation (though implicit in standard setup, ensure `focus:ring` utilities are used on form elements).

This design system ensures that whether the user is interacting with the Laravel Blade dashboard or the Next.js frontend, the visual language remains cohesive, professional, and accessible.
