/**
 * "Input Customization" tab — per-field editor. Settings are organized into
 * collapsible sections (General, Options, Validation,
 * Advanced) and adapt to the selected field's type.
 */
import React, { useState } from 'react';
import { Toggle } from '../components/ui';
import Icon from './icons.jsx';
import config from '../config';

const COUNTRIES = (config && config.countries) || {};
const LABEL_PLACEMENTS = [
  ['top', 'Top'],
  ['right', 'Right'],
  ['bottom', 'Bottom'],
  ['left', 'Left'],
  ['hide', 'Hide Label'],
];

const NO_PLACEHOLDER = ['radio', 'checkbox', 'terms', 'gdpr', 'section', 'html', 'submit', 'container', 'nps', 'signature', 'file_upload', 'image_upload', 'repeater', 'rating', 'range', 'color', 'name', 'address'];

// Date/Time field configuration. Values are flatpickr-style format tokens that
// the frontend picker (form.js) understands; labels show a worked example.
const DATETIME_MODES = [
  ['date', 'Date Only'],
  ['time', 'Time Only'],
  ['datetime', 'Date & Time'],
  ['range', 'Date Range'],
];
const DATE_FORMATS = [
  ['m/d/Y', 'm/d/Y (04/28/2018)'],
  ['d/m/Y', 'd/m/Y (28/04/2018)'],
  ['d.m.Y', 'd.m.Y (28.04.2019)'],
  ['n/j/y', 'n/j/y (4/28/18)'],
  ['m/d/y', 'm/d/y (04/28/18)'],
  ['M/d/Y', 'M/d/Y (Apr/28/2018)'],
  ['y/m/d', 'y/m/d (18/04/28)'],
  ['Y-m-d', 'Y-m-d (2018-04-28)'],
  ['d-M-y', 'd-M-y (28-Apr-18)'],
];
const DATETIME_FORMATS = [
  ['m/d/Y h:i K', 'm/d/Y h:i K (04/28/2018 08:55 PM)'],
  ['m/d/Y H:i', 'm/d/Y H:i (04/28/2018 20:55)'],
  ['d/m/Y h:i K', 'd/m/Y h:i K (28/04/2018 08:55 PM)'],
  ['d/m/Y H:i', 'd/m/Y H:i (28/04/2018 20:55)'],
  ['d.m.Y h:i K', 'd.m.Y h:i K (28.04.2019 08:55 PM)'],
  ['d.m.Y H:i', 'd.m.Y H:i (28.04.2019 20:55)'],
];
const TIME_FORMATS = [
  ['h:i K', 'h:i K (08:55 PM)'],
  ['H:i', 'H:i (20:55)'],
];
const CHOICE_TYPES = ['select', 'radio', 'checkbox', 'multiselect'];
// Fields where a free-text "Default value" makes no sense.
const NO_DEFAULT = ['nps', 'signature', 'file_upload', 'image_upload', 'repeater', 'rating', 'range', 'color', 'name', 'address', 'multiselect'];
const UPLOAD_TYPES = ['file_upload', 'image_upload'];
// Grouped fields whose value is an object of toggleable sub-fields.
const COMPOSITE_TYPES = ['name', 'address'];
const AUTOCOMPLETE_PROVIDERS = [
  { value: 'none', label: 'None' },
  { value: 'google', label: 'Google Places (Pro)' },
  { value: 'html5', label: 'Browser geolocation' },
];

/** Collapsible section. */
function Section({ title, children, open: initial = true }) {
  const [open, setOpen] = useState(initial);
  return (
    <div className={`radiusforms-sec${open ? ' is-open' : ''}`}>
      <button type="button" className="radiusforms-sec__head" onClick={() => setOpen((o) => !o)}>
        <span>{title}</span>
        <Icon name={open ? 'chevronDown' : 'chevronRight'} size={15} />
      </button>
      {open && <div className="radiusforms-sec__body">{children}</div>}
    </div>
  );
}

/** Labeled text input row. */
function Row({ label, hint, children }) {
  return (
    <div className="radiusforms-form-row">
      {label && <label>{label}</label>}
      {children}
      {hint && <div className="radiusforms-help-text">{hint}</div>}
    </div>
  );
}

