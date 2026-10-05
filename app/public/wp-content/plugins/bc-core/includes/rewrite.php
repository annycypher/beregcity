<?php
/**
 * Программатик-SEO страницы /katalog/{cat}/{feature}/ (Этап 3.3, D14).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Регистрируем query var для снипета.
 */
function bc_register_feature_query_var( $vars ) {
	$vars[] = 'bc_feature';
	return $vars;
}
add_filter( 'query_vars', 'bc_register_feature_query_var' );

/**
 * Правило /katalog/{категория}/{снипет}/ (не трогаем /katalog/organization/{slug}/).
 */
function bc_add_feature_rewrite() {
	add_rewrite_rule(
		'^katalog/(?!organization/)([^/]+)/([^/]+)/?$',
		'index.php?bc_cat=$matches[1]&bc_feature=$matches[2]',
		'top'
	);
}
add_action( 'init', 'bc_add_feature_rewrite' );

/**
 * Ворота D14: страница /katalog/{cat}/{feature}/ существует при ≥3 организациях.
 */
function bc_feature_gate() {
	if ( ! is_tax( 'bc_cat' ) ) {
		return;
	}
	$feature = get_query_var( 'bc_feature' );
	if ( ! $feature ) {
		return;
	}

	$feature_term = get_term_by( 'name', $feature, 'features' );
	if ( ! $feature_term || is_wp_error( $feature_term ) ) {
		$feature_term = get_term_by( 'slug', rawurldecode( $feature ), 'features' );
	}
	if ( ! $feature_term || is_wp_error( $feature_term ) ) {
		bc_set_404();
		return;
	}

	$cat = get_queried_object();
	if ( ! is_object( $cat ) || empty( $cat->term_id ) ) {
		return;
	}

	$q = new WP_Query(
		array(
			'post_type'      => 'organizations',
			'post_status'    => 'publish',
			'posts_per_page' => -1,
			'fields'         => 'ids',
			'tax_query'      => array(
				'relation' => 'AND',
				array( 'taxonomy' => 'bc_cat', 'field' => 'term_id', 'terms' => $cat->term_id ),
				array( 'taxonomy' => 'features', 'field' => 'term_id', 'terms' => $feature_term->term_id ),
			),
		)
	);

	if ( $q->found_posts < 3 ) {
		bc_set_404();
	}
}
add_action( 'template_redirect', 'bc_feature_gate' );

/**
 * 404 без редиректа.
 */
function bc_set_404() {
	global $wp_query;
	$wp_query->set_404();
	status_header( 404 );
	nocache_headers();
}
