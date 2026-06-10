/**
 * Reads the single localized config object published by PHP (AdminAssets).
 * Nothing about branding/prefix/REST is hard-coded in the React app.
 */
const fallback = {
  version: '1.0.0',
  restUrl: '/wp-json/easyforms/v1',
  restNamespace: 'easyforms/v1',
  nonce: '',
  adminUrl: '',
  assetsUrl: '',
  capabilities: {},
  brand: { name: 'EasyForms', shortName: 'EasyForms', tagline: 'Drag & Drop Form Builder' },
  designTokens: {},
  currencies: ['USD'],
  dateFormat: 'F j, Y',
  strings: {},
};

const config = typeof window !== 'undefined' && window.EasyFormsConfig
  ? window.EasyFormsConfig
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
