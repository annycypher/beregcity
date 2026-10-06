<?php
/**
 * Карточка организации (/katalog/organization/{slug}/).
 * Вёрстка 1:1 с design/org-card.html.
 *
 * @package BC
 */

get_header();

while ( have_posts() ) :
	the_post();

	if ( function_exists( 'bc_track_view' ) ) {
		bc_track_view( get_the_ID() );
	}

	$bc_phone   = get_field( 'field_bc_phone' );
	$bc_address = get_field( 'field_bc_address' );
	$bc_website = get_field( 'field_bc_website' );
	$bc_wa      = get_field( 'field_bc_whatsapp' );
	$bc_tg      = get_field( 'field_bc_telegram' );
	$bc_vk      = get_field( 'field_bc_vk' );
	$bc_gal     = get_field( 'field_bc_gallery' );
	$bc_cats    = get_the_terms( get_the_ID(), 'bc_cat' );
	$bc_cat     = ( $bc_cats && ! is_wp_error( $bc_cats ) ) ? $bc_cats[0] : null;
	$bc_feats   = get_the_terms( get_the_ID(), 'features' );
	$bc_tel_uri = preg_replace( '/[^0-9+]/', '', (string) $bc_phone );
?>
<div class="wrap">

	<div class="crumbs">
		<a href="/">Главная</a><i>›</i><a href="/katalog">Каталог</a><i>›</i>
		<?php if ( $bc_cat ) : ?><a href="<?php echo esc_url( get_term_link( $bc_cat ) ); ?>"><?php echo esc_html( $bc_cat->name ); ?></a><i>›</i><?php endif; ?>
		<?php the_title(); ?>
	</div>

	<div class="org-head">

		<div class="gal">
			<div class="gal-main">
				<?php if ( $bc_gal && is_array( $bc_gal ) && ! empty( $bc_gal ) ) : ?>
					<?php echo wp_get_attachment_image( $bc_gal[0], 'large' ); ?>
				<?php elseif ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php else : ?>
					<svg class="ico" viewBox="0 0 24 24"><path d="M18 8h1a4 4 0 0 1 0 8h-1"/><path d="M2 8h16v9a4 4 0 0 1-4 4H6a4 4 0 0 1-4-4V8z"/></svg>
				<?php endif; ?>
			</div>
			<?php if ( $bc_gal && is_array( $bc_gal ) && count( $bc_gal ) > 1 ) : ?>
			<div class="gal-thumbs">
				<?php foreach ( $bc_gal as $bc_gi => $bc_img ) : ?>
					<span class="<?php echo 0 === $bc_gi ? 'on' : ''; ?>"><?php echo wp_get_attachment_image( $bc_img, 'thumbnail' ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
		</div>

		<div class="org-side">
			<div class="badges">
				<?php /* .ribbon «Премиум» — по тарифу (3.4) */ ?>
				<?php if ( get_field( 'field_bc_is_verified' ) ) : ?>
					<span class="vbadge"><svg viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg>Проверено</span>
				<?php endif; ?>
			</div>

			<h1><?php the_title(); ?></h1>
			<div class="org-cat"><?php echo $bc_cat ? esc_html( $bc_cat->name ) : ''; ?><?php echo $bc_address ? ' · ' . esc_html( $bc_address ) : ''; ?></div>

			<?php if ( $bc_feats && ! is_wp_error( $bc_feats ) ) : ?>
			<div class="org-feats">
				<?php foreach ( $bc_feats as $bc_f ) : ?>
					<span class="feat"><svg class="ico" viewBox="0 0 24 24"><path d="M20 6L9 17l-5-5"/></svg><?php echo esc_html( $bc_f->name ); ?></span>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php
			$bc_sched = function_exists( 'bc_schedule_status' ) ? bc_schedule_status( get_the_ID() ) : array( 'open' => false, 'text' => '' );
			if ( ! empty( $bc_sched['text'] ) ) :
			?>
			<div class="sched<?php echo ! empty( $bc_sched['open'] ) ? '' : ' closed'; ?>"><span class="dot"></span><?php echo esc_html( $bc_sched['text'] ); ?></div>
			<?php endif; ?>

			<?php if ( $bc_phone ) : ?>
				<a class="btn btn-terra" href="/go/<?php echo (int) get_the_ID(); ?>/phone/"><?php echo esc_html( $bc_phone ); ?></a>
			<?php endif; ?>

			<?php if ( $bc_website ) : ?>
				<a class="btn btn-glass" style="margin-bottom:10px" href="/go/<?php echo (int) get_the_ID(); ?>/site/">Сайт организации</a>
			<?php endif; ?>

			<div class="org-links">
				<?php if ( $bc_wa ) : ?><a class="btn btn-glass" href="/go/<?php echo (int) get_the_ID(); ?>/whatsapp/">WhatsApp</a><?php endif; ?>
				<?php if ( $bc_tg ) : ?><a class="btn btn-glass" href="/go/<?php echo (int) get_the_ID(); ?>/telegram/">Telegram</a><?php endif; ?>
				<?php /* bc_map: «Маршрут» — Яндекс.Карта под BC_MAP_ON (D27) */ ?>
				<a class="btn btn-glass" href="#">Маршрут</a>
			</div>

			<?php if ( $bc_vk ) : ?>
			<div class="soc-mini">
				<a href="<?php echo esc_url( $bc_vk ); ?>" aria-label="ВКонтакте" rel="nofollow noopener" target="_blank"><svg viewBox="0 0 24 24"><path d="M3 8c1.5 6 5 9.5 9 10v-4c2 .5 3.5 2 4.5 4H20c-.7-3-2.5-5-4-6 1.5-1.5 3-3.5 3.5-6h-3.3c-.8 2.3-2.3 4.3-4.2 5V6H9v7C6.5 11.5 5 9.5 4.5 8z"/></svg></a>
			</div>
			<?php endif; ?>
		</div>
	</div>

	<!-- Описание -->
	<?php if ( trim( get_the_content() ) ) : ?>
	<section>
		<h2 class="sec">О компании</h2>
		<div class="org-desc"><?php the_content(); ?></div>
	</section>
	<?php endif; ?>

	<?php /* bc_promo: акция (.org-promo) — Этап 6.1, сейчас не выводится */ ?>

	<!-- Стории компании (Этап 5.4в — якорь) -->
	<section>
		<h2 class="sec">Стории компании</h2>
		<div class="org-stories">
			<?php /* bc_org_stories: заменить на живые стории на Этапе 5 */ ?>
			<div class="story"><span class="ring"><span class="in">Х</span></span><small>Новинки</small></div>
			<div class="story"><span class="ring"><span class="in">К</span></span><small>Заходите</small></div>
			<div class="story"><span class="ring"><span class="in">А</span></span><small>Акции</small></div>
		</div>
	</section>

	<!-- Отзывы (Этап 4 — якорь) -->
	<section>
		<h2 class="sec">Отзывы</h2>
		<?php /* bc_reviews: живые отзывы — Этап 4 */ ?>
		<div class="rev-sum">
			<span class="num">—</span>
			<span>Отзывы появятся после модерации</span>
		</div>
		<a class="btn btn-glass btn-sm" href="#">Написать отзыв</a>
	</section>

	<!-- Карта (D27 — заглушка до BC_MAP_ON) -->
	<section>
		<h2 class="sec">Как найти</h2>
		<div class="map-ph">
			<?php /* bc_map: Яндекс.Карта по BC_MAP_ON (D27); lat/lng в полях — сейчас SVG-заглушка */ ?>
			<svg class="mark" viewBox="0 0 24 24" style="width:38px;height:38px"><path class="ico" style="width:38px;height:38px;stroke:currentColor;stroke-width:1.6;fill:none" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle style="stroke:currentColor;stroke-width:1.6;fill:none" cx="12" cy="10" r="3"/></svg>
			<a class="btn btn-glass" style="position:absolute;bottom:14px;left:14px" href="#">Маршрут</a>
		</div>
	</section>

	<!-- Похожие рядом (Патч 3: та же категория, без текущей, макс. 3; при недостатке — скрыть) -->
	<?php
	$bc_sim = null;
	if ( $bc_cat ) {
		$bc_sim = new WP_Query(
			array(
				'post_type'      => 'organizations',
				'post_status'    => 'publish',
				'posts_per_page' => 3,
				'post__not_in'   => array( get_the_ID() ),
				'tax_query'      => array(
					array(
						'taxonomy' => 'bc_cat',
						'field'    => 'term_id',
						'terms'    => $bc_cat->term_id,
					),
				),
			)
		);
	}
	if ( $bc_sim && $bc_sim->have_posts() ) :
	?>
	<section>
		<h2 class="sec">Похожие рядом</h2>
		<div class="sim">
			<?php while ( $bc_sim->have_posts() ) : $bc_sim->the_post(); ?>
				<?php get_template_part( 'template-parts/org-card' ); ?>
			<?php endwhile; wp_reset_postdata(); ?>
		</div>
	</section>
	<?php endif; ?>

</div>

<!-- Мобильная панель звонка -->
<div class="callbar">
	<?php if ( $bc_phone ) : ?>
		<a class="btn btn-terra" href="/go/<?php echo (int) get_the_ID(); ?>/phone/">Позвонить</a>
	<?php endif; ?>
	<a class="btn btn-glass" href="#">Маршрут</a>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>

