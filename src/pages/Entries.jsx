/**
 * Entries viewer. Without a form id, shows a form picker; with one, lists and
 * inspects submissions with status filters and CSV export.
 */
import React, { useEffect, useState, useCallback } from 'react';
import api from '../api/client';
import { Loading, Empty, Button, Card, PageHead } from '../components/ui';
import { useToast } from '../components/Toast';

const FILTERS = [
  { key: '', label: 'All' },
  { key: 'unread', label: 'Unread' },
  { key: 'read', label: 'Read' },
  { key: 'favorites', label: 'Favorites' },
  { key: 'trashed', label: 'Trash' },
];

/** Form picker when no form is selected. */
function FormPicker() {
  const [forms, setForms] = useState(null);
  useEffect(() => { api.get('/forms').then((r) => setForms(r.items || [])).catch(() => setForms([])); }, []);
  if (forms === null) return <Loading />;
  return (
    <div>
      <PageHead title="Entries" subtitle="Select a form to view its submissions" />
      {forms.length === 0 ? <Card><Empty icon="✉" title="No forms yet" /></Card> : (
        <Card pad={false}>
          <table className="easyforms-table">
            <thead><tr><th>Form</th><th>Entries</th><th></th></tr></thead>
            <tbody>
              {forms.map((f) => (
                <tr key={f.id}>
                  <td style={{ fontWeight: 600 }}>{f.title}</td>
                  <td>{f.entries ?? 0}</td>
                  <td><a className="easyforms-btn easyforms-btn--sm" href={`#/forms/${f.id}/entries`}>View</a></td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  );
}

/** Entry detail drawer. */
function EntryDetail({ entry, fields, onClose, onToggleFav }) {
  return (
    <div style={{ position: 'fixed', inset: 0, background: 'rgba(15,23,42,.45)', zIndex: 1100, display: 'flex', justifyContent: 'flex-end' }} onClick={onClose}>
      <div className="easyforms-card" style={{ width: 460, maxWidth: '92vw', height: '100%', borderRadius: 0, overflow: 'auto' }} onClick={(e) => e.stopPropagation()}>
        <div className="easyforms-card-pad">
          <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center' }}>
            <h2 style={{ margin: 0 }}>Entry #{entry.serial}</h2>
            <Button onClick={onClose}>Close</Button>
          </div>
          <div style={{ color: 'var(--_muted)', fontSize: 12, margin: '4px 0 16px' }}>{entry.created_at} · {entry.ip}</div>
          <Button size="sm" onClick={() => onToggleFav(entry)}>{entry.is_favorite ? '★ Favorited' : '☆ Favorite'}</Button>
          <table className="easyforms-table" style={{ marginTop: 14 }}>
            <tbody>
              {Object.entries(entry.response || {}).map(([key, value]) => (
                <tr key={key}>
                  <td style={{ fontWeight: 600, width: 140 }}>{(fields[key] && fields[key].label) || key}</td>
                  <td>{Array.isArray(value) ? value.join(', ') : (typeof value === 'object' ? JSON.stringify(value) : String(value))}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}

/** Entries page. */
export default function Entries({ formId }) {
  const [data, setData] = useState(null);
  const [filter, setFilter] = useState('');
  const [active, setActive] = useState(null);
  const { notify } = useToast();

  const load = useCallback(() => {
    if (!formId) return;
    api.get(`/forms/${formId}/entries?status=${filter}`).then(setData).catch(() => setData({ items: [], form: null }));
  }, [formId, filter]);

  useEffect(() => { load(); }, [load]);

  if (!formId) return <FormPicker />;
  if (data === null) return <Loading />;

  const fieldMap = {};
  const flatten = (list) => (list || []).forEach((f) => {
    if (f.type === 'container') (f.columns || []).forEach((c) => flatten(c.fields));
    else if (f.key) fieldMap[f.key] = f;
  });
  flatten(data.form && data.form.fields);
  const columns = Object.keys(fieldMap).slice(0, 4);

  const open = async (entry) => {
    const full = await api.get(`/entries/${entry.id}`).catch(() => entry);
    setActive(full);
    load();
  };

  const toggleFav = async (entry) => {
    const updated = await api.put(`/entries/${entry.id}`, { is_favorite: entry.is_favorite ? 0 : 1 }).catch(() => null);
    if (updated) { setActive(updated); load(); }
  };

  const exportCsv = async () => {
    try {
      const res = await api.get(`/forms/${formId}/entries/export`);
      const blob = new Blob([atob(res.content)], { type: res.mime });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = res.filename; a.click();
      URL.revokeObjectURL(url);
    } catch (e) { notify(e.message, 'error'); }
  };

  return (
    <div>
      <PageHead
        title={data.form ? `Entries — ${data.form.title}` : 'Entries'}
        actions={<>
          <Button onClick={() => { window.location.hash = '#/forms'; }}>All Forms</Button>
          <Button variant="primary" onClick={exportCsv}>Export CSV</Button>
        </>}
      />

      <div className="easyforms-tabs">
        {FILTERS.map((f) => (
          <button key={f.key} className={filter === f.key ? 'is-active' : ''} onClick={() => setFilter(f.key)}>{f.label}</button>
        ))}
      </div>

      {data.items.length === 0 ? (
        <Card><Empty icon="✉" title="No submissions yet" /></Card>
      ) : (
        <Card pad={false}>
          <table className="easyforms-table">
            <thead>
              <tr>
                <th>#</th>
                {columns.map((c) => <th key={c}>{fieldMap[c].label || c}</th>)}
                <th>Date</th><th></th>
              </tr>
            </thead>
            <tbody>
              {data.items.map((entry) => (
                <tr key={entry.id} style={{ fontWeight: entry.status === 'unread' ? 700 : 400 }}>
                  <td>{entry.serial}</td>
                  {columns.map((c) => {
                    const v = entry.response[c];
                    return <td key={c}>{Array.isArray(v) ? v.join(', ') : (typeof v === 'object' ? JSON.stringify(v) : String(v ?? ''))}</td>;
                  })}
                  <td style={{ color: 'var(--_muted)', fontSize: 12 }}>{entry.created_at}</td>
                  <td><Button size="sm" onClick={() => open(entry)}>View</Button></td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}

      {active && <EntryDetail entry={active} fields={fieldMap} onClose={() => setActive(null)} onToggleFav={toggleFav} />}
    </div>
  );
}
