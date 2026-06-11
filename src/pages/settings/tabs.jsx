/**
 * Settings tab registry + per-tab content.
 *
 * TABS drives the left navigation rail (grouped, ordered, optionally badged).
 * TAB_CONTENT maps each tab key to a component that receives the live settings
 * object plus typed setters. Functional controls are bound to state; controls
 * for unshipped features carry a SOON/PRO badge and are rendered disabled, so
 * the final IA never has to be reshuffled when those features land — the slot
 * is already where it belongs.
 */
import React from 'react';
import { Toggle, Text } from '../../components/ui';
import { Section, Row, Note, Segmented, Select, SOON, PRO } from './parts.jsx';

/**
 * Navigation rail definition. `functional: true` means the tab persists real
 * settings (and so shows a Save button); the rest are roadmap previews.
 */
export const TABS = [
  { key: 'general', label: 'General', icon: 'admin-settings', group: 'Form Defaults', functional: true },
  { key: 'validation', label: 'Validation Messages', icon: 'editor-spellcheck', group: 'Form Defaults', functional: true },
  { key: 'spam', label: 'Spam & Security', icon: 'shield-alt', group: 'Security', functional: true },
  { key: 'privacy', label: 'Privacy & Data', icon: 'privacy', group: 'Security', functional: true },
  { key: 'email', label: 'Email Notifications', icon: 'email-alt', group: 'Communication', badge: SOON },
  { key: 'payments', label: 'Payments', icon: 'cart', group: 'Access & Billing', badge: SOON },
  { key: 'permissions', label: 'Permissions', icon: 'groups', group: 'Access & Billing', badge: SOON },
  { key: 'license', label: 'License & Add-ons', icon: 'admin-network', group: 'Access & Billing', badge: PRO },
];

/** Order in which rail groups are rendered. */
export const GROUP_ORDER = ['Form Defaults', 'Security', 'Communication', 'Access & Billing'];

const PLACEMENT_OPTS = [
  { value: 'top', label: 'Top aligned' },
  { value: 'left', label: 'Left aligned' },
  { value: 'right', label: 'Right aligned' },
  { value: 'bottom', label: 'Bottom aligned' },
  { value: 'hide', label: 'Hidden' },
];

/* ----------------------------------------------------------------- General */

function GeneralTab({ settings, setField }) {
  return (
    <>
      <Section title="Global Layout" description="Defaults applied to every form field. Individual fields can override these in the builder.">
        <Row title="Label Placement" description="Where a field's label sits relative to its input.">
          <Select value={settings.label_placement || 'top'} onChange={(v) => setField('label_placement', v)} options={PLACEMENT_OPTS} />
        </Row>
        <Row title="Help Message Placement" badge={SOON} disabled description="Position of the help text shown beneath a field.">
          <Select value="below" disabled options={[{ value: 'below', label: 'Below input element' }]} />
        </Row>
        <Row title="Error Message Placement" badge={SOON} disabled description="Where inline validation errors appear.">
          <Select value="below" disabled options={[{ value: 'below', label: 'Below input element' }]} />
        </Row>
      </Section>

      <Section title="Form Behaviour" description="Editor and submission defaults for new forms.">
        <Row title="Form Editor Autosave" badge={SOON} disabled toggle description="Automatically save the builder while you work.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
        <Row title="No-Conflict Mode" badge={SOON} disabled toggle description="Unload third-party scripts on EasyForms admin screens to prevent conflicts.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
      </Section>
    </>
  );
}

/* ------------------------------------------------------- Validation messages */

