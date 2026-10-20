<?php
/**
 * OpenStreetMap: выгрузка POI (Overpass API) и дозаполнение карточек организаций.
 *
 * Команды:
 *   wp bc-osm scan   [--city=Красноярск] [--bbox=S,W,N,E] [--mode=district|city]
 *                    [--tile=0.012] [--out=dir] [--import=file] [--force] [--retries=4]
 *   wp bc-osm enrich [--dry-run] [--limit=N] [--threshold=0.6] [--file=file] [--force]
 *
 * Данные OSM (ODbL): https://www.openstreetmap.org/copyright. Запросы — с честным
 * User-Agent, кэшем ответов и ретраями (публичный Overpass часто отвечает 502/504).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BC_OSM_UA          = 'BeregCityBot/1.0 (+https://beregcity.ru/; hello@beregcity.ru)';
const BC_OSM_SCAN_OPTION = 'bc_osm_last_scan';
const BC_OSM_BBOX_OPTION = 'bc_osm_bbox_cache';
const BC_OSM_MAX_TILES   = 60;

/**
 * Список эндпоинтов Overpass (первый — основной, остальные — фолбэк).
 *
 * @return string[]
 */
function bc_osm_endpoints() {
	$list = defined( 'BC_OVERPASS_URL' ) ? array( (string) BC_OVERPASS_URL ) : array();

	return array_values(
		array_unique(
			array_merge(
				$list,
				array(
					'https://overpass-api.de/api/interpreter',
					'https://z.overpass-api.de/api/interpreter',
					'https://lz4.overpass-api.de/api/interpreter',
					'https://maps.mail.ru/osm/tools/overpass/api/interpreter',
				)
			)
		)
	);
}

/**
 * Каталог выгрузок (uploads/bc-osm[/подкаталог]).
 *
 * @param string $sub Подкаталог.
 * @return string Путь без завершающего слэша.
 */
function bc_osm_dir( $sub = '' ) {
	$up  = wp_upload_dir();
	$dir = trailingslashit( $up['basedir'] ) . 'bc-osm' . ( '' !== $sub ? '/' . $sub : '' );

	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	return untrailingslashit( $dir );
}

/**
 * Путь к файлу кэша запроса Overpass.
 *
 * @param string $query Тело запроса.
 * @return string
 */
function bc_osm_cache_path( $query ) {
	return bc_osm_dir( 'cache' ) . '/' . md5( $query ) . '.json';
}

/**
 * POST в Overpass с перебором зеркал и экспоненциальной паузой.
 *
 * @param string $query   Overpass QL.
 * @param int    $retries Сколько кругов повторов.
 * @param bool   $force   Игнорировать файловый кэш.
 * @return array{ok:bool,body:string,url:string,error:string,cached:bool}
 */
function bc_osm_fetch( $query, $retries = 4, $force = false ) {
	$path = bc_osm_cache_path( $query );

	if ( ! $force && file_exists( $path ) ) {
		$body = (string) file_get_contents( $path );
		if ( '' !== $body && false !== strpos( $body, '"elements"' ) ) {
			return array( 'ok' => true, 'body' => $body, 'url' => 'cache', 'error' => '', 'cached' => true );
		}
	}

	$retries = max( 1, (int) $retries );
	$errors  = array();
	$wait    = 8;

	for ( $round = 0; $round < $retries; $round++ ) {
		foreach ( bc_osm_endpoints() as $url ) {
			$res = wp_remote_post(
				$url,
				array(
					'timeout' => 150,
					'headers' => array(
						'User-Agent' => BC_OSM_UA,
						'Accept'     => 'application/json',
					),
					'body'    => array( 'data' => $query ),
				)
			);

			if ( is_wp_error( $res ) ) {
				$errors[] = $url . ': ' . $res->get_error_message();
				continue;
			}

			$code = (int) wp_remote_retrieve_response_code( $res );
			$body = (string) wp_remote_retrieve_body( $res );

			if ( 200 === $code && false !== strpos( $body, '"elements"' ) ) {
				if ( ! is_dir( dirname( $path ) ) ) {
					wp_mkdir_p( dirname( $path ) );
				}
				file_put_contents( $path, $body );
				return array( 'ok' => true, 'body' => $body, 'url' => $url, 'error' => '', 'cached' => false );
			}

			$errors[] = $url . ': HTTP ' . $code;

			// 400 — ошибка в запросе, повторять бессмысленно.
			if ( 400 === $code ) {
				return array(
					'ok'     => false,
					'body'   => $body,
					'url'    => $url,
					'error'  => 'HTTP 400: ' . mb_substr( trim( wp_strip_all_tags( $body ) ), 0, 200 ),
					'cached' => false,
				);
			}
		}

		if ( $round < $retries - 1 ) {
			if ( defined( 'WP_CLI' ) && WP_CLI ) {
				WP_CLI::log( sprintf( '  зеркала заняты, пауза %d с…', $wait ) );
			}
			sleep( $wait );
			$wait = min( 120, $wait * 2 );
		}
	}

	return array(
		'ok'     => false,
		'body'   => '',
		'url'    => '',
		'error'  => implode( ' | ', array_slice( $errors, -4 ) ),
		'cached' => false,
	);
}


/**
 * Bbox населённого пункта через Nominatim (кэш на неделю).
 *
 * @param string $city Название города.
 * @return array|null [s,w,n,e]
 */
function bc_osm_city_bbox( $city ) {
	$cache = get_option( BC_OSM_BBOX_OPTION, array() );
	$key   = mb_strtolower( trim( $city ) );

	if ( isset( $cache[ $key ]['ts'], $cache[ $key ]['bbox'] ) && ( time() - (int) $cache[ $key ]['ts'] ) < WEEK_IN_SECONDS ) {
		return $cache[ $key ]['bbox'];
	}

	$url = add_query_arg(
		array(
			'format'       => 'jsonv2',
			'limit'        => 1,
			'countrycodes' => 'ru',
			'q'            => $city . ', Россия',
		),
		'https://nominatim.openstreetmap.org/search'
	);

	$res = wp_remote_get(
		$url,
		array(
			'timeout' => 15,
			'headers' => array(
				'User-Agent' => BC_GEOCODE_UA,
				'Accept'     => 'application/json',
			),
		)
	);

	if ( is_wp_error( $res ) || 200 !== (int) wp_remote_retrieve_response_code( $res ) ) {
		return null;
	}

	$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );
	if ( empty( $data[0]['boundingbox'] ) || ! is_array( $data[0]['boundingbox'] ) ) {
		return null;
	}

	$bb   = array_map( 'floatval', $data[0]['boundingbox'] ); // [s, n, w, e].
	$bbox = array( $bb[0], $bb[2], $bb[1], $bb[3] );

	$cache[ $key ] = array( 'ts' => time(), 'bbox' => $bbox );
	update_option( BC_OSM_BBOX_OPTION, $cache, false );

	return $bbox;
}

/**
 * Bbox вокруг уже геокодированных организаций (±$pad градуса).
 *
 * @param float $pad Отступ в градусах.
 * @return array|null [s,w,n,e]
 */
