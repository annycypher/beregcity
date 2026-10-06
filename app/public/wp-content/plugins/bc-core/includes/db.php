<?php
/**
 * Таблица платежей wp_bc_payments (D8/cabinet §35, Этап 3а.4).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_payments_table() {
	global $wpdb;
	return $wpdb->prefix . 'bc_payments';
}

function bc_create_payments_table() {
	global $wpdb;
	$table   = bc_payments_table();
	$charset = $wpdb->get_charset_collate();
	$sql     = "CREATE TABLE {$table} (
		id bigint(20) unsigned NOT NULL AUTO_INCREMENT,
		org_id bigint(20) unsigned NOT NULL,
		plan varchar(20) NOT NULL,
		period varchar(20) NOT NULL,
		amount int NOT NULL DEFAULT 0,
		method varchar(20) NOT NULL DEFAULT 'manual',
		yk_payment_id varchar(100) DEFAULT NULL,
		status varchar(20) NOT NULL DEFAULT 'created',
		created_at datetime NOT NULL,
		activated_at datetime DEFAULT NULL,
		plan_until date DEFAULT NULL,
		PRIMARY KEY  (id),
		KEY org_id (org_id),
		KEY status (status)
	) {$charset};";
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	dbDelta( $sql );
}
