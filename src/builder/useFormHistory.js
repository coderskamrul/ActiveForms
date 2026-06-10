/**
 * Undo/redo history for the builder's editable state ({ title, fields }).
 *
 * Structural changes (add/delete/reorder/duplicate) commit a discrete history
 * entry. Rapid property edits to the same field coalesce into a single entry so
 * typing in a label doesn't create dozens of undo steps.
 */
import { useReducer, useCallback, useRef } from 'react';

const COALESCE_MS = 700;

function reducer(state, action) {
  switch (action.type) {
    case 'set': {
      const { present, past, future } = state;
      // Coalesce: replace present without growing history.
      if (action.coalesce) {
        return { present: action.value, past, future: [] };
      }
      return {
        present: action.value,
        past: [...past, present],
        future: [],
      };
    }
    case 'undo': {
      if (!state.past.length) return state;
      const previous = state.past[state.past.length - 1];
      return {
        present: previous,
        past: state.past.slice(0, -1),
        future: [state.present, ...state.future],
      };
    }
    case 'redo': {
      if (!state.future.length) return state;
      const next = state.future[0];
      return {
        present: next,
        past: [...state.past, state.present],
        future: state.future.slice(1),
      };
    }
    case 'reset':
      return { present: action.value, past: [], future: [] };
    default:
      return state;
  }
}

/**
 * @param {object} initial Initial { title, fields } snapshot.
 * @returns {object} History controls.
 */
export default function useFormHistory(initial) {
  const [state, dispatch] = useReducer(reducer, {
    present: initial,
    past: [],
    future: [],
  });

  // Track the last coalescing key + timestamp to merge bursty edits.
  const lastEdit = useRef({ key: null, at: 0 });

  /**
   * Commit a new state. Pass a `coalesceKey` for high-frequency edits (e.g.
   * `field:label:3`) so consecutive same-key updates merge into one entry.
   */
  const set = useCallback((value, coalesceKey = null) => {
    let coalesce = false;
    if (coalesceKey !== null) {
      const now = Date.now();
      const prev = lastEdit.current;
      coalesce = prev.key === coalesceKey && now - prev.at < COALESCE_MS;
      lastEdit.current = { key: coalesceKey, at: now };
    } else {
      lastEdit.current = { key: null, at: 0 };
    }
    dispatch({ type: 'set', value, coalesce });
  }, []);

  const undo = useCallback(() => {
    lastEdit.current = { key: null, at: 0 };
    dispatch({ type: 'undo' });
  }, []);

  const redo = useCallback(() => {
    lastEdit.current = { key: null, at: 0 };
    dispatch({ type: 'redo' });
  }, []);

  const reset = useCallback((value) => {
    lastEdit.current = { key: null, at: 0 };
    dispatch({ type: 'reset', value });
  }, []);

  return {
    state: state.present,
    set,
    undo,
    redo,
    reset,
    canUndo: state.past.length > 0,
    canRedo: state.future.length > 0,
    depth: state.past.length,
  };
}
