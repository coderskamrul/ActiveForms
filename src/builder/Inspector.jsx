/**
 * Tabbed right-hand inspector: Input Fields (palette) / Input Customization
 * (selected field) / History. Tab state is controlled by the Builder so that
 * selecting a field can auto-switch to the customization tab.
 */
import React from 'react';
import Icon from './icons.jsx';
import Palette from './Palette.jsx';
import SettingsPanel from './SettingsPanel.jsx';
import History from './History.jsx';
import { t } from '../config';

const TABS = [
  { key: 'fields', label: () => t('inputFields', 'Input Fields'), icon: 'plus' },
  { key: 'customize', label: () => t('customize', 'Input Customization'), icon: 'sliders' },
  { key: 'history', label: () => t('history', 'History'), icon: 'history' },
];

/** Inspector panel. */
export default function Inspector({
  active, onTab,
  defs, onAdd,
  selectedField, selectedDef, onFieldChange, allFields,
  history,
}) {
  return (
    <div className="activeforms-insp-pane">
      <div className="activeforms-insp-tabs" role="tablist">
        {TABS.map((tab) => (
          <button
            key={tab.key}
            type="button"
            role="tab"
            aria-selected={active === tab.key}
            className={`activeforms-insp-tab${active === tab.key ? ' is-active' : ''}`}
            onClick={() => onTab(tab.key)}
          >
            <Icon name={tab.icon} size={14} />
            <span>{tab.label()}</span>
          </button>
        ))}
      </div>

      <div className="activeforms-insp-body">
        {active === 'fields' && (
          <Palette definitions={defs.fields} categories={defs.categories} onAdd={onAdd} />
        )}
        {active === 'customize' && (
          <SettingsPanel
            field={selectedField}
            definition={selectedDef}
            onChange={onFieldChange}
            allFields={allFields}
          />
        )}
        {active === 'history' && (
          <History
            canUndo={history.canUndo}
            canRedo={history.canRedo}
            onUndo={history.undo}
            onRedo={history.redo}
            depth={history.depth}
            lastSaved={history.lastSaved}
          />
        )}
      </div>
    </div>
  );
}
