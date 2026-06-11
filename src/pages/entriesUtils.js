/**
 * Shared helpers for the Entries list and detail views: turning a form's nested
 * field schema into a flat label map and rendering arbitrary response values as
 * readable text.
 */

/**
 * Flatten a form's field schema (containers/columns included) into an ordered
 * map of key => field, mirroring the server-side Arr::flatten_fields().
 * @param {Array} fields Form field schema.
 * @returns {Object<string, object>}
 */
export function flattenFields(fields) {
  const map = {};
  const walk = (list) => (list || []).forEach((f) => {
    if (!f || typeof f !== 'object') return;
    if (f.type === 'container') (f.columns || []).forEach((c) => walk(c.fields));
    else if (f.key) map[f.key] = f;
  });
  walk(fields);
  return map;
}

/**
 * Render a response value (string, array, or object) as display text.
 * @param {*} value Raw response value.
 * @returns {string}
 */
export function formatValue(value) {
  if (value === null || value === undefined || value === '') return '';
  if (Array.isArray(value)) return value.filter((v) => v !== '' && v != null).join(', ');
  if (typeof value === 'object') {
    return Object.values(value).filter((v) => v !== '' && v != null).join(', ');
  }
  return String(value);
}

/**
 * Status → badge tone + label, shared by the list and detail.
 * @param {string} status Entry status.
 * @returns {{tone: string, label: string}}
 */
export function statusMeta(status) {
  switch (status) {
    case 'unread': return { tone: 'unread', label: 'Unread' };
    case 'read': return { tone: 'read', label: 'Read' };
    case 'trashed': return { tone: 'trashed', label: 'Trashed' };
    default: return { tone: '', label: status || '—' };
  }
}
