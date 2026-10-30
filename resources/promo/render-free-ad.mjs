// Renders sofra-free-ad.html with headless Chrome (CDP, no dependencies).
// Usage: node render-free-ad.mjs stills <outDir> <t1> <t2> ...
//        node render-free-ad.mjs video <out.mp4> [tmpDir]
import { spawn } from 'node:child_process';
import { mkdtempSync, mkdirSync, readFileSync, existsSync, writeFileSync } from 'node:fs';
import { pathToFileURL, fileURLToPath } from 'node:url';
import { tmpdir } from 'node:os';
import path from 'node:path';
import { synth } from './free-ad-audio.mjs';

const CHROME = 'C:/Program Files/Google/Chrome/Application/chrome.exe';
const here = path.dirname(fileURLToPath(import.meta.url));
const [mode, out, ...rest] = process.argv.slice(2);

const prof = mkdtempSync(path.join(tmpdir(), 'sofra-ad-'));
const chrome = spawn(CHROME, ['--headless=new', '--remote-debugging-port=0', `--user-data-dir=${prof}`, '--allow-file-access-from-files', '--hide-scrollbars', '--no-first-run', '--force-color-profile=srgb', 'about:blank'], { stdio: 'ignore' });
let port, wsPath;
for (let i = 0; i < 100 && !port; i++) {
  await new Promise(r => setTimeout(r, 100));
  const f = path.join(prof, 'DevToolsActivePort');
  if (existsSync(f)) [port, wsPath] = readFileSync(f, 'utf8').trim().split('\n');
}
const ws = new WebSocket(`ws://127.0.0.1:${port}${wsPath}`);
await new Promise(r => (ws.onopen = r));
let id = 0; const pending = new Map();
ws.onmessage = e => { const m = JSON.parse(e.data); if (m.id && pending.has(m.id)) { const { res, rej } = pending.get(m.id); pending.delete(m.id); m.error ? rej(new Error(m.error.message)) : res(m.result); } };
const send = (method, params = {}, sessionId) => new Promise((res, rej) => { const i = ++id; pending.set(i, { res, rej }); ws.send(JSON.stringify({ id: i, method, params, sessionId })); });

const { targetId } = await send('Target.createTarget', { url: 'about:blank' });
const { sessionId } = await send('Target.attachToTarget', { targetId, flatten: true });
const S = (m, p) => send(m, p, sessionId);
await S('Emulation.setDeviceMetricsOverride', { width: 1080, height: 1920, deviceScaleFactor: 1, mobile: false });
await S('Page.enable');
await S('Page.navigate', { url: pathToFileURL(path.join(here, 'sofra-free-ad.html')).href + (process.env.AD_LANG ? `?lang=${process.env.AD_LANG}` : '') });
const ev = async expr => (await S('Runtime.evaluate', { expression: expr, returnByValue: true, awaitPromise: true })).result;
for (let i = 0; i < 100; i++) { if ((await ev('window.READY===true')).value) break; await new Promise(r => setTimeout(r, 100)); }
const shot = async (t, fmt = 'jpeg') => {
  await ev(`seek(${t})`);
  const { data } = await S('Page.captureScreenshot', fmt === 'png' ? { format: 'png' } : { format: 'jpeg', quality: 95 });
  return Buffer.from(data, 'base64');
};

try {
  if (mode === 'stills') {
    mkdirSync(out, { recursive: true });
    for (const t of rest) writeFileSync(path.join(out, `t${t}.png`), await shot(+t, 'png'));
  } else {
    const dur = (await ev('window.DUR')).value, fps = (await ev('window.FPS')).value, cues = (await ev('window.CUES')).value;
    const tmp = rest[0] || tmpdir(); mkdirSync(tmp, { recursive: true });
    const wav = path.join(tmp, 'sofra-free-ad.wav');
    synth(cues, dur, wav);
    const ff = spawn('ffmpeg', ['-y', '-f', 'image2pipe', '-framerate', String(fps), '-c:v', 'mjpeg', '-i', '-', '-i', wav,
      '-c:v', 'libx264', '-preset', 'slow', '-crf', '17', '-pix_fmt', 'yuv420p', '-r', String(fps),
      '-af', `afade=t=out:st=${dur - .7}:d=0.7,loudnorm=I=-14:TP=-1.5:LRA=11`, '-c:a', 'aac', '-b:a', '192k', '-ar', '48000', '-t', String(dur), '-movflags', '+faststart', out], { stdio: ['pipe', 'ignore', 'inherit'] });
    const n = Math.round(dur * fps);
    for (let i = 0; i < n; i++) {
      const buf = await shot(i / fps);
      if (!ff.stdin.write(buf)) await new Promise(r => ff.stdin.once('drain', r));
      if (i % 90 === 0) console.log('frame', i, '/', n);
    }
    ff.stdin.end(); await new Promise(r => ff.on('close', r));
    console.log('done', out);
  }
} finally { ws.close(); chrome.kill(); }
