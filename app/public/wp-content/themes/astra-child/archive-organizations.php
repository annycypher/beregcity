<?php
/**
 * Архив всех организаций (/katalog/).
 *
 * @package BC
 */

get_header();

$bc_n     = (int) wp_count_posts( 'organizations' )->publish;
$bc_feat  = isset( $_GET['feat'] ) ? array_filter( array_map( 'absint', (array) $_GET['feat'] ) ) : array();
$bc_sort  = isset( $_GET['sort'] ) ? sanitize_key( $_GET['sort'] ) : 'premium';
$bc_order = function_exists( 'bc_sort_args' ) ? bc_sort_args( $bc_sort ) : array( 'orderby' => 'date', 'order' => 'DESC' );
$bc_w = 'организаций';
if ( $bc_n % 10 === 1 && $bc_n % 100 !== 11 ) {
	$bc_w = 'организация';
} elseif ( in_array( $bc_n % 10, array( 2, 3, 4 ), true ) && ! in_array( $bc_n % 100, array( 12, 13, 14 ), true ) ) {
	$bc_w = 'организации';
}
?>
<div class="wrap">

	<div class="crumbs"><a href="/">Главная</a><i>›</i>Каталог</div>
	<?php
	echo function_exists( 'bc_breadcrumb_jsonld' ) ? bc_breadcrumb_jsonld(
		array(
			array( 'name' => 'Главная', 'url' => home_url( '/' ) ),
			array( 'name' => 'Каталог', 'url' => home_url( '/katalog/' ) ),
		)
	) : '';
	?>

	<div class="cat-head">
		<h1>Каталог организаций <span class="cat-count"><?php echo $bc_n . ' ' . $bc_w; ?></span></h1>
		<p class="cat-intro">Организации микрорайона Южный берег: еда, медицина, магазины, услуги и развлечения — с адресами, телефонами и графиком.</p>
	</div>

	<div class="cat-layout">

		<aside>
			<button class="btn btn-glass btn-sm f-toggle" id="ftoggle" type="button">Фильтры</button>
			<form method="get" action="<?php echo esc_url( home_url( '/katalog/' ) ); ?>">
				<?php echo function_exists( 'bc_filters' ) ? bc_filters( 0 ) : ''; ?>
			</form>
		</aside>

		<div>
			<div class="cat-top">
				<span class="found">Найдено: <?php echo $bc_n; ?></span>
				<form method="get" action="<?php echo esc_url( home_url( '/katalog/' ) ); ?>" style="margin:0">
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
			$bc_args  = array(
				'post_type'      => 'organizations',
				'post_status'    => 'publish',
				'posts_per_page' => 12,
				'paged'          => $bc_paged,
				'orderby'        => $bc_order['orderby'],
				'order'          => $bc_order['order'],
			);
			if ( $bc_feat ) {
				$bc_args['tax_query'] = function_exists( 'bc_catalog_tax_query' ) ? bc_catalog_tax_query( 0, $bc_feat ) : array();
			}
			$bc_query = new WP_Query( $bc_args );
			if ( $bc_query->have_posts() ) :
				while ( $bc_query->have_posts() ) :
					$bc_query->the_post();
					get_template_part( 'template-parts/org-card' );
				endwhile;
			else :
				echo '<p class="found">Организаций пока нет.</p>';
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

			<?php bc_disclaimer_short(); ?>
			<?php
			echo function_exists( 'bc_pager' ) ? bc_pager( $bc_query, $bc_paged ) : '';
			?>
		</div>

	</div>
</div>

<?php get_footer(); ?>