function ValidationTab({ settings, setMessage }) {
  const m = settings.messages || {};
  return (
    <Section
      title="Default Validation Messages"
      description="Used as the fallback message for every form. Leave a field blank to use the built-in default. Per-field overrides set in the builder always win."
    >
      <Row title="Required Field" stacked description="Shown when a required field is left empty.">
        <Text value={m.required} onChange={(v) => setMessage('required', v)} placeholder="This field is required." />
      </Row>
      <Row title="Invalid Email" stacked description="Shown when an email field contains an invalid address.">
        <Text value={m.invalid_email} onChange={(v) => setMessage('invalid_email', v)} placeholder="Please enter a valid email address." />
      </Row>
      <Row title="Invalid URL" stacked description="Shown when a URL field contains an invalid address.">
        <Text value={m.invalid_url} onChange={(v) => setMessage('invalid_url', v)} placeholder="Please enter a valid URL." />
      </Row>
      <Row title="Invalid Number" stacked description="Shown when a number field contains a non-numeric value.">
        <Text value={m.invalid_number} onChange={(v) => setMessage('invalid_number', v)} placeholder="Please enter a valid number." />
      </Row>

      <Row title="Minimum / Maximum Value" badge={SOON} stacked disabled description="Range validation messages for number fields.">
        <Text value="" disabled placeholder="Value is out of the allowed range." />
      </Row>
      <Row title="Allowed File Types" badge={SOON} stacked disabled description="Shown when an uploaded file type is not permitted.">
        <Text value="" disabled placeholder="This file type is not allowed." />
      </Row>
      <Row title="Valid Phone Number" badge={SOON} stacked disabled description="Shown when a phone field fails validation.">
        <Text value="" disabled placeholder="Phone number is not valid." />
      </Row>
    </Section>
  );
}

/* ----------------------------------------------------------- Spam & Security */

const PROVIDER_OPTS = [
  { value: '', label: 'None (honeypot only)' },
  { value: 'recaptcha', label: 'Google reCAPTCHA' },
  { value: 'hcaptcha', label: 'hCaptcha' },
  { value: 'turnstile', label: 'Cloudflare Turnstile' },
];

function SpamTab({ settings, setRecaptcha }) {
  const r = settings.recaptcha || {};
  return (
    <>
      <Section title="Honeypot" description="A hidden field that traps automated bots. Enabled on every form and requires no configuration.">
        <Note icon="shield">Honeypot protection is always active and adds zero friction for real visitors.</Note>
      </Section>

      <Section title="CAPTCHA" description="Add a challenge from a third-party provider for an extra layer of spam protection.">
        <Row title="Provider" description="Choose a CAPTCHA service, or keep honeypot-only protection.">
          <Select value={r.provider || ''} onChange={(v) => setRecaptcha('provider', v)} options={PROVIDER_OPTS} />
        </Row>
        {r.provider && (
          <>
            <Row title="Site Key" stacked description="The public site key from your provider dashboard.">
              <Text value={r.site_key} onChange={(v) => setRecaptcha('site_key', v)} placeholder="Site key" />
            </Row>
            <Row title="Secret Key" stacked description="The private key used for server-side verification. Stored securely.">
              <input className="easyforms-input" type="password" value={r.secret_key || ''} onChange={(e) => setRecaptcha('secret_key', e.target.value)} placeholder="Secret key" />
            </Row>
          </>
        )}
      </Section>

      <Section title="Advanced Protection">
        <Row title="Token-Based Spam Protection" badge={SOON} disabled toggle description="Sign each form render with a one-time token to block replayed submissions.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
        <Row title="Akismet Integration" badge={SOON} disabled toggle description="Run submissions through Akismet's spam-detection network.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
      </Section>
    </>
  );
}

/* ------------------------------------------------------------ Privacy & Data */

function PrivacyTab({ settings, setField }) {
  return (
    <>
      <Section title="Data Collection" description="Control what EasyForms stores alongside each submission.">
        <Row title="Disable IP Logging" badge={SOON} disabled toggle description="Stop recording the submitter's IP address with each entry.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
        <Row title="Disable Form Analytics" badge={SOON} disabled toggle description="Stop tracking views and conversion rates for forms.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
        <Row title="Entry Auto-Delete" badge={SOON} disabled description="Automatically purge entries older than a set period.">
          <Select value="never" disabled options={[{ value: 'never', label: 'Never' }]} />
        </Row>
      </Section>

      <Section title="Data Cleanup" description="What happens to your forms and entries when EasyForms is removed.">
        <Note icon="warning">Deleting all data is permanent. Forms, entries, and settings cannot be recovered after uninstall.</Note>
        <Row title="Remove All Data on Uninstall" toggle description="Drop all EasyForms tables and options when the plugin is deleted.">
          <Toggle checked={!!settings.remove_data_on_uninstall} onChange={(v) => setField('remove_data_on_uninstall', v)} />
        </Row>
      </Section>
    </>
  );
}

