/* БерегСити — вьюер сторис (Этап 5.2). Порт механики design/stories.html.
   Vanilla JS, без библиотек. Данные: window.BC_STORIES[containerId]. */
(function () {
	'use strict';

	var DUR = 6000;
	var seen = [];
	try { seen = JSON.parse(localStorage.getItem('bc_seen') || '[]'); } catch (e) { seen = []; }

	var ov = document.createElement('div');
	ov.className = 'overlay';
	ov.id = 'bc-ov';
	ov.innerHTML = '<div class="viewer" id="bc-vw">' +
		'<div class="bars" id="bc-bars"></div>' +
		'<span class="ad-tag" id="bc-ad">Реклама</span>' +
		'<button class="close" id="bc-close" aria-label="Закрыть">✕</button>' +
		'<img class="slide-img" id="bc-img" alt="">' +
		'<div class="caption" id="bc-cap"></div>' +
		'<a class="slide-btn" id="bc-btn" target="_blank" rel="nofollow noopener"><span id="bc-btnText">Узнать больше</span></a>' +
		'<div class="zone l"></div><div class="zone r"></div>' +
		'</div>';
	document.body.appendChild(ov);

	var vw = document.getElementById('bc-vw');
	var bars = document.getElementById('bc-bars');
	var img = document.getElementById('bc-img');
	var cap = document.getElementById('bc-cap');
	var btn = document.getElementById('bc-btn');
	var btnText = document.getElementById('bc-btnText');
	var adTag = document.getElementById('bc-ad');
	var btnClose = document.getElementById('bc-close');

	var STORIES = [], row = null, si = 0, pi = 0, prog = 0, last = 0, playing = false;

	function markSeen() {
		if (STORIES[si] && seen.indexOf(STORIES[si].id) === -1) {
			seen.push(STORIES[si].id);
			localStorage.setItem('bc_seen', JSON.stringify(seen));
			if (row) {
				var c = row.querySelector('.story[data-id="' + STORIES[si].id + '"]');
				if (c) c.classList.add('seen');
			}
		}
	}

	function build() {
		bars.innerHTML = '';
		STORIES[si].slides.forEach(function () {
			var b = document.createElement('div');
			b.className = 'bar';
			b.innerHTML = '<i></i>';
			bars.appendChild(b);
		});
	}

	function show(j) {
		var st = STORIES[si];
		pi = j; prog = 0; playing = true;
		var sl = st.slides[j];
		img.src = sl.img; img.alt = st.title;
		cap.textContent = sl.caption || '';
		cap.style.display = sl.caption ? 'block' : 'none';
		btn.href = sl.link || '#';
		btnText.textContent = sl.btn || 'Узнать больше';
		btn.style.display = sl.link ? 'flex' : 'none';
		adTag.style.display = sl.ad ? 'block' : 'none';
		Array.prototype.forEach.call(bars.children, function (b, k) {
			b.classList.toggle('done', k < j);
			b.querySelector('i').style.width = '0%';
		});
		var nx = st.slides[j + 1];
		if (nx) { var im = new Image(); im.src = nx.img; }
	}

	function openStory(i) {
		si = i; pi = 0;
		build(); show(0);
		ov.classList.add('open');
		document.body.style.overflow = 'hidden';
	}
	function close() {
		ov.classList.remove('open');
		document.body.style.overflow = '';
		markSeen();
	}
	function next() {
		var st = STORIES[si];
		if (pi + 1 < st.slides.length) show(pi + 1);
		else if (si + 1 < STORIES.length) openStory(si + 1);
		else close();
	}
	function prev() {
		if (pi > 0) show(pi - 1);
		else if (si > 0) { var st = STORIES[si - 1]; openStory(si - 1); show(st.slides.length - 1); }
		else { prog = 0; playing = true; }
	}

	requestAnimationFrame(function tick(t) {
		if (ov.classList.contains('open') && playing) {
			prog += t - last;
			if (prog >= DUR) next();
			else if (bars.children[pi]) bars.children[pi].querySelector('i').style.width = (prog / DUR * 100) + '%';
		}
		last = t;
		requestAnimationFrame(tick);
	});

	var px = 0, py = 0, moved = false;
	vw.addEventListener('pointerdown', function (e) {
		if (e.target.closest('a,button')) return;
		px = e.clientX; py = e.clientY; moved = false; playing = false;
	});
	vw.addEventListener('pointermove', function (e) {
		if (Math.abs(e.clientX - px) > 10 || Math.abs(e.clientY - py) > 10) moved = true;
	});
	vw.addEventListener('pointerup', function (e) {
		if (e.target.closest('a,button')) return;
		var dx = e.clientX - px, dy = e.clientY - py;
		playing = true;
		if (!moved) {
			var rect = vw.getBoundingClientRect();
			if (e.clientX > rect.left + vw.clientWidth * 0.3) next(); else prev();
		} else if (dx < -40) next();
		else if (dx > 40) prev();
		else if (dy > 80) close();
	});

	btnClose.onclick = close;
	document.addEventListener('keydown', function (e) {
		if (!ov.classList.contains('open')) return;
		if (e.key === 'Escape') close();
		if (e.key === 'ArrowRight') next();
		if (e.key === 'ArrowLeft') prev();
	});

	function bind() {
		var rows = document.querySelectorAll('.stories[id^="bcvw_"]');
		Array.prototype.forEach.call(rows, function (r) {
			Array.prototype.forEach.call(r.querySelectorAll('.story'), function (el, i) {
				el.onclick = function () {
					STORIES = window.BC_STORIES[r.id] || [];
					row = r;
					openStory(i);
				};
			});
			Array.prototype.forEach.call(r.querySelectorAll('.story'), function (el) {
				var id = parseInt(el.getAttribute('data-id'), 10);
				if (seen.indexOf(id) !== -1) el.classList.add('seen');
			});
		});
	}
	bind();
	document.addEventListener('DOMContentLoaded', bind);
})();
