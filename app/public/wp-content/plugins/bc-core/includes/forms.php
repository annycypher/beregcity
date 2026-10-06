<?php
/**
 * Формы (Этап 6.2): «Разместить рекламу» → email + Telegram (honeypot).
 *
 * Telegram — только при константах BC_TG_BOT_TOKEN/BC_TG_CHAT_ID (на проде);
 * на Local внешних запросов нет (D11).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_reklama_rewrite() {
	add_rewrite_rule( '^reklama/?$', 'index.php?bc_form=reklama', 'top' );
}
add_action( 'init', 'bc_reklama_rewrite' );

function bc_form_query_var( $vars ) {
	$vars[] = 'bc_form';
	return $vars;
}
add_filter( 'query_vars', 'bc_form_query_var' );

function bc_send_telegram( $text ) {
	$token = defined( 'BC_TG_BOT_TOKEN' ) ? BC_TG_BOT_TOKEN : '';
	$chat  = defined( 'BC_TG_CHAT_ID' ) ? BC_TG_CHAT_ID : '';
	if ( ! $token || ! $chat ) {
		return false; // Local: внешних запросов нет
	}
	$url  = 'https://api.telegram.org/bot' . $token . '/sendMessage';
	$resp = wp_remote_post(
		$url,
		array(
			'timeout' => 10,
			'body'    => array( 'chat_id' => $chat, 'text' => $text ),
		)
	);
	return ! is_wp_error( $resp );
}

function bc_reklama_template_redirect() {
	if ( 'reklama' !== get_query_var( 'bc_form' ) ) {
		return;
	}
	$msg = '';
	$err = '';

	if ( isset( $_POST['bc_reklama_nonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_reklama_nonce'] ) ), 'bc_reklama' ) ) {
		if ( ! empty( $_POST['website'] ) ) {
			$msg = 'Заявка отправлена.'; // honeypot — тихий «успех»
		} else {
			$name    = sanitize_text_field( wp_unslash( $_POST['name'] ?? '' ) );
			$contact = sanitize_text_field( wp_unslash( $_POST['contact'] ?? '' ) );
			$text    = sanitize_textarea_field( wp_unslash( $_POST['message'] ?? '' ) );
			if ( ! $name || ! $contact ) {
				$err = 'Укажите имя и контакт.';
			} else {
				$body = "Заявка «Разместить рекламу»\nИмя: {$name}\nКонтакт: {$contact}\nСообщение: {$text}";
				wp_mail( get_option( 'admin_email' ), 'БерегСити: заявка на рекламу', $body );
				bc_send_telegram( $body );
				$msg = 'Заявка отправлена — свяжемся с вами.';
			}
		}
	}

	bc_render_reklama_form( $msg, $err );
	exit;
}
add_action( 'template_redirect', 'bc_reklama_template_redirect' );

function bc_render_reklama_form( $msg = '', $err = '' ) {
	get_header();
	?>
	<div class="wrap">
		<div class="panel" style="max-width:520px;margin:36px auto">
			<h2>Разместить рекламу</h2>
			<?php if ( $msg ) : ?><div style="background:#f1f5ec;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#2b332e"><?php echo esc_html( $msg ); ?></div><?php endif; ?>
			<?php if ( $err ) : ?><div style="background:#fbeae5;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#b0755c"><?php echo esc_html( $err ); ?></div><?php endif; ?>
			<form method="post">
				<div class="f"><label>Имя / название организации</label><input name="name" required></div>
				<div class="f"><label>Контакт (e-mail или телефон)</label><input name="contact" required></div>
				<div class="f"><label>Что хотите разместить</label><textarea name="message" rows="4"></textarea></div>
				<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off">
				<?php wp_nonce_field( 'bc_reklama', 'bc_reklama_nonce' ); ?>
				<button class="btn btn-terra" type="submit">Отправить заявку</button>
			</form>
		</div>
	</div>
	<?php
	get_footer();
}
