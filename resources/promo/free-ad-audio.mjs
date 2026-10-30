// Synthesises the music bed + sound design for the 18s free-ad from its cue list (window.CUES).
import { writeFileSync } from 'node:fs';

const SR = 44100;
export function synth(cues, dur, outPath) {
  const N = Math.ceil((dur + .8) * SR), L = new Float32Array(N), R = new Float32Array(N);
  let seed = 11; const rnd = () => ((seed = (seed * 16807) % 2147483647) / 2147483647) * 2 - 1;
  const add = (t, b, g = 1, pan = 0) => {
    const i0 = Math.round(t * SR), gl = g * (1 - Math.max(0, pan)), gr = g * (1 + Math.min(0, pan));
    for (let i = 0; i < b.length && i0 + i < N; i++) { L[i0 + i] += b[i] * gl; R[i0 + i] += b[i] * gr; }
  };
  const tone = (f, len, { a = .004, d = .15, h = [1], f2 = f } = {}) => {
    const n = Math.round(len * SR), b = new Float32Array(n); let ph = 0;
    for (let i = 0; i < n; i++) {
      ph += 2 * Math.PI * (f + (f2 - f) * i / n) / SR;
      let s = 0; for (let k = 0; k < h.length; k++) s += Math.sin(ph * (k + 1)) * h[k];
      b[i] = s * Math.min(1, i / (a * SR)) * Math.exp(-i / (d * SR)) * Math.min(1, (n - i) / (.01 * SR));
    } return b;
  };
  const noise = (len, f0, f1, { peak = .5, shape = 'bell', hp = 0 } = {}) => {
    const n = Math.round(len * SR), b = new Float32Array(n); let lp = 0, lp2 = 0, hpv = 0;
    for (let i = 0; i < n; i++) {
      const p = i / n, fc = f0 + (f1 - f0) * p, k = 1 - Math.exp(-2 * Math.PI * fc / SR);
      const x = rnd(); lp += k * (x - lp); lp2 += k * (lp - lp2);
      let y = (lp - lp2) * 6;
      if (hp) { hpv += (1 - Math.exp(-2 * Math.PI * hp / SR)) * (x - hpv); y = x - hpv; }
      const env = shape === 'bell' ? Math.sin(Math.PI * p) ** 2 : shape === 'rise' ? p * p : Math.exp(-i / (len * SR * .2));
      b[i] = y * env * peak;
    } return b;
  };
  const mix = (...bs) => { const n = Math.max(...bs.map(b => b.length)), o = new Float32Array(n); bs.forEach(b => b.forEach((v, i) => o[i] += v)); return o; };
  const mtof = m => 440 * Math.pow(2, (m - 69) / 12);

  // ---- music: 120 BPM, 2s bars: Am F C G Am F C G C
  const beat = .5, bars = [[57, 60, 64], [53, 57, 60], [48, 52, 55], [55, 59, 62], [57, 60, 64], [53, 57, 60], [48, 52, 55], [55, 59, 62], [48, 52, 55]];
  const roots = [45, 41, 48, 43, 45, 41, 48, 43, 48];
  const kick = () => mix(tone(52, .3, { a: .001, d: .09, f2: 52 }).map((v, i) => v), (() => { const n = Math.round(.2 * SR), b = new Float32Array(n); let ph = 0; for (let i = 0; i < n; i++) { ph += 2 * Math.PI * (45 + 120 * Math.exp(-i / (.025 * SR))) / SR; b[i] = Math.sin(ph) * Math.exp(-i / (.07 * SR)); } return b; })());
  const hat = (g = 1) => noise(.05, 7000, 9000, { shape: 'decay', peak: .5 * g, hp: 6500 });
  const clap = () => mix(noise(.14, 1500, 1500, { shape: 'decay', peak: 1.1, hp: 900 }), noise(.03, 2200, 2200, { shape: 'decay', peak: .8, hp: 1200 }));
  const nBars = bars.length;
  for (let b = 0; b < nBars; b++) {
    const t0 = b * 2, chord = bars[b];
    add(t0, mix(...chord.map((m, k) => tone(mtof(m - 12), 2.1, { a: .35, d: 1.6, h: [1, .25] }))), .05, k0(b));     // pad
    function k0(x) { return x % 2 ? .15 : -.15; }
    for (let s = 0; s < 8; s++) {                                                                                   // 8th grid
      const t = t0 + s * beat / 2;
      if (t >= dur) break;
      add(t, hat(s % 2 ? 1 : .55), t < 3 ? .035 : .06, s % 2 ? .25 : -.25);
      if (t >= 3 && t < 13) {                                                                                       // arp (verse)
        const idx = [0, 1, 2, 1, 0, 1, 2, 1][s], oct = (s === 6 || s === 7) ? 12 : 0;
        add(t, tone(mtof(chord[idx] + 12 + oct), .45, { a: .003, d: .11, h: [1, .35, .12] }), .075, s % 2 ? .3 : -.3);
      }
      if (t >= 13.5) {                                                                                              // arp (CTA, brighter, doubled)
        const idx = [0, 2, 1, 2, 0, 2, 1, 2][s];
        add(t, tone(mtof(chord[idx] + 12), .45, { a: .003, d: .12, h: [1, .4, .15] }), .08, -.25);
        add(t, tone(mtof(chord[idx] + 24), .35, { a: .003, d: .09, h: [1, .2] }), .045, .25);
      }
    }
    for (let bt = 0; bt < 4; bt++) {
      const t = t0 + bt * beat;
      if (t >= dur) break;
      const loud = t >= 3 && !(t >= 13 && t < 13.5);
      if (t < 3 && bt % 2 === 0) add(t, kick(), .22);                      // tense heartbeat in the hook
      if (loud) add(t, kick(), t >= 13.5 ? .62 : .5);
      if (t >= 10 && bt % 2 === 1) add(t, clap(), t >= 13.5 ? .34 : .26);
    }
    if (t0 >= 3) for (const [off, len] of [[0, .45], [.75, .2], [1, .45], [1.75, .2]]) {                            // bass
      const t = t0 + off; if (t >= dur) continue;
      add(t, tone(mtof(roots[b]), len + .1, { a: .005, d: .22, h: [1, .35, .1] }), .2);
    }
  }
  // ---- sfx
  const FX = {
    whoosh: v => [[0, noise(.8, 400, 3600, { peak: .7 }), .5 * v]],
    pop: v => [[0, tone(520, .16, { a: .002, d: .05, h: [1, .15], f2: 820 }), .3 * v]],
    tick: v => [[0, tone(2100, .08, { a: .001, d: .02, h: [1, .2] }), .16 * v]],
    thud: v => [[0, tone(150, .22, { a: .002, d: .07, f2: 62 }), .55 * v], [0, noise(.04, 2500, 2500, { shape: 'decay', hp: 1500, peak: .6 }), .2 * v]],
    pen: v => [[0, noise(.16, 1800, 5200, { peak: .8, hp: 1800 }), .22 * v]],
    chime: v => [[0, tone(988, 1, { a: .003, d: .24, h: [1, .35, .1] }), .2 * v], [.1, tone(1319, 1.2, { a: .003, d: .3, h: [1, .3, .1] }), .17 * v]],
    ding: v => [[0, tone(1568, .8, { a: .002, d: .2, h: [1, .3] }), .22 * v], [.09, tone(2093, 1, { a: .002, d: .26, h: [1, .25] }), .18 * v]],
    sweep: v => [[0, tone(380, .95, { a: .02, d: 2, h: [1, .15], f2: 1500 }), .09 * v]],
    riser: v => [[0, noise(1, 500, 7000, { shape: 'rise', peak: .8 }), .45 * v], [0, tone(220, 1, { a: .3, d: 4, h: [1, .3], f2: 880 }), .09 * v]],
    impact: v => [[0, tone(70, 1.2, { a: .002, d: .4, f2: 38 }), .85 * v], [0, noise(1.1, 3000, 9000, { shape: 'decay', peak: 1, hp: 3000 }), .3 * v]],
    sparkle: v => [[0, tone(2637, .5, { a: .002, d: .1 }), .1 * v], [.07, tone(3136, .5, { a: .002, d: .1 }), .09 * v], [.14, tone(3951, .6, { a: .002, d: .14 }), .08 * v], [.22, tone(5274, .6, { a: .002, d: .14 }), .05 * v]],
  };
  for (const c of cues) (FX[c.k]?.(c.v) || []).forEach(([off, b, g]) => add(Math.max(0, c.t + off), b, g));

  // small room
  const rev = (ch, ds) => { const o = new Float32Array(ch.length); ds.forEach(([ms, g]) => { const d = Math.round(ms / 1000 * SR); for (let i = d; i < ch.length; i++) o[i] += ch[i - d] * g; }); return o; };
  const rL = rev(L, [[41, .17], [67, .12], [109, .08], [163, .05]]), rR = rev(R, [[47, .17], [73, .12], [113, .08], [173, .05]]);
  let peak = 0; for (let i = 0; i < N; i++) { L[i] += rL[i]; R[i] += rR[i]; peak = Math.max(peak, Math.abs(L[i]), Math.abs(R[i])); }
  const g = .85 / peak, buf = Buffer.alloc(44 + N * 4);
  buf.write('RIFF', 0); buf.writeUInt32LE(36 + N * 4, 4); buf.write('WAVEfmt ', 8); buf.writeUInt32LE(16, 16); buf.writeUInt16LE(1, 20); buf.writeUInt16LE(2, 22);
  buf.writeUInt32LE(SR, 24); buf.writeUInt32LE(SR * 4, 28); buf.writeUInt16LE(4, 32); buf.writeUInt16LE(16, 34); buf.write('data', 36); buf.writeUInt32LE(N * 4, 40);
  for (let i = 0; i < N; i++) { buf.writeInt16LE(Math.round(Math.max(-1, Math.min(1, L[i] * g)) * 32767), 44 + i * 4); buf.writeInt16LE(Math.round(Math.max(-1, Math.min(1, R[i] * g)) * 32767), 46 + i * 4); }
  writeFileSync(outPath, buf);
}
