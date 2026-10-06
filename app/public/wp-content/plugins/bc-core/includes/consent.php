<?php
/**
 * Юридический контур: согласия + cookie-баннер (шаг «3а-юри», D37).
 *
 * - bc_consent_field()          — обязательный чекбокс согласия на ПДн.
 * - bc_consent_newsletter_field() — опциональный чекбокс рассылки.
 * - bc_consent_check()          — валидация (без чекбокса — ошибка формы, НЕ silent).
 * - bc_consent_log()            — лог согласия (версия политики, дата-время, IP).
 * - bc_cookie_banner()          — cookie-баннер (гостям, vanilla JS ≤15 строк).
 * - bc_jur_marker()             — [ЮР-ЗАПОЛНИТЬ] → жёлтый бейдж (только админ).
 * - bc_disclaimer_short()       — краткий дисклеймер каталога.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BC_POLICY_VERSION', '1.0' );

/**
 * Обязательный чекбокс согласия на обработку ПДн.
 */
function bc_consent_field() {
	echo '<label class="consent"><input type="checkbox" name="bc_consent" value="1"> Соглашаюсь с обработкой персональных данных на условиях <a href="/privacy/" target="_blank" rel="noopener">Политики конфиденциальности</a></label>';
}

/**
 * Опциональный чекбокс рассылки (отдельное согласие).
 */
function bc_consent_newsletter_field() {
	echo '<label class="consent"><input type="checkbox" name="bc_newsletter" value="1"> Хочу получать новости района</label>';
}

/**
 * Валидация согласия: false — чекбокс не отмечен.
 */
function bc_consent_check() {
	return ! empty( $_POST['bc_consent'] );
}

/**
 * Лог факта согласия: контекст, ref_id, версия политики, дата-время, IP.
 */
function bc_consent_log( $context, $ref_id = 0, $extra = array() ) {
	$entry = array(
		'context' => $context,
		'ref_id'  => (int) $ref_id,
		'version' => BC_POLICY_VERSION,
		'date'    => current_time( 'mysql' ),
		'ip'      => function_exists( 'bc_client_ip' ) ? bc_client_ip() : '',
	);
	if ( $extra ) {
		$entry['extra'] = $extra;
	}
	$log = get_option( 'bc_consent_log', array() );
	if ( ! is_array( $log ) ) {
		$log = array();
	}
	$log[] = $entry;
	if ( count( $log ) > 500 ) {
		$log = array_slice( $log, -500 );
	}
	update_option( 'bc_consent_log', $log, false );
}

/**
 * Cookie-баннер (гостям, до клика). Залогиненным не показываем.
 */
function bc_cookie_banner() {
	if ( is_admin() || is_user_logged_in() ) {
		return;
	}
	?>
	<div class="cookie-banner" id="bc-cookie" role="dialog" aria-label="Уведомление о cookies">
		<span>Мы используем cookies и Яндекс Метрику. Продолжая пользоваться сайтом, вы соглашаетесь с <a href="/privacy/">Политикой конфиденциальности</a>.</span>
		<button class="btn btn-terra" id="bc-cookie-ok" type="button">Хорошо</button>
	</div>
	<script>
	(function(){
		var b=document.getElementById('bc-cookie');
		if(!b)return;
		var ok=localStorage.getItem('bc_cookie_ok');
		if(ok&&Date.now()-parseInt(ok,10)<31536000000){b.remove();return;}
		document.getElementById('bc-cookie-ok').addEventListener('click',function(){
			localStorage.setItem('bc_cookie_ok',String(Date.now()));
			b.remove();
		});
	})();
	</script>
	<?php
}
add_action( 'wp_footer', 'bc_cookie_banner' );

/**
 * Маркер [ЮР-ЗАПОЛНИТЬ: …] → жёлтый бейдж только для админа.
 * Срабатывает только на страницах /privacy/ и /terms/.
 */
function bc_jur_marker( $content ) {
	if ( ! is_page( array( 'privacy', 'terms' ) ) ) {
		return $content;
	}
	$pattern = '/\[ЮР-ЗАПОЛНИТЬ:\s*([^\]]+)\]/u';
	if ( current_user_can( 'manage_options' ) ) {
		$content = preg_replace_callback(
			$pattern,
			function ( $m ) {
				return '<span class="jur-badge">⚠ ЮР-ЗАПОЛНИТЬ: ' . esc_html( $m[1] ) . '</span>';
			},
			$content
		);
	} else {
		$content = preg_replace( $pattern, '', $content );
	}
	return $content;
}
add_filter( 'the_content', 'bc_jur_marker', 20 );

/**
 * Краткий дисклеймер каталога (1 строка, мелкий muted).
 */
function bc_disclaimer_short() {
	echo '<p class="bc-disclaimer">Информация предоставлена организацией. Актуальность уточняйте по телефону.</p>';
}
