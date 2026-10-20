<?php
/**
 * Вкладка «Мои сторис» ЛК (Этап 5.4): режимы ВЫКЛ/Заявки/Самостоятельные.
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

// Лимиты: сколько сторис организация держит на модерации и в публикации.
$limits    = function_exists( 'bc_story_limits' ) ? bc_story_limits() : array( 'pending' => 1, 'publish' => 3 );
$pending   = get_posts( array( 'post_type' => 'stories', 'author' => $user->ID, 'post_status' => 'pending', 'numberposts' => -1, 'fields' => 'ids' ) );
$published = get_posts( array( 'post_type' => 'stories', 'author' => $user->ID, 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
$can_upload = count( $pending ) < (int) $limits['pending'] && count( $published ) < (int) $limits['publish'];

if ( isset( $_GET['bc_sent'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
	$ok = 'Фото отправлено на модерацию — редакция проверит и опубликует.';
}

if ( isset( $_POST['bc_story_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_story_nonce'] ) ), 'bc_story' ) ) {
	$title = sanitize_text_field( wp_unslash( $_POST['story_title'] ?? '' ) );
	$desc  = sanitize_textarea_field( wp_unslash( $_POST['story_desc'] ?? '' ) );
	if ( ! $title ) {
		$err = 'Укажите название сторис.';
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
		wp_mail( get_option( 'admin_email' ), 'БерегСити: заявка на сторис', 'Организация просит сторис: «' . $title . '». Описание: ' . $desc );
		$ok = 'Заявка отправлена — редакция соберёт сторис из ваших материалов.';
	}
}

$my_stories = get_posts( array( 'post_type' => 'stories', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => -1 ) );
?>
<div class="panel">
	<h2>Мои сторис</h2>
	<?php if ( $err ) : ?><div style="background:#fbeae5;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#b0755c"><?php echo esc_html( $err ); ?></div><?php endif; ?>
	<?php if ( $ok ) : ?><div style="background:#f1f5ec;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#2b332e"><?php echo esc_html( $ok ); ?></div><?php endif; ?>

	<?php if ( 'self' === $mode && function_exists( 'acf_form' ) ) : ?>
		<p class="gal-lim">Одно вертикальное фото: <?php echo esc_html( function_exists( 'bc_hint' ) ? bc_hint( 'story_slide' ) : '1080×1920 (вертикаль)' ); ?>. Фото не обрезается — в рамке 9:16 края заполнит размытая подложка.</p>
		<?php if ( ! $can_upload ) : ?>
			<div class="hint" style="color:#8f6a33">Лимит: <?php echo (int) $limits['pending']; ?> на модерации и до <?php echo (int) $limits['publish']; ?> опубликованных. Дождитесь проверки редакции.</div>
		<?php else : ?>
			<?php
			acf_form(
				array(
					'post_id'            => 'new_post',
					'new_post'           => array(
						'post_type'   => 'stories',
						'post_status' => 'pending',
						'post_author' => $user->ID,
						'post_title'  => get_the_title( $org_id ) . ' — ' . date_i18n( 'd.m.Y' ),
					),
					'fields'             => array( 'field_bc_story_image', 'field_bc_story_caption', 'field_bc_story_link', 'field_bc_story_btn' ),
					'submit_value'       => 'Отправить на модерацию',
					'return'             => add_query_arg( 'bc_sent', 1, home_url( '/kabinet/stories/' ) ),
					'html_before_fields' => '<div class="hint">Рамка показа — 9:16 (вертикаль). Подпись, ссылка и кнопка — по желанию.</div>',
				)
			);
			?>
		<?php endif; ?>
	<?php else : ?>
		<p class="gal-lim">Подайте заявку — редакция соберёт сторис из ваших материалов (фото приложите в описании или передайте редакции).</p>
		<form method="post">
			<div class="f"><label>Название сторис</label><input name="story_title" required><div class="hint">Внутреннее название — что будет в сторис.</div></div>
			<div class="f"><label>Что показать (описание)</label><textarea name="story_desc" rows="3"></textarea><div class="hint"><?php echo esc_html( bc_hint( 'story_slide' ) ); ?>. Опишите идею — редакция соберёт сторис.</div></div>
			<?php wp_nonce_field( 'bc_story', 'bc_story_nonce' ); ?>
			<button class="btn btn-terra" type="submit">Подать заявку</button>
		</form>
	<?php endif; ?>
</div>

<div class="panel">
	<h2>Мои заявки и сторис</h2>
	<?php if ( ! $my_stories ) : ?><p class="found">Сторис пока нет.</p><?php else : ?>
	<table class="inv-table">
		<thead><tr><th>Сторис</th><th>Статус</th><th>Дата</th></tr></thead>
		<tbody>
		<?php
		$bc_labels = array( 'publish' => 'Опубликовано', 'pending' => 'На модерации', 'draft' => 'Отклонено' );
		foreach ( $my_stories as $s ) :
			$st  = get_post_status( $s->ID );
			$img = function_exists( 'bc_story_single_slide' ) ? bc_story_single_slide( $s->ID ) : null;
			?>
			<tr>
				<td>
					<?php if ( $img && ! empty( $img['thumb'] ) ) : ?>
						<img src="<?php echo esc_url( $img['thumb'] ); ?>" alt="" style="width:36px;height:64px;object-fit:cover;border-radius:4px;vertical-align:middle;margin-right:8px">
					<?php endif; ?>
					<?php echo esc_html( $s->post_title ); ?>
					<?php if ( 'draft' === $st && get_post_meta( $s->ID, 'bc_reject_reason', true ) ) : ?>
						<div class="hint">Причина: <?php echo esc_html( get_post_meta( $s->ID, 'bc_reject_reason', true ) ); ?></div>
					<?php endif; ?>
				</td>
				<td><span class="st <?php echo 'publish' === $st ? 'ok' : ( 'pending' === $st ? 'wait' : 'reject' ); ?>"><?php echo esc_html( isset( $bc_labels[ $st ] ) ? $bc_labels[ $st ] : $st ); ?></span></td>
				<td><?php echo get_the_date( 'd.m.Y', $s->ID ); ?></td>
			</tr>
		<?php endforeach; ?>
		</tbody>
	</table>
	<?php endif; ?>
</div>
