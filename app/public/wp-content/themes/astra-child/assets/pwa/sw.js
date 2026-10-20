/**
 * Сервис-воркер «БерегСити» (PWA, минимальный).
 *
 * Отдаётся эндпоинтом /bc-sw.js (astra-child/includes/pwa.php), плейсхолдеры
 * __BC_PWA_VERSION__ и __BC_PWA_PRECACHE__ подставляет PHP при отдаче.
 *
 * Стратегия: HTML и данные каталога — network-first (свежесть важнее офлайна),
 * в кэш кладутся только иконки и манифест PWA.
 */
var BC_SW_VERSION = '__BC_PWA_VERSION__';
var BC_SW_PRECACHE = __BC_PWA_PRECACHE__;
var BC_SW_PWA_PATH = '/wp-content/themes/astra-child/assets/pwa/';

self.addEventListener('install', function (event) {
	event.waitUntil(
		caches.open(BC_SW_VERSION).then(function (cache) {
			return Promise.all(BC_SW_PRECACHE.map(function (url) {
				// Один недоступный файл не должен ломать установку воркера.
				return cache.add(url).catch(function () {});
			}));
		}).then(function () {
			return self.skipWaiting();
		})
	);
});

self.addEventListener('activate', function (event) {
	event.waitUntil(
		caches.keys().then(function (keys) {
			return Promise.all(keys.filter(function (key) {
				return key !== BC_SW_VERSION;
			}).map(function (key) {
				return caches.delete(key);
			}));
		}).then(function () {
			return self.clients.claim();
		})
	);
});

self.addEventListener('fetch', function (event) {
	var req = event.request;
	if (req.method !== 'GET') {
		return;
	}

	var url = new URL(req.url);
	if (url.origin !== self.location.origin) {
		return;
	}

	// Статика PWA — cache-first.
	if (url.pathname === '/bc-manifest.json' || url.pathname.indexOf(BC_SW_PWA_PATH) === 0) {
		event.respondWith(
			caches.match(req).then(function (hit) {
				return hit || fetch(req);
			})
		);
		return;
	}

	// Навигация — network-first; кэш только как аварийный фолбэк.
	if (req.mode === 'navigate') {
		event.respondWith(
			fetch(req).catch(function () {
				return caches.match('/');
			})
		);
	}
});