function bc_osm_district_bbox( $pad = 0.012 ) {
	global $wpdb;

	$rows = $wpdb->get_results(
		"SELECT lat.meta_value AS lat, lng.meta_value AS lng
		 FROM {$wpdb->postmeta} lat
		 INNER JOIN {$wpdb->postmeta} lng ON lng.post_id = lat.post_id AND lng.meta_key = 'bc_lng'
		 WHERE lat.meta_key = 'bc_lat'
		   AND lat.meta_value REGEXP '^-?[0-9.]+$'
		   AND lng.meta_value REGEXP '^-?[0-9.]+$'",
		ARRAY_A
	);

	$lats = array();
	$lngs = array();

	foreach ( (array) $rows as $row ) {
		$la = (float) $row['lat'];
		$ln = (float) $row['lng'];
		if ( ! bc_geocode_valid( $la, $ln ) ) {
			continue;
		}
		$lats[] = $la;
		$lngs[] = $ln;
	}

	if ( ! $lats ) {
		return null;
	}

	return array(
		max( -90, min( $lats ) - $pad ),
		max( -180, min( $lngs ) - $pad * 1.4 ),
		min( 90, max( $lats ) + $pad ),
		min( 180, max( $lngs ) + $pad * 1.4 ),
	);
}

/**
 * Нарезка bbox на тайлы (публичный Overpass легче переносит мелкие запросы).
 *
 * @param array $bbox [s,w,n,e]
 * @param float $step Размер тайла в градусах.
 * @return array[]
 */
function bc_osm_tiles( $bbox, $step ) {
	list( $s, $w, $n, $e ) = array_map( 'floatval', $bbox );
	$step                  = max( 0.002, (float) $step );

	while ( true ) {
		$tiles = array();
		for ( $lat = $s; $lat < $n - 0.0001; $lat += $step ) {
			for ( $lng = $w; $lng < $e - 0.0001; $lng += $step ) {
				$tiles[] = array( $lat, $lng, min( $lat + $step, $n ), min( $lng + $step, $e ) );
			}
		}
		if ( count( $tiles ) <= BC_OSM_MAX_TILES || $step > 1 ) {
			break;
		}
		$step *= 1.5;
	}

	return $tiles;
}

/**
 * Overpass QL для одного тайла: все именованные POI + объекты с контактами.
 *
 * @param array $bbox [s,w,n,e]
 * @return string
 */
function bc_osm_query( $bbox ) {
	list( $s, $w, $n, $e ) = array_map( 'floatval', $bbox );
	$area                  = sprintf( '%.5f,%.5f,%.5f,%.5f', $s, $w, $n, $e );

	$keys = array(
		'shop',
		'amenity',
		'office',
		'healthcare',
		'craft',
		'tourism',
		'leisure',
		'phone',
		'contact:phone',
		'opening_hours',
		'website',
		'contact:website',
		'contact:vk',
		'contact:telegram',
		'contact:whatsapp',
	);

	$clauses = '';
	foreach ( $keys as $key ) {
		$clauses .= sprintf( 'nwr["name"]["%s"](%s);', $key, $area );
	}

	return sprintf( '[out:json][timeout:90];(%s);out tags center;', $clauses );
}


/**
 * Разбор ответа Overpass в плоский список объектов.
 *
 * @param string $json Тело ответа.
 * @return array[] Объекты с ключами id, name, lat, lng, phones, hours, website, vk, telegram, whatsapp, street, house, category.
 */
function bc_osm_normalize_elements( $json ) {
	$data = json_decode( (string) $json, true );
	if ( ! is_array( $data ) || empty( $data['elements'] ) || ! is_array( $data['elements'] ) ) {
		return array();
	}

	$out = array();

	foreach ( $data['elements'] as $el ) {
		$tags = isset( $el['tags'] ) && is_array( $el['tags'] ) ? $el['tags'] : array();
		$name = isset( $tags['name'] ) ? trim( (string) $tags['name'] ) : '';
		if ( '' === $name ) {
			continue;
		}

		// Отбрасываем всё, что не POI и не несёт контактов (OSM API отдаёт и улицы, и дома).
		$useful = false;
		foreach ( array( 'shop', 'amenity', 'office', 'healthcare', 'craft', 'tourism', 'leisure', 'phone', 'contact:phone', 'opening_hours', 'website', 'contact:website', 'contact:vk', 'contact:telegram', 'contact:whatsapp' ) as $key ) {
			if ( ! empty( $tags[ $key ] ) ) {
				$useful = true;
				break;
			}
		}
		if ( ! $useful ) {
			continue;
		}

		$lat = isset( $el['lat'] ) ? (float) $el['lat'] : ( isset( $el['center']['lat'] ) ? (float) $el['center']['lat'] : 0.0 );
		$lng = isset( $el['lon'] ) ? (float) $el['lon'] : ( isset( $el['center']['lon'] ) ? (float) $el['center']['lon'] : 0.0 );
		if ( ! bc_geocode_valid( $lat, $lng ) ) {
			$lat = 0.0;
			$lng = 0.0;
		}

		$type = isset( $el['type'] ) ? (string) $el['type'] : 'node';
		$id   = isset( $el['id'] ) ? (string) $el['id'] : '';

		$phones = array();
		foreach ( array( 'phone', 'contact:phone', 'contact:mobile' ) as $pk ) {
			if ( ! empty( $tags[ $pk ] ) ) {
				$phones = array_merge( $phones, bc_osm_split_phones( $tags[ $pk ] ) );
			}
		}

		$category = '';
		foreach ( array( 'healthcare', 'amenity', 'shop', 'office', 'craft', 'tourism', 'leisure' ) as $ck ) {
			if ( ! empty( $tags[ $ck ] ) ) {
				$category = $ck . '=' . $tags[ $ck ];
				break;
			}
		}

		$out[ $type . '/' . $id ] = array(
			'id'        => $type . '/' . $id,
			'name'      => $name,
			'lat'       => $lat,
			'lng'       => $lng,
			'phones'    => array_values( array_unique( $phones ) ),
			'hours'     => isset( $tags['opening_hours'] ) ? trim( (string) $tags['opening_hours'] ) : '',
			'website'   => ! empty( $tags['website'] ) ? trim( (string) $tags['website'] ) : ( ! empty( $tags['contact:website'] ) ? trim( (string) $tags['contact:website'] ) : '' ),
			'vk'        => isset( $tags['contact:vk'] ) ? trim( (string) $tags['contact:vk'] ) : '',
			'telegram'  => isset( $tags['contact:telegram'] ) ? trim( (string) $tags['contact:telegram'] ) : '',
			'whatsapp'  => isset( $tags['contact:whatsapp'] ) ? trim( (string) $tags['contact:whatsapp'] ) : '',
			'instagram' => isset( $tags['contact:instagram'] ) ? trim( (string) $tags['contact:instagram'] ) : '',
			'street'    => isset( $tags['addr:street'] ) ? trim( (string) $tags['addr:street'] ) : '',
			'house'     => isset( $tags['addr:housenumber'] ) ? trim( (string) $tags['addr:housenumber'] ) : '',
			'city'      => isset( $tags['addr:city'] ) ? trim( (string) $tags['addr:city'] ) : '',
			'category'  => $category,
		);
	}

	return array_values( $out );
}

/**
 * Разбор строки телефонов OSM в список нормализованных номеров.
 *
 * @param string $raw Значение тега phone/contact:phone.
 * @return string[]
 */
function bc_osm_split_phones( $raw ) {
	$parts  = preg_split( '/[;,\/]+/u', (string) $raw );
	$phones = array();

	foreach ( (array) $parts as $part ) {
		$digits = preg_replace( '/[^0-9+]/', '', (string) $part );
		if ( '' === $digits ) {
			continue;
		}

		$digits = ltrim( $digits, '+' );
		$len    = strlen( $digits );

		if ( 11 === $len && '8' === $digits[0] ) {
			$digits = '7' . substr( $digits, 1 );
		} elseif ( 10 === $len ) {
			$digits = '7' . $digits;
		}

		$len = strlen( $digits );
		if ( 11 !== $len || '7' !== $digits[0] ) {
			continue; // Городские и обрезанные номера пропускаем.
		}

		$phones[] = '+' . $digits;
	}

	return array_values( array_unique( $phones ) );
}

