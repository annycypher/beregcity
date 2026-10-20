<?php
/**
 * Рекламные позиции сайта (Этап 6.1+): слоты по позициям из CPT «Баннеры».
 *
 * Позиция выключена или пуста → НИЧЕГО не выводится (ни рамок, ни заглушек).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Список позиций: код => подпись + рекомендованный размер.
 *
 * @return array[]
 */
function bc_ad_positions() {
return array(
'hero'            => array( 'label' => 'Главный баннер (слайдер)', 'size' => 'десктоп 1920×740, мобильный 800×800' ),
'under_header'    => array( 'label' => 'Под шапкой', 'size' => '970×90 (на телефоне сжимается по ширине)' ),
'after_hero'      => array( 'label' => 'После главного баннера', 'size' => '1200×200 (на телефоне — по ширине)' ),
'after_sections'  => array( 'label' => 'Под блоками разделов', 'size' => '970×120 (на телефоне — по ширине)' ),
'home_end'        => array( 'label' => 'В конце главной', 'size' => '1200×160 (на телефоне — по ширине)' ),
'catalog_sidebar' => array( 'label' => 'Сайдбар каталога', 'size' => '300×600 или 300×250' ),
'org_bottom'      => array( 'label' => 'Низ карточки организации', 'size' => '970×120 (на телефоне — по ширине)' ),
'map'             => array( 'label' => 'Баннер карты', 'size' => 'десктоп 1200×300, мобильный 720×280' ),
);
}

/**
 * Один включённый баннер позиции.
 *
 * @param string $pos Код позиции.
 * @return int ID баннера или 0.
 */
function bc_ad_find( $pos ) {
$pos = sanitize_key( $pos );
if ( '' === $pos ) {
return 0;
}

$query = new WP_Query(
array(
'post_type'      => 'banners',
'post_status'    => 'publish',
'posts_per_page' => 1,
'no_found_rows'  => true,
'fields'         => 'ids',
'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
'meta_query'     => array(
'relation' => 'AND',
array(
'key'   => 'banner_zone',
'value' => $pos,
),
array(
'key'   => 'banner_show',
'value' => '1',
),
),
)
);

foreach ( $query->posts as $id ) {
if ( bc_banner_is_active( (int) $id ) ) {
return (int) $id;
}
}

return 0;
}

/**
 * Внутренняя разметка баннера: картинка или HTML/код рекламной сети.
 *
 * @param int $id ID баннера.
 * @return string
 */
function bc_ad_inner( $id ) {
$image = (int) get_field( 'banner_image', $id );
$html  = trim( (string) get_field( 'banner_html', $id ) );

if ( $image ) {
return (string) wp_get_attachment_image(
$image,
'full',
false,
array(
'class'   => 'bc-ad__img',
'alt'     => get_the_title( $id ),
'loading' => 'lazy',
)
);
}

// HTML/код вводит администратор сайта — выводим как есть.
return $html;
}

/**
 * Слот позиции. Пустой/выключенный баннер → пустая строка.
 *
 * @param string $pos Код позиции.
 * @return string HTML.
 */
function bc_ad_slot( $pos ) {
$id = bc_ad_find( $pos );
if ( ! $id ) {
return '';
}

$inner = (string) bc_ad_inner( $id );
if ( '' === trim( $inner ) ) {
return '';
}

bc_banner_track_show( $id );

$link = (string) get_field( 'banner_link', $id );
$wrap = '<div class="bc-ad bc-ad--' . esc_attr( sanitize_key( $pos ) ) . '" data-ad="' . (int) $id . '">';

if ( '' !== $link ) {
$wrap .= '<a class="bc-ad__link" href="' . esc_url( home_url( '/go/banner/' . $id . '/' ) ) . '" rel="nofollow noopener">' . $inner . '</a>';
} else {
$wrap .= $inner;
}

return $wrap . '</div>';
}

/**
 * Стили слотов — только на страницах, где они есть.
 */
function bc_ad_enqueue_assets() {
$need = is_front_page() || is_post_type_archive( 'organizations' ) || is_tax( 'bc_cat' ) || is_singular( 'organizations' );
if ( ! $need ) {
return;
}

$dir = plugin_dir_path( __FILE__ ) . '../assets';
if ( file_exists( $dir . '/css/ads.css' ) ) {
wp_enqueue_style(
'bc-ads',
plugins_url( 'assets/css/ads.css', dirname( __FILE__ ) ),
array( 'bc-theme' ),
filemtime( $dir . '/css/ads.css' )
);
}
}
add_action( 'wp_enqueue_scripts', 'bc_ad_enqueue_assets' );