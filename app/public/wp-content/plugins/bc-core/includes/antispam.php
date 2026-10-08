<?php
/**
 * Единый хелпер антиспама (микро-шаг «3а-фрод», D35).
 *
 * Используется ВСЕМИ публичными формами проекта:
 *   - bc_antispam_field()  — honeypot + скрытый токен формы (рендер);
 *   - bc_antispam_check()  — проверка на сохранении (silent-reject / rate-limit).
 *
 * Silent-reject: боту отвечаем 200 и «спасибо», письмо НЕ отправляем,
 * запись в БД НЕ создаём — фильтры не раскрываем.
 *
 * Автоблокировки нет: решение по каждой карточке всегда за админом.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

define( 'BC_AS_OK', 'ok' );
define( 'BC_AS_SILENT', 'silent' );
define( 'BC_AS_LIMIT', 'limit' );

/**
 * Токен формы: md5( form_id + IP + час ).
 * Перестраивается без обращения к БД; на проверке сверяется с текущим
 * и предыдущим часом (переход через границу часа не рвёт валидность).
 *
 * @param string $form_id Идентификатор формы (например 'register', 'reklama').
 * @return string
 */
function bc_antispam_token( $form_id ) {
	$ip = function_exists( 'bc_client_ip' ) ? bc_client_ip() : '0.0.0.0';
	return md5( $form_id . '|' . $ip . '|' . gmdate( 'YmdH' ) );
}

/**
 * Проверка токена: текущий час + предыдущий час.
 *
 * @param string $form_id Идентификатор формы.
 * @param string $token   Значение из скрытого поля.
 * @return bool
 */
function bc_antispam_token_valid( $form_id, $token ) {
	$ip    = function_exists( 'bc_client_ip' ) ? bc_client_ip() : '0.0.0.0';
	$valid = array(
		md5( $form_id . '|' . $ip . '|' . gmdate( 'YmdH' ) ),
		md5( $form_id . '|' . $ip . '|' . gmdate( 'YmdH', time() - HOUR_IN_SECONDS ) ),
	);
	return in_array( (string) $token, $valid, true );
}

/**
 * Вывод honeypot-поля и скрытого токена формы.
 * При рендере запоминает время (timestamp в transient по токену) —
 * для отсечки «быстрее 3 секунд» (бот).
 *
 * @param string $form_id Идентификатор формы.
 */
function bc_antispam_field( $form_id ) {
	$token = bc_antispam_token( $form_id );
	// Ставим метку времени только при первом рендере в этом часе:
	// повторные рендеры (в т.ч. ответ формы на POST) не сдвигают её.
	if ( false === get_transient( 'bc_as_ts_' . $token ) ) {
		set_transient( 'bc_as_ts_' . $token, time(), HOUR_IN_SECONDS );
	}
	echo '<input class="hp" type="text" name="website" tabindex="-1" autocomplete="off" aria-hidden="true">' . "\n";
	echo '<input type="hidden" name="bc_as_token" value="' . esc_attr( $token ) . '">' . "\n";
}

/**
 * Проверка формы на сохранении. Порядок: токен → honeypot → время → rate-limit.
 *
 * Возвращает:
 *   BC_AS_SILENT — бот (токен невалиден / honeypot заполнен / быстрее 3 сек).
 *                  Обработчик отвечает «спасибо» и НЕ создаёт письмо/запись.
 *   BC_AS_LIMIT  — превышен лимит сабмитов / час / IP (показываем сообщение).
 *   BC_AS_OK     — можно обрабатывать (окно rate-limit инкрементируется).
 *
 * @param string $form_id Идентификатор формы.
 * @param int    $limit   Максимум сабмитов / час / IP (по умолчанию 5; форма отзыва — 1).
 * @return string Одна из констант BC_AS_*.
 */
function bc_antispam_check( $form_id, $limit = 5 ) {
	$token = isset( $_POST['bc_as_token'] ) ? sanitize_text_field( wp_unslash( $_POST['bc_as_token'] ) ) : '';
	if ( ! bc_antispam_token_valid( $form_id, $token ) ) {
		return BC_AS_SILENT;
	}

	if ( ! empty( $_POST['website'] ) ) {
		return BC_AS_SILENT;
	}

	$ts = (int) get_transient( 'bc_as_ts_' . $token );
	if ( $ts && ( time() - $ts ) < 3 ) {
		return BC_AS_SILENT;
	}

	// Скользящее окно: max $limit сабмитов / час / IP / форма.
	$ip     = function_exists( 'bc_client_ip' ) ? bc_client_ip() : '0.0.0.0';
	$key    = 'bc_rl_' . md5( $form_id . '|' . $ip );
	$stamps = get_transient( $key );
	$stamps = is_array( $stamps ) ? $stamps : array();
	$now    = time();
	$stamps = array_values( array_filter( $stamps, static function ( $t ) use ( $now ) {
		return ( $now - $t ) < HOUR_IN_SECONDS;
	} ) );

	if ( count( $stamps ) >= $limit ) {
		set_transient( $key, $stamps, HOUR_IN_SECONDS );
		return BC_AS_LIMIT;
	}

	$stamps[] = $now;
	set_transient( $key, $stamps, HOUR_IN_SECONDS );
	return BC_AS_OK;
}

/*
 * Заготовки вызова хелпера для форм, которые появятся позже:
 *
 * — «Добавить организацию» (страница /dobavit, сейчас мёртвая ссылка, обработчика нет):
 *     в форме:  <?php bc_antispam_field( 'org_add' ); ?>
 *     в обработчике:  $as = bc_antispam_check( 'org_add' );
 *
 * — «Добавить событие» (страница /dobavit-sobytie, обработчика нет):
 *     в форме:  <?php bc_antispam_field( 'event_add' ); ?>
 *     в обработчике:  $as = bc_antispam_check( 'event_add' );
 *
 * — «Написать отзыв» (форма отзыва появится на Этапе 4):
 *     в форме:  <?php bc_antispam_field( 'review' ); ?>
 *     в обработчике:  $as = bc_antispam_check( 'review' );
 */
