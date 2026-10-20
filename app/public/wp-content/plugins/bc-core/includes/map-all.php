<?php
/**
 * Шорткод [bc_map_all] — карта всех организаций каталога (Этап 6.3+).
 *
 * Провайдер — константа BC_MAP_PROVIDER: 'yandex' (JS API 3.0) или 'osm' (Leaflet).
 * OSM-бэкенд всегда подключён как аварийный: если Яндекс не отдал API, карта
 * рисуется на OpenStreetMap. Точки — ACF-поля bc_lat / bc_lng. В БД не пишем.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Точки организаций с координатами.
 *
 * @return array[]
 */
function bc_map_all_points() {
$query = new WP_Query(
array(
'post_type'      => 'organizations',
'post_status'    => 'publish',
'posts_per_page' => -1,
'no_found_rows'  => true,
'fields'         => 'ids',
'meta_query'     => array(
'relation' => 'AND',
array(
'key'     => 'bc_lat',
'value'   => '',
'compare' => '!=',
),
array(
'key'     => 'bc_lng',
'value'   => '',
'compare' => '!=',
),
),
)
);

$points = array();
foreach ( $query->posts as $post_id ) {
$lat = (float) str_replace( ',', '.', (string) get_field( 'bc_lat', $post_id ) );
$lng = (float) str_replace( ',', '.', (string) get_field( 'bc_lng', $post_id ) );
if ( ! $lat || ! $lng || abs( $lat ) > 90 || abs( $lng ) > 180 ) {
continue;
}

$points[] = array(
'id'      => (int) $post_id,
'lat'     => $lat,
'lng'     => $lng,
'title'   => wp_strip_all_tags( get_the_title( $post_id ) ),
'url'     => get_permalink( $post_id ),
'address' => wp_strip_all_tags( (string) get_field( 'bc_address', $post_id ) ),
);
}

return (array) apply_filters( 'bc_map_all_points', $points );
}

/**
 * Ассеты карты: стили + OSM-бэкенд всегда, Яндекс-загрузчик — в режиме yandex.
 *
 * @param bool $with_api Подключать ли скрипт карты (только когда есть точки).
 */
function bc_map_all_enqueue( $with_api = false ) {
bc_map_common_assets();

if ( ! $with_api ) {
return;
}

$dir = plugin_dir_path( __FILE__ ) . '../assets';
$deps = array( 'bc-map-osm' );

if ( bc_map_enqueue_yandex() ) {
$deps[] = 'bc-ymaps3';
}

if ( file_exists( $dir . '/js/map-all.js' ) ) {
wp_enqueue_script(
'bc-map-all',
plugins_url( 'assets/js/map-all.js', dirname( __FILE__ ) ),
$deps,
filemtime( $dir . '/js/map-all.js' ),
true
);
}
}

/**
 * Раннее подключение стилей на странице с шорткодом (без FOUC).
 */
function bc_map_all_maybe_enqueue() {
if ( ! is_singular() ) {
return;
}
$post = get_post();
if ( $post && has_shortcode( (string) $post->post_content, 'bc_map_all' ) ) {
bc_map_all_enqueue();
}
}
add_action( 'wp_enqueue_scripts', 'bc_map_all_maybe_enqueue' );

/**
 * Шорткод [bc_map_all].
 *
 * @return string HTML.
 */
function bc_map_all_shortcode() {
bc_map_all_enqueue();

if ( ! bc_map_on() ) {
return '<div class="bc-map-all bc-map-all--ph">' . bc_map_placeholder() . '</div>';
}

if ( 'yandex' === bc_map_provider() && '' === bc_map_key() ) {
return '<div class="bc-map-all bc-map-all--ph">' . bc_map_placeholder() . '</div>';
}

$points = bc_map_all_points();
if ( ! $points ) {
return '<div class="bc-map-all bc-map-all--ph">'
. bc_map_placeholder( 'Карта появится, когда у организаций заполнятся адреса' )
. '</div>';
}

bc_map_all_enqueue( true );

wp_add_inline_script(
'bc-map-all',
'window.BC_MAP_ALL=' . wp_json_encode(
array(
'points'      => $points,
'provider'    => bc_map_provider(),
'zoom'        => 12,
'clusterFrom' => 40,
),
JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
) . ';',
'before'
);

return '<div class="bc-map-all" id="bcMapAll"><div class="bc-map-all-canvas" id="bcMapAllCanvas" role="application" aria-label="Карта организаций микрорайона"></div></div>';
}
add_shortcode( 'bc_map_all', 'bc_map_all_shortcode' );