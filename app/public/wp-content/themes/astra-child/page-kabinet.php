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

	<div class="lk">
		<nav class="lk-nav">
			<a class="<?php echo 'card' === $tab ? 'on' : ''; ?>" href="/kabinet/card/">Моя карточка</a>
			<a class="<?php echo 'photo' === $tab ? 'on' : ''; ?>" href="/kabinet/photo/">Фото</a>
			<a class="<?php echo 'promo' === $tab ? 'on' : ''; ?>" href="/kabinet/promo/">Акции</a>
			<a class="<?php echo 'billing' === $tab ? 'on' : ''; ?>" href="/kabinet/billing/">Тариф и оплата</a>
			<a class="<?php echo 'stats' === $tab ? 'on' : ''; ?>" href="/kabinet/stats/">Статистика</a>
			<a class="off" href="#" title="Появится после запуска сторий (Этап 5)">Мои стории</a>
		</nav>

		<div class="lk-content">
			<?php if ( ! $org_id ) : ?>
				<div class="panel"><p>Организация не найдена. Обратитесь в поддержку.</p></div>
			<?php elseif ( 'card' === $tab ) : ?>
				<?php get_template_part( 'template-parts/lk-card' ); ?>
			<?php elseif ( 'photo' === $tab ) : ?>
				<div class="panel"><h2>Фото</h2><p>Загрузка фотографий — в следующем шаге.</p></div>
			<?php elseif ( 'promo' === $tab ) : ?>
				<?php /* bc_promo: акции организации — каркас; запись включится на Этапе 4 */ ?>
				<div class="panel"><h2>Акции</h2><p>Акции появятся после запуска (Этап 4).</p></div>
			<?php elseif ( 'billing' === $tab ) : ?>
				<div class="panel"><h2>Тариф и оплата</h2><p>Платежи и счета — в шаге 3а.5.</p></div>
			<?php elseif ( 'stats' === $tab ) : ?>
				<div class="panel"><h2>Статистика</h2><p>Статистика — в шаге 3а.6.</p></div>
			<?php else : ?>
				<div class="panel"><p>Вкладка не найдена.</p></div>
			<?php endif; ?>
		</div>
	</div>
</div>

<?php get_footer(); ?>
