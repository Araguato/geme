/*
 * Service worker de GEME.
 *
 * Seguridad: SOLO se guardan en caché recursos estáticos del propio sitio (CSS, JS,
 * imágenes, fuentes, manifest). Nunca páginas HTML, respuestas JSON, exportaciones
 * ni nada que dependa de la sesión: en un equipo compartido esos datos quedarían
 * en el dispositivo después de cerrar sesión.
 *
 * Al cambiar CACHE_NAME, "activate" borra las cachés anteriores (incluida la
 * geme-pwa-v2, que guardaba páginas con sesión).
 */
const CACHE_NAME = 'geme-static-v3';
const OFFLINE_URL = '/offline.html';

const PRECACHE_URLS = [
  OFFLINE_URL,
  '/manifest.webmanifest',
  '/Aurea.png'
];

const STATIC_DESTINATIONS = ['style', 'script', 'image', 'font', 'manifest'];
const STATIC_EXTENSIONS = /\.(?:css|js|mjs|png|jpe?g|gif|webp|svg|ico|woff2?|ttf|otf|webmanifest)$/i;

self.addEventListener('install', (event) => {
  event.waitUntil(
    caches.open(CACHE_NAME)
      .then((cache) => cache.addAll(PRECACHE_URLS))
      .then(() => self.skipWaiting())
  );
});

self.addEventListener('activate', (event) => {
  event.waitUntil(
    caches.keys()
      .then((keys) => Promise.all(keys.filter((key) => key !== CACHE_NAME).map((key) => caches.delete(key))))
      .then(() => self.clients.claim())
  );
});

// Permite a la página pedir que se vacíe la caché (p. ej. al cerrar sesión)
self.addEventListener('message', (event) => {
  if (event.data === 'geme:clear-cache') {
    event.waitUntil(caches.keys().then((keys) => Promise.all(keys.map((key) => caches.delete(key)))));
  }
});

function isStaticAsset(request, url) {
  if (url.origin !== self.location.origin) return false;
  if (url.search) return false; // con parámetros: puede ser dinámico
  return STATIC_DESTINATIONS.includes(request.destination) || STATIC_EXTENSIONS.test(url.pathname);
}

function isCacheable(response) {
  if (!response || response.status !== 200 || response.type !== 'basic') return false;
  const cacheControl = (response.headers.get('Cache-Control') || '').toLowerCase();
  const contentType = (response.headers.get('Content-Type') || '').toLowerCase();
  return !cacheControl.includes('no-store') && !cacheControl.includes('private') && !contentType.includes('text/html');
}

self.addEventListener('fetch', (event) => {
  const { request } = event;
  if (request.method !== 'GET') return;

  const url = new URL(request.url);

  // Páginas: siempre a la red. Sin conexión, una página genérica (sin datos).
  if (request.mode === 'navigate') {
    event.respondWith(
      fetch(request).catch(() => caches.match(OFFLINE_URL))
    );
    return;
  }

  // Recursos estáticos propios: caché con actualización en segundo plano.
  if (isStaticAsset(request, url)) {
    event.respondWith(
      caches.open(CACHE_NAME).then((cache) =>
        cache.match(request).then((cached) => {
          const network = fetch(request).then((response) => {
            if (isCacheable(response)) cache.put(request, response.clone());
            return response;
          }).catch(() => cached);
          return cached || network;
        })
      )
    );
    return;
  }

  // Todo lo demás (JSON, exportaciones, otros dominios): el navegador lo gestiona sin caché del SW.
});
