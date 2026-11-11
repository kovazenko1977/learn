const CACHE_NAME = 'medservice-pwa-v2';
const ASSETS_TO_CACHE = [
  './',
  './index.php',
  './manifest.json',
  './assets/css/style.css',
  './assets/js/app.js',
  './assets/icons/icon-192.png',
  './assets/icons/icon-512.png'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS_TO_CACHE);
    })
  );
  self.skipWaiting();
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((keys) => {
      return Promise.all(
        keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))
      );
    })
  );
  self.clients.claim();
});

self.addEventListener('fetch', (event) => {
  // Network first for API endpoints, Cache first for static shell
  if (event.request.url.includes('/api/')) {
    event.respondWith(
      fetch(event.request).catch(() => {
        return new Response(
          JSON.stringify({ offline: true, error: 'Нет подключения к интернету' }),
          { headers: { 'Content-Type': 'application/json' } }
        );
      })
    );
    return;
  }

  event.respondWith(
    caches.match(event.request).then((cachedResponse) => {
      if (cachedResponse) {
        fetch(event.request).then((networkResponse) => {
          if (networkResponse && networkResponse.status === 200) {
            caches.open(CACHE_NAME).then((cache) => cache.put(event.request, networkResponse));
          }
        }).catch(() => {});
        return cachedResponse;
      }
      return fetch(event.request);
    })
  );
});

// Deep Push Notification Handlers
self.addEventListener('push', (event) => {
  let data = { title: 'МедСервис', body: 'Новое уведомление в больнице', url: './index.php' };

  if (event.data) {
    try {
      data = event.data.json();
    } catch (e) {
      data.body = event.data.text();
    }
  }

  const options = {
    body: data.body || data.message,
    icon: 'assets/icons/icon-192.png',
    badge: 'assets/icons/icon-192.png',
    vibrate: [300, 100, 300, 100, 300],
    tag: data.tag || 'medservice-notification',
    renotify: true,
    data: { url: data.url || './index.php' },
    actions: [
      { action: 'open', title: '📱 Открыть приложение' },
      { action: 'dismiss', title: 'Закрыть' }
    ]
  };

  if ('setAppBadge' in self.navigator) {
    self.navigator.setAppBadge(1).catch(() => {});
  }

  event.waitUntil(
    self.registration.showNotification(data.title || 'МедСервис', options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  if (event.action === 'dismiss') return;

  const targetUrl = event.notification.data.url || './index.php';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url.includes('index.php') && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});
