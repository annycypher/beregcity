<?php
/**
 * «Платежи и счета» — очередь (D25, Этап 3.5c).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Страница платежей.
 */
function bc_payments_page() {
	global $wpdb;
	$table = bc_payments_table();

	if ( isset( $_GET['bc_pay'], $_GET['pid'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'bc_pay' ) ) {
		$pid    = (int) $_GET['pid'];
		$action = sanitize_key( wp_unslash( $_GET['bc_pay'] ) );
		$row    = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $pid ) );
		if ( $row ) {
			if ( 'approve' === $action ) {
				$until = class_exists( 'BC_Plans' ) ? BC_Plans::activate( $row->org_id, $row->plan, $row->period, $row->id ) : '';
				$wpdb->update( $table, array( 'status' => 'paid', 'activated_at' => current_time( 'mysql' ), 'plan_until' => $until ), array( 'id' => $pid ) );
			} elseif ( 'reject' === $action ) {
				$reason = isset( $_GET['bc_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['bc_reason'] ) ) : '';
				$wpdb->update( $table, array( 'status' => 'rejected' ), array( 'id' => $pid ) );
				$email = get_the_author_meta( 'user_email', (int) get_post_field( 'post_author', $row->org_id ) );
				if ( $email ) {
					wp_mail( $email, 'БерегСити: платёж отклонён', 'Ваш платёж не подтверждён. Причина: ' . ( $reason ? $reason : 'платёж не найден' ) . '.' );
				}
			}
		}
		echo '<div class="notice notice-success is-dismissible"><p>Действие выполнено.</p></div>';
	}

	$rows = $wpdb->get_results( "SELECT * FROM {$table} WHERE status = 'created' ORDER BY created_at DESC LIMIT 100" );

	echo '<div class="wrap"><h1>Платежи и счета</h1>';
	if ( ! $rows ) {
		echo '<p>Очередь пуста — платежей к подтверждению нет.</p>';
	} else {
		echo '<p>Подтверждение активирует тариф (BC_Plans::activate). Отклонение — с причиной. Без массовых действий.</p>';
		echo '<table class="widefat striped"><thead><tr><th>Организация</th><th>Тариф</th><th>Период</th><th>Сумма</th><th>Дата</th><th>Действия</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$n = wp_create_nonce( 'bc_pay' );
			echo '<tr>';
			echo '<td>' . esc_html( get_the_title( $r->org_id ) ) . '</td>';
			echo '<td>' . esc_html( $r->plan ) . '</td>';
			echo '<td>' . esc_html( $r->period ) . '</td>';
			echo '<td><b>' . (int) $r->amount . ' ₽</b></td>';
			echo '<td>' . esc_html( $r->created_at ) . '</td>';
			echo '<td>';
			echo '<a class="button button-primary" href="' . esc_url( add_query_arg( array( 'bc_pay' => 'approve', 'pid' => $r->id, '_wpnonce' => $n ) ) ) . '">Подтвердить</a> ';
			echo '<form method="get" style="display:inline">';
			echo '<input type="hidden" name="page" value="bc-payments">';
			echo '<input type="hidden" name="bc_pay" value="reject">';
			echo '<input type="hidden" name="pid" value="' . (int) $r->id . '">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $n ) . '">';
			echo '<select name="bc_reason"><option>платёж не найден</option><option>сумма не совпадает</option><option>дубликат</option></select> ';
			echo '<button class="button">Отклонить</button>';
			echo '</form>';
			echo '</td></tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}
