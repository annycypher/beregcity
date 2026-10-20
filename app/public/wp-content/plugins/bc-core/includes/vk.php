<?php
/**
 * ВКонтакте: дозаполнение телефонов и ссылок на сообщества (WP-CLI).
 *
 * Команда: wp bc-vk enrich [--dry-run] [--limit=N] [--threshold=0.6] [--city=Красноярск]
 *          wp bc-vk test
 *
 * Токен: константа BC_VK_TOKEN в wp-config.php (сервисный ключ) или опция bc_vk_token.
 * Методы: groups.search, groups.getById (fields=contacts,place,members_count).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

const BC_VK_CACHE_OPTION = 'bc_vk_cache';
const BC_VK_API_VERSION   = '5.199';
const BC_VK_PAUSE_US      = 700000; // 0,7 с между запросами (лимит 3 req/s).

/**
 * Токен ВКонтакте.
 *
 * @return string
 */
function bc_vk_token() {
	$token = defined( 'BC_VK_TOKEN' ) ? trim( (string) BC_VK_TOKEN ) : '';
	if ( '' === $token ) {
		$token = trim( (string) get_option( 'bc_vk_token', '' ) );
	}

	return $token;
}

/**
 * Метод VK API.
 *
 * @param string $method Метод (groups.search).
 * @param array  $params Параметры.
 * @return array{ok:bool,data:mixed,error:string}
 */
function bc_vk_api( $method, $params ) {
	$token = bc_vk_token();
	if ( '' === $token ) {
		return array( 'ok' => false, 'data' => null, 'error' => 'не задан BC_VK_TOKEN' );
	}

	$cache = get_option( BC_VK_CACHE_OPTION, array() );
	$key   = md5( $method . '|' . wp_json_encode( $params ) );

	if ( isset( $cache[ $key ] ) ) {
		return $cache[ $key ];
	}

	$body = array_merge(
		$params,
		array(
			'access_token' => $token,
			'v'            => BC_VK_API_VERSION,
			'lang'         => 'ru',
		)
	);

	$res = wp_remote_post(
		'https://api.vk.com/method/' . $method,
		array(
			'timeout' => 20,
			'body'    => $body,
			'headers' => array( 'User-Agent' => BC_OSM_UA ),
		)
	);

	if ( is_wp_error( $res ) ) {
		return array( 'ok' => false, 'data' => null, 'error' => $res->get_error_message() );
	}

	$code = (int) wp_remote_retrieve_response_code( $res );
	$data = json_decode( (string) wp_remote_retrieve_body( $res ), true );

	if ( 200 !== $code || ! is_array( $data ) ) {
		return array( 'ok' => false, 'data' => null, 'error' => 'HTTP ' . $code );
	}

	if ( isset( $data['error'] ) ) {
		$msg = isset( $data['error']['error_msg'] ) ? $data['error']['error_msg'] : 'ошибка VK API';
		$no  = isset( $data['error']['error_code'] ) ? (int) $data['error']['error_code'] : 0;
		return array( 'ok' => false, 'data' => null, 'error' => $msg . ' (код ' . $no . ')' );
	}

	$result = array( 'ok' => true, 'data' => isset( $data['response'] ) ? $data['response'] : null, 'error' => '' );

	$cache[ $key ] = $result;
	if ( count( $cache ) > 2000 ) {
		$cache = array_slice( $cache, -1500, null, true );
	}
	update_option( BC_VK_CACHE_OPTION, $cache, false );

	return $result;
}


/**
 * Поиск сообщества по названию организации.
 *
 * @param array  $org       Карточка (bc_osm_org_targets).
 * @param string $city      Город.
 * @param float  $threshold Порог.
 * @return array|null ['group'=>array,'score'=>float]
 */
function bc_vk_find_group( $org, $city, $threshold ) {
	$search = bc_vk_api(
		'groups.search',
		array(
			'q'      => $org['title'] . ' ' . $city,
			'type'   => 'group',
			'count'  => 20,
			'fields' => 'contacts,place,members_count',
		)
	);

	if ( ! $search['ok'] || empty( $search['data']['items'] ) ) {
		return null;
	}

	usleep( BC_VK_PAUSE_US );

	$ids = array();
	foreach ( $search['data']['items'] as $item ) {
		if ( ! empty( $item['id'] ) ) {
			$ids[] = (int) $item['id'];
		}
	}
	if ( ! $ids ) {
		return null;
	}

	$details = bc_vk_api(
		'groups.getById',
		array(
			'group_ids' => implode( ',', array_slice( $ids, 0, 20 ) ),
			'fields'    => 'contacts,place,members_count,activity',
		)
	);

	if ( ! $details['ok'] || empty( $details['data']['groups'] ) ) {
		return null;
	}

	usleep( BC_VK_PAUSE_US );

	$best       = null;
	$best_score = 0.0;

	foreach ( $details['data']['groups'] as $group ) {
		$score = bc_osm_name_score( $org['title'], (string) $group['name'] );

		$place = isset( $group['place']['title'] ) ? (string) $group['place']['title'] : '';
		if ( '' !== $place && false !== mb_stripos( $place, $city ) ) {
			$score += 0.1;
		}

		if ( $score > $best_score ) {
			$best_score = $score;
			$best       = $group;
		}
	}

	if ( ! $best || $best_score < $threshold ) {
		return null;
	}

	return array( 'group' => $best, 'score' => round( $best_score, 3 ) );
}

/**
 * Телефоны из блока contacts сообщества.
 *
 * @param array $group Сообщество VK.
 * @return string[]
 */
