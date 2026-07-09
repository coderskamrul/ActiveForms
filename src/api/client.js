/**
 * Nonce-aware REST client for the ActiveForms admin app.
 * All admin data flows through activeforms/v1 routes (cookie + nonce auth).
 */
import config from '../config';

const base = config.restUrl.replace(/\/$/, '');

/**
 * Perform a REST request.
 * @param {string} path Route path beginning with "/".
 * @param {object} [options] fetch options (method, body).
 * @returns {Promise<any>} The `data` payload from the standard envelope.
 */
async function request(path, options = {}) {
  const opts = {
    method: options.method || 'GET',
    headers: {
      'Content-Type': 'application/json',
      'X-WP-Nonce': config.nonce,
    },
    credentials: 'same-origin',
  };

  if (options.body !== undefined) {
    opts.body = JSON.stringify(options.body);
  }

  const res = await fetch(base + path, opts);
  let json;
  try {
    json = await res.json();
  } catch (e) {
    throw new Error('Invalid server response');
  }

  if (!res.ok || json.success === false) {
    const message = json.message || 'Request failed';
    const err = new Error(message);
    err.errors = json.errors;
    throw err;
  }

  return json.data !== undefined ? json.data : json;
}

export const api = {
  get: (p) => request(p),
  post: (p, body) => request(p, { method: 'POST', body }),
  put: (p, body) => request(p, { method: 'PUT', body }),
  del: (p) => request(p, { method: 'DELETE' }),
};

export default api;
