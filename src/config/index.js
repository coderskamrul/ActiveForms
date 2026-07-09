/**
 * Reads the single localized config object published by PHP (AdminAssets).
 * Nothing about branding/prefix/REST is hard-coded in the React app.
 */
const fallback = {
  version: '1.0.0',
  restUrl: '/wp-json/activeforms/v1',
  restNamespace: 'activeforms/v1',
  nonce: '',
  adminUrl: '',
  assetsUrl: '',
  capabilities: {},
  brand: { name: 'ActiveForms', shortName: 'ActiveForms', tagline: 'Drag & Drop Form Builder' },
  designTokens: {},
  currencies: ['USD'],
  dateFormat: 'F j, Y',
  strings: {},
};

const config = typeof window !== 'undefined' && window.ActiveFormsConfig
  ? window.ActiveFormsConfig
  : fallback;

export default config;

/**
 * Translate helper backed by the PHP strings dictionary.
 * @param {string} key Strings dictionary key.
 * @param {string} [def] Default English text.
 * @returns {string}
 */
export function t(key, def) {
  return (config.strings && config.strings[key]) || def || key;
}
