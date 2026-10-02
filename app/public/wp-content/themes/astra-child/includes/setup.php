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
			'bc_primary' => 'БерегСити: меню шапки',
			'bc_mobile'  => 'БерегСити: меню в бургере',
		)
	);
}
add_action( 'after_setup_theme', 'bc_child_setup' );

require_once get_stylesheet_directory() . '/includes/nav-walker.php';

/**
 * Favicon — inline SVG «волна» (Этап 2.5-б), взят из design/homepage.html.
 * Выводится через wp_head. Если владелец задаст WP Site Icon — не дублируем.
 */
function bc_favicon_svg() {
	if ( function_exists( 'has_site_icon' ) && has_site_icon() ) {
		return;
	}

	$svg = "<svg xmlns='http://www.w3.org/2000/svg' viewBox='0 0 100 100'>"
		. "<defs><linearGradient id='g' x1='0' y1='0' x2='1' y2='1'>"
		. "<stop offset='0' stop-color='%23dda38b'/><stop offset='1' stop-color='%23c48067'/>"
		. '</linearGradient></defs>'
		. "<circle cx='50' cy='50' r='48' fill='url(%23g)'/>"
		. "<path d='M18 60c10-12 20-12 30 0s22 12 34 0' stroke='white' stroke-width='8' fill='none' stroke-linecap='round'/>"
		. '</svg>';

	printf( '<link rel="icon" href="%s">' . "\n", esc_attr( 'data:image/svg+xml,' . $svg ) );
}
add_action( 'wp_head', 'bc_favicon_svg' );
