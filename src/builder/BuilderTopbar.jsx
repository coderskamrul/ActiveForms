/**
 * Builder top bar: navigation, title, undo/redo, responsive preview toggle,
 * shortcode chip, preview, and save. Designed to read cleaner than the
 * reference builders while exposing the same controls.
 */
import React, { useState } from 'react';
import Icon from './icons.jsx';
import { go } from '../router';
import { t } from '../config';

const DEVICES = [
  { key: 'desktop', icon: 'desktop', label: 'Desktop' },
  { key: 'tablet', icon: 'tablet', label: 'Tablet' },
  { key: 'mobile', icon: 'mobile', label: 'Mobile' },
];

/** Copy-to-clipboard shortcode chip. */
function ShortcodeChip({ formId }) {
  const [copied, setCopied] = useState(false);
  if (!formId) return null;
  const code = `[radiusforms id="${formId}"]`;

  const copy = () => {
    try {
      navigator.clipboard.writeText(code);
      setCopied(true);
      setTimeout(() => setCopied(false), 1400);
    } catch (e) { /* clipboard unavailable */ }
  };

  return (
    <button type="button" className="radiusforms-tb__chip" onClick={copy} title="Copy shortcode">
      <Icon name="code" size={14} />
      <code>{code}</code>
      {copied && <span className="radiusforms-tb__chip__ok"><Icon name="check" size={12} /></span>}
    </button>
  );
}

/** Builder top bar. */
export default function BuilderTopbar({
  title, onTitle, formId,
  canUndo, canRedo, onUndo, onRedo,
  device, onDevice,
  preview, onPreview,
  dirty, saving, onSave,
}) {
  return (
    <div className="radiusforms-tb">
      <div className="radiusforms-tb__left">
        <button type="button" className="radiusforms-tb__icon" title={t('back', 'Back')} onClick={() => go('/forms')}>
          <Icon name="back" />
        </button>
        <div className="radiusforms-tb__hist">
          <button type="button" className="radiusforms-tb__icon" title={t('undo', 'Undo')} disabled={!canUndo} onClick={onUndo}>
            <Icon name="undo" />
          </button>
          <button type="button" className="radiusforms-tb__icon" title={t('redo', 'Redo')} disabled={!canRedo} onClick={onRedo}>
            <Icon name="redo" />
          </button>
        </div>
        <input
          className="radiusforms-tb__title"
          value={title}
          onChange={(e) => onTitle(e.target.value)}
          aria-label="Form title"
          spellCheck={false}
        />
      </div>

      <div className="radiusforms-tb__center">
        <div className="radiusforms-tb__devices" role="group" aria-label="Preview width">
          {DEVICES.map((d) => (
            <button
              key={d.key}
              type="button"
              className={`radiusforms-tb__dev${device === d.key ? ' is-active' : ''}`}
              title={d.label}
              aria-pressed={device === d.key}
              onClick={() => onDevice(d.key)}
            >
              <Icon name={d.icon} size={15} />
            </button>
          ))}
        </div>
      </div>

      <div className="radiusforms-tb__right">
        <ShortcodeChip formId={formId} />
        {dirty && <span className="radiusforms-tb__dirty">{t('unsaved', 'Unsaved changes')}</span>}
        <button type="button" className={`radiusforms-tb__btn${preview ? ' is-active' : ''}`} onClick={onPreview}>
          <Icon name="eye" size={15} /> {t('previewDesign', 'Preview & Design')}
        </button>
        <button type="button" className="radiusforms-tb__btn radiusforms-tb__btn--primary" onClick={onSave} disabled={saving}>
          <Icon name={saving ? 'history' : 'check'} size={15} />
          {saving ? 'Saving…' : t('saveForm', 'Save Form')}
        </button>
      </div>
    </div>
  );
}
