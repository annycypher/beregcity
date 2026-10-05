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
	return 'free' !== bc_plan( $post_id );
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
}

/**
 * Сортировка premium↑: orderby 'bc_plan' → приоритет тарифа.
 */
function bc_plan_sort_orderby( $orderby, $query ) {
	if ( 'bc_plan' === $query->get( 'orderby' ) ) {
		global $wpdb;
		$dir     = strtoupper( $query->get( 'order' ) ) === 'ASC' ? 'ASC' : 'DESC';
		$orderby = "CASE {$wpdb->postmeta}.meta_value WHEN 'premium' THEN 3 WHEN 'standard' THEN 2 WHEN 'trial' THEN 1 ELSE 0 END {$dir}, {$wpdb->posts}.post_date {$dir}";
	}
	return $orderby;
}
add_filter( 'posts_orderby', 'bc_plan_sort_orderby', 10, 2 );