/**
 * Человекочитаемый график из opening_hours.
 *
 * Если синтаксис не разобрать — возвращает пустую строку (поле не заполняем,
 * чтобы не писать в карточку мусор вроде «sunrise-sunset»).
 *
 * @param string $oh Значение opening_hours.
 * @return string
 */
function bc_osm_humanize_hours( $oh ) {
	$oh = trim( (string) $oh );
	if ( '' === $oh ) {
		return '';
	}

	if ( preg_match( '/^24\/7$/i', $oh ) ) {
		return 'Круглосуточно';
	}

	$days = array(
		'Mo' => 'Пн',
		'Tu' => 'Вт',
		'We' => 'Ср',
		'Th' => 'Чт',
		'Fr' => 'Пт',
		'Sa' => 'Сб',
		'Su' => 'Вс',
		'PH' => 'праздники',
	);

	$result = preg_replace_callback(
		'/\b(Mo|Tu|We|Th|Fr|Sa|Su|PH)(?:\s*-\s*(Mo|Tu|We|Th|Fr|Sa|Su|PH))?\b/',
		function ( $m ) use ( $days ) {
			$from = $days[ $m[1] ];
			$to   = isset( $m[2] ) && '' !== $m[2] ? $days[ $m[2] ] : '';
			return '' !== $to ? $from . '–' . $to : $from;
		},
		$oh
	);

	$result = preg_replace( '/\boff\b/i', 'выходной', (string) $result );
	$result = preg_replace( '/(\d{1,2}:\d{2})\s*-\s*(\d{1,2}:\d{2})/', '$1–$2', (string) $result );
	$result = str_replace( ';', ', ', (string) $result );
	$result = trim( preg_replace( '/\s{2,}/u', ' ', (string) $result ), " ," );

	// Остались латинские буквы/нестандартные конструкции — не рискуем.
	if ( '' === $result || preg_match( '/[A-Za-z]/', $result ) ) {
		return '';
	}

	return $result;
}


/**
 * Нормализация названия: нижний регистр, без кавычек, ООО/ИП, пунктуации.
 *
 * @param string $name Название.
 * @return string
 */
function bc_osm_norm_name( $name ) {
	$s = mb_strtolower( trim( (string) $name ), 'UTF-8' );
	$s = str_replace( 'ё', 'е', $s );
	$s = preg_replace( '/(^|\s)(ооо|ип|зао|оао|пао|ао|нко|мбу|гбу|фгбу|мбуз|гбуз|ao)(\s|$)/u', ' ', $s );
	$s = preg_replace( '/[«»"“”„\'’`]/u', ' ', (string) $s );
	$s = preg_replace( '/[^\p{L}\p{N}]+/u', ' ', (string) $s );

	return trim( preg_replace( '/\s+/u', ' ', (string) $s ) );
}

/**
 * Убрать типовое слово-категорию в начале названия.
 *
 * @param string $norm Уже нормализованное название.
 * @return string
 */
function bc_osm_strip_generic( $norm ) {
	$words = array(
		'аптека',
		'аптечный',
		'супермаркет',
		'магазин',
		'кафе',
		'кофейня',
		'пельменная',
		'ресторан',
		'бар',
		'столовая',
		'доставка',
		'еды',
		'стоматология',
		'стоматологическая',
		'клиника',
		'медцентр',
		'медицинский',
		'центр',
		'салон',
		'красоты',
		'студия',
		'фитнес',
		'фитнесстудия',
		'барбершоп',
		'детский',
		'центр',
		'частный',
		'детсад',
		'детский сад',
		'кабинет',
		'психолога',
		'агентство',
		'автосервис',
		'авто',
		'сервис',
		'ремонта',
		'it',
		'услуги',
	);

	$out   = (string) $norm;
	$guard = 0;

	while ( $guard++ < 4 ) {
		$changed = false;
		foreach ( $words as $w ) {
			if ( '' !== $w && 0 === mb_strpos( $out, $w . ' ' ) ) {
				$out     = trim( mb_substr( $out, mb_strlen( $w ) ) );
				$changed = true;
				break;
			}
		}
		if ( ! $changed ) {
			break;
		}
	}

	return $out;
}

/**
 * Посимвольное расстояние (mb-safe) — байтовый similar_text на кириллице врёт.
 *
 * @param string $a Первая строка.
 * @param string $b Вторая строка.
 * @return float 0…1
 */
function bc_osm_char_ratio( $a, $b ) {
	$ca = function_exists( 'mb_str_split' ) ? mb_str_split( (string) $a ) : preg_split( '//u', (string) $a, -1, PREG_SPLIT_NO_EMPTY );
	$cb = function_exists( 'mb_str_split' ) ? mb_str_split( (string) $b ) : preg_split( '//u', (string) $b, -1, PREG_SPLIT_NO_EMPTY );

	$la = count( $ca );
	$lb = count( $cb );

	if ( ! $la && ! $lb ) {
		return 1.0;
	}
	if ( ! $la || ! $lb ) {
		return 0.0;
	}

	$prev = range( 0, $lb );

	foreach ( $ca as $i => $ch1 ) {
		$cur = array( $i + 1 );
		foreach ( $cb as $j => $ch2 ) {
			$cost      = $ch1 === $ch2 ? 0 : 1;
			$cur[ $j + 1 ] = min( $prev[ $j + 1 ] + 1, $cur[ $j ] + 1, $prev[ $j ] + $cost );
		}
		$prev = $cur;
	}

	return 1 - $prev[ $lb ] / max( $la, $lb );
}

/**
 * Схожесть двух нормализованных строк (0…1).
 *
 * Учитываем: посимвольное расстояние, пересечение слов и полное покрытие слов
 * короткого названия. Вхождение подстроки даёт бонус только для содержательных
 * названий (≥5 символов и ≥50% длины) — иначе «дом» совпадал бы с «Домми».
 *
 * @param string $a Первая строка.
 * @param string $b Вторая строка.
 * @return float
 */
function bc_osm_similarity( $a, $b ) {
	$a = trim( (string) $a );
	$b = trim( (string) $b );

	if ( '' === $a || '' === $b ) {
		return 0.0;
	}
	if ( $a === $b ) {
		return 1.0;
	}

	$short = mb_strlen( $a ) <= mb_strlen( $b ) ? $a : $b;
	$long  = ( $short === $a ) ? $b : $a;
	$sl    = mb_strlen( $short );
	$ll    = mb_strlen( $long );

	// Слишком короткие названия («дом», «бар») сравниваем только на точное совпадение.
	if ( $sl < 4 ) {
		return 0.0;
	}

	$contain = ( $sl >= 5 && $ll > 0 && ( $sl / $ll ) >= 0.5 && false !== mb_strpos( $long, $short ) ) ? 0.85 : 0.0;

	$sim = bc_osm_char_ratio( $a, $b );

	$ta = array_values( array_unique( preg_split( '/\s+/u', $a, -1, PREG_SPLIT_NO_EMPTY ) ) );
	$tb = array_values( array_unique( preg_split( '/\s+/u', $b, -1, PREG_SPLIT_NO_EMPTY ) ) );
	$union    = array_unique( array_merge( $ta, $tb ) );
	$jaccard  = $union ? count( array_intersect( $ta, $tb ) ) / count( $union ) : 0.0;

	$ts        = preg_split( '/\s+/u', $short, -1, PREG_SPLIT_NO_EMPTY );
	$tl        = preg_split( '/\s+/u', $long, -1, PREG_SPLIT_NO_EMPTY );
	$cover     = ( $ts && ! array_diff( $ts, $tl ) ) ? 0.8 : 0.0;

	return round( max( $sim, $jaccard, $cover, $contain ), 3 );
}

