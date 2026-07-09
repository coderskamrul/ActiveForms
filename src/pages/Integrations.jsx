/**
 * Integrations: list available providers and configure global credentials.
 */
import React, { useEffect, useState } from 'react';
import api from '../api/client';
import { Loading, Card, PageHead, Button, Field } from '../components/ui';
import { useToast } from '../components/Toast';

const CAT_LABELS = {
  email_marketing: 'Email Marketing',
  crm: 'CRM',
  automation: 'Automation',
  notification: 'Notifications',
  storage: 'Storage',
};

/** Integration config modal. */
function ConfigModal({ integration, onClose, onSaved }) {
  const [values, setValues] = useState({});
  const [saving, setSaving] = useState(false);
  const { notify } = useToast();

  const save = async () => {
    setSaving(true);
    try {
      const res = await api.put(`/integrations/${integration.slug}`, { settings: values });
      notify(`${integration.title} saved`);
      onSaved(res);
    } catch (e) { notify(e.message, 'error'); } finally { setSaving(false); }
  };

  return (
    <div style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.45)', zIndex: 1200, display: 'grid', placeItems: 'center' }} onClick={onClose}>
      <div className="activeforms-card" style={{ width: 460, maxWidth: '92vw' }} onClick={(e) => e.stopPropagation()}>
        <div className="activeforms-card-pad">
          <h2 style={{ marginTop: 0 }}>{integration.title}</h2>
          {(integration.globalFields || []).length === 0 && (
            <p style={{ color: 'var(--_muted)' }}>This integration is configured per-form (no global credentials needed).</p>
          )}
          {(integration.globalFields || []).map((f) => (
            <Field key={f.key} label={f.label}>
              <input
                className="activeforms-input"
                type={f.type === 'password' ? 'password' : 'text'}
                value={values[f.key] || ''}
                onChange={(e) => setValues({ ...values, [f.key]: e.target.value })}
              />
            </Field>
          ))}
          <div style={{ display: 'flex', gap: 8, justifyContent: 'flex-end', marginTop: 12 }}>
            <Button onClick={onClose}>Cancel</Button>
            <Button variant="primary" onClick={save} disabled={saving}>{saving ? 'Saving…' : 'Save'}</Button>
          </div>
        </div>
      </div>
    </div>
  );
}

/** Integrations page. */
export default function Integrations() {
  const [list, setList] = useState(null);
  const [active, setActive] = useState(null);

  const load = () => api.get('/integrations').then(setList).catch(() => setList([]));
  useEffect(() => { load(); }, []);

  if (list === null) return <Loading />;

  const grouped = {};
  list.forEach((i) => { (grouped[i.category] = grouped[i.category] || []).push(i); });

  return (
    <div>
      <PageHead title="Integrations" subtitle="Connect ActiveForms to your favorite tools" />
      {Object.entries(grouped).map(([cat, items]) => (
        <div key={cat} style={{ marginBottom: 22 }}>
          <h3 style={{ marginBottom: 10 }}>{CAT_LABELS[cat] || cat}</h3>
          <div className="activeforms-grid activeforms-grid-4">
            {items.map((i) => (
              <Card key={i.slug} pad={false}>
                <div className="activeforms-card-pad">
                  <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
                    <strong>{i.title}</strong>
                    {i.configured && <span className="activeforms-badge activeforms-badge--published">Ready</span>}
                  </div>
                  <div style={{ marginTop: 12 }}>
                    <Button size="sm" onClick={() => setActive(i)}>Configure</Button>
                  </div>
                </div>
              </Card>
            ))}
          </div>
        </div>
      ))}

      {active && <ConfigModal integration={active} onClose={() => setActive(null)} onSaved={() => { setActive(null); load(); }} />}
    </div>
  );
}
