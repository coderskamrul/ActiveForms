/**
 * ActiveForms admin entry point.
 *
 * Mounts the React app into the node printed by Admin\Menu::render_app().
 */
import React from 'react';
import { createRoot } from 'react-dom/client';
import App from './App.jsx';
import './theme/app.scss';
import './theme/builder.scss';

const mount = document.getElementById('activeforms-app');

if (mount) {
  mount.innerHTML = '';
  createRoot(mount).render(<App />);
}