/**
 * Схожесть названий с учётом отбрасывания типового слова.
 *
 * @param string $a Название карточки.
 * @param string $b Название объекта OSM.
 * @return float
 */
function bc_osm_name_score( $a, $b ) {
	$na = bc_osm_norm_name( $a );
	$nb = bc_osm_norm_name( $b );

	$score = bc_osm_similarity( $na, $nb );

	$sa = bc_osm_strip_generic( $na );
	$sb = bc_osm_strip_generic( $nb );
	if ( '' !== $sa && '' !== $sb ) {
		$score = max( $score, bc_osm_similarity( $sa, $sb ) );
	}

	return round( $score, 3 );
}

/**
 * Название улицы из адреса карточки («ул. Парусная, 10» → «парусная»).
 *
 * @param string $address Адрес.
 * @return string
 */
function bc_osm_street_key( $address ) {
	$address = preg_replace( '/\s*\([^)]*\)/u', '', (string) $address );
	$address = preg_replace( '/(^|,)\s*(ул|пр-т|пр|просп|пер|б-р|наб|ш|мкр)\.?\s+/u', '$1 ', $address );
	$parts   = preg_split( '/,/', (string) $address );

	$first = isset( $parts[0] ) ? bc_osm_norm_name( $parts[0] ) : '';

	return trim( $first );
}

/**
 * Расстояние между точками в метрах (haversine).
 *
 * @param float $lat1 Широта 1.
 * @param float $lng1 Долгота 1.
 * @param float $lat2 Широта 2.
 * @param float $lng2 Долгота 2.
 * @return float
 */
function bc_osm_distance( $lat1, $lng1, $lat2, $lng2 ) {
	$r    = 6371000;
	$dLat = deg2rad( $lat2 - $lat1 );
	$dLng = deg2rad( $lng2 - $lng1 );
	$a    = sin( $dLat / 2 ) * sin( $dLat / 2 ) + cos( deg2rad( $lat1 ) ) * cos( deg2rad( $lat2 ) ) * sin( $dLng / 2 ) * sin( $dLng / 2 );

	return $r * 2 * atan2( sqrt( $a ), sqrt( 1 - $a ) );
}

/**
 * Итоговый балл совпадения карточки и объекта OSM.
 *
 * @param array  $org      ['title','address','lat','lng']
 * @param array  $cand     Объект OSM.
 * @param string $street   Ключ улицы карточки.
 * @return array{score:float,name:float,street:bool,dist:float,strict:bool}
 */
function bc_osm_match_score( $org, $cand, $street ) {
	$score = bc_osm_name_score( $org['title'], $cand['name'] );
	$bonus = 0.0;

	$na = bc_osm_norm_name( $org['title'] );
	$nb = bc_osm_norm_name( $cand['name'] );
	$sa = bc_osm_strip_generic( $na );
	$sb = bc_osm_strip_generic( $nb );

	$ratio = max(
		bc_osm_char_ratio( $na, $nb ),
		( '' !== $sa && '' !== $sb ) ? bc_osm_char_ratio( $sa, $sb ) : 0
	);

	// «Строгое» совпадение: точное имя/имя без типового слова либо близость ≥0.7.
	// Общие слова («южный», «центр») сами по себе карточку не подтверждают.
	$strict = ( $na === $nb ) || ( '' !== $sa && $sa === $sb ) || $ratio >= 0.7;

	$cand_street = bc_osm_norm_name( $cand['street'] );
	$street_hit  = ( '' !== $street && '' !== $cand_street && false !== mb_strpos( $cand_street, $street ) );
	if ( $street_hit ) {
		$bonus += 0.18;
	}

	$dist = 0.0;
	if ( $org['lat'] && $org['lng'] && $cand['lat'] && $cand['lng'] ) {
		$dist = bc_osm_distance( $org['lat'], $org['lng'], $cand['lat'], $cand['lng'] );
		if ( $dist <= 300 ) {
			$bonus += 0.08;
		} elseif ( $dist > 3000 ) {
			$bonus -= 0.25;
		}
	}

	return array(
		'score'  => round( max( 0, min( 1.2, $score + $bonus ) ), 3 ),
		'name'   => $score,
		'street' => $street_hit,
		'dist'   => $dist,
		'strict' => $strict,
	);
}


/**
 * Конвертация XML из api.openstreetmap.org/api/0.6/map в Overpass-подобные элементы.
 *
 * Резервный источник, когда публичный Overpass перегружен (502/504). Чистая функция
 * (без WP) — используется и командой, и локальным сборщиком.
 *
 * @param string $xml Тело XML-ответа OSM API.
 * @return array[] [type,id,lat/lon|center,tags]
 */
function bc_osm_xml_to_elements( $xml ) {
	if ( '' === trim( (string) $xml ) || false === strpos( (string) $xml, '<osm' ) ) {
		return array();
	}

	$prev = libxml_use_internal_errors( true );
	$doc  = simplexml_load_string( (string) $xml, 'SimpleXMLElement', LIBXML_NOCDATA | LIBXML_PARSEHUGE );
	libxml_clear_errors();
	libxml_use_internal_errors( $prev );

	if ( ! $doc ) {
		return array();
	}

	$coords = array();

	foreach ( $doc->node as $node ) {
		$coords[ (string) $node['id'] ] = array( (float) $node['lat'], (float) $node['lon'] );
	}

	$elements = array();

	foreach ( $doc->node as $node ) {
		if ( ! $node->tag ) {
			continue;
		}
		$tags = array();
		foreach ( $node->tag as $tag ) {
			$tags[ (string) $tag['k'] ] = (string) $tag['v'];
		}
		$elements[] = array(
			'type' => 'node',
			'id'   => (string) $node['id'],
			'lat'  => (float) $node['lat'],
			'lon'  => (float) $node['lon'],
			'tags' => $tags,
		);
	}

	foreach ( $doc->way as $way ) {
		if ( ! $way->tag ) {
			continue;
		}

		$lats = array();
		$lngs = array();
		foreach ( $way->nd as $nd ) {
			$ref = (string) $nd['ref'];
			if ( isset( $coords[ $ref ] ) ) {
				$lats[] = $coords[ $ref ][0];
				$lngs[] = $coords[ $ref ][1];
			}
		}
		if ( ! $lats ) {
			continue;
		}

		$tags = array();
		foreach ( $way->tag as $tag ) {
			$tags[ (string) $tag['k'] ] = (string) $tag['v'];
		}

		$elements[] = array(
			'type'   => 'way',
			'id'     => (string) $way['id'],
			'center' => array(
				'lat' => array_sum( $lats ) / count( $lats ),
				'lon' => array_sum( $lngs ) / count( $lngs ),
			),
			'tags'   => $tags,
		);
	}

	return $elements;
}

/**
 * Резервная выгрузка через OSM API (без очереди Overpass), с файловым кэшем.
 *
 * @param array $bbox  [s,w,n,e]
 * @param bool  $force Игнорировать кэш.
 * @return array{ok:bool,elements:array,error:string,cached:bool}
 */
