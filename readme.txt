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

RadiusForms is a fast, modern WordPress form builder. Create contact forms, surveys, quizzes,
conversational forms, and lead-generation forms with an intuitive drag-and-drop builder built in
React. Collect and manage submissions, get instant email notifications, protect forms from spam,
and connect to your favorite tools.

= Highlights =

* **Drag & drop builder** – a three-pane React builder (field library, live canvas, settings).
* **18+ field types** – text, email, number, name, address, dropdown, radio, checkbox, country,
  date/time, terms, GDPR, columns, sections, custom HTML, and more.
* **Entries management** – view, filter, favorite, and export submissions to CSV.
* **Email notifications** – multiple notifications with merge tags (smart codes).
* **Spam protection** – built-in honeypot plus optional reCAPTCHA, hCaptcha, or Cloudflare Turnstile.
* **Reports** – response distribution charts for choice fields.
* **Integrations** – Mailchimp, Slack, Webhook, and Google Sheets included.
* **Templates** – start fast with prebuilt form templates.
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

== Development ==

Development repository (full source + build tooling): https://github.com/coderskamrul/RadiusForms

The React admin app and the public form script are compiled from the `src/` directory into `assets/dist/` and `assets/frontend/`. To regenerate the compiled assets from source:

1. Install Node.js 20+ and npm.
2. Run `npm install` to install the build dependencies.
3. Run `npm run build` to compile `src/` into `assets/dist/` and `assets/frontend/`. The build uses webpack with two named entry points — `dist/radiusforms` (the React admin app) and `frontend/form` (the public form script) — as configured in `webpack.config.js`.

Use `npm run dev` for an unminified development build, or `npm start` to watch for changes.

== Changelog ==

= 1.0.0 =
* Initial release: React drag-and-drop builder, entries management, notifications, spam protection,
  reports, templates, REST API, and core integrations.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
