<?php
/**
 * Базовая настройка дочерней темы «БерегСити» (Этап 2.1).
 *
 * @package Astra_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Поддержки темы и область меню.
 */
function bc_child_setup() {
	add_theme_support( 'title-tag' );
	add_theme_support( 'post-thumbnails' );
	add_theme_support(
		'html5',
		array( 'search-form', 'gallery', 'caption', 'style', 'script' )
	);

	// Область меню под будущий шаг 2.5 (перевод шапки на wp_nav_menu).
	register_nav_menus(
		array(
			'bc_primary' => 'БерегСити: главное меню (шапка)',
		)
	);
}
add_action( 'after_setup_theme', 'bc_child_setup' );
