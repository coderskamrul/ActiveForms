/**
 * Drag-and-drop form builder.
 *
 * Composition:
 *   BuilderTopbar  — title, undo/redo, responsive preview, shortcode, save
 *   Canvas         — sortable preview with column containers & step pages
 *   Preview        — live, frontend-accurate render (preview mode)
 *   Inspector      — tabbed: Input Fields / Input Customization / History
 *
 * The field tree supports one level of nesting (container → columns → fields).
 * Nodes are addressed by a client-only `_id`; see builder/schemaTree.js.
 */
import React, { useEffect, useState, useCallback, useRef } from 'react';
import { DndContext, DragOverlay, PointerSensor, useSensor, useSensors, closestCenter } from '@dnd-kit/core';
import api from '../api/client';
import { Loading } from '../components/ui';
import { useToast } from '../components/Toast';
import { go } from '../router';
import config, { t } from '../config';
import BuilderTopbar from '../builder/BuilderTopbar.jsx';
import Canvas, { COL_PREFIX } from '../builder/Canvas.jsx';
import Inspector from '../builder/Inspector.jsx';
import useFormHistory from '../builder/useFormHistory.js';
import { makeField } from '../builder/fieldFactory.js';
import {
  locate, findNode, replaceNode, removeNode, insertBefore, insertAfter,
  appendToColumn, moveBefore, moveToColumn, collectInputs, groupSteps,
} from '../builder/schemaTree.js';

let idSeq = 0;
const withId = (field) => ({ ...field, _id: `f${(idSeq += 1)}` });

/** Recursively assign client _ids (containers' nested fields included). */
function hydrate(fields) {
  return (fields || []).map((f) => {
    const n = withId(f);
    if (n.type === 'container' && Array.isArray(n.columns)) {
      n.columns = n.columns.map((c) => ({ ...c, fields: hydrate(c.fields || []) }));
    }
    return n;
  });
}

/** Recursively strip client _ids before persisting. */
function dehydrate(fields) {
  return (fields || []).map((f) => {
    const { _id, ...rest } = f;
    if (rest.type === 'container' && Array.isArray(rest.columns)) {
      rest.columns = rest.columns.map((c) => ({ ...c, fields: dehydrate(c.fields || []) }));
    }
    return rest;
  });
}

/** Resolve a dnd-kit `over.id` into a structural drop target. */
function resolveTarget(overId) {
  if (overId === 'canvas-root' || overId === 'canvas-append') return { kind: 'root' };
  if (typeof overId === 'string' && overId.startsWith(COL_PREFIX)) {
    const [containerId, colStr] = overId.slice(COL_PREFIX.length).split('::');
    return { kind: 'column', containerId, colIndex: Number(colStr) };
  }
  return { kind: 'field', id: overId };
}

