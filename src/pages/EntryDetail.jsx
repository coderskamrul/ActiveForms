/**
 * Full-page submission detail. Two columns: the submitted form data (with an
 * inline Edit Entry mode) on the left, and a submission metadata sidebar on the
 * right. Supports favorite, status changes, delete, CSV-less navigation between
 * neighbouring entries, and back-to-list.
 */
import React, { useEffect, useState, useCallback } from 'react';
import api from '../api/client';
import { Loading, Empty, Button, Card, Badge } from '../components/ui';
import { useToast } from '../components/Toast';
import { flattenFields, formatValue, statusMeta } from './entriesUtils';

const STATUS_OPTIONS = [
  { value: 'unread', label: 'Unread' },
  { value: 'read', label: 'Read' },
  { value: 'trashed', label: 'Trash' },
];

/** Whether the current hash requests edit mode (#/...?edit=1). */
function wantsEdit() {
  return /[?&]edit=1\b/.test(window.location.hash);
}

/** One metadata row in the sidebar. */
function MetaRow({ label, children }) {
  if (children === null || children === undefined || children === '') return null;
  return (
    <div className="easyforms-meta-row">
      <span className="easyforms-meta-row__label">{label}</span>
      <span className="easyforms-meta-row__value">{children}</span>
    </div>
  );
}

/**
 * Editor for a single field value. Adapts to the value/field shape: composite
 * objects render a sub-input per key, arrays edit as comma lists, textareas get
 * a multi-line box, everything else a text input.
 */
function FieldEditor({ field, value, onChange }) {
  if (value && typeof value === 'object' && !Array.isArray(value)) {
    return (
      <div className="easyforms-subgrid">
        {Object.keys(value).map((sub) => (
          <input
            key={sub}
            className="easyforms-input"
            placeholder={sub}
            value={value[sub] ?? ''}
            onChange={(e) => onChange({ ...value, [sub]: e.target.value })}
          />
        ))}
      </div>
    );
  }
  if (Array.isArray(value)) {
    return (
      <input
        className="easyforms-input"
        value={value.join(', ')}
        onChange={(e) => onChange(e.target.value.split(',').map((s) => s.trim()).filter(Boolean))}
      />
    );
  }
  if (field && field.type === 'textarea') {
    return <textarea className="easyforms-textarea" rows={3} value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
  }
  return <input className="easyforms-input" value={value ?? ''} onChange={(e) => onChange(e.target.value)} />;
}

