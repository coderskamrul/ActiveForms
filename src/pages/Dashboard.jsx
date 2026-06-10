/**
 * Dashboard overview: totals, submission trend, top forms.
 */
import React, { useEffect, useState } from 'react';
import api from '../api/client';
import { Loading, Card, PageHead, Button } from '../components/ui';
import { go } from '../router';

/** Sparkline-ish bar chart from trend buckets. */
function TrendBars({ trend }) {
  if (!trend || !trend.length) {
    return <p style={{ color: 'var(--_muted)' }}>No submissions in the last 30 days.</p>;
  }
  const max = Math.max(...trend.map((d) => d.count), 1);
  return (
    <div style={{ display: 'flex', alignItems: 'flex-end', gap: 3, height: 120, marginTop: 10 }}>
      {trend.map((d) => (
        <div
          key={d.day}
          title={`${d.day}: ${d.count}`}
          style={{
            flex: 1,
            minWidth: 3,
            height: `${Math.max(4, (d.count / max) * 100)}%`,
            background: 'var(--easyforms-color-primary, #4f46e5)',
            borderRadius: '3px 3px 0 0',
            opacity: 0.85,
          }}
        />
      ))}
    </div>
  );
}

/** Dashboard page. */
export default function Dashboard() {
  const [data, setData] = useState(null);

  useEffect(() => {
    api.get('/reports/overview').then(setData).catch(() => setData({ totals: {}, trend: [], topForms: [] }));
  }, []);

  if (!data) return <Loading />;

  const totals = data.totals || {};

  return (
    <div>
      <PageHead
        title="Dashboard"
        subtitle="Your form activity at a glance KM"
        actions={<Button variant="primary" onClick={() => go('/forms/new')}>+ Add New Form</Button>}
      />

      <div className="easyforms-grid easyforms-grid-4" style={{ marginBottom: 20 }}>
        <Card pad={false}><div className="easyforms-stat"><div className="num">{totals.forms || 0}</div><div className="lbl">Total Forms</div></div></Card>
        <Card pad={false}><div className="easyforms-stat"><div className="num">{totals.entries || 0}</div><div className="lbl">Submissions</div></div></Card>
        <Card pad={false}><div className="easyforms-stat"><div className="num">{totals.unread || 0}</div><div className="lbl">Unread</div></div></Card>
        <Card pad={false}>
          <div className="easyforms-stat">
            <div className="num">{totals.entries && totals.forms ? Math.round(totals.entries / totals.forms) : 0}</div>
            <div className="lbl">Avg / Form</div>
          </div>
        </Card>
      </div>

      <div className="easyforms-grid easyforms-grid-2">
        <Card>
          <h3 style={{ marginTop: 0 }}>Submissions — Last 30 Days</h3>
          <TrendBars trend={data.trend} />
        </Card>
        <Card>
          <h3 style={{ marginTop: 0 }}>Top Forms</h3>
          {(!data.topForms || !data.topForms.length) && <p style={{ color: 'var(--_muted)' }}>No data yet.</p>}
          {(data.topForms || []).map((f) => (
            <div key={f.form_id} style={{ display: 'flex', justifyContent: 'space-between', padding: '8px 0', borderBottom: '1px solid var(--_border)' }}>
              <a href={`#/forms/${f.form_id}/entries`} style={{ textDecoration: 'none', color: 'var(--_text)', fontWeight: 600 }}>{f.title}</a>
              <span className="easyforms-badge">{f.count}</span>
            </div>
          ))}
        </Card>
      </div>
    </div>
  );
}
