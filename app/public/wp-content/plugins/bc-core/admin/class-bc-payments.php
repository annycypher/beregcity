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
	echo '<div class="wrap"><h1>Платежи и счета</h1>';
	echo '<p>Функционал платежей (D25: очередь «Я оплатил(а)», подтверждение → активация тарифа, автосчёт с печатью/PDF и письмом) появится вместе с личным кабинетом на <strong>Этапе 3а</strong> — потребуется таблица <code>wp_bc_payments</code> и метод <code>BC_Plans::activate()</code>.</p>';
	echo '</div>';
}
