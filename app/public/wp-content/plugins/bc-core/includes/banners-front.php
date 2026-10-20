<?php
/**
 * Вывод баннеров (Этап 6.1): [bc_banner zone] + hero-слайды слайдера,
 * счётчики показов/кликов и /go/banner/{id}.
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

/**
 * Баннер ещё в периоде показа.
 *
 * @param int $id ID баннера.
 * @return bool
 */
function bc_banner_is_active( $id ) {
$until = (string) get_field( 'banner_until', $id );
return ( '' === $until || $until >= current_time( 'Y-m-d' ) );
}

/**
 * URL видео из ACF-поля типа file (может вернуться строкой или массивом).
 *
 * @param mixed $value Значение поля.
 * @return string
 */
function bc_banner_video_url( $value ) {
	if ( is_array( $value ) ) {
		return isset( $value['url'] ) ? (string) $value['url'] : '';
	}

	return trim( (string) $value );
}

/**
 * ID баннеров зоны hero для слайдера главной.
 *
 * @return int[]
 */
function bc_banner_heroes() {
$query = new WP_Query(
array(
'post_type'      => 'banners',
'post_status'    => 'publish',
'posts_per_page' => -1,
'fields'         => 'ids',
'no_found_rows'  => true,
'orderby'        => array( 'menu_order' => 'ASC', 'date' => 'DESC' ),
'meta_query'     => array(
'relation' => 'AND',
array(
'key'   => 'banner_zone',
'value' => 'hero',
),
array(
'key'   => 'banner_show',
'value' => '1',
),
),
)
);

$ids = array();
foreach ( $query->posts as $id ) {
$id    = (int) $id;
$video = bc_banner_video_url( get_field( 'banner_video', $id ) );

if ( '' === $video ) {
$video = bc_banner_video_url( get_field( 'banner_video_mobile', $id ) );
}

// Слайд возможен с картинкой-постером либо с видео (картинка желательна, но не обязательна).
if ( bc_banner_is_active( $id ) && ( get_field( 'banner_image', $id ) || '' !== $video ) ) {
$ids[] = $id;
}
}

return $ids;
}

/**
 * Слайд слайдера из баннера.
 *
 * Мобильные картинка/видео/текст — необязательные: если пусто, работает
 * десктопный вариант (никаких пустых баннеров).
 *
 * @param int  $id    ID баннера.
 * @param bool $track Считать ли показ.
 * @param int  $index Позиция в слайдере (0 — первый: картинку грузим сразу, это LCP).
 * @return string HTML.
 */