/** Options editor for choice fields. */
function OptionsEditor({ options, onChange }) {
  const list = options || [];
  const update = (i, key, value) => onChange(list.map((o, idx) => (idx === i ? { ...o, [key]: value } : o)));
  const add = () => onChange([...list, { label: `Option ${list.length + 1}`, value: `option_${list.length + 1}` }]);
  const remove = (i) => onChange(list.filter((_, idx) => idx !== i));

  return (
    <div className="radiusforms-opts">
      {list.map((o, i) => (
        <div className="radiusforms-opts__row" key={i}>
          <Icon name="drag" size={14} />
          <input className="radiusforms-input" value={o.label} onChange={(e) => update(i, 'label', e.target.value)} placeholder="Label" />
          <input className="radiusforms-input radiusforms-input--val" value={o.value ?? ''} onChange={(e) => update(i, 'value', e.target.value)} placeholder="Value" />
          <button type="button" className="radiusforms-opts__rm" onClick={() => remove(i)} title="Remove"><Icon name="close" size={13} /></button>
        </div>
      ))}
      <button type="button" className="radiusforms-btn radiusforms-btn--sm" onClick={add}><Icon name="plus" size={13} /> Add Option</button>
    </div>
  );
}

/** Label-placement segmented control (used per field AND per sub-field). */
function LabelPlacementPicker({ value, onChange }) {
  return (
    <div className="radiusforms-form-row">
      <label>Label Placement</label>
      <div className="radiusforms-seg__group radiusforms-seg__group--wrap">
        {LABEL_PLACEMENTS.map(([v, l]) => (
          <button key={v} type="button" className={`radiusforms-seg__btn${(value || 'top') === v ? ' is-active' : ''}`} onClick={() => onChange(v)}>{l}</button>
        ))}
      </div>
    </div>
  );
}

/** On/off segmented control. */
function OnOff({ label, value, onChange }) {
  return (
    <div className="radiusforms-seg">
      <span className="radiusforms-seg__label">{label}</span>
      <div className="radiusforms-seg__group">
        <button type="button" className={`radiusforms-seg__btn${value ? ' is-active' : ''}`} onClick={() => onChange(true)}>On</button>
        <button type="button" className={`radiusforms-seg__btn${!value ? ' is-active' : ''}`} onClick={() => onChange(false)}>Off</button>
      </div>
    </div>
  );
}

/**
 * Country list/flag/search options. `value` is the field (or sub-field) object;
 * `onChange` receives a partial patch to merge.
 */
function CountryOptions({ value, onChange }) {
  const mode = value.country_list_mode || 'all';
  const list = value.country_list || [];
  return (
    <>
      <div className="radiusforms-seg">
        <span className="radiusforms-seg__label">Country List</span>
        <div className="radiusforms-seg__group">
          {[['all', 'All'], ['include', 'Show selected'], ['exclude', 'Hide selected']].map(([v, l]) => (
            <button key={v} type="button" className={`radiusforms-seg__btn${mode === v ? ' is-active' : ''}`} onClick={() => onChange({ country_list_mode: v })}>{l}</button>
          ))}
        </div>
      </div>
      {mode !== 'all' && (
        <Row label={mode === 'include' ? 'Countries to show' : 'Countries to hide'} hint="Ctrl / Cmd-click to select multiple.">
          <select
            multiple
            className="radiusforms-input"
            style={{ height: 170 }}
            value={list}
            onChange={(e) => onChange({ country_list: Array.from(e.target.selectedOptions).map((o) => o.value) })}
          >
            {Object.entries(COUNTRIES).map(([code, name]) => <option key={code} value={code}>{name}</option>)}
          </select>
        </Row>
      )}
      <OnOff label="Show country flags" value={!!value.show_flags} onChange={(v) => onChange({ show_flags: v })} />
      <OnOff label="Searchable" value={!!value.searchable} onChange={(v) => onChange({ searchable: v })} />
    </>
  );
}

/**
 * Sub-field editor for grouped fields (Name / Address). Each row can be toggled
 * visible, expanded to edit its label/placeholder/required, and — when
 * reorderable — dragged to change order (native HTML5 drag, no extra deps).
 */
