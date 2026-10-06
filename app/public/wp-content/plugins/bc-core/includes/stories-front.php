<?php
/**
 * Вывод сторис (Этап 5.2): шорткоды [bc_stories]/[bc_stories org] + данные STORIES.
 *
 * Данные в форме stories.md §36: STORIES[{id, title, slides:[{img, caption, link, btn, ad}]}].
 * Вьюер (overlay/viewer) создаёт assets/js/stories.js.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Данные сторий.
 *
 * @param int $org_id 0 — редакция; иначе — стории организации (post_author).
 * @return array
 */
function bc_stories_data( $org_id = 0 ) {
	$args = array(
		'post_type'      => 'stories',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'meta_key'       => 'sort_order',
		'orderby'        => 'meta_value_num',
		'order'          => 'ASC',
	);
	if ( $org_id ) {
		$args['author'] = (int) $org_id;
	}

	$out = array();
	$q   = new WP_Query( $args );
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$until = get_field( 'show_until', get_the_ID() );
			if ( $until && $until < current_time( 'Y-m-d' ) ) {
				continue; // истекла
			}
			$slides = array();
			foreach ( (array) get_field( 'slides', get_the_ID() ) as $sl ) {
				$img = ! empty( $sl['image'] ) ? wp_get_attachment_image_url( (int) $sl['image'], 'full' ) : '';
				if ( ! $img ) {
					continue;
				}
				$slides[] = array(
					'img'     => $img,
					'caption' => isset( $sl['caption'] ) ? $sl['caption'] : '',
					'link'    => isset( $sl['link_url'] ) ? $sl['link_url'] : '',
					'btn'     => isset( $sl['btn_text'] ) ? $sl['btn_text'] : '',
					'ad'      => ! empty( $sl['is_ad'] ),
				);
			}
			if ( ! $slides ) {
				continue;
			}
			$out[] = array(
				'id'     => get_the_ID(),
				'title'  => get_the_title(),
				'slides' => $slides,
			);
		}
		wp_reset_postdata();
	}
	return $out;
}

function bc_stories_shortcode( $atts ) {
	$atts    = shortcode_atts( array( 'org' => 0 ), $atts, 'bc_stories' );
	$org_id  = absint( $atts['org'] );
	$stories = bc_stories_data( $org_id );
	if ( ! $stories ) {
		return '';
	}

	$json = wp_json_encode( $stories );
	$uniq = 'bcvw_' . md5( $json . $org_id );

	ob_start();
	?>
	<div class="stories" id="<?php echo esc_attr( $uniq ); ?>">
		<?php foreach ( $stories as $s ) : ?>
		<div class="story" data-id="<?php echo (int) $s['id']; ?>">
			<span class="ring"><span class="in"><?php echo esc_html( mb_substr( $s['title'], 0, 1 ) ); ?></span></span>
			<b><?php echo esc_html( $s['title'] ); ?></b>
		</div>
		<?php endforeach; ?>
	</div>
	<script>window.BC_STORIES = window.BC_STORIES || {}; window.BC_STORIES['<?php echo esc_js( $uniq ); ?>'] = <?php echo $json; // phpcs:ignore ?>;</script>
	<?php
	return ob_get_clean();
}
add_shortcode( 'bc_stories', 'bc_stories_shortcode' );

function bc_stories_enqueue() {
	$js = get_stylesheet_directory() . '/assets/js/stories.js';
	if ( file_exists( $js ) ) {
		wp_enqueue_script( 'bc-stories', get_stylesheet_directory_uri() . '/assets/js/stories.js', array(), filemtime( $js ), true );
	}
}
add_action( 'wp_enqueue_scripts', 'bc_stories_enqueue' );

/**
 * Режим сторис организаций (stories.md §режимы): off | requests | self.
 */
function bc_stories_mode() {
	return get_option( 'bc_stories_mode', 'requests' );
}
