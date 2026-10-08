<?php
/**
 * Напоминания админу и организациям + email-дайджест (шаг «3а-подсказки»).
 *
 * bc_reminders_admin() — карточка «Напоминания» на дашборде.
 * bc_reminders_org()   — карточка в ЛК организации (над вкладками).
 * bc_mail()            — единая отправка писем с подписью-антифрод (D35-смежное).
 * Cron: weekly-дайджест (пн 09:00) + ежедневная проверка дедлайнов (6а.3, в notify.php).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Отправка письма с подписью-антифрод.
 */
function bc_mail( $to, $subject, $body ) {
	$body .= "\n\n— БерегСити\nПисьмо отправлено автоматически. Мы никогда не спрашиваем пароли и коды по почте.";
	return wp_mail( $to, $subject, $body );
}

/**
 * Организации, у которых триал/тариф истекает в пределах N дней.
 */
function bc_expiring_orgs( $days ) {
	$orgs  = get_posts( array( 'post_type' => 'organizations', 'post_status' => 'publish', 'numberposts' => -1, 'fields' => 'ids' ) );
	$out   = array();
	$today = strtotime( current_time( 'Y-m-d' ) );
	foreach ( $orgs as $id ) {
		$plan  = function_exists( 'bc_plan' ) ? bc_plan( $id ) : 'free';
		$until = '';
		if ( 'trial' === $plan ) {
			$until = get_field( 'field_bc_trial_until', $id );
		} elseif ( 'standard' === $plan || 'premium' === $plan ) {
			$until = get_field( 'field_bc_plan_until', $id );
		}
		if ( ! $until ) {
			continue;
		}
		$d = (int) ( ( strtotime( $until ) - $today ) / DAY_IN_SECONDS );
		if ( $d >= 0 && $d <= $days ) {
			$out[] = array( 'id' => $id, 'title' => get_the_title( $id ), 'days' => $d, 'until' => $until );
		}
	}
	return $out;
}

/**
 * Статус ритуала: сколько дней назад пройден, просрочен ли (>period дней).
 */
function bc_ritual_status( $key, $period ) {
	$last = get_option( 'bc_ritual_' . $key . '_last', '' );
	if ( ! $last ) {
		return array( 'days' => null, 'overdue' => true );
	}
	$days = (int) ( ( strtotime( current_time( 'Y-m-d' ) ) - strtotime( $last ) ) / DAY_IN_SECONDS );
	return array( 'days' => $days, 'overdue' => $days > $period );
}

function bc_ritual_text( $key, $period ) {
	$st = bc_ritual_status( $key, $period );
	if ( null === $st['days'] ) {
		return 'не пройден';
	}
	return $st['days'] . ' дн. назад' . ( $st['overdue'] ? ' (просрочен)' : '' );
}

/**
 * Обработка «Отметить выполненным» (nonce).
 */
