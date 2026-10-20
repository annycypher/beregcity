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
	$bc_plan_now = function_exists( 'bc_plan' ) ? bc_plan( get_the_ID() ) : 'free';
	$bc_plan_cls = ' is-' . $bc_plan_now;

	// Контакты: телефоны (JSON bc_phones + основной bc_phone), соцсети, точка на карте.
	$bc_phones_raw = get_post_meta( get_the_ID(), 'bc_phones', true );
	$bc_phones     = array();
	if ( $bc_phones_raw ) {
		$bc_decoded = json_decode( (string) $bc_phones_raw, true );
		if ( is_array( $bc_decoded ) ) {
			$bc_phones = array_values( array_filter( array_map( 'strval', $bc_decoded ) ) );
		}
	}
	if ( $bc_phone ) {
		array_unshift( $bc_phones, $bc_phone );
	}
	$bc_phones = array_values( array_unique( array_filter( $bc_phones ) ) );

	$bc_socials = json_decode( (string) get_post_meta( get_the_ID(), 'bc_socials', true ), true );
	if ( ! is_array( $bc_socials ) ) {
		$bc_socials = array();
	}
	if ( empty( $bc_socials['vk'] ) && $bc_vk ) {
		$bc_socials['vk'] = $bc_vk;
	}
	if ( empty( $bc_socials['telegram'] ) && $bc_tg ) {
		$bc_socials['telegram'] = $bc_tg;
	}
	if ( empty( $bc_socials['whatsapp'] ) && $bc_wa ) {
		$bc_socials['whatsapp'] = $bc_wa;
	}

	$bc_lat   = (float) get_post_meta( get_the_ID(), 'bc_lat', true );
	$bc_lng   = (float) get_post_meta( get_the_ID(), 'bc_lng', true );
	$bc_route = ( $bc_lat && $bc_lng ) ? 'https://yandex.ru/maps/?rtext=~' . $bc_lat . ',' . $bc_lng . '&rtt=auto' : '';
