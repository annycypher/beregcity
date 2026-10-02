<?php
/**
 * Подключение стилей и скриптов темы (Этап 2.1).
 *
 * Кешбастинг — filemtime(): версия ассета = время изменения файла (ПРОТОКОЛ).
 *
 * @package Astra_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Версия ассета по времени изменения файла.
 *
 * @param string $rel Путь относительно каталога темы, напр. 'assets/css/theme.css'.
 * @return int|null Метка версии или null, если файла ещё нет.
 */
function bc_asset_version( $rel ) {
	$file = get_stylesheet_directory() . '/' . ltrim( $rel, '/' );
	return file_exists( $file ) ? filemtime( $file ) : null;
}

/**
 * Стили темы.
 */
function bc_enqueue_styles() {
	$rel  = 'assets/css/theme.css';
	$file = get_stylesheet_directory() . '/' . $rel;
	if ( ! file_exists( $file ) ) {
		return; // Ассет ещё не создан — не выводим 404-ссылку.
	}
	wp_enqueue_style(
		'bc-theme',
		get_stylesheet_directory_uri() . '/' . $rel,
		array(),
		bc_asset_version( $rel )
	);
}
add_action( 'wp_enqueue_scripts', 'bc_enqueue_styles' );

/**
 * Скрипты темы (в подвал страницы).
 */
function bc_enqueue_scripts() {
	$rel  = 'assets/js/theme.js';
	$file = get_stylesheet_directory() . '/' . $rel;
	if ( ! file_exists( $file ) ) {
		return; // Ассет ещё не создан — не выводим 404-ссылку.
	}
	wp_enqueue_script(
		'bc-theme',
		get_stylesheet_directory_uri() . '/' . $rel,
		array(),
		bc_asset_version( $rel ),
		true
	);
}
add_action( 'wp_enqueue_scripts', 'bc_enqueue_scripts' );
