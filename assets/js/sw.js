/**
 * Medium Clone — Service Worker v2.0
 * Stratégies : Cache First | Network First | Stale-While-Revalidate
 */

const SW_VERSION   = 'mc-sw-v2.0.0';
const CACHE_STATIC = `${SW_VERSION}-static`;
const CACHE_PAGES  = `${SW_VERSION}-pages`;
const CACHE_IMAGES = `${SW_VERSION}-images`;
const CACHE_API    = `${SW_VERSION}-api`;

const OFFLINE_URL  = '/offline';

// Assets à pré-cacher lors de l'installation
const PRECACHE_ASSETS = [
  '/',
  OFFLINE_URL,
];

// ─── INSTALL : pré-cache des assets critiques ───────────────────────────────
self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_STATIC).then((cache) =>
      cache.addAll(PRECACHE_ASSETS).catch(() => {
        // On ne bloque pas si offline.php n'est pas encore accessible
      })
    ).then(() => self.skipWaiting())
  );
});

// ─── ACTIVATE : nettoyage des anciens caches ─────────────────────────────────
self.addEventListener('activate', (event) => {
  const VALID_CACHES = [CACHE_STATIC, CACHE_PAGES, CACHE_IMAGES, CACHE_API];

  event.waitUntil(
    caches.keys().then((keys) =>
      Promise.all(
        keys
          .filter((k) => !VALID_CACHES.includes(k))
          .map((k) => caches.delete(k))
      )
    ).then(() => self.clients.claim())
  );
});

// ─── FETCH : routage des stratégies ──────────────────────────────────────────
self.addEventListener('fetch', (event) => {
  const { request } = event;
  const url = new URL(request.url);

  // Ignorés : admin, ajax, cron, non-GET
  if (
    request.method !== 'GET' ||
    url.pathname.startsWith('/wp-admin') ||
    url.pathname.startsWith('/wp-login') ||
    url.pathname === '/wp-cron.php' ||
    url.pathname.includes('admin-ajax.php')
  ) {
    return;
  }

  // 1. API REST WordPress → Stale-While-Revalidate
  if (url.pathname.startsWith('/wp-json/')) {
    event.respondWith(staleWhileRevalidate(request, CACHE_API));
    return;
  }

  // 2. Images → Cache First (7 jours)
  if (request.destination === 'image') {
    event.respondWith(cacheFirst(request, CACHE_IMAGES, 7 * 24 * 60 * 60));
    return;
  }

  // 3. CSS, JS, Fonts → Cache First (30 jours)
  if (
    request.destination === 'style' ||
    request.destination === 'script' ||
    request.destination === 'font' ||
    url.origin === 'https://fonts.googleapis.com' ||
    url.origin === 'https://fonts.gstatic.com' ||
    url.origin === 'https://cdnjs.cloudflare.com' ||
    url.origin === 'https://cdn.jsdelivr.net' ||
    url.origin === 'https://cdn.quilljs.com'
  ) {
    event.respondWith(cacheFirst(request, CACHE_STATIC, 30 * 24 * 60 * 60));
    return;
  }

  // 4. Pages HTML → Network First avec fallback offline
  if (request.headers.get('Accept')?.includes('text/html')) {
    event.respondWith(networkFirstWithOfflineFallback(request));
    return;
  }

  // 5. Autres → Network only (pas de cache)
  // (on laisse le browser gérer)
});

// ─── BACKGROUND SYNC : "Read Later" ─────────────────────────────────────────
self.addEventListener('sync', (event) => {
  if (event.tag === 'mc-read-later-sync') {
    event.waitUntil(syncReadLater());
  }
});

// ─── PUSH NOTIFICATIONS ───────────────────────────────────────────────────────
self.addEventListener('push', (event) => {
  if (!event.data) return;

  let data = {};
  try {
    data = event.data.json();
  } catch {
    data = { title: 'Medium Clone', body: event.data.text() };
  }

  const options = {
    body: data.body || 'Vous avez une nouvelle notification.',
    icon: '/wp-content/themes/theme-medium-clone/assets/images/icons/icon-192x192.png',
    badge: '/wp-content/themes/theme-medium-clone/assets/images/icons/icon-72x72.png',
    vibrate: [100, 50, 100],
    data: { url: data.url || '/', notificationId: data.id },
    actions: [
      { action: 'open',    title: 'Lire',   icon: '/wp-content/themes/theme-medium-clone/assets/images/icons/icon-72x72.png' },
      { action: 'dismiss', title: 'Ignorer' }
    ],
    tag:    data.tag || 'mc-notification',
    renotify: true,
  };

  event.waitUntil(
    self.registration.showNotification(data.title || 'Medium Clone', options)
  );
});

