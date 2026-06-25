/*
 * Service Worker do FlowFin — cache da casca do app (app shell).
 *
 * Objetivo (Task 5.1): permitir que a interface abra mesmo offline, sem servir
 * conteúdo desatualizado de forma que quebre o app após um novo deploy.
 *
 * Estratégias por tipo de requisição:
 *  - Navegações (HTML): network-first. Busca a versão fresca; se a rede falhar,
 *    devolve a última navegação cacheada e, em último caso, a página /offline.html.
 *    (Evita servir HTML "velho" enquanto há rede — importante após deploy.)
 *  - Assets versionados do Vite (/build/...): cache-first. Os nomes têm hash, então
 *    são imutáveis: uma vez em cache, podem ser servidos sem ir à rede.
 *  - Demais GET de mesma origem (ícones, manifest, fontes): stale-while-revalidate.
 *  - Chamadas de API (/api/...): NUNCA cacheadas pelo SW. O offline de escrita é
 *    tratado na camada do app (fila em IndexedDB, Task 5.2); leituras exigem rede.
 *
 * Versionar o cache (CACHE_VERSION) invalida os caches antigos a cada mudança
 * relevante neste arquivo. Bump manual ao alterar a casca/estratégia.
 */

const CACHE_VERSION = 'v2';
const SHELL_CACHE = `flowfin-shell-${CACHE_VERSION}`;
const ASSET_CACHE = `flowfin-assets-${CACHE_VERSION}`;
const OFFLINE_URL = '/offline.html';

// Recursos mínimos pré-cacheados na instalação (a casca real é cacheada sob demanda).
const PRECACHE_URLS = [
    OFFLINE_URL,
    '/manifest.webmanifest',
    '/img/pwa/app-icon.svg',
    '/img/pwa/app-icon-192.png',
    '/img/pwa/app-icon-512.png',
    '/img/brand/icon_flowfin.svg',
];

self.addEventListener('install', (event) => {
    event.waitUntil(
        caches.open(SHELL_CACHE)
            .then((cache) => cache.addAll(PRECACHE_URLS))
            .then(() => self.skipWaiting())
    );
});

self.addEventListener('activate', (event) => {
    event.waitUntil(
        caches.keys()
            .then((keys) => Promise.all(
                keys
                    .filter((k) => k !== SHELL_CACHE && k !== ASSET_CACHE)
                    .map((k) => caches.delete(k))
            ))
            .then(() => self.clients.claim())
    );
});

function isBuildAsset(url) {
    return url.origin === self.location.origin && url.pathname.startsWith('/build/');
}

function isApi(url) {
    return url.origin === self.location.origin && url.pathname.startsWith('/api/');
}

// Cache-first: assets imutáveis com hash. Vai à rede só na primeira vez.
async function cacheFirst(request) {
    const cache = await caches.open(ASSET_CACHE);
    const cached = await cache.match(request);
    if (cached) return cached;
    const response = await fetch(request);
    if (response && response.ok) cache.put(request, response.clone());
    return response;
}

// Stale-while-revalidate: devolve o cache na hora e atualiza em segundo plano.
async function staleWhileRevalidate(request) {
    const cache = await caches.open(SHELL_CACHE);
    const cached = await cache.match(request);
    const network = fetch(request)
        .then((response) => {
            if (response && response.ok) cache.put(request, response.clone());
            return response;
        })
        .catch(() => null);
    return cached || (await network) || fetch(request);
}

// Network-first para navegações: HTML fresco quando há rede; cache/offline quando não há.
async function navigationHandler(request) {
    const cache = await caches.open(SHELL_CACHE);
    try {
        const response = await fetch(request);
        if (response && response.ok) cache.put(request, response.clone());
        return response;
    } catch (e) {
        const cached = await cache.match(request);
        if (cached) return cached;
        return (await cache.match(OFFLINE_URL)) || Response.error();
    }
}

self.addEventListener('fetch', (event) => {
    const { request } = event;

    // Só lidamos com GET; escritas (POST/PUT/DELETE) passam direto pela rede.
    if (request.method !== 'GET') return;

    const url = new URL(request.url);

    // API nunca é cacheada pelo SW (dados financeiros; offline tratado no app).
    if (isApi(url)) return;

    if (request.mode === 'navigate') {
        event.respondWith(navigationHandler(request));
        return;
    }

    if (isBuildAsset(url)) {
        event.respondWith(cacheFirst(request));
        return;
    }

    // Demais GET de mesma origem (ícones, manifest, fontes self-hosted).
    if (url.origin === self.location.origin) {
        event.respondWith(staleWhileRevalidate(request));
    }
});

// Permite que a página force a ativação imediata de uma nova versão do SW.
self.addEventListener('message', (event) => {
    if (event.data === 'skip-waiting') self.skipWaiting();
});
