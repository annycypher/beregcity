<?php
/**
 * Вкладка «Моя карточка» (Этап 3а.2): прогресс §17 + acf_form.
 *
 * @package BC
 */

$user   = wp_get_current_user();
$orgs   = get_posts( array( 'post_type' => 'organizations', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => 1 ) );
$org_id = $orgs ? (int) $orgs[0]->ID : 0;
if ( ! $org_id ) {
	return;
}

$prog = function_exists( 'bc_progress' ) ? bc_progress( $org_id ) : array( 'percent' => 0, 'missing' => array() );
?>
<?php
	$bc_plan_now    = function_exists( 'bc_plan' ) ? bc_plan( $org_id ) : 'free';
	$bc_plan_labels = array( 'trial' => 'Триал', 'free' => 'Free', 'standard' => 'Стандарт', 'premium' => 'Премиум' );
	?>
	<!-- Моя организация (D38) -->
	<div class="panel" id="my-org">
		<div class="lk-hdr" style="margin:0 0 12px">
			<h1 style="font-size:20px"><?php echo esc_html( get_the_title( $org_id ) ); ?></h1>
			<span class="status"><?php echo esc_html( isset( $bc_plan_labels[ $bc_plan_now ] ) ? $bc_plan_labels[ $bc_plan_now ] : $bc_plan_now ); ?></span>
		</div>
		<a class="btn btn-terra btn-sm" href="/kabinet/card/">Редактировать карточку</a>
		<?php if ( 'free' === $bc_plan_now && function_exists( 'bc_is_claimed' ) && bc_is_claimed( $org_id ) ) : ?>
			<div class="hint" style="margin-top:10px">Расширенные возможности — на тарифе Стандарт: сайт и соцсети, ярлыки, акции, QR. <a href="/kabinet/billing/">Повысить тариф</a>.</div>
		<?php endif; ?>
	</div>
<div class="prog">
	<div class="prog-ring" style="background:conic-gradient(var(--terra) 0 <?php echo (int) $prog['percent']; ?>%,#eee7db <?php echo (int) $prog['percent']; ?>% 100%)"><b><?php echo (int) $prog['percent']; ?>%</b></div>
	<div class="prog-txt">
		<b>Карточка заполнена на <?php echo (int) $prog['percent']; ?>%</b>
		<p>Заполненные карточки получают больше просмотров. Начните с самого простого:</p>
		<div class="prog-list">
			<?php foreach ( array_slice( $prog['missing'], 0, 4 ) as $m ) : ?>
				<a href="/kabinet/<?php echo esc_attr( $m['tab'] ); ?>/"><?php echo esc_html( $m['label'] ); ?></a>
			<?php endforeach; ?>
		</div>
	</div>
</div>

<div class="panel">
	<h2>Основные сведения</h2>
	<?php
	if ( function_exists( 'acf_form' ) ) {
		acf_form(
			array(
				'post_id'         => $org_id,
				'fields'          => array( 'field_bc_phone', 'field_bc_address', 'field_bc_lat', 'field_bc_lng', 'field_bc_website', 'field_bc_whatsapp', 'field_bc_telegram', 'field_bc_vk', 'field_bc_excerpt', 'field_bc_keywords' ),
				'submit_value'    => 'Сохранить изменения',
				'updated_message' => 'Изменения сохранены',
			)
		);
	}
	?>
</div>

	<?php if ( function_exists( 'bc_qr_svg' ) && in_array( bc_plan( $org_id ), array( 'standard', 'premium' ), true ) ) : ?>
	<div class="panel">
		<h2>QR-код вашей карточки</h2>
		<div class="qr-block">
			<div class="qr-img"><?php echo bc_qr_svg( get_permalink( $org_id ) ); ?></div>
			<div class="qr-txt">
				<b>Найдите нас на beregcity.ru</b>
				<p>Отсканируйте телефоном — откроется ваша карточка. Доступно на тарифе Стандарт+.</p>
				<a class="btn btn-glass btn-sm" href="/qr/<?php echo (int) $org_id; ?>/">Скачать PNG</a>
			</div>
		</div>
	</div>
	<?php endif; ?>
