/**
 * Settings — a vertical-rail, tab-based settings experience.
 *
 * The rail (TABS) is grouped and scalable: adding a feature area is a one-line
 * entry plus a content component in ./settings/tabs.jsx. The active tab is
 * driven by the hash (#/settings/<tab>) so tabs are deep-linkable and the
 * browser back button works. Each tab persists real settings and surfaces a
 * Save button.
 */
import React, { useEffect, useState } from 'react';
import api from '../api/client';
import { Loading, PageHead, Button } from '../components/ui';
import { useToast } from '../components/Toast';
import { useRouter } from '../router';
import { TABS, GROUP_ORDER, TAB_CONTENT } from './settings/tabs.jsx';

const DEFAULT_TAB = 'general';

/** Resolve a valid tab key from the route, falling back to the default. */
function tabFromRoute(route) {
  const key = route.parts[1];
  return TABS.some((t) => t.key === key) ? key : DEFAULT_TAB;
}

/** Settings page. */
export default function Settings() {
  const { route, navigate } = useRouter();
  const [settings, setSettings] = useState(null);
  const [saving, setSaving] = useState(false);
  const { notify } = useToast();

  useEffect(() => { api.get('/settings').then(setSettings).catch(() => setSettings({})); }, []);

  const activeKey = tabFromRoute(route);
  const activeTab = TABS.find((t) => t.key === activeKey) || TABS[0];

  if (settings === null) return <Loading />;

  // Typed setters passed to every tab — keeps tab components free of state plumbing.
  const setField = (key, value) => setSettings((s) => ({ ...s, [key]: value }));
  const setMessage = (key, value) => setSettings((s) => ({ ...s, messages: { ...(s.messages || {}), [key]: value } }));
  const setRecaptcha = (key, value) => setSettings((s) => ({ ...s, recaptcha: { ...(s.recaptcha || {}), [key]: value } }));

  const save = async () => {
    setSaving(true);
    try {
      const next = await api.put('/settings', settings);
      setSettings(next);
      notify('Settings saved');
    } catch (e) { notify(e.message, 'error'); } finally { setSaving(false); }
  };

  const Content = TAB_CONTENT[activeKey];

  return (
    <div>
      <PageHead title="Settings" subtitle="Configure global defaults, protection, and account preferences for RadiusForms." />

      <div className="radiusforms-settings">
        <nav className="radiusforms-settings__rail" aria-label="Settings sections">
          {GROUP_ORDER.map((group) => {
            const items = TABS.filter((t) => t.group === group);
            if (!items.length) return null;
            return (
              <div className="radiusforms-settings__group" key={group}>
                <span className="radiusforms-settings__group-label">{group}</span>
                {items.map((t) => (
                  <a
                    key={t.key}
                    href={`#/settings/${t.key}`}
                    className={`radiusforms-settings__navitem${t.key === activeKey ? ' is-active' : ''}`}
                  >
                    <span className={`dashicons dashicons-${t.icon}`} aria-hidden="true" />
                    <span className="radiusforms-settings__navitem-label">{t.label}</span>
                  </a>
                ))}
              </div>
            );
          })}
        </nav>

        <div className="radiusforms-settings__panel">
          <div className="radiusforms-settings__panel-head">
            <div>
              <h2>{activeTab.label}</h2>
            </div>
            <Button variant="primary" onClick={save} disabled={saving}>{saving ? 'Saving…' : 'Save Changes'}</Button>
          </div>

          <div className="radiusforms-settings__sections">
            <Content
              settings={settings}
              setField={setField}
              setMessage={setMessage}
              setRecaptcha={setRecaptcha}
            />
          </div>
        </div>
      </div>
    </div>
  );
}