/** Builder page. */
export default function Builder({ formId }) {
  const [loading, setLoading] = useState(true);
  const [defs, setDefs] = useState({ fields: [], categories: [] });
  const [selectedId, setSelectedId] = useState(null);
  const [tab, setTab] = useState('fields');
  const [device, setDevice] = useState('desktop');
  const [activeStep, setActiveStep] = useState(0);
  const [drag, setDrag] = useState(null);
  const [saving, setSaving] = useState(false);
  const [dirty, setDirty] = useState(false);
  const [lastSaved, setLastSaved] = useState(null);

  const idRef = useRef(formId);
  const settingsRef = useRef({});
  const { notify } = useToast();

  const hist = useFormHistory({ title: 'Untitled Form', fields: [] });
  const { title, fields } = hist.state;

  const sensors = useSensors(useSensor(PointerSensor, { activationConstraint: { distance: 5 } }));

  const setTitle = useCallback((value) => { hist.set({ title: value, fields }, 'title'); setDirty(true); }, [hist, fields]);
  const setFields = useCallback((next, coalesceKey = null) => { hist.set({ title, fields: next }, coalesceKey); setDirty(true); }, [hist, title]);

  // Load field definitions + the form (when editing).
  useEffect(() => {
    let active = true;
    (async () => {
      const meta = await api.get('/builder/fields').catch(() => ({ fields: [], categories: [] }));
      if (!active) return;
      setDefs(meta);
      if (formId) {
        const form = await api.get(`/forms/${formId}`).catch(() => null);
        if (active && form) {
          idRef.current = form.id;
          settingsRef.current = form.settings || {};
          hist.reset({ title: form.title, fields: hydrate(form.fields || []) });
        }
      }
      if (active) setLoading(false);
    })();
    return () => { active = false; };
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [formId]);

  const defByType = useCallback((type) => defs.fields.find((d) => d.type === type), [defs]);
  const isInputType = useCallback((type) => { const d = defByType(type); return d ? d.isInput : false; }, [defByType]);

  // Append a node at the end of the currently visible step page.
  const appendToCurrentPage = useCallback((tree, node) => {
    const pages = groupSteps(tree);
    const page = pages[Math.min(activeStep, pages.length - 1)];
    if (page && page.boundaryId) return insertBefore(tree, node, page.boundaryId);
    return [...tree, node];
  }, [activeStep]);

  // Insert a new node honoring drop target + nesting rules.
  const insertNode = useCallback((tree, node, target) => {
    const tgt = target || { kind: 'root' };
    const blockNest = node.type === 'container' || node.type === 'step';
    if (tgt.kind === 'column') {
      if (blockNest) return appendToCurrentPage(tree, node);
      return appendToColumn(tree, node, tgt.containerId, tgt.colIndex);
    }
    if (tgt.kind === 'field') {
      const loc = locate(tree, tgt.id);
      if (blockNest && loc && loc.parentId !== null) return appendToCurrentPage(tree, node);
      return insertBefore(tree, node, tgt.id);
    }
    return appendToCurrentPage(tree, node);
  }, [appendToCurrentPage]);

  const addField = useCallback((def, target = null) => {
    const node = withId(makeField(def));
    setFields(insertNode(fields, node, target));
    setSelectedId(node._id);
    setTab('customize');
  }, [fields, insertNode, setFields]);

  const onDragStart = useCallback((event) => {
    const cur = event.active.data.current;
    if (cur && cur.fromPalette) {
      setDrag({ kind: 'palette', label: cur.def.label, icon: cur.def.icon });
    } else {
      const node = findNode(fields, event.active.id);
      if (node) {
        const def = defByType(node.type);
        setDrag({ kind: 'field', label: node.admin_label || node.label || (def && def.label) || node.type, icon: def && def.icon });
      }
    }
  }, [fields, defByType]);

  const onDragEnd = useCallback((event) => {
    setDrag(null);
    const { active, over } = event;
    if (!over) return;
    const target = resolveTarget(over.id);

    if (active.data.current && active.data.current.fromPalette) {
      addField(active.data.current.def, target);
      return;
    }

    const activeId = active.id;
    if (activeId === over.id) return;

    if (target.kind === 'column') {
      setFields(moveToColumn(fields, activeId, target.containerId, target.colIndex));
    } else if (target.kind === 'field') {
      setFields(moveBefore(fields, activeId, target.id));
    } else {
      const node = findNode(fields, activeId);
      if (!node) return;
      const { fields: without } = removeNode(fields, activeId);
      setFields(appendToCurrentPage(without, node));
    }
  }, [fields, addField, setFields, appendToCurrentPage]);

  const selectField = (id) => { setSelectedId(id); if (id) setTab('customize'); };

  const updateField = (next, coalesceKey = null) => {
    if (!selectedId) return;
    setFields(replaceNode(fields, selectedId, { ...next, _id: selectedId }), coalesceKey);
  };

  const deleteField = (id) => {
    setFields(removeNode(fields, id).fields);
    setSelectedId((s) => (s === id ? null : s));
  };

  const duplicateField = (id) => {
    const node = findNode(fields, id);
    if (!node) return;
    const copy = hydrate([JSON.parse(JSON.stringify(node))])[0];
    if (copy.key) copy.key = `${copy.type}_${Date.now().toString(36)}`;
    setFields(insertAfter(fields, copy, id));
    setSelectedId(copy._id);
  };

  const save = useCallback(async () => {
    setSaving(true);
    try {
      const payload = { title, fields: dehydrate(fields) };
      let form;
      if (idRef.current) {
        form = await api.put(`/forms/${idRef.current}`, payload);
      } else {
        form = await api.post('/forms', payload);
        idRef.current = form.id;
        go(`/forms/${form.id}/edit`);
      }
      setDirty(false);
      setLastSaved(new Date().toLocaleTimeString());
      notify(t('saved', 'Saved'));
    } catch (e) {
      notify(e.message, 'error');
    } finally {
      setSaving(false);
    }
  }, [title, fields, notify]);

  // Open the real frontend preview in a new tab (saving first if needed).
  const openPreview = useCallback(async () => {
    if (!idRef.current) { notify('Save the form first to preview it.', 'error'); return; }
    if (dirty) { await save(); }
    const base = (config.home || '/').replace(/\/$/, '/');
    window.open(`${base}?easyforms_preview=${idRef.current}`, '_blank', 'noopener');
  }, [dirty, save, notify]);

  // Keyboard shortcuts.
  useEffect(() => {
    const onKey = (e) => {
      const meta = e.metaKey || e.ctrlKey;
      const tag = (document.activeElement && document.activeElement.tagName) || '';
      const editing = ['INPUT', 'TEXTAREA', 'SELECT'].includes(tag);
      if (meta && e.key.toLowerCase() === 's') { e.preventDefault(); save(); return; }
      if (meta && e.key.toLowerCase() === 'z') { e.preventDefault(); if (e.shiftKey) hist.redo(); else hist.undo(); return; }
      if (meta && e.key.toLowerCase() === 'y') { e.preventDefault(); hist.redo(); return; }
      if ((e.key === 'Delete' || e.key === 'Backspace') && selectedId && !editing) { e.preventDefault(); deleteField(selectedId); }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [save, hist, selectedId, fields]);

  if (loading) return <Loading />;

  const selectedField = selectedId ? findNode(fields, selectedId) : null;
  const allInputFields = collectInputs(fields, isInputType)
    .filter((f) => f._id !== selectedId)
    .map((f) => ({ key: f.key, label: f.admin_label || f.label || f.key }));

  return (
    <div className="easyforms-builder-shell">
      <BuilderTopbar
        title={title}
        onTitle={setTitle}
        formId={idRef.current}
        canUndo={hist.canUndo}
        canRedo={hist.canRedo}
        onUndo={hist.undo}
        onRedo={hist.redo}
        device={device}
        onDevice={setDevice}
        onPreview={openPreview}
        dirty={dirty}
        saving={saving}
        onSave={save}
      />

      <DndContext sensors={sensors} collisionDetection={closestCenter} onDragStart={onDragStart} onDragEnd={onDragEnd} onDragCancel={() => setDrag(null)}>
          <div className="easyforms-builder-2">
            <Canvas
              fields={fields}
              selectedId={selectedId}
              onSelect={selectField}
              onDelete={deleteField}
              onDuplicate={duplicateField}
              dropLabel={t('dropHere', 'Drag fields here to build your form')}
              device={device}
              activeStep={activeStep}
              onStep={setActiveStep}
            />
            <Inspector
              active={tab}
              onTab={setTab}
              defs={defs}
              onAdd={(def) => addField(def)}
              selectedField={selectedField}
              selectedDef={selectedField ? defByType(selectedField.type) : null}
              onFieldChange={updateField}
              allFields={allInputFields}
              history={{
                canUndo: hist.canUndo, canRedo: hist.canRedo, undo: hist.undo, redo: hist.redo,
                depth: hist.depth, lastSaved,
              }}
            />
          </div>

          <DragOverlay dropAnimation={{ duration: 180, easing: 'cubic-bezier(0.2, 0, 0, 1)' }}>
            {drag ? (
              <div className="easyforms-drag-ghost">
                <span className={`dashicons dashicons-${drag.icon || 'forms'}`} aria-hidden="true" />
                <span>{drag.label}</span>
              </div>
            ) : null}
          </DragOverlay>
      </DndContext>
    </div>
  );
}
