/**
 * Renders a non-interactive preview of a field on the builder canvas,
 * mirroring how the PHP FormRenderer will display it on the frontend.
 */
import React from 'react';

/** Visible sub-fields of a composite (name/address), in order. */
function visibleSubs(field) {
  return (field.fields || []).filter((s) => s && s.visible);
}

/** A single composite sub-field mock (text input or country select). */
function SubControl({ sub }) {
  const ph = sub.placeholder || sub.label || '';
  const lp = sub.label_placement || 'top';
  const lpClass = ` easyforms-subfield--lp-${lp}`;
  return (
    <div className={`easyforms-subfield${lpClass}`} style={{ flex: '1 1 0', minWidth: 0 }}>
      {sub.type === 'country' ? (
        <select disabled><option>— Select Country —</option></select>
      ) : (
        <input placeholder={ph} readOnly />
      )}
      {lp !== 'hide' && (
        <small style={{ color: 'var(--_muted)', fontSize: 12 }}>
          {sub.label}{sub.required ? ' *' : ''}
        </small>
      )}
    </div>
  );
}

/** Chunk an array into groups of n. */
function chunk(arr, n) {
  const out = [];
  for (let i = 0; i < arr.length; i += n) out.push(arr.slice(i, i + n));
  return out;
}

/** Render the inner mock control for a field. */
function Control({ field }) {
  const ph = field.placeholder || '';
  switch (field.type) {
    case 'textarea':
      return <textarea rows={field.rows || 3} placeholder={ph} readOnly />;
    case 'select':
    case 'country':
      return (
        <select disabled>
          <option>{ph || '— Select —'}</option>
          {(field.options || []).map((o, i) => <option key={i}>{o.label}</option>)}
        </select>
      );
    case 'radio':
    case 'checkbox':
      return (
        <div>
          {(field.options || []).map((o, i) => (
            <label key={i} style={{ display: 'block', margin: '4px 0' }}>
              <input type={field.type === 'radio' ? 'radio' : 'checkbox'} disabled /> {o.label}
            </label>
          ))}
        </div>
      );
    case 'multiselect': {
      const opts = field.options || [];
      return (
        <div style={{ display: 'flex', flexWrap: 'wrap', gap: 6, alignItems: 'center', minHeight: 40, padding: '7px 32px 7px 10px', border: '1px solid #e5e7eb', borderRadius: 8, background: '#fff', position: 'relative', color: '#9ca3af' }}>
          {opts.length ? (
            opts.slice(0, 3).map((o, i) => (
              <span key={i} style={{ display: 'inline-flex', alignItems: 'center', gap: 6, background: '#eef2ff', color: '#4f46e5', borderRadius: 6, padding: '3px 8px', fontSize: 13, fontWeight: 600 }}>
                {o.label} <span aria-hidden="true">×</span>
              </span>
            ))
          ) : (
            <span>{ph || 'Select options…'}</span>
          )}
          <span style={{ position: 'absolute', right: 12 }} aria-hidden="true">▾</span>
        </div>
      );
    }
    case 'name': {
      const subs = visibleSubs(field);
      return (
        <div style={{ display: 'flex', gap: 8 }}>
          {(subs.length ? subs : [{ label: 'First', placeholder: 'First' }, { label: 'Last', placeholder: 'Last' }]).map((s, i) => (
            <SubControl key={s.key || i} sub={s} />
          ))}
        </div>
      );
    }
    case 'address': {
      const subs = visibleSubs(field);
      return (
        <div>
          {chunk(subs, 2).map((pair, r) => (
            <div key={r} style={{ display: 'flex', gap: 8, marginBottom: 8 }}>
              {pair.map((s, i) => <SubControl key={s.key || i} sub={s} />)}
            </div>
          ))}
        </div>
      );
    }
    case 'date_time':
      return <input type="date" readOnly />;
    case 'terms':
    case 'gdpr':
      return <label><input type="checkbox" disabled /> {field.content || 'I agree'}</label>;
    case 'number':
      return <input type="number" placeholder={ph} readOnly />;
    case 'email':
      return <input type="email" placeholder={ph} readOnly />;
    case 'url':
      return <input type="url" placeholder={ph || 'https://'} readOnly />;
    case 'password':
      return <input type="password" placeholder={ph} readOnly />;
    case 'masked_text':
      return <input type="text" placeholder={ph || field.mask || '(___) ___-____'} readOnly />;
    case 'phone':
      return <input type="tel" placeholder={ph || '+1 (555) 000-0000'} readOnly />;
    case 'range':
      return <input type="range" min={field.min ?? 0} max={field.max ?? 100} step={field.step ?? 1} readOnly style={{ padding: 0 }} />;
    case 'color':
      return <input type="color" defaultValue={field.default || '#4f46e5'} readOnly style={{ width: 56, height: 34, padding: 2 }} />;
    case 'rating':
      return (
        <div style={{ fontSize: 22, color: '#f59e0b', letterSpacing: 2 }} aria-hidden="true">
          {'★'.repeat(Math.max(1, field.max_rating || 5))}
        </div>
      );
    case 'nps':
      return (
        <div>
          <div style={{ display: 'flex', gap: 4 }} aria-hidden="true">
            {Array.from({ length: 11 }, (_, i) => (
              <span key={i} style={{ flex: 1, textAlign: 'center', padding: '6px 0', border: '1px solid var(--_border)', borderRadius: 6, fontSize: 12, fontWeight: 600 }}>{i}</span>
            ))}
          </div>
          <div style={{ display: 'flex', justifyContent: 'space-between', color: 'var(--_muted)', fontSize: 11, marginTop: 4 }}>
            <span>{field.low_label || 'Not likely'}</span>
            <span>{field.high_label || 'Very likely'}</span>
          </div>
        </div>
      );
    case 'signature':
      return (
        <div style={{ height: 90, border: '1px dashed var(--_border)', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--_muted)', fontStyle: 'italic' }}>
          ✍ Sign here
        </div>
      );
    case 'rich_text':
      return (
        <div>
          <div style={{ display: 'flex', gap: 2, padding: 4, background: '#f9fafb', border: '1px solid var(--_border)', borderBottom: 0, borderRadius: '6px 6px 0 0' }} aria-hidden="true">
            {['B', 'I', 'U', '••', '🔗'].map((b, i) => (
              <span key={i} style={{ minWidth: 24, height: 22, display: 'flex', alignItems: 'center', justifyContent: 'center', background: '#fff', border: '1px solid var(--_border)', borderRadius: 4, fontSize: 11 }}>{b}</span>
            ))}
          </div>
          <div style={{ minHeight: 56, border: '1px solid var(--_border)', borderRadius: '0 0 6px 6px', padding: 8, color: 'var(--_muted)' }}>{ph || 'Rich text…'}</div>
        </div>
      );
    case 'file_upload':
    case 'image_upload':
      return (
        <div style={{ minHeight: 64, border: '2px dashed var(--_border)', borderRadius: 6, display: 'flex', alignItems: 'center', justifyContent: 'center', color: 'var(--_muted)', background: '#f9fafb', fontSize: 13 }}>
          {field.type === 'image_upload' ? '🖼 Choose an image or drag it here' : '📎 Choose a file or drag it here'}
        </div>
      );
    case 'repeater':
      return (
        <div>
          <div style={{ display: 'flex', gap: 8 }}>
            {(field.columns || [{ label: 'Item' }, { label: 'Detail' }]).map((c, i) => (
              <input key={i} placeholder={c.label || c.key} readOnly style={{ flex: 1 }} />
            ))}
            <span style={{ width: 32, textAlign: 'center', color: 'var(--_muted)' }}>×</span>
          </div>
          <div style={{ color: 'var(--_muted)', fontSize: 12, marginTop: 6 }}>+ Add Row</div>
        </div>
      );
    default:
      return <input type="text" placeholder={ph} readOnly />;
  }
}

/** Field preview wrapper (handles layout/content fields too). */
export default function FieldPreview({ field }) {
  if (field.type === 'section') {
    return (
      <div>
        <h3 style={{ margin: '0 0 4px' }}>{field.label || 'Section'}</h3>
        {field.description && <p style={{ margin: 0, color: 'var(--_muted)' }}>{field.description}</p>}
      </div>
    );
  }
  if (field.type === 'html') {
    return <div style={{ color: 'var(--_muted)', fontStyle: 'italic' }}>Custom HTML block</div>;
  }
  if (field.type === 'submit') {
    const align = field.align || 'left';
    const justify = align === 'center' ? 'center' : align === 'right' ? 'flex-end' : 'flex-start';
    return (
      <div style={{ display: 'flex', justifyContent: justify }}>
        <span className="easyforms-btn easyforms-btn--primary">{field.label || 'Submit'}</span>
      </div>
    );
  }
  if (field.type === 'container') {
    return (
      <div style={{ display: 'flex', gap: 10 }}>
        {(field.columns || []).map((c, i) => (
          <div key={i} style={{ flex: c.width || 50, border: '1px dashed var(--_border)', borderRadius: 6, padding: 10, color: 'var(--_muted)', fontSize: 12 }}>
            Column ({c.width || 50}%)
          </div>
        ))}
      </div>
    );
  }

  const lp = field.label_placement || 'top';
  return (
    <div className={`easyforms-mock easyforms-mock--lp-${lp}`}>
      {lp !== 'hide' && (
        <span className="fld-label">
          {field.label || field.type}
          {field.required && <span className="fld-req"> *</span>}
        </span>
      )}
      <Control field={field} />
      {field.help && <div style={{ color: 'var(--_muted)', fontSize: 12, marginTop: 4 }}>{field.help}</div>}
    </div>
  );
}
