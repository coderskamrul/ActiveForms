/**
 * Minimal hash router. The WP admin menu items point at #/route fragments;
 * this hook parses the current hash into a path + params.
 */
import { useState, useEffect, useCallback } from 'react';

/**
 * Parse the current location hash.
 * @returns {{path: string, parts: string[]}}
 */
function parse() {
  let hash = window.location.hash.replace(/^#/, '');
  if (!hash || hash === '/') hash = '/dashboard';
  const path = hash.split('?')[0];
  return { path, parts: path.split('/').filter(Boolean) };
}

/**
 * Subscribe to hash changes.
 * @returns {{route: object, navigate: function}}
 */
export function useRouter() {
  const [route, setRoute] = useState(parse());

  useEffect(() => {
    const onChange = () => setRoute(parse());
    window.addEventListener('hashchange', onChange);
    return () => window.removeEventListener('hashchange', onChange);
  }, []);

  const navigate = useCallback((to) => {
    window.location.hash = to.startsWith('#') ? to : `#${to}`;
  }, []);

  return { route, navigate };
}

/**
 * Imperative navigation helper for non-hook contexts.
 * @param {string} to Target path.
 */
export function go(to) {
  window.location.hash = to.startsWith('#') ? to : `#${to}`;
}
