<?php
/**
 * Автостатус графика организации (Этап 3.2а, §19 cabinet.md).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Статус организации по графику (на лету).
 *
 * Приоритет: особые дни > обычное расписание > «сегодня закрыто».
 * Таймзона — настройка сайта (current_time учитывает её).
 *
 * @param int $post_id ID организации.
 * @return array{open:bool, text:string}
 */
function bc_schedule_status( $post_id ) {
	$default = array( 'open' => false, 'text' => '' );

	if ( ! function_exists( 'get_field' ) ) {
		return $default;
	}

	$exceptions = get_field( 'field_bc_exceptions', $post_id );
	$schedule   = get_field( 'field_bc_schedule', $post_id );

	$now_day  = (int) current_time( 'N' ); // 1 (Пн) … 7 (Вс)
	$now_time = current_time( 'H:i' );
	$now_date = current_time( 'Y-m-d' );

	// 1. Особые дни — приоритет над обычным расписанием.
	if ( is_array( $exceptions ) ) {
		foreach ( $exceptions as $ex ) {
			if ( ! empty( $ex['date'] ) && $ex['date'] === $now_date ) {
				$note = ! empty( $ex['note'] ) ? $ex['note'] : 'Особый график';
				return array( 'open' => false, 'text' => $note );
			}
		}
	}

	// 2. Обычное расписание на сегодня.
	$today = null;
	if ( is_array( $schedule ) ) {
		foreach ( $schedule as $row ) {
			if ( isset( $row['day'] ) && (int) $row['day'] === $now_day ) {
				$today = $row;
				break;
			}
		}
	}

	if ( ! $today || empty( $today['time_from'] ) || empty( $today['time_to'] ) ) {
		return array( 'open' => false, 'text' => 'Сегодня закрыто' );
	}

	$from = date( 'H:i', strtotime( $today['time_from'] ) );
	$to   = date( 'H:i', strtotime( $today['time_to'] ) );

	if ( $now_time >= $from && $now_time < $to ) {
		return array( 'open' => true, 'text' => 'Сейчас открыто · до ' . $to );
	}

	if ( $now_time < $from ) {
		return array( 'open' => false, 'text' => 'Откроется в ' . $from );
	}

	return array( 'open' => false, 'text' => 'Закрыто' );
}
