<?php
/**
 * Архив новостей (/news/). Вёрстка по компонентам homepage (палитра + .org-card).
 *
 * @package BC
 */

get_header();
?>
<div class="wrap">
	<div class="crumbs"><a href="/">Главная</a><i>›</i>Новости</div>
	<div class="cat-head"><h1>Новости района</h1></div>
	<div class="org-grid">
		<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); $bc_type = get_field( 'news_type' ); ?>
		<article class="org-card">
			<a class="org-cover" href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium_large' ); else : ?><svg class="ico" viewBox="0 0 24 24"><path d="M3 6h18v12H3z"/><path d="M3 10h18"/></svg><?php endif; ?>
			</a>
			<div class="org-body">
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<span class="org-sub"><?php echo get_the_date( 'd.m.Y' ); ?><?php echo ( $bc_type && 'news' !== $bc_type ) ? ' · ' . esc_html( $bc_type ) : ''; ?></span>
				<p class="org-desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 18 ) ); ?></p>
			</div>
		</article>
		<?php endwhile; endif; ?>
	</div>
	<?php echo function_exists( 'bc_pager' ) ? bc_pager( $wp_query, max( 1, (int) get_query_var( 'paged' ) ) ) : ''; ?>
</div>
<?php get_footer(); ?>
