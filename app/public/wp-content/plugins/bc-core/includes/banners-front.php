<?php
/**
 * Вывод баннеров (Этап 6.1): [bc_banner zone] + счётчики показов/кликов + /go/banner/{id}.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_banner_track_show( $id ) {
	$today = current_time( 'Y-m-d' );
	$key   = 'bc_banner_views_' . $today;
	update_post_meta( $id, $key, (int) get_post_meta( $id, $key, true ) + 1 );
}

function bc_banner_track_click( $id ) {
	$today = current_time( 'Y-m-d' );
	$key   = 'bc_banner_clicks_' . $today;
	update_post_meta( $id, $key, (int) get_post_meta( $id, $key, true ) + 1 );
}

function bc_banner_shortcode( $atts ) {
	$atts = shortcode_atts( array( 'zone' => 'wide' ), $atts, 'bc_banner' );
	$zone = sanitize_key( $atts['zone'] );

	$q = new WP_Query(
		array(
			'post_type'      => 'banners',
			'post_status'    => 'publish',
			'posts_per_page' => 1,
			'meta_key'       => 'banner_zone',
			'meta_value'     => $zone,
		)
	);
	if ( ! $q->have_posts() ) {
		return '';
	}
	$q->the_post();
	$id    = get_the_ID();
	$until = get_field( 'banner_until', $id );
	if ( $until && $until < current_time( 'Y-m-d' ) ) {
		wp_reset_postdata();
		return '';
	}
	$image = get_field( 'banner_image', $id );
	$img   = $image ? wp_get_attachment_image( (int) $image, 'full' ) : '';
	bc_banner_track_show( $id );
	$url = home_url( '/go/banner/' . $id . '/' );

	ob_start();
	if ( 'wide' === $zone ) {
		echo '<div class="ad ad-wide"><a href="' . esc_url( $url ) . '" rel="nofollow noopener">' . $img . '</a></div>';
	} elseif ( 'duo' === $zone ) {
		echo '<div class="ad"><a href="' . esc_url( $url ) . '" rel="nofollow noopener">' . $img . '</a></div>';
	} else {
		echo '<div class="ad"><a href="' . esc_url( $url ) . '" rel="nofollow noopener">' . $img . '</a></div>';
	}
	wp_reset_postdata();
	return ob_get_clean();
}
add_shortcode( 'bc_banner', 'bc_banner_shortcode' );

function bc_banner_go_rewrite() {
	add_rewrite_rule( '^go/banner/([0-9]+)/?$', 'index.php?bc_banner_go=$matches[1]', 'top' );
}
add_action( 'init', 'bc_banner_go_rewrite' );

function bc_banner_go_query_var( $vars ) {
	$vars[] = 'bc_banner_go';
	return $vars;
}
add_filter( 'query_vars', 'bc_banner_go_query_var' );

function bc_banner_go_redirect() {
	$id = (int) get_query_var( 'bc_banner_go' );
	if ( ! $id || 'banners' !== get_post_type( $id ) ) {
		return;
	}
	bc_banner_track_click( $id );
	$link = get_field( 'banner_link', $id );
	wp_safe_redirect( $link ? $link : home_url( '/' ) );
	exit;
}
add_action( 'template_redirect', 'bc_banner_go_redirect' );
