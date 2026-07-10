/**
 * "Input Fields" tab — the draggable field library as collapsible accordions
 * with a focus-on-"/" search box. Fields come from the registry; column
 * presets build containers in one click.
 */
import React, { useEffect, useMemo, useRef, useState } from 'react';
import { useDraggable } from '@dnd-kit/core';
import Icon from './icons.jsx';
import { t } from '../config';

const COLUMN_PRESETS = [
  { cols: 1, label: 'One Column', icon: 'menu-alt' },
  { cols: 2, label: 'Two Columns', icon: 'columns' },
  { cols: 3, label: 'Three Columns', icon: 'screenoptions' },
  { cols: 4, label: 'Four Columns', icon: 'grid-view' },
];

/* Canonical display order for the General Fields section. Keeping the sequence
   here (rather than relying on registration order, which spans core + Pro)
   makes it the single place to reshuffle the everyday fields. Types not listed
   fall to the end in their existing order. */
const GENERAL_ORDER = [
  'name', 'email', 'text', 'masked_text', 'textarea', 'address', 'country',
  'number', 'select', 'radio', 'checkbox', 'multiselect', 'url', 'date_time',
  'image_upload', 'file_upload', 'html', 'phone',
];
const generalRank = (type) => {
  const i = GENERAL_ORDER.indexOf(type);
  return i === -1 ? GENERAL_ORDER.length : i;
};
const byGeneralOrder = (a, b) => generalRank(a.type) - generalRank(b.type);

/** A single draggable (free) palette item. */
function PaletteItem({ def, onAdd }) {
  const { attributes, listeners, setNodeRef, isDragging } = useDraggable({
    id: `palette-${def.type}-${def.presetColumns || 0}`,
    data: { fromPalette: true, def },
  });
  return (
    <button
      ref={setNodeRef}
      type="button"
      className={`activeforms-pal-item${isDragging ? ' is-dragging' : ''}`}
      onClick={() => onAdd(def)}
      {...listeners}
      {...attributes}
      title={`Add ${def.label}`}
    >
      <span className={`dashicons dashicons-${def.icon || 'forms'}`} aria-hidden="true" />
      <span className="activeforms-pal-item__label">{def.label}</span>
    </button>
  );
}

/** One collapsible accordion group. */
function Group({ title, open, onToggle, children, count }) {
  return (
    <div className={`activeforms-pal-acc${open ? ' is-open' : ''}`}>
      <button type="button" className="activeforms-pal-acc__head" onClick={onToggle} aria-expanded={open}>
        <span>{title}</span>
        <span className="activeforms-pal-acc__meta">
          {typeof count === 'number' && <em>{count}</em>}
          <Icon name={open ? 'chevronDown' : 'chevronRight'} size={15} />
        </span>
      </button>
      {open && <div className="activeforms-pal-acc__body"><div className="activeforms-pal__grid">{children}</div></div>}
    </div>
  );
}

/** Field library palette. */
export default function Palette({ definitions, categories, onAdd }) {
  const [query, setQuery] = useState('');
  const [openKeys, setOpenKeys] = useState(() => new Set(['general', 'containers']));
  const searchRef = useRef(null);

  useEffect(() => {
    const onKey = (e) => {
      if (e.key === '/' && document.activeElement !== searchRef.current) {
        const tag = (document.activeElement && document.activeElement.tagName) || '';
        if (['INPUT', 'TEXTAREA', 'SELECT'].includes(tag)) return;
        e.preventDefault();
        searchRef.current && searchRef.current.focus();
      }
    };
    window.addEventListener('keydown', onKey);
    return () => window.removeEventListener('keydown', onKey);
  }, []);

  const q = query.trim().toLowerCase();
  const match = (label) => !q || label.toLowerCase().includes(q);
  const catLabel = (key, fallback) => {
    const c = (categories || []).find((x) => x.key === key);
    return (c && c.label) || fallback;
  };

  const containerDef = useMemo(() => definitions.find((d) => d.type === 'container'), [definitions]);

  // Assemble sections in display order. Registered fields render as draggable
  // items.
  const sections = useMemo(() => {
    const byCat = (cat) => definitions.filter((d) => (d.category || 'general') === cat);

    const presetItems = containerDef
      ? COLUMN_PRESETS.map((p) => ({ ...containerDef, label: p.label, icon: p.icon, presetColumns: p.cols }))
      : [];

    return [
      { key: 'general', title: catLabel('general', 'General Fields'), free: byCat('general').slice().sort(byGeneralOrder) },
      { key: 'layout', title: 'Layout', free: byCat('layout').filter((d) => d.type !== 'container') },
      { key: 'containers', title: 'Containers', free: presetItems },
      { key: 'advanced', title: 'Advanced Fields', free: byCat('advanced') },
    ];
    // eslint-disable-next-line react-hooks/exhaustive-deps
  }, [definitions, containerDef, categories]);

  const toggle = (key) => setOpenKeys((prev) => {
    const next = new Set(prev);
    if (next.has(key)) next.delete(key); else next.add(key);
    return next;
  });

  return (
    <div className="activeforms-pal">
      <div className="activeforms-pal__search">
        <Icon name="search" size={15} />
        <input
          ref={searchRef}
          value={query}
          onChange={(e) => setQuery(e.target.value)}
          placeholder={t('searchFields', 'Search fields ( press / to focus )')}
          aria-label="Search fields"
        />
        {query && (
          <button type="button" className="activeforms-pal__clear" onClick={() => setQuery('')} aria-label="Clear">
            <Icon name="close" size={13} />
          </button>
        )}
      </div>

      <div className="activeforms-pal__body">
        {sections.map((sec) => {
          const free = sec.free.filter((d) => match(d.label));
          if (!free.length) return null;
          const open = q ? true : openKeys.has(sec.key);
          return (
            <Group key={sec.key} title={sec.title} open={open} onToggle={() => toggle(sec.key)} count={free.length}>
              {free.map((def) => (
                <PaletteItem key={`${def.type}-${def.presetColumns || 0}`} def={def} onAdd={onAdd} />
              ))}
            </Group>
          );
        })}
      </div>
    </div>
  );
}
