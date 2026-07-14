* Plugin Name: **RadiusForms**
* Free Plugin Slug: **radiusforms**
* Pro Plugin: **RadiusForms Pro**
* Text Domain: **radiusforms**
* PHP Prefix: **radiusforms_**
* Class Prefix: **RadiusForms\**
* React Namespace: **RadiusForms**
* Gutenberg Namespace: **radiusforms/**

Create a centralized global configuration and design system for this WordPress plugin so that Plugin Name, Plugin Slug, Prefix, Text Domain, REST Namespace, React Config, Option Keys, Translation Strings, and UI Styles are manageable from a single source.

Requirements:

* Use PHP constants for all global plugin variables
* Sync PHP config with React using `wp_localize_script`
* Avoid hardcoded plugin names, prefixes, text domains, and UI values
* Use namespaces or class-based architecture instead of function prefixes
* Make React and PHP both use s
* hared config values
* Keep internal DB keys, namespaces, and option names stable even if branding changes later
* Follow WordPress.org coding standards and compatibility requirements
* Add scalable architecture for future rebranding or white-label support
* Make translation strings dynamic and reusable

Global UI Design System:

* Create a centralized theme/style configuration for the entire plugin
* Store design tokens such as colors, typography, spacing, border radius, shadows, z-indexes, breakpoints, and animation values in one place
* Use CSS variables (custom properties) for all design tokens
* Ensure all React components consume global design tokens instead of hardcoded styles
* Support light and dark mode through the global theme system
* Allow easy future branding updates by changing only the global theme configuration
* Maintain consistent styling across all admin pages, modals, forms, tables, notices, and React components
* Ensure the design system is responsive, accessible, and scalable
* Follow modern WordPress admin UI patterns while keeping a unique plugin identity
