<?php
/**
 * Маршруты личного кабинета (Этап 3а.2): /kabinet/ и /kabinet/{tab}/.
 *
 * Гость → форма входа (auth-card «Вход»); org_manager → кабинет (шаблон темы).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_cabinet_rewrite() {
	add_rewrite_rule( '^kabinet/?$', 'index.php?bc_lk=cabinet', 'top' );
	add_rewrite_rule( '^kabinet/(?!register/)([^/]+)/?$', 'index.php?bc_lk=cabinet&bc_tab=$matches[1]', 'top' );
}
add_action( 'init', 'bc_cabinet_rewrite' );

function bc_lk_tab_query_var( $vars ) {
	$vars[] = 'bc_tab';
	return $vars;
}
add_filter( 'query_vars', 'bc_lk_tab_query_var' );

function bc_cabinet_template_redirect() {
	if ( 'cabinet' !== get_query_var( 'bc_lk' ) ) {
		return;
	}

	if ( isset( $_POST['bc_login_nonce'] ) ) {
		bc_process_login();
	}

	if ( ! is_user_logged_in() ) {
		bc_render_login_form();
		exit;
	}

	$user = wp_get_current_user();
	if ( ! in_array( 'org_manager', (array) $user->roles, true ) && ! in_array( 'administrator', (array) $user->roles, true ) ) {
		wp_safe_redirect( home_url( '/' ) );
		exit;
	}
}
add_action( 'template_redirect', 'bc_cabinet_template_redirect' );

function bc_process_login() {
	global $bc_login_error;
	if ( ! isset( $_POST['bc_login_nonce'] ) || ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_login_nonce'] ) ), 'bc_login' ) ) {
		return;
	}
	$creds = array(
		'user_login'    => sanitize_text_field( wp_unslash( $_POST['log'] ?? '' ) ),
		'user_password' => wp_unslash( $_POST['pwd'] ?? '' ),
		'remember'      => true,
	);
	$user = wp_signon( $creds, false );
	if ( is_wp_error( $user ) ) {
		$bc_login_error = 'Неверный e-mail или пароль.';
	} else {
		wp_safe_redirect( home_url( '/kabinet/' ) );
		exit;
	}
}

function bc_cabinet_template( $template ) {
	if ( 'cabinet' === get_query_var( 'bc_lk' ) && is_user_logged_in() ) {
		$tpl = get_stylesheet_directory() . '/page-kabinet.php';
		if ( file_exists( $tpl ) ) {
			return $tpl;
		}
	}
	return $template;
}
add_filter( 'template_include', 'bc_cabinet_template' );

function bc_render_login_form() {
	global $bc_login_error;
	get_header();
	?>
	<div class="wrap">
		<div class="auth-row" style="grid-template-columns:1fr;max-width:420px;margin:36px auto">
			<div class="auth-card">
				<h3>Вход</h3>
				<?php if ( ! empty( $bc_login_error ) ) : ?><div style="background:#fbeae5;border-radius:10px;padding:10px 14px;margin-bottom:14px;font-size:13px;color:#b0755c"><?php echo esc_html( $bc_login_error ); ?></div><?php endif; ?>
				<form method="post">
					<div class="f"><label>E-mail</label><input type="email" name="log" required></div>
					<div class="f"><label>Пароль</label><input type="password" name="pwd" required></div>
					<?php wp_nonce_field( 'bc_login', 'bc_login_nonce' ); ?>
					<button class="btn btn-terra" type="submit" style="width:100%">Войти</button>
					<div class="auth-note">Нет аккаунта? <a href="<?php echo esc_url( home_url( '/kabinet/register/' ) ); ?>">Зарегистрировать организацию</a></div>
				</form>
			</div>
		</div>
	</div>
	<?php
	get_footer();
}

/**
 * Прогресс заполнения карточки (cabinet.md §17, Этап 3а.2).
 *
 * @param int $post_id ID организации.
 * @return array{percent:int, missing:array}
 */
function bc_progress( $post_id ) {
	$gallery = get_field( 'field_bc_gallery', $post_id );
	$items   = array(
		'logo'     => array( 'label' => 'Загрузить логотип', 'weight' => 10, 'tab' => 'photo', 'ok' => has_post_thumbnail( $post_id ) ),
		'photos'   => array( 'label' => 'Загрузить фото (3)', 'weight' => 15, 'tab' => 'photo', 'ok' => count( (array) $gallery ) >= 3 ),
		'desc'     => array( 'label' => 'Заполнить описание', 'weight' => 15, 'tab' => 'card', 'ok' => ( function_exists( 'mb_strlen' ) ? mb_strlen( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) ) : strlen( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ) ) ) >= 1000 ),
		'features' => array( 'label' => 'Добавить снипеты', 'weight' => 10, 'tab' => 'card', 'ok' => count( get_the_terms( $post_id, 'features' ) ?: array() ) >= 5 ),
		'social'   => array( 'label' => 'Добавить соцсети', 'weight' => 10, 'tab' => 'card', 'ok' => (bool) ( get_field( 'field_bc_whatsapp', $post_id ) || get_field( 'field_bc_telegram', $post_id ) || get_field( 'field_bc_vk', $post_id ) ) ),
		'site'     => array( 'label' => 'Указать сайт', 'weight' => 10, 'tab' => 'card', 'ok' => (bool) get_field( 'field_bc_website', $post_id ) ),
		'schedule' => array( 'label' => 'Расписать график', 'weight' => 10, 'tab' => 'card', 'ok' => (bool) get_field( 'field_bc_schedule', $post_id ) ),
		'keywords' => array( 'label' => 'Добавить ключевые слова', 'weight' => 10, 'tab' => 'card', 'ok' => (bool) get_field( 'field_bc_keywords', $post_id ) ),
		'verified' => array( 'label' => 'Подтверждение', 'weight' => 10, 'tab' => 'card', 'ok' => (bool) get_field( 'field_bc_is_verified', $post_id ) ),
	);
	$percent = 0;
	$missing = array();
	foreach ( $items as $it ) {
		if ( $it['ok'] ) {
			$percent += $it['weight'];
		} else {
			$missing[] = $it;
		}
	}
	return array( 'percent' => $percent, 'missing' => $missing );
}