function bc_banner_slide( $id, $track = true, $index = 0 ) {
$image     = (int) get_field( 'banner_image', $id );
$image_mob = (int) get_field( 'banner_image_mobile', $id );
$video     = bc_banner_video_url( get_field( 'banner_video', $id ) );
$video_mob = bc_banner_video_url( get_field( 'banner_video_mobile', $id ) );
$title     = trim( (string) get_field( 'banner_title', $id ) );
$text      = trim( (string) get_field( 'banner_text', $id ) );
$text_mob  = trim( (string) get_field( 'banner_text_mobile', $id ) );
$btn_text  = trim( (string) get_field( 'banner_button_text', $id ) );
$ad        = (bool) get_field( 'banner_ad', $id );

if ( '' === $btn_text ) {
$btn_text = 'Подробнее';
}

$alt = $title ? $title : get_the_title( $id );
$url = home_url( '/go/banner/' . $id . '/' );

if ( $track ) {
bc_banner_track_show( $id );
}

$first      = ( 0 === (int) $index );
$classes    = 'slide bc-slide';
$has_video  = ( '' !== $video || '' !== $video_mob );
$poster     = $image ? (string) wp_get_attachment_image_url( $image, 'full' ) : '';
$poster_mob = $image_mob ? (string) wp_get_attachment_image_url( $image_mob, 'full' ) : '';

if ( '' !== $text_mob ) {
$classes .= ' bc-slide--has-mob';
}
if ( $has_video ) {
$classes .= ' bc-slide--video';
}


ob_start();
?>
<div class="<?php echo esc_attr( $classes ); ?>" data-banner="<?php echo (int) $id; ?>">
<?php if ( $ad ) : ?>
<span class="tag-ad">Реклама</span>
<?php endif; ?>
<picture class="bc-slide-media">
<?php if ( $image_mob ) : ?>
<source media="(max-width:767px)" srcset="<?php echo esc_url( (string) wp_get_attachment_image_url( $image_mob, 'full' ) ); ?>">
<?php endif; ?>
<?php
if ( $image ) {
echo wp_get_attachment_image(
$image,
'full',
false,
array(
'class'         => 'slide-img',
'alt'           => $alt,
'loading'       => $first ? 'eager' : 'lazy',
'fetchpriority' => $first ? 'high' : 'auto',
)
);
}
?>
</picture>
<?php if ( $has_video ) : ?>
<?php
$bc_video_attrs = array(
'class="bc-slide-video"',
'muted',
'loop',
'playsinline',
'autoplay',
'preload="none"',
);

if ( '' !== $poster ) {
$bc_video_attrs[] = 'poster="' . esc_url( $poster ) . '"';
}

$bc_video_attrs[] = 'data-src="' . esc_url( '' !== $video ? $video : $video_mob ) . '"';
$bc_video_attrs[] = 'data-src-mob="' . esc_url( '' !== $video_mob ? $video_mob : $video ) . '"';

if ( '' !== $poster_mob ) {
$bc_video_attrs[] = 'data-poster-mob="' . esc_url( $poster_mob ) . '"';
}

$bc_video_attrs[] = 'aria-hidden="true"';
$bc_video_attrs[] = 'tabindex="-1"';
?>
<video <?php echo implode( ' ', $bc_video_attrs ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- атрибуты экранированы выше. ?>></video>
<?php endif; ?>
<span class="bc-slide-shade" aria-hidden="true"></span>
<div class="bc-slide-body">
<?php if ( '' !== $title ) : ?>
<h2 class="bc-slide-title"><?php echo esc_html( $title ); ?></h2>
<?php endif; ?>
<?php if ( '' !== $text ) : ?>
<p class="bc-slide-text"><?php echo wp_kses_post( nl2br( esc_html( $text ) ) ); ?></p>
<?php endif; ?>
<?php if ( '' !== $text_mob ) : ?>
<p class="bc-slide-text bc-slide-text--mob"><?php echo wp_kses_post( nl2br( esc_html( $text_mob ) ) ); ?></p>
<?php endif; ?>
<a class="btn btn-terra bc-slide-btn" href="<?php echo esc_url( $url ); ?>" rel="nofollow noopener"><?php echo esc_html( $btn_text ); ?></a>
</div>
</div>
<?php
return (string) ob_get_clean();
}

/**
 * Стили баннеров — только там, где они реально нужны.
 */
function bc_banner_maybe_enqueue() {
$need = is_front_page();

if ( ! $need && is_singular() ) {
$post = get_post();
$need = $post && ( has_shortcode( (string) $post->post_content, 'bc_slider' ) || has_shortcode( (string) $post->post_content, 'bc_banner' ) );
}

if ( ! $need ) {
return;
}

$dir = plugin_dir_path( __FILE__ ) . '../assets';

if ( file_exists( $dir . '/js/banners.js' ) ) {
wp_enqueue_script(
'bc-banners',
plugins_url( 'assets/js/banners.js', dirname( __FILE__ ) ),
array(),
filemtime( $dir . '/js/banners.js' ),
true
);
}

if ( file_exists( $dir . '/css/banners.css' ) ) {
wp_enqueue_style(
'bc-banners',
plugins_url( 'assets/css/banners.css', dirname( __FILE__ ) ),
array( 'bc-theme' ),
filemtime( $dir . '/css/banners.css' )
);
}
}
add_action( 'wp_enqueue_scripts', 'bc_banner_maybe_enqueue' );

function bc_banner_shortcode( $atts ) {
$atts = shortcode_atts( array( 'zone' => 'wide' ), $atts, 'bc_banner' );
$zone = sanitize_key( $atts['zone'] );

if ( 'hero' === $zone ) {
$heroes = bc_banner_heroes();
return $heroes ? bc_banner_slide( $heroes[0] ) : '';
}

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