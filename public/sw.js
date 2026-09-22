const CACHE_NAME = 'protrack-v6';
const ASSETS_TO_CACHE = [
  '/favicon.ico',
  '/manifest.json',
  '/pwa-icon-192.png',
  '/pwa-icon-512.png'
];

// Paths that must NEVER be intercepted by the Service Worker
// — dynamic API endpoints, auth, notifications, invoices, etc.
const BYPASS_PATHS = [
  '/api/',
  '/sanctum/',
  '/notifications',
  '/invoices/',
  '/users',
  '/orders',
  '/broadcasting/',
];

// Install Event: pre-cache static assets only
self.addEventListener('install', (event) => {
  self.skipWaiting();
  event.waitUntil(
    caches.open(CACHE_NAME).then((cache) => {
      return Promise.all(
        ASSETS_TO_CACHE.map((url) => {
          return cache.add(url).catch((err) => {
            console.warn('[SW] Failed to cache asset during install:', url, err);
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
            console.log('[SW] Purging old PWA cache:', cache);
            return caches.delete(cache);
          }
        })
      );
    }).then(() => self.clients.claim())
  );
});

// Fetch Event: Network-first with proper offline fallback
self.addEventListener('fetch', (event) => {
  const request = event.request;
  const url = new URL(request.url);

  // --- Bypass conditions: let browser handle these directly ---
  // 1. Non-GET requests (POST, PUT, DELETE, PATCH)
  if (request.method !== 'GET') return;

  // 2. Non-HTTP(S) schemes (chrome-extension://, data:, blob:, etc.)
  if (!request.url.startsWith('http')) return;

  // 3. Navigation requests (full page loads / Inertia page transitions)
  if (request.mode === 'navigate') return;

  // 4. Inertia.js XHR requests
  if (request.headers.get('X-Inertia')) return;

  // 5. Dynamic application paths that must always be fresh from network
  const isBypassPath = BYPASS_PATHS.some((path) => url.pathname.startsWith(path));
  if (isBypassPath) return;

  // 6. Cross-origin requests (e.g. Google Fonts, Pusher, etc.) - only handle same-origin or CDN assets
  const isSameOrigin = url.origin === self.location.origin;
  const isTrustedCdn = url.hostname.includes('fonts.bunny.net') ||
                       url.hostname.includes('fonts.googleapis.com') ||
                       url.hostname.includes('fonts.gstatic.com');
  if (!isSameOrigin && !isTrustedCdn) return;

  // --- Network-first strategy with graceful offline fallback ---
  event.respondWith(
    fetch(request)
      .then((networkResponse) => {
        // Only cache valid 200 responses for static assets (JS/CSS/fonts/images)
        if (
          networkResponse &&
          networkResponse.status === 200 &&
          networkResponse.type !== 'opaque' &&
          (
            url.pathname.match(/\.(js|css|woff2?|ttf|eot|svg|png|jpg|jpeg|gif|ico|webp)(\?.*)?$/) ||
            isTrustedCdn
          )
        ) {
          const responseToCache = networkResponse.clone();
          caches.open(CACHE_NAME).then((cache) => {
            cache.put(request, responseToCache);
          });
        }
        return networkResponse;
      })
      .catch(async () => {
        // Network failed — attempt to serve from cache
        const cached = await caches.match(request);

        // Return cached version if available
        if (cached) return cached;

        // Nothing in cache — return a proper 503 Response (NOT undefined!)
        // This prevents the "Failed to convert value to 'Response'" TypeError
        return new Response(
          JSON.stringify({ error: 'offline', message: 'Resource not available offline.' }),
          {
            status: 503,
            statusText: 'Service Unavailable',
            headers: {
              'Content-Type': 'application/json',
              'X-SW-Offline': '1',
            },
          }
        );
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
