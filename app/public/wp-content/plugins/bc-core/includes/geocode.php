<?php
/**
 * Геокодинг адресов организаций (WP-CLI): wp bc-geocode [--provider=nominatim|yandex]
 *
 * Источник координат — OpenStreetMap Nominatim (без ключа). Требования сервиса:
 * собственный User-Agent с контактом, не чаще 1 запроса в секунду, кэш ответов.
 * Координаты пишутся в мету bc_lat / bc_lng (их читает bc_map_markup и [bc_map_all]).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

const BC_GEOCODE_UA      = 'BeregCityBot/1.0 (+https://beregcity.ru/; hello@beregcity.ru)';
const BC_GEOCODE_CACHE   = 'bc_geocode_cache';
const BC_GEOCODE_PAUSE   = 1100000; // микросекунды между запросами к Nominatim.

/**
 * Координаты пригодны для карты.
 *
 * @param mixed $lat Широта.
 * @param mixed $lng Долгота.
 * @return bool
 */
function bc_geocode_valid( $lat, $lng ) {
$lat = (float) str_replace( ',', '.', (string) $lat );
$lng = (float) str_replace( ',', '.', (string) $lng );

if ( ! $lat || ! $lng ) {
return false;
}

return abs( $lat ) <= 90 && abs( $lng ) <= 180;
}

/**
 * Поисковый запрос для адреса: добавляем город, если его нет.
 *
 * @param string $address Адрес из карточки.
 * @return string
 */
function bc_geocode_query( $address ) {
$address = trim( preg_replace( '/\s+/u', ' ', (string) $address ) );
if ( '' === $address ) {
return '';
}

if ( false === mb_stripos( $address, 'Красноярск' ) ) {
$address .= ', Красноярск';
}

return $address;
}

/**
 * Организации без валидных координат.
 *
 * @param int $limit Ограничение количества (0 — все).
 * @return array[] [ id, title, address ]
 */
function bc_geocode_targets( $limit = 0 ) {
$query = new WP_Query(
array(
'post_type'      => 'organizations',
'post_status'    => array( 'publish', 'draft', 'pending' ),
'posts_per_page' => $limit > 0 ? $limit : -1,
'fields'         => 'ids',
'no_found_rows'  => true,
'orderby'        => 'ID',
'order'          => 'ASC',
)
);

$targets = array();
foreach ( $query->posts as $id ) {
if ( bc_geocode_valid( get_post_meta( $id, 'bc_lat', true ), get_post_meta( $id, 'bc_lng', true ) ) ) {
continue;
}

$address = trim( (string) get_post_meta( $id, 'bc_address', true ) );
if ( '' === $address && function_exists( 'get_field' ) ) {
$address = trim( (string) get_field( 'bc_address', $id ) );
}
if ( '' === $address ) {
continue;
}

$targets[] = array(
'id'      => (int) $id,
'title'   => get_the_title( $id ),
'address' => $address,
);
}

return $targets;
}

/**
 * Кэш ответов Nominatim (опция bc_geocode_cache).
 *
 * @return array
 */
function bc_geocode_cache_get() {
$cache = get_option( BC_GEOCODE_CACHE, array() );
return is_array( $cache ) ? $cache : array();
}

/**
 * Сохранение кэша с ограничением размера.
 *
 * @param array $cache Кэш.
 */
function bc_geocode_cache_set( $cache ) {
if ( count( $cache ) > 3000 ) {
$cache = array_slice( $cache, -2000, null, true );
}
update_option( BC_GEOCODE_CACHE, $cache, false );
}

/**
 * Запрос к Nominatim.
 *
 * @param string $q Поисковая строка.
 * @return array ['lat','lng'] | ['miss'] | ['error']
 */
function bc_geocode_nominatim_fetch( $q ) {
$url = add_query_arg(
array(
'format'       => 'jsonv2',
'limit'        => 1,
'countrycodes' => 'ru',
'q'            => $q,
),
'https://nominatim.openstreetmap.org/search'
);

$res = wp_remote_get(
$url,
array(
'timeout' => 10,
'headers' => array(
'User-Agent' => BC_GEOCODE_UA,
'Accept'     => 'application/json',
),
)
);

if ( is_wp_error( $res ) ) {
return array( 'error' => $res->get_error_message() );
}

$code = (int) wp_remote_retrieve_response_code( $res );
if ( 200 !== $code ) {
return array( 'error' => 'HTTP ' . $code );
}

$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
if ( ! is_array( $data ) || empty( $data[0]['lat'] ) || ! isset( $data[0]['lon'] ) ) {
return array( 'miss' => true );
}

$lat = (float) $data[0]['lat'];
$lng = (float) $data[0]['lon'];
if ( ! bc_geocode_valid( $lat, $lng ) ) {
return array( 'miss' => true );
}

return array( 'lat' => $lat, 'lng' => $lng );
}