function SubFieldsEditor({ fields, onChange, reorderable = false }) {
  const list = fields || [];
  const [openKey, setOpenKey] = useState(null);
  const [dragIndex, setDragIndex] = useState(null);

  const patch = (i, key, value) => onChange(list.map((s, idx) => (idx === i ? { ...s, [key]: value } : s)));
  const patchMany = (i, obj) => onChange(list.map((s, idx) => (idx === i ? { ...s, ...obj } : s)));

  const move = (from, to) => {
    if (from === to || from == null || to == null) return;
    const next = list.slice();
    const [moved] = next.splice(from, 1);
    next.splice(to, 0, moved);
    onChange(next);
  };

  return (
    <div className="radiusforms-subfe">
      {list.map((sub, i) => {
        const open = openKey === sub.key;
        return (
          <div
            key={sub.key || i}
            className={`radiusforms-subfe__item${dragIndex === i ? ' is-dragging' : ''}`}
            draggable={reorderable}
            onDragStart={reorderable ? () => setDragIndex(i) : undefined}
            onDragOver={reorderable ? (e) => e.preventDefault() : undefined}
            onDrop={reorderable ? () => { move(dragIndex, i); setDragIndex(null); } : undefined}
            onDragEnd={reorderable ? () => setDragIndex(null) : undefined}
          >
            <div className="radiusforms-subfe__head">
              {reorderable && <span className="radiusforms-subfe__handle" title="Drag to reorder"><Icon name="drag" size={14} /></span>}
              <label className="radiusforms-subfe__toggle">
                <input type="checkbox" checked={!!sub.visible} onChange={(e) => patch(i, 'visible', e.target.checked)} />
                <span className={sub.visible ? 'is-on' : ''}>{sub.label || sub.key}</span>
              </label>
              <button type="button" className="radiusforms-subfe__caret" onClick={() => setOpenKey(open ? null : sub.key)} aria-expanded={open}>
                <Icon name={open ? 'chevronDown' : 'chevronRight'} size={14} />
              </button>
            </div>
            {open && (
              <div className="radiusforms-subfe__body">
                <Row label="Label"><input className="radiusforms-input" value={sub.label || ''} onChange={(e) => patch(i, 'label', e.target.value)} /></Row>
                <LabelPlacementPicker value={sub.label_placement} onChange={(v) => patch(i, 'label_placement', v)} />
                {sub.type !== 'country' && (
                  <Row label="Placeholder"><input className="radiusforms-input" value={sub.placeholder || ''} onChange={(e) => patch(i, 'placeholder', e.target.value)} /></Row>
                )}
                <div className="radiusforms-seg">
                  <span className="radiusforms-seg__label">Required</span>
                  <div className="radiusforms-seg__group">
                    <button type="button" className={`radiusforms-seg__btn${sub.required ? ' is-active' : ''}`} onClick={() => patch(i, 'required', true)}>Yes</button>
                    <button type="button" className={`radiusforms-seg__btn${!sub.required ? ' is-active' : ''}`} onClick={() => patch(i, 'required', false)}>No</button>
                  </div>
                </div>
                {sub.type === 'country' && <CountryOptions value={sub} onChange={(p) => patchMany(i, p)} />}
              </div>
            )}
          </div>
        );
      })}
    </div>
  );
}

/** Sub-column editor for the repeater field. */
function RepeaterColumnsEditor({ columns, onChange }) {
  const list = columns && columns.length ? columns : [{ key: 'col_1', label: 'Item' }];
  const slug = (label, i) => (label || `col_${i + 1}`).toLowerCase().replace(/[^a-z0-9]+/g, '_').replace(/^_+|_+$/g, '') || `col_${i + 1}`;
  const update = (i, label) => onChange(list.map((c, idx) => (idx === i ? { key: c.key || slug(label, i), label } : c)));
  const add = () => onChange([...list, { key: `col_${list.length + 1}`, label: `Column ${list.length + 1}` }]);
  const remove = (i) => onChange(list.length > 1 ? list.filter((_, idx) => idx !== i) : list);

  return (
    <div className="radiusforms-opts">
      {list.map((c, i) => (
        <div className="radiusforms-opts__row" key={i}>
          <Icon name="drag" size={14} />
          <input className="radiusforms-input" value={c.label} onChange={(e) => update(i, e.target.value)} placeholder="Column label" />
          <button type="button" className="radiusforms-opts__rm" onClick={() => remove(i)} title="Remove"><Icon name="close" size={13} /></button>
        </div>
      ))}
      <button type="button" className="radiusforms-btn radiusforms-btn--sm" onClick={add}><Icon name="plus" size={13} /> Add Column</button>
    </div>
  );
}

