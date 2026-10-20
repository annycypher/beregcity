/**
 * Одиночная карта организации: Яндекс 3.0 или OSM/Leaflet (BC_MAP_PROVIDER).
 * Разметку даёт bc_map_markup(): <div class="bc-map-single" data-bc-map-lat data-bc-map-lng ...>.
 */
(function () {
'use strict';

var nodes = document.querySelectorAll('[data-bc-map-lat]');
if (!nodes.length) {
return;
}

function toOsm(node) {
if (window.bcOsm && window.bcOsm.renderSingle) {
window.bcOsm.renderSingle(node);
return true;
}
return false;
}

function placeholder(node) {
if (toOsm(node)) {
return;
}
node.innerHTML = '<div class="map-ph" style="height:100%"></div>';
}

function render() {
var api = window.ymaps3;

nodes.forEach(function (node) {
var lat = Number(node.getAttribute('data-bc-map-lat'));
var lng = Number(node.getAttribute('data-bc-map-lng'));
if (!lat || !lng) {
return;
}

try {
var map = new api.YMap(node, { location: { center: [lat, lng], zoom: 16 } });
map.addChild(new api.YMapDefaultSchemeLayer());
map.addChild(new api.YMapDefaultFeaturesLayer());

var pin = document.createElement('div');
pin.className = 'bc-map-pin bc-map-pin--static';
pin.innerHTML = '<span class="bc-map-pin-dot"></span>';
map.addChild(new api.YMapMarker({ coordinates: [lat, lng] }, pin));
} catch (e) {
placeholder(node);
}
});
}

// Явный OSM-режим: Яндекс даже не пытаемся.
if (window.BC_MAP_PROVIDER === 'osm') {
nodes.forEach(function (node) {
if (!toOsm(node)) {
placeholder(node);
}
});
return;
}

var tries = 0;
function boot() {
if (typeof window.ymaps3 === 'undefined' || !window.ymaps3.ready) {
if (++tries < 24) {
setTimeout(boot, 250);
return;
}
nodes.forEach(placeholder);
return;
}

window.ymaps3.ready.then(render, function () {
nodes.forEach(placeholder);
});
}

boot();
}());