self.addEventListener('notificationclick', (event) => {
  event.notification.close();

  if (event.action === 'dismiss') return;

  const targetUrl = event.notification.data?.url || '/';

  event.waitUntil(
    clients.matchAll({ type: 'window', includeUncontrolled: true }).then((clientList) => {
      for (const client of clientList) {
        if (client.url === targetUrl && 'focus' in client) {
          return client.focus();
        }
      }
      if (clients.openWindow) {
        return clients.openWindow(targetUrl);
      }
    })
  );
});

// ─── MESSAGE : force update ───────────────────────────────────────────────────
self.addEventListener('message', (event) => {
  if (event.data?.type === 'SKIP_WAITING') {
    self.skipWaiting();
  }
  if (event.data?.type === 'CACHE_PAGE') {
    caches.open(CACHE_PAGES).then(cache => cache.add(event.data.url));
  }
});

// ═══════════════════════════════════════════════════════════════════════════════
// HELPERS — Stratégies de cache
// ═══════════════════════════════════════════════════════════════════════════════

/**
 * Cache First : on retourne le cache si disponible, sinon réseau + mise en cache
 */
async function cacheFirst(request, cacheName, maxAgeSeconds = 86400) {
  const cache    = await caches.open(cacheName);
  const cached   = await cache.match(request);

  if (cached) {
    // Vérifie l'âge du cache via l'en-tête Date
    const dateHeader = cached.headers.get('date');
    if (dateHeader) {
      const age = (Date.now() - new Date(dateHeader).getTime()) / 1000;
      if (age < maxAgeSeconds) return cached;
    } else {
      return cached;
    }
  }

  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    return cached || new Response('Ressource non disponible hors ligne.', { status: 503 });
  }
}

/**
 * Network First : on essaie le réseau, fallback sur cache, puis page offline
 */
async function networkFirstWithOfflineFallback(request) {
  const cache = await caches.open(CACHE_PAGES);

  try {
    const response = await fetch(request);
    if (response.ok) {
      cache.put(request, response.clone());
    }
    return response;
  } catch {
    const cached = await cache.match(request);
    if (cached) return cached;

    // Fallback vers la page offline
    const offlineCache = await caches.open(CACHE_STATIC);
    const offlinePage  = await offlineCache.match(OFFLINE_URL);
    if (offlinePage) return offlinePage;

    // Ultime fallback : HTML minimal
    return new Response(
      `<!DOCTYPE html><html><head><meta charset="utf-8"><title>Hors ligne</title></head>
       <body style="font-family:Inter,sans-serif;text-align:center;padding:3rem;">
       <h1 style="color:#10b981;">📴 Hors ligne</h1>
       <p>Vérifiez votre connexion et réessayez.</p>
       <button onclick="location.reload()" style="background:#10b981;color:#fff;padding:.75rem 2rem;border:none;border-radius:.5rem;cursor:pointer;font-size:1rem;">Réessayer</button>
       </body></html>`,
      { headers: { 'Content-Type': 'text/html; charset=utf-8' } }
    );
  }
}

/**
 * Stale-While-Revalidate : retourne le cache immédiatement + met à jour en arrière-plan
 */
async function staleWhileRevalidate(request, cacheName) {
  const cache  = await caches.open(cacheName);
  const cached = await cache.match(request);

  const fetchPromise = fetch(request).then((response) => {
    if (response.ok) cache.put(request, response.clone());
    return response;
  }).catch(() => null);

  return cached || fetchPromise || new Response(JSON.stringify({ error: 'offline' }), {
    headers: { 'Content-Type': 'application/json' },
    status:  503,
  });
}

/**
 * Background sync pour "Lire plus tard"
 */
async function syncReadLater() {
  // Logique de sync : on pourrait envoyer des bookmarks en attente
  console.log('[SW] Sync read-later effectuée.');
}
