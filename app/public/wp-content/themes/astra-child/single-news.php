<?php
/**
 * Карточка новости (/news/{slug}/).
 *
 * @package BC
 */

get_header();
while ( have_posts() ) :
	the_post();
	$bc_type = get_field( 'news_type' );
	?>
<div class="wrap">
	<div class="crumbs"><a href="/">Главная</a><i>›</i><a href="/news">Новости</a><i>›</i><?php the_title(); ?></div>
	<div class="panel">
		<h1 style="font-size:28px;font-weight:300;color:var(--ink);line-height:1.2"><?php the_title(); ?></h1>
		<div class="org-sub" style="margin:8px 0 16px"><?php echo get_the_date( 'd.m.Y' ); ?><?php echo ( $bc_type && 'news' !== $bc_type ) ? ' · ' . esc_html( $bc_type ) : ''; ?><?php $bc_erid = get_field( 'erid' ); if ( $bc_erid ) echo ' · erid: ' . esc_html( $bc_erid ); ?></div>
		<div class="org-desc"><?php the_content(); ?></div>
	</div>
</div>
	<?php
endwhile;
get_footer();
