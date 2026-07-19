const CACHE_NAME = 'berezina-v1';
const ASSETS = [
  'index.php',
  'procedures.php',
  'booking.php',
  'diagnostics.php',
  'contact.php',
  'assets/css/style.css',
  'assets/js/app.js'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => cache.addAll(ASSETS))
  );
});

self.addEventListener('fetch', event => {
  event.respondWith(
    caches.match(event.request)
      .then(response => response || fetch(event.request))
  );
});