/** Column layout editor for container fields. */
function ColumnsEditor({ field, set }) {
  const cols = field.columns || [];
  const count = cols.length || 2;

  const setCount = (n) => {
    let next = cols.slice(0, n).map((c) => ({ ...c }));
    if (n < cols.length) {
      const orphan = cols.slice(n).flatMap((c) => c.fields || []);
      if (orphan.length) {
        const last = next[next.length - 1];
        last.fields = [...(last.fields || []), ...orphan];
      }
    }
    while (next.length < n) next.push({ width: 0, fields: [] });
    const w = Math.floor(100 / n);
    next = next.map((c, i) => ({ ...c, width: i === n - 1 ? 100 - w * (n - 1) : w }));
    set('columns', next);
  };

  const setWidth = (i, v) => set('columns', cols.map((c, idx) => (idx === i ? { ...c, width: Number(v) || 0 } : c)));

  return (
    <>
      <Row label="Number of columns">
        <div className="radiusforms-seg__group">
          {[1, 2, 3, 4].map((n) => (
            <button key={n} type="button" className={`radiusforms-seg__btn${count === n ? ' is-active' : ''}`} onClick={() => setCount(n)}>{n}</button>
          ))}
        </div>
      </Row>
      <Row label="Column widths (%)" hint="Should add up to roughly 100%.">
        <div className="radiusforms-colw">
          {cols.map((c, i) => (
            <input key={i} className="radiusforms-input" type="number" min="10" max="100" value={c.width || 0} onChange={(e) => setWidth(i, e.target.value)} />
          ))}
        </div>
      </Row>
    </>
  );
}

/** Required toggle + custom error message. */
function RequiredEditor({ field, set }) {
  return (
    <>
      <div className="radiusforms-seg">
        <span className="radiusforms-seg__label">Required</span>
        <div className="radiusforms-seg__group">
          <button type="button" className={`radiusforms-seg__btn${field.required ? ' is-active' : ''}`} onClick={() => set('required', true)}>Yes</button>
          <button type="button" className={`radiusforms-seg__btn${!field.required ? ' is-active' : ''}`} onClick={() => set('required', false)}>No</button>
        </div>
      </div>
      {field.required && (
        <Row label="Custom Error Message" hint="Shown when the field is left empty.">
          <input className="radiusforms-input" value={field.required_message || ''} placeholder="This field is required." onChange={(e) => set('required_message', e.target.value)} />
        </Row>
      )}
    </>
  );
}