function bc_osm_osmapi_fetch( $bbox, $force = false ) {
	list( $s, $w, $n, $e ) = array_map( 'floatval', $bbox );
	$area = sprintf( '%.5f,%.5f,%.5f,%.5f', $w, $s, $e, $n ); // OSM API: minlon,minlat,maxlon,maxlat.
	$path = bc_osm_dir( 'cache' ) . '/api-' . md5( $area ) . '.json';

	if ( ! $force && file_exists( $path ) ) {
		$body = (string) file_get_contents( $path );
		$data = json_decode( $body, true );
		if ( is_array( $data ) && isset( $data['elements'] ) ) {
			return array( 'ok' => true, 'elements' => $data['elements'], 'error' => '', 'cached' => true );
		}
	}

	$url = 'https://api.openstreetmap.org/api/0.6/map?bbox=' . $area;
	$res = wp_remote_get(
		$url,
		array(
			'timeout' => 120,
			'headers' => array( 'User-Agent' => BC_OSM_UA ),
		)
	);

	if ( is_wp_error( $res ) ) {
		return array( 'ok' => false, 'elements' => array(), 'error' => $res->get_error_message(), 'cached' => false );
	}

	$code = (int) wp_remote_retrieve_response_code( $res );
	if ( 200 !== $code ) {
		return array( 'ok' => false, 'elements' => array(), 'error' => 'OSM API HTTP ' . $code, 'cached' => false );
	}

	$elements = bc_osm_xml_to_elements( (string) wp_remote_retrieve_body( $res ) );

	if ( ! is_dir( dirname( $path ) ) ) {
		wp_mkdir_p( dirname( $path ) );
	}
	file_put_contents( $path, wp_json_encode( array( 'area' => $area, 'elements' => $elements ), JSON_UNESCAPED_UNICODE ) );

	return array( 'ok' => true, 'elements' => $elements, 'error' => '', 'cached' => false );
}

/**
 * Обход тайлов и сбор объектов OSM (Overpass, при перегрузке — OSM API).
 *
 * @param array  $bbox    [s,w,n,e]
 * @param float  $tile    Размер тайла.
 * @param bool   $force   Игнорировать кэш.
 * @param int    $retries Повторы.
 * @param string $source  auto|overpass|osmapi.
 * @return array{elements:array,errors:array,tiles:int,cached:int}
 */
function bc_osm_collect( $bbox, $tile, $force, $retries, $source = 'auto' ) {
	$tiles    = bc_osm_tiles( $bbox, $tile );
	$elements = array();
	$errors   = array();
	$cached   = 0;

	foreach ( $tiles as $i => $t ) {
		$els = null;
		$src = '';

		if ( 'osmapi' !== $source ) {
			$query = bc_osm_query( $t );
			$res   = bc_osm_fetch( $query, $retries, $force );

			if ( $res['ok'] ) {
				$els = bc_osm_normalize_elements( $res['body'] );
				$src = $res['cached'] ? 'кэш Overpass' : 'Overpass';
				if ( $res['cached'] ) {
					$cached++;
				}
			} else {
				$errors[] = sprintf( 'тайл %d: Overpass — %s', $i + 1, $res['error'] );
			}
		}

		if ( null === $els && 'overpass' !== $source ) {
			$fb = bc_osm_osmapi_fetch( $t, $force );
			if ( $fb['ok'] ) {
				$els = bc_osm_normalize_elements( wp_json_encode( array( 'elements' => $fb['elements'] ) ) );
				$src = $fb['cached'] ? 'кэш OSM API' : 'OSM API';
				if ( $fb['cached'] ) {
					$cached++;
				}
			} else {
				$errors[] = sprintf( 'тайл %d: OSM API — %s', $i + 1, $fb['error'] );
			}
		}

		if ( null === $els ) {
			continue;
		}

		foreach ( $els as $el ) {
			$elements[ $el['id'] ] = $el;
		}

		if ( defined( 'WP_CLI' ) && WP_CLI ) {
			WP_CLI::log( sprintf( '  тайл %d/%d: %s, всего объектов %d', $i + 1, count( $tiles ), $src, count( $elements ) ) );
		}

		usleep( 300000 );
	}

	return array(
		'elements' => array_values( $elements ),
		'errors'   => $errors,
		'tiles'    => count( $tiles ),
		'cached'   => $cached,
	);
}

/**
 * Сохранение выгрузки в JSON и CSV.
 *
 * @param array  $elements Объекты OSM.
 * @param string $out_dir  Каталог (пусто — uploads/bc-osm).
 * @param array  $meta     Метаданные (bbox, city, режим).
 * @return array{json:string,csv:string}
 */
function bc_osm_save_files( $elements, $out_dir, $meta ) {
	$dir  = '' !== $out_dir ? untrailingslashit( $out_dir ) : bc_osm_dir();
	$date = gmdate( 'Y-m-d' );

	if ( ! file_exists( $dir ) ) {
		wp_mkdir_p( $dir );
	}

	$json_path = $dir . '/osm-scan-' . $date . '.json';
	$csv_path  = $dir . '/osm-scan-' . $date . '.csv';

	$payload = array(
		'generated' => gmdate( 'c' ),
		'source'    => 'OpenStreetMap / Overpass API (ODbL)',
		'meta'      => $meta,
		'count'     => count( $elements ),
		'elements'  => $elements,
	);

	file_put_contents( $json_path, wp_json_encode( $payload, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT ) );

	$fh = fopen( $csv_path, 'wb' );
	if ( $fh ) {
		fwrite( $fh, "\xEF\xBB\xBF" ); // BOM для Excel.
		fputcsv( $fh, array( 'osm_id', 'name', 'category', 'lat', 'lng', 'street', 'house', 'phones', 'opening_hours', 'hours_ru', 'website', 'vk', 'telegram', 'whatsapp' ), ';' );
		foreach ( $elements as $el ) {
			fputcsv(
				$fh,
				array(
					$el['id'],
					$el['name'],
					$el['category'],
					$el['lat'] ?: '',
					$el['lng'] ?: '',
					$el['street'],
					$el['house'],
					implode( ', ', $el['phones'] ),
					$el['hours'],
					bc_osm_humanize_hours( $el['hours'] ),
					$el['website'],
					$el['vk'],
					$el['telegram'],
					$el['whatsapp'],
				),
				';'
			);
		}
		fclose( $fh );
	}

	update_option(
		BC_OSM_SCAN_OPTION,
		array(
			'ts'     => time(),
			'bbox'   => $meta['bbox'],
			'mode'   => $meta['mode'],
			'city'   => $meta['city'],
			'count'  => count( $elements ),
			'json'   => $json_path,
			'csv'    => $csv_path,
		),
		false
	);

	return array( 'json' => $json_path, 'csv' => $csv_path );
}


/**
 * Разбор --bbox=S,W,N,E.
 *
 * @param string $raw Строка.
 * @return array|null [s,w,n,e]
 */
function bc_osm_parse_bbox( $raw ) {
	$parts = array_map( 'trim', explode( ',', (string) $raw ) );
	if ( 4 !== count( $parts ) ) {
		return null;
	}
	$vals = array_map( 'floatval', $parts );
	if ( ! is_numeric( $parts[0] ) || ! is_numeric( $parts[3] ) ) {
		return null;
	}

	return array( $vals[0], $vals[1], $vals[2], $vals[3] );
}

