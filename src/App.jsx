/**
 * Root application: top navbar shell + hash-routed pages.
 */
import React, { useEffect } from 'react';
import config, { t } from './config';
import { useRouter } from './router';
import { ToastProvider } from './components/Toast';
import Dashboard from './pages/Dashboard.jsx';
import Forms from './pages/Forms.jsx';
import Builder from './pages/Builder.jsx';
import Entries from './pages/Entries.jsx';
import EntryDetail from './pages/EntryDetail.jsx';
import Reports from './pages/Reports.jsx';
import Settings from './pages/Settings.jsx';

const NAV = [
  { path: '/dashboard', key: 'dashboard', label: 'Dashboard' },
  { path: '/forms', key: 'forms', label: 'Forms' },
  { path: '/entries', key: 'entries', label: 'Entries' },
  { path: '/reports', key: 'reports', label: 'Reports' },
  { path: '/settings', key: 'settings', label: 'Settings' },
];

/**
 * Resolve the active page component from the route.
 * @param {object} route Parsed route.
 * @returns {React.ReactNode}
 */
function renderPage(route) {
  const [section, a, b, c] = route.parts;

  switch (section) {
    case 'forms':
      if (a === 'new') return <Builder formId={null} />;
      if (a && b === 'edit') return <Builder formId={parseInt(a, 10)} />;
      if (a && b === 'entries' && c) return <EntryDetail formId={parseInt(a, 10)} entryId={parseInt(c, 10)} />;
      if (a && b === 'entries') return <Entries formId={parseInt(a, 10)} />;
      return <Forms />;
    case 'entries':
      return <Entries formId={a ? parseInt(a, 10) : null} />;
    case 'reports':
      return <Reports formId={a ? parseInt(a, 10) : null} />;
    case 'settings':
      return <Settings />;
    case 'dashboard':
    default:
      return <Dashboard />;
  }
}

/** App root. */
export default function App() {
  const { route } = useRouter();
  const active = route.parts[0] || 'dashboard';
  const brand = config.brand || {};
  // The builder is a full-bleed screen; hide the page chrome padding handled by CSS.
  const inBuilder = route.parts[0] === 'forms' && (route.parts[1] === 'new' || route.parts[2] === 'edit');

  // Run the builder full-screen: the stylesheet keys off these classes to hide
  // the WP admin bar + side menu (this is a client-side hash route, so PHP can't
  // distinguish it). Always clean up so leaving the builder restores wp-admin.
  useEffect(() => {
    const cls = 'radiusforms-builder-fullscreen';
    document.body.classList.toggle(cls, inBuilder);
    document.documentElement.classList.toggle(cls, inBuilder);
    return () => {
      document.body.classList.remove(cls);
      document.documentElement.classList.remove(cls);
    };
  }, [inBuilder]);

  return (
    <ToastProvider>
      <div className="radiusforms-app">
        {!inBuilder && (
          <nav className="radiusforms-topnav">
            <a className="radiusforms-brand" href="#/dashboard">
              <span className="radiusforms-logo">{(brand.shortName || 'E').slice(0, 1)}</span>
              <b>{(brand.name || 'RadiusForms').replace(/^easy/i, '')}</b>
            </a>
            <div className="radiusforms-nav">
              {NAV.map((item) => (
                <a key={item.key} href={`#${item.path}`} className={active === item.key ? 'is-active' : ''}>
                  {t(item.key, item.label)}
                </a>
              ))}
            </div>
            <span className="radiusforms-topnav__search" title="Search">
              <span className="dashicons dashicons-search" style={{ fontSize: 16, width: 16, height: 16 }} aria-hidden="true" />
              <kbd>⌘K</kbd>
            </span>
          </nav>
        )}
        <main className={`radiusforms-main${inBuilder ? ' is-builder' : ''}`}>
          {renderPage(route)}
        </main>
      </div>
    </ToastProvider>
  );
}