/* ----------------------------------------------------------- Roadmap previews */

function EmailTab() {
  return (
    <>
      <Section title="Email Summaries" badge={SOON} locked description="Receive a recurring report showing how your forms are performing.">
        <Row title="Enable Email Summaries" badge={SOON} disabled toggle description="Send a performance digest to the chosen address.">
          <Toggle checked={false} onChange={() => {}} />
        </Row>
        <Row title="Send To" badge={SOON} disabled description="Where the summary is delivered.">
          <Segmented value="admin" disabled options={[{ value: 'admin', label: 'Site Admin' }, { value: 'custom', label: 'Custom Email' }]} />
        </Row>
        <Row title="Frequency" badge={SOON} disabled description="How often the summary is sent.">
          <Select value="weekly" disabled options={[{ value: 'weekly', label: 'Weekly (Monday)' }]} />
        </Row>
      </Section>

      <Section title="Integration Failure Notification" badge={SOON} locked description="Get notified by email whenever a connected integration fails to run.">
        <Row title="Enable Failure Notifications" badge={SOON} disabled toggle>
          <Toggle checked={false} onChange={() => {}} />
        </Row>
      </Section>

      <Section title="Email Appearance" badge={SOON} locked description="Branding applied to outgoing notification emails.">
        <Row title="Email Footer Text" badge={SOON} stacked disabled description="Appended to the bottom of every notification email.">
          <textarea className="easyforms-textarea" rows={3} disabled placeholder="Powered by EasyForms" />
        </Row>
      </Section>
    </>
  );
}

function PaymentsTab() {
  return (
    <Section title="Payment Settings" badge={SOON} locked description="Collect payments directly through your forms. This area unlocks once the Payments module ships.">
      <Note icon="cart">Stripe and PayPal gateways, currency selection, and per-form pricing are on the EasyForms roadmap.</Note>
      <Row title="Default Currency" badge={SOON} disabled description="Currency used for new payment fields.">
        <Select value="usd" disabled options={[{ value: 'usd', label: 'USD — US Dollar' }]} />
      </Row>
      <Row title="Test Mode" badge={SOON} disabled toggle description="Process payments against gateway sandboxes.">
        <Toggle checked={false} onChange={() => {}} />
      </Row>
    </Section>
  );
}

function PermissionsTab() {
  return (
    <Section title="Permissions" badge={SOON} locked description="Choose which user roles can manage forms, view entries, and change settings.">
      <Note icon="groups">Granular role-based access control is coming. For now, EasyForms is available to administrators.</Note>
      <Row title="Manage Forms" badge={SOON} disabled description="Roles allowed to create and edit forms.">
        <Select value="admin" disabled options={[{ value: 'admin', label: 'Administrator' }]} />
      </Row>
      <Row title="View Entries" badge={SOON} disabled description="Roles allowed to read submitted entries.">
        <Select value="admin" disabled options={[{ value: 'admin', label: 'Administrator' }]} />
      </Row>
    </Section>
  );
}

function LicenseTab() {
  return (
    <Section title="License & Add-ons" badge={PRO} locked description="Activate EasyForms Pro to unlock premium fields, integrations, and priority support.">
      <Note icon="admin-network">Enter your license key here once EasyForms Pro is installed to receive automatic updates.</Note>
      <Row title="License Key" badge={PRO} stacked disabled description="Found in your WPDeveloper account dashboard.">
        <Text value="" disabled placeholder="XXXX-XXXX-XXXX-XXXX" />
      </Row>
    </Section>
  );
}

/** Tab key → content component. */
export const TAB_CONTENT = {
  general: GeneralTab,
  validation: ValidationTab,
  spam: SpamTab,
  privacy: PrivacyTab,
  email: EmailTab,
  payments: PaymentsTab,
  permissions: PermissionsTab,
  license: LicenseTab,
};
