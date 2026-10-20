/**
 * OSM/Leaflet-бэкенд карты «БерегСити» (режим BC_MAP_PROVIDER=osm и автозапасной
 * вариант, если Яндекс.Карты недоступны). Leaflet тянется с unpkg по требованию.
 */
(function () {
'use strict';

var LEAFLET_JS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.js';
var LEAFLET_CSS = 'https://unpkg.com/leaflet@1.9.4/dist/leaflet.css';
var TILES = 'https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png';
var ATTR = '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a>';

function ensureLeaflet(done) {
if (window.L && window.L.map) {
done();
return;
}
if (!document.querySelector('link[data-bc-leaflet]')) {
var css = document.createElement('link');
css.rel = 'stylesheet';
css.href = LEAFLET_CSS;
css.setAttribute('data-bc-leaflet', '1');
document.head.appendChild(css);
}
var script = document.querySelector('script[data-bc-leaflet]');
if (script) {
script.addEventListener('load', done);
return;
}
script = document.createElement('script');
script.src = LEAFLET_JS;
script.setAttribute('data-bc-leaflet', '1');
script.onload = done;
document.head.appendChild(script);
}

function esc(value) {
return String(value == null ? '' : value).replace(/[&<>"']/g, function (ch) {
return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[ch];
});
}

function popupHtml(point) {
return '<b class="bc-osm-title">' + esc(point.title) + '</b>' +
(point.address ? '<div class="bc-osm-addr">' + esc(point.address) + '</div>' : '') +
'<a class="bc-osm-link" href="' + esc(point.url) + '">Открыть карточку</a>';
}

function pinIcon() {
return window.L.divIcon({
className: 'bc-osm-pin-wrap',
html: '<span class="bc-map-pin bc-map-pin--static"><span class="bc-map-pin-dot"></span></span>',
iconSize: [26, 26],
iconAnchor: [13, 26],
popupAnchor: [0, -24]
});
}

function baseMap(el) {
var map = window.L.map(el, { scrollWheelZoom: false });
window.L.tileLayer(TILES, { maxZoom: 19, attribution: ATTR }).addTo(map);
return map;
}

function renderAll(data) {
var el = document.getElementById('bcMapAllCanvas');
if (!el || !window.L) {
return;
}
var points = data.points;
var map = baseMap(el);
var markers = points.map(function (point) {
return window.L.marker([Number(point.lat), Number(point.lng)], { icon: pinIcon(), title: point.title })
.bindPopup(popupHtml(point));
});
var group = window.L.featureGroup(markers).addTo(map);
map.fitBounds(group.getBounds().pad(0.15), { maxZoom: 16 });
window.bcMapInstance = map;
}

function renderSingle(node) {
var lat = Number(node.getAttribute('data-bc-map-lat'));
var lng = Number(node.getAttribute('data-bc-map-lng'));
if (!lat || !lng || !window.L) {
return;
}
var map = baseMap(node).setView([lat, lng], 16);
window.L.marker([lat, lng], { icon: pinIcon() }).addTo(map);
}

window.bcOsm = {
ready: false,
renderAll: function (data) {
ensureLeaflet(function () {
window.bcOsm.ready = true;
renderAll(data);
});
},
renderSingle: function (node) {
ensureLeaflet(function () {
window.bcOsm.ready = true;
renderSingle(node);
});
}
};
}());