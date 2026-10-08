<?php
/**
 * Тарифы организации: текущий тариф + лимиты (D15, Этап 3.3c/3.4).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Текущий тариф организации (с учётом истечения триала/тарифа).
 *
 * @param int $post_id ID организации.
 * @return string trial|free|standard|premium
 */
function bc_plan( $post_id ) {
	$plan = get_field( 'field_bc_plan', $post_id );
	if ( ! $plan ) {
		$plan = 'free';
	}
	$now = current_time( 'Y-m-d' );

	if ( 'trial' === $plan ) {
		$until = get_field( 'field_bc_trial_until', $post_id );
		if ( $until && $until < $now ) {
			return 'free';
		}
		return 'trial';
	}

	if ( 'standard' === $plan || 'premium' === $plan ) {
		$until = get_field( 'field_bc_plan_until', $post_id );
		if ( $until && $until < $now ) {
			return 'free';
		}
		return $plan;
	}

	return 'free';
}

/**
 * Премиум?
 */
function bc_plan_is_premium( $post_id ) {
	return 'premium' === bc_plan( $post_id );
}

/**
 * Есть ли снипеты в карточке (free — без снипетов).
 */
function bc_plan_can_features( $post_id ) {
	return 'free' !== bc_plan( $post_id );
}

/**
 * Есть ли описание в карточке (free — без описания).
 */
function bc_plan_can_description( $post_id ) {
	if ( 'free' !== bc_plan( $post_id ) ) {
		return true;
	}
	// Free: витринная карточка (без владельца) — без описания;
	// клейменная (автор org_manager) — описание в лимите free.
	return function_exists( 'bc_is_claimed' ) ? bc_is_claimed( $post_id ) : false;
}

/**
 * Класс тарифных лимитов (D15): trial / free / standard / premium.
 * photos — фото, desc — описание (знаков), features — снипеты (-1 = без лимита),
 * social — сайт/соцсети/мессенджеры, promo — акции, keywords — ключевые слова.
 */
class BC_Plans {
	const LIMITS = array(
		'trial'    => array( 'photos' => 2, 'desc' => 500, 'features' => 3, 'social' => false, 'promo' => 0, 'keywords' => 0 ),
		'free'     => array( 'photos' => 1, 'desc' => 300, 'features' => 0, 'social' => false, 'promo' => 0, 'keywords' => 0 ),
		'standard' => array( 'photos' => 8, 'desc' => 2000, 'features' => 7, 'social' => true, 'promo' => 2, 'keywords' => 5 ),
		'premium'  => array( 'photos' => 25, 'desc' => 5000, 'features' => -1, 'social' => true, 'promo' => 8, 'keywords' => 15 ),
	);

	// Цены и периоды (СПРАВОЧНИК §2; скидки [ФАКТ-ПРОВЕРКА период]).
	const PRICES         = array( 'standard' => 1290, 'premium' => 3690 );
	const PERIODS        = array( 'month' => 1, 'quarter' => 3, 'year' => 12 );
	const PERIOD_DISCOUNT = array( 'month' => 0, 'quarter' => 0.05, 'year' => 0.10 );

	public static function limit( $plan, $key ) {
		return isset( self::LIMITS[ $plan ][ $key ] ) ? self::LIMITS[ $plan ][ $key ] : 0;
	}

	public static function can( $plan, $key, $count ) {
		$limit = self::limit( $plan, $key );
		if ( -1 === $limit ) {
			return true; // без лимита (premium снипеты)
		}
		if ( is_bool( $limit ) ) {
			return $limit;
		}
		return (int) $count <= (int) $limit;
	}

	/**
	 * Сумма за период (с учётом скидки).
	 */
	public static function price( $plan, $period ) {
		$base     = isset( self::PRICES[ $plan ] ) ? self::PRICES[ $plan ] : 0;
		$months   = isset( self::PERIODS[ $period ] ) ? self::PERIODS[ $period ] : 1;
		$discount = isset( self::PERIOD_DISCOUNT[ $period ] ) ? self::PERIOD_DISCOUNT[ $period ] : 0;
		return (int) round( $base * $months * ( 1 - $discount ) );
	}

	/**
	 * Единая точка активации тарифа (D8/D25): plan_until = max(today, текущий) + период.
	 */
	public static function activate( $org_id, $plan, $period, $payment_id = 0 ) {
		$months  = isset( self::PERIODS[ $period ] ) ? self::PERIODS[ $period ] : 1;
		$current = get_field( 'field_bc_plan_until', $org_id );
		$now     = current_time( 'Y-m-d' );
		$base    = ( $current && $current > $now ) ? $current : $now;
		$until   = date( 'Y-m-d', strtotime( $base . ' +' . $months . ' months' ) );
		update_field( 'field_bc_plan', $plan, $org_id );
		update_field( 'field_bc_plan_until', $until, $org_id );
		update_field( 'field_bc_trial_until', '', $org_id );
		return $until;
	}

	/**
	 * Триал: trial_until = дата одобрения +7 дней (D15).
	 */
	public static function start_trial( $org_id ) {
		$until = date( 'Y-m-d', strtotime( current_time( 'Y-m-d' ) . ' +7 days' ) );
		update_field( 'field_bc_plan', 'trial', $org_id );
		update_field( 'field_bc_trial_until', $until, $org_id );
		update_field( 'field_bc_plan_until', '', $org_id );
		return $until;
	}
}

/**
 * Проверка лимитов ДО сохранения (ACF): превышение → ошибка со ссылкой на тариф.
 */
function bc_validate_limits() {
	if ( ! isset( $_POST['_acf_post_id'] ) ) {
		return;
	}
	$pid = sanitize_text_field( wp_unslash( $_POST['_acf_post_id'] ) );
	if ( 'new' === $pid || 'new_post' === $pid || '' === $pid ) {
		return;
	}
	$post_id = (int) $pid;
	if ( 'organizations' !== get_post_type( $post_id ) ) {
		return;
	}
	$plan  = bc_plan( $post_id );
	$feats = count( get_the_terms( $post_id, 'features' ) ?: array() );
	if ( ! BC_Plans::can( $plan, 'features', $feats ) ) {
		acf_add_validation_error( '', 'Превышен лимит снипетов для вашего тарифа. <a href="/kabinet/billing/">Повысить тариф</a>' );
	}
}
add_filter( 'acf/validate_save_post', 'bc_validate_limits' );

/**
 * Сортировка premium↑: orderby 'bc_plan' → приоритет тарифа.
 */
function bc_plan_sort_orderby( $orderby, $query ) {
	if ( 'bc_plan' === $query->get( 'orderby' ) ) {
		global $wpdb;
		$dir     = strtoupper( $query->get( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby = "CASE (SELECT pm.meta_value FROM {$wpdb->postmeta} pm WHERE pm.post_id = {$wpdb->posts}.ID AND pm.meta_key = 'bc_plan') WHEN 'premium' THEN 3 WHEN 'standard' THEN 2 WHEN 'trial' THEN 1 ELSE 0 END {$dir}, {$wpdb->posts}.post_date {$dir}";
	}
	return $orderby;
}
add_filter( 'posts_orderby', 'bc_plan_sort_orderby', 10, 2 );
