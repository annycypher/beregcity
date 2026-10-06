<?php
/**
 * Вкладка «Акции» ЛК (Этап 4.4): создание акции (news тип promo) + лимит тарифа + модерация.
 *
 * @package BC
 */

$user   = wp_get_current_user();
$orgs   = get_posts( array( 'post_type' => 'organizations', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => 1 ) );
$org_id = $orgs ? (int) $orgs[0]->ID : 0;
if ( ! $org_id ) {
	return;
}
$plan  = function_exists( 'bc_plan' ) ? bc_plan( $org_id ) : 'free';
$limit = class_exists( 'BC_Plans' ) ? (int) BC_Plans::limit( $plan, 'promo' ) : 0;
$err   = '';
$ok    = '';

if ( isset( $_POST['bc_promo_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_promo_nonce'] ) ), 'bc_promo' ) ) {
	$title   = sanitize_text_field( wp_unslash( $_POST['promo_title'] ?? '' ) );
	$content = wp_kses_post( wp_unslash( $_POST['promo_content'] ?? '' ) );
	$count   = count(
		get_posts(
			array(
				'post_type'   => 'news',
				'author'      => $user->ID,
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_key'    => 'news_type',
				'meta_value'  => 'promo',
			)
		)
	);
	if ( ! $title ) {
		$err = 'Укажите заголовок акции.';
	} elseif ( $count >= $limit ) {
		$err = 'Лимит акций для вашего тарифа исчерпан (' . $limit . '/мес). <a href="/kabinet/billing/">Повысить тариф</a>';
	} else {
		$post_id = wp_insert_post(
			array(
				'post_type'    => 'news',
				'post_title'   => $title,
				'post_content' => $content,
				'post_status'  => 'pending',
				'post_author'  => $user->ID,
			)
		);
		if ( function_exists( 'update_field' ) ) {
			update_field( 'news_type', 'promo', $post_id );
		}
		wp_mail( get_option( 'admin_email' ), 'БерегСити: новая акция на модерацию', 'Акция «' . $title . '» ожидает проверки редакции.' );
		$ok = 'Акция отправлена на модерацию.';
	}
}

$promos = get_posts(
	array(
		'post_type'   => 'news',
		'author'      => $user->ID,
		'post_status' => 'any',
		'numberposts' => -1,
		'meta_key'    => 'news_type',
		'meta_value'  => 'promo',
	)
);
?>
<div class="panel">
	<h2>Новая акция</h2>
	<p class="gal-lim">Доступно акций: <b><?php echo (int) $limit; ?> в месяц</b> · тариф <?php echo esc_html( $plan ); ?></p>
	<?php if ( $err ) : ?><div style="background:#fbeae5;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#b0755c"><?php echo wp_kses_post( $err ); ?></div><?php endif; ?>
	<?php if ( $ok ) : ?><div style="background:#f1f5ec;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#2b332e"><?php echo esc_html( $ok ); ?></div><?php endif; ?>
	<form method="post">
		<div class="f"><label>Заголовок акции</label><input name="promo_title" required></div>
		<div class="f"><label>Описание</label><textarea name="promo_content" rows="4"></textarea><div class="hint">Акция появится в ленте с пометкой «Реклама» после проверки редакцией</div></div>
		<?php wp_nonce_field( 'bc_promo', 'bc_promo_nonce' ); ?>
		<button class="btn btn-terra" type="submit">Отправить на модерацию</button>
	</form>
</div>

<div class="panel">
	<h2>Мои акции</h2>
	<?php if ( ! $promos ) : ?><p class="found">Акций пока нет.</p><?php else : ?>
	<table class="inv-table">
		<thead><tr><th>Акция</th><th>Статус</th><th>Дата</th></tr></thead>
		<tbody>
		<?php foreach ( $promos as $p ) : $st = get_post_status( $p->ID ); ?>
			<tr>
				<td><?php echo esc_html( $p->post_title ); ?></td>
				<td><span class="st <?php echo 'publish' === $st ? 'ok' : ( 'pending' === $st ? 'wait' : 'reject' ); ?>"><?php echo esc_html( $st ); ?></span></td>
				<td><?php echo get_the_date( 'd.m.Y', $p->ID ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
