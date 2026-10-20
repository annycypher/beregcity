<?php
/**
 * PWA «БерегСити»: манифест, сервис-воркер и кнопка «Установить» в шапке.
 *
 * Ядро WordPress, тема Astra и сторонние плагины не редактируются (D1).
 * /bc-manifest.json и /bc-sw.js отдаются PHP-эндпоинтами: сервис-воркер из
 * подпапки темы не может получить scope «/» без заголовка Service-Worker-Allowed,
 * а манифесту нужен Content-Type application/manifest+json.
 *
 * @package Astra_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/** Версия PWA-набора: смена значения сбрасывает кэш сервис-воркера и rewrite-правила. */
const BC_PWA_VERSION     = '2026-10-20-icons';
/** Цвет бренда (мастер design/1026.png): theme_color/background_color и холст иконок. */
const BC_PWA_THEME_COLOR = '#F48671';
/** Версия файлов иконок: ?v=N в манифесте и <head> сбрасывает кэш браузеров. */
const BC_PWA_ASSET_V     = '2';

// Favicon-теги выводит bc_pwa_head() с точными размерами (ico/32/16/180),
// поэтому дубли ядра из «Значка сайта» не нужны (сам site_icon остаётся заданным).
remove_action( 'wp_head', 'wp_site_icon', 99 );

/**
 * Цвет бренда для meta theme-color и манифеста.
 *
 * @return string HEX.
 */
function bc_pwa_theme_color() {
	return BC_PWA_THEME_COLOR;
}

/**
 * URL файла из assets/pwa/.
 *
 * @param string $rel Имя файла, напр. 'icon-192.png'.
 * @return string
 */
function bc_pwa_asset_url( $rel ) {
	return get_stylesheet_directory_uri() . '/assets/pwa/' . ltrim( $rel, '/' );
}

/**
 * URL иконки с версией файла (?v=N) — сброс кэша при замене набора.
 *
 * @param string $rel Имя файла из assets/pwa/.
 * @return string
 */
function bc_pwa_icon_url( $rel ) {
	return bc_pwa_asset_url( $rel ) . '?v=' . BC_PWA_ASSET_V;
}

/**
 * Данные манифеста (иконки — абсолютные URL, как требует Chrome).
 *
 * @return array
 */
function bc_pwa_manifest() {
	return array(
		'name'             => 'БерегСити — Южный берег',
		'short_name'       => 'БерегСити',
		'description'      => 'Каталог организаций, новости и афиша микрорайона Южный берег, Красноярск',
		'id'               => '/',
		'start_url'        => '/',
		'scope'            => '/',
		'display'          => 'standalone',
		'display_override' => array( 'standalone', 'minimal-ui' ),
		'orientation'      => 'portrait',
		'theme_color'      => bc_pwa_theme_color(),
		'background_color' => bc_pwa_theme_color(),
		'lang'             => 'ru',
		'dir'              => 'ltr',
		'categories'       => array( 'lifestyle', 'news' ),
		'icons'            => array(
			array(
				'src'     => bc_pwa_icon_url( 'icon-192.png' ),
				'sizes'   => '192x192',
				'type'    => 'image/png',
				'purpose' => 'any',
			),
			array(
				'src'     => bc_pwa_icon_url( 'icon-512.png' ),
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'any',
			),
			array(
				'src'     => bc_pwa_icon_url( 'icon-192-maskable.png' ),
				'sizes'   => '192x192',
				'type'    => 'image/png',
				'purpose' => 'maskable',
			),
			array(
				'src'     => bc_pwa_icon_url( 'icon-512-maskable.png' ),
				'sizes'   => '512x512',
				'type'    => 'image/png',
				'purpose' => 'maskable',
			),
		),
	);
}

/**
 * Текст сервис-воркера: файл assets/pwa/sw.js с подстановкой версии и списка precache.
 *
 * @return string JavaScript.
 */
function bc_pwa_sw_source() {
	$file = get_stylesheet_directory() . '/assets/pwa/sw.js';
	if ( ! file_exists( $file ) ) {
		return '';
	}

	$precache = array(
		'/bc-manifest.json',
		wp_make_link_relative( bc_pwa_icon_url( 'icon-192.png' ) ),
		wp_make_link_relative( bc_pwa_icon_url( 'icon-512.png' ) ),
		wp_make_link_relative( bc_pwa_icon_url( 'icon-192-maskable.png' ) ),
		wp_make_link_relative( bc_pwa_icon_url( 'icon-512-maskable.png' ) ),
		wp_make_link_relative( bc_pwa_icon_url( 'icon-180.png' ) ),
	);

	return str_replace(
		array( '__BC_PWA_VERSION__', '__BC_PWA_PRECACHE__' ),
		array( BC_PWA_VERSION, wp_json_encode( $precache, JSON_UNESCAPED_SLASHES ) ),
		file_get_contents( $file ) // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- локальный файл темы.
	);
}

/**
 * Rewrite-правила эндпоинтов PWA.
 */
function bc_pwa_rewrites() {
	add_rewrite_rule( '^bc-manifest\.json$', 'index.php?bc_pwa=manifest', 'top' );
	add_rewrite_rule( '^bc-sw\.js$', 'index.php?bc_pwa=sw', 'top' );
}
add_action( 'init', 'bc_pwa_rewrites' );

/**
 * Query var эндпоинтов.
 *
 * @param string[] $vars Переменные запроса.
 * @return string[]
 */
function bc_pwa_query_vars( $vars ) {
	$vars[] = 'bc_pwa';
	return $vars;
}
add_filter( 'query_vars', 'bc_pwa_query_vars' );

