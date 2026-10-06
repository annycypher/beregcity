<?php
/**
 * Вкладка «Мои стории» ЛК (Этап 5.4): режимы ВЫКЛ/Заявки/Самостоятельные.
 *
 * @package BC
 */

$user   = wp_get_current_user();
$orgs   = get_posts( array( 'post_type' => 'organizations', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => 1 ) );
$org_id = $orgs ? (int) $orgs[0]->ID : 0;
if ( ! $org_id ) {
	return;
}
$mode = function_exists( 'bc_stories_mode' ) ? bc_stories_mode() : 'requests';
$ok   = '';
$err  = '';

if ( isset( $_POST['bc_story_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_story_nonce'] ) ), 'bc_story' ) ) {
	$title = sanitize_text_field( wp_unslash( $_POST['story_title'] ?? '' ) );
	$desc  = sanitize_textarea_field( wp_unslash( $_POST['story_desc'] ?? '' ) );
	if ( ! $title ) {
		$err = 'Укажите название стории.';
	} else {
		$story_id = wp_insert_post(
			array(
				'post_type'   => 'stories',
				'post_title'  => $title,
				'post_status' => 'pending',
				'post_author' => $user->ID,
			)
		);
		update_post_meta( $story_id, 'bc_story_request', $desc );
		wp_mail( get_option( 'admin_email' ), 'БерегСити: заявка на сторию', 'Организация просит сторию: «' . $title . '». Описание: ' . $desc );
		$ok = 'Заявка отправлена — редакция соберёт сторию из ваших материалов.';
	}
}

$my_stories = get_posts( array( 'post_type' => 'stories', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => -1 ) );
?>
<div class="panel">
	<h2>Мои стории</h2>
	<?php if ( $err ) : ?><div style="background:#fbeae5;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#b0755c"><?php echo esc_html( $err ); ?></div><?php endif; ?>
	<?php if ( $ok ) : ?><div style="background:#f1f5ec;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#2b332e"><?php echo esc_html( $ok ); ?></div><?php endif; ?>

	<?php if ( 'self' === $mode && function_exists( 'acf_form' ) ) : ?>
		<p class="gal-lim">Загрузите фото (1080×1920, до 5 слайдов). Стория уйдёт на модерацию редакции.</p>
		<?php
		acf_form(
			array(
				'post_id'         => 'new_post',
				'new_post'        => array( 'post_type' => 'stories', 'post_status' => 'pending' ),
				'fields'          => array( 'field_bc_slides' ),
				'submit_value'    => 'Отправить на модерацию',
				'updated_message' => 'Стория отправлена на модерацию',
			)
		);
		?>
	<?php else : ?>
		<p class="gal-lim">Подайте заявку — редакция соберёт сторию из ваших материалов (фото приложите в описании или передайте редакции).</p>
		<form method="post">
			<div class="f"><label>Название стории</label><input name="story_title" required></div>
			<div class="f"><label>Что показать (описание)</label><textarea name="story_desc" rows="3"></textarea></div>
			<?php wp_nonce_field( 'bc_story', 'bc_story_nonce' ); ?>
			<button class="btn btn-terra" type="submit">Подать заявку</button>
		</form>
	<?php endif; ?>
</div>

<div class="panel">
	<h2>Мои заявки и стории</h2>
	<?php if ( ! $my_stories ) : ?><p class="found">Сторий пока нет.</p><?php else : ?>
	<table class="inv-table">
		<thead><tr><th>Стория</th><th>Статус</th><th>Дата</th></tr></thead>
		<tbody>
		<?php foreach ( $my_stories as $s ) : $st = get_post_status( $s->ID ); ?>
			<tr>
				<td><?php echo esc_html( $s->post_title ); ?></td>
				<td><span class="st <?php echo 'publish' === $st ? 'ok' : ( 'pending' === $st ? 'wait' : 'reject' ); ?>"><?php echo esc_html( $st ); ?></span></td>
				<td><?php echo get_the_date( 'd.m.Y', $s->ID ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
