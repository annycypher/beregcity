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
 * Форма логотипа «БерегСити» (знак + волна) — из design/favicon.svg (форма владельца).
 * Возвращает разметку путей в системе координат viewBox «0 0 5.78 5.03».
 */
function bc_logo_paths() {
	return "<path d='M0.45 0l1 0 0 1.44 0.67 0 0 -0.4c0,-0.3 0.13,-0.55 0.35,-0.74 0.22,-0.2 0.49,-0.3 0.81,-0.3l1.16 0c0.32,0 0.59,0.1 0.81,0.3 0.22,0.19 0.34,0.44 0.34,0.74l0 1.82c0,0.3 -0.12,0.54 -0.34,0.74 -0.22,0.2 -0.49,0.3 -0.81,0.3l-0.83 0c-0.12,-0.12 -0.45,-0.44 -0.61,-0.54 -0.26,-0.16 -0.52,-0.25 -0.77,-0.28 -0.07,-0.13 -0.11,-0.27 -0.11,-0.43l0 -0.2 -0.67 0 0 0.71c-0.38,0.1 -0.72,0.31 -1,0.53l0 0 0 -3.69zm3.77 1l0 0 -0.71 0 0 0c-0.21,0.01 -0.39,0.19 -0.39,0.41l0 1.08c0,0.22 0.19,0.41 0.42,0.41l0.64 0c0.23,0 0.41,-0.19 0.41,-0.41l0 -1.08c0,-0.21 -0.16,-0.39 -0.37,-0.41z'/>"
		. "<path d='M0 4.47c0.17,-0.02 0.48,-0.23 0.72,-0.3 1.05,-0.34 1.62,0.13 2.27,0.53 1.24,0.75 2.61,0.08 2.79,-0.58 -0.53,0.22 -0.94,0.51 -1.71,0.24 -0.64,-0.21 -0.81,-0.55 -1.26,-0.81 -1.31,-0.77 -2.75,0.6 -2.81,0.92z'/>";
}

/**
 * Знак логотипа для шапки/подвала: форма белым внутри CSS-круга `.mark`.
 *
 * @param int $size Размер знака в px.
 * @return string HTML.
 */
function bc_logo_mark( $size = 22 ) {
	$size = (int) $size;
	return "<svg width='" . $size . "' height='" . $size . "' viewBox='0 0 5.78 5.03' class='ico' style='stroke:none;fill:#fff' aria-hidden='true'>"
		. bc_logo_paths() . '</svg>';
}

/**
 * Favicon — inline SVG (Этап 2.5-б): форма логотипа из design/favicon.svg
 * в бренд-стиле (терракотовый градиент + белый знак). Выводится через wp_head.
 * Если владелец задаст WP Site Icon — не дублируем.
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
		. "<g transform='translate(19.65 23.6) scale(10.5)' fill='white'>"
		. bc_logo_paths()
		. '</g>'
		. '</svg>';

	printf( '<link rel="icon" href="%s">' . "\n", esc_attr( 'data:image/svg+xml,' . $svg ) );
}
add_action( 'wp_head', 'bc_favicon_svg' );
