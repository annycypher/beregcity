<?php
/**
 * Карта (Этап 6.3): Яндекс JS API 3.0 или OSM/Leaflet под BC_MAP_PROVIDER (D11).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Включена ли карта.
 */
function bc_map_on() {
return defined( 'BC_MAP_ON' ) && BC_MAP_ON;
}

/**
 * Провайдер карты: 'yandex' (по умолчанию) или 'osm'.
 *
 * @return string
 */
function bc_map_provider() {
$provider = defined( 'BC_MAP_PROVIDER' ) ? strtolower( trim( (string) BC_MAP_PROVIDER ) ) : 'yandex';
return in_array( $provider, array( 'yandex', 'osm' ), true ) ? $provider : 'yandex';
}

/**
 * Ключ Яндекс.Карт из wp-config.php (пусто, если ключа нет).
 *
 * @return string
 */
function bc_map_key() {
$key = defined( 'BC_YMAP_KEY' ) ? trim( (string) BC_YMAP_KEY ) : '';
if ( '' === $key || false !== strpos( $key, 'ВАШ' ) ) {
$key = '';
}
return (string) apply_filters( 'bc_map_key', $key );
}

/**
 * SVG-заглушка карты (стиль .map-ph из темы).
 *
 * @param string $text Необязательный текст под пином.
 * @return string HTML.
 */
function bc_map_placeholder( $text = '' ) {
$html = '<div class="map-ph"><svg class="mark" viewBox="0 0 24 24" style="width:38px;height:38px"><path class="ico" style="width:38px;height:38px;stroke:currentColor;stroke-width:1.6;fill:none" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle style="stroke:currentColor;stroke-width:1.6;fill:none" cx="12" cy="10" r="3"/></svg>';
if ( '' !== $text ) {
$html .= '<p class="map-ph-text">' . esc_html( $text ) . '</p>';
}
return $html . '</div>';
}

/**
 * Общие ассеты: стили + OSM-бэкенд (он же аварийный) + глобальный провайдер.
 */
function bc_map_common_assets() {
$dir = plugin_dir_path( __FILE__ ) . '../assets';
$url = plugins_url( 'assets', dirname( __FILE__ ) );

if ( file_exists( $dir . '/css/map-all.css' ) ) {
wp_enqueue_style( 'bc-map-all', $url . '/css/map-all.css', array(), filemtime( $dir . '/css/map-all.css' ) );
}

if ( file_exists( $dir . '/js/map-osm.js' ) ) {
wp_enqueue_script( 'bc-map-osm', $url . '/js/map-osm.js', array(), filemtime( $dir . '/js/map-osm.js' ), true );
wp_add_inline_script( 'bc-map-osm', 'window.BC_MAP_PROVIDER=' . wp_json_encode( bc_map_provider() ) . ';', 'before' );
}
}

/**
 * Загрузчик Яндекс.Карт 3.0 — только в режиме yandex и при наличии ключа.
 *
 * @return bool Подключён ли загрузчик.
 */
function bc_map_enqueue_yandex() {
$key = bc_map_key();
if ( 'yandex' !== bc_map_provider() || '' === $key ) {
return false;
}

wp_enqueue_script(
'bc-ymaps3',
'https://api-maps.yandex.ru/v3/?apikey=' . rawurlencode( $key ) . '&lang=ru_RU',
array(),
null,
true
);

return true;
}

/**
 * Ассеты одиночной карты организации.
 */
function bc_map_single_enqueue() {
$dir = plugin_dir_path( __FILE__ ) . '../assets';
$url = plugins_url( 'assets', dirname( __FILE__ ) );

bc_map_common_assets();

$deps = array( 'bc-map-osm' );
if ( bc_map_enqueue_yandex() ) {
$deps[] = 'bc-ymaps3';
}

if ( file_exists( $dir . '/js/map-single.js' ) ) {
wp_enqueue_script( 'bc-map-single', $url . '/js/map-single.js', $deps, filemtime( $dir . '/js/map-single.js' ), true );
}
}

/**
 * Карта организации (Яндекс 3.0 или OSM) либо SVG-заглушка.
 *
 * @param float  $lat     Широта.
 * @param float  $lng     Долгота.
 * @param string $address Адрес для aria-label.
 * @return string HTML.
 */
function bc_map_markup( $lat = 0, $lng = 0, $address = '' ) {
if ( ! bc_map_on() || ! $lat || ! $lng ) {
return bc_map_placeholder();
}
if ( 'yandex' === bc_map_provider() && '' === bc_map_key() ) {
return bc_map_placeholder();
}

bc_map_single_enqueue();

return sprintf(
'<div class="bc-map-single" data-bc-map-lat="%1$s" data-bc-map-lng="%2$s" role="application" aria-label="%3$s"></div>',
esc_attr( (float) $lat ),
esc_attr( (float) $lng ),
esc_attr( $address ? $address : 'Карта организации' )
);
}