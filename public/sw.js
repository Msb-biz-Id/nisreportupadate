const CACHE_NAME = 'protrack-v1';
const ASSETS_TO_CACHE = [
  '/',
  '/favicon.ico',
  '/manifest.json',
  '/pwa-icon-192.png',
  '/pwa-icon-512.png'
];

// Install Event: pre-cache critical assets (soft caching to prevent aborting on redirects/errors)
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.all(
        ASSETS_TO_CACHE.map((url) => {
          return cache.add(url).catch((err) => {
            console.warn('Failed to cache asset during install:', url, err);
          });
        })
      );
    }).then(() => self.skipWaiting())
  );
});

// Activate Event: clean up old caches
self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys().then((cacheNames) => {
      return Promise.all(
        cacheNames.map((cache) => {
          if (cache !== CACHE_NAME) {
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event: network-first for pages, cache-first for static assets
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // Exclude non-HTTP/HTTPS requests, non-GET requests, hot reload, and external/api paths
  if (!request.url.startsWith('http') || request.method !== 'GET' || url.pathname.startsWith('/api/') || url.pathname.startsWith('/sanctum/')) {
    return;
  }

  // Check if it's a static asset (CSS, JS, images, fonts)
  const isStaticAsset = 
    url.pathname.includes('/build/') || 
    url.pathname.endsWith('.js') || 
    url.pathname.endsWith('.css') || 
    url.pathname.endsWith('.png') || 
    url.pathname.endsWith('.jpg') || 
    url.pathname.endsWith('.svg') || 
    url.pathname.endsWith('.woff2') || 
    url.pathname.endsWith('.ico');

  if (isStaticAsset) {
    // Cache-First Strategy
    event.respondWith(
      caches.match(request).then((cachedResponse) => {
        if (cachedResponse) {
          // Fetch new version in the background to update the cache (Stale-While-Revalidate)
          fetch(request).then((networkResponse) => {
            if (networkResponse.status === 200) {
              caches.open(CACHE_NAME).then((cache) => {
                if (request.url.startsWith('http')) {
                  cache.put(request, networkResponse);
                }
              });
            }
          }).catch(() => {/* Ignore network errors during background fetch */});
          return cachedResponse;
        }

        return fetch(request).then((networkResponse) => {
          if (networkResponse.status === 200) {
            const responseToCache = networkResponse.clone();
            caches.open(CACHE_NAME).then((cache) => {
              if (request.url.startsWith('http')) {
                cache.put(request, responseToCache);
              }
            });
          }
          return networkResponse;
        });
      })
    );
  } else {
    // Network-First Strategy for dynamic pages/Inertia responses
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
          return caches.match(request).then((cachedResponse) => {
            if (cachedResponse) {
              return cachedResponse;
            }
            // If completely offline and not in cache, fallback to '/'
            return caches.match('/');
          });
        })
    );
  }
});
