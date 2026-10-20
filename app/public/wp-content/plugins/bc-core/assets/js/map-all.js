/**
 * [bc_map_all] — карта всех организаций каталога.
 *
 * Основной провайдер — Яндекс JS API 3.0 (ymaps3), запасной — OSM/Leaflet:
 * при BC_MAP_PROVIDER=osm или если Яндекс-API не загрузился, карта рисуется
 * на OpenStreetMap через window.bcOsm (assets/js/map-osm.js).
 *
 * Данные приходят из PHP: window.BC_MAP_ALL = { points: [...], provider, zoom, clusterFrom }.
 */
(function () {
'use strict';

var data = window.BC_MAP_ALL;
var container = document.getElementById('bcMapAllCanvas');
if (!container || !data || !data.points || !data.points.length) {
return;
}

// Явный OSM-режим: Яндекс даже не пытаемся.
if (data.provider === 'osm' || window.BC_MAP_PROVIDER === 'osm') {
if (window.bcOsm && window.bcOsm.renderAll) {
window.bcOsm.renderAll(data);
}
return;
}

var wrap = container.closest('.bc-map-all') || container.parentNode;

function esc(value) {
return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
});
}

// Если Яндекс недоступен — уходим на OSM, и только потом показываем текст.
function fallback(text) {
if (window.bcOsm && window.bcOsm.renderAll) {
window.bcOsm.renderAll(data);
return;
}
container.innerHTML = '<div class="map-ph" style="height:100%"><p class="map-ph-text">' + esc(text) + '</p></div>';
}

var popup = document.createElement('div');
popup.className = 'bc-map-popup';
popup.hidden = true;
wrap.appendChild(popup);

function closePopup() {
popup.hidden = true;
}

function openPopup(point) {
popup.innerHTML =
'<button type="button" class="bc-map-popup-close" aria-label="Закрыть">×</button>' +
'<b class="bc-map-popup-title">' + esc(point.title) + '</b>' +
(point.address ? '<span class="bc-map-popup-addr">' + esc(point.address) + '</span>' : '') +
'<a class="bc-map-popup-link" href="' + esc(point.url) + '">Открыть карточку</a>';
popup.hidden = false;
popup.querySelector('.bc-map-popup-close').addEventListener('click', closePopup);
}

document.addEventListener('keydown', function (e) {
if (e.key === 'Escape') {
closePopup();
}
});

container.addEventListener('click', function (e) {
if (!popup.hidden && !popup.contains(e.target)) {
closePopup();
}
});

function pinElement(point) {
var el = document.createElement('button');
el.type = 'button';
el.className = 'bc-map-pin';
el.setAttribute('aria-label', point.title);
el.innerHTML = '<span class="bc-map-pin-dot"></span>';
el.addEventListener('click', function (ev) {
ev.preventDefault();
ev.stopPropagation();
openPopup(point);
});
return el;
}

function centerOf(points) {
var lat = 0;
var lng = 0;
points.forEach(function (p) {
lat += Number(p.lat);
lng += Number(p.lng);
});
return [lat / points.length, lng / points.length];
}

function fitBounds(map, points) {
if (!points.length) {
return;
}
if (points.length === 1) {
map.update({ location: { center: [Number(points[0].lat), Number(points[0].lng)], zoom: 15, duration: 0 } });
return;
}
var lats = points.map(function (p) { return Number(p.lat); });
var lngs = points.map(function (p) { return Number(p.lng); });
map.update({
location: {
bounds: [[Math.min.apply(null, lats), Math.min.apply(null, lngs)], [Math.max.apply(null, lats), Math.max.apply(null, lngs)]],
duration: 0
}
});
}

function render() {
var api = window.ymaps3;
var points = data.points;
var map = new api.YMap(container, { location: { center: centerOf(points), zoom: data.zoom || 12 } });

map.addChild(new api.YMapDefaultSchemeLayer());
map.addChild(new api.YMapDefaultFeaturesLayer());
window.bcMapInstance = map;

points.forEach(function (point) {
map.addChild(new api.YMapMarker({ coordinates: [Number(point.lat), Number(point.lng)] }, pinElement(point)));
});

fitBounds(map, points);
}

var tries = 0;
function boot() {
if (typeof window.ymaps3 === 'undefined' || !window.ymaps3.ready) {
if (++tries < 24) {
setTimeout(boot, 250);
return;
}
fallback('Карта недоступна, попробуйте обновить страницу');
return;
}

window.ymaps3.ready.then(function () {
try {
render();
} catch (e) {
fallback('Карта недоступна, попробуйте обновить страницу');
}
}, function () {
fallback('Карта недоступна, попробуйте обновить страницу');
});
}

boot();
}());