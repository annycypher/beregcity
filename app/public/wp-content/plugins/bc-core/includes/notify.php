<?php
/**
 * Письма истечения триала/тарифа (Этап 6а.3, D15): cron-проверка за 3 и 1 день.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_expiry_cron_schedule() {
	if ( ! wp_next_scheduled( 'bc_expiry_check' ) ) {
		wp_schedule_event( time(), 'daily', 'bc_expiry_check' );
	}
}
add_action( 'init', 'bc_expiry_cron_schedule' );

function bc_expiry_check() {
	$orgs = get_posts(
		array(
			'post_type'   => 'organizations',
			'post_status' => 'publish',
			'numberposts' => -1,
			'fields'      => 'ids',
		)
	);
	foreach ( $orgs as $org_id ) {
		$plan  = bc_plan( $org_id );
		$until = '';
		if ( 'trial' === $plan ) {
			$until = get_field( 'field_bc_trial_until', $org_id );
		} elseif ( 'standard' === $plan || 'premium' === $plan ) {
			$until = get_field( 'field_bc_plan_until', $org_id );
		}
		if ( ! $until ) {
			continue;
		}
		$days = (int) ( ( strtotime( $until ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );
		if ( 3 === $days || 1 === $days ) {
			bc_send_expiry_email( $org_id, $days, $plan, $until );
		}
	}
}
add_action( 'bc_expiry_check', 'bc_expiry_check' );

function bc_send_expiry_email( $org_id, $days, $plan, $until ) {
	$email = get_the_author_meta( 'user_email', (int) get_post_field( 'post_author', $org_id ) );
	if ( ! $email ) {
		return;
	}
	$label = ( 'trial' === $plan ) ? 'триал' : 'тариф';
	bc_mail( $email, 'БерегСити: ' . $label . ' истекает', 'Ваш ' . $label . ' истекает через ' . $days . ' дн. (до ' . $until . '). Продлите тариф, чтобы карточка осталась в полном объёме.' );
}
