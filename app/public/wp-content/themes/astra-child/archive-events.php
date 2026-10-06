<?php
/**
 * Афиша событий (/afisha/). Фильтр по датам (предстоящие/прошедшие/все).
 *
 * @package BC
 */

get_header();

$period = isset( $_GET['period'] ) ? sanitize_key( $_GET['period'] ) : 'upcoming';
$paged  = max( 1, (int) get_query_var( 'paged' ) );
$today  = current_time( 'Y-m-d' );

$args = array(
	'post_type'      => 'events',
	'post_status'    => 'publish',
	'posts_per_page' => 12,
	'paged'          => $paged,
	'meta_key'       => 'event_date',
	'orderby'        => 'meta_value',
	'order'          => ( 'past' === $period ) ? 'DESC' : 'ASC',
);
if ( 'upcoming' === $period ) {
	$args['meta_query'] = array( array( 'key' => 'event_date', 'value' => $today, 'compare' => '>=', 'type' => 'DATE' ) );
} elseif ( 'past' === $period ) {
	$args['meta_query'] = array( array( 'key' => 'event_date', 'value' => $today, 'compare' => '<', 'type' => 'DATE' ) );
}
$q = new WP_Query( $args );
?>
<div class="wrap">
	<div class="crumbs"><a href="/">Главная</a><i>›</i>Афиша</div>
	<div class="cat-head">
		<h1>Афиша событий</h1>
		<div class="periods" style="margin-top:14px">
			<label><input type="radio" name="period"<?php checked( $period, 'upcoming' ); ?> onchange="location.href='?period=upcoming'"><span>Предстоящие</span></label>
			<label><input type="radio" name="period"<?php checked( $period, 'past' ); ?> onchange="location.href='?period=past'"><span>Прошедшие</span></label>
			<label><input type="radio" name="period"<?php checked( $period, 'all' ); ?> onchange="location.href='?period=all'"><span>Все</span></label>
		</div>
	</div>
	<div class="org-grid">
		<?php if ( $q->have_posts() ) : while ( $q->have_posts() ) : $q->the_post(); $bc_d = get_field( 'event_date' ); ?>
		<article class="org-card">
			<a class="org-cover" href="<?php the_permalink(); ?>">
				<?php if ( has_post_thumbnail() ) : the_post_thumbnail( 'medium_large' ); else : ?><svg class="ico" viewBox="0 0 24 24"><rect x="3" y="4" width="18" height="17" rx="3"/><path d="M8 2v4M16 2v4M3 10h18"/></svg><?php endif; ?>
			</a>
			<div class="org-body">
				<h3><a href="<?php the_permalink(); ?>"><?php the_title(); ?></a></h3>
				<span class="org-sub"><?php echo $bc_d ? date( 'd.m.Y', strtotime( $bc_d ) ) : ''; ?><?php echo get_field( 'event_place' ) ? ' · ' . esc_html( get_field( 'event_place' ) ) : ''; ?></span>
				<p class="org-desc"><?php echo esc_html( wp_trim_words( get_the_excerpt(), 15 ) ); ?></p>
			</div>
		</article>
		<?php endwhile; endif; wp_reset_postdata(); ?>
	</div>
	<?php echo function_exists( 'bc_pager' ) ? bc_pager( $q, $paged ) : ''; ?>
</div>
<?php get_footer(); ?>
