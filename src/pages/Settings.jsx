/**
 * Global settings: label placement, spam/captcha provider, data cleanup.
 */
import React, { useEffect, useState } from 'react';
import api from '../api/client';
import { Loading, Card, PageHead, Button, Field, Toggle } from '../components/ui';
import { useToast } from '../components/Toast';

/** Settings page. */
export default function Settings() {
  const [settings, setSettings] = useState(null);
  const [saving, setSaving] = useState(false);
  const { notify } = useToast();

  useEffect(() => { api.get('/settings').then(setSettings).catch(() => setSettings({})); }, []);

  if (settings === null) return <Loading />;

  const recaptcha = settings.recaptcha || {};
  const setRecaptcha = (key, value) => setSettings({ ...settings, recaptcha: { ...recaptcha, [key]: value } });

  const save = async () => {
    setSaving(true);
    try {
      const next = await api.put('/settings', settings);
      setSettings(next);
      notify('Settings saved');
    } catch (e) { notify(e.message, 'error'); } finally { setSaving(false); }
  };

  return (
    <div>
      <PageHead title="Settings" actions={<Button variant="primary" onClick={save} disabled={saving}>{saving ? 'Saving…' : 'Save Settings'}</Button>} />

      <div className="easyforms-grid easyforms-grid-2">
        <Card>
          <h3 style={{ marginTop: 0 }}>General</h3>
          <Field label="Label placement">
            <select className="easyforms-select" value={settings.label_placement || 'top'} onChange={(e) => setSettings({ ...settings, label_placement: e.target.value })}>
              <option value="top">Top</option>
              <option value="left">Left</option>
              <option value="hidden">Hidden</option>
            </select>
          </Field>
          <div className="easyforms-option-row">
            <label style={{ margin: 0 }}>Delete all data on uninstall</label>
            <Toggle checked={settings.remove_data_on_uninstall} onChange={(v) => setSettings({ ...settings, remove_data_on_uninstall: v })} />
          </div>
        </Card>

        <Card>
          <h3 style={{ marginTop: 0 }}>Spam Protection / CAPTCHA</h3>
          <Field label="Provider" help="Honeypot is always enabled. Choose an additional CAPTCHA provider.">
            <select className="easyforms-select" value={recaptcha.provider || ''} onChange={(e) => setRecaptcha('provider', e.target.value)}>
              <option value="">None (honeypot only)</option>
              <option value="recaptcha">Google reCAPTCHA</option>
              <option value="hcaptcha">hCaptcha</option>
              <option value="turnstile">Cloudflare Turnstile</option>
            </select>
          </Field>
          {recaptcha.provider && (
            <>
              <Field label="Site Key"><input className="easyforms-input" value={recaptcha.site_key || ''} onChange={(e) => setRecaptcha('site_key', e.target.value)} /></Field>
              <Field label="Secret Key"><input className="easyforms-input" type="password" value={recaptcha.secret_key || ''} onChange={(e) => setRecaptcha('secret_key', e.target.value)} /></Field>
            </>
          )}
        </Card>
      </div>
    </div>
  );
}
