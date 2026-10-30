// Usage: PROMO_TOOLS=<dir with playwright-core> node capture.mjs stills out/dir t1 t2 ...
//        PROMO_TOOLS=... node capture.mjs frames out/dir
import { createRequire } from 'node:module';
import { pathToFileURL, fileURLToPath } from 'node:url';
import { mkdirSync } from 'node:fs';
import path from 'node:path';
const require = createRequire(path.join(process.env.PROMO_TOOLS, 'x.js'));
const { chromium } = require('playwright-core');
const [mode, outDir, ...times] = process.argv.slice(2);
mkdirSync(outDir, { recursive: true });
const url = pathToFileURL(path.join(path.dirname(fileURLToPath(import.meta.url)), 'index.html')).href + '?raw' + (process.env.PROMO_LANG ? '&lang=' + process.env.PROMO_LANG : '') + (process.env.PROMO_FMT ? '&fmt=' + process.env.PROMO_FMT : '') + (process.env.PROMO_TEXT ? '&text=' + process.env.PROMO_TEXT : '');
const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', args: ['--allow-file-access-from-files'] });
const page = await browser.newPage({ viewport: process.env.PROMO_FMT === 'reel' ? { width: 1080, height: 1920 } : { width: 1920, height: 1080 } });
page.on('console', m => m.type() === 'error' && console.log('console:', m.text()));
page.on('pageerror', e => console.log('pageerror:', e.message));
await page.goto(url);
await page.waitForFunction('window.READY===true');
if (mode === 'stills') {
  for (const t of times) { await page.evaluate(t => seek(t), +t); await page.screenshot({ path: path.join(outDir, `t${t}.png`) }); }
} else {
  const n = await page.evaluate('Math.round(window.DUR*window.FPS)');
  for (let i = 0; i < n; i++) {
    await page.evaluate(t => seek(t), i / 30);
    await page.screenshot({ path: path.join(outDir, `f${String(i).padStart(5, '0')}.jpg`), type: 'jpeg', quality: 96 });
    if (i % 100 === 0) console.log('frame', i, '/', n);
  }
}
await browser.close();
