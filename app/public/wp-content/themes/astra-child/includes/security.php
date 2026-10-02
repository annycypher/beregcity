<?php
/**
 * Безопасность проекта «БерегСити» (Этап 1б, D19).
 *
 * Правки ядра WordPress и сторонних плагинов запрещены (D1) — вся логика здесь.
 *
 * @package Astra_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/*
 * 1. XML-RPC отключён (D19).
 */
add_filter( 'xmlrpc_enabled', '__return_false' );

add_filter(
	'wp_headers',
	function ( $headers ) {
		unset( $headers['X-Pingback'] );
		return $headers;
	}
);

/** Полностью закрыть файл xmlrpc.php (403) — «XML-RPC off» (D19). */
add_action(
	'init',
	function () {
		$uri  = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = trim( (string) parse_url( $uri, PHP_URL_PATH ), '/' );
		if ( 'xmlrpc.php' === $path ) {
			status_header( 403 );
			nocache_headers();
			exit( 'Forbidden' );
		}
	},
	0
);

/*
 * 2. Убрать из <head> генератор версии, RSD, WLW и короткие ссылки.
 */
add_action(
	'init',
	function () {
		remove_action( 'wp_head', 'wp_generator' );
		remove_action( 'wp_head', 'rsd_link' );
		remove_action( 'wp_head', 'wlwmanifest_link' );
		remove_action( 'wp_head', 'wp_shortlink_wp_head' );
	}
);

/*
 * 3. Закрыть REST-эндпоинты пользователей для гостей (users-endpoint, D19).
 */
add_filter(
	'rest_endpoints',
	function ( $endpoints ) {
		if ( is_user_logged_in() ) {
			return $endpoints;
		}
		foreach ( array_keys( $endpoints ) as $route ) {
			if ( 0 === strpos( $route, '/wp/v2/users' ) ) {
				unset( $endpoints[ $route ] );
			}
		}
		return $endpoints;
	},
	999
);

/*
 * 4. Запретить перечисление авторов (?author=N) для гостей.
 */
add_action(
	'template_redirect',
	function () {
		if ( ! is_admin() && ! is_user_logged_in() && isset( $_GET['author'] ) ) {
			wp_safe_redirect( home_url( '/' ), 301 );
			exit;
		}
	}
);

/*
 * 5. Переименование wp-login.php (слаг задаётся константой BC_LOGIN_SLUG в wp-config.php).
 *    Если константа не задана — поведение WordPress по умолчанию.
 */
if ( defined( 'BC_LOGIN_SLUG' ) && BC_LOGIN_SLUG ) {

	add_filter(
		'site_url',
		function ( $url, $path ) {
			if ( 'wp-login.php' === $path ) {
				$url = str_replace( 'wp-login.php', BC_LOGIN_SLUG, $url );
			}
			return $url;
		},
		10,
		2
	);

	add_filter(
		'network_site_url',
		function ( $url, $path ) {
			if ( 'wp-login.php' === $path ) {
				$url = str_replace( 'wp-login.php', BC_LOGIN_SLUG, $url );
			}
			return $url;
		},
		10,
		2
	);

	add_filter(
		'wp_redirect',
		function ( $location ) {
			if ( false !== strpos( $location, 'wp-login.php' ) && false === strpos( $location, BC_LOGIN_SLUG ) ) {
				$location = str_replace( 'wp-login.php', BC_LOGIN_SLUG, $location );
			}
			return $location;
		}
	);

	add_action(
		'init',
		function () {
			$slug   = BC_LOGIN_SLUG;
			$uri    = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
			$path   = trim( (string) parse_url( $uri, PHP_URL_PATH ), '/' );
			$method = isset( $_SERVER['REQUEST_METHOD'] ) ? $_SERVER['REQUEST_METHOD'] : 'GET';

			// Запрос к новому адресу входа — отдаём стандартную форму wp-login.php.
			if ( '' !== $path && ( $path === $slug || 0 === strpos( $path, $slug . '/' ) ) ) {
				$_GET[ $slug ] = 1;
				require_once ABSPATH . 'wp-login.php';
				exit;
			}

			// Прямое обращение к wp-login.php для гостей (кроме POST-обработки формы) — запрет.
			if ( ! is_user_logged_in() && false !== strpos( $path, 'wp-login.php' ) && 'POST' !== $method ) {
				status_header( 403 );
				nocache_headers();
				exit( 'Forbidden' );
			}
		},
		1
	);
}
