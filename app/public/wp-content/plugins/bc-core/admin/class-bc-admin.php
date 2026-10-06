<?php
/**
 * Админ-меню «БерегСити» + дашборд (§16 cabinet.md, Этап 3.5).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Меню «БерегСити» и подпункты.
 */
function bc_admin_menu() {
	add_menu_page( 'БерегСити', 'БерегСити', 'manage_options', 'beregcity', 'bc_dashboard_page', 'dashicons-store', 3 );
	add_submenu_page( 'beregcity', 'Дашборд', 'Дашборд', 'manage_options', 'beregcity', 'bc_dashboard_page' );
	add_submenu_page( 'beregcity', 'Утверждение карточек', 'Утверждение карточек', 'manage_options', 'bc-approval', 'bc_approval_page' );
	add_submenu_page( 'beregcity', 'Платежи и счета', 'Платежи и счета', 'manage_options', 'bc-payments', 'bc_payments_page' );
}
add_action( 'admin_menu', 'bc_admin_menu' );

/**
 * Дашборд: 4 очереди-счётчика + быстрые ссылки. Ноль действий = «Всё чисто».
 */
function bc_dashboard_page() {
	$pending = (int) wp_count_posts( 'organizations' )->pending;
	global $wpdb;
	$payments = 0;
	if ( function_exists( 'bc_payments_table' ) ) {
		$payments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . bc_payments_table() . " WHERE status = 'created'" );
	}
	$stories = 0;
	$reviews = 0;
	$total    = $pending + $payments + $stories + $reviews;

	$cards = array(
		array( 'n' => $pending, 'label' => 'Карточки на утверждении', 'page' => 'bc-approval' ),
		array( 'n' => $payments, 'label' => 'Платежи к подтверждению', 'page' => 'bc-payments' ),
		array( 'n' => $stories, 'label' => 'Стории на модерации', 'page' => '' ),
		array( 'n' => $reviews, 'label' => 'Отзывы', 'page' => '' ),
	);
	?>
	<div class="wrap">
		<h1>БерегСити — панель управления</h1>
		<p style="font-size:14px"><?php echo $total ? 'Есть задачи для проверки.' : '✅ Всё чисто — новых задач нет.'; ?></p>
		<div style="display:grid;grid-template-columns:repeat(4,1fr);gap:16px;max-width:960px;margin-top:16px">
			<?php foreach ( $cards as $c ) : ?>
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px">
				<div style="font-size:34px;font-weight:600;color:#1d2327"><?php echo (int) $c['n']; ?></div>
				<div style="color:#50575e;margin:4px 0 10px"><?php echo esc_html( $c['label'] ); ?></div>
				<?php if ( $c['page'] ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $c['page'] ) ); ?>">Открыть →</a><?php else : ?><span style="color:#a7aaad">Скоро</span><?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}
