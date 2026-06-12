/**
 * Lightweight, dependency-free SVG charts for the Reports dashboard. Each scales
 * to its container via a viewBox; colors come from the design tokens so charts
 * match the rest of the admin.
 */
import React, { useState } from 'react';

const MONTHS = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];

/** "Jun 09" style short label for a YYYY-MM-DD day. */
function shortDay(d) {
  const parts = String(d).split('-');
  if (parts.length !== 3) return d;
  return `${MONTHS[Number(parts[1]) - 1]} ${Number(parts[2])}`;
}

/** A stat card with icon, value and optional delta pill. */
export function StatCard({ icon, label, value, delta }) {
  const hasDelta = delta !== null && delta !== undefined;
  const up = hasDelta && delta >= 0;
  return (
    <div className="easyforms-card easyforms-rep-stat">
      <div className="easyforms-rep-stat__icon" aria-hidden="true">
        <span className={`dashicons dashicons-${icon}`} />
      </div>
      <div className="easyforms-rep-stat__body">
        <div className="easyforms-rep-stat__label">{label}</div>
        <div className="easyforms-rep-stat__value">
          {value}
          {hasDelta && (
            <span className={`easyforms-rep-delta${up ? ' is-up' : ' is-down'}`}>
              {up ? '▲' : '▼'} {Math.abs(delta)}%
            </span>
          )}
        </div>
      </div>
    </div>
  );
}

/** Card header with a title and optional right-aligned controls. */
export function ChartCard({ title, subtitle, right, children }) {
  return (
    <div className="easyforms-card easyforms-rep-card">
      <div className="easyforms-rep-card__head">
        <div>
          <h3>{title}</h3>
          {subtitle && <p>{subtitle}</p>}
        </div>
        {right && <div className="easyforms-rep-card__tools">{right}</div>}
      </div>
      <div className="easyforms-rep-card__body">{children}</div>
    </div>
  );
}

/** Small segmented toggle used inside chart headers. */
export function Segmented({ value, onChange, options }) {
  return (
    <div className="easyforms-rep-seg" role="group">
      {options.map((o) => (
        <button key={o.value} type="button" className={value === o.value ? 'is-active' : ''} onClick={() => onChange(o.value)}>
          {o.label}
        </button>
      ))}
    </div>
  );
}

/** Empty state shown inside a chart body when there's no data. */
export function ChartEmpty({ children = 'No data available for the selected range.' }) {
  return (
    <div className="easyforms-rep-empty">
      <span className="dashicons dashicons-chart-area" aria-hidden="true" />
      <p>{children}</p>
    </div>
  );
}

/**
 * Multi-series line / bar chart with a toggleable legend.
 * @param {object[]} series  [{ key, label, color, data: number[] }]
 * @param {string[]} labels  X-axis labels (one per data point).
 * @param {string}   mode    'line' | 'bar'
 */
export function LineBarChart({ series, labels, mode = 'bar', height = 240 }) {
  const [hidden, setHidden] = useState({});
  const active = series.filter((s) => !hidden[s.key]);

  const W = 760;
  const H = height;
  const padL = 34;
  const padR = 12;
  const padT = 12;
  const padB = 28;
  const innerW = W - padL - padR;
  const innerH = H - padT - padB;
  const n = labels.length;

  const max = Math.max(1, ...active.flatMap((s) => s.data));
  // "Nice" rounded max for the axis.
  const niceMax = niceCeil(max);
  const y = (v) => padT + innerH - (v / niceMax) * innerH;
  const colX = (i) => padL + (n <= 1 ? innerW / 2 : (i / (n - 1)) * innerW);
  const bandW = innerW / Math.max(1, n);

  const ticks = 4;
  const gridVals = Array.from({ length: ticks + 1 }, (_, i) => Math.round((niceMax / ticks) * i));
  const labelEvery = Math.max(1, Math.ceil(n / 8));

  return (
    <div>
      <div className="easyforms-rep-legend">
        {series.map((s) => (
          <button
            key={s.key}
            type="button"
            className={`easyforms-rep-legend__item${hidden[s.key] ? ' is-off' : ''}`}
            onClick={() => setHidden((h) => ({ ...h, [s.key]: !h[s.key] }))}
          >
            <span className="dot" style={{ background: s.color }} />
            {s.label}
          </button>
        ))}
      </div>
      <svg className="easyforms-rep-svg" viewBox={`0 0 ${W} ${H}`} role="img">
        {gridVals.map((v, i) => (
          <g key={i}>
            <line x1={padL} x2={W - padR} y1={y(v)} y2={y(v)} className="easyforms-rep-grid" />
            <text x={padL - 6} y={y(v) + 3} textAnchor="end" className="easyforms-rep-axis">{v}</text>
          </g>
        ))}

        {mode === 'bar'
          ? active.map((s, si) => {
            const group = active.length;
            const slot = bandW * 0.7;
            const bw = Math.max(2, slot / group);
            return s.data.map((v, i) => {
              const gx = padL + i * bandW + (bandW - slot) / 2 + si * bw;
              return <rect key={`${s.key}-${i}`} x={gx} y={y(v)} width={Math.max(1, bw - 1)} height={Math.max(0, padT + innerH - y(v))} rx={2} fill={s.color} />;
            });
          })
          : active.map((s) => (
            <g key={s.key}>
              <polyline
                fill="none"
                stroke={s.color}
                strokeWidth="2"
                strokeLinejoin="round"
                strokeLinecap="round"
                points={s.data.map((v, i) => `${colX(i)},${y(v)}`).join(' ')}
              />
              {n <= 31 && s.data.map((v, i) => <circle key={i} cx={colX(i)} cy={y(v)} r="2.5" fill={s.color} />)}
            </g>
          ))}

        {labels.map((l, i) => (i % labelEvery === 0 || i === n - 1) && (
          <text key={i} x={colX(i)} y={H - 8} textAnchor="middle" className="easyforms-rep-axis">{shortDay(l)}</text>
        ))}
      </svg>
    </div>
  );
}

