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
 * Данные сторис.
 *
 * @param int $org_id 0 — редакция; иначе — сторис организации (post_author).
 * @return array
 */
function bc_stories_data( $org_id = 0 ) {
	$args = array(
		'post_type'      => 'stories',
		'post_status'    => 'publish',
		'posts_per_page' => -1,
		'orderby'        => 'date',
		'order'          => 'DESC',
		'no_found_rows'  => true,
	);
	if ( $org_id ) {
		$author = (int) get_post_field( 'post_author', (int) $org_id );
		if ( ! $author ) {
			return array(); // Витринная карточка без владельца — сторис не бывает.
		}
		$args['author'] = $author; // Сторис принадлежат пользователю-владельцу, не посту организации.
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
					'thumb'   => wp_get_attachment_image_url( (int) $sl['image'], 'thumbnail' ),
				);
			}
			if ( ! $slides && function_exists( 'bc_story_single_slide' ) ) {
				$single = bc_story_single_slide( get_the_ID() ); // ACF free: одно фото из ЛК.
				if ( $single ) {
					$slides[] = $single;
				}
			}
			if ( ! $slides ) {
				continue;
			}
			$out[] = array(
				'id'     => get_the_ID(),
				'title'  => get_the_title(),
				'slides' => $slides,
				'sort'   => (int) get_field( 'sort_order', get_the_ID() ), // Ручной порядок (0 — по дате).
				'ts'     => (int) get_post_time( 'U', true, get_the_ID() ),
			);
		}
		wp_reset_postdata();
	}

	// Сортировка: sort_order по возрастанию, при равенстве — свежие выше.
	usort(
		$out,
		function ( $a, $b ) {
			if ( $a['sort'] === $b['sort'] ) {
				return $b['ts'] <=> $a['ts'];
			}
			return $a['sort'] <=> $b['sort'];
		}
	);
	foreach ( $out as &$bc_row ) {
		unset( $bc_row['sort'], $bc_row['ts'] );
	}
	unset( $bc_row );

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
		<?php
		$bc_thumb = ! empty( $s['slides'][0]['thumb'] ) ? $s['slides'][0]['thumb'] : '';
		?>
		<div class="story" data-id="<?php echo (int) $s['id']; ?>">
			<span class="ring"><span class="in">
				<?php if ( $bc_thumb ) : ?>
					<img src="<?php echo esc_url( $bc_thumb ); ?>" alt="" loading="lazy" decoding="async">
				<?php else : ?>
					<?php echo esc_html( mb_substr( $s['title'], 0, 1 ) ); ?>
				<?php endif; ?>
			</span></span>
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
