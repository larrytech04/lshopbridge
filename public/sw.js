// Minimal service worker: exists so the site satisfies PWA installability
// checks and the app icon/manifest stay available offline. It does not cache
// pages or API responses — LshopBridge's content is dynamic/personalized, so
// pretending to support full offline use would be dishonest. Everything else
// simply passes straight through to the network.
const CACHE = 'lshopbridge-shell-v1';
const SHELL_ASSETS = [
    '/icons/icon-192.png',
    '/icons/icon-512.png',
    '/manifest.webmanifest',
];

self.addEventListener('install', (event) => {
    event.waitUntil(caches.open(CACHE).then((cache) => cache.addAll(SHELL_ASSETS)));
    self.skipWaiting();
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys().then((keys) => Promise.all(keys.filter((k) => k !== CACHE).map((k) => caches.delete(k))))
    );
    self.clients.claim();
});

self.addEventListener('fetch', (event) => {
    if (event.request.method !== 'GET') return;

    const url = new URL(event.request.url);
    if (!SHELL_ASSETS.includes(url.pathname)) return;

    event.respondWith(
        caches.match(event.request).then((cached) => cached || fetch(event.request))
    );
});
