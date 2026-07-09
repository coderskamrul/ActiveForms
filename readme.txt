=== ActiveForms – Drag & Drop Form Builder ===
Contributors: pluginshift
Tags: contact form, form builder, forms, drag and drop, survey
Requires at least: 6.2
Tested up to: 6.8
Requires PHP: 7.4
Stable tag: 1.0.0
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Build contact forms, surveys, quizzes, and lead-generation forms with a modern React-powered drag & drop builder.

== Description ==

ActiveForms is a fast, modern WordPress form builder. Create contact forms, surveys, quizzes,
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
* **Integrations** – Mailchimp, Slack, and Webhook included; 40+ more in ActiveForms Pro.
* **Templates** – start fast with prebuilt form templates.
* **Developer friendly** – REST API (`activeforms/v1`), action/filter hooks, and extension registries.
* **Centralized design system** – token-driven styling with light/dark support.

= Shortcode =

Embed any form with `[activeforms id="123"]`.

== Installation ==

1. Upload the `activeforms` folder to `/wp-content/plugins/`, or install via the Plugins screen.
2. Activate the plugin through the **Plugins** menu in WordPress.
3. Open **ActiveForms** in the admin menu and create your first form.
4. Place the form on any page with the `[activeforms id="123"]` shortcode.

== Frequently Asked Questions ==

= Is there a limit on forms or submissions? =
No. Create unlimited forms and collect unlimited submissions.

= How is form data stored? =
ActiveForms uses its own custom database tables (prefixed `activeforms_`) for performance and scalability.

= Does it work without JavaScript on the frontend? =
Forms are progressively enhanced; submissions are always validated and stored server-side.

== Changelog ==

= 1.0.0 =
* Initial release: React drag-and-drop builder, entries management, notifications, spam protection,
  reports, templates, REST API, and core integrations.

== Upgrade Notice ==

= 1.0.0 =
Initial release.
