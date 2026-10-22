/**
 * Service Worker: Sistema de Control de Asistencia SENA
 * Estrategia de Caché Híbrida y Soporte Offline-First
 */

const CACHE_NAME = 'sena-asistencia-v1';

const STATIC_ASSETS = [
    './public/manifest.json',
    './public/assets/css/theme.css',
    './public/assets/css/style.css',
    './public/assets/css/main.css',
    './public/assets/css/responsive.css',
    './public/assets/css/aprendiz-custom.css',
    './public/assets/css/nalika-icon.css',
    './public/assets/js/asistencia-offline.js',
    './public/assets/js/main.js',
    './public/assets/js/aprendiz-custom.js',
    './public/assets/img/logo/logo_sena_verde.svg',
    './public/assets/img/logo/logo.png',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/css/bootstrap.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap-icons@1.13.1/font/bootstrap-icons.min.css',
    'https://cdn.jsdelivr.net/npm/bootstrap@5.3.8/dist/js/bootstrap.bundle.min.js',
    'https://fonts.googleapis.com/css2?family=Roboto:wght@300;400;500;700&display=swap'
];

// Instalación: Precarga de activos estáticos esenciales
self.addEventListener('install', event => {
    event.waitUntil(
        caches.open(CACHE_NAME).then(cache => {
            return cache.addAll(STATIC_ASSETS).catch(err => {
                console.warn('[ServiceWorker] Advertencia al precargar recursos:', err);
            });
        }).then(() => self.skipWaiting())
    );
});

// Activación: Limpieza de versiones obsoletas de caché
self.addEventListener('activate', event => {
    event.waitUntil(
        caches.keys().then(cacheNames => {
            return Promise.all(
                cacheNames.map(cache => {
                    if (cache !== CACHE_NAME) {
                        console.log('[ServiceWorker] Eliminando caché antigua:', cache);
                        return caches.delete(cache);
                    }
                })
            );
        }).then(() => self.clients.claim())
    );
});

// Interceptación de solicitudes de red
self.addEventListener('fetch', event => {
    const request = event.request;
    const url = new URL(request.url);

    // Solo procesar peticiones GET (las peticiones POST no se almacenan en caché)
    if (request.method !== 'GET') {
        return;
    }

    // 1. Solicitudes de Navegación HTML (páginas web)
    if (request.mode === 'navigate' || request.headers.get('accept')?.includes('text/html')) {
        event.respondWith(
            fetch(request)
                .then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            // Almacenar en caché la pantalla de control de ingresos
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                })
                .catch(() => {
                    // Fallback sin conexión: Servir la última pantalla visitada o la de ingresos
                    return caches.match(request).then(cachedResponse => {
                        if (cachedResponse) {
                            return cachedResponse;
                        }
                        // Si la URL exacta no está, intentar servir la pantalla principal de ingresos en caché
                        return caches.match('./index.php?page=instructor_ingresos').then(ingresosCached => {
                            if (ingresosCached) return ingresosCached;
                            return caches.match(new Request('./index.php?page=instructor_ingresos'));
                        });
                    });
                })
        );
        return;
    }

    // 2. Solicitudes de Recursos Estáticos (CSS, JS, Fuentes, Imágenes) -> Cache-First con fallback a red
    const isStaticAsset = (
        url.pathname.endsWith('.css') ||
        url.pathname.endsWith('.js') ||
        url.pathname.endsWith('.svg') ||
        url.pathname.endsWith('.png') ||
        url.pathname.endsWith('.jpg') ||
        url.pathname.endsWith('.ico') ||
        url.pathname.endsWith('.woff') ||
        url.pathname.endsWith('.woff2') ||
        url.pathname.endsWith('.ttf') ||
        url.hostname.includes('cdn.jsdelivr.net') ||
        url.hostname.includes('fonts.googleapis.com') ||
        url.hostname.includes('fonts.gstatic.com')
    );

    if (isStaticAsset) {
        event.respondWith(
            caches.match(request).then(cachedResponse => {
                if (cachedResponse) {
                    return cachedResponse;
                }
                return fetch(request).then(networkResponse => {
                    if (networkResponse && networkResponse.status === 200) {
                        const responseClone = networkResponse.clone();
                        caches.open(CACHE_NAME).then(cache => {
                            cache.put(request, responseClone);
                        });
                    }
                    return networkResponse;
                }).catch(() => {
                    // Si falla el recurso estático y no está en caché, no lanzar excepción fatal
                    return new Response('', { status: 408, statusText: 'Resource unavailable offline' });
                });
            })
        );
        return;
    }

    // 3. Otras peticiones GET estándar -> Network-First
    event.respondWith(
        fetch(request).catch(() => caches.match(request))
    );
});
