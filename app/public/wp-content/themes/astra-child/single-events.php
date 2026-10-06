<?php
/**
 * Карточка события (/afisha/{slug}/) + JSON-LD Event (D16).
 *
 * @package BC
 */

get_header();
while ( have_posts() ) :
	the_post();
	$bc_date = get_field( 'event_date' );
	$bc_time = get_field( 'event_time' );
	$bc_place = get_field( 'event_place' );
	$bc_price = get_field( 'event_price' );
	$bc_free  = get_field( 'is_free' );
	?>
<div class="wrap">
	<div class="crumbs"><a href="/">Главная</a><i>›</i><a href="/afisha">Афиша</a><i>›</i><?php the_title(); ?></div>
	<div class="panel">
		<h1 style="font-size:28px;font-weight:300;color:var(--ink);line-height:1.2"><?php the_title(); ?></h1>
		<div class="org-sub" style="margin:8px 0 16px">
			<?php echo $bc_date ? date( 'd.m.Y', strtotime( $bc_date ) ) : ''; ?><?php echo $bc_time ? ' · ' . esc_html( $bc_time ) : ''; ?><?php echo $bc_place ? ' · ' . esc_html( $bc_place ) : ''; ?>
			· <?php echo $bc_free ? 'Бесплатно' : ( $bc_price ? esc_html( $bc_price ) : 'Цена не указана' ); ?>
		</div>
		<div class="org-desc"><?php the_content(); ?></div>
	</div>
</div>
<script type="application/ld+json">
<?php
echo wp_json_encode(
	array(
		'@context'  => 'https://schema.org',
		'@type'     => 'Event',
		'name'      => get_the_title(),
		'startDate' => $bc_date . ( $bc_time ? 'T' . $bc_time : '' ),
		'location'  => array( '@type' => 'Place', 'name' => $bc_place ),
		'offers'    => array(
			'@type'         => 'Offer',
			'price'         => $bc_free ? '0' : $bc_price,
			'priceCurrency' => 'RUB',
			'url'           => get_permalink(),
		),
	)
);
?>
</script>
	<?php
endwhile;
get_footer();