/**
 * WP-CLI: wp bc-osm scan.
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_osm_scan_cli( $args, $assoc_args ) {
	$city    = isset( $assoc_args['city'] ) ? (string) $assoc_args['city'] : (string) get_option( 'bc_osm_city', 'Красноярск' );
	$mode    = isset( $assoc_args['mode'] ) ? (string) $assoc_args['mode'] : 'district';
	$tile    = isset( $assoc_args['tile'] ) ? (float) $assoc_args['tile'] : 0.012;
	$out     = isset( $assoc_args['out'] ) ? (string) $assoc_args['out'] : '';
	$import  = isset( $assoc_args['import'] ) ? (string) $assoc_args['import'] : '';
	$force   = isset( $assoc_args['force'] );
	$retries = isset( $assoc_args['retries'] ) ? (int) $assoc_args['retries'] : 4;
	$source  = isset( $assoc_args['source'] ) ? (string) $assoc_args['source'] : 'auto';

	if ( ! in_array( $source, array( 'auto', 'overpass', 'osmapi' ), true ) ) {
		$source = 'auto';
	}

	$bbox = null;
	if ( isset( $assoc_args['bbox'] ) ) {
		$bbox = bc_osm_parse_bbox( $assoc_args['bbox'] );
		if ( ! $bbox ) {
			WP_CLI::error( 'Не разобрал --bbox: нужен формат S,W,N,E (широта/долгота через запятую).' );
		}
	}

	if ( ! $bbox && 'city' === $mode ) {
		$bbox = bc_osm_city_bbox( $city );
		if ( ! $bbox ) {
			WP_CLI::error( 'Nominatim не отдал bbox для «' . $city . '». Проверьте сеть/название или передайте --bbox.' );
		}
	}

	if ( ! $bbox ) {
		$bbox = bc_osm_district_bbox();
		if ( $bbox ) {
			WP_CLI::log( 'Bbox микрорайона по координатам карточек (±0.012°). Для всего города: --mode=city.' );
		}
	}

	if ( ! $bbox ) {
		$bbox = bc_osm_city_bbox( $city );
		if ( ! $bbox ) {
			WP_CLI::error( 'Не удалось определить bbox: нет координат у организаций и Nominatim недоступен.' );
		}
		WP_CLI::log( 'Координат у карточек нет — взял bbox города через Nominatim.' );
	}

	WP_CLI::log( sprintf( 'bbox: %.5f,%.5f,%.5f,%.5f (%s)', $bbox[0], $bbox[1], $bbox[2], $bbox[3], $city ) );

	if ( '' !== $import ) {
		if ( ! file_exists( $import ) ) {
			WP_CLI::error( 'Файл не найден: ' . $import );
		}
		$raw  = (string) file_get_contents( $import );
		$data = json_decode( $raw, true );
		if ( ! is_array( $data ) ) {
			WP_CLI::error( 'Не JSON: ' . $import );
		}
		$elements = isset( $data['elements'] ) ? bc_osm_normalize_elements( $raw ) : array();
		if ( ! $elements && isset( $data['elements'] ) && isset( $data['elements'][0]['name'] ) ) {
			$elements = array_values( $data['elements'] ); // Уже нормализованная выгрузка.
		}
		WP_CLI::log( 'Импорт готового JSON: ' . count( $elements ) . ' объектов.' );
		$stats = array( 'tiles' => 0, 'cached' => 0, 'errors' => array() );
	} else {
		$collected = bc_osm_collect( $bbox, $tile, $force, $retries, $source );
		$elements  = $collected['elements'];
		$stats     = array( 'tiles' => $collected['tiles'], 'cached' => $collected['cached'], 'errors' => $collected['errors'] );
	}

	$files = bc_osm_save_files(
		$elements,
		$out,
		array(
			'bbox' => $bbox,
			'mode' => $mode,
			'city' => $city,
		)
	);

	$phones = 0;
	$hours  = 0;
	$hours_ru = 0;
	$sites  = 0;
	$coords = 0;
	$cats   = array();

	foreach ( $elements as $el ) {
		if ( $el['phones'] ) {
			$phones++;
		}
		if ( '' !== $el['hours'] ) {
			$hours++;
			if ( '' !== bc_osm_humanize_hours( $el['hours'] ) ) {
				$hours_ru++;
			}
		}
		if ( '' !== $el['website'] ) {
			$sites++;
		}
		if ( $el['lat'] && $el['lng'] ) {
			$coords++;
		}
		$cat = '' !== $el['category'] ? explode( '=', $el['category'] )[0] : 'без категории';
		$cats[ $cat ] = isset( $cats[ $cat ] ) ? $cats[ $cat ] + 1 : 1;
	}

	arsort( $cats );

	WP_CLI::log( '' );
	WP_CLI::log( sprintf( 'Объектов: %d | с телефоном: %d | с графиком: %d (переведено: %d) | с сайтом: %d | с координатами: %d', count( $elements ), $phones, $hours, $hours_ru, $sites, $coords ) );
	WP_CLI::log( sprintf( 'Тайлов: %d | из кэша: %d', (int) $stats['tiles'], (int) $stats['cached'] ) );

	$top = array();
	foreach ( array_slice( $cats, 0, 8, true ) as $k => $v ) {
		$top[] = $k . ': ' . $v;
	}
	if ( $top ) {
		WP_CLI::log( 'Категории (ключ тега): ' . implode( ', ', $top ) );
	}

	WP_CLI::log( 'JSON: ' . $files['json'] );
	WP_CLI::log( 'CSV:  ' . $files['csv'] );

	if ( $stats['errors'] ) {
		WP_CLI::warning( 'Часть тайлов не получена:' );
		foreach ( $stats['errors'] as $e ) {
			WP_CLI::log( '  ! ' . $e );
		}
	}

	WP_CLI::success( 'Скан OSM завершён.' );
}


/**
 * Карточки организаций с текущими значениями нужных полей.
 *
 * @param int $limit Ограничение (0 — все).
 * @return array[]
 */
function bc_osm_org_targets( $limit = 0 ) {
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

	$out = array();

	foreach ( $query->posts as $id ) {
		$lat = (float) get_post_meta( $id, 'bc_lat', true );
		$lng = (float) get_post_meta( $id, 'bc_lng', true );
		if ( ! bc_geocode_valid( $lat, $lng ) ) {
			$lat = 0.0;
			$lng = 0.0;
		}

		$out[] = array(
			'id'       => (int) $id,
			'title'    => get_the_title( $id ),
			'address'  => trim( (string) get_post_meta( $id, 'bc_address', true ) ),
			'lat'      => $lat,
			'lng'      => $lng,
			'phone'    => trim( (string) get_post_meta( $id, 'bc_phone', true ) ),
			'phones'   => trim( (string) get_post_meta( $id, 'bc_phones', true ) ),
			'schedule' => trim( (string) get_post_meta( $id, 'bc_schedule_text', true ) ),
			'website'  => trim( (string) get_post_meta( $id, 'bc_website', true ) ),
			'socials'  => trim( (string) get_post_meta( $id, 'bc_socials', true ) ),
			'vk'       => trim( (string) get_post_meta( $id, 'bc_vk', true ) ),
			'tg'       => trim( (string) get_post_meta( $id, 'bc_telegram', true ) ),
			'wa'       => trim( (string) get_post_meta( $id, 'bc_whatsapp', true ) ),
			'osm_id'   => trim( (string) get_post_meta( $id, 'bc_osm_id', true ) ),
		);
	}

	return $out;
}

/**
 * Загрузка последней выгрузки OSM (--file или путь из опции).
 *
 * @param string $file Явный путь к JSON.
 * @return array{elements:array,path:string,error:string}
 */
function bc_osm_load_scan( $file = '' ) {
	$opt  = get_option( BC_OSM_SCAN_OPTION, array() );
	$path = '' !== $file ? $file : ( isset( $opt['json'] ) ? (string) $opt['json'] : '' );

	if ( '' === $path || ! file_exists( $path ) ) {
		return array( 'elements' => array(), 'path' => $path, 'error' => 'Нет выгрузки OSM: сначала выполните wp bc-osm scan.' );
	}

	$raw  = (string) file_get_contents( $path );
	$data = json_decode( $raw, true );
	if ( ! is_array( $data ) || empty( $data['elements'] ) ) {
		return array( 'elements' => array(), 'path' => $path, 'error' => 'Выгрузка пуста: ' . $path );
	}

	$first = $data['elements'][0];
	$els   = isset( $first['phones'] ) ? array_values( $data['elements'] ) : bc_osm_normalize_elements( $raw );

	return array( 'elements' => $els, 'path' => $path, 'error' => '' );
}

