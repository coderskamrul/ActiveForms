/**
 * Reports — an analytics dashboard (overview / submissions),
 * filterable by form and date range. All figures come from the live
 * /reports/dashboard endpoint; the per-form field breakdown is layered in when a
 * single form is selected.
 */
import React, { useEffect, useMemo, useState } from 'react';
import api from '../api/client';
import { Loading, Card, PageHead, Empty } from '../components/ui';
import {
  StatCard, ChartCard, Segmented, ChartEmpty, LineBarChart, HBars, Heatmap,
} from './reports/charts.jsx';

const SERIES_COLORS = {
  submissions: '#7c3aed', spam: '#ef4444', unread: '#f59e0b', read: '#3b82f6', trashed: '#9ca3af',
};
const SERIES_DEFS = [
  { key: 'submissions', label: 'Submissions' },
  { key: 'spam', label: 'Spam' },
  { key: 'unread', label: 'Unread' },
  { key: 'read', label: 'Read' },
  { key: 'trashed', label: 'Trashed' },
];

/** Two-digit local YYYY-MM-DD (avoids the UTC shift of toISOString). */
function ymd(d) {
  return `${d.getFullYear()}-${String(d.getMonth() + 1).padStart(2, '0')}-${String(d.getDate()).padStart(2, '0')}`;
}

const PRESETS = [
  { value: 'last7', label: 'Last 7 days' },
  { value: 'last30', label: 'Last 30 days' },
  { value: 'thisMonth', label: 'This month' },
  { value: 'lastMonth', label: 'Last month' },
  { value: 'last90', label: 'Last 90 days' },
  { value: 'thisYear', label: 'This year' },
];

/** Resolve a preset key into a {from, to} pair of YYYY-MM-DD. */
function presetRange(preset) {
  const now = new Date();
  const start = new Date(now);
  if (preset === 'last7') start.setDate(now.getDate() - 6);
  else if (preset === 'last90') start.setDate(now.getDate() - 89);
  else if (preset === 'thisMonth') start.setDate(1);
  else if (preset === 'thisYear') { start.setMonth(0); start.setDate(1); }
  else if (preset === 'lastMonth') {
    const s = new Date(now.getFullYear(), now.getMonth() - 1, 1);
    const e = new Date(now.getFullYear(), now.getMonth(), 0);
    return { from: ymd(s), to: ymd(e) };
  } else start.setDate(now.getDate() - 29); // last30 default
  return { from: ymd(start), to: ymd(now) };
}

/** "May 13, 2026 - Jun 12, 2026" */
function rangeLabel(from, to) {
  const opt = { month: 'short', day: 'numeric', year: 'numeric' };
  const f = new Date(`${from}T00:00:00`).toLocaleDateString(undefined, opt);
  const t = new Date(`${to}T00:00:00`).toLocaleDateString(undefined, opt);
  return `${f} - ${t}`;
}

/** Horizontal bar distribution for one reportable field. */
function FieldChart({ field }) {
  const total = field.buckets.reduce((sum, b) => sum + b.count, 0) || 1;
  return (
    <Card>
      <h3 style={{ marginTop: 0 }}>{field.label}</h3>
      {field.buckets.length === 0 && <p style={{ color: 'var(--_muted)' }}>No responses.</p>}
      {field.buckets.map((b, i) => (
        <div key={i} style={{ marginBottom: 10 }}>
          <div style={{ display: 'flex', justifyContent: 'space-between', fontSize: 13, marginBottom: 3 }}>
            <span>{b.label || '(empty)'}</span>
            <span style={{ color: 'var(--_muted)' }}>{b.count} · {Math.round((b.count / total) * 100)}%</span>
          </div>
          <div style={{ height: 8, background: 'var(--_surface-alt)', borderRadius: 999 }}>
            <div style={{ width: `${(b.count / total) * 100}%`, height: '100%', background: 'var(--radiusforms-color-primary, #4f46e5)', borderRadius: 999 }} />
          </div>
        </div>
      ))}
    </Card>
  );
}

