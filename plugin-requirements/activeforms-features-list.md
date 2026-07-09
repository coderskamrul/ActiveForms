# ActiveForms — Project Requirements & Feature Specification

> **Plugin:** ActiveForms – Drag & Drop Form Builder for WordPress
> **Free slug:** `activeforms` · **Pro:** ActiveForms Pro · **Text Domain:** `activeforms`
> **Class namespace:** `ActiveForms\` · **PHP/option prefix:** `activeforms_` · **REST namespace:** `activeforms/v1`
> **React namespace:** `ActiveForms` · **Block namespace:** `activeforms/`
> **Targets:** WordPress 6.2+, PHP 7.4+, Node.js 20 (frontend build), WordPress.org Plugin Directory.

This document specifies the complete feature set ActiveForms delivers. It is derived from a full
analysis of Fluent Forms (Free) and Fluent Forms Pro, and reframed as an **original implementation**
with a distinct architecture, folder structure, and codebase. ActiveForms aims for feature parity with
the user-facing capabilities of both products while shipping a modern React admin and a clean,
WordPress.org-compliant PHP backend.

---

## 1. Product Vision & Principles

| Principle | What it means for ActiveForms |
| --- | --- |
| **Modern, fast UI** | A single-page React admin (Vite build) with a drag-and-drop builder, instant preview, and a token-driven design system supporting light/dark mode. |
| **Original codebase** | No code copied from Fluent Forms. Custom autoloader, service container, REST layer, field registry, and DB schema designed from scratch. |
| **.org-ready** | Strict adherence to WordPress Plugin Directory guidelines: prefixing, escaping/sanitization, nonces, `permission_callback`, GPLv2+, no obfuscation, sandboxed assets. |
| **Free + Pro split** | A free core that is fully usable, with Pro features gated behind an extensibility layer (hooks/registries) so ActiveForms Pro can attach without forking the core. |
| **Centralized config** | Plugin name, slug, prefixes, REST namespace, option keys, capabilities, design tokens, and translatable strings all flow from a single source of truth shared by PHP and React. |
| **Scalable & white-label-ready** | Internal DB keys, namespaces, and option names stay stable even if branding changes. A theme/config object enables future rebranding by editing one place. |

---

## 2. Architecture Overview (Original Design)

ActiveForms deliberately uses a different architecture from Fluent Forms (which uses the WPFluent
framework, Eloquent-style models, and a `boot/app.php` container). ActiveForms uses a lightweight,
purpose-built structure:

```
activeforms/
├── activeforms.php                  # Main bootstrap (header, constants, autoloader handoff)
├── uninstall.php                  # Full data cleanup guarded by WP_UNINSTALL_PLUGIN
├── readme.txt                     # WordPress.org readme
├── package.json / vite.config.js  # Node 20 frontend toolchain
├── includes/                      # PHP backend (PSR-4: ActiveForms\)
│   ├── Core/                       # Plugin, Config, Container, Autoloader, Activator, Deactivator, I18n
│   ├── Database/                   # Schema, Installer, Tables/*, Query builder helpers
│   ├── Models/                     # Form, Entry, FormMeta, EntryMeta, Log (plain data-mapper objects)
│   ├── Rest/                       # AbstractController + Forms/Entries/Settings/Builder/Integrations controllers
│   ├── Fields/                     # FieldRegistry + Field type classes (validation/sanitization/render)
│   ├── Builder/                    # Form schema service, templates, import/export, duplication
│   ├── Frontend/                   # Renderer, Shortcode, Block, SubmissionProcessor, AssetManager
│   ├── Admin/                      # Menu, AdminApp bootstrap, AssetManager, DashboardWidget
│   ├── Notifications/              # Email engine, SmartCodes parser, notification router
│   ├── Spam/                       # Honeypot, captcha providers, Akismet bridge
│   ├── Integrations/               # IntegrationRegistry + Abstract integration + free integrations
│   ├── Reporting/                  # Analytics aggregator, report builders
│   ├── Acl/                        # Capability map and role manager
│   └── Support/                    # Helpers, Arr, Str, Sanitizer, Validator, Logger
├── src/                            # React app (JSX)
│   ├── main.jsx                    # Entry; mounts router
│   ├── config/                     # Reads window.ActiveFormsConfig (localized), design tokens
│   ├── theme/                      # CSS variables + light/dark; tokens.css
│   ├── api/                        # REST client (nonce-aware fetch wrapper)
│   ├── store/                      # Global state (forms, builder, entries)
│   ├── components/                 # Reusable UI primitives (Button, Modal, Table, Drawer, …)
│   ├── builder/                    # Drag-and-drop builder (Canvas, FieldPanel, SettingsPanel)
│   ├── pages/                      # Dashboard, Forms, Builder, Entries, Reports, Settings, Integrations
│   └── render/                     # Shared field renderers used by builder preview & frontend
└── assets/
    ├── dist/                        # Built JS/CSS (committed for .org distribution)
    └── frontend/                    # Frontend form CSS + lightweight vanilla submit handler
