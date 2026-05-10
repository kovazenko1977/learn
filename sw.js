const CACHE_NAME = 'sanatorium-pro-v1';
const ASSETS = [
  './index.php',
  './admin/index.php',
  './assets/css/admin.css',
  './assets/css/style.css',
  './manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return cache.addAll(ASSETS);
    })
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request).then((response) => {
      return response || fetch(event.request);
    })
  );
});
