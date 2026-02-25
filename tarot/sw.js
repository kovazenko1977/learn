const CACHE_NAME = 'tarot-scanner-v1';
const ASSETS = [
  'index.php',
  'assets/css/style.css',
  'assets/js/tarot.js',
  'data/cards.json',
  'manifest.json'
];

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(ASSETS))
  );
});

self.addEventListener('fetch', (event) => {
  event.respondWith(
    caches.match(event.request)
      .then((response) => response || fetch(event.request))
  );
});
