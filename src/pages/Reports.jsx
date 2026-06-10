/**
 * Reports: per-form field distribution charts.
 */
import React, { useEffect, useState } from 'react';
import api from '../api/client';
import { Loading, Empty, Card, PageHead } from '../components/ui';

/** Horizontal bar distribution for one field. */
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
            <div style={{ width: `${(b.count / total) * 100}%`, height: '100%', background: 'var(--easyforms-color-primary, #4f46e5)', borderRadius: 999 }} />
          </div>
        </div>
      ))}
    </Card>
  );
}

/** Form picker for reports. */
function Picker() {
  const [forms, setForms] = useState(null);
  useEffect(() => { api.get('/forms').then((r) => setForms(r.items || [])).catch(() => setForms([])); }, []);
  if (forms === null) return <Loading />;
  return (
    <div>
      <PageHead title="Reports" subtitle="Choose a form to see response analytics" />
      <Card pad={false}>
        <table className="easyforms-table">
          <tbody>
            {forms.map((f) => (
              <tr key={f.id}>
                <td style={{ fontWeight: 600 }}>{f.title}</td>
                <td style={{ textAlign: 'right' }}><a className="easyforms-btn easyforms-btn--sm" href={`#/reports/${f.id}`}>View Report</a></td>
              </tr>
            ))}
          </tbody>
        </table>
      </Card>
    </div>
  );
}

/** Reports page. */
export default function Reports({ formId }) {
  const [report, setReport] = useState(null);

  useEffect(() => {
    if (formId) api.get(`/reports/forms/${formId}`).then(setReport).catch(() => setReport({ fields: [] }));
  }, [formId]);

  if (!formId) return <Picker />;
  if (report === null) return <Loading />;

  return (
    <div>
      <PageHead
        title={report.form ? `Report — ${report.form.title}` : 'Report'}
        actions={<a className="easyforms-btn" href="#/reports">All Reports</a>}
      />
      {(!report.fields || report.fields.length === 0) ? (
        <Card><Empty icon="📊" title="No reportable fields">Add dropdown, radio, checkbox, or country fields to see analytics.</Empty></Card>
      ) : (
        <div className="easyforms-grid easyforms-grid-2">
          {report.fields.map((f) => <FieldChart key={f.key} field={f} />)}
        </div>
      )}
    </div>
  );
}
