/**
 * ActiveForms webpack build.
 *
 * Two entries:
 *   - dist/activeforms   → the React admin app  (assets/dist/activeforms.{js,css})
 *   - frontend/form    → the public form JS+SCSS (assets/frontend/form.{js,css})
 *
 * SCSS is compiled with sass and extracted to standalone CSS files that the PHP
 * AssetManager enqueues directly. React is bundled (no WordPress externals) so
 * the admin app is fully self-contained.
 */
const path = require('path');
const MiniCssExtractPlugin = require('mini-css-extract-plugin');

module.exports = (env, argv) => {
  const isProd = argv.mode === 'production';

  return {
    entry: {
      'dist/activeforms': path.resolve(__dirname, 'src/main.jsx'),
      'frontend/form': path.resolve(__dirname, 'src/frontend/form.js'),
    },
    output: {
      path: path.resolve(__dirname, 'assets'),
      filename: '[name].js',
      clean: false,
    },
    resolve: {
      extensions: ['.js', '.jsx'],
    },
    devtool: isProd ? false : 'source-map',
    module: {
      rules: [
        {
          test: /\.jsx?$/,
          exclude: /node_modules/,
          use: {
            loader: 'babel-loader',
            options: {
              presets: [
                ['@babel/preset-env', { targets: 'defaults' }],
                ['@babel/preset-react', { runtime: 'automatic' }],
              ],
            },
          },
        },
        {
          test: /\.s?css$/,
          use: [
            MiniCssExtractPlugin.loader,
            'css-loader',
            {
              loader: 'sass-loader',
              options: { api: 'modern' },
            },
          ],
        },
      ],
    },
    plugins: [
      new MiniCssExtractPlugin({ filename: '[name].css' }),
    ],
    optimization: {
      minimize: isProd,
    },
    performance: { hints: false },
    stats: 'minimal',
  };
};