function bc_vk_group_phones( $group ) {
	$phones = array();

	if ( ! empty( $group['contacts']['phone'] ) ) {
		$phones = array_merge( $phones, bc_osm_split_phones( $group['contacts']['phone'] ) );
	}
	if ( ! empty( $group['contacts']['mobile_phone'] ) ) {
		$phones = array_merge( $phones, bc_osm_split_phones( $group['contacts']['mobile_phone'] ) );
	}

	return array_values( array_unique( $phones ) );
}


/**
 * WP-CLI: wp bc-vk enrich [--dry-run] [--limit=N] [--threshold=0.6] [--city=Красноярск].
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_vk_enrich_cli( $args, $assoc_args ) {
	if ( '' === bc_vk_token() ) {
		WP_CLI::warning( 'VK-токен не задан: добавьте define( \'BC_VK_TOKEN\', \'…\' ) в wp-config.php. Этап 3 пропущен.' );
		return;
	}

	$dry       = isset( $assoc_args['dry-run'] );
	$limit     = isset( $assoc_args['limit'] ) ? (int) $assoc_args['limit'] : 0;
	$threshold = isset( $assoc_args['threshold'] ) ? (float) $assoc_args['threshold'] : 0.6;
	$city      = isset( $assoc_args['city'] ) ? (string) $assoc_args['city'] : (string) get_option( 'bc_osm_city', 'Красноярск' );

	$orgs    = bc_osm_org_targets( $limit );
	$no_phone = array();

	foreach ( $orgs as $org ) {
		if ( '' === $org['phone'] ) {
			$no_phone[] = $org;
		}
	}

	WP_CLI::log( sprintf( 'Карточек: %d | без телефона: %d | город: %s | режим: %s', count( $orgs ), count( $no_phone ), $city, $dry ? 'dry-run' : 'запись' ) );

	$rows   = array();
	$filled = 0;
	$missed = array();

	foreach ( $no_phone as $org ) {
		$found = bc_vk_find_group( $org, $city, $threshold );

		if ( ! $found ) {
			$missed[] = $org['title'];
			continue;
		}

		$group  = $found['group'];
		$phones = bc_vk_group_phones( $group );
		$url    = ! empty( $group['screen_name'] ) ? 'https://vk.com/' . $group['screen_name'] : ( ! empty( $group['id'] ) ? 'https://vk.com/club' . (int) $group['id'] : '' );

		$fields = array();
		if ( $phones ) {
			$fields['bc_phone']  = $phones[0];
			$fields['bc_phones'] = wp_json_encode( array_values( $phones ), JSON_UNESCAPED_UNICODE );
		}

		$socials = array();
		if ( '' !== $org['socials'] ) {
			$decoded = json_decode( $org['socials'], true );
			$socials = is_array( $decoded ) ? $decoded : array();
		}
		if ( '' !== $url && empty( $socials['vk'] ) ) {
			$socials['vk']        = $url;
			$fields['bc_socials'] = wp_json_encode( $socials, JSON_UNESCAPED_UNICODE );
		}
		if ( '' === $org['vk'] && '' !== $url ) {
			$fields['bc_vk'] = $url;
		}

		if ( $fields ) {
			$filled++;
			if ( ! $dry ) {
				foreach ( $fields as $k => $v ) {
					update_post_meta( $org['id'], $k, $v );
				}
			}
		}

		$rows[] = array(
			'Название'   => $org['title'],
			'Сообщество' => $group['name'],
			'Балл'       => number_format( $found['score'], 2, '.', '' ),
			'+поля'      => $fields ? implode( ' ', array_keys( $fields ) ) : '—',
		);
	}

	if ( $rows ) {
		WP_CLI\Utils\format_items( 'table', $rows, array( 'Название', 'Сообщество', 'Балл', '+поля' ) );
	}

	WP_CLI::log( '' );
	WP_CLI::log( sprintf( 'Дозаполнено: %d | без совпадений: %d', $filled, count( $missed ) ) );
	foreach ( $missed as $m ) {
		WP_CLI::log( '  — ' . $m );
	}

	WP_CLI::success( $dry ? 'Dry-run завершён (мета не изменялась).' : 'VK-дозаполнение завершено.' );
}

/**
 * Проверка токена: wp bc-vk test.
 */
function bc_vk_test_cli() {
	if ( '' === bc_vk_token() ) {
		WP_CLI::error( 'BC_VK_TOKEN не задан (wp-config.php) и опция bc_vk_token пуста.' );
	}

	$res = bc_vk_api( 'groups.search', array( 'q' => 'детский центр Красноярск', 'type' => 'group', 'count' => 3 ) );
	if ( ! $res['ok'] ) {
		WP_CLI::error( 'VK API: ' . $res['error'] );
	}

	$names = array();
	foreach ( (array) $res['data']['items'] as $item ) {
		$names[] = $item['name'];
	}
	WP_CLI::success( 'Токен рабочий. Пример выдачи: ' . implode( ' | ', $names ) );
}

/**
 * Диспетчер команд wp bc-vk.
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_vk_cli( $args, $assoc_args ) {
	$sub = isset( $args[0] ) ? (string) $args[0] : '';

	if ( 'enrich' === $sub ) {
		bc_vk_enrich_cli( $args, $assoc_args );
		return;
	}
	if ( 'test' === $sub ) {
		bc_vk_test_cli();
		return;
	}

	WP_CLI::error( 'Использование: wp bc-vk enrich [--dry-run] | wp bc-vk test' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bc-vk', 'bc_vk_cli' );
}
