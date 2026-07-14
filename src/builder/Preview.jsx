/**
 * Live, frontend-accurate preview. Posts the current (unsaved) schema to the
 * `/builder/preview` endpoint, which renders it with the very same PHP
 * FormRenderer used on the front end, and injects the frontend stylesheet so
 * what you see matches production. Interactions are inert (submit is blocked).
 */
import React, { useEffect, useState } from 'react';
import api from '../api/client';
import config from '../config';

const WIDTHS = { desktop: '100%', tablet: '768px', mobile: '390px' };

/** Ensure the frontend stylesheet is present in the document head once. */
function useFrontendStyles() {
  useEffect(() => {
    const id = 'radiusforms-frontend-css';
    if (!document.getElementById(id) && config.assetsUrl) {
      const link = document.createElement('link');
      link.id = id;
      link.rel = 'stylesheet';
      link.href = `${config.assetsUrl}frontend/form.css`;
      document.head.appendChild(link);
    }
  }, []);
}

/** Live preview pane. */
export default function Preview({ title, fields, device = 'desktop' }) {
  const [html, setHtml] = useState(null);
  const [error, setError] = useState(null);
  useFrontendStyles();

  useEffect(() => {
    let active = true;
    setHtml(null);
    setError(null);
    api.post('/builder/preview', { title, fields })
      .then((res) => { if (active) setHtml(res.html || ''); })
      .catch((e) => { if (active) setError(e.message); });
    return () => { active = false; };
  }, [title, fields]);

  return (
    <div className={`radiusforms-cv radiusforms-pv radiusforms-cv--${device}`}>
      <div className="radiusforms-cv__frame" style={{ maxWidth: WIDTHS[device] || '100%' }}>
        <div className="radiusforms-pv__sheet">
          {html === null && !error && <div className="radiusforms-center"><div className="radiusforms-spinner" /></div>}
          {error && <div className="radiusforms-pv__err">Preview failed: {error}</div>}
          {html !== null && (
            <div
              className="radiusforms-pv__form"
              onSubmitCapture={(e) => e.preventDefault()}
              // eslint-disable-next-line react/no-danger
              dangerouslySetInnerHTML={{ __html: html }}
            />
          )}
        </div>
      </div>
    </div>
  );
}