/** Settings panel. */
export default function SettingsPanel({ field, definition, onChange, allFields = [] }) {
  if (!field) {
    return (
      <div className="radiusforms-insp-empty">
        <Icon name="sliders" size={28} />
        <p>Select a field on the canvas to customize it.</p>
      </div>
    );
  }

  const set = (key, value) => onChange({ ...field, [key]: value }, `field:${key}`);
  const type = field.type;
  const isInput = definition ? definition.isInput : true;
  const isChoice = CHOICE_TYPES.includes(type) || Array.isArray(field.options);

  return (
    <div className="radiusforms-insp">
      <div className="radiusforms-insp__title">
        <span className={`dashicons dashicons-${(definition && definition.icon) || 'forms'}`} aria-hidden="true" />
        {(definition && definition.label) || type}
      </div>

      {type === 'container' && (
        <Section title="Column Layout">
          <ColumnsEditor field={field} set={set} />
        </Section>
      )}

      {type !== 'container' && (
      <Section title="General">
        {type === 'html' ? (
          <Row label="HTML Content">
            <textarea className="radiusforms-textarea" rows={6} value={field.content || ''} onChange={(e) => set('content', e.target.value)} />
          </Row>
        ) : (type === 'terms' || type === 'gdpr') ? (
          <Row label="Consent Text" hint="Supports basic HTML for links.">
            <textarea className="radiusforms-textarea" rows={3} value={field.content || ''} onChange={(e) => set('content', e.target.value)} />
          </Row>
        ) : (
          <>
            <Row label={type === 'submit' ? 'Button Label' : 'Element Label'}>
              <input className="radiusforms-input" value={field.label || ''} onChange={(e) => set('label', e.target.value)} />
            </Row>
            {isInput && (
              <Row label="Admin Field Label" hint="Used in entries & exports. Defaults to the label.">
                <input className="radiusforms-input" value={field.admin_label || ''} placeholder={field.label || ''} onChange={(e) => set('admin_label', e.target.value)} />
              </Row>
            )}
            {type === 'section' && (
              <Row label="Description">
                <input className="radiusforms-input" value={field.description || ''} onChange={(e) => set('description', e.target.value)} />
              </Row>
            )}
            {!NO_PLACEHOLDER.includes(type) && (
              <Row label="Placeholder">
                <input className="radiusforms-input" value={field.placeholder || ''} onChange={(e) => set('placeholder', e.target.value)} />
              </Row>
            )}
            {isInput && (
              <>
                <Row label="Help Text">
                  <input className="radiusforms-input" value={field.help || ''} onChange={(e) => set('help', e.target.value)} />
                </Row>
                {!NO_DEFAULT.includes(type) && (
                  <Row label="Default Value">
                    <input className="radiusforms-input" value={field.default || ''} onChange={(e) => set('default', e.target.value)} />
                  </Row>
                )}
              </>
            )}
            {type === 'submit' && (
              <Row label="Alignment">
                <div className="radiusforms-seg__group">
                  {['left', 'center', 'right'].map((a) => (
                    <button key={a} type="button" className={`radiusforms-seg__btn${(field.align || 'left') === a ? ' is-active' : ''}`} onClick={() => set('align', a)}>{a}</button>
                  ))}
                </div>
              </Row>
            )}
          </>
        )}
      </Section>
      )}

      {type === 'name' && (
        <Section title="Name Fields">
          <SubFieldsEditor fields={field.fields || []} onChange={(f) => set('fields', f)} />
        </Section>
      )}

      {type === 'address' && (
        <Section title="Address Fields">
          <SubFieldsEditor fields={field.fields || []} onChange={(f) => set('fields', f)} reorderable />
          <Row label="Autocomplete Provider" hint="Address autocomplete. Google Places requires RadiusForms Pro.">
            <select className="radiusforms-input radiusforms-select" value={field.autocomplete_provider || 'none'} onChange={(e) => set('autocomplete_provider', e.target.value)}>
              {AUTOCOMPLETE_PROVIDERS.map((p) => <option key={p.value} value={p.value}>{p.label}</option>)}
            </select>
          </Row>
        </Section>
      )}

      {isChoice && !COMPOSITE_TYPES.includes(type) && (
        <Section title="Options">
          <OptionsEditor options={field.options || []} onChange={(opts) => set('options', opts)} />
        </Section>
      )}

      {type === 'number' && (
        <Section title="Number Range">
          <div className="radiusforms-row-2">
            <Row label="Min"><input className="radiusforms-input" value={field.min || ''} onChange={(e) => set('min', e.target.value)} /></Row>
            <Row label="Max"><input className="radiusforms-input" value={field.max || ''} onChange={(e) => set('max', e.target.value)} /></Row>
            <Row label="Step"><input className="radiusforms-input" value={field.step || ''} onChange={(e) => set('step', e.target.value)} /></Row>
          </div>
        </Section>
      )}

      {type === 'date_time' && (
        <Section title="Date & Time">
          <Row label="Field Mode" hint="What this field collects. Only the relevant format is shown below.">
            <select className="radiusforms-input radiusforms-select" value={field.mode || 'date'} onChange={(e) => set('mode', e.target.value)}>
              {DATETIME_MODES.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
            </select>
          </Row>
          {field.mode === 'time' ? (
            <Row label="Time Format">
              <select className="radiusforms-input radiusforms-select" value={field.time_format || 'h:i K'} onChange={(e) => set('time_format', e.target.value)}>
                {TIME_FORMATS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
            </Row>
          ) : field.mode === 'datetime' ? (
            <Row label="Date & Time Format">
              <select className="radiusforms-input radiusforms-select" value={field.datetime_format || 'm/d/Y h:i K'} onChange={(e) => set('datetime_format', e.target.value)}>
                {DATETIME_FORMATS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
            </Row>
          ) : (
            <Row label={field.mode === 'range' ? 'Date Range Format' : 'Date Format'} hint={field.mode === 'range' ? 'Applied to both the start and end dates.' : undefined}>
              <select className="radiusforms-input radiusforms-select" value={field.date_format || 'm/d/Y'} onChange={(e) => set('date_format', e.target.value)}>
                {DATE_FORMATS.map(([v, l]) => <option key={v} value={v}>{l}</option>)}
              </select>
            </Row>
          )}
          {field.mode === 'range' && (
            <Row hint="Pick a start date, then an end date — both appear in this one field." />
          )}
        </Section>
      )}

      {type === 'textarea' && (
        <Section title="Appearance">
          <Row label="Rows"><input className="radiusforms-input" type="number" value={field.rows || 3} onChange={(e) => set('rows', e.target.value)} /></Row>
          <div className="radiusforms-seg">
            <span className="radiusforms-seg__label">Auto-resize</span>
            <div className="radiusforms-seg__group">
              <button type="button" className={`radiusforms-seg__btn${field.auto_resize ? ' is-active' : ''}`} onClick={() => set('auto_resize', true)}>On</button>
              <button type="button" className={`radiusforms-seg__btn${!field.auto_resize ? ' is-active' : ''}`} onClick={() => set('auto_resize', false)}>Off</button>
            </div>
          </div>
        </Section>
      )}

      {type === 'masked_text' && (
        <Section title="Input Mask">
          <Row label="Mask Pattern" hint="9 = digit, a = letter, * = any. e.g. (999) 999-9999">
            <input className="radiusforms-input radiusforms-input--mono" value={field.mask || ''} placeholder="(999) 999-9999" onChange={(e) => set('mask', e.target.value)} />
          </Row>
        </Section>
      )}

      {type === 'rich_text' && (
        <Section title="Appearance">
          <Row label="Editor Height (rows)"><input className="radiusforms-input" type="number" min="2" value={field.rows || 5} onChange={(e) => set('rows', e.target.value)} /></Row>
        </Section>
      )}

      {type === 'nps' && (
        <Section title="Scale Labels">
          <div className="radiusforms-row-2" style={{ gridTemplateColumns: '1fr 1fr' }}>
            <Row label="Low Label" hint="Shown under 0."><input className="radiusforms-input" value={field.low_label ?? ''} placeholder="Not likely" onChange={(e) => set('low_label', e.target.value)} /></Row>
            <Row label="High Label" hint="Shown under 10."><input className="radiusforms-input" value={field.high_label ?? ''} placeholder="Very likely" onChange={(e) => set('high_label', e.target.value)} /></Row>
          </div>
        </Section>
      )}

      {UPLOAD_TYPES.includes(type) && (
        <Section title="Upload Rules">
          <Row label="Allowed File Types" hint="Comma-separated extensions, e.g. pdf, jpg, png. Leave blank for defaults.">
            <input className="radiusforms-input" value={Array.isArray(field.allowed_types) ? field.allowed_types.join(', ') : (field.allowed_types || '')} onChange={(e) => set('allowed_types', e.target.value.split(',').map((s) => s.trim()).filter(Boolean))} />
          </Row>
          <div className="radiusforms-row-2" style={{ gridTemplateColumns: '1fr 1fr' }}>
            <Row label="Max Size (KB)"><input className="radiusforms-input" type="number" min="1" value={field.max_size || 5120} onChange={(e) => set('max_size', Number(e.target.value) || 0)} /></Row>
            <Row label="Max Files"><input className="radiusforms-input" type="number" min="1" value={field.max_files || 1} onChange={(e) => set('max_files', Math.max(1, Number(e.target.value) || 1))} /></Row>
          </div>
        </Section>
      )}

      {type === 'repeater' && (
        <Section title="Columns">
          <RepeaterColumnsEditor columns={field.columns || []} onChange={(cols) => set('columns', cols)} />
          <Row label="Max Rows" hint="0 = unlimited."><input className="radiusforms-input" type="number" min="0" value={field.max_rows || 0} onChange={(e) => set('max_rows', Math.max(0, Number(e.target.value) || 0))} /></Row>
        </Section>
      )}

      {type === 'country' && (
        <Section title="Country Options">
          <CountryOptions value={field} onChange={(p) => onChange({ ...field, ...p }, 'field:country')} />
        </Section>
      )}

      {isInput && !COMPOSITE_TYPES.includes(type) && (
        <Section title="Validation">
          <RequiredEditor field={field} set={set} />
        </Section>
      )}

      <Section title="Advanced" open={false}>
        {type !== 'html' && type !== 'hidden' && (
          <LabelPlacementPicker value={field.label_placement} onChange={(v) => set('label_placement', v)} />
        )}
        <Row label="CSS Class" hint="Space-separated classes added to the field wrapper.">
          <input className="radiusforms-input" value={field.css_class || ''} onChange={(e) => set('css_class', e.target.value)} />
        </Row>
        {isInput && (
          <Row label="Field Key" hint="Stable identifier used in smart codes & exports.">
            <input className="radiusforms-input radiusforms-input--mono" value={field.key || ''} readOnly />
          </Row>
        )}
      </Section>
    </div>
  );
}