?>
<div class="wrap">

	<div class="crumbs">
		<a href="/">Главная</a><i>›</i><a href="/katalog">Каталог</a><i>›</i>
		<?php if ( $bc_cat ) : ?><a href="<?php echo esc_url( get_term_link( $bc_cat ) ); ?>"><?php echo esc_html( $bc_cat->name ); ?></a><i>›</i><?php endif; ?>
		<?php the_title(); ?>
	</div>

	<div class="org-head<?php echo esc_attr( $bc_plan_cls ); ?>">

		<div class="gal">
			<div class="gal-main">
				<?php if ( $bc_gal && is_array( $bc_gal ) && ! empty( $bc_gal ) ) : ?>
					<?php echo wp_get_attachment_image( $bc_gal[0], 'large' ); ?>
				<?php elseif ( has_post_thumbnail() ) : ?>
					<?php the_post_thumbnail( 'large' ); ?>
				<?php else : ?>
					<div class="org-cover-ph org-cover-ph--single"><span><?php echo esc_html( get_the_title() ); ?></span></div>
				<?php endif; ?>
			</div>
			<?php if ( 'free' !== $bc_plan_now && $bc_gal && is_array( $bc_gal ) && count( $bc_gal ) > 1 ) : ?>
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
				$bc_sched_cls = ! empty( $bc_sched['note'] ) ? ' note' : ( ! empty( $bc_sched['open'] ) ? '' : ' closed' );
			?>
			<div class="sched<?php echo esc_attr( $bc_sched_cls ); ?>"><span class="dot"></span><?php echo esc_html( $bc_sched['text'] ); ?></div>
			<?php endif; ?>

			<?php if ( $bc_phones ) : ?>
			<div class="org-phones">
				<?php foreach ( $bc_phones as $bc_p ) : ?>
					<a class="btn btn-terra" href="tel:<?php echo esc_attr( preg_replace( '/[^0-9+]/', '', $bc_p ) ); ?>"><?php echo esc_html( $bc_p ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>

			<?php if ( $bc_website ) : ?>
				<a class="btn btn-glass" href="<?php echo esc_url( $bc_website ); ?>" target="_blank" rel="nofollow noopener">Сайт организации</a>
			<?php endif; ?>

			<div class="org-links">
				<?php if ( ! empty( $bc_socials['vk'] ) ) : ?>
					<a class="btn btn-glass" href="<?php echo esc_url( $bc_socials['vk'] ); ?>" target="_blank" rel="nofollow noopener">ВКонтакте</a>
				<?php endif; ?>
				<?php if ( ! empty( $bc_socials['telegram'] ) ) : ?>
					<?php $bc_tg_url = preg_match( '#^https?://#i', $bc_socials['telegram'] ) ? $bc_socials['telegram'] : 'https://t.me/' . ltrim( $bc_socials['telegram'], '@' ); ?>
					<a class="btn btn-glass" href="<?php echo esc_url( $bc_tg_url ); ?>" target="_blank" rel="nofollow noopener">Telegram</a>
				<?php endif; ?>
				<?php if ( ! empty( $bc_socials['whatsapp'] ) ) : ?>
					<?php $bc_wa_url = preg_match( '#^https?://#i', $bc_socials['whatsapp'] ) ? $bc_socials['whatsapp'] : 'https://wa.me/' . preg_replace( '/[^0-9]/', '', $bc_socials['whatsapp'] ); ?>
					<a class="btn btn-glass" href="<?php echo esc_url( $bc_wa_url ); ?>" target="_blank" rel="nofollow noopener">WhatsApp</a>
				<?php endif; ?>
				<?php if ( $bc_route ) : ?>
					<a class="btn btn-glass" href="<?php echo esc_url( $bc_route ); ?>" target="_blank" rel="nofollow noopener">Маршрут</a>
				<?php endif; ?>
			</div>
		</div>
	</div>

	<?php /* Клейминг (D38, 3б): кнопка «Заявить права» на витринной карточке */ ?>
	<?php echo do_shortcode( '[bc_claim]' ); ?>

	<!-- Описание -->
	<?php if ( trim( get_the_content() ) ) : ?>
	<section>
		<h2 class="sec">О компании</h2>
		<div class="org-desc"><?php the_content(); ?></div>
		<?php bc_disclaimer_short(); ?>
	</section>
	<?php endif; ?>

	<?php /* bc_promo: акция (.org-promo) — Этап 6.1, сейчас не выводится */ ?>

	<!-- Сторис организации (Этап 5.4в): секция выводится только при наличии опубликованных сторис -->
	<?php
	$bc_stories = function_exists( 'bc_stories_data' ) ? bc_stories_data( get_the_ID() ) : array();
	if ( $bc_stories && ( ! function_exists( 'bc_stories_mode' ) || 'off' !== bc_stories_mode() ) ) :
		?>
	<section>
		<h2 class="sec">Сторис организации</h2>
		<?php echo do_shortcode( '[bc_stories org="' . get_the_ID() . '"]' ); ?>
	</section>
	<?php endif; ?>

	<!-- Отзывы (Этап 4.5, D40) -->
	<section>
		<h2 class="sec">Отзывы</h2>
		<?php echo do_shortcode( '[bc_reviews]' ); ?>
	</section>

	<!-- Карта (D27 — Яндекс под BC_MAP_ON) -->
	<section>
		<h2 class="sec">Как найти</h2>
		<?php
		echo function_exists( 'bc_map_markup' ) ? bc_map_markup( $bc_lat, $bc_lng, $bc_address ) : '';
		?>
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
	<?php if ( $bc_phones ) : ?>
		<a class="btn btn-terra" href="/go/<?php echo (int) get_the_ID(); ?>/phone/">Позвонить</a>
	<?php endif; ?>
	<?php if ( $bc_route ) : ?>
		<a class="btn btn-glass" href="<?php echo esc_url( $bc_route ); ?>" target="_blank" rel="nofollow noopener">Маршрут</a>
	<?php endif; ?>
</div>

<?php endwhile; ?>

<?php get_footer(); ?>

