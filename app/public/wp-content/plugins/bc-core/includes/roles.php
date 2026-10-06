<?php
/**
 * Роль org_manager (D26, Этап 3а.1): без wp-admin, админ-бар скрыт.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Роль менеджера организации (создаётся при активации/init).
 */
function bc_add_org_manager_role() {
	add_role(
		'org_manager',
		'Менеджер организации',
		array(
			'read'         => true,
			'upload_files' => true,
			'edit_posts'   => true, // map_meta_cap: edit_post своей организации
		)
	);
}
add_action( 'init', 'bc_add_org_manager_role' );

/**
 * Скрыть админ-бар для org_manager.
 */
function bc_hide_admin_bar_for_org_manager( $show ) {
	$user = wp_get_current_user();
	if ( $user && in_array( 'org_manager', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
		return false;
	}
	return $show;
}
add_filter( 'show_admin_bar', 'bc_hide_admin_bar_for_org_manager' );

/**
 * org_manager никогда не видит wp-admin (кроме admin-ajax).
 */
function bc_block_org_manager_admin() {
	if ( is_admin() && ! wp_doing_ajax() ) {
		$user = wp_get_current_user();
		if ( in_array( 'org_manager', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
			wp_safe_redirect( home_url( '/kabinet/' ) );
			exit;
		}
	}
}
add_action( 'admin_init', 'bc_block_org_manager_admin' );