/** Entry detail page. */
export default function EntryDetail({ formId, entryId }) {
  const [entry, setEntry] = useState(null);
  const [editing, setEditing] = useState(wantsEdit());
  const [draft, setDraft] = useState({});
  const [saving, setSaving] = useState(false);
  const { notify } = useToast();

  const load = useCallback(() => {
    api.get(`/entries/${entryId}`)
      .then((e) => { setEntry(e); setDraft(e.response || {}); })
      .catch(() => setEntry(false));
  }, [entryId]);

  // Re-fetch (and reset edit state) whenever the target entry changes.
  useEffect(() => { setEntry(null); setEditing(wantsEdit()); load(); }, [load]);

  if (entry === null) return <Loading />;
  if (entry === false) return <Card><Empty icon="⚠" title="Entry not found">This submission may have been deleted.</Empty></Card>;

  const fieldMap = flattenFields(entry.form && entry.form.fields);
  const keys = Object.keys(entry.response || {});
  const sm = statusMeta(entry.status);
  const meta = entry.meta || {};
  const neighbors = entry.neighbors || {};
  const backToList = `#/forms/${formId}/entries`;

  const labelFor = (key) => (fieldMap[key] && fieldMap[key].label) || key;

  const setField = (key, val) => setDraft((d) => ({ ...d, [key]: val }));

  const save = async () => {
    setSaving(true);
    try {
      const updated = await api.put(`/entries/${entry.id}`, { response: draft });
      setEntry((e) => ({ ...e, ...updated, meta: e.meta, user: e.user, form: e.form, neighbors: e.neighbors }));
      setEditing(false);
      notify('Entry updated', 'success');
    } catch (err) { notify(err.message, 'error'); } finally { setSaving(false); }
  };

  const cancelEdit = () => { setDraft(entry.response || {}); setEditing(false); };

  const toggleFav = async () => {
    const updated = await api.put(`/entries/${entry.id}`, { is_favorite: entry.is_favorite ? 0 : 1 }).catch(() => null);
    if (updated) setEntry((e) => ({ ...e, ...updated, meta: e.meta, user: e.user, form: e.form, neighbors: e.neighbors }));
  };

  const changeStatus = async (status) => {
    const updated = await api.put(`/entries/${entry.id}`, { status }).catch(() => null);
    if (updated) setEntry((e) => ({ ...e, ...updated, meta: e.meta, user: e.user, form: e.form, neighbors: e.neighbors }));
  };

  const remove = async () => {
    if (!window.confirm(`Permanently delete entry #${entry.serial}?`)) return;
    try {
      await api.del(`/entries/${entry.id}`);
      notify('Entry deleted', 'success');
      window.location.hash = backToList;
    } catch (err) { notify(err.message, 'error'); }
  };

  const navTo = (id) => { window.location.hash = `#/forms/${formId}/entries/${id}`; };

  return (
    <div className="easyforms-entry-detail">
      {/* Header */}
      <div className="easyforms-detail-head">
        <div className="easyforms-breadcrumb">
          <a href={backToList}>Entries</a>
          <span>/</span>
          <b>Serial #{entry.serial}</b>
          {!!entry.is_favorite && <span className="easyforms-star is-on" title="Favorited">★</span>}
        </div>
        <div className="easyforms-detail-nav">
          <Button disabled={!neighbors.older} onClick={() => navTo(neighbors.older)}>← Previous</Button>
          <Button disabled={!neighbors.newer} onClick={() => navTo(neighbors.newer)}>Next →</Button>
          <Button onClick={() => { window.location.hash = backToList; }}>View All</Button>
        </div>
      </div>

      <div className="easyforms-detail-grid">
        {/* Left: form data */}
        <Card>
          <div className="easyforms-detail-card-head">
            <h2>Form Entry Data</h2>
            <div className="easyforms-detail-card-head__actions">
              {!editing && <button type="button" className="easyforms-star" onClick={toggleFav} title={entry.is_favorite ? 'Unfavorite' : 'Favorite'}>{entry.is_favorite ? '★' : '☆'}</button>}
              {editing ? (
                <>
                  <Button size="sm" onClick={cancelEdit} disabled={saving}>Cancel</Button>
                  <Button size="sm" variant="primary" onClick={save} disabled={saving}>{saving ? 'Saving…' : 'Save changes'}</Button>
                </>
              ) : (
                <Button size="sm" onClick={() => setEditing(true)}>✎ Edit Entry</Button>
              )}
            </div>
          </div>

          {keys.length === 0 ? (
            <Empty icon="📋" title="No field data" />
          ) : (
            <div className="easyforms-entry-fields">
              {keys.map((key) => (
                <div className="easyforms-entry-field" key={key}>
                  <div className="easyforms-entry-field__label">{labelFor(key)}</div>
                  <div className="easyforms-entry-field__value">
                    {editing
                      ? <FieldEditor field={fieldMap[key]} value={draft[key]} onChange={(v) => setField(key, v)} />
                      : (formatValue(entry.response[key]) || <span className="easyforms-muted">— empty —</span>)}
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Right: submission info */}
        <Card>
          <div className="easyforms-detail-card-head">
            <h2>Submission Info</h2>
          </div>

          <div className="easyforms-meta-list">
            <MetaRow label="Submission ID">#{entry.serial}</MetaRow>
            <MetaRow label="Status">
              <span className="easyforms-meta-status">
                <Badge tone={sm.tone}>{sm.label}</Badge>
                <select className="easyforms-select easyforms-select--status" value={entry.status} onChange={(e) => changeStatus(e.target.value)}>
                  {STATUS_OPTIONS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
              </span>
            </MetaRow>
            <MetaRow label="User IP">{entry.ip || '—'}</MetaRow>
            <MetaRow label="Source URL">
              {entry.source_url ? <a href={entry.source_url} target="_blank" rel="noreferrer">{entry.source_url}</a> : '—'}
            </MetaRow>
            <MetaRow label="Referrer">
              {meta.referer ? <a href={meta.referer} target="_blank" rel="noreferrer">{meta.referer}</a> : null}
            </MetaRow>
            <MetaRow label="Browser">{entry.browser || '—'}</MetaRow>
            <MetaRow label="Operating System">{meta.os || null}</MetaRow>
            <MetaRow label="Device">{entry.device || null}</MetaRow>
            <MetaRow label="User">
              {entry.user && entry.user.edit_link
                ? <a href={entry.user.edit_link}>{entry.user.name}</a>
                : (entry.user ? entry.user.name : '—')}
            </MetaRow>
            <MetaRow label="Submitted On">{entry.created_at}</MetaRow>
            {entry.updated_at && entry.updated_at !== entry.created_at && (
              <MetaRow label="Last Updated">{entry.updated_at}</MetaRow>
            )}
            <MetaRow label="User Agent">
              {meta.user_agent ? <code className="easyforms-ua">{meta.user_agent}</code> : null}
            </MetaRow>
          </div>

          <div className="easyforms-detail-actions">
            <Button variant="danger" onClick={remove}>🗑 Delete entry</Button>
          </div>
        </Card>
      </div>
    </div>
  );
}
