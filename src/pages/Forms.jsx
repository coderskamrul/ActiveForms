/**
 * Forms list: status filter, search, create from template, inline row actions
 * (edit / settings / entries / preview / duplicate / export / delete), active
 * toggle, copy-able shortcode, and pagination.
 */
import React, { useEffect, useState, useCallback } from 'react';
import api from '../api/client';
import config from '../config';
import { Loading, Empty, Button, Card, Toggle } from '../components/ui';
import { useToast } from '../components/Toast';
import { go } from '../router';

const STATUSES = [
  { value: '', label: 'All Forms' },
  { value: 'published', label: 'Active' },
  { value: 'draft', label: 'Draft' },
];

/** Open the public, frontend-accurate preview for a form in a new tab. */
function previewUrl(id) {
  const base = (config.home || '/').replace(/\/$/, '/');
  return `${base}?activeforms_preview=${id}`;
}

/** Template picker modal. */
function TemplateModal({ onClose, onCreate }) {
  const [templates, setTemplates] = useState([]);
  const [query, setQuery] = useState('');

  useEffect(() => {
    api.get('/templates').then(setTemplates).catch(() => setTemplates([]));
  }, []);

  const q = query.trim().toLowerCase();
  const blank = templates.find((tpl) => tpl.id === 'blank');
  const rest = templates.filter((tpl) => tpl.id !== 'blank' && (!q || tpl.name.toLowerCase().includes(q) || (tpl.description || '').toLowerCase().includes(q)));

  return (
    <div className="activeforms-tpl-overlay" onClick={onClose}>
      <div className="activeforms-tpl-modal" onClick={(e) => e.stopPropagation()}>
        <div className="activeforms-tpl-modal__head">
          <div>
            <h2>Create a new form</h2>
            <p>Start from scratch or pick a ready-made template.</p>
          </div>
          <button type="button" className="activeforms-tpl-modal__close" onClick={onClose} aria-label="Close">✕</button>
        </div>
        <div className="activeforms-tpl-modal__bar">
          <input className="activeforms-input" placeholder="Search templates…" value={query} onChange={(e) => setQuery(e.target.value)} />
        </div>
        <div className="activeforms-tpl-modal__body">
          {blank && (
            <button type="button" className="activeforms-tpl-card activeforms-tpl-card--blank" onClick={() => onCreate(blank)}>
              <span className="activeforms-tpl-card__icon dashicons dashicons-plus-alt2" aria-hidden="true" />
              <span className="activeforms-tpl-card__name">{blank.name}</span>
              <span className="activeforms-tpl-card__desc">{blank.description}</span>
            </button>
          )}
          <div className="activeforms-tpl-grid">
            {rest.map((tpl) => (
              <button key={tpl.id} type="button" className="activeforms-tpl-card" onClick={() => onCreate(tpl)}>
                <span className={`activeforms-tpl-card__icon dashicons dashicons-${tpl.icon || 'forms'}`} aria-hidden="true" />
                <span className="activeforms-tpl-card__name">{tpl.name}</span>
                <span className="activeforms-tpl-card__desc">{tpl.description}</span>
              </button>
            ))}
          </div>
          {!templates.length && <div className="activeforms-empty"><p>Loading templates…</p></div>}
        </div>
      </div>
    </div>
  );
}

/** A copy-to-clipboard shortcode chip. */
function Shortcode({ id }) {
  const [copied, setCopied] = useState(false);
  const code = `[activeforms id="${id}"]`;
  const copy = () => {
    try { navigator.clipboard.writeText(code); setCopied(true); setTimeout(() => setCopied(false), 1300); } catch (e) { /* noop */ }
  };
  return (
    <button type="button" className="activeforms-sc" onClick={copy} title="Copy shortcode">
      <span className="dashicons dashicons-shortcode" aria-hidden="true" />
      <code>{code}</code>
      {copied && <span className="activeforms-sc__ok">Copied</span>}
    </button>
  );
}

