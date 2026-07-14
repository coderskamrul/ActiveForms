/**
 * Entries management. Without a form id, shows a form picker; with one, renders
 * the premium submissions table: status filters with live counts, search, column
 * sorting, bulk actions, favorites, per-row actions, pagination, and CSV export.
 * Individual submissions open at #/forms/:id/entries/:entryId (see EntryDetail).
 */
import React, { useEffect, useState, useCallback, useRef } from 'react';
import api from '../api/client';
import { Loading, Empty, Button, Card, PageHead, Badge } from '../components/ui';
import { useToast } from '../components/Toast';
import { flattenFields, formatValue, statusMeta } from './entriesUtils';

const FILTERS = [
  { key: '', label: 'All', count: 'all' },
  { key: 'unread', label: 'Unread', count: 'unread' },
  { key: 'read', label: 'Read', count: 'read' },
  { key: 'favorites', label: 'Favorites', count: 'favorites' },
  { key: 'trashed', label: 'Trash', count: 'trashed' },
];

const PER_PAGE_OPTIONS = [10, 20, 50, 100];

/**
 * Format a MySQL datetime ("YYYY-MM-DD HH:MM:SS") as a short relative label.
 * @param {string} mysql Datetime string.
 * @returns {string}
 */
function timeAgo(mysql) {
  if (!mysql) return '';
  const then = new Date(mysql.replace(' ', 'T')).getTime();
  if (Number.isNaN(then)) return mysql;
  const secs = Math.round((Date.now() - then) / 1000);
  if (secs < 60) return 'just now';
  const mins = Math.round(secs / 60);
  if (mins < 60) return `${mins} minute${mins === 1 ? '' : 's'} ago`;
  const hrs = Math.round(mins / 60);
  if (hrs < 24) return `${hrs} hour${hrs === 1 ? '' : 's'} ago`;
  const days = Math.round(hrs / 24);
  if (days < 30) return `${days} day${days === 1 ? '' : 's'} ago`;
  return mysql.split(' ')[0];
}

