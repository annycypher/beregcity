<?php
/**
 * Заявки на права над витринной карточкой (шаг «3б-клейминг», D38).
 *
 * - bc_claims_table()        — имя таблицы заявок.
 * - bc_create_claims_table() — создание таблицы wp_bc_claims (dbDelta при активации).
 * - bc_is_claimed()          — карточка клейменная (автор — org_manager).
 * - bc_claim_roles()         — фиксированный список ролей заявителя.
 * - [bc_claim]               — кнопка «Это ваша организация? Заявить права» + форма.
 * - Обработка POST (nonce / bc_antispam_* / bc_consent_*) + верификация e-mail.
 * - Письма — через bc_mail().
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Имя таблицы заявок.
 *
 * @return string
 */
function bc_claims_table() {
	global $wpdb;
	return $wpdb->prefix . 'bc_claims';
}

/**
 * Создание таблицы wp_bc_claims при активации плагина (dbDelta).
 */
function bc_create_claims_table() {
	global $wpdb;
	$table   = bc_claims_table();
	$charset = $wpdb->get_charset_collate();
	$sql     = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		post_id bigint(20) unsigned NOT NULL,
		user_id bigint(20) unsigned NOT NULL DEFAULT 0,
		name varchar(190) NOT NULL,
		email varchar(190) NOT NULL,
		phone varchar(40) NOT NULL DEFAULT '',
		role varchar(30) NOT NULL DEFAULT '',
		comment text,
		status varchar(20) NOT NULL DEFAULT 'new',
		token varchar(64) DEFAULT NULL,
		ip varchar(45) NOT NULL DEFAULT '',
		created_at datetime NOT NULL,
		PRIMARY KEY  (id),
		KEY post_id (post_id),
		KEY email (email),
		KEY status (status)
		) {$charset};";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}

/**
 * Карточка уже клейменная? (автор — пользователь с ролью org_manager)
 *
 * @param int $post_id ID организации.
 * @return bool
 */
function bc_is_claimed( $post_id ) {
	$author_id = (int) get_post_field( 'post_author', $post_id );
	if ( ! $author_id ) {
		return false;
	}
	$user = get_userdata( $author_id );
	return $user && in_array( 'org_manager', (array) $user->roles, true );
}

/**
 * Варианты роли заявителя (фиксированный список: ключ => подпись).
 *
 * @return array
 */
function bc_claim_roles() {
	return array(
		'owner'    => 'Владелец',
		'director' => 'Директор',
		'marketer' => 'Маркетолог',
	);
}

/**
 * Текст ответа по коду результата.
 *
 * @param string $code Код.
 * @return array|null array( 'ok|err', 'текст' ).
 */
function bc_claim_message( $code ) {
	$map = array(
		'done'       => array( 'ok', 'Спасибо! Заявка отправлена. Мы прислали письмо — подтвердите e-mail по ссылке из него.' ),
		'verified'   => array( 'ok', 'E-mail подтверждён. Заявка на проверке: позвоним на номер, указанный в карточке организации.' ),
		'verifyfail' => array( 'err', 'Ссылка подтверждения недействительна или устарела.' ),
		'dup'        => array( 'err', 'Заявка по этой организации с таким e-mail уже отправлена — ожидайте звонка.' ),
		'limit'      => array( 'err', 'Слишком много отправок, попробуйте позже.' ),
		'consent'    => array( 'err', 'Отметьте согласие на обработку персональных данных.' ),
		'invalid'    => array( 'err', 'Заполните имя и корректный e-mail.' ),
		'session'    => array( 'err', 'Сессия устарела, обновите страницу и попробуйте снова.' ),
	);
	return isset( $map[ $code ] ) ? $map[ $code ] : null;
}

/**
 * [bc_claim] — кнопка «Заявить права» + форма заявки.
 *
 * Скрыт, если карточка уже клейменная (автор — org_manager) или текущий
 * пользователь — владелец карточки.
 *
 * @return string HTML.
 */
