// Sofra service worker: makes the dashboard installable. It never caches
// pages or data (they are private and always live); it only shows a small
// offline notice when the network is down.
const OFFLINE = '<!doctype html><meta charset="utf-8"><meta name="viewport" content="width=device-width,initial-scale=1"><title>Sofra</title><body style="font-family:system-ui;background:#f4f2e7;color:#2b2b22;display:grid;place-items:center;min-height:100vh;margin:0;text-align:center"><p style="padding:24px;font-size:18px;line-height:1.8">لا يوجد اتصال بالإنترنت<br>No internet connection<br>İnternet bağlantısı yok</p>';

self.addEventListener('install', () => self.skipWaiting());
self.addEventListener('activate', (event) => event.waitUntil(self.clients.claim()));

self.addEventListener('fetch', (event) => {
    if (event.request.mode !== 'navigate') {
        return;
    }

    event.respondWith(fetch(event.request).catch(() => new Response(OFFLINE, {
        headers: { 'Content-Type': 'text/html; charset=utf-8' },
    })));
});