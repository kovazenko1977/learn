const CACHE_NAME = 'wes-med-v1';
const ASSETS = [
  '/',
  '/index.php',
  '/assets/css/style.css',
  'https://unpkg.com/lucide@latest'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
});

self.addEventListener('fetch', event => {
  // Only cache GET requests
  if (event.request.method !== 'GET') return;

  event.respondWith(
    caches.match(event.request).then(response => {
      return response || fetch(event.request).then(networkResponse => {
        // Cache new assets dynamically? Optional, but let's stick to base for now
        return networkResponse;
      });
    }).catch(() => {
        // Fallback or ignore
    })
  );
});
