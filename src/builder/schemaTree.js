/**
 * Immutable helpers for the builder field tree.
 *
 * The tree is an array of field nodes. A `container` node holds
 * `columns: [{ width, fields: [...] }]`; nested fields live one level deep
 * inside columns (containers are not nested inside columns). Every node carries
 * a client-only `_id` used for selection and drag/drop addressing.
 */

/** @param {object} node @returns {boolean} */
export const isContainer = (node) => !!node && node.type === 'container';

/**
 * Locate a node by id.
 * @param {Array} fields Tree.
 * @param {string} id    Node _id.
 * @returns {{parentId:?string, colIndex:?number, index:number}|null}
 */
export function locate(fields, id) {
  for (let i = 0; i < fields.length; i += 1) {
    if (fields[i]._id === id) return { parentId: null, colIndex: null, index: i };
    if (isContainer(fields[i])) {
      const cols = fields[i].columns || [];
      for (let c = 0; c < cols.length; c += 1) {
        const inner = cols[c].fields || [];
        for (let j = 0; j < inner.length; j += 1) {
          if (inner[j]._id === id) return { parentId: fields[i]._id, colIndex: c, index: j };
        }
      }
    }
  }
  return null;
}

/**
 * Find a node by id (read-only).
 * @param {Array} fields Tree.
 * @param {string} id    Node _id.
 * @returns {object|null}
 */
export function findNode(fields, id) {
  const loc = locate(fields, id);
  if (!loc) return null;
  if (loc.parentId === null) return fields[loc.index];
  const parent = fields.find((f) => f._id === loc.parentId);
  return parent.columns[loc.colIndex].fields[loc.index];
}

/**
 * Replace a node (top-level or nested) with a new node.
 * @param {Array} fields  Tree.
 * @param {string} id     Node _id.
 * @param {object} newNode Replacement.
 * @returns {Array}
 */
export function replaceNode(fields, id, newNode) {
  return fields.map((f) => {
    if (f._id === id) return newNode;
    if (isContainer(f)) {
      return {
        ...f,
        columns: (f.columns || []).map((col) => ({
          ...col,
          fields: (col.fields || []).map((n) => (n._id === id ? newNode : n)),
        })),
      };
    }
    return f;
  });
}

/**
 * Remove a node and return the new tree plus the removed node.
 * @param {Array} fields Tree.
 * @param {string} id    Node _id.
 * @returns {{fields:Array, removed:?object}}
 */
export function removeNode(fields, id) {
  let removed = null;
  const out = [];
  fields.forEach((f) => {
    if (f._id === id) { removed = f; return; }
    if (isContainer(f)) {
      const columns = (f.columns || []).map((col) => {
        const kept = [];
        (col.fields || []).forEach((n) => { if (n._id === id) removed = n; else kept.push(n); });
        return { ...col, fields: kept };
      });
      out.push({ ...f, columns });
    } else {
      out.push(f);
    }
  });
  return { fields: out, removed };
}

/**
 * Insert a node immediately before the target node (in the target's own list).
 * Falls back to appending at root when the target is missing.
 * @param {Array} fields   Tree.
 * @param {object} node    Node to insert.
 * @param {string} targetId Target node _id.
 * @returns {Array}
 */
export function insertBefore(fields, node, targetId) {
  const loc = locate(fields, targetId);
  if (!loc) return [...fields, node];
  if (loc.parentId === null) {
    const out = [...fields];
    out.splice(loc.index, 0, node);
    return out;
  }
  return fields.map((f) => {
    if (f._id !== loc.parentId) return f;
    const columns = (f.columns || []).map((col, ci) => {
      if (ci !== loc.colIndex) return col;
      const inner = [...(col.fields || [])];
      inner.splice(loc.index, 0, node);
      return { ...col, fields: inner };
    });
    return { ...f, columns };
  });
}

/**
 * Insert a node immediately after the target node.
 * @param {Array} fields   Tree.
 * @param {object} node    Node to insert.
 * @param {string} targetId Target node _id.
 * @returns {Array}
 */
export function insertAfter(fields, node, targetId) {
  const loc = locate(fields, targetId);
  if (!loc) return [...fields, node];
  if (loc.parentId === null) {
    const out = [...fields];
    out.splice(loc.index + 1, 0, node);
    return out;
  }
  return fields.map((f) => {
    if (f._id !== loc.parentId) return f;
    const columns = (f.columns || []).map((col, ci) => {
      if (ci !== loc.colIndex) return col;
      const inner = [...(col.fields || [])];
      inner.splice(loc.index + 1, 0, node);
      return { ...col, fields: inner };
    });
    return { ...f, columns };
  });
}

/**
 * Append a node to a specific container column.
 * @param {Array} fields      Tree.
 * @param {object} node       Node to append.
 * @param {string} containerId Container _id.
 * @param {number} colIndex   Column index.
 * @returns {Array}
 */
export function appendToColumn(fields, node, containerId, colIndex) {
  return fields.map((f) => {
    if (f._id !== containerId) return f;
    const columns = (f.columns || []).map((col, ci) => (
      ci !== colIndex ? col : { ...col, fields: [...(col.fields || []), node] }
    ));
    return { ...f, columns };
  });
}

/**
 * Move an existing node before another (works across root/columns).
 * @param {Array} fields   Tree.
 * @param {string} activeId Dragged node _id.
 * @param {string} overId   Drop-target node _id.
 * @returns {Array}
 */
export function moveBefore(fields, activeId, overId) {
  if (activeId === overId) return fields;
  const { fields: without, removed } = removeNode(fields, activeId);
  if (!removed) return fields;
  return insertBefore(without, removed, overId);
}

/**
 * Move an existing node into a column (append).
 * @param {Array} fields      Tree.
 * @param {string} activeId   Dragged node _id.
 * @param {string} containerId Target container _id.
 * @param {number} colIndex   Target column index.
 * @returns {Array}
 */
export function moveToColumn(fields, activeId, containerId, colIndex) {
  const active = findNode(fields, activeId);
  // Guard: never drop a container (or a step break) inside a column.
  if (!active || isContainer(active) || active.type === 'step') return fields;
  const { fields: without, removed } = removeNode(fields, activeId);
  if (!removed) return fields;
  return appendToColumn(without, removed, containerId, colIndex);
}

/** Collect every input field (with a key) across the whole tree. */
export function collectInputs(fields, isInputType) {
  const out = [];
  const walk = (list) => {
    list.forEach((f) => {
      if (isContainer(f)) { (f.columns || []).forEach((c) => walk(c.fields || [])); return; }
      if (f.key && isInputType(f.type)) out.push(f);
    });
  };
  walk(fields);
  return out;
}

/**
 * Split top-level fields into step pages at each `step` node.
 * Page 0 has no header; each subsequent page's header is the step node that
 * begins it. The step node itself is not part of any page's render list.
 * @param {Array} fields Tree.
 * @returns {Array<{header:?object, items:Array, boundaryId:?string}>}
 *   boundaryId is the id of the step node that STARTS the next page (or null).
 */
export function groupSteps(fields) {
  const pages = [{ header: null, items: [], boundaryId: null }];
  fields.forEach((f) => {
    if (f.type === 'step') {
      // Close current page with this step as the next boundary, open a new one.
      pages[pages.length - 1].boundaryId = f._id;
      pages.push({ header: f, items: [], boundaryId: null });
    } else {
      pages[pages.length - 1].items.push(f);
    }
  });
  return pages;
}
