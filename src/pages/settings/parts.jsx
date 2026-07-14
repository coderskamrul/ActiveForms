/**
 * Presentational building blocks for the Settings experience.
 *
 * These are intentionally dumb: a Section is a titled card and a Row pairs a
 * label/description block with a control. New settings are added by
 * dropping more <Row>s into a <Section> — no layout plumbing required.
 */
import React from 'react';
import { Badge } from '../../components/ui';

/** Render a badge from a {tone,label} descriptor. */
function Mark({ badge }) {
  return badge ? <Badge tone={badge.tone}>{badge.label}</Badge> : null;
}

/** A titled, optionally-badged card grouping related rows. */
export function Section({ title, description, badge, locked = false, children }) {
  return (
    <section className={`radiusforms-set-section${locked ? ' is-locked' : ''}`}>
      <header className="radiusforms-set-section__head">
        <div className="radiusforms-set-section__head-top">
          <h3>{title}</h3>
          <Mark badge={badge} />
        </div>
        {description && <p>{description}</p>}
      </header>
      <div className="radiusforms-set-section__body">{children}</div>
    </section>
  );
}

/**
 * A single setting. `stacked` puts the control on its own line (for wide
 * inputs/textareas); `toggle` right-aligns a compact control; `disabled` dims
 * the row for not-yet-available features.
 */
export function Row({ title, description, badge, disabled = false, stacked = false, toggle = false, children }) {
  const cls = [
    'radiusforms-set-row',
    disabled && 'is-disabled',
    stacked && 'is-stacked',
  ].filter(Boolean).join(' ');
  return (
    <div className={cls}>
      <div className="radiusforms-set-row__label">
        <span className="radiusforms-set-row__title">{title}<Mark badge={badge} /></span>
        {description && <span className="radiusforms-set-row__desc">{description}</span>}
      </div>
      <div className={`radiusforms-set-row__control${toggle ? ' radiusforms-set-row__control--toggle' : ''}`}>
        {children}
      </div>
    </div>
  );
}

/** A small inline informational callout shown inside a section body. */
export function Note({ icon = 'info-outline', children }) {
  return (
    <div className="radiusforms-set-note">
      <span className={`dashicons dashicons-${icon}`} aria-hidden="true" />
      <div>{children}</div>
    </div>
  );
}

/** Segmented (single-choice) control — a compact alternative to radios. */
export function Segmented({ value, onChange, options, disabled = false }) {
  return (
    <div className="radiusforms-seg" role="group">
      {options.map((o) => (
        <button
          key={o.value}
          type="button"
          className={value === o.value ? 'is-active' : ''}
          disabled={disabled}
          onClick={() => onChange(o.value)}
        >
          {o.label}
        </button>
      ))}
    </div>
  );
}

/** A native select bound to value/onChange with an options array. */
export function Select({ value, onChange, options, disabled = false }) {
  return (
    <select className="radiusforms-select" value={value} disabled={disabled} onChange={(e) => onChange(e.target.value)}>
      {options.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
    </select>
  );
}
