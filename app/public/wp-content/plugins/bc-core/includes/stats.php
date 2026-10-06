<?php
/**
 * Статистика ЛК (Этап 3а.6): просмотры (cookie-дедуп/сутки) + клики, витрина 7/30.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_track_view( $org_id ) {
	$today  = current_time( 'Y-m-d' );
	$cookie = 'bc_v_' . $org_id;
	if ( isset( $_COOKIE[ $cookie ] ) && $_COOKIE[ $cookie ] === $today ) {
		return; // дедуп по cookie/сутки
	}
	$key = 'bc_views_' . $today;
	$n   = (int) get_post_meta( $org_id, $key, true );
	update_post_meta( $org_id, $key, $n + 1 );
	setcookie( $cookie, $today, time() + DAY_IN_SECONDS, COOKIEPATH, COOKIE_DOMAIN );
}

function bc_track_click( $org_id, $type ) {
	$today = current_time( 'Y-m-d' );
	$key   = 'bc_click_' . $type . '_' . $today;
	$n     = (int) get_post_meta( $org_id, $key, true );
	update_post_meta( $org_id, $key, $n + 1 );
}

/**
 * Данные за N дней (просмотры + клики) для витрины.
 */
function bc_stats( $org_id, $days = 14 ) {
	$out = array();
	for ( $i = $days - 1; $i >= 0; $i-- ) {
		$date = date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' -' . $i . ' days' ) );
		$out[] = array(
			'date'   => $date,
			'views'  => (int) get_post_meta( $org_id, 'bc_views_' . $date, true ),
			'clicks' => (int) get_post_meta( $org_id, 'bc_click_phone_' . $date, true )
				+ (int) get_post_meta( $org_id, 'bc_click_site_' . $date, true )
				+ (int) get_post_meta( $org_id, 'bc_click_whatsapp_' . $date, true )
				+ (int) get_post_meta( $org_id, 'bc_click_telegram_' . $date, true ),
		);
	}
	return $out;
}

function bc_stats_totals( $org_id, $days ) {
	$rows  = bc_stats( $org_id, $days );
	$views = 0;
	$clicks = 0;
	foreach ( $rows as $r ) {
		$views  += $r['views'];
		$clicks += $r['clicks'];
	}
	return array( 'views' => $views, 'clicks' => $clicks );
}

/**
 * Редирект-эндпоинт кликов /go/{org_id}/{type}/.
 */
function bc_go_rewrite() {
	add_rewrite_rule( '^go/([0-9]+)/([a-z]+)/?$', 'index.php?bc_go=$matches[1]&bc_go_type=$matches[2]', 'top' );
}
add_action( 'init', 'bc_go_rewrite' );

function bc_go_query_vars( $vars ) {
	$vars[] = 'bc_go';
	$vars[] = 'bc_go_type';
	return $vars;
}
add_filter( 'query_vars', 'bc_go_query_vars' );

function bc_go_redirect() {
	$org_id = (int) get_query_var( 'bc_go' );
	if ( ! $org_id ) {
		return;
	}
	$type = sanitize_key( get_query_var( 'bc_go_type' ) );
	bc_track_click( $org_id, $type );

	$target = '';
	if ( 'phone' === $type ) {
		$target = 'tel:' . preg_replace( '/[^0-9+]/', '', (string) get_field( 'field_bc_phone', $org_id ) );
	} elseif ( 'site' === $type ) {
		$target = get_field( 'field_bc_website', $org_id );
	} elseif ( 'whatsapp' === $type ) {
		$target = get_field( 'field_bc_whatsapp', $org_id );
	} elseif ( 'telegram' === $type ) {
		$target = get_field( 'field_bc_telegram', $org_id );
	}
	wp_safe_redirect( $target ? $target : get_permalink( $org_id ) );
	exit;
}
add_action( 'template_redirect', 'bc_go_redirect' );
