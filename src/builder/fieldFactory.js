/**
 * Builds new field instances for the canvas from palette definitions, assigning
 * a stable unique key derived from the type.
 */
let counter = 0;

/**
 * Generate a unique field key.
 * @param {string} type Field type.
 * @returns {string}
 */
export function uniqueKey(type) {
  counter += 1;
  const rand = Math.random().toString(36).slice(2, 6);
  return `${type}_${Date.now().toString(36).slice(-4)}${counter}${rand}`.toLowerCase();
}

/**
 * Deep clone a plain object/array (schema is JSON-safe).
 * @param {any} value Value to clone.
 * @returns {any}
 */
export function clone(value) {
  return JSON.parse(JSON.stringify(value ?? null));
}

/**
 * Create a canvas field instance from a definition.
 * @param {object} def Field definition from the registry.
 * @returns {object}
 */
export function makeField(def) {
  const schema = clone(def.schema || {});
  schema.type = def.type;
  if (def.isInput) {
    schema.key = uniqueKey(def.type);
  }
  // Column-container presets (One/Two/Three… column) set their column count.
  if (schema.type === 'container' && def.presetColumns) {
    const n = Math.max(1, Math.min(6, def.presetColumns));
    const w = Math.floor(100 / n);
    schema.columns = Array.from({ length: n }, (_, i) => ({
      width: i === n - 1 ? 100 - w * (n - 1) : w,
      fields: [],
    }));
  }
  return schema;
}
