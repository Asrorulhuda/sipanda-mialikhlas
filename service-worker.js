const CACHE_NAME = 'sipanda-cache-v1';
const urlsToCache = [
  '/sipanda2/',
  '/sipanda2/index.php',
  'https://cdn.tailwindcss.com',
  'https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css'
];

self.addEventListener('install', event => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then(cache => {
        return cache.addAll(urlsToCache);
      })
  );
});

self.addEventListener('fetch', event => {
  const url = new URL(event.request.url);
  
  // Strategy: Network First, fallback to cache for HTML/API
  if (event.request.mode === 'navigate' || url.pathname.endsWith('.php') || url.pathname.includes('/api/')) {
    event.respondWith(
      fetch(event.request)
        .then(response => {
          // Clone and cache the successful response
          const responseToCache = response.clone();
          caches.open(CACHE_NAME).then(cache => {
            cache.put(event.request, responseToCache);
          });
          return response;
        })
        .catch(() => {
          // Offline fallback
          return caches.match(event.request);
        })
    );
  } else {
    // Strategy: Cache First, fallback to network for assets
    event.respondWith(
      caches.match(event.request)
        .then(response => {
          if (response) return response;
          return fetch(event.request).then(response => {
            if(!response || response.status !== 200 || response.type !== 'basic') {
              return response;
            }
            const responseToCache = response.clone();
            caches.open(CACHE_NAME).then(cache => {
              cache.put(event.request, responseToCache);
            });
            return response;
          });
        })
    );
  }
});
