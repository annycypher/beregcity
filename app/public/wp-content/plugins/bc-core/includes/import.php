<?php
/**
 * Импорт витринных карточек из CSV (D41, шаг «D10-импортер»).
 *
 * Только WP-CLI: `wp bc-import file=organizations.csv [--dry-run]`.
 * Логика вынесена в bc_import_run() (тестируется отдельно).
 * Организации создаются в статусе pending и проходят утверждение (D24).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Нормализация телефона: только цифры и ведущий «+».
 */
function bc_import_norm_phone( $phone ) {
	$s = preg_replace( '/[^0-9+]/', '', (string) $phone );
	$s = str_replace( '+', '', $s );
	return $s ? '+' . $s : '';
}

/**
 * Чтение CSV: UTF-8, разделитель «;», BOM-толерантно, первая строка — заголовок.
 *
 * @return array { rows: array, error: string }
 */
function bc_import_read_csv( $path ) {
	if ( ! is_file( $path ) || ! is_readable( $path ) ) {
		return array( 'rows' => array(), 'error' => 'Файл не найден или недоступен: ' . $path );
	}
	$raw = file_get_contents( $path );
	if ( false === $raw ) {
		return array( 'rows' => array(), 'error' => 'Не удалось прочитать файл.' );
	}
	$raw   = preg_replace( '/^\xEF\xBB\xBF/', '', $raw );
	$lines = preg_split( '/\r\n|\n|\r/', $raw );
	$rows  = array();
	foreach ( $lines as $ln ) {
		if ( '' === trim( $ln ) ) {
			continue;
		}
		$rows[] = str_getcsv( $ln, ';' );
	}
	if ( count( $rows ) < 2 ) {
		return array( 'rows' => array(), 'error' => 'CSV пуст или нет строк данных (нужна строка-заголовок).' );
	}
	$header = array_map( 'trim', $rows[0] );
	$out    = array();
	foreach ( array_slice( $rows, 1 ) as $r ) {
		$item = array();
		foreach ( $header as $ci => $col ) {
			$item[ $col ] = isset( $r[ $ci ] ) ? trim( (string) $r[ $ci ] ) : '';
		}
		$out[] = $item;
	}
	return array( 'rows' => $out, 'error' => '' );
}

/**
 * Карта терминов таксономии для сверки (ключ — имя/slug в нижнем регистре).
 */
function bc_import_term_map( $taxonomy ) {
	$map   = array();
	$terms = get_terms( array( 'taxonomy' => $taxonomy, 'hide_empty' => false ) );
	if ( is_wp_error( $terms ) ) {
		return $map;
	}
	foreach ( $terms as $t ) {
		$map[ mb_strtolower( $t->name ) ] = (int) $t->term_id;
		$map[ mb_strtolower( $t->slug ) ] = (int) $t->term_id;
	}
	return $map;
}

/**
 * Поиск дубликата в БД: по нормализованному телефону ИЛИ title+address.
 *
 * @return string Пусто — не дубликат; иначе причина.
 */
function bc_import_find_duplicate( $phone, $title, $address ) {
	global $wpdb;
	$digits = preg_replace( '/[^0-9]/', '', (string) $phone );
	if ( strlen( $digits ) >= 5 ) {
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'bc_phone'
				 WHERE p.post_type = 'organizations'
				   AND p.post_status IN ('publish','pending','draft')
				   AND REGEXP_REPLACE( pm.meta_value, '[^0-9]', '' ) = %s
				 LIMIT 1",
				$digits
			)
		);
		if ( $id ) {
			return 'дубликат по телефону';
		}
	}
	if ( '' !== $title && '' !== $address ) {
		$id = $wpdb->get_var(
			$wpdb->prepare(
				"SELECT p.ID FROM {$wpdb->posts} p
				 INNER JOIN {$wpdb->postmeta} pm ON pm.post_id = p.ID AND pm.meta_key = 'bc_address'
				 WHERE p.post_type = 'organizations'
				   AND p.post_status IN ('publish','pending','draft')
				   AND p.post_title = %s AND pm.meta_value = %s
				 LIMIT 1",
				$title,
				$address
			)
		);
		if ( $id ) {
			return 'дубликат по названию и адресу';
		}
	}
	return '';
}

