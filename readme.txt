=== RadiusForms ===
Contributors: hasandev
Tags: contact form, form builder, forms, drag and drop, survey
Requires at least: 6.2
Tested up to: 7.0
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build contact forms, surveys, quizzes, and lead-generation forms with a modern React-powered drag & drop builder.

== Description ==

RadiusForms is a fast, modern WordPress form builder. Create contact forms, surveys, quizzes, and
lead-generation forms with an intuitive drag-and-drop builder built in React. Collect and manage
submissions, get an email notification for every entry, and keep spam out — all on your own site,
with no third-party services involved.

= Highlights =

* **Drag & drop builder** – a three-pane React builder (field library, live canvas, settings).
* **18+ field types** – text, email, number, name, address, dropdown, radio, checkbox, country,
  date/time, terms, GDPR, columns, sections, custom HTML, and more.
* **Entries management** – view, filter, favorite, and export submissions to CSV.
* **Email notifications** – an email is sent to the site administrator for each new submission,
  with merge tags (smart codes) for the submitted values.
* **Spam protection** – a built-in honeypot that runs entirely on your own site.
* **Reports** – submission trends, top forms, timeline patterns, and per-field response charts.
* **Templates** – start fast with prebuilt form templates.
* **No third-party services** – RadiusForms never phones home and sends no data anywhere.
* **Developer friendly** – REST API (`radiusforms/v1`), action/filter hooks, and extension registries.
* **Centralized design system** – token-driven styling with light/dark support.

= Shortcode =

Embed any form with `[radiusforms id="123"]`.

== Installation ==

1. Upload the `radiusforms` folder to `/wp-content/plugins/`, or install via the Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **RadiusForms** in the admin menu and create your first form.
4. Place the form on any page with the `[radiusforms id="123"]` shortcode.

== Frequently Asked Questions ==

= Is there a limit on forms or submissions? =
No. Create unlimited forms and collect unlimited submissions.

= How is form data stored? =
RadiusForms uses its own custom database tables (prefixed `radiusforms_`) for performance and scalability.

= Does it work without JavaScript on the frontend? =
Forms are progressively enhanced; submissions are always validated and stored server-side.

== External services ==

RadiusForms does not use any third-party or external service.

It makes no outbound HTTP requests of any kind: it never phones home, sends no analytics, telemetry, or
usage data, loads no remote scripts, fonts, or images, and transmits no form submission — or any other
data — to the plugin author or to any other party. Everything the plugin does happens on your own site,
against your own database.

== Privacy ==

RadiusForms stores form submissions in custom database tables on your own site. Submission data is
never sent anywhere — the plugin makes no outbound requests at all (see "External services" above).

Alongside each submission the plugin records, on your own server only:

* the values submitted in the form fields,
* the submitter's IP address,
* the browser user agent string,
* the referring URL and the page the form was submitted from,
* the date and time of the submission.

This data is used to display and manage entries and to help you identify spam. It is never transmitted
to the plugin author and is never used for advertising, profiling, or cross-site tracking.

Site administrators can delete individual entries at any time from the Entries screen. Enabling
"Remove all data on uninstall" in RadiusForms → Settings deletes all forms, entries, and settings when
the plugin is deleted.

== Development ==

The compiled JavaScript and CSS in `assets/dist/` and `assets/frontend/` are minified builds. The
complete, unminified, human-readable source is included with the plugin in the `src/` directory, and
the build configuration (`package.json`, `webpack.config.js`) ships alongside it, so the compiled
assets can be reproduced from the plugin as distributed.

To rebuild the assets from source:

1. Install Node.js 20+ and npm.
2. From the plugin directory, run `npm install` to install the build dependencies.
3. Run `npm run build` to compile `src/` into `assets/dist/` and `assets/frontend/`.

The build uses webpack with two named entry points — `dist/radiusforms` (the React admin app, from
`src/main.jsx`) and `frontend/form` (the public form script, from `src/frontend/form.js`) — as
configured in `webpack.config.js`. No minified or obfuscated third-party code is bundled; all
dependencies are declared in `package.json`.

Use `npm run dev` for an unminified development build, or `npm start` to watch for changes.

== Changelog ==

= 1.0.0 =
* Initial release: React drag-and-drop builder, entries management, notifications, spam protection,
  reports, templates, REST API, and core integrations.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
