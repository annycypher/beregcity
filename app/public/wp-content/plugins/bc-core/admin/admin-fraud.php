<?php
/**
 * Фрод-индикаторы в очереди «Утверждение карточек» (D24, микро-шаг «3а-фрод», D35).
 *
 * Для каждой pending-карточки вычисляются значки-плашки (стиль админки,
 * без внешних ассетов). Карточка с >=1 красным флагом подсвечивает строку.
 * Индикаторы — информационные: решение всегда за админом, автоблокировок нет.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Значки-индикаторы для карточки.
 *
 * @param int $post_id ID организации.
 * @return array Карта 'slug' => array( 'label' => ..., 'red' => bool ).
 */
function bc_fraud_flags( $post_id ) {
	$flags = array();

	$gallery = function_exists( 'get_field' ) ? get_field( 'field_bc_gallery', $post_id ) : null;
	if ( empty( $gallery ) ) {
		$flags['no_photo'] = array( 'label' => 'БЕЗ ФОТО', 'red' => true );
	}

	$content = (string) get_post_field( 'post_content', $post_id );
	$len     = function_exists( 'mb_strlen' ) ? mb_strlen( $content ) : strlen( $content );
	if ( $len < 200 ) {
		$flags['short_text'] = array( 'label' => 'ТЕКСТ < 200', 'red' => true );
	}

	if ( bc_fraud_has_external_link( $content ) ) {
		$flags['ext_link'] = array( 'label' => 'ВНЕШНЯЯ ССЫЛКА', 'red' => true );
	}

	$author_id = (int) get_post_field( 'post_author', $post_id );
	$email     = $author_id ? get_the_author_meta( 'user_email', $author_id ) : '';
	if ( $email && bc_fraud_is_free_email( $email ) ) {
		$flags['free_email'] = array( 'label' => 'FREE-EMAIL', 'red' => false );
	}

	$ip       = get_post_meta( $post_id, 'bc_reg_ip', true );
	$rejected = get_option( 'bc_rejected_ips', array() );
	if ( $ip && is_array( $rejected ) && in_array( $ip, $rejected, true ) ) {
		$flags['ip_match'] = array( 'label' => 'IP-СОВПАДЕНИЕ', 'red' => true );
	}

	return $flags;
}

/**
 * Есть ли во вложенном контенте <a href> на внешний домен.
 *
 * @param string $content post_content.
 * @return bool
 */
function bc_fraud_has_external_link( $content ) {
	if ( ! preg_match_all( '/<a\s[^>]*href=["\']([^"\']+)["\']/i', $content, $m ) ) {
		return false;
	}
	$home_host = strtolower( (string) wp_parse_url( home_url(), PHP_URL_HOST ) );
	foreach ( $m[1] as $url ) {
		$host = strtolower( (string) wp_parse_url( $url, PHP_URL_HOST ) );
		if ( $host && $host !== $home_host && $host !== 'www.' . $home_host ) {
			return true;
		}
	}
	return false;
}

/**
 * E-mail на бесплатном сервисе (информационно, не флаг).
 *
 * @param string $email Адрес.
 * @return bool
 */
function bc_fraud_is_free_email( $email ) {
	$free   = array( 'mail.ru', 'yandex.ru', 'gmail.com' );
	$domain = strtolower( (string) substr( strrchr( $email, '@' ), 1 ) );
	return in_array( $domain, $free, true );
}

/**
 * Есть ли у карточки хотя бы один красный флаг (подсветка строки).
 *
 * @param int $post_id ID организации.
 * @return bool
 */
function bc_fraud_has_red( $post_id ) {
	foreach ( bc_fraud_flags( $post_id ) as $f ) {
		if ( ! empty( $f['red'] ) ) {
			return true;
		}
	}
	return false;
}

/**
 * HTML плашек-индикаторов для колонки очереди.
 *
 * @param int $post_id ID организации.
 * @return string
 */
function bc_fraud_badges( $post_id ) {
	$flags = bc_fraud_flags( $post_id );
	if ( ! $flags ) {
		return '<span style="color:#1a7a4a">—</span>';
	}
	$out = '';
	foreach ( $flags as $f ) {
		$bg  = $f['red'] ? '#b3261e' : '#6b7a72';
		$out .= '<span style="display:inline-block;margin:1px 2px 1px 0;padding:1px 6px;border-radius:3px;font-size:11px;line-height:1.6;color:#fff;background:' . $bg . '">' . esc_html( $f['label'] ) . '</span>';
	}
	return $out;
}

/**
 * Записать IP отклонённой карточки в опцию-список (для «IP-СОВПАДЕНИЕ»).
 * Вызывается при действии «Отклонить» в очереди утверждения.
 *
 * @param int $post_id ID организации.
 */
function bc_record_rejected_ip( $post_id ) {
	$ip = get_post_meta( $post_id, 'bc_reg_ip', true );
	if ( ! $ip ) {
		return;
	}
	$list = get_option( 'bc_rejected_ips', array() );
	if ( ! is_array( $list ) ) {
		$list = array();
	}
	if ( ! in_array( $ip, $list, true ) ) {
		$list[] = $ip;
		update_option( 'bc_rejected_ips', $list, false );
	}
}
