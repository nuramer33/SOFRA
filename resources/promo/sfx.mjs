// Synthesises the soft UI sound design from the cue sheet in index.html (window.CUES).
// Usage: PROMO_TOOLS=<dir with playwright-core> node sfx.mjs out.wav
import { createRequire } from 'node:module';
import { pathToFileURL, fileURLToPath } from 'node:url';
import { writeFileSync } from 'node:fs';
import path from 'node:path';
const require = createRequire(path.join(process.env.PROMO_TOOLS, 'x.js'));
const { chromium } = require('playwright-core');
const browser = await chromium.launch({ executablePath: 'C:/Program Files/Google/Chrome/Application/chrome.exe', args: ['--allow-file-access-from-files'] });
const page = await browser.newPage();
await page.goto(pathToFileURL(path.join(path.dirname(fileURLToPath(import.meta.url)), 'index.html')).href + '?raw');
await page.waitForFunction('window.READY===true');
const { cues, dur } = await page.evaluate(() => ({ cues: window.CUES, dur: window.DUR }));
await browser.close();

const SR = 48000, N = Math.ceil((dur + 1) * SR);
const L = new Float32Array(N), R = new Float32Array(N);
let seed = 7; const rnd = () => ((seed = (seed * 16807) % 2147483647) / 2147483647) * 2 - 1;
const add = (t, buf, g = 1, pan = 0) => {
  const i0 = Math.round(t * SR), gl = g * (1 - Math.max(0, pan)), gr = g * (1 + Math.min(0, pan));
  for (let i = 0; i < buf.length && i0 + i < N; i++) { L[i0 + i] += buf[i] * gl; R[i0 + i] += buf[i] * gr; }
};
const env = (i, n, a, d) => Math.min(1, i / (a * SR)) * Math.exp(-i / (d * SR));
function tone(f, len, a = .004, d = .12, harm = [1], f2 = f) {
  const n = Math.round(len * SR), b = new Float32Array(n); let ph = 0;
  for (let i = 0; i < n; i++) {
    const fr = f + (f2 - f) * (i / n); ph += 2 * Math.PI * fr / SR;
    let s = 0; harm.forEach((h, k) => s += Math.sin(ph * (k + 1)) * h);
    b[i] = s * env(i, n, a, d);
  } return b;
}
function air(len, f0, f1, peak = .5) {                      // band-limited noise swell
  const n = Math.round(len * SR), b = new Float32Array(n); let lp = 0, lp2 = 0;
  for (let i = 0; i < n; i++) {
    const p = i / n, fc = f0 + (f1 - f0) * p, k = 1 - Math.exp(-2 * Math.PI * fc / SR);
    lp += k * (rnd() - lp); lp2 += k * (lp - lp2);
    b[i] = (lp - lp2) * 6 * Math.sin(Math.PI * p) ** 2 * peak;
  } return b;
}
function click(len = .02, f = 3000) { const n = Math.round(len * SR), b = new Float32Array(n); let lp = 0; const k = 1 - Math.exp(-2 * Math.PI * f / SR);
  for (let i = 0; i < n; i++) { lp += k * (rnd() - lp); b[i] = lp * Math.exp(-i / (.004 * SR)); } return b; }
const mixb = (...bs) => { const n = Math.max(...bs.map(b => b.length)), o = new Float32Array(n); bs.forEach(b => b.forEach((v, i) => o[i] += v)); return o; };
const scale = (b, g) => b.map(v => v * g);
const K = {
  air: () => [[0, air(1.6, 500, 1800, .5), .35]],
  glide: () => [[0, air(1.1, 400, 2200, .6), .4], [.55, tone(392, .5, .02, .22, [1, .2]), .05]],
  pan: () => [[0, air(1.5, 350, 1600, .6), .38]],
  settle: () => [[0, tone(196, .7, .01, .25, [1, .3]), .13], [0, click(.03, 1800), .1]],
  tap: () => [[0, tone(150, .12, .002, .035, [1], 85), .32], [0, click(.02, 2400), .12]],
  pop: () => [[0, tone(560, .16, .003, .05, [1, .15], 840), .16]],
  tick: () => [[0, tone(1900, .09, .001, .018, [1, .2]), .12]],
  lang: () => [[0, tone(1180, .1, .002, .028), .1], [.075, tone(1580, .12, .002, .035), .09]],
  bar: () => [[0, tone(440, .3, .01, .09, [1, .25]), .1], [.08, tone(660, .34, .01, .1, [1, .2]), .09]],
  sheet: () => [[0, air(.7, 300, 1400, .5), .3], [.45, tone(262, .4, .01, .15), .05]],
  confirm: () => [[0, tone(660, .4, .004, .13, [1, .25]), .11], [.1, tone(990, .5, .004, .16, [1, .2]), .1]],
  chime: () => [[0, tone(988, .9, .003, .22, [1, .35, .1]), .15], [.16, tone(1319, 1.1, .003, .3, [1, .3, .1]), .13]],
  color: () => [[0, tone(2093, .5, .001, .09, [1, .3]), .06], [.04, tone(3136, .5, .001, .06), .035]],
  resolve: () => [[0, mixb(...[220, 277.2, 329.6, 440].map((f, i) => { const n = Math.round(3.2 * SR), b = new Float32Array(n); for (let j = 0; j < n; j++) { const e = Math.min(1, j / (.45 * SR)) * Math.exp(-j / (1.3 * SR)); b[j] = Math.sin(2 * Math.PI * f * j / SR) * e * (i === 3 ? .6 : 1); } return b; })), .08]],
};
const KEEP = new Set(['tick', 'pop', 'chime', 'color', 'confirm', 'lang']);
for (const c0 of cues) {
  const c = c0.k === 'tap' ? { ...c0, k: 'tick', v: c0.v * .5 } : c0;
  if (!KEEP.has(c.k)) continue;
  K[c.k]().forEach(([off, b, g]) => add(c.t + off, b, g * c.v, 0));
}

// small room: three combs for a touch of space
const rev = (ch, delays) => { const o = new Float32Array(ch.length); delays.forEach(([ms, g]) => { const d = Math.round(ms / 1000 * SR); for (let i = d; i < ch.length; i++) o[i] += ch[i - d] * g; }); return o; };
const rL = rev(L, [[37, .16], [61, .11], [97, .07]]), rR = rev(R, [[43, .16], [71, .11], [109, .07]]);
let peak = 0; for (let i = 0; i < N; i++) { L[i] += rL[i]; R[i] += rR[i]; peak = Math.max(peak, Math.abs(L[i]), Math.abs(R[i])); }
const g = .3 / peak, buf = Buffer.alloc(44 + N * 4);
buf.write('RIFF', 0); buf.writeUInt32LE(36 + N * 4, 4); buf.write('WAVEfmt ', 8); buf.writeUInt32LE(16, 16); buf.writeUInt16LE(1, 20); buf.writeUInt16LE(2, 22);
buf.writeUInt32LE(SR, 24); buf.writeUInt32LE(SR * 4, 28); buf.writeUInt16LE(4, 32); buf.writeUInt16LE(16, 34); buf.write('data', 36); buf.writeUInt32LE(N * 4, 40);
for (let i = 0; i < N; i++) { buf.writeInt16LE(Math.round(Math.max(-1, Math.min(1, L[i] * g)) * 32767), 44 + i * 4); buf.writeInt16LE(Math.round(Math.max(-1, Math.min(1, R[i] * g)) * 32767), 46 + i * 4); }
writeFileSync(process.argv[2], buf); console.log('wrote', process.argv[2], (N / SR).toFixed(1) + 's', 'peak', (peak * g).toFixed(2));