```

**Key technical choices**

- **Autoloading:** A small custom PSR-4 autoloader (`ActiveForms\` → `includes/`). Composer optional for
  dev only; the distributed plugin does not require a vendor directory for core.
- **Service container:** A minimal `Container` providing singletons for Config, DB, Logger, registries.
- **Storage:** Custom tables (not CPT) for forms and entries, mirroring proven scalability, plus a
  meta table pattern. JSON columns hold the form schema and entry response.
- **REST-first:** The React admin talks exclusively to `activeforms/v1` REST routes (cookie + nonce auth,
  capability-checked `permission_callback`). No `admin-ajax.php` for the admin app.
- **Frontend submission:** Public form posts to a nonce-protected REST endpoint; progressive
  enhancement so forms work and validate server-side regardless of JS.

---

## 3. Centralized Configuration & Design System

### 3.1 PHP configuration (single source of truth)
- Constants defined in the main file: `ACTIVEFORMS_VERSION`, `ACTIVEFORMS_FILE`, `ACTIVEFORMS_PATH`,
  `ACTIVEFORMS_URL`, `ACTIVEFORMS_BASENAME`, `ACTIVEFORMS_MIN_PHP`, `ACTIVEFORMS_MIN_WP`.
- `ActiveForms\Core\Config` exposes: text domain, REST namespace, capability map, option key names,
  DB table names, asset handles, and the design-token palette.
- All option keys namespaced `activeforms_*`; all transients `activeforms_*`; all hooks `activeforms/*`.

### 3.2 PHP→React bridge
- `wp_localize_script` (or `wp_add_inline_script` with a JSON blob) publishes `window.ActiveFormsConfig`
  containing: `restUrl`, `restNamespace`, `nonce`, `adminUrl`, `assetsUrl`, `capabilities`,
  `version`, `currencies`, `dateFormats`, `designTokens`, and a `strings` dictionary for i18n.
- React reads this object once at boot; nothing about branding/prefix is hard-coded in JS.

### 3.3 Design tokens (theme system)
- A single `tokens` object (colors, typography scale, spacing scale, radii, shadows, z-index layers,
  breakpoints, motion/easing durations) is defined in PHP **and** emitted as CSS custom properties
  (`--ef-color-primary`, `--ef-space-3`, `--ef-radius-md`, …).
- Every React component consumes tokens via CSS variables — no hard-coded hex/px.
- **Light & dark mode** via a `data-theme` attribute toggling the variable set.
- Rebranding/white-label = edit the token object + config in one place.
- Consistent styling across admin pages, modals, tables, drawers, notices, and the builder.
- Responsive, accessible (WCAG-minded contrast, focus rings, ARIA), RTL-aware.

---

## 4. Form Field Library

ActiveForms ships every field type below. **Free** fields are in the core plugin; **Pro** fields are
registered by ActiveForms Pro through the same `FieldRegistry` API. Each field declares: editor
settings schema, frontend render template, validation rules, sanitization callback, conditional-logic
eligibility, and reporting eligibility.

### 4.1 General fields (Free)
| Field | Internal type | Notes |
| --- | --- | --- |
| Single Line Text | `text` | Min/max length, placeholder, mask-ready |
| Paragraph / Textarea | `textarea` | Rows, max length, character counter |
| Name | `name` | Composite: prefix, first, middle, last (toggle sub-fields) |
| Email | `email` | Format validation, uniqueness option |
| Number | `number` | Min/max, step, numeric validation |
| Mask Input | `masked_text` | Pattern masking (phone, SSN, custom) |
| Website / URL | `url` | URL validation |
| Password | `password` | Strength + confirm option |
| Hidden | `hidden` | Default value / smart code population |
| Dropdown / Select | `select` | Searchable, multi-select option |
| Radio | `radio` | Inline/stacked, "Other" option |
| Checkbox (multi) | `checkbox` | Min/max selections, "Other" option |
| Multiple Choice Grid | `grid` | Matrix of rows × columns (single/multi) |
| Country List | `country` | 200+ ISO countries, prioritized list |
| Date / Time | `date_time` | Date, time, or both; format, range |
| Address | `address` | Street, line 2, city, state, zip, country |
| Terms & Conditions | `terms` | Required checkbox + HTML content |
| GDPR Agreement | `gdpr` | Consent checkbox with custom text |

### 4.2 Layout & content (Free)
| Field | Internal type | Notes |
| --- | --- | --- |
| Section Break | `section` | Heading + description divider |
| Custom HTML | `html` | Raw HTML/content block |
| Columns Container | `container` | 1–6 column responsive layouts |
| Submit Button | `submit` | Label, alignment, processing text |

### 4.3 Advanced fields (Pro)
| Field | Internal type | Notes |
| --- | --- | --- |
| Phone / Mobile | `phone` | Intl. dial-code selector + validation |
| File Upload | `file_upload` | Multiple, type/size limits, drag-drop |
| Image Upload | `image_upload` | Preview + crop |
| Rich Text | `rich_text` | WYSIWYG editor field |
| Color Picker | `color` | Hex/RGB picker |
| Range Slider | `range` | Min/max/step slider |
| Star Rating | `rating` | Configurable icon & scale |
| Net Promoter Score | `nps` | 0–10 promoter scale |
| Ranking | `ranking` | Drag-to-rank list/grid |
| Repeater | `repeater` | Repeatable field groups (table rows) |
| Chained Select | `chained_select` | Dependent dropdowns from CSV/source |
| Dynamic Field | `dynamic` | Options from posts/users/terms/CSV |
| Post/Taxonomy Selectors | `post_select`, `tax_select` | For post-creation forms |
| Featured Image | `featured_image` | Upload + crop for post forms |
| Signature | `signature` | Canvas signature pad |
| Action Hook | `action_hook` | Fire custom `do_action` mid-form |
| Form Step | `step` | Multi-step/wizard divider |
| Save & Resume Button | `save_resume` | Persist draft, resume by link |
| Shortcode | `shortcode` | Embed shortcode output |

### 4.4 Payment fields (Free core + Pro gateways)
| Field | Internal type | Notes |
| --- | --- | --- |
| Payment Item | `payment_item` | Products with single/multiple/quantity pricing |
| Custom Amount | `custom_amount` | User-entered amount, min/max |
| Item Quantity | `quantity` | Quantity selector tied to items |
| Subscription | `subscription` | Recurring plans, intervals, signup fee |
| Payment Method | `payment_method` | Gateway selector |
| Payment Summary | `payment_summary` | Itemized running total |
| Coupon | `coupon` | Discount code input (Pro) |

---

## 5. Form Builder (React) — UX Requirements

- **Drag-and-drop canvas**: drag fields from a categorized panel onto the canvas; reorder by drag;
  drop into column containers; keyboard-accessible alternatives.
- **Live preview** that matches the frontend renderer exactly (shared `render/` components).
- **Per-field settings drawer**: label, placeholder, help text, required toggle, default value,
  validation rules, CSS class, conditional logic, and field-specific options.
- **Three-pane layout**: Field Library (left) · Canvas (center) · Settings (right) — matching modern
  builder UX from the reference screenshots.
- **Undo / redo** history stack; **autosave** with dirty-state indicator; manual save.
- **Duplicate / delete** field (incl. keyboard shortcut); multi-column drag.
- **Responsive preview** toggle (desktop / tablet / mobile).
- **Conditional logic builder**: AND/OR groups, operators (equals, not equals, >, <, contains,
  starts/ends with, in, not in), show/hide actions per field, step, and confirmation.
- **Templates gallery**: 25+ prebuilt forms (contact, support, registration, survey, quiz, RFQ,
  application, feedback, NPS, payment/order, etc.) + "blank" + "AI generate" entry point.
- **Field search/filter** within the library; collapsible advanced sections.
- **Form settings tabs** in builder: General, Confirmations, Notifications, Integrations, Style,
  Advanced (custom CSS/JS), Restrictions/Scheduling.

---

## 6. Form Management

- Create / rename / duplicate / trash / delete forms.
- Forms list with search, sort (date, title, entries), status filter, bulk actions.
- **Import / Export** forms as JSON (schema + settings + meta).
- **Duplication** copies fields, settings, notifications, confirmations, integrations config.
- **Migrators** (import from other plugins): Contact Form 7, WPForms, Gravity Forms, Ninja Forms,
  Caldera Forms — registered via a `MigratorRegistry` (core ships CF7 + WPForms; others via Pro).
- **Form types**: classic, multi-step, conversational (one-question-at-a-time), and landing page.
- Per-form unique key + numeric ID; stable internal keys regardless of title changes.

---

## 7. Form Settings

### 7.1 Confirmations (success behavior)
- Show message (rich text) · Redirect to URL · Redirect to page · Show same-page replacement.
- **Conditional confirmations**: different outcomes based on submitted data (Pro-extensible).
- Smart-code support in messages and redirect URLs.

### 7.2 Notifications (email)
- Multiple notification rules per form (enable/disable each).
- Admin notification(s) + user confirmation email.
- Fields: To, CC, BCC, Reply-To, From name/email, Subject, Body (HTML), conditional send.
- Merge/smart codes for personalization; attach entry as PDF (Pro) or file uploads.
- Routing: dynamic recipients from form fields.
- HTML email templating with the design system; plain-text fallback.

### 7.3 Restrictions & scheduling
- Limit total submissions (count + period: day/week/month/total) with custom "limit reached" message.
- Schedule availability (start/end datetime) with pending & expired messages.
- Require login (+ message); restrict by user role (Pro).
- Restrict by country / IP (Pro, geo-based).
- One-entry-per-user / per-IP options.

### 7.4 Layout & advanced
- Label placement (top/right/left/hidden), help & error placement, asterisk position.
- Per-form custom CSS & JS editors.
- Default values & pre-population: URL params, user meta, cookies, smart codes.
- Honeypot + captcha toggles per form.

---

## 8. Entries / Submissions Management

- **Entries table** per form: columns, search, sort, status filter (unread/read/favorite/trash).
- **Entry detail** view with formatted field values, navigation (prev/next), and metadata
  (serial #, date, user, IP, country, browser/device, source URL, payment status).
- Status workflow: read/unread, favorite/star, trash/restore, permanent delete; bulk actions.
- **Notes**: internal notes per entry with author + timestamp.
- **Edit entry** (capability-gated) with type-aware sanitization.
- **Export**: CSV, Excel (XLSX), ODS, JSON — with field selection and filtering.
- **Import** entries from CSV with field mapping (Pro).
- **Resend notifications** for an entry; **API logs** per entry with retry.
- Pinned/important columns; remembered column preferences.

---

## 9. Spam Protection & Security

- **Honeypot** hidden field (always available, zero-config).
- **Google reCAPTCHA** v2 (checkbox/invisible) & v3 (score).
- **hCaptcha** and **Cloudflare Turnstile**.
- **Akismet** bridge; pluggable anti-spam provider interface (CleanTalk via Pro).
- Per-form override of the global captcha provider.
- Server-side validation & sanitization for every field type.
- Nonce-protected submission + REST; CSRF-safe; capability checks throughout admin/REST.
- Email/attachment path hardening; smart-code output escaping in notifications.

---

## 10. Payments

- **Core (Free):** Stripe (card), one-time & subscription, test/live mode, webhook verification,
  payment summary, quantity & multi-item pricing, currency handling.
- **Pro gateways:** PayPal (Standard + Commerce), Mollie, RazorPay, Square, Authorize.Net, Paddle,
  Paystack, and Offline/Manual.
- **Payment features:** coupons/discounts, tax, shipping address capture, global inventory/stock,
  subscriptions with signup fees & intervals, partial/draft payments, refund status, receipts,
  payment logs, subscription cancellation, AffiliateWP commission hooks (Pro).
- Transaction & subscription records linked to entries; `payment_status` on entries.
- Front-end smart codes: `{payment_total}`, `{transaction_id}`, `{payment_method}`, etc.

---

## 11. Integrations

Integrations are registered through an `IntegrationRegistry` with an `AbstractIntegration` contract
(settings schema, field mapping, conditional feed, async dispatch via the scheduled-actions queue).
Core ships a representative free set; ActiveForms Pro registers the full catalog.

**Free (core):** Mailchimp, Slack, Webhook, local CRM bridge, Mailpoet.

**Pro catalog (grouped):**
- *Email marketing:* ActiveCampaign, Campaign Monitor, ConvertKit, GetResponse, MailerLite, Drip,
  SendFox, iContact, Brevo (Sendinblue), Constant Contact, MooSend, CleverReach, Mailster, MailJet.
- *CRM & sales:* HubSpot, Salesforce, Pipedrive, Zoho CRM, amoCRM, OnePageCRM, Insightly,
  Salesflare, GetGist, Notion, Airtable.
- *Automation & webhooks:* Zapier, generic Webhook, Platform.ly, ClickSend.
- *Notifications:* Twilio SMS, Discord, Telegram, Slack (enhanced).
- *Data/storage:* Google Sheets, Notion databases, Trello.
- *WordPress:* User Registration, post/CPT creation & update, BuddyPress/BuddyBoss.
- *AI:* AI form & confirmation generation (ChatGPT-style provider).

Each integration supports per-form feeds, conditional logic on send, field mapping, and logging.

---

## 12. Reporting & Analytics

- **Dashboard overview**: total forms, total entries, entries over time, top forms, conversion rate.
- **Form views & conversion tracking** (views vs submissions).
- **Field-level reports**: distribution charts (pie/bar) for select/radio/checkbox/country/rating/NPS,
  with sub-field analysis; ranking results; quiz score distribution.
- **Payment reports**: revenue, transactions, subscription analytics.
- **Date-range filtering**, exportable charts.
- Built with a chart library in React; data served by `Reporting/` aggregators over the entries table.

---

## 13. Conversational & Quiz/Calculation Forms

- **Conversational mode**: one-question-per-screen, welcome & thank-you screens, progress bar,
  keyboard nav, theme/colors, shareable pretty URL, shortcode/block embed.
- **Quiz**: scored questions, pass/fail or personality results, result smart codes, per-option scoring.
- **Calculations**: numeric/price calculation fields with real-time totals (used by payments too).

---

## 14. Output Surfaces (Shortcodes, Block, Widgets)

- **Shortcode**: `[activeforms id="123"]`, `[activeforms id="123" type="conversational"]`,
  plus user payment views `[activeforms_payments]`.
- **Gutenberg block** (`activeforms/form`) with form picker + style controls (uses the design system).
- **Page builder widgets**: Elementor & Oxygen widgets registered conditionally.
- **Sidebar widget** (classic) + **admin dashboard widget** (quick stats & recent entries).

---

## 15. Developer & Extensibility Layer

- **REST API** (`activeforms/v1`): forms CRUD, entries CRUD + export, settings, builder schema,
  templates, integrations, reports. All routes have `permission_callback` and schema validation.
- **Hooks**: documented actions/filters — `activeforms/before_validation`,
  `activeforms/after_submission`, `activeforms/form_schema`, `activeforms/rendering_form`,
  `activeforms/notification_args`, `activeforms/register_fields`, `activeforms/register_integrations`,
  `activeforms/reportable_fields`, etc. Filters always return; actions documented with args.
- **Registries**: `FieldRegistry`, `IntegrationRegistry`, `MigratorRegistry`, `GatewayRegistry`,
  `SmartCodeRegistry` — the Pro plugin and third parties extend via these.
- **Smart codes**: `{field.key}`, `{user.*}`, `{post.*}`, `{get.*}`, `{cookie.*}`, `{date.*}`,
  `{site.url}`, `{admin.email}`, payment/quiz codes.
- **WP-CLI**: `wp activeforms` commands (export/import forms, prune entries, status).
- **i18n**: every user-facing string wrapped with text domain `activeforms`; React strings localized
  via the config `strings` dictionary; `/languages` domain path.

---

## 16. Lifecycle, Performance & Compliance

- **Activation**: create/upgrade custom tables via `dbDelta`, seed default options & capabilities,
  store DB version; no `flush_rewrite_rules` on `init`.
- **Deactivation**: clear scheduled events & transients only (non-destructive).
- **Uninstall** (`uninstall.php`, guarded by `WP_UNINSTALL_PLUGIN`): optionally drop tables, delete
  options/transients/user-meta, controlled by a "remove data on uninstall" setting.
- **Conditional asset loading**: admin bundle only on ActiveForms screens; frontend assets only on
  pages that actually render a form; small frontend CSS/JS footprint.
- **Caching-friendly**, async integration dispatch via a scheduled-actions table.
- **Accessibility**: ARIA roles, labels, focus management, keyboard nav, contrast-checked tokens.
- **WordPress.org compliance**: ABSPATH guards, prefixing/namespacing, escaping/sanitization, nonces,
  GPLv2+, readme.txt, no remote code execution, sandboxed assets, no `eval`/obfuscation.

---

## 17. Database Schema (Original)

| Table | Purpose | Key columns |
| --- | --- | --- |
| `{prefix}activeforms_forms` | Form definitions | `id`, `title`, `slug`, `status`, `type`, `fields` (JSON), `settings` (JSON), `has_payment`, `created_by`, `created_at`, `updated_at` |
| `{prefix}activeforms_form_meta` | Per-form metadata (notifications, integrations, style) | `id`, `form_id`, `meta_key`, `meta_value` (LONGTEXT), index on (`form_id`,`meta_key`) |
| `{prefix}activeforms_entries` | Submissions | `id`, `form_id`, `serial`, `response` (JSON), `status`, `is_favorite`, `user_id`, `source_url`, `ip`, `country`, `browser`, `device`, `payment_status`, `payment_total`, `currency`, `created_at`, `updated_at` |
| `{prefix}activeforms_entry_meta` | Per-entry metadata (notes, integration logs, payment refs) | `id`, `entry_id`, `form_id`, `meta_key`, `meta_value`, `created_at` |
| `{prefix}activeforms_entry_details` | Flattened field values for fast reporting/search | `id`, `form_id`, `entry_id`, `field_key`, `sub_field`, `field_value` (LONGTEXT) |
| `{prefix}activeforms_logs` | API/integration & system logs | `id`, `form_id`, `entry_id`, `component`, `status`, `title`, `message`, `created_at` |
| `{prefix}activeforms_scheduled_actions` | Async/integration queue | `id`, `action`, `payload` (JSON), `status`, `scheduled_at`, `attempts` |

All tables prefixed `activeforms_`, created with `dbDelta()`, `$wpdb->prefix`, and
`$wpdb->get_charset_collate()`. Internal keys remain stable across rebrands.

---

## 18. Free vs Pro Matrix (summary)

| Capability | Free | Pro |
| --- | --- | --- |
| Core fields, layout, containers | ✅ | ✅ |
| Drag-and-drop builder, templates, conditional logic | ✅ | ✅ |
| Entries, export (CSV/Excel/ODS/JSON) | ✅ | ✅ |
| Honeypot, reCAPTCHA, hCaptcha, Turnstile, Akismet | ✅ | ✅ |
| Conversational forms | ✅ | ✅ |
| Stripe payments | ✅ (basic) | ✅ (full + more gateways) |
| Advanced fields (repeater, file upload, signature, NPS, etc.) | — | ✅ |
| Full integration catalog (40+) | partial | ✅ |
| Multi-step, save & resume, quiz/calculations | partial | ✅ |
| Post/User creation, PDF, advanced reports | — | ✅ |
| Entry import, scheduled approval, role restrictions | — | ✅ |

---

## 19. Milestone Roadmap

1. **M1 – Foundation:** config/design system, autoloader, DB schema, lifecycle, REST scaffolding.
2. **M2 – Builder MVP:** field registry (core fields), React builder, save/load, frontend render.
3. **M3 – Submissions:** validation, sanitization, storage, entries UI, email notifications, spam.
4. **M4 – Settings & confirmations:** restrictions, scheduling, confirmations, smart codes.
5. **M5 – Reporting & exports:** analytics dashboard, field reports, CSV/Excel/ODS/JSON export.
6. **M6 – Integrations & payments (free set):** registry, Mailchimp/Slack/Webhook, Stripe.
7. **M7 – Surfaces:** Gutenberg block, widgets, conversational mode, templates gallery.
8. **M8 – Pro hooks:** registries/extension points finalized for ActiveForms Pro.

---

*This specification is the contract for ActiveForms development. Implementation follows WordPress
coding standards and the WordPress.org Plugin Directory guidelines, with an original codebase that
shares no source with Fluent Forms.*