/**
 * Третий вариант: убираем сокращения типа улиц («ул.», «пр.», «наб.») — так Nominatim
 * иногда находит дом, который с сокращением не находится.
 *
 * @param string $q Запрос.
 * @return string Пустая строка, если менять нечего.
 */
function bc_geocode_streetless_query( $q ) {
$clean = preg_replace( '/(^|,)\s*(ул|пр-т|пр|просп|пер|б-р|наб|ш|мкр|д)\.?\s+/u', '$1 ', (string) $q );
$clean = trim( preg_replace( '/\s{2,}/u', ' ', str_replace( ',,', ',', (string) $clean ) ), ' ,' );

return $clean === $q ? '' : $clean;
}

/**
 * Ключ Яндекс Геокодера: отдельная константа BC_YGEO_KEY или общий BC_YMAP_KEY.
 *
 * @return string
 */
function bc_geocode_yandex_key() {
$key = defined( 'BC_YGEO_KEY' ) ? trim( (string) BC_YGEO_KEY ) : '';
if ( '' === $key && function_exists( 'bc_map_key' ) ) {
$key = bc_map_key();
}
return $key;
}

/**
 * Запрос к Яндекс Геокодеру (HTTP API 1.x).
 *
 * @param string $q Адрес.
 * @return array ['lat','lng'] | ['miss'] | ['error']
 */
function bc_geocode_yandex_fetch( $q ) {
$key = bc_geocode_yandex_key();
if ( '' === $key ) {
return array( 'error' => 'не задан ключ BC_YGEO_KEY / BC_YMAP_KEY' );
}

$url = add_query_arg(
array(
'apikey'  => $key,
'geocode' => $q,
'format'  => 'json',
'results' => 1,
'lang'    => 'ru_RU',
),
'https://geocode-maps.yandex.ru/1.x/'
);

$res = wp_remote_get(
$url,
array(
'timeout' => 10,
'headers' => array( 'User-Agent' => BC_GEOCODE_UA ),
)
);

if ( is_wp_error( $res ) ) {
return array( 'error' => $res->get_error_message() );
}

$code = (int) wp_remote_retrieve_response_code( $res );
$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );

if ( 200 !== $code ) {
$msg = is_array( $data ) && isset( $data['message'] ) ? $data['message'] : ( 'HTTP ' . $code );
return array( 'error' => $msg );
}

if ( ! isset( $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'] ) ) {
return array( 'miss' => true );
}

$pos = preg_split( '/\s+/', trim( (string) $data['response']['GeoObjectCollection']['featureMember'][0]['GeoObject']['Point']['pos'] ) );
if ( count( $pos ) < 2 ) {
return array( 'miss' => true );
}

$lng = (float) $pos[0];
$lat = (float) $pos[1];
if ( ! bc_geocode_valid( $lat, $lng ) ) {
return array( 'miss' => true );
}

return array( 'lat' => $lat, 'lng' => $lng );
}

/**
 * Провайдер геокодинга.
 *
 * @param string $q        Запрос.
 * @param string $provider nominatim|yandex.
 * @return array
 */
function bc_geocode_fetch( $q, $provider = 'nominatim' ) {
return 'yandex' === $provider ? bc_geocode_yandex_fetch( $q ) : bc_geocode_nominatim_fetch( $q );
}

/**
 * Упрощённый адрес для второй попытки: убираем офисы/помещения/примечания в скобках.
 *
 * @param string $q Запрос.
 * @return string Пустая строка, если упрощать нечего.
 */
function bc_geocode_clean_query( $q ) {
$clean = preg_replace( '/,?\s*(пом|оф|кв|каб)\.?\s*[0-9A-Za-zА-Яа-я\/\-]+/u', '', (string) $q );
$clean = preg_replace( '/,?\s*стр\.?\s*[0-9A-Za-zА-Яа-я\/\-]+/u', '', (string) $clean );
$clean = preg_replace( '/\s*\([^)]*\)/u', '', (string) $clean );
$clean = trim( preg_replace( '/\s{2,}/u', ' ', str_replace( ',,', ',', (string) $clean ) ), ' ,' );

return $clean === $q ? '' : $clean;
}

