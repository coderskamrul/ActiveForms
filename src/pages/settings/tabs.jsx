/**
 * Settings tab registry + per-tab content.
 *
 * TABS drives the left navigation rail (grouped, ordered). TAB_CONTENT maps
 * each tab key to a component that receives the live settings object plus typed
 * setters. Every control is bound to state and persists real settings.
 */
import React from 'react';
import { Toggle, Text } from '../../components/ui';
import { Section, Row, Note, Select } from './parts.jsx';

/**
 * Navigation rail definition. `functional: true` means the tab persists real
 * settings (and so shows a Save button).
 */
export const TABS = [
  { key: 'general', label: 'General', icon: 'admin-settings', group: 'Form Defaults', functional: true },
  { key: 'validation', label: 'Validation Messages', icon: 'editor-spellcheck', group: 'Form Defaults', functional: true },
];

/** Order in which rail groups are rendered. */
export const GROUP_ORDER = ['Form Defaults', 'Security'];

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
    <Section title="Global Layout" description="Defaults applied to every form field. Individual fields can override these in the builder.">
      <Row title="Label Placement" description="Where a field's label sits relative to its input.">
        <Select value={settings.label_placement || 'top'} onChange={(v) => setField('label_placement', v)} options={PLACEMENT_OPTS} />
      </Row>
    </Section>
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
              <input className="radiusforms-input" type="password" value={r.secret_key || ''} onChange={(e) => setRecaptcha('secret_key', e.target.value)} placeholder="Secret key" />
            </Row>
          </>
        )}
      </Section>
    </>
  );
}

/* ------------------------------------------------------------ Privacy & Data */

function PrivacyTab({ settings, setField }) {
  return (
    <Section title="Data Cleanup" description="What happens to your forms and entries when RadiusForms is removed.">
      <Note icon="warning">Deleting all data is permanent. Forms, entries, and settings cannot be recovered after uninstall.</Note>
      <Row title="Remove All Data on Uninstall" toggle description="Drop all RadiusForms tables and options when the plugin is deleted.">
        <Toggle checked={!!settings.remove_data_on_uninstall} onChange={(v) => setField('remove_data_on_uninstall', v)} />
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
};
