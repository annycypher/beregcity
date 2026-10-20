/* БерегСити — видео-фон главного баннера.
   Ролик подключается только когда он реально нужен:
   • уважаем «уменьшить движение» (prefers-reduced-motion) — остаётся постер;
   • уважаем экономию трафика (saveData/2G) — остаётся постер;
   • на телефон отдаём вертикальный файл, на компьютер — широкий;
   • играем только видимый слайд, при ошибке загрузки остаётся картинка. */
(function () {
	'use strict';

	var videos = [].slice.call(document.querySelectorAll('video.bc-slide-video'));
	if (!videos.length) {
		return;
	}

	var reduce = window.matchMedia ? window.matchMedia('(prefers-reduced-motion: reduce)').matches : false;
	var conn = navigator.connection || {};
	var slow = conn.saveData === true || /(^|-)2g$/.test(String(conn.effectiveType || ''));

	if (reduce || slow) {
		return; // Постер уже в разметке — ничего не качаем.
	}

	var mq = window.matchMedia ? window.matchMedia('(max-width: 767px)') : null;
	var track = document.getElementById('track');

	var pickSource = function (v) {
		var src = (mq && mq.matches) ? (v.getAttribute('data-src-mob') || v.getAttribute('data-src')) : v.getAttribute('data-src');
		var poster = (mq && mq.matches) ? (v.getAttribute('data-poster-mob') || v.getAttribute('poster')) : v.getAttribute('poster');

		if (poster && v.getAttribute('poster') !== poster) {
			v.setAttribute('poster', poster);
		}
		if (!src) {
			return;
		}
		if (v.getAttribute('src') !== src) {
			v.setAttribute('src', src);
			v.load();
		}
	};

	var tryPlay = function (v) {
		if (v.classList.contains('is-failed')) {
			return;
		}
		var p = v.play();
		if (p && typeof p.catch === 'function') {
			p.catch(function () { /* Автоплей заблокирован (iOS «экономия энергии») — остаётся постер. */ });
		}
	};

	var inView = function (el) {
		var r = el.getBoundingClientRect();
		return r.bottom > 0 && r.top < (window.innerHeight || document.documentElement.clientHeight);
	};

	videos.forEach(function (v) {
		pickSource(v);

		if (mq) {
			var onChange = function () { pickSource(v); };
			if (mq.addEventListener) {
				mq.addEventListener('change', onChange);
			} else if (mq.addListener) {
				mq.addListener(onChange);
			}
		}

		v.addEventListener('error', function () {
			v.classList.add('is-failed');
		});
	});

	var slides = videos.map(function (v) {
		return v.closest ? v.closest('.slide') : null;
	});

	if ('IntersectionObserver' in window) {
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (entry) {
				var v = entry.target.querySelector('video.bc-slide-video');
				if (!v) {
					return;
				}
				if (entry.isIntersecting) {
					tryPlay(v);
				} else {
					v.pause();
				}
			});
		}, { root: track || null, threshold: 0.55 });

		slides.forEach(function (s) {
			if (s) {
				io.observe(s);
			}
		});
	} else {
		tryPlay(videos[0]);
	}

	document.addEventListener('visibilitychange', function () {
		videos.forEach(function (v) {
			var s = v.closest ? v.closest('.slide') : null;
			if (document.hidden) {
				v.pause();
			} else if (s && inView(s)) {
				tryPlay(v);
			}
		});
	});
}());
