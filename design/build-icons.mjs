// Renders the favicon set from the SVG sources in this folder.
// Usage: node design/build-icons.mjs <output-dir>   (needs Playwright + Chromium, e.g. PW_PATH=/path/to/node_modules)
import { createRequire } from 'module';
import { readFileSync, writeFileSync, mkdirSync } from 'fs';
import { dirname, join } from 'path';
import { fileURLToPath } from 'url';

const require = createRequire(process.env.PW_PATH ? process.env.PW_PATH + '/' : import.meta.url);
const { chromium } = require('playwright');
const here = dirname(fileURLToPath(import.meta.url));
const out = process.argv[2] ?? join(here, '..', 'public');
mkdirSync(out, { recursive: true });

const browser = await chromium.launch({ executablePath: process.env.CHROME_PATH, args: ['--no-sandbox'] });
const page = await browser.newPage();

async function render(svgFile, size) {
    const svg = readFileSync(join(here, svgFile)).toString('base64');
    await page.setViewportSize({ width: size, height: size });
    await page.setContent(`<style>html,body{margin:0;background:transparent}img{display:block;width:${size}px;height:${size}px}</style><img src="data:image/svg+xml;base64,${svg}">`);
    return page.screenshot({ omitBackground: true, clip: { x: 0, y: 0, width: size, height: size } });
}

const files = {
    'apple-touch-icon.png': await render('apple-touch.svg', 180),
    'icon-192.png': await render('icon.svg', 192),
    'icon-512.png': await render('icon.svg', 512),
    'icon-maskable-512.png': await render('icon-maskable.svg', 512),
};
for (const [name, data] of Object.entries(files)) writeFileSync(join(out, name), data);

// favicon.ico: three PNG images (16, 32, 48) in one ICO container
const sizes = [16, 32, 48];
const pngs = [];
for (const s of sizes) pngs.push(await render('icon.svg', s));
const header = Buffer.alloc(6); header.writeUInt16LE(1, 2); header.writeUInt16LE(sizes.length, 4);
let offset = 6 + 16 * sizes.length; const entries = [];
sizes.forEach((s, i) => {
    const e = Buffer.alloc(16);
    e.writeUInt8(s, 0); e.writeUInt8(s, 1); e.writeUInt16LE(1, 4); e.writeUInt16LE(32, 6);
    e.writeUInt32LE(pngs[i].length, 8); e.writeUInt32LE(offset, 12);
    offset += pngs[i].length; entries.push(e);
});
writeFileSync(join(out, 'favicon.ico'), Buffer.concat([header, ...entries, ...pngs]));
writeFileSync(join(out, 'icon.svg'), readFileSync(join(here, 'icon.svg')));
await browser.close();
console.log('written to', out);
