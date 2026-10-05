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
