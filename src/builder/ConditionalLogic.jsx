/**
 * Conditional-logic builder for a field. Edits the field's `conditional` object:
 *   { enabled, action: 'show'|'hide', logic: 'all'|'any', rules: [{field,operator,value}] }
 * Rules reference other input fields by key.
 */
import React from 'react';
import { Toggle } from '../components/ui';
import Icon from './icons.jsx';

const OPERATORS = [
  { value: 'equals', label: 'equals' },
  { value: 'not_equals', label: 'does not equal' },
  { value: 'contains', label: 'contains' },
  { value: 'not_contains', label: 'does not contain' },
  { value: 'starts_with', label: 'starts with' },
  { value: 'ends_with', label: 'ends with' },
  { value: 'greater_than', label: 'greater than' },
  { value: 'less_than', label: 'less than' },
  { value: 'empty', label: 'is empty' },
  { value: 'not_empty', label: 'is not empty' },
];

const VALUELESS = ['empty', 'not_empty'];
const EMPTY_RULE = { field: '', operator: 'equals', value: '' };

/**
 * @param {object} props
 * @param {object} props.conditional Current conditional object.
 * @param {Array}  props.fields      Selectable other input fields [{key,label}].
 * @param {Function} props.onChange  Receives the updated conditional object.
 */
export default function ConditionalLogic({ conditional, fields, onChange }) {
  const cond = conditional || { enabled: false, action: 'show', logic: 'all', rules: [] };
  const rules = cond.rules && cond.rules.length ? cond.rules : [{ ...EMPTY_RULE }];

  const patch = (changes) => onChange({ ...cond, ...changes });
  const setRule = (i, key, value) => {
    const next = rules.map((r, idx) => (idx === i ? { ...r, [key]: value } : r));
    patch({ rules: next });
  };
  const addRule = () => patch({ rules: [...rules, { ...EMPTY_RULE }] });
  const removeRule = (i) => patch({ rules: rules.filter((_, idx) => idx !== i) });

  return (
    <div className="radiusforms-cl">
      <div className="radiusforms-cl__switch">
        <div>
          <strong>Enable Conditional Logic</strong>
          <p>Show or hide this field based on other answers.</p>
        </div>
        <Toggle checked={!!cond.enabled} onChange={(v) => patch({ enabled: v })} />
      </div>

      {cond.enabled && (
        <div className="radiusforms-cl__body">
          <div className="radiusforms-cl__intro">
            <select
              className="radiusforms-select radiusforms-select--inline"
              value={cond.action || 'show'}
              onChange={(e) => patch({ action: e.target.value })}
            >
              <option value="show">Show</option>
              <option value="hide">Hide</option>
            </select>
            <span>this field if</span>
            <select
              className="radiusforms-select radiusforms-select--inline"
              value={cond.logic || 'all'}
              onChange={(e) => patch({ logic: e.target.value })}
            >
              <option value="all">all</option>
              <option value="any">any</option>
            </select>
            <span>of these match:</span>
          </div>

          <div className="radiusforms-cl__rules">
            {rules.map((rule, i) => (
              <div className="radiusforms-cl__rule" key={i}>
                <select
                  className="radiusforms-select"
                  value={rule.field}
                  onChange={(e) => setRule(i, 'field', e.target.value)}
                >
                  <option value="">— Select field —</option>
                  {fields.map((f) => <option key={f.key} value={f.key}>{f.label || f.key}</option>)}
                </select>
                <select
                  className="radiusforms-select"
                  value={rule.operator}
                  onChange={(e) => setRule(i, 'operator', e.target.value)}
                >
                  {OPERATORS.map((o) => <option key={o.value} value={o.value}>{o.label}</option>)}
                </select>
                {!VALUELESS.includes(rule.operator) && (
                  <input
                    className="radiusforms-input"
                    value={rule.value}
                    placeholder="Value"
                    onChange={(e) => setRule(i, 'value', e.target.value)}
                  />
                )}
                <button
                  type="button"
                  className="radiusforms-cl__rm"
                  title="Remove"
                  onClick={() => removeRule(i)}
                  disabled={rules.length === 1}
                >
                  <Icon name="close" size={14} />
                </button>
              </div>
            ))}
          </div>

          <button type="button" className="radiusforms-btn radiusforms-btn--sm" onClick={addRule}>
            <Icon name="plus" size={13} /> Add condition
          </button>

          {!fields.length && (
            <p className="radiusforms-cl__hint">Add other input fields first to build conditions.</p>
          )}
        </div>
      )}
    </div>
  );
}