/**
 * Сброс rewrite-правил при смене BC_PWA_VERSION (как в bc-core/includes/rewrite.php).
 */
function bc_pwa_maybe_flush() {
	if ( get_option( 'bc_pwa_version' ) === BC_PWA_VERSION ) {
		return;
	}
	flush_rewrite_rules( false );
	update_option( 'bc_pwa_version', BC_PWA_VERSION );
}
add_action( 'init', 'bc_pwa_maybe_flush', 99 );

/**
 * Отдача /bc-manifest.json и /bc-sw.js с нужными заголовками.
 */
function bc_pwa_serve() {
	$what = get_query_var( 'bc_pwa' );
	if ( ! $what ) {
		return;
	}

	if ( 'manifest' === $what ) {
		status_header( 200 );
		header( 'Content-Type: application/manifest+json; charset=utf-8' );
		header( 'Cache-Control: public, max-age=3600' );
		echo wp_json_encode(
			bc_pwa_manifest(),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_PRETTY_PRINT
		);
		exit;
	}

	if ( 'sw' === $what ) {
		status_header( 200 );
		header( 'Content-Type: application/javascript; charset=utf-8' );
		header( 'Service-Worker-Allowed: /' );
		header( 'Cache-Control: no-cache, no-store, must-revalidate' );
		echo bc_pwa_sw_source(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- JS-файл.
		exit;
	}
}
add_action( 'template_redirect', 'bc_pwa_serve', 0 );

/**
 * Теги PWA в <head>: манифест, apple-touch-icon, iOS-меты.
 * <meta name="theme-color"> уже выводит header.php из bc_pwa_theme_color().
 */
function bc_pwa_head() {
	printf( '<link rel="manifest" href="%s">' . "\n", esc_url( home_url( '/bc-manifest.json' ) ) );

	// Файловые фавиконы из корня сайта (мастер design/1026.png), ?v=2 — сброс кэша.
	$v = BC_PWA_ASSET_V;
	printf( '<link rel="icon" href="%s">' . "\n", esc_url( home_url( '/favicon.ico?v=' . $v ) ) );
	printf( '<link rel="icon" type="image/png" sizes="32x32" href="%s">' . "\n", esc_url( home_url( '/favicon-32.png?v=' . $v ) ) );
	printf( '<link rel="icon" type="image/png" sizes="16x16" href="%s">' . "\n", esc_url( home_url( '/favicon-16.png?v=' . $v ) ) );

	printf(
		'<link rel="apple-touch-icon" sizes="180x180" href="%s">' . "\n",
		esc_url( bc_pwa_icon_url( 'icon-180.png' ) )
	);
	printf( '<meta name="msapplication-TileColor" content="%s">' . "\n", esc_attr( bc_pwa_theme_color() ) );
	printf( '<meta name="msapplication-TileImage" content="%s">' . "\n", esc_url( bc_pwa_icon_url( 'icon-192.png' ) ) );
	echo '<meta name="mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-capable" content="yes">' . "\n";
	echo '<meta name="apple-mobile-web-app-title" content="БерегСити">' . "\n";
	echo '<meta name="apple-mobile-web-app-status-bar-style" content="default">' . "\n";
}
add_action( 'wp_head', 'bc_pwa_head', 5 );

/**
 * Стили и скрипт PWA. Скрипт — в <head>, чтобы не проспать beforeinstallprompt.
 */
function bc_pwa_assets() {
	$dir = get_stylesheet_directory();

	if ( file_exists( $dir . '/assets/css/pwa.css' ) ) {
		wp_enqueue_style(
			'bc-pwa',
			get_stylesheet_directory_uri() . '/assets/css/pwa.css',
			array( 'bc-theme' ),
			bc_asset_version( 'assets/css/pwa.css' )
		);
	}

	if ( ! file_exists( $dir . '/assets/js/pwa.js' ) ) {
		return;
	}

	wp_enqueue_script(
		'bc-pwa',
		get_stylesheet_directory_uri() . '/assets/js/pwa.js',
		array(),
		bc_asset_version( 'assets/js/pwa.js' ),
		false
	);

	wp_add_inline_script(
		'bc-pwa',
		'window.BC_PWA=' . wp_json_encode(
			array(
				'sw'     => wp_make_link_relative( home_url( '/bc-sw.js' ) ),
				'iosTip' => 'Safari: кнопка «Поделиться» → «На экран “Домой”»',
			),
			JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES
		) . ';',
		'before'
	);
}
add_action( 'wp_enqueue_scripts', 'bc_pwa_assets' );

/**
 * Кнопка «Установить» в шапке — вывод на хук bc_header_actions (см. header.php).
 * Скрыта в разметке; CSS показывает её только на ≤921px и при html.bc-pwa-can.
 */
function bc_pwa_header_button() {
	?>
	<div class="bc-pwa" id="bcPwa" hidden>
		<button type="button" class="btn bc-pwa-btn" id="bcPwaBtn" aria-label="Установить приложение на устройство">
			<svg class="ico bc-pwa-ico" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 3v11"/><path d="M7.5 10.5 12 15l4.5-4.5"/><path d="M4 17v2a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2v-2"/></svg>
			<span class="bc-pwa-t">Установить</span>
		</button>
		<div class="bc-pwa-tip" id="bcPwaTip" role="status" aria-live="polite" hidden>Safari: кнопка «Поделиться» → «На экран “Домой”»</div>
	</div>
	<?php
}
add_action( 'bc_header_actions', 'bc_pwa_header_button' );