/** Form picker shown when no form is selected. */
function FormPicker() {
  const [forms, setForms] = useState(null);
  useEffect(() => { api.get('/forms').then((r) => setForms(r.items || [])).catch(() => setForms([])); }, []);
  if (forms === null) return <Loading />;
  return (
    <div>
      <PageHead title="Entries" subtitle="Select a form to view its submissions" />
      {forms.length === 0 ? <Card><Empty icon="✉" title="No forms yet">Create a form to start collecting entries.</Empty></Card> : (
        <Card pad={false}>
          <table className="radiusforms-table radiusforms-entries-table">
            <thead><tr><th>Form</th><th>Entries</th><th aria-label="Actions" /></tr></thead>
            <tbody>
              {forms.map((f) => (
                <tr key={f.id}>
                  <td style={{ fontWeight: 600 }}>{f.title}</td>
                  <td><Badge>{f.entries ?? 0}</Badge></td>
                  <td style={{ textAlign: 'right' }}>
                    <a className="radiusforms-btn radiusforms-btn--sm" href={`#/forms/${f.id}/entries`}>View entries</a>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </Card>
      )}
    </div>
  );
}

/** Sortable column header cell. */
function SortTh({ label, col, sort, onSort, style }) {
  const active = sort.orderby === col;
  const arrow = !active ? '' : (sort.order === 'ASC' ? ' ▲' : ' ▼');
  return (
    <th style={style}>
      <button type="button" className={`radiusforms-th-sort${active ? ' is-active' : ''}`} onClick={() => onSort(col)}>
        {label}<span className="radiusforms-th-sort__arrow">{arrow}</span>
      </button>
    </th>
  );
}

/** Entries list for a single form. */
export default function Entries({ formId }) {
  const [data, setData] = useState(null);
  const [filter, setFilter] = useState('');
  const [search, setSearch] = useState('');
  const [debounced, setDebounced] = useState('');
  const [sort, setSort] = useState({ orderby: 'id', order: 'DESC' });
  const [page, setPage] = useState(1);
  const [perPage, setPerPage] = useState(20);
  const [selected, setSelected] = useState([]);
  const [busy, setBusy] = useState(false);
  const { notify } = useToast();
  const firstLoad = useRef(true);

  // Debounce the search box so typing doesn't fire a request per keystroke.
  useEffect(() => {
    const id = setTimeout(() => setDebounced(search), 350);
    return () => clearTimeout(id);
  }, [search]);

  // Reset to the first page whenever a query dimension changes.
  useEffect(() => { setPage(1); setSelected([]); }, [filter, debounced, perPage, sort]);

  const load = useCallback(() => {
    if (!formId) return;
    const params = new URLSearchParams({
      status: filter,
      search: debounced,
      page: String(page),
      per_page: String(perPage),
      orderby: sort.orderby,
      order: sort.order,
    });
    api.get(`/forms/${formId}/entries?${params.toString()}`)
      .then((r) => { setData(r); firstLoad.current = false; })
      .catch(() => setData({ items: [], form: null, total: 0, counts: {} }));
  }, [formId, filter, debounced, page, perPage, sort]);

  useEffect(() => { load(); }, [load]);

  if (!formId) return <FormPicker />;
  if (data === null && firstLoad.current) return <Loading />;

  const fieldMap = flattenFields(data && data.form && data.form.fields);
  const columns = Object.keys(fieldMap).slice(0, 4);
  const items = (data && data.items) || [];
  const counts = (data && data.counts) || {};
  const total = (data && data.total) || 0;
  const pages = Math.max(1, Math.ceil(total / perPage));

  const onSort = (col) => setSort((s) => (
    s.orderby === col ? { orderby: col, order: s.order === 'ASC' ? 'DESC' : 'ASC' } : { orderby: col, order: 'ASC' }
  ));

  const allOnPageSelected = items.length > 0 && items.every((e) => selected.includes(e.id));
  const toggleAll = () => setSelected(allOnPageSelected ? [] : items.map((e) => e.id));
  const toggleOne = (id) => setSelected((s) => (s.includes(id) ? s.filter((x) => x !== id) : [...s, id]));

  const goView = (entry) => { window.location.hash = `#/forms/${formId}/entries/${entry.id}`; };

  const toggleFav = async (entry, e) => {
    e.stopPropagation();
    await api.put(`/entries/${entry.id}`, { is_favorite: entry.is_favorite ? 0 : 1 }).catch(() => null);
    load();
  };

  const deleteOne = async (entry, e) => {
    e.stopPropagation();
    if (!window.confirm(`Permanently delete entry #${entry.serial}? This cannot be undone.`)) return;
    try {
      await api.del(`/entries/${entry.id}`);
      notify('Entry deleted', 'success');
      load();
    } catch (err) { notify(err.message, 'error'); }
  };

  const runBulk = async (action) => {
    if (selected.length === 0) return;
    const isDelete = action === 'delete';
    if (isDelete && !window.confirm(`Permanently delete ${selected.length} ${selected.length === 1 ? 'entry' : 'entries'}?`)) return;
    setBusy(true);
    try {
      const res = await api.post(`/forms/${formId}/entries/bulk`, { action, ids: selected });
      notify(`${res.affected} ${res.affected === 1 ? 'entry' : 'entries'} updated`, 'success');
      setSelected([]);
      load();
    } catch (err) { notify(err.message, 'error'); } finally { setBusy(false); }
  };

  const exportCsv = async () => {
    try {
      const res = await api.get(`/forms/${formId}/entries/export`);
      const blob = new Blob([Uint8Array.from(atob(res.content), (c) => c.charCodeAt(0))], { type: res.mime });
      const url = URL.createObjectURL(blob);
      const a = document.createElement('a');
      a.href = url; a.download = res.filename; a.click();
      URL.revokeObjectURL(url);
    } catch (e) { notify(e.message, 'error'); }
  };

  const isTrash = filter === 'trashed';

  return (
    <div>
      <PageHead
        title={data && data.form ? `Entries — ${data.form.title}` : 'Entries'}
        subtitle={data && data.form ? `${total} ${total === 1 ? 'submission' : 'submissions'}` : null}
        actions={<>
          <Button onClick={() => { window.location.hash = '#/entries'; }}>All Forms</Button>
          <Button variant="primary" onClick={exportCsv}>⭳ Export CSV</Button>
        </>}
      />

      {/* Status filter pills with live counts */}
      <div className="radiusforms-entries-filters">
        {FILTERS.map((f) => (
          <button
            key={f.key}
            type="button"
            className={`radiusforms-pill${filter === f.key ? ' is-active' : ''}`}
            onClick={() => setFilter(f.key)}
          >
            {f.label}
            {counts[f.count] !== undefined && <span className="radiusforms-pill__count">{counts[f.count]}</span>}
          </button>
        ))}
        <div className="radiusforms-entries-filters__spacer" />
        <div className="radiusforms-search">
          <span className="dashicons dashicons-search" aria-hidden="true" />
          <input
            type="search"
            placeholder="Search entries…"
            value={search}
            onChange={(e) => setSearch(e.target.value)}
          />
        </div>
      </div>

      {/* Bulk action bar */}
      {selected.length > 0 && (
        <div className="radiusforms-bulkbar">
          <span className="radiusforms-bulkbar__count">{selected.length} selected</span>
          <div className="radiusforms-bulkbar__actions">
            {!isTrash && <Button size="sm" disabled={busy} onClick={() => runBulk('read')}>Mark read</Button>}
            {!isTrash && <Button size="sm" disabled={busy} onClick={() => runBulk('unread')}>Mark unread</Button>}
            {!isTrash && <Button size="sm" disabled={busy} onClick={() => runBulk('favorite')}>★ Favorite</Button>}
            {!isTrash && <Button size="sm" disabled={busy} onClick={() => runBulk('trash')}>Trash</Button>}
            {isTrash && <Button size="sm" disabled={busy} onClick={() => runBulk('restore')}>Restore</Button>}
            <Button size="sm" variant="danger" disabled={busy} onClick={() => runBulk('delete')}>Delete</Button>
          </div>
          <button type="button" className="radiusforms-bulkbar__clear" onClick={() => setSelected([])}>Clear</button>
        </div>
      )}

      {items.length === 0 ? (
        <Card><Empty icon="✉" title={debounced ? 'No matching entries' : 'No submissions yet'}>
          {debounced ? 'Try a different search term.' : 'Submissions to this form will appear here.'}
        </Empty></Card>
      ) : (
        <Card pad={false}>
          <div className="radiusforms-table-scroll">
            <table className="radiusforms-table radiusforms-entries-table">
              <thead>
                <tr>
                  <th className="radiusforms-col-check">
                    <input type="checkbox" checked={allOnPageSelected} onChange={toggleAll} aria-label="Select all" />
                  </th>
                  <SortTh label="#" col="serial" sort={sort} onSort={onSort} style={{ width: 70 }} />
                  {columns.map((c) => <th key={c}>{fieldMap[c].label || c}</th>)}
                  <SortTh label="Status" col="status" sort={sort} onSort={onSort} />
                  <th>Submitter</th>
                  <SortTh label="Submitted" col="created_at" sort={sort} onSort={onSort} />
                  <th className="radiusforms-col-actions" aria-label="Actions" />
                </tr>
              </thead>
              <tbody>
                {items.map((entry) => {
                  const sm = statusMeta(entry.status);
                  const checked = selected.includes(entry.id);
                  return (
                    <tr
                      key={entry.id}
                      className={`radiusforms-entry-row${entry.status === 'unread' ? ' is-unread' : ''}${checked ? ' is-selected' : ''}`}
                      onClick={() => goView(entry)}
                    >
                      <td className="radiusforms-col-check" onClick={(e) => e.stopPropagation()}>
                        <input type="checkbox" checked={checked} onChange={() => toggleOne(entry.id)} aria-label={`Select entry ${entry.serial}`} />
                      </td>
                      <td className="radiusforms-td-serial">
                        <button type="button" className="radiusforms-star" onClick={(e) => toggleFav(entry, e)} title={entry.is_favorite ? 'Unfavorite' : 'Favorite'}>
                          {entry.is_favorite ? '★' : '☆'}
                        </button>
                        <span>#{entry.serial}</span>
                      </td>
                      {columns.map((c) => (
                        <td key={c} className="radiusforms-td-value" title={formatValue(entry.response[c])}>
                          {formatValue(entry.response[c]) || <span className="radiusforms-muted">—</span>}
                        </td>
                      ))}
                      <td><Badge tone={sm.tone}>{sm.label}</Badge></td>
                      <td className="radiusforms-td-user">{entry.user ? entry.user.name : '—'}</td>
                      <td className="radiusforms-td-time" title={entry.created_at}>{timeAgo(entry.created_at)}</td>
                      <td className="radiusforms-col-actions" onClick={(e) => e.stopPropagation()}>
                        <div className="radiusforms-row-actions">
                          <button type="button" className="radiusforms-iconbtn" title="View" onClick={() => goView(entry)}>👁</button>
                          <button type="button" className="radiusforms-iconbtn" title="Edit" onClick={() => { window.location.hash = `#/forms/${formId}/entries/${entry.id}?edit=1`; }}>✎</button>
                          <button type="button" className="radiusforms-iconbtn is-danger" title="Delete" onClick={(e) => deleteOne(entry, e)}>🗑</button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
              </tbody>
            </table>
          </div>

          {/* Pagination */}
          <div className="radiusforms-pager">
            <span className="radiusforms-pager__total">Total {total}</span>
            <select className="radiusforms-select radiusforms-select--pp" value={perPage} onChange={(e) => setPerPage(Number(e.target.value))}>
              {PER_PAGE_OPTIONS.map((n) => <option key={n} value={n}>{n}/page</option>)}
            </select>
            <button className="radiusforms-pager__btn" disabled={page <= 1} onClick={() => setPage((p) => Math.max(1, p - 1))} aria-label="Previous page">‹</button>
            <span className="radiusforms-pager__page">{page}</span>
            <span className="radiusforms-pager__total">of {pages}</span>
            <button className="radiusforms-pager__btn" disabled={page >= pages} onClick={() => setPage((p) => Math.min(pages, p + 1))} aria-label="Next page">›</button>
          </div>
        </Card>
      )}
    </div>
  );
}
