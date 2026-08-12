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

// Real OS-level push notifications (see App\Notifications\Concerns\Derives
// WebPushFromMail on the backend, which builds this exact payload shape).
// Every field maps straight onto the Notification API's own options object.
self.addEventListener('push', (event) => {
    if (!event.data) return;

    let payload;
    try {
        payload = event.data.json();
    } catch (e) {
        return;
    }

    const title = payload.title || 'LshopBridge';
    const options = {
        body: payload.body || '',
        icon: payload.icon || '/icons/icon-192.png',
        badge: payload.badge || '/icons/icon-192.png',
        data: payload.data || {},
        tag: payload.tag || undefined,
        requireInteraction: !!payload.requireInteraction,
    };

    event.waitUntil(self.registration.showNotification(title, options));
});

// Tapping the notification focuses an already-open tab on that URL if one
// exists, otherwise opens a fresh one — never just closes silently.
self.addEventListener('notificationclick', (event) => {
    event.notification.close();
    const url = (event.notification.data && event.notification.data.url) || '/dashboard';

    event.waitUntil(
        self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientsList) => {
            for (const client of clientsList) {
                if (client.url === url && 'focus' in client) return client.focus();
            }

            return self.clients.openWindow ? self.clients.openWindow(url) : null;
        })
    );
});
