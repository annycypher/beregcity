<?php
/**
 * Регистрация организации (Этап 3а.1): /kabinet/register.
 *
 * Защита (1б.3/D19): nonce + honeypot + время заполнения + rate-limit 5/час/IP.
 * Email-верификация: ссылка-активация; карточка draft → pending после активации.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_register_rewrite() {
	add_rewrite_rule( '^kabinet/register/?$', 'index.php?bc_lk=register', 'top' );
}
add_action( 'init', 'bc_register_rewrite' );

function bc_lk_query_vars( $vars ) {
	$vars[] = 'bc_lk';
	$vars[] = 'bc_reg';
	$vars[] = 'bc_key';
	return $vars;
}
add_filter( 'query_vars', 'bc_lk_query_vars' );

function bc_client_ip() {
	foreach ( array( 'HTTP_X_FORWARDED_FOR', 'HTTP_CLIENT_IP', 'REMOTE_ADDR' ) as $k ) {
		if ( ! empty( $_SERVER[ $k ] ) ) {
			$ip = trim( explode( ',', sanitize_text_field( wp_unslash( $_SERVER[ $k ] ) ) )[0] );
			if ( $ip ) {
				return $ip;
			}
		}
	}
	return '0.0.0.0';
}

function bc_lk_template_redirect() {
	if ( 'register' !== get_query_var( 'bc_lk' ) ) {
		return;
	}
	if ( is_user_logged_in() ) {
		wp_safe_redirect( home_url( '/kabinet/' ) );
		exit;
	}

	$msg = '';
	$err = '';

	if ( isset( $_GET['bc_reg'], $_GET['bc_key'] ) ) {
		$r   = bc_process_verification( sanitize_text_field( wp_unslash( $_GET['bc_reg'] ) ), sanitize_text_field( wp_unslash( $_GET['bc_key'] ) ) );
		$msg = $r['msg'];
		$err = $r['err'];
	}

	if ( isset( $_POST['bc_register_nonce'] ) ) {
		$r   = bc_process_registration();
		$msg = $r['msg'];
		$err = $r['err'];
	}

	bc_render_register_form( $msg, $err );
	exit;
}
add_action( 'template_redirect', 'bc_lk_template_redirect' );

function bc_process_registration() {
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_register_nonce'] ) ), 'bc_register' ) ) {
		return array( 'msg' => '', 'err' => 'Сессия устарела, обновите страницу.' );
	}

	$as = bc_antispam_check( 'register' );
	if ( BC_AS_SILENT === $as ) {
		return array( 'msg' => 'Спасибо! Заявка получена.', 'err' => '' ); // silent-reject: бот, письмо не шлём
	}
	if ( BC_AS_LIMIT === $as ) {
		return array( 'msg' => '', 'err' => 'Слишком много отправок, попробуйте позже.' );
	}

	if ( ! bc_consent_check() ) {
		return array( 'msg' => '', 'err' => 'Необходимо согласие на обработку персональных данных.' );
	}

	$org_name = sanitize_text_field( wp_unslash( $_POST['org_name'] ?? '' ) );
	$email    = sanitize_email( wp_unslash( $_POST['email'] ?? '' ) );
	$phone    = sanitize_text_field( wp_unslash( $_POST['phone'] ?? '' ) );
	$cat      = isset( $_POST['org_cat'] ) ? absint( $_POST['org_cat'] ) : 0;
	$pass     = isset( $_POST['pass'] ) ? wp_unslash( $_POST['pass'] ) : '';

	if ( ! $org_name || ! $email || ! $pass ) {
		return array( 'msg' => '', 'err' => 'Заполните название, e-mail и пароль.' );
	}
	if ( email_exists( $email ) ) {
		return array( 'msg' => '', 'err' => 'Этот e-mail уже зарегистрирован.' );
	}

	$user_id = wp_insert_user(
		array(
			'user_login' => $email,
			'user_email' => $email,
			'user_pass'  => $pass,
			'role'       => 'org_manager',
		)
	);
	if ( is_wp_error( $user_id ) ) {
		return array( 'msg' => '', 'err' => 'Не удалось создать аккаунт.' );
	}

	$post_id = wp_insert_post(
		array(
			'post_type'   => 'organizations',
			'post_title'  => $org_name,
			'post_status' => 'draft',
			'post_author' => $user_id,
		)
	);
	if ( $cat ) {
		wp_set_object_terms( $post_id, array( $cat ), 'bc_cat' );
	}
	if ( function_exists( 'update_field' ) ) {
		update_field( 'field_bc_phone', $phone, $post_id );
	}
	update_post_meta( $post_id, 'bc_reg_ip', bc_client_ip() );
	bc_consent_log( 'register', $post_id );
	update_post_meta( $post_id, 'bc_consent_version', BC_POLICY_VERSION );
	if ( ! empty( $_POST['bc_newsletter'] ) ) {
		update_user_meta( $user_id, 'bc_newsletter', 1 );
	}

	$vkey = wp_generate_password( 32, false );
	set_transient( 'bc_verify_' . $user_id, $vkey, DAY_IN_SECONDS );
	$link = add_query_arg( array( 'bc_reg' => 'verify', 'bc_key' => $user_id . ':' . $vkey ), home_url( '/kabinet/register/' ) );
	wp_mail( $email, 'БерегСити: подтвердите e-mail', 'Перейдите по ссылке для подтверждения: ' . $link );
	wp_mail( get_option( 'admin_email' ), 'БерегСити: новая заявка на организацию', 'Организация «' . $org_name . '» ожидает подтверждения e-mail.' );

	return array( 'msg' => 'Аккаунт создан. Мы отправили письмо для подтверждения e-mail — перейдите по ссылке.', 'err' => '' );
}
function bc_process_verification( $action, $key ) {
	if ( 'verify' !== $action || ! $key ) {
		return array( 'msg' => '', 'err' => '' );
	}
	$parts = explode( ':', $key );
	if ( 2 !== count( $parts ) ) {
		return array( 'msg' => '', 'err' => 'Неверная ссылка активации.' );
	}
	$user_id = (int) $parts[0];
	$vkey    = sanitize_text_field( $parts[1] );
	$stored  = get_transient( 'bc_verify_' . $user_id );
	if ( ! $stored || ! hash_equals( $stored, $vkey ) ) {
		return array( 'msg' => '', 'err' => 'Ссылка активации недействительна или истекла.' );
	}
	delete_transient( 'bc_verify_' . $user_id );

	$post = get_posts(
		array(
			'post_type'   => 'organizations',
			'post_status' => 'draft',
			'author'      => $user_id,
			'numberposts' => 1,
		)
	);
	if ( $post ) {
		wp_update_post( array( 'ID' => $post[0]->ID, 'post_status' => 'pending' ) );
		wp_mail( get_option( 'admin_email' ), 'БерегСити: карточка на утверждение', 'Организация «' . get_the_title( $post[0]->ID ) . '» подтвердила e-mail и ждёт утверждения.' );
	}

	return array( 'msg' => 'E-mail подтверждён. Карточка отправлена на проверку — опубликуем после одобрения.', 'err' => '' );
}

function bc_render_register_form( $msg = '', $err = '' ) {
	get_header();
	?>
	<div class="wrap">
		<div class="auth-row" style="grid-template-columns:1fr;max-width:460px;margin:36px auto">
			<div class="auth-card">
				<h3>Регистрация организации</h3>
				<?php if ( $msg ) : ?><div style="background:#f1f5ec;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#2b332e"><?php echo esc_html( $msg ); ?></div><?php endif; ?>
				<?php if ( $err ) : ?><div style="background:#fbeae5;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#b0755c"><?php echo esc_html( $err ); ?></div><?php endif; ?>
				<form method="post">
					<div class="f"><label>Название организации</label><input name="org_name" required></div>
					<div class="f"><label>Категория</label><select name="org_cat"><?php foreach ( get_terms( array( 'taxonomy' => 'bc_cat', 'hide_empty' => false ) ) as $t ) : ?><option value="<?php echo (int) $t->term_id; ?>"><?php echo esc_html( $t->name ); ?></option><?php endforeach; ?></select></div>
					<div class="f"><label>E-mail</label><input type="email" name="email" required></div>
					<div class="f"><label>Телефон</label><input name="phone" placeholder="+7…"></div>
					<div class="f"><label>Пароль</label><input type="password" name="pass" minlength="8" required></div>
					<?php bc_consent_field(); ?>
					<?php bc_consent_newsletter_field(); ?>
					<?php bc_antispam_field( 'register' ); ?>
					<?php wp_nonce_field( 'bc_register', 'bc_register_nonce' ); ?>
					<button class="btn btn-terra" type="submit" style="width:100%">Создать аккаунт</button>
					<div class="auth-note">Подтвердите e-mail по ссылке из письма. Карточка публикуется после проверки администратором</div>
				</form>
			</div>
		</div>
	</div>
	<?php
	get_footer();
}