function bc_reminders_handle_toggle() {
	if ( isset( $_POST['bc_ritual_nonce'], $_POST['bc_ritual'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_ritual_nonce'] ) ), 'bc_ritual' ) ) {
		$r = sanitize_key( wp_unslash( $_POST['bc_ritual'] ) );
		if ( in_array( $r, array( 'weekly', 'monthly' ), true ) ) {
			update_option( 'bc_ritual_' . $r . '_last', current_time( 'Y-m-d' ), false );
		}
	}
}

/**
 * Карточка «Напоминания» на дашборде «БерегСити».
 */
function bc_reminders_admin() {
	bc_reminders_handle_toggle();

	$expiring = bc_expiring_orgs( 3 );
	$pending  = (int) wp_count_posts( 'organizations' )->pending;
	global $wpdb;
	$payments = function_exists( 'bc_payments_table' ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . bc_payments_table() . " WHERE status = 'created'" ) : 0;
	$stories  = (int) wp_count_posts( 'stories' )->pending;
	$nonce    = wp_create_nonce( 'bc_ritual' );
	?>
	<div style="margin-top:24px">
		<h2>Напоминания</h2>
		<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px;max-width:960px">
			<h3 style="margin-top:0">Автоматические</h3>
			<ul style="margin:0 0 12px;padding-left:18px">
				<li>Карточки на утверждении: <b><?php echo (int) $pending; ?></b></li>
				<li>Платежи к подтверждению: <b><?php echo (int) $payments; ?></b></li>
				<li>Сторис на модерации: <b><?php echo (int) $stories; ?></b></li>
				<?php if ( $expiring ) : ?>
					<?php foreach ( $expiring as $e ) : ?>
					<li style="color:#b3261e">Тариф «<?php echo esc_html( $e['title'] ); ?>» истекает через <?php echo (int) $e['days']; ?> дн. (до <?php echo esc_html( $e['until'] ); ?>)</li>
					<?php endforeach; ?>
				<?php else : ?>
					<li>Тарифов, истекающих в ближайшие 3 дня, нет.</li>
				<?php endif; ?>
			</ul>

			<h3>Ритуалы (вручную)</h3>
			<?php
			$rituals = array(
				'weekly'  => array( 'label' => 'Недельный ритуал', 'period' => 7 ),
				'monthly' => array( 'label' => 'Месячный ритуал', 'period' => 35 ),
			);
			foreach ( $rituals as $key => $r ) :
				$st   = bc_ritual_status( $key, $r['period'] );
				$warn = $st['overdue'];
				?>
				<div style="display:flex;align-items:center;gap:12px;padding:8px 0;border-top:1px solid #f0f0f1">
					<div style="flex:1">
						<b><?php echo esc_html( $r['label'] ); ?></b>
						<span style="<?php echo $warn ? 'color:#b3261e' : 'color:#1a7a4a'; ?>;margin-left:8px">последний: <?php echo esc_html( bc_ritual_text( $key, $r['period'] ) ); ?></span>
					</div>
					<form method="post" style="margin:0">
						<input type="hidden" name="bc_ritual" value="<?php echo esc_attr( $key ); ?>">
						<input type="hidden" name="bc_ritual_nonce" value="<?php echo esc_attr( $nonce ); ?>">
						<button class="button">Отметить выполненным</button>
					</form>
				</div>
			<?php endforeach; ?>
		</div>
	</div>
	<?php
}

/**
 * Карточка напоминаний в ЛК организации (над вкладками). Всё on-the-fly.
 */
function bc_reminders_org( $org_id ) {
	if ( ! $org_id ) {
		return;
	}
	$lines = array();

	$plan  = function_exists( 'bc_plan' ) ? bc_plan( $org_id ) : 'free';
	$until = '';
	if ( 'trial' === $plan ) {
		$until = get_field( 'field_bc_trial_until', $org_id );
	} elseif ( 'standard' === $plan || 'premium' === $plan ) {
		$until = get_field( 'field_bc_plan_until', $org_id );
	}
	if ( $until ) {
		$d = (int) ( ( strtotime( $until ) - strtotime( current_time( 'Y-m-d' ) ) ) / DAY_IN_SECONDS );
		if ( $d >= 0 && $d <= 3 ) {
			$lines[] = 'Тариф истекает ' . ( $d ? 'через ' . $d . ' дн.' : 'сегодня' ) . ' — <a href="/kabinet/billing/">продлите</a>.';
		}
	}

	$prog = function_exists( 'bc_progress' ) ? bc_progress( $org_id ) : array( 'percent' => 0 );
	if ( $prog['percent'] < 50 ) {
		$lines[] = 'Карточка заполнена на ' . (int) $prog['percent'] . '% — <a href="/kabinet/card/">дополните</a> для большего охвата.';
	}

	$limit = class_exists( 'BC_Plans' ) ? (int) BC_Plans::limit( $plan, 'promo' ) : 0;
	if ( $limit > 0 ) {
		$author = (int) get_post_field( 'post_author', $org_id );
		$used   = count( get_posts( array( 'post_type' => 'news', 'author' => $author, 'post_status' => 'any', 'numberposts' => -1, 'meta_key' => 'news_type', 'meta_value' => 'promo' ) ) );
		$left   = $limit - $used;
		if ( $left > 0 ) {
			$lines[] = 'Доступно акций в этом месяце: ' . $left . ' — <a href="/kabinet/promo/">добавить</a>.';
		}
	}

	$reviews = (int) get_comments( array( 'post_id' => $org_id, 'status' => 'hold', 'count' => true ) );
	if ( $reviews > 0 ) {
		$lines[] = 'Отзывы на проверке: ' . $reviews . '.';
	}

	if ( ! $lines ) {
		return;
	}
	echo '<div style="background:#f1f5ec;border-radius:10px;padding:12px 16px;margin:0 0 16px;font-size:13px;color:#2b332e">';
	echo '<b>Важно</b><ul style="margin:6px 0 0;padding-left:18px">';
	foreach ( $lines as $l ) {
		echo '<li>' . wp_kses( $l, array( 'a' => array( 'href' => array() ) ) ) . '</li>';
	}
	echo '</ul></div>';
}

/**
 * Планировщик cron (weekly-дайджест). Дедлайны — в notify.php (bc_expiry_check, 6а.3).
 */
function bc_reminders_schedule() {
	if ( ! wp_next_scheduled( 'bc_weekly_digest' ) ) {
		wp_schedule_event( bc_next_monday_9am(), 'weekly', 'bc_weekly_digest' );
	}
}
add_action( 'init', 'bc_reminders_schedule' );

/**
 * Следующий понедельник 09:00 в таймзоне сайта.
 */
function bc_next_monday_9am() {
	$tz  = wp_timezone();
	$now = new DateTimeImmutable( 'now', $tz );
	$dow = (int) $now->format( 'N' ); // 1=Пн .. 7=Вс
	$add = ( 8 - $dow ) % 7;
	if ( 0 === $add ) {
		$add = ( (int) $now->format( 'G' ) >= 9 ) ? 7 : 0;
	}
	$target = ( 0 === $add ) ? $now : $now->modify( '+' . $add . ' days' );
	return $target->setTime( 9, 0, 0 )->getTimestamp();
}

/**
 * Еженедельный дайджест админу.
 */
function bc_weekly_digest() {
	$expiring = bc_expiring_orgs( 7 );
	$pending  = (int) wp_count_posts( 'organizations' )->pending;
	global $wpdb;
	$payments = function_exists( 'bc_payments_table' ) ? (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . bc_payments_table() . " WHERE status = 'created'" ) : 0;
	$stories  = (int) wp_count_posts( 'stories' )->pending;

	$body  = "Еженедельный дайджест БерегСити\n\n";
	$body .= "Очереди:\n";
	$body .= "- карточки на утверждении: {$pending}\n";
	$body .= "- платежи к подтверждению: {$payments}\n";
	$body .= "- сторис на модерации: {$stories}\n\n";
	$body .= "Ритуалы:\n";
	$body .= "- недельный: " . bc_ritual_text( 'weekly', 7 ) . "\n";
	$body .= "- месячный: " . bc_ritual_text( 'monthly', 35 ) . "\n\n";
	$body .= 'Тарифов, истекающих в ближайшие 7 дней: ' . count( $expiring ) . "\n";
	$body .= "\nПанель: " . admin_url( 'admin.php?page=beregcity' ) . "\n";

	bc_mail( get_option( 'admin_email' ), 'БерегСити: еженедельный дайджест', $body );
}
add_action( 'bc_weekly_digest', 'bc_weekly_digest' );


