/**
 * Reusable UI primitives shared across pages. Each consumes design tokens via
 * the CSS classes defined in the theme, keeping styling centralized.
 */
import React from 'react';

/** Spinner centered in its container. */
export function Loading() {
  return (
    <div className="activeforms-center"><div className="activeforms-spinner" /></div>
  );
}

/** Empty state block. */
export function Empty({ icon = '📋', title, children }) {
  return (
    <div className="activeforms-empty">
      <div className="ico">{icon}</div>
      <h3>{title}</h3>
      {children && <p>{children}</p>}
    </div>
  );
}

/** Button. */
export function Button({ variant = '', size = '', children, ...rest }) {
  const cls = ['activeforms-btn', variant && `activeforms-btn--${variant}`, size && `activeforms-btn--${size}`].filter(Boolean).join(' ');
  return <button type="button" className={cls} {...rest}>{children}</button>;
}

/** Card wrapper. */
export function Card({ pad = true, children, ...rest }) {
  return <div className="activeforms-card" {...rest}>{pad ? <div className="activeforms-card-pad">{children}</div> : children}</div>;
}

/** Page header. */
export function PageHead({ title, subtitle, actions }) {
  return (
    <div className="activeforms-page-head">
      <div>
        <h1>{title}</h1>
        {subtitle && <p>{subtitle}</p>}
      </div>
      {actions && <div style={{ display: 'flex', gap: 8 }}>{actions}</div>}
    </div>
  );
}

/** Toggle switch. */
export function Toggle({ checked, onChange }) {
  return (
    <label className="activeforms-toggle">
      <input type="checkbox" checked={!!checked} onChange={(e) => onChange(e.target.checked)} />
      <span className="track" />
    </label>
  );
}

/** Labeled text/select/textarea field. */
export function Field({ label, help, children }) {
  return (
    <div className="activeforms-form-row">
      {label && <label>{label}</label>}
      {children}
      {help && <div className="activeforms-help-text">{help}</div>}
    </div>
  );
}

/** Text input bound to value/onChange. */
export function Text({ value, onChange, ...rest }) {
  return <input className="activeforms-input" value={value ?? ''} onChange={(e) => onChange(e.target.value)} {...rest} />;
}

/**
 * Status/label pill. `tone` maps to a color treatment; falls back to neutral.
 */
export function Badge({ tone = '', children, ...rest }) {
  const cls = ['activeforms-badge', tone && `activeforms-badge--${tone}`].filter(Boolean).join(' ');
  return <span className={cls} {...rest}>{children}</span>;
}