/** Round up to a friendly axis maximum. */
function niceCeil(v) {
  if (v <= 5) return 5;
  const pow = Math.pow(10, Math.floor(Math.log10(v)));
  const step = pow / 2;
  return Math.ceil(v / step) * step;
}

/** Semicircular gauge for a 0–100 percentage. */
export function Gauge({ percentage = 0 }) {
  const W = 220;
  const H = 130;
  const cx = W / 2;
  const cy = H - 12;
  const r = 92;
  const a0 = Math.PI;
  const a1 = Math.PI - (percentage / 100) * Math.PI;
  const polar = (ang) => `${cx + r * Math.cos(ang)},${cy - r * Math.sin(ang)}`;
  const arc = (from, to) => `M ${polar(from)} A ${r} ${r} 0 0 1 ${polar(to)}`;
  return (
    <svg className="easyforms-rep-gauge" viewBox={`0 0 ${W} ${H}`} role="img">
      <path d={arc(a0, 0)} className="easyforms-rep-gauge__track" />
      <path d={arc(a0, a1)} className="easyforms-rep-gauge__fill" />
      <text x={cx} y={cy - 30} textAnchor="middle" className="easyforms-rep-gauge__pct">{percentage}</text>
      <text x={cx} y={cy - 12} textAnchor="middle" className="easyforms-rep-gauge__lbl">PERCENTAGE (%)</text>
    </svg>
  );
}

/** Horizontal bars for ranked items (top forms, countries). */
export function HBars({ items, color = 'var(--easyforms-color-primary, #4f46e5)', format }) {
  const max = Math.max(1, ...items.map((i) => i.value));
  return (
    <div className="easyforms-rep-hbars">
      {items.map((it, i) => (
        <div key={i} className="easyforms-rep-hbar">
          <div className="easyforms-rep-hbar__label" title={it.label}>{it.label}</div>
          <div className="easyforms-rep-hbar__track">
            <div className="easyforms-rep-hbar__fill" style={{ width: `${(it.value / max) * 100}%`, background: color }} />
          </div>
          <div className="easyforms-rep-hbar__val">{format ? format(it.value) : it.value}</div>
        </div>
      ))}
    </div>
  );
}

const DOW = ['SUN', 'MON', 'TUE', 'WED', 'THU', 'FRI', 'SAT'];

/**
 * Day-of-week × hour heatmap. `matrix` is 7 rows (Sun..Sat) × 24 hours.
 * `half` selects the AM (0–11) or PM (12–23) columns.
 */
export function Heatmap({ matrix, half = 'am' }) {
  const startH = half === 'pm' ? 12 : 0;
  const hours = Array.from({ length: 12 }, (_, i) => startH + i);
  const max = Math.max(1, ...matrix.flat());
  const hourLabel = (h) => {
    const ap = h < 12 ? 'AM' : 'PM';
    const hh = h % 12 === 0 ? 12 : h % 12;
    return `${hh} ${ap}`;
  };
  const shade = (v) => {
    if (!v) return 0;
    return Math.min(4, 1 + Math.floor((v / max) * 3.999));
  };
  return (
    <div className="easyforms-rep-heat">
      <table>
        <thead>
          <tr>
            <th className="easyforms-rep-heat__corner">Day</th>
            {hours.map((h) => <th key={h}>{hourLabel(h)}</th>)}
          </tr>
        </thead>
        <tbody>
          {matrix.map((row, di) => (
            <tr key={di}>
              <th>{DOW[di]}</th>
              {hours.map((h) => (
                <td key={h}>
                  <span className={`easyforms-rep-cell s${shade(row[h])}`} title={`${DOW[di]} ${hourLabel(h)} — ${row[h]}`} />
                </td>
              ))}
            </tr>
          ))}
        </tbody>
      </table>
      <div className="easyforms-rep-heat__legend">
        <span>Low</span>
        {[0, 1, 2, 3, 4].map((s) => <span key={s} className={`easyforms-rep-cell s${s}`} />)}
        <span>High</span>
      </div>
    </div>
  );
}