/** Forms list page. */
export default function Forms() {
  const [data, setData] = useState(null);
  const [status, setStatus] = useState('');
  const [search, setSearch] = useState('');
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(10);
  const [showTpl, setShowTpl] = useState(false);
  const { notify } = useToast();

  const load = useCallback(() => {
    const qs = new URLSearchParams({ search, status, page: String(page), per_page: String(perPage) });
    api.get(`/forms?${qs.toString()}`)
      .then((res) => setData({ items: res.items || [], total: res.total ?? (res.items || []).length }))
      .catch(() => setData({ items: [], total: 0 }));
  }, [search, status, page, perPage]);

  useEffect(() => { load(); }, [load]);

  const create = async (tpl) => {
    try {
      const form = await api.post('/forms', { title: tpl.id === 'blank' ? 'Untitled Form' : tpl.name, template: tpl.id });
      setShowTpl(false);
      go(`/forms/${form.id}/edit`);
    } catch (e) { notify(e.message, 'error'); }
  };

  const duplicate = async (id) => {
    try { await api.post(`/forms/${id}/duplicate`); notify('Form duplicated'); load(); }
    catch (e) { notify(e.message, 'error'); }
  };

  const remove = async (id) => {
    // eslint-disable-next-line no-alert
    if (!window.confirm(config.strings.confirmDelete || 'Delete this form and all its entries?')) return;
    try { await api.del(`/forms/${id}`); notify('Form deleted'); load(); }
    catch (e) { notify(e.message, 'error'); }
  };

  const toggleStatus = async (form) => {
    const next = form.status === 'published' ? 'draft' : 'published';
    try {
      await api.put(`/forms/${form.id}`, { status: next });
      setData((d) => ({ ...d, items: d.items.map((f) => (f.id === form.id ? { ...f, status: next } : f)) }));
    } catch (e) { notify(e.message, 'error'); }
  };

  const exportForm = async (id) => {
    try {
      const form = await api.get(`/forms/${id}`);
      const blob = new Blob([JSON.stringify(form, null, 2)], { type: 'application/json' });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = `activeforms-${id}.json`; a.click();
      URL.revokeObjectURL(url);
    } catch (e) { notify(e.message, 'error'); }
  };

  const items = data ? data.items : [];
  const total = data ? data.total : 0;
  const totalPages = Math.max(1, Math.ceil(total / perPage));

  return (
    <div>
      <div className="activeforms-page-head">
        <h1>Forms</h1>
      </div>

      <div className="activeforms-list-bar">
        <select className="activeforms-select activeforms-select--status" value={status} onChange={(e) => { setStatus(e.target.value); setPage(1); }}>
          {STATUSES.map((s) => <option key={s.value} value={s.value}>{s.label}</option>)}
        </select>
        <Button variant="primary" onClick={() => setShowTpl(true)}><span className="dashicons dashicons-plus-alt2" /> Add New Form</Button>
        <div className="activeforms-list-bar__spacer" />
        <div className="activeforms-search">
          <span className="dashicons dashicons-search" aria-hidden="true" />
          <input placeholder="Search Forms" value={search} onChange={(e) => { setSearch(e.target.value); setPage(1); }} />
        </div>
      </div>

      {data === null && <Loading />}

      {data && items.length === 0 && (
        <Card><Empty icon="📝" title="No forms found">Create your first form to start collecting submissions.</Empty></Card>
      )}

      {data && items.length > 0 && (
        <Card pad={false}>
          <table className="activeforms-table activeforms-forms-table">
            <thead>
              <tr>
                <th style={{ width: 60 }}>ID</th>
                <th>Title</th>
                <th style={{ width: 280 }}>Shortcode</th>
                <th style={{ width: 90, textAlign: 'center' }}>Entries</th>
              </tr>
            </thead>
            <tbody>
              {items.map((f) => (
                <tr key={f.id}>
                  <td className="activeforms-td-id">{f.id}</td>
                  <td>
                    <a className="activeforms-form-title" href={`#/forms/${f.id}/edit`}>{f.title}</a>
                    <div className="activeforms-flinks">
                      <a href={`#/forms/${f.id}/edit`}>Edit</a>
                      <a href={`#/forms/${f.id}/edit`}>Settings</a>
                      <a href={`#/forms/${f.id}/entries`}>Entries</a>
                      <a href={previewUrl(f.id)} target="_blank" rel="noreferrer">Preview</a>
                      <button type="button" onClick={() => duplicate(f.id)}>Duplicate</button>
                      <button type="button" onClick={() => exportForm(f.id)}>Export</button>
                      <button type="button" className="is-danger" onClick={() => remove(f.id)}>Delete</button>
                      <span className="activeforms-flinks__status">
                        <Toggle checked={f.status === 'published'} onChange={() => toggleStatus(f)} />
                        <em className={f.status === 'published' ? 'is-active' : ''}>{f.status === 'published' ? 'Active' : 'Draft'}</em>
                      </span>
                    </div>
                  </td>
                  <td><Shortcode id={f.id} /></td>
                  <td style={{ textAlign: 'center' }}>
                    <a className="activeforms-entries-link" href={`#/forms/${f.id}/entries`}>{f.entries ?? 0}</a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          <div className="activeforms-pager">
            <span className="activeforms-pager__total">Total {total}</span>
            <select className="activeforms-select activeforms-select--pp" value={perPage} onChange={(e) => { setPerPage(Number(e.target.value)); setPage(1); }}>
              {[10, 20, 50].map((n) => <option key={n} value={n}>{n}/page</option>)}
            </select>
            <button type="button" className="activeforms-pager__btn" disabled={page <= 1} onClick={() => setPage((p) => p - 1)}>‹</button>
            <span className="activeforms-pager__page">{page}</span>
            <button type="button" className="activeforms-pager__btn" disabled={page >= totalPages} onClick={() => setPage((p) => p + 1)}>›</button>
          </div>
        </Card>
      )}

      {showTpl && <TemplateModal onClose={() => setShowTpl(false)} onCreate={create} />}
    </div>
  );
}
