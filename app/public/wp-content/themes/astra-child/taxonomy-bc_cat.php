<?php
/**
 * Шаблон категории каталога (/katalog/{cat}/).
 * Вёрстка 1:1 с design/catalog.html.
 *
 * @package BC
 */

get_header();

$bc_term = get_queried_object();
$bc_n    = (int) $bc_term->count;
$bc_w    = 'организаций';
if ( $bc_n % 10 === 1 && $bc_n % 100 !== 11 ) {
	$bc_w = 'организация';
} elseif ( in_array( $bc_n % 10, array( 2, 3, 4 ), true ) && ! in_array( $bc_n % 100, array( 12, 13, 14 ), true ) ) {
	$bc_w = 'организации';
}
?>
<div class="wrap">

	<div class="crumbs"><a href="/">Главная</a><i>›</i><a href="/katalog">Каталог</a><i>›</i><?php echo esc_html( $bc_term->name ); ?></div>

	<div class="cat-head">
		<h1><?php echo esc_html( $bc_term->name ); ?> на Южном берегу <span class="cat-count"><?php echo $bc_n . ' ' . $bc_w; ?></span></h1>
		<?php $bc_intro = term_description( $bc_term ); if ( $bc_intro ) : ?>
		<p class="cat-intro"><?php echo esc_html( $bc_intro ); ?></p>
		<?php endif; ?>
	</div>

	<div class="cat-layout">

		<aside>
			<button class="btn btn-glass btn-sm f-toggle" id="ftoggle">Фильтры</button>
			<div class="filters" id="fl">
				<?php /* bc_filters: Этап 3.3 — группы/пункты из features, счётчики живые */ ?>
			</div>
		</aside>

		<div>
			<div class="cat-top">
				<span class="found">Найдено: <?php echo $bc_n; ?></span>
				<label class="sort">Сортировка
					<select><option>Сначала премиум</option><option>По названию</option><option>По рейтингу</option><option>Новые</option></select>
				</label>
			</div>

			<div class="org-grid">
			<?php
			$bc_paged = max( 1, (int) get_query_var( 'paged' ) );
			$bc_query = new WP_Query(
				array(
					'post_type'      => 'organizations',
					'post_status'    => 'publish',
					'posts_per_page' => 12,
					'paged'          => $bc_paged,
					'tax_query'      => array(
						array(
							'taxonomy' => 'bc_cat',
							'field'    => 'term_id',
							'terms'    => $bc_term->term_id,
						),
					),
				)
			);
			if ( $bc_query->have_posts() ) :
				while ( $bc_query->have_posts() ) :
					$bc_query->the_post();
					get_template_part( 'template-parts/org-card' );
				endwhile;
			else :
				echo '<p class="found">В этой категории пока нет организаций.</p>';
			endif;
			wp_reset_postdata();
			?>
			</div>

			<?php
			if ( $bc_query->max_num_pages > 1 ) :
				$bc_links = paginate_links(
					array(
						'base'      => str_replace( 999999999, '%#%', esc_url( get_pagenum_link( 999999999 ) ) ),
						'format'    => '?paged=%#%',
						'current'   => $bc_paged,
						'total'     => $bc_query->max_num_pages,
						'prev_text' => '←',
						'next_text' => '→',
						'type'      => 'array',
					)
				);
				if ( $bc_links ) :
					echo '<div class="pager">';
					foreach ( $bc_links as $bc_link ) {
						if ( strpos( $bc_link, 'current' ) !== false ) {
							echo '<span class="on">' . esc_html( wp_strip_all_tags( $bc_link ) ) . '</span>';
						} else {
							echo $bc_link; // phpcs:ignore — ссылки пагинации WP
						}
					}
					echo '</div>';
				endif;
			endif;
			?>
		</div>

	</div>
</div>

<?php get_footer(); ?>