/**
 * План дозаполнения одной карточки по данным OSM.
 *
 * @param array    $org       Карточка (см. bc_osm_org_targets).
 * @param array[]  $elements  Объекты OSM.
 * @param float    $threshold Порог совпадения.
 * @return array{match:?array,score:float,name:float,street:bool,dist:float,fields:array,conflicts:array}
 */
function bc_osm_build_plan( $org, $elements, $threshold ) {
	$street     = bc_osm_street_key( $org['address'] );
	$best       = null;
	$best_score = 0.0;
	$best_m     = array( 'name' => 0.0, 'street' => false, 'dist' => 0.0 );

	foreach ( $elements as $el ) {
		$m = bc_osm_match_score( $org, $el, $street );

		// Порог — по названию; улица/расстояние только уточняют порядок кандидатов.
		if ( $m['name'] < (float) $threshold || ! $m['strict'] ) {
			continue;
		}
		if ( $m['score'] > $best_score ) {
			$best_score = $m['score'];
			$best       = $el;
			$best_m     = $m;
		}
	}

	$plan = array(
		'match'     => null,
		'score'     => $best_score,
		'name'      => $best_m['name'],
		'street'    => $best_m['street'],
		'dist'      => $best_m['dist'],
		'fields'    => array(),
		'conflicts' => array(),
	);

	if ( ! $best ) {
		return $plan;
	}

	$plan['match'] = $best;
	$fields        = array();

	// Координаты.
	if ( ! $org['lat'] && $best['lat'] && $best['lng'] ) {
		$fields['bc_lat'] = (string) round( $best['lat'], 6 );
		$fields['bc_lng'] = (string) round( $best['lng'], 6 );
	}

	// Телефоны.
	if ( '' === $org['phone'] && $best['phones'] ) {
		$fields['bc_phone']  = $best['phones'][0];
		$fields['bc_phones'] = wp_json_encode( array_values( $best['phones'] ), JSON_UNESCAPED_UNICODE );
	} elseif ( '' !== $org['phone'] && $best['phones'] ) {
		$existing = bc_osm_split_phones( $org['phone'] );
		if ( $existing && $existing[0] !== $best['phones'][0] ) {
			$plan['conflicts'][] = 'телефон: в карточке ' . $org['phone'] . ', в OSM ' . $best['phones'][0];
		}
	}

	// График.
	$hours_ru = bc_osm_humanize_hours( $best['hours'] );
	if ( '' === $org['schedule'] && '' !== $hours_ru ) {
		$fields['bc_schedule_text'] = $hours_ru;
	} elseif ( '' !== $org['schedule'] && '' !== $hours_ru && $org['schedule'] !== $hours_ru ) {
		$plan['conflicts'][] = 'график: в карточке «' . $org['schedule'] . '», в OSM «' . $hours_ru . '»';
	}

	// Сайт.
	if ( '' === $org['website'] && '' !== $best['website'] ) {
		$fields['bc_website'] = $best['website'];
	} elseif ( '' !== $org['website'] && '' !== $best['website'] && $org['website'] !== $best['website'] ) {
		$plan['conflicts'][] = 'сайт: в карточке ' . $org['website'] . ', в OSM ' . $best['website'];
	}

	// Соцсети.
	$socials = array();
	foreach ( array( 'vk' => 'vk', 'telegram' => 'telegram', 'whatsapp' => 'whatsapp', 'instagram' => 'instagram' ) as $key => $meta_key ) {
		if ( '' !== $best[ $key ] ) {
			$socials[ $key ] = $best[ $key ];
		}
	}
	if ( $socials ) {
		if ( '' === $org['socials'] ) {
			$fields['bc_socials'] = wp_json_encode( $socials, JSON_UNESCAPED_UNICODE );
		}
		if ( '' === $org['vk'] && ! empty( $best['vk'] ) ) {
			$fields['bc_vk'] = $best['vk'];
		}
		if ( '' === $org['tg'] && ! empty( $best['telegram'] ) ) {
			$fields['bc_telegram'] = $best['telegram'];
		}
		if ( '' === $org['wa'] && ! empty( $best['whatsapp'] ) ) {
			$fields['bc_whatsapp'] = $best['whatsapp'];
		}
	}

	// OSM ID.
	if ( '' === $org['osm_id'] ) {
		$fields['bc_osm_id'] = $best['id'];
	}

	$plan['fields'] = $fields;

	return $plan;
}


/**
 * Добор координат через Nominatim (для карточек, оставшихся без точки).
 *
 * @param string $address Адрес карточки.
 * @param array  $cache   Кэш геокодера (по ссылке).
 * @param int    $calls   Счётчик запросов (по ссылке).
 * @return array|null ['lat','lng']
 */
function bc_osm_fallback_coords( $address, &$cache, &$calls ) {
	$q = bc_geocode_query( $address );
	if ( '' === $q ) {
		return null;
	}

	foreach ( bc_geocode_variants( $q ) as $vq ) {
		$key = 'nominatim|' . mb_strtolower( $vq );

		if ( isset( $cache[ $key ] ) ) {
			$res = $cache[ $key ];
		} else {
			if ( $calls > 0 ) {
				usleep( BC_GEOCODE_PAUSE );
			}
			$calls++;
			$res = bc_geocode_fetch( $vq, 'nominatim' );
			if ( isset( $res['error'] ) ) {
				continue;
			}
			$cache[ $key ] = $res;
		}

		if ( isset( $res['lat'], $res['lng'] ) ) {
			return $res;
		}
	}

	return null;
}