function bc_claim_shortcode() {
	if ( ! is_singular( 'organizations' ) ) {
		return '';
	}
	$post_id = (int) get_the_ID();
	if ( ! $post_id ) {
		return '';
	}
	if ( bc_is_claimed( $post_id ) ) {
		return '';
	}
	if ( is_user_logged_in() && get_current_user_id() === (int) get_post_field( 'post_author', $post_id ) ) {
		return '';
	}

	$code    = isset( $_GET['bc_claim'] ) && 'verify' !== $_GET['bc_claim'] ? sanitize_key( wp_unslash( $_GET['bc_claim'] ) ) : '';
	$message = $code ? bc_claim_message( $code ) : null;

	ob_start();
	?>
	<div class="claim" id="bc-claim">
		<?php if ( $message ) : ?>
			<div class="claim-msg <?php echo esc_attr( $message[0] ); ?>"><?php echo esc_html( $message[1] ); ?></div>
		<?php endif; ?>
		<details<?php echo ( $message && 'err' === $message[0] ) ? ' open' : ''; ?>>
			<summary class="btn btn-glass" style="width:100%">Это ваша организация? Заявить права</summary>
			<form method="post" class="claim-form" style="margin-top:14px">
				<p class="hint">Заполните заявку — позвоним на номер, указанный в карточке организации, и подтвердим права.</p>
				<div class="f"><label>Ваше имя <b>*</b></label><input name="bc_claim_name" required></div>
				<div class="f"><label>E-mail <b>*</b></label><input type="email" name="bc_claim_email" required></div>
				<div class="f"><label>Телефон</label><input name="bc_claim_phone" placeholder="+7…"></div>
				<div class="f"><label>Кем вы приходитесь организации</label>
					<select name="bc_claim_role">
						<?php foreach ( bc_claim_roles() as $k => $label ) : ?>
							<option value="<?php echo esc_attr( $k ); ?>"><?php echo esc_html( $label ); ?></option>
						<?php endforeach; ?>
					</select>
				</div>
				<div class="f"><label>Комментарий</label><textarea name="bc_claim_comment" rows="3"></textarea></div>
				<?php bc_consent_field(); ?>
				<?php bc_antispam_field( 'claim' ); ?>
				<?php wp_nonce_field( 'bc_claim', 'bc_claim_nonce' ); ?>
				<button class="btn btn-terra" type="submit">Отправить заявку</button>
			</form>
		</details>
	</div>
	<?php
	return ob_get_clean();
}
add_shortcode( 'bc_claim', 'bc_claim_shortcode' );

/**
 * Обработка ссылки верификации e-mail и отправки формы заявки.
 */
function bc_claim_template_redirect() {
	if ( isset( $_GET['bc_claim'], $_GET['key'] ) && 'verify' === sanitize_key( wp_unslash( $_GET['bc_claim'] ) ) ) {
		bc_claim_process_verify( sanitize_text_field( wp_unslash( $_GET['key'] ) ) );
		return;
	}

	if ( ! is_singular( 'organizations' ) || empty( $_POST['bc_claim_nonce'] ) ) {
		return;
	}
	$post_id = get_queried_object_id();
	$result  = bc_claim_process_submit( (int) $post_id );
	$link    = get_permalink( $post_id );
	if ( ! $link || is_wp_error( $link ) ) {
		$link = home_url( '/' );
	}
	wp_safe_redirect( add_query_arg( 'bc_claim', $result, $link ) . '#bc-claim' );
	exit;
}
add_action( 'template_redirect', 'bc_claim_template_redirect' );

/**
 * Обработка отправки формы заявки.
 *
 * @param int $post_id ID организации.
 * @return string Код результата (см. bc_claim_message).
 */
