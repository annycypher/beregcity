<?php
/**
 * Шаблон личного кабинета организации (Этап 3а.2). Вёрстка по design/lk.html.
 *
 * @package BC
 */

if ( function_exists( 'acf_form_head' ) ) {
	acf_form_head();
}
get_header();

$user   = wp_get_current_user();
$org_id = 0;
$orgs   = get_posts( array( 'post_type' => 'organizations', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => 1 ) );
if ( $orgs ) {
	$org_id = (int) $orgs[0]->ID;
}

$tab   = get_query_var( 'bc_tab' ) ? sanitize_key( get_query_var( 'bc_tab' ) ) : 'card';
$plan  = ( $org_id && function_exists( 'bc_plan' ) ) ? bc_plan( $org_id ) : 'free';
$until = ( $org_id && function_exists( 'get_field' ) ) ? get_field( 'field_bc_plan_until', $org_id ) : '';
?>
<div class="wrap">

	<div class="lk-hdr">
		<h1>Личный кабинет</h1>
		<span class="status"><?php echo esc_html( $plan ); ?></span>
		<?php if ( $until ) : ?><span class="until">тариф до <?php echo esc_html( $until ); ?></span><?php endif; ?>
	</div>

	<?php if ( function_exists( 'bc_reminders_org' ) ) { bc_reminders_org( $org_id ); } ?>

	<div class="lk">
		<nav class="lk-nav">
			<a class="<?php echo 'card' === $tab ? 'on' : ''; ?>" href="/kabinet/card/">Моя карточка</a>
			<a class="<?php echo 'photo' === $tab ? 'on' : ''; ?>" href="/kabinet/photo/">Фото</a>
			<a class="<?php echo 'promo' === $tab ? 'on' : ''; ?>" href="/kabinet/promo/">Акции</a>
			<a class="<?php echo 'billing' === $tab ? 'on' : ''; ?>" href="/kabinet/billing/">Тариф и оплата</a>
			<a class="<?php echo 'stats' === $tab ? 'on' : ''; ?>" href="/kabinet/stats/">Статистика</a>
			<?php if ( function_exists( 'bc_stories_mode' ) && 'off' !== bc_stories_mode() ) : ?>
			<a class="<?php echo 'stories' === $tab ? 'on' : ''; ?>" href="/kabinet/stories/">Мои стории</a>
			<?php else : ?>
			<a class="off" href="#" title="Стории выключены">Мои стории</a>
			<?php endif; ?>
		</nav>

		<div class="lk-content">
			<?php if ( ! $org_id ) : ?>
				<div class="panel"><p>Организация не найдена. Обратитесь в поддержку.</p></div>
			<?php elseif ( 'card' === $tab ) : ?>
				<?php get_template_part( 'template-parts/lk-card' ); ?>
			<?php elseif ( 'photo' === $tab ) : ?>
				<div class="panel"><h2>Фото</h2>
					<div class="hint"><?php echo esc_html( bc_hint( 'org_photo' ) ); ?>. Горизонтальные, первое — обложка.</div>
					<?php if ( 'free' === $plan ) : ?>
					<div class="hint" style="color:#8f6a33">Ваши фото показываются в тёплом ч/б — цветные открываются на тарифе Стандарт. <a href="/kabinet/billing/">Повысить тариф</a>.</div>
					<?php endif; ?>
					<p>Загрузка фотографий — в следующем шаге.</p>
				</div>
			<?php elseif ( 'promo' === $tab ) : ?>
				<?php get_template_part( 'template-parts/lk-promo' ); ?>
			<?php elseif ( 'billing' === $tab ) : ?>
				<div class="panel"><h2>Тариф и оплата</h2>
					<div class="hint">Здесь будут счета и продление тарифа. Пока оплата — по договорённости с редакцией.</div>
					<?php if ( class_exists( 'BC_Plans' ) ) : ?>
					<div class="inv-table"><table style="width:100%">
						<tr><th>Возможность</th><th>Free</th><th>Стандарт</th><th>Премиум</th></tr>
						<tr><td>Фото</td><td><?php echo (int) BC_Plans::limit( 'free', 'photos' ); ?></td><td><?php echo (int) BC_Plans::limit( 'standard', 'photos' ); ?></td><td><?php echo (int) BC_Plans::limit( 'premium', 'photos' ); ?></td></tr>
						<tr><td>Цветные фото</td><td>✕</td><td>✓</td><td>✓</td></tr>
						<tr><td>Описание, знаков</td><td>до <?php echo (int) BC_Plans::limit( 'free', 'desc' ); ?></td><td>до <?php echo (int) BC_Plans::limit( 'standard', 'desc' ); ?></td><td>до <?php echo (int) BC_Plans::limit( 'premium', 'desc' ); ?></td></tr>
					</table></div>
					<?php endif; ?>
					<p>Платежи и счета — в шаге 3а.5.</p>
				</div>
			<?php elseif ( 'stats' === $tab ) : ?>
				<?php get_template_part( 'template-parts/lk-stats' ); ?>
			<?php elseif ( 'stories' === $tab ) : ?>
				<?php get_template_part( 'template-parts/lk-stories' ); ?>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php get_footer(); ?>