/**
 * Варианты поискового запроса: основной, упрощённый, без сокращений улиц.
 *
 * @param string $q Запрос (уже с городом).
 * @return string[]
 */
function bc_geocode_variants( $q ) {
	return array_values(
		array_unique(
			array_filter(
				array(
					$q,
					bc_geocode_clean_query( $q ),
					bc_geocode_streetless_query( $q ),
					bc_geocode_streetless_query( bc_geocode_clean_query( $q ) ),
				)
			)
		)
	);
}

/**
 * WP-CLI: wp bc-geocode [--dry-run] [--limit=N] [--force] [--provider=nominatim|yandex].
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_geocode_cli( $args, $assoc_args ) {
$dry   = isset( $assoc_args['dry-run'] );
$force = isset( $assoc_args['force'] );
$limit = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
$provider = isset( $assoc_args['provider'] ) ? strtolower( (string) $assoc_args['provider'] ) : 'nominatim';
if ( ! in_array( $provider, array( 'nominatim', 'yandex' ), true ) ) {
$provider = 'nominatim';
}

$targets = bc_geocode_targets( $limit );
if ( ! $targets ) {
WP_CLI::success( 'У всех организаций есть валидные координаты — геокодить нечего.' );
return;
}

$cache = bc_geocode_cache_get();
$rows  = array();
$found = 0;
$miss  = array();
$errs  = array();
$calls = 0;
$first_request = true;

foreach ( $targets as $t ) {
$q        = bc_geocode_query( $t['address'] );
$variants = bc_geocode_variants( $q );
$coord    = null;

foreach ( $variants as $vq ) {
$vkey = $provider . '|' . mb_strtolower( $vq );

if ( ! $force && isset( $cache[ $vkey ] ) ) {
$res = $cache[ $vkey ];
} else {
if ( ! $first_request ) {
usleep( BC_GEOCODE_PAUSE );
}
$first_request = false;
$calls++;
$res = bc_geocode_fetch( $vq, $provider );

if ( isset( $res['error'] ) ) {
$errs[] = $t['title'] . ' — ' . $res['error'];
continue;
}
$cache[ $vkey ] = $res;
}

if ( isset( $res['lat'], $res['lng'] ) ) {
$coord = $res;
break;
}
}

if ( $coord ) {
$found++;
$rows[] = array(
'Название'   => $t['title'],
'Адрес'      => $t['address'],
'Найдено'    => 'да',
'Координаты' => round( $coord['lat'], 6 ) . ', ' . round( $coord['lng'], 6 ),
);
if ( ! $dry ) {
update_post_meta( $t['id'], 'bc_lat', (string) round( $coord['lat'], 6 ) );
update_post_meta( $t['id'], 'bc_lng', (string) round( $coord['lng'], 6 ) );
}
} else {
$miss[] = $t['title'] . ' — ' . $t['address'];
$rows[] = array(
'Название'   => $t['title'],
'Адрес'      => $t['address'],
'Найдено'    => 'нет',
'Координаты' => '—',
);
}
}

if ( ! $dry ) {
bc_geocode_cache_set( $cache );
}

WP_CLI\Utils\format_items( 'table', $rows, array( 'Название', 'Адрес', 'Найдено', 'Координаты' ) );
WP_CLI::log( '' );
WP_CLI::log( sprintf( 'Провайдер: %s | всего карточек: %d | найдено: %d | не найдено: %d | запросов: %d', $provider, count( $targets ), $found, count( $miss ), $calls ) );

if ( $miss ) {
WP_CLI::log( 'Не найдены (нужен ручной разбор):' );
foreach ( $miss as $m ) {
WP_CLI::log( '  — ' . $m );
}
}

if ( $errs ) {
WP_CLI::warning( 'Ошибки запросов:' );
foreach ( $errs as $e ) {
WP_CLI::log( '  ! ' . $e );
}
}

WP_CLI::success( $dry ? 'Dry-run завершён (мета не изменялась).' : 'Геокодинг завершён, координаты записаны в bc_lat / bc_lng.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
WP_CLI::add_command( 'bc-geocode', 'bc_geocode_cli' );
}