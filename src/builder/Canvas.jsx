/**
 * The builder canvas (edit mode): a responsive, sortable preview of the form
 * with column containers (side-by-side drop zones) and multi-step pages.
 * Each field shows a floating action pill on hover/selection. Live preview is
 * handled separately by the standalone preview page.
 */
import React from 'react';
import { useDroppable } from '@dnd-kit/core';
import { SortableContext, useSortable, verticalListSortingStrategy } from '@dnd-kit/sortable';
import { CSS } from '@dnd-kit/utilities';
import FieldPreview from './FieldPreview.jsx';
import Icon from './icons.jsx';
import { groupSteps } from './schemaTree.js';

const WIDTHS = { desktop: '100%', tablet: '768px', mobile: '390px' };
export const COL_PREFIX = 'col::';

/** Floating action pill shared by field & container cards. */
function ActionPill({ id, dragProps, onSelect, onDelete, onDuplicate }) {
  return (
    <div className="activeforms-cv-pill">
      <button type="button" className="activeforms-cv-pill__move" title="Drag to move" {...dragProps} onClick={(e) => e.stopPropagation()}>
        <Icon name="move" size={14} />
      </button>
      <span className="activeforms-cv-pill__sep" />
      <button type="button" title="Edit" onClick={(e) => { e.stopPropagation(); onSelect(id); }}><Icon name="pencil" size={14} /></button>
      <button type="button" title="Duplicate" onClick={(e) => { e.stopPropagation(); onDuplicate(id); }}><Icon name="copy" size={14} /></button>
      <button type="button" className="is-danger" title="Delete" onClick={(e) => { e.stopPropagation(); onDelete(id); }}><Icon name="trash" size={14} /></button>
    </div>
  );
}

/** A single sortable field card (used at root and inside columns). */
function SortableField({ field, selectedId, onSelect, onDelete, onDuplicate }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: field._id });
  const style = { transform: CSS.Transform.toString(transform), transition };

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`activeforms-cv-field${selectedId === field._id ? ' is-selected' : ''}${isDragging ? ' is-dragging' : ''}`}
      onClick={(e) => { e.stopPropagation(); onSelect(field._id); }}
    >
      <ActionPill id={field._id} dragProps={{ ...listeners, ...attributes }} onSelect={onSelect} onDelete={onDelete} onDuplicate={onDuplicate} />
      <FieldPreview field={field} />
    </div>
  );
}

/** A droppable container column. */
function Column({ containerId, colIndex, column, common }) {
  const droppableId = `${COL_PREFIX}${containerId}::${colIndex}`;
  const { setNodeRef, isOver } = useDroppable({ id: droppableId });
  const inner = column.fields || [];

  return (
    <div className="activeforms-cv-col" style={{ flex: `${Math.max(5, column.width || 50)} 1 0`, minWidth: 0 }}>
      <div ref={setNodeRef} className={`activeforms-cv-col__zone${isOver ? ' is-over' : ''}${inner.length ? ' has-items' : ''}`}>
        <SortableContext items={inner.map((f) => f._id)} strategy={verticalListSortingStrategy}>
          {inner.length === 0
            ? <div className="activeforms-cv-col__empty"><Icon name="plus" size={14} /> Drop field</div>
            : inner.map((f) => <SortableField key={f._id} field={f} {...common} />)}
        </SortableContext>
      </div>
    </div>
  );
}

/** A sortable column container card. */
function ContainerField({ field, common }) {
  const { attributes, listeners, setNodeRef, transform, transition, isDragging } = useSortable({ id: field._id });
  const style = { transform: CSS.Transform.toString(transform), transition };
  const selected = common.selectedId === field._id;
  const cols = (field.columns || []).length;

  return (
    <div
      ref={setNodeRef}
      style={style}
      className={`activeforms-cv-field activeforms-cv-container${selected ? ' is-selected' : ''}${isDragging ? ' is-dragging' : ''}`}
      onClick={(e) => { e.stopPropagation(); common.onSelect(field._id); }}
    >
      <ActionPill id={field._id} dragProps={{ ...listeners, ...attributes }} onSelect={common.onSelect} onDelete={common.onDelete} onDuplicate={common.onDuplicate} />
      <span className="activeforms-cv-container__tag">{cols} column{cols === 1 ? '' : 's'}</span>
      <div className="activeforms-cv-container__cols">
        {(field.columns || []).map((col, ci) => (
          <Column key={ci} containerId={field._id} colIndex={ci} column={col} common={common} />
        ))}
      </div>
    </div>
  );
}

/** Step page-break chip (selectable / deletable). */
function StepChip({ field, common }) {
  const selected = common.selectedId === field._id;
  return (
    <div className={`activeforms-cv-step${selected ? ' is-selected' : ''}`} onClick={(e) => { e.stopPropagation(); common.onSelect(field._id); }}>
      <Icon name="chevronRight" size={13} />
      <span>{field.label || 'Page Break'}</span>
      <button type="button" title="Delete step" className="activeforms-cv-step__rm" onClick={(e) => { e.stopPropagation(); common.onDelete(field._id); }}>
        <Icon name="close" size={13} />
      </button>
    </div>
  );
}

/** Canvas. */
export default function Canvas({
  fields, selectedId, onSelect, onDelete, onDuplicate, dropLabel,
  device = 'desktop', activeStep = 0, onStep,
}) {
  const { setNodeRef, isOver } = useDroppable({ id: 'canvas-root' });
  const common = { selectedId, onSelect, onDelete, onDuplicate };

  const pages = groupSteps(fields);
  const hasSteps = pages.length > 1;
  const step = Math.min(Math.max(activeStep, 0), pages.length - 1);
  const page = pages[step];
  const items = page.items;

  return (
    <div className={`activeforms-cv activeforms-cv--${device}`}>
      <div className="activeforms-cv__frame" style={{ maxWidth: WIDTHS[device] || '100%' }}>
        {hasSteps && (
          <div className="activeforms-cv-steps" role="tablist">
            {pages.map((p, i) => (
              <button
                key={i}
                type="button"
                role="tab"
                aria-selected={i === step}
                className={`activeforms-cv-steps__tab${i === step ? ' is-active' : ''}`}
                onClick={() => onStep && onStep(i)}
              >
                <span className="activeforms-cv-steps__num">{i + 1}</span>
                {p.header ? (p.header.label || `Step ${i + 1}`) : 'Step 1'}
              </button>
            ))}
          </div>
        )}

        <div className={`activeforms-cv__sheet${isOver ? ' is-over' : ''}`} ref={setNodeRef} onClick={() => onSelect(null)}>
          {page.header && <StepChip field={page.header} common={common} />}

          {items.length === 0 && !page.header ? (
            <div className={`activeforms-cv__drop${isOver ? ' is-over' : ''}`}>
              <span className="dashicons dashicons-feedback" aria-hidden="true" />
              <p>{dropLabel}</p>
              <small>Drag a field from the right, or click one to add it.</small>
            </div>
          ) : (
            <SortableContext items={items.map((f) => f._id)} strategy={verticalListSortingStrategy}>
              {items.length === 0 && (
                <div className={`activeforms-cv__drop${isOver ? ' is-over' : ''}`}><p>Drop fields for this step</p></div>
              )}
              {items.map((field) => (
                field.type === 'container'
                  ? <ContainerField key={field._id} field={field} common={common} />
                  : <SortableField key={field._id} field={field} {...common} />
              ))}
            </SortableContext>
          )}
        </div>
      </div>
    </div>
  );
}