/**
 * Импорт: читает CSV и (если не dry-run) создаёт карточки. Возвращает отчёт.
 * Дедупликация — по телефону/title+address, в том числе внутри файла.
 *
 * @param string $path    Путь к CSV.
 * @param bool   $dry_run Только отчёт, без записи.
 * @return array
 */
function bc_import_run( $path, $dry_run = false ) {
	$report = array(
		'total'      => 0,
		'created'    => 0,
		'duplicates' => array(),
		'errors'     => array(),
		'warnings'   => array(),
	);

	$csv = bc_import_read_csv( $path );
	if ( '' !== $csv['error'] ) {
		$report['errors'][] = array( 'line' => 0, 'reason' => $csv['error'] );
		return $report;
	}

	$cats  = bc_import_term_map( 'bc_cat' );
	$feats = bc_import_term_map( 'features' );

	$admins   = get_users( array( 'role' => 'administrator', 'number' => 1, 'orderby' => 'ID' ) );
	$admin_id = $admins ? (int) $admins[0]->ID : 1;

	$seen_phones = array();
	$seen_ta     = array();

	foreach ( $csv['rows'] as $idx => $row ) {
		$line = $idx + 2;
		$report['total']++;

		$title    = isset( $row['title'] ) ? $row['title'] : '';
		$category = isset( $row['category'] ) ? $row['category'] : '';
		$address  = isset( $row['address'] ) ? $row['address'] : '';
		$phone    = isset( $row['phone'] ) ? $row['phone'] : '';

		if ( '' === $title || '' === $category || '' === $address || '' === $phone ) {
			$report['errors'][] = array( 'line' => $line, 'reason' => 'обязательные поля title/category/address/phone' );
			continue;
		}

		$cat_key = mb_strtolower( $category );
		if ( ! isset( $cats[ $cat_key ] ) ) {
			$report['errors'][] = array( 'line' => $line, 'reason' => 'неизвестная категория: ' . $category );
			continue;
		}

		$phone_norm = bc_import_norm_phone( $phone );
		if ( strlen( preg_replace( '/[^0-9]/', '', $phone_norm ) ) < 5 ) {
			$report['errors'][] = array( 'line' => $line, 'reason' => 'некорректный телефон: ' . $phone );
			continue;
		}

		$entry = array(
			'title'    => $title,
			'address'  => $address,
			'phone'    => $phone_norm,
			'website'  => isset( $row['website'] ) ? $row['website'] : '',
			'lat'      => isset( $row['lat'] ) ? $row['lat'] : '',
			'lng'      => isset( $row['lng'] ) ? $row['lng'] : '',
			'excerpt'  => isset( $row['excerpt'] ) ? $row['excerpt'] : '',
			'schedule' => isset( $row['schedule_text'] ) ? $row['schedule_text'] : '',
			'cat_id'   => (int) $cats[ $cat_key ],
			'feat_ids' => array(),
		);

		if ( ! empty( $row['features'] ) ) {
			foreach ( explode( ',', $row['features'] ) as $fn ) {
				$fn = trim( $fn );
				if ( '' === $fn ) {
					continue;
				}
				$fk = mb_strtolower( $fn );
				if ( isset( $feats[ $fk ] ) ) {
					$entry['feat_ids'][] = (int) $feats[ $fk ];
				} else {
					$report['warnings'][] = array( 'line' => $line, 'reason' => 'неизвестный снипет, пропущен: ' . $fn );
				}
			}
			$entry['feat_ids'] = array_values( array_unique( $entry['feat_ids'] ) );
		}

		if ( '' !== $entry['lat'] || '' !== $entry['lng'] ) {
			$lf = (float) str_replace( ',', '.', $entry['lat'] );
			$nf = (float) str_replace( ',', '.', $entry['lng'] );
			if ( ! ( $lf >= 55.5 && $lf <= 56.5 && $nf >= 92.3 && $nf <= 93.3 ) ) {
				$report['warnings'][] = array( 'line' => $line, 'reason' => 'проверить координаты: ' . $entry['lat'] . ',' . $entry['lng'] );
			}
		}

		$digits = preg_replace( '/[^0-9]/', '', $phone_norm );
		$ta_key = mb_strtolower( $title . '|' . $address );
		if ( $digits && isset( $seen_phones[ $digits ] ) ) {
			$dup = 'дубликат по телефону (в файле)';
		} elseif ( isset( $seen_ta[ $ta_key ] ) ) {
			$dup = 'дубликат по названию и адресу (в файле)';
		} else {
			$dup = bc_import_find_duplicate( $phone_norm, $title, $address );
		}
		if ( '' !== $dup ) {
			$report['duplicates'][] = array( 'line' => $line, 'reason' => $dup . ': ' . $title );
			continue;
		}
		$seen_phones[ $digits ] = 1;
		$seen_ta[ $ta_key ]     = 1;

		if ( $dry_run ) {
			$report['created']++;
			continue;
		}

		$post_id = wp_insert_post(
			array(
				'post_type'    => 'organizations',
				'post_status'  => 'pending',
				'post_title'   => $title,
				'post_author'  => $admin_id,
				'post_content' => '',
			),
			true
		);
		if ( is_wp_error( $post_id ) ) {
			$report['errors'][] = array( 'line' => $line, 'reason' => 'не удалось создать запись: ' . $post_id->get_error_message() );
			continue;
		}

		update_field( 'field_bc_phone', $entry['phone'], $post_id );
		update_field( 'field_bc_address', $entry['address'], $post_id );
		update_field( 'field_bc_website', $entry['website'], $post_id );
		if ( '' !== $entry['lat'] ) {
			update_field( 'field_bc_lat', str_replace( ',', '.', $entry['lat'] ), $post_id );
		}
		if ( '' !== $entry['lng'] ) {
			update_field( 'field_bc_lng', str_replace( ',', '.', $entry['lng'] ), $post_id );
		}
		if ( '' !== $entry['excerpt'] ) {
			update_field( 'field_bc_excerpt', mb_substr( $entry['excerpt'], 0, 300 ), $post_id );
		}
		update_field( 'field_bc_is_verified', 0, $post_id );
		if ( '' !== $entry['schedule'] ) {
			update_post_meta( $post_id, 'bc_schedule_text', $entry['schedule'] );
		}
		wp_set_object_terms( $post_id, array( $entry['cat_id'] ), 'bc_cat', false );
		if ( $entry['feat_ids'] ) {
			wp_set_object_terms( $post_id, $entry['feat_ids'], 'features', false );
		}
		$report['created']++;
	}

	return $report;
}