function bc_claim_process_submit( $post_id ) {
	global $wpdb;

	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_claim_nonce'] ) ), 'bc_claim' ) ) {
		return 'session';
	}

	$as = bc_antispam_check( 'claim' );
	if ( BC_AS_SILENT === $as ) {
		return 'done';
	}
	if ( BC_AS_LIMIT === $as ) {
		return 'limit';
	}
	if ( ! bc_consent_check() ) {
		return 'consent';
	}

	$name  = sanitize_text_field( wp_unslash( $_POST['bc_claim_name'] ?? '' ) );
	$email = sanitize_email( wp_unslash( $_POST['bc_claim_email'] ?? '' ) );
	$phone = sanitize_text_field( wp_unslash( $_POST['bc_claim_phone'] ?? '' ) );
	$role  = sanitize_key( wp_unslash( $_POST['bc_claim_role'] ?? '' ) );
	$comm  = sanitize_textarea_field( wp_unslash( $_POST['bc_claim_comment'] ?? '' ) );

	if ( ! $name || ! is_email( $email ) ) {
		return 'invalid';
	}
	if ( ! array_key_exists( $role, bc_claim_roles() ) ) {
		$role = 'owner';
	}

	$table = bc_claims_table();

	$dup = $wpdb->get_var( $wpdb->prepare( "SELECT id FROM {$table} WHERE post_id = %d AND email = %s AND status IN ('new','verified') LIMIT 1", $post_id, $email ) );
	if ( $dup ) {
		return 'dup';
	}

	$token = bin2hex( random_bytes( 32 ) );
	$ip    = function_exists( 'bc_client_ip' ) ? bc_client_ip() : '';
	$ok    = $wpdb->insert(
		$table,
		array(
			'post_id'    => $post_id,
			'user_id'    => is_user_logged_in() ? get_current_user_id() : 0,
			'name'       => $name,
			'email'      => $email,
			'phone'      => $phone,
			'role'       => $role,
			'comment'    => $comm,
			'status'     => 'new',
			'token'      => $token,
			'ip'         => $ip,
			'created_at' => current_time( 'mysql' ),
		),
		array( '%d', '%d', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s', '%s' )
	);
	if ( ! $ok ) {
		return 'invalid';
	}
	$claim_id = (int) $wpdb->insert_id;

	if ( function_exists( 'bc_consent_log' ) ) {
		bc_consent_log( 'claim', $claim_id, array( 'post_id' => $post_id ) );
	}

	$verify = add_query_arg(
		array(
			'bc_claim' => 'verify',
			'key'      => $claim_id . ':' . $token,
		),
		home_url( '/' )
	);
	$title  = get_the_title( $post_id );

	bc_mail(
		$email,
		'БерегСити: подтвердите e-mail',
		"Вы заявили права на организацию «{$title}».\nПодтвердите e-mail по ссылке:\n{$verify}\n\nПосле подтверждения мы позвоним на номер, указанный в карточке организации, и подтвердим права."
	);
	bc_mail(
		get_option( 'admin_email' ),
		'БерегСити: новая заявка на права',
		"Карточка: {$title}\nЗаявитель: {$name}\nE-mail: {$email}\nТелефон: {$phone}\nРоль: {$role}\nКомментарий: {$comm}\n\nОткрыть заявки: " . admin_url( 'admin.php?page=bc-claims' )
	);

	return 'done';
}

/**
 * Верификация e-mail по ссылке из письма (одноразовый token).
 *
 * @param string $key Строка вида {id}:{token}.
 */
function bc_claim_process_verify( $key ) {
	global $wpdb;
	$parts = explode( ':', $key );
	if ( 2 !== count( $parts ) ) {
		wp_safe_redirect( add_query_arg( 'bc_claim', 'verifyfail', home_url( '/' ) ) );
		exit;
	}
	$id    = (int) $parts[0];
	$token = sanitize_text_field( $parts[1] );
	$table = bc_claims_table();
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $id ) );

	$fail = true;
	$link = home_url( '/' );
	if ( $row ) {
		$permalink = get_permalink( (int) $row->post_id );
		if ( $permalink && ! is_wp_error( $permalink ) ) {
			$link = $permalink;
		}
		if ( $row->token && 'new' === $row->status && hash_equals( (string) $row->token, $token ) ) {
			$wpdb->update( $table, array( 'status' => 'verified', 'token' => null ), array( 'id' => $id ) );
			bc_mail(
				$row->email,
				'БерегСити: e-mail подтверждён',
				'Спасибо! Заявка на права по организации «' . get_the_title( (int) $row->post_id ) . '» принята. Мы позвоним на номер, указанный в карточке организации, и подтвердим права.'
			);
			$fail = false;
		}
	}

	wp_safe_redirect( add_query_arg( 'bc_claim', $fail ? 'verifyfail' : 'verified', $link ) . '#bc-claim' );
	exit;
}

/**
 * Заявки в статусе new дольше 14 дней (для дайджеста админу — подшаг 1b).
 *
 * @return int
 */
function bc_claims_stale_count() {
	global $wpdb;
	$table = bc_claims_table();
	$limit = date( 'Y-m-d H:i:s', strtotime( current_time( 'mysql' ) . ' -14 days' ) );
	return (int) $wpdb->get_var( $wpdb->prepare( "SELECT COUNT(*) FROM {$table} WHERE status = 'new' AND created_at < %s", $limit ) );
}