/**
 * WP-CLI: wp bc-osm enrich [--dry-run] [--limit=N] [--threshold=0.6] [--file=file].
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_osm_enrich_cli( $args, $assoc_args ) {
	$dry       = isset( $assoc_args['dry-run'] );
	$limit     = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
	$threshold = isset( $assoc_args['threshold'] ) ? (float) $assoc_args['threshold'] : 0.6;
	$file      = isset( $assoc_args['file'] ) ? (string) $assoc_args['file'] : '';

	$scan = bc_osm_load_scan( $file );
	if ( '' !== $scan['error'] ) {
		WP_CLI::warning( $scan['error'] );
	}

	$elements = $scan['elements'];
	$orgs     = bc_osm_org_targets( $limit );

	if ( ! $orgs ) {
		WP_CLI::error( 'Организаций не найдено.' );
	}

	WP_CLI::log( sprintf( 'Выгрузка OSM: %d объектов (%s)', count( $elements ), $scan['path'] ? $scan['path'] : '—' ) );
	WP_CLI::log( sprintf( 'Карточек: %d | порог совпадения: %.2f | режим: %s', count( $orgs ), $threshold, $dry ? 'dry-run' : 'запись' ) );
	WP_CLI::log( '' );

	$rows      = array();
	$filled    = 0;
	$missed    = array();
	$conflicts = array();
	$counts    = array(
		'bc_phone'         => 0,
		'bc_phones'        => 0,
		'bc_schedule_text' => 0,
		'bc_website'       => 0,
		'bc_socials'       => 0,
		'bc_osm_id'        => 0,
		'coords'           => 0,
	);

	$before = array(
		'coords'   => 0,
		'phone'    => 0,
		'schedule' => 0,
		'website'  => 0,
		'socials'  => 0,
		'osm_id'   => 0,
	);

	foreach ( $orgs as $o ) {
		if ( $o['lat'] && $o['lng'] ) {
			$before['coords']++;
		}
		if ( '' !== $o['phone'] ) {
			$before['phone']++;
		}
		if ( '' !== $o['schedule'] ) {
			$before['schedule']++;
		}
		if ( '' !== $o['website'] ) {
			$before['website']++;
		}
		if ( '' !== $o['socials'] ) {
			$before['socials']++;
		}
		if ( '' !== $o['osm_id'] ) {
			$before['osm_id']++;
		}
	}

	foreach ( $orgs as $org ) {
		$plan = bc_osm_build_plan( $org, $elements, $threshold );

		if ( ! $plan['match'] ) {
			$missed[] = array( 'org' => $org, 'score' => $plan['score'] );
			$rows[]   = array(
				'Название' => $org['title'],
				'OSM'      => '—',
				'Балл'     => number_format( $plan['score'], 2, '.', '' ),
				'+поля'    => '',
			);
			continue;
		}

		$added = array();
		foreach ( $plan['fields'] as $k => $v ) {
			$added[] = '+' . $k;
			if ( 'bc_lat' === $k ) {
				$counts['coords']++;
			} elseif ( isset( $counts[ $k ] ) ) {
				$counts[ $k ]++;
			}
		}

		if ( $plan['fields'] ) {
			$filled++;
			if ( ! $dry ) {
				foreach ( $plan['fields'] as $k => $v ) {
					update_post_meta( $org['id'], $k, $v );
				}
			}
		}

		foreach ( $plan['conflicts'] as $c ) {
			$conflicts[] = $org['title'] . ' — ' . $c;
		}

		$rows[] = array(
			'Название' => $org['title'],
			'OSM'      => $plan['match']['name'],
			'Балл'     => number_format( $plan['score'], 2, '.', '' ),
			'+поля'    => $added ? implode( ' ', $added ) : '—',
		);
	}

	WP_CLI\Utils\format_items( 'table', $rows, array( 'Название', 'OSM', 'Балл', '+поля' ) );


	/* Добор координат через Nominatim для оставшихся без точки. */
	$geo_cache = function_exists( 'bc_geocode_cache_get' ) ? bc_geocode_cache_get() : array();
	$geo_calls = 0;
	$geo_added = array();
	$geo_fail  = array();

	foreach ( $orgs as $org ) {
		if ( $org['lat'] && $org['lng'] ) {
			continue;
		}
		if ( '' === $org['address'] ) {
			continue;
		}

		$plan = bc_osm_build_plan( $org, $elements, $threshold );
		if ( $plan['match'] && isset( $plan['fields']['bc_lat'] ) ) {
			continue; // Точку даст OSM-совпадение.
		}

		$coord = bc_osm_fallback_coords( $org['address'], $geo_cache, $geo_calls );
		if ( $coord ) {
			$geo_added[] = $org['title'] . ' → ' . round( $coord['lat'], 6 ) . ', ' . round( $coord['lng'], 6 );
			if ( ! $dry ) {
				update_post_meta( $org['id'], 'bc_lat', (string) round( $coord['lat'], 6 ) );
				update_post_meta( $org['id'], 'bc_lng', (string) round( $coord['lng'], 6 ) );
			}
		} else {
			$geo_fail[] = $org['title'] . ' — ' . $org['address'];
		}
	}

	if ( ! $dry && $geo_calls > 0 && function_exists( 'bc_geocode_cache_set' ) ) {
		bc_geocode_cache_set( $geo_cache );
	}

	WP_CLI::log( '' );
	WP_CLI::log( sprintf( 'Дозаполнено карточек: %d из %d', $filled, count( $orgs ) ) );

	$after = $before;
	if ( $dry ) {
		$after['coords']   += $counts['coords'] + count( $geo_added );
		$after['phone']    += $counts['bc_phone'];
		$after['schedule'] += $counts['bc_schedule_text'];
		$after['website']  += $counts['bc_website'];
		$after['socials']  += $counts['bc_socials'];
		$after['osm_id']   += $counts['bc_osm_id'];
	} else {
		$after = array(
			'coords'   => 0,
			'phone'    => 0,
			'schedule' => 0,
			'website'  => 0,
			'socials'  => 0,
			'osm_id'   => 0,
		);
		foreach ( bc_osm_org_targets( $limit ) as $o ) {
			$after['coords']  += ( $o['lat'] && $o['lng'] ) ? 1 : 0;
			$after['phone']   += ( '' !== $o['phone'] ) ? 1 : 0;
			$after['schedule'] += ( '' !== $o['schedule'] ) ? 1 : 0;
			$after['website'] += ( '' !== $o['website'] ) ? 1 : 0;
			$after['socials'] += ( '' !== $o['socials'] ) ? 1 : 0;
			$after['osm_id']  += ( '' !== $o['osm_id'] ) ? 1 : 0;
		}
	}

	WP_CLI::log(
		sprintf(
			'Было → стало: телефоны %d → %d, координаты %d → %d, график %d → %d, сайт %d → %d, соцсети %d → %d, osm_id %d → %d%s',
			$before['phone'],
			$after['phone'],
			$before['coords'],
			$after['coords'],
			$before['schedule'],
			$after['schedule'],
			$before['website'],
			$after['website'],
			$before['socials'],
			$after['socials'],
			$before['osm_id'],
			$after['osm_id'],
			$dry ? ' (прогноз)' : ''
		)
	);
	WP_CLI::log(
		sprintf(
			'Поля: телефон +%d, телефоны(JSON) +%d, график +%d, сайт +%d, соцсети +%d, osm_id +%d, координаты +%d',
			$counts['bc_phone'],
			$counts['bc_phones'],
			$counts['bc_schedule_text'],
			$counts['bc_website'],
			$counts['bc_socials'],
			$counts['bc_osm_id'],
			$counts['coords']
		)
	);

	if ( $geo_added ) {
		WP_CLI::log( sprintf( 'Nominatim добрал координаты: %d', count( $geo_added ) ) );
		foreach ( $geo_added as $g ) {
			WP_CLI::log( '  + ' . $g );
		}
	}

	if ( $geo_fail ) {
		WP_CLI::log( 'Без координат (ручной разбор):' );
		foreach ( $geo_fail as $g ) {
			WP_CLI::log( '  — ' . $g );
		}
	}

	if ( $conflicts ) {
		WP_CLI::log( 'Расхождения с OSM (поля не менялись):' );
		foreach ( $conflicts as $c ) {
			WP_CLI::log( '  ! ' . $c );
		}
	}

	if ( $missed ) {
		WP_CLI::log( sprintf( 'Не найдено в OSM: %d', count( $missed ) ) );
		foreach ( $missed as $m ) {
			WP_CLI::log( '  — ' . $m['org']['title'] . ' (лучший балл ' . number_format( $m['score'], 2, '.', '' ) . ')' );
		}
	}

	WP_CLI::success( $dry ? 'Dry-run завершён (мета не изменялась).' : 'Дозаполнение завершено.' );
}

/**
 * Диспетчер команд wp bc-osm.
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_osm_cli( $args, $assoc_args ) {
	$sub = isset( $args[0] ) ? (string) $args[0] : '';

	if ( 'scan' === $sub ) {
		bc_osm_scan_cli( $args, $assoc_args );
		return;
	}
	if ( 'enrich' === $sub ) {
		bc_osm_enrich_cli( $args, $assoc_args );
		return;
	}

	WP_CLI::error( 'Использование: wp bc-osm scan [--mode=district|city] | wp bc-osm enrich [--dry-run]' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bc-osm', 'bc_osm_cli' );
}


