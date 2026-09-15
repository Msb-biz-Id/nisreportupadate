const CACHE_NAME = 'protrack-v5';
const ASSETS_TO_CACHE = [
  '/favicon.ico',
  '/manifest.json',
  '/pwa-icon-192.png',
  '/pwa-icon-512.png'
];

// Install Event: pre-cache static assets only (never cache dynamic HTML root '/')
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

// Activate Event: immediately purge all old PWA caches and claim clients
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            console.log('Purging old PWA cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event: strictly bypass navigation & inertia requests to allow dynamic Laravel & Vite updates
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Exclude non-GET, non-HTTP, API, auth/sanctum, and Inertia requests
  if (
    !request.url.startsWith('http') ||
    request.method !== 'GET' ||
    request.mode === 'navigate' ||
    request.headers.get('X-Inertia') ||
    url.pathname.startsWith('/api/') ||
    url.pathname.startsWith('/sanctum/')
  ) {
    // Let the browser handle network directly without service worker interference
    return;
  }

  // Network-First strategy for static assets
  event.respondWith(
    fetch(request)
      .then((networkResponse) => {
        if (networkResponse && networkResponse.status === 200) {
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

// Message Listener for explicit skipWaiting
self.addEventListener('message', (event) => {
  if (event.data && event.data.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.ports && event.ports[0]) {
    event.ports[0].postMessage({ status: 'ok' });
  }
});
