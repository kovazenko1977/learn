const CACHE_NAME = 'alco-erp-v2';
const ASSETS = [
  'index.php',
  'https://cdn.tailwindcss.com',
  'https://cdn.jsdelivr.net/npm/lucide-static@0.321.0/font/lucide.css'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME).then(cache => cache.addAll(ASSETS))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request).then(response => response || fetch(event.request))
  );
});
