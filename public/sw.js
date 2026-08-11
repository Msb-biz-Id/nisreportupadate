const CACHE_NAME = 'protrack-v2';
const ASSETS_TO_CACHE = [
  '/',
  '/favicon.ico',
  '/manifest.json',
  '/pwa-icon-192.png',
  '/pwa-icon-512.png'
];

// Install Event: pre-cache critical assets
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.all(
        ASSETS_TO_CACHE.map((url) => {
          return cache.add(url).catch((err) => {
            console.warn('Failed to cache asset during install:', url, err);
          });
        })
      );
    })
  );
});

// Activate Event: clean up old caches immediately
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('Deleting old PWA cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event: network-first for pages & build assets, fallback to cache
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Exclude non-HTTP/HTTPS requests, non-GET requests, hot reload, and external/api paths
  if (!request.url.startsWith('http') || request.method !== 'GET' || url.pathname.startsWith('/api/') || url.pathname.startsWith('/sanctum/')) {
    return;
  }

  // Network-First Strategy for build assets & pages so updates are served instantly
  event.respondWith(
    fetch(request)
      .then((networkResponse) => {
        if (networkResponse.status === 200) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            if (request.url.startsWith('http')) {
              cache.put(request, responseToCache);
            }
          });
        }
        return networkResponse;
      })
      .catch(() => {
        return caches.match(request);
      })
  );
});

// Safe Message Listener to prevent message channel closed errors
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.ports && event.ports[0]) {
    event.ports[0].postMessage({ status: 'ok' });
  }
});
