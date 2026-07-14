/**
 * Builds a distributable radiusforms.zip containing only the files WordPress.org
 * needs. The archive nests everything under an "radiusforms/" top-level folder so
 * it installs to wp-content/plugins/radiusforms.
 *
 * Run after `npm run build` (the `zip` / `release` npm scripts do this).
 */
import { createWriteStream, readdirSync, statSync } from 'fs';
import { join, relative, sep, extname } from 'path';
import archiver from 'archiver';

const root = process.cwd();
const slug = 'radiusforms';
const outFile = join(root, `${slug}.zip`);

// Directories and files excluded from the distributed build.
// `src/` + package.json + webpack.config.js SHIP with the plugin on purpose: the
// compiled bundles in assets/ are minified, and WordPress.org requires the original
// human-readable sources (or a public repo) to accompany them. Keeping them in the
// archive means the build is reproducible straight from the downloaded plugin.
const denyDirs = new Set([
  'node_modules', 'scripts', '.git', '.github', '.claude',
  '.idea', '.vscode', 'plugin-requirements',
]);
const denyFiles = new Set([
  'package-lock.json', 'vite.config.js',
  '.babelrc', '.distignore', '.gitignore', '.DS_Store', `${slug}.zip`,
]);
// Source maps and any Markdown (dev notes, review correspondence, specs) never
// belong in a distributed build — readme.txt is the only doc WordPress.org reads.
const denyExt = new Set(['.map', '.md']);

/**
 * Recursively collect distributable file paths relative to the plugin root.
 * @param {string} dir Directory to scan.
 * @param {string[]} acc Accumulator.
 * @returns {string[]}
 */
function collect(dir, acc = []) {
  for (const name of readdirSync(dir)) {
    const full = join(dir, name);
    const rel = relative(root, full);
    const st = statSync(full);
    if (st.isDirectory()) {
      if (denyDirs.has(name)) continue;
      collect(full, acc);
    } else {
      if (denyFiles.has(name)) continue;
      if (denyExt.has(extname(name))) continue;
      acc.push(rel);
    }
  }
  return acc;
}

const files = collect(root);

const output = createWriteStream(outFile);
const archive = archiver('zip', { zlib: { level: 9 } });

output.on('close', () => {
  const kb = (archive.pointer() / 1024).toFixed(1);
  // eslint-disable-next-line no-console
  console.log(`Created ${slug}.zip (${kb} KB, ${files.length} files)`);
});
archive.on('warning', (err) => { throw err; });
archive.on('error', (err) => { throw err; });

archive.pipe(output);
for (const rel of files) {
  archive.file(join(root, rel), { name: `${slug}/${rel.split(sep).join('/')}` });
}
archive.finalize();
