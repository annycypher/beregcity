/* БерегСити — интерактив темы (Этап 2.1b).
   Логика из design/homepage.html (слайдер + бургер), vanilla JS, без jQuery.
   Селекторы как в референсе: #track, .dot, .prev/.next, #burger, #mmenu.
   Скрипт подключается в подвал (footer=true), поэтому DOM уже готов.
   Добавлена защита: если узла нет на странице — модуль молчит (без ошибок в консоли). */
(function () {
	'use strict';

	/* ── Слайдер главной: стрелки, точки, автоплей 6 сек, стоп по касанию ── */
	var track = document.getElementById('track');
	if (track) {
		var slides = [].slice.call(track.children);
		var dots = [].slice.call(document.querySelectorAll('.dot'));
		var i = 0;
		var timer;

		var go = function (k) {
			i = (k + slides.length) % slides.length;
			slides[i].scrollIntoView({ behavior: 'smooth', inline: 'center', block: 'nearest' });
			dots.forEach(function (d, n) { d.classList.toggle('on', n === i); });
		};
		var stop = function () { clearInterval(timer); };
		var play = function () { timer = setInterval(function () { go(i + 1); }, 6000); };

		var prev = document.querySelector('.prev');
		var next = document.querySelector('.next');
		if (prev) { prev.onclick = function () { stop(); go(i - 1); }; }
		if (next) { next.onclick = function () { stop(); go(i + 1); }; }
		dots.forEach(function (d, n) {
			d.onclick = function () { stop(); go(n); };
		});

		track.addEventListener('scroll', function () {
			var n = Math.round(track.scrollLeft / track.clientWidth);
			if (n !== i) {
				i = n;
				dots.forEach(function (d, k) { d.classList.toggle('on', k === i); });
			}
		}, { passive: true });

		play();
		track.addEventListener('pointerdown', stop, { once: true });
	}

	/* ── Мобильное меню (бургер) ── */
	var burger = document.getElementById('burger');
	var mm = document.getElementById('mmenu');
	if (burger && mm) {
		burger.onclick = function () { mm.classList.toggle('open'); };
	}
}());