/**
 * WP-CLI-обёртка: wp bc-import file=organizations.csv [--dry-run].
 */
function bc_import_cli( $args, $assoc ) {
	$file = isset( $assoc['file'] ) ? (string) $assoc['file'] : '';
	if ( '' === $file ) {
		WP_CLI::error( 'Укажите файл: wp bc-import file=organizations.csv [--dry-run]' );
	}
	$dry = isset( $assoc['dry-run'] );
	$r   = bc_import_run( $file, $dry );
	if ( isset( $r['errors'][0] ) && 0 === $r['errors'][0]['line'] ) {
		WP_CLI::error( $r['errors'][0]['reason'] );
	}
	WP_CLI::log( sprintf( 'Всего строк данных: %d', $r['total'] ) );
	WP_CLI::log( sprintf( '%s: %d', $dry ? 'Будет создано (dry-run)' : 'Создано', $r['created'] ) );
	WP_CLI::log( sprintf( 'Дубликаты (пропущены): %d', count( $r['duplicates'] ) ) );
	WP_CLI::log( sprintf( 'Ошибки (пропущены): %d', count( $r['errors'] ) ) );
	WP_CLI::log( sprintf( 'Предупреждения: %d', count( $r['warnings'] ) ) );
	foreach ( $r['errors'] as $e ) {
		WP_CLI::log( sprintf( '  ошибка — строка %d: %s', $e['line'], $e['reason'] ) );
	}
	foreach ( $r['duplicates'] as $e ) {
		WP_CLI::log( sprintf( '  дубликат — строка %d: %s', $e['line'], $e['reason'] ) );
	}
	foreach ( $r['warnings'] as $e ) {
		WP_CLI::log( sprintf( '  предупреждение — строка %d: %s', $e['line'], $e['reason'] ) );
	}
	WP_CLI::success( $dry ? 'Dry-run завершён (записи в БД не было).' : 'Импорт завершён. Карточки — в очереди утверждения (pending).' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
	WP_CLI::add_command( 'bc-import', 'bc_import_cli' );
}