/** Overview tab — the headline analytics dashboard. */
function OverviewTab({ data }) {
  const [chartMode, setChartMode] = useState('bar');
  const [half, setHalf] = useState('am');

  const labels = data.series.map((s) => s.day);
  const overviewSeries = SERIES_DEFS.map((d) => ({ ...d, color: SERIES_COLORS[d.key], data: data.series.map((s) => s[d.key]) }));


  const topItems = (data.topForms || []).map((f) => ({ label: f.title, value: f.count }));
  const topHasData = topItems.some((t) => t.value > 0);

  return (
    <>
      <div className="radiusforms-grid radiusforms-grid-4 radiusforms-rep-stats">
        <StatCard icon="feedback" label="Total Submissions" value={data.cards.submissions.value} delta={data.cards.submissions.delta} />
        <StatCard icon="warning" label="Spam Submissions" value={data.cards.spam.value} />
        <StatCard icon="email" label="Unread Submissions" value={data.cards.unread.value} />
        <StatCard icon="feedback" label="Created Forms" value={data.cards.forms.value} />
      </div>

      <ChartCard
        title="Overview Chart"
        right={<Segmented value={chartMode} onChange={setChartMode} options={[{ value: 'line', label: 'Line' }, { value: 'bar', label: 'Bar' }]} />}
      >
        <LineBarChart series={overviewSeries} labels={labels} mode={chartMode} />
      </ChartCard>

      <ChartCard title="Top Performing Forms">
        {topHasData
          ? <HBars items={topItems} />
          : <ChartEmpty>No submission data available for the selected date range</ChartEmpty>}
      </ChartCard>

      <ChartCard
        title="Submission Timeline Patterns"
        right={<Segmented value={half} onChange={setHalf} options={[{ value: 'am', label: 'AM (12-11)' }, { value: 'pm', label: 'PM (12-11)' }]} />}
      >
        <Heatmap matrix={data.timeline} half={half} />
      </ChartCard>
    </>
  );
}

/** Submissions tab — per-field response breakdown. */
function SubmissionsTab({ formId, fieldReport }) {
  return (
    <>
      {!formId ? (
        <Card><Empty icon="📊" title="Select a form">Choose a single form above to see its field-by-field response breakdown.</Empty></Card>
      ) : fieldReport === null ? (
        <Loading />
      ) : (!fieldReport.fields || fieldReport.fields.length === 0) ? (
        <Card><Empty icon="📊" title="No reportable fields">Add dropdown, radio, checkbox, or country fields to see analytics.</Empty></Card>
      ) : (
        <div className="radiusforms-grid radiusforms-grid-2">
          {fieldReport.fields.map((f) => <FieldChart key={f.key} field={f} />)}
        </div>
      )}
    </>
  );
}

const TABS = [
  { key: 'overview', label: 'Overview' },
  { key: 'submissions', label: 'Submissions' },
];

/** Reports page. */
export default function Reports({ formId: routeFormId }) {
  const [forms, setForms] = useState([]);
  const [formId, setFormId] = useState(routeFormId ? Number(routeFormId) : 0);
  const [preset, setPreset] = useState('last30');
  const [tab, setTab] = useState('overview');
  const [data, setData] = useState(null);
  const [fieldReport, setFieldReport] = useState(null);

  const range = useMemo(() => presetRange(preset), [preset]);

  useEffect(() => { api.get('/forms').then((r) => setForms(r.items || [])).catch(() => setForms([])); }, []);

  useEffect(() => {
    setData(null);
    api.get(`/reports/dashboard?form_id=${formId}&from=${range.from}&to=${range.to}`)
      .then(setData)
      .catch(() => setData({ series: [], topForms: [], timeline: Array.from({ length: 7 }, () => Array(24).fill(0)), cards: { submissions: { value: 0, delta: 0 }, spam: { value: 0 }, unread: { value: 0 }, forms: { value: 0 } } }));
  }, [formId, range.from, range.to]);

  useEffect(() => {
    if (!formId) { setFieldReport(null); return; }
    setFieldReport(null);
    api.get(`/reports/forms/${formId}`).then(setFieldReport).catch(() => setFieldReport({ fields: [] }));
  }, [formId]);

  const filters = (
    <div className="radiusforms-rep-filters">
      <select className="radiusforms-select" value={formId} onChange={(e) => setFormId(Number(e.target.value))}>
        <option value={0}>All Forms</option>
        {forms.map((f) => <option key={f.id} value={f.id}>{f.title}</option>)}
      </select>
      <select className="radiusforms-select" value={preset} onChange={(e) => setPreset(e.target.value)}>
        {PRESETS.map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
      </select>
      <span className="radiusforms-rep-range"><span className="dashicons dashicons-calendar-alt" aria-hidden="true" /> {rangeLabel(range.from, range.to)}</span>
    </div>
  );

  return (
    <div>
      <PageHead title="Reports" subtitle="A brief look at your overall form performance" actions={filters} />

      <div className="radiusforms-tabs radiusforms-rep-tabs">
        {TABS.map((t) => (
          <button key={t.key} type="button" className={tab === t.key ? 'is-active' : ''} onClick={() => setTab(t.key)}>{t.label}</button>
        ))}
      </div>

      {data === null ? <Loading /> : (
        <div className="radiusforms-rep">
          {tab === 'overview' && <OverviewTab data={data} />}
          {tab === 'submissions' && <SubmissionsTab formId={formId} fieldReport={fieldReport} />}
         </div>
      )}
    </div>
  );
}
