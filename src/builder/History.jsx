/**
 * "History" tab — session undo/redo controls and a change counter, plus the
 * last-saved status. Mirrors the reference builder's History pane while making
 * undo/redo directly actionable here too.
 */
import React from 'react';
import Icon from './icons.jsx';

/**
 * @param {object} props
 * @param {boolean} props.canUndo
 * @param {boolean} props.canRedo
 * @param {Function} props.onUndo
 * @param {Function} props.onRedo
 * @param {number} props.depth      Number of undoable steps.
 * @param {string|null} props.lastSaved Human label of last save, or null.
 */
export default function History({ canUndo, canRedo, onUndo, onRedo, depth, lastSaved }) {
  return (
    <div className="radiusforms-hist">
      <div className="radiusforms-hist__row">
        <button type="button" className="radiusforms-btn" disabled={!canUndo} onClick={onUndo}>
          <Icon name="undo" size={15} /> Undo
        </button>
        <button type="button" className="radiusforms-btn" disabled={!canRedo} onClick={onRedo}>
          <Icon name="redo" size={15} /> Redo
        </button>
      </div>

      <div className="radiusforms-hist__meta">
        <div className="radiusforms-hist__stat">
          <span className="num">{depth}</span>
          <span className="lbl">undoable change{depth === 1 ? '' : 's'} this session</span>
        </div>
      </div>

      <div className="radiusforms-hist__card">
        <div className="radiusforms-hist__card-head"><Icon name="history" size={15} /> Saved version</div>
        {lastSaved
          ? <p>Last saved <strong>{lastSaved}</strong>. Use Save Form to capture a new version.</p>
          : (
            <>
              <p><strong>To see your recent changes here:</strong></p>
              <ul>
                <li>Save your current form changes.</li>
                <li>The saved version then appears in the form history.</li>
                <li>Undo / redo above to step through this session's edits.</li>
              </ul>
            </>
          )}
      </div>
    </div>
  );
}
