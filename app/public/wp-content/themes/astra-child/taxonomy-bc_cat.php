<?php
/**
 * Шаблон категории каталога (/katalog/{cat}/).
 * Вёрстка 1:1 с design/catalog.html.
 *
 * @package BC
 */

get_header();

$bc_term  = get_queried_object();
$bc_feat  = isset( $_GET['feat'] ) ? array_filter( array_map( 'absint', (array) $_GET['feat'] ) ) : array();
$bc_sort  = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'premium';
$bc_order     = function_exists( 'bc_sort_args' ) ? bc_sort_args( $bc_sort ) : array( 'orderby' => 'date', 'order' => 'DESC' );
$bc_feat_q    = get_query_var( 'bc_feature' );
$bc_feat_term = false;
if ( $bc_feat_q ) {
	$bc_feat_term = get_term_by( 'name', $bc_feat_q, 'features' );
	if ( ! $bc_feat_term || is_wp_error( $bc_feat_term ) ) {
		$bc_feat_term = get_term_by( 'slug', rawurldecode( $bc_feat_q ), 'features' );
	}
}
$bc_n    = (int) $bc_term->count;
$bc_w    = 'организаций';
if ( $bc_n % 10 === 1 && $bc_n % 100 !== 11 ) {
	$bc_w = 'организация';
} elseif ( in_array( $bc_n % 10, array( 2, 3, 4 ), true ) && ! in_array( $bc_n % 100, array( 12, 13, 14 ), true ) ) {
	$bc_w = 'организации';
}
?>
<div class="wrap">

	<div class="crumbs"><a href="/">Главная</a><i>›</i><a href="/katalog">Каталог</a><i>›</i><?php echo esc_html( $bc_term->name ); ?><?php if ( $bc_feat_term ) : ?><i>›</i><?php echo esc_html( $bc_feat_term->name ); ?><?php endif; ?></div>
	<?php
	$bc_crumbs = array(
		array( 'name' => 'Главная', 'url' => home_url( '/' ) ),
		array( 'name' => 'Каталог', 'url' => home_url( '/katalog/' ) ),
		array( 'name' => $bc_term->name, 'url' => get_term_link( $bc_term ) ),
	);
	if ( $bc_feat_term ) {
		$bc_crumbs[] = array( 'name' => $bc_feat_term->name, 'url' => null );
	}
	echo function_exists( 'bc_breadcrumb_jsonld' ) ? bc_breadcrumb_jsonld( $bc_crumbs ) : '';
	?>

	<div class="cat-head">
		<?php if ( $bc_feat_term ) : ?>
			<h1><?php echo esc_html( $bc_feat_term->name ); ?> — <?php echo esc_html( $bc_term->name ); ?> на Южном берегу <span class="cat-count"><?php echo $bc_n . ' ' . $bc_w; ?></span></h1>
			<p class="cat-intro">Организации категории «<?php echo esc_html( $bc_term->name ); ?>» со снипетом «<?php echo esc_html( $bc_feat_term->name ); ?>» в микрорайоне Южный берег. Подобрали места с телефонами, адресами и графиком — выбирайте рядом с домом.</p>
		<?php else : ?>
			<h1><?php echo esc_html( $bc_term->name ); ?> на Южном берегу <span class="cat-count"><?php echo $bc_n . ' ' . $bc_w; ?></span></h1>
			<?php $bc_intro = term_description( $bc_term ); if ( $bc_intro ) : ?>
			<p class="cat-intro"><?php echo esc_html( $bc_intro ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
	</div>

	<div class="cat-layout">

		<aside>
			<button class="btn btn-glass btn-sm f-toggle" id="ftoggle" type="button">Фильтры</button>
			<form method="get" action="<?php echo esc_url( get_term_link( $bc_term ) ); ?>">
				<?php echo function_exists( 'bc_filters' ) ? bc_filters( $bc_term->term_id ) : ''; ?>
			</form>
		</aside>

		<div>
			<div class="cat-top">
				<span class="found">Найдено: <?php echo $bc_n; ?></span>
				<form method="get" action="<?php echo esc_url( get_term_link( $bc_term ) ); ?>" style="margin:0">
					<label class="sort">Сортировка
						<select name="sort" onchange="this.form.submit()">
							<option value="premium"<?php selected( $bc_sort, 'premium' ); ?>>Сначала премиум</option>
							<option value="name"<?php selected( $bc_sort, 'name' ); ?>>По названию</option>
							<option value="rating"<?php selected( $bc_sort, 'rating' ); ?>>По рейтингу</option>
							<option value="new"<?php selected( $bc_sort, 'new' ); ?>>Новые</option>
						</select>
						<?php foreach ( $bc_feat as $bc_fid ) { echo '<input type="hidden" name="feat[]" value="' . (int) $bc_fid . '">'; } ?>
					</label>
				</form>
			</div>

			<div class="org-grid">
			<?php
			$bc_paged = max( 1, (int) get_query_var( 'paged' ) );
			$bc_tax   = array(
				array(
					'taxonomy' => 'bc_cat',
					'field'    => 'term_id',
					'terms'    => $bc_term->term_id,
				),
			);
			if ( $bc_feat ) {
				$bc_tax[]           = array( 'taxonomy' => 'features', 'field' => 'term_id', 'terms' => array_values( $bc_feat ) );
				$bc_tax['relation'] = 'AND';
			}
			$bc_query = new WP_Query(
				array(
					'post_type'      => 'organizations',
					'post_status'    => 'publish',
					'posts_per_page' => 12,
					'paged'          => $bc_paged,
					'tax_query'      => $bc_tax,
					'orderby'        => $bc_order['orderby'],
					'order'          => $bc_order['order'],
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
			$bc_list = array();
			foreach ( (array) $bc_query->posts as $bc_p ) {
				$bc_list[] = array( '@type' => 'ListItem', 'position' => count( $bc_list ) + 1, 'url' => get_permalink( $bc_p->ID ) );
			}
			if ( $bc_list ) {
				echo '<script type="application/ld+json">' . wp_json_encode( array( '@context' => 'https://schema.org', '@type' => 'ItemList', 'itemListElement' => $bc_list ) ) . '</script>';
			}
			?>

			<?php
			echo function_exists( 'bc_pager' ) ? bc_pager( $bc_query, $bc_paged ) : '';
			?>
		</div>

	</div>
</div>

<?php get_footer(); ?>
