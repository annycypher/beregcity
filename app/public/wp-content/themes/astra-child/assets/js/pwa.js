/**
 * PWA «БерегСити»: кнопка «Установить» в шапке.
 *
 * beforeinstallprompt → показываем кнопку, клик → нативный промпт Chrome.
 * iOS не умеет beforeinstallprompt: там кнопка показывает подсказку про Safari.
 * Конфиг приходит из PHP: window.BC_PWA = { sw, iosTip }.
 */
(function () {
	'use strict';

	var cfg = window.BC_PWA || {};
	var root = document.documentElement;
	var wrap = null;
	var btn = null;
	var tip = null;
	var deferred = null;
	var ua = navigator.userAgent || '';
	var isIOS = /iPad|iPhone|iPod/.test(ua) || (navigator.platform === 'MacIntel' && navigator.maxTouchPoints > 1);

	function isStandalone() {
		return (window.matchMedia && window.matchMedia('(display-mode: standalone)').matches) || navigator.standalone === true;
	}

	function show() {
		if (!wrap) {
			return;
		}
		wrap.hidden = false;
		root.classList.add('bc-pwa-can');
	}

	function hide() {
		root.classList.remove('bc-pwa-can');
		if (wrap) {
			wrap.hidden = true;
		}
		if (tip) {
			tip.hidden = true;
		}
	}

	function onClick() {
		if (deferred && typeof deferred.prompt === 'function') {
			var ev = deferred;
			deferred = null;
			ev.prompt();
			if (ev.userChoice && typeof ev.userChoice.then === 'function') {
				ev.userChoice.then(function (choice) {
					if (choice && choice.outcome === 'accepted') {
						hide();
					}
				}).catch(function () {});
			}
			return;
		}
		// iOS и прочие браузеры без промпта: мини-подсказка.
		if (isIOS && tip) {
			tip.hidden = false;
		}
	}

	function bind() {
		wrap = document.getElementById('bcPwa');
		btn = document.getElementById('bcPwaBtn');
		tip = document.getElementById('bcPwaTip');
		if (!btn) {
			return;
		}

		btn.addEventListener('click', onClick);

		if (tip) {
			tip.addEventListener('click', function () {
				tip.hidden = true;
			});
		}

		document.addEventListener('keydown', function (e) {
			if (e.key === 'Escape' && tip) {
				tip.hidden = true;
			}
		});

		document.addEventListener('click', function (e) {
			if (tip && !tip.hidden && wrap && !wrap.contains(e.target)) {
				tip.hidden = true;
			}
		});

		// Событие могло прийти до DOMContentLoaded.
		if (deferred || isIOS) {
			show();
		}
	}

	// Уже установлено (standalone) — кнопка не нужна.
	if (isStandalone()) {
		return;
	}

	window.addEventListener('beforeinstallprompt', function (e) {
		e.preventDefault();
		deferred = e;
		show();
	});

	window.addEventListener('appinstalled', hide);

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', bind);
	} else {
		bind();
	}

	// Регистрация воркера только в защищённом контексте (https или localhost),
	// иначе на http-локалке браузер сыпал бы ошибку в консоль.
	if (cfg.sw && 'serviceWorker' in navigator && window.isSecureContext) {
		window.addEventListener('load', function () {
			navigator.serviceWorker.register(cfg.sw, { scope: '/' }).catch(function () {});
		});
	}

	// Тест-хук для скриншотов и демонстрации владельцу: window.bcPwaShow().
	window.bcPwaShow = function () {
		if (!wrap) {
			bind();
		}
		show();
	};
}());
