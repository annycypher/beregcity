<?php
/**
 * Счета v1 (D25, Этап 3а.5): автосоздание, шаблон bc-invoice, печать, письмо, токен.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_create_invoice( $org_id, $plan, $period ) {
	global $wpdb;
	$table  = bc_payments_table();
	$amount = class_exists( 'BC_Plans' ) ? BC_Plans::price( $plan, $period ) : 0;
	$token  = wp_generate_password( 32, false );
	$wpdb->insert(
		$table,
		array(
			'org_id'     => (int) $org_id,
			'plan'       => $plan,
			'period'     => $period,
			'amount'     => $amount,
			'method'     => 'manual',
			'status'     => 'created',
			'created_at' => current_time( 'mysql' ),
			'token'      => $token,
		)
	);
	return array( 'id' => (int) $wpdb->insert_id, 'token' => $token, 'amount' => $amount );
}

function bc_invoice_url( $token ) {
	return home_url( '/kabinet/invoice/' . $token . '/' );
}

function bc_invoice_number( $id ) {
	return 'БС-' . date( 'Y' ) . '-' . str_pad( (string) $id, 3, '0', STR_PAD_LEFT );
}

function bc_plan_label( $plan ) {
	$labels = array( 'trial' => 'Триал', 'free' => 'Free', 'standard' => 'Стандарт', 'premium' => 'Премиум' );
	return isset( $labels[ $plan ] ) ? $labels[ $plan ] : $plan;
}

function bc_period_label( $period ) {
	$labels = array( 'month' => 'месяц', 'quarter' => '3 месяца', 'year' => 'год' );
	return isset( $labels[ $period ] ) ? $labels[ $period ] : $period;
}

function bc_render_invoice( $row ) {
	$org    = get_post( $row->org_id );
	$number = bc_invoice_number( $row->id );
	?>
	<div class="bc-invoice">
		<div class="inv-head">
			<div class="inv-logo">БерегСити</div>
			<div class="inv-meta">
				<div>Получатель: [ФАКТ-ПРОВЕРКА: ФИО/ИП из СПРАВОЧНИКА §5]</div>
				<div>Номер карты: [ФАКТ-ПРОВЕРКА §5] · Банк: [ФАКТ-ПРОВЕРКА §5]</div>
			</div>
		</div>
		<h1>Счёт на оплату № <?php echo esc_html( $number ); ?></h1>
		<p>Плательщик: <?php echo esc_html( $org ? $org->post_title : '—' ); ?></p>
		<table class="inv-lines">
			<tr><th>Позиция</th><th>Сумма</th></tr>
			<tr><td>Тариф «<?php echo esc_html( bc_plan_label( $row->plan ) ); ?>», <?php echo esc_html( bc_period_label( $row->period ) ); ?></td><td><?php echo (int) $row->amount; ?> ₽</td></tr>
		</table>
		<div class="inv-total">Итого: <?php echo (int) $row->amount; ?> ₽ · НДС не облагается</div>
		<div class="inv-note">Инструкция плательщику: «Переведите сумму по номеру карты, в комментарии укажите название организации. Нажмите „Я оплатил(а)“. Подтверждение — в течение рабочего дня».</div>
		<div class="inv-actions"><button class="btn btn-terra" onclick="window.print()">Печать / Сохранить PDF</button></div>
	</div>
	<?php
}
function bc_invoice_rewrite() {
	add_rewrite_rule( '^kabinet/invoice/([a-z0-9]+)/?$', 'index.php?bc_invoice=$matches[1]', 'top' );
}
add_action( 'init', 'bc_invoice_rewrite' );

function bc_invoice_query_var( $vars ) {
	$vars[] = 'bc_invoice';
	return $vars;
}
add_filter( 'query_vars', 'bc_invoice_query_var' );

function bc_invoice_template_redirect() {
	$token = get_query_var( 'bc_invoice' );
	if ( ! $token ) {
		return;
	}
	global $wpdb;
	$row = $wpdb->get_row( $wpdb->prepare( 'SELECT * FROM ' . bc_payments_table() . ' WHERE token = %s', $token ) );
	if ( ! $row ) {
		wp_die( 'Счёт не найден или ссылка недействительна.' );
	}
	get_header();
	echo '<div class="wrap" style="max-width:720px;margin:36px auto">';
	bc_render_invoice( $row );
	echo '</div>';
	get_footer();
	exit;
}
add_action( 'template_redirect', 'bc_invoice_template_redirect' );

/**
 * Письмо со счётом (HTML) + публичная ссылка.
 */
function bc_send_invoice_email( $org_id, $invoice ) {
	$org   = get_post( $org_id );
	$email = get_the_author_meta( 'user_email', (int) $org->post_author );
	if ( ! $email ) {
		return;
	}
	$subject = 'БерегСити: счёт ' . bc_invoice_number( $invoice['id'] );
	$link    = bc_invoice_url( $invoice['token'] );
	$body    = '<p>Ваш счёт на сумму ' . (int) $invoice['amount'] . ' ₽ готов.</p>';
	$body   .= '<p>Просмотреть и оплатить: <a href="' . esc_url( $link ) . '">' . esc_html( $link ) . '</a></p>';
	wp_mail( $email, $subject, $body, array( 'Content-Type: text/html; charset=UTF-8' ) );
}

/**
 * «Я оплатил(а)» в ЛК (D25, Этап 3а.5): создаёт счёт/платёж (status=created)
 * для организации текущего org_manager; подтверждение — в админке.
 */
function bc_invoice_handle_pay() {
	if ( 'cabinet' !== get_query_var( 'bc_lk' ) || ! is_user_logged_in() ) {
		return;
	}
	if ( empty( $_POST['bc_pay_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_pay_nonce'] ) ), 'bc_pay_create' ) ) {
		return;
	}

	$user = wp_get_current_user();
	$role = (array) $user->roles;
	if ( ! in_array( 'org_manager', $role, true ) && ! in_array( 'administrator', $role, true ) ) {
		return;
	}

	$orgs = get_posts(
		array(
			'post_type'   => 'organizations',
			'author'      => $user->ID,
			'post_status' => 'any',
			'numberposts' => 1,
			'fields'      => 'ids',
		)
	);
	if ( ! $orgs ) {
		return;
	}
	$org_id = (int) $orgs[0];

	$plan   = isset( $_POST['bc_pay_plan'] ) ? sanitize_key( wp_unslash( $_POST['bc_pay_plan'] ) ) : 'standard';
	$period = isset( $_POST['bc_pay_period'] ) ? sanitize_key( wp_unslash( $_POST['bc_pay_period'] ) ) : 'month';
	if ( ! in_array( $plan, array( 'standard', 'premium' ), true ) ) {
		$plan = 'standard';
	}
	$periods = ( class_exists( 'BC_Plans' ) ) ? array_keys( BC_Plans::PERIODS ) : array( 'month', 'quarter', 'year' );
	if ( ! in_array( $period, $periods, true ) ) {
		$period = 'month';
	}

	$invoice = bc_create_invoice( $org_id, $plan, $period );
	if ( ! empty( $invoice['id'] ) && function_exists( 'bc_send_invoice_email' ) ) {
		bc_send_invoice_email( $org_id, $invoice );
	}

	wp_safe_redirect( add_query_arg( 'bc_paid', '1', home_url( '/kabinet/billing/' ) ) );
	exit;
}
add_action( 'template_redirect', 'bc_invoice_handle_pay', 5 );
