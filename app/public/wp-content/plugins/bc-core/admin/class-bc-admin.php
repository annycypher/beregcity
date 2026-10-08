<?php
/**
 * Админ-меню «БерегСити»: дашборд, утверждение карточек, заявки на права (D38), платежи.
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
	add_submenu_page( 'beregcity', 'Заявки на права', 'Заявки на права', 'manage_options', 'bc-claims', 'bc_claims_page' );
add_submenu_page( 'beregcity', 'Отзывы', 'Отзывы', 'manage_options', 'bc-reviews', 'bc_reviews_page' );
	add_submenu_page( 'beregcity', 'Платежи и счета', 'Платежи и счета', 'manage_options', 'bc-payments', 'bc_payments_page' );
}
add_action( 'admin_menu', 'bc_admin_menu' );

/**
 * Количество заявок в очереди (new + verified) — для дашборда.
 *
 * @return int
 */
function bc_claims_queue_count() {
	global $wpdb;
	if ( ! function_exists( 'bc_claims_table' ) ) {
		return 0;
	}
	$table = bc_claims_table();
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$table} WHERE status IN ('new','verified')" );
}

/**
 * Дашборд: очереди-счётчики + быстрые ссылки.
 */
function bc_dashboard_page() {
	$pending = (int) wp_count_posts( 'organizations' )->pending;
	global $wpdb;
	$payments = 0;
	if ( function_exists( 'bc_payments_table' ) ) {
		$payments = (int) $wpdb->get_var( "SELECT COUNT(*) FROM " . bc_payments_table() . " WHERE status = 'created'" );
	}
	$claims  = bc_claims_queue_count();
	$stale   = function_exists( 'bc_claims_stale_count' ) ? bc_claims_stale_count() : 0;
	$stories = 0;
	$reviews = function_exists( 'bc_reviews_queue_count' ) ? bc_reviews_queue_count() : 0;
	$total   = $pending + $payments + $claims + $stories + $reviews;

	$cards = array(
		array( 'n' => $pending, 'label' => 'Карточки на утверждении', 'page' => 'bc-approval', 'sub' => '' ),
		array( 'n' => $claims, 'label' => 'Заявки на права', 'page' => 'bc-claims', 'sub' => ( $stale ? 'просрочено: ' . $stale : '' ) ),
		array( 'n' => $payments, 'label' => 'Платежи к подтверждению', 'page' => 'bc-payments', 'sub' => '' ),
		array( 'n' => $stories, 'label' => 'Сторис на модерации', 'page' => '', 'sub' => '' ),
		array( 'n' => $reviews, 'label' => 'Отзывы на модерации', 'page' => 'bc-reviews', 'sub' => '' ),
	);
	?>
	<div class="wrap">
		<h1>БерегСити — панель управления</h1>
		<p style="font-size:14px"><?php echo $total ? 'Есть задачи для проверки.' : '✅ Всё чисто — новых задач нет.'; ?></p>
		<div style="display:grid;grid-template-columns:repeat(5,1fr);gap:16px;max-width:1260px;margin-top:16px">
			<?php foreach ( $cards as $c ) : ?>
			<div style="background:#fff;border:1px solid #dcdcde;border-radius:12px;padding:18px">
				<div style="font-size:34px;font-weight:600;color:#1d2327"><?php echo (int) $c['n']; ?></div>
				<div style="color:#50575e;margin:4px 0 10px"><?php echo esc_html( $c['label'] ); ?></div>
				<?php if ( ! empty( $c['sub'] ) ) : ?><div style="color:#8c8f94;font-size:12px;margin:-6px 0 8px"><?php echo esc_html( $c['sub'] ); ?></div><?php endif; ?>
				<?php if ( $c['page'] ) : ?><a href="<?php echo esc_url( admin_url( 'admin.php?page=' . $c['page'] ) ); ?>">Открыть →</a><?php else : ?><span style="color:#a7aaad">Скоро</span><?php endif; ?>
			</div>
			<?php endforeach; ?>
		</div>
		<?php bc_info_block( 'dashboard' ); ?>
		<?php bc_reminders_admin(); ?>
	</div>
	<?php
}

/**
 * Экран «Заявки на права» (D38): таблица заявок, привязка и отклонение.
 */
function bc_claims_page() {
	$notice = bc_claims_handle_action();
	$table  = function_exists( 'bc_claims_table' ) ? bc_claims_table() : '';
	$view   = isset( $_GET['view'] ) ? sanitize_key( wp_unslash( $_GET['view'] ) ) : 'claims';
	$stale  = function_exists( 'bc_claims_stale_count' ) ? bc_claims_stale_count() : 0;
	$nonce  = wp_create_nonce( 'bc_claim_admin' );
	$base   = admin_url( 'admin.php?page=bc-claims' );
	?>
	<div class="wrap">
		<h1>Заявки на права</h1>
		<?php if ( $notice ) : ?>
			<div class="notice notice-<?php echo 'error' === $notice['type'] ? 'error' : 'success'; ?> is-dismissible"><p><?php echo esc_html( $notice['text'] ); ?></p></div>
		<?php endif; ?>

		<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:14px 16px;margin:12px 0">
			<strong style="font-size:14px">Как проверять заявку</strong>
			<ul style="margin:8px 0 0;padding-left:18px;color:#50575e">
				<li style="margin:3px 0">Звоните <b>только на телефон ИЗ КАРТОЧКИ</b> (публичный номер организации), <b>не</b> на телефон из заявки.</li>
				<li style="margin:3px 0">Цель звонка — подтвердить, что заявитель представляет организацию.</li>
				<li style="margin:3px 0">Нет дозвона — отклонить с причиной «не удалось подтвердить по телефону».</li>
				<li style="margin:3px 0">Просроченных заявок (в статусе «новая» дольше 14 дней): <b><?php echo (int) $stale; ?></b>.</li>
			</ul>
		</div>

		<h2 class="nav-tab-wrapper" style="margin-bottom:12px">
			<a class="nav-tab <?php echo 'claims' === $view ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( $base ); ?>">Заявки</a>
			<a class="nav-tab <?php echo 'showcase' === $view ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'view', 'showcase', $base ) ); ?>">Витрина (без владельца)</a>
			<a class="nav-tab <?php echo 'claimed' === $view ? 'nav-tab-active' : ''; ?>" href="<?php echo esc_url( add_query_arg( 'view', 'claimed', $base ) ); ?>">Клейменные</a>
		</h2>
		<?php
		if ( 'claims' === $view ) {
			bc_claims_render_table( $table, $nonce, $base );
		} else {
			bc_claims_render_orgs( 'showcase' === $view );
		}
		?>
	</div>
	<?php
}

/**
 * Обработка действия экрана (bind / reject) по GET + nonce.
 *
 * @return array|null array( 'type' => 'success|error', 'text' => string ).
 */
function bc_claims_handle_action() {
	if ( ! isset( $_GET['bc_claim_action'], $_GET['cid'], $_GET['_wpnonce'] ) ) {
		return null;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'bc_claim_admin' ) ) {
		return array( 'type' => 'error', 'text' => 'Сессия устарела, обновите страницу.' );
	}
	global $wpdb;
	$table = bc_claims_table();
	$cid   = (int) $_GET['cid'];
	$row   = $wpdb->get_row( $wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $cid ) );
	if ( ! $row ) {
		return array( 'type' => 'error', 'text' => 'Заявка не найдена.' );
	}
	$action = sanitize_key( wp_unslash( $_GET['bc_claim_action'] ) );
	$org    = get_the_title( (int) $row->post_id );

	if ( 'reject' === $action ) {
		$reason = isset( $_GET['bc_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['bc_reason'] ) ) : '';
		if ( '' === trim( $reason ) ) {
			return array( 'type' => 'error', 'text' => 'Укажите причину отклонения — без причины отклонить нельзя.' );
		}
		$wpdb->update( $table, array( 'status' => 'rejected' ), array( 'id' => $cid ) );
		if ( $row->ip ) {
			$list = get_option( 'bc_rejected_ips', array() );
			$list = is_array( $list ) ? $list : array();
			if ( ! in_array( $row->ip, $list, true ) ) {
				$list[] = $row->ip;
				update_option( 'bc_rejected_ips', $list, false );
			}
		}
		bc_mail( $row->email, 'БерегСити: заявка на права отклонена', "Заявка на права по организации «{$org}» отклонена.\nПричина: {$reason}.\nЕсли это ошибка — ответьте на это письмо." );
		return array( 'type' => 'success', 'text' => 'Заявка #' . $cid . ' отклонена.' );
	}

	if ( 'bind' === $action ) {
		$email    = $row->email;
		$user     = get_user_by( 'email', $email );
		if ( ! $user ) {
			$user = get_user_by( 'login', $email );
		}
		$new_user = false;
		if ( $user ) {
			$uid = (int) $user->ID;
			if ( ! in_array( 'org_manager', (array) $user->roles, true ) ) {
				$user->add_role( 'org_manager' );
			}
		} else {
			$uid = wp_insert_user(
				array(
					'user_login' => $email,
					'user_email' => $email,
					'user_pass'  => wp_generate_password( 24, true ),
					'role'       => 'org_manager',
				)
			);
			if ( is_wp_error( $uid ) ) {
				return array( 'type' => 'error', 'text' => 'Не удалось создать пользователя: ' . $uid->get_error_message() );
			}
			$new_user = true;
		}

		$post_id = (int) $row->post_id;
		wp_update_post(
			array(
				'ID'          => $post_id,
				'post_author' => $uid,
				'post_status' => 'publish',
			)
		);
		if ( function_exists( 'get_field' ) && ! get_field( 'field_bc_plan', $post_id ) ) {
			update_field( 'field_bc_plan', 'free', $post_id );
		}
		$wpdb->update( $table, array( 'status' => 'approved', 'user_id' => $uid ), array( 'id' => $cid ) );

		$body = "Доступ к личному кабинету организации «{$org}» открыт.\nВход: " . home_url( '/kabinet/' ) . "\nЛогин: {$email}";
		if ( $new_user ) {
			$body .= "\nАккаунт создан — мы отправили отдельным письмом ссылку для установки пароля. Если письма нет, нажмите «Забыли пароль?» на странице входа.";
		}
		bc_mail( $email, 'БерегСити: доступ открыт', $body );
		if ( $new_user ) {
			retrieve_password( $email );
		}
		return array( 'type' => 'success', 'text' => 'Готово: ' . ( $new_user ? 'пользователь создан' : 'пользователь найден' ) . ', карточка привязана к ' . $email . '.' );
	}

	return null;
}

/**
 * Таблица заявок.
 */
function bc_claims_render_table( $table, $nonce, $base ) {
	global $wpdb;
	$rows = $wpdb->get_results( "SELECT * FROM {$table} ORDER BY (status IN ('new','verified')) DESC, created_at DESC LIMIT 200" );
	if ( ! $rows ) {
		echo '<p>Заявок пока нет.</p>';
		return;
	}
	$roles = function_exists( 'bc_claim_roles' ) ? bc_claim_roles() : array();
	$now   = strtotime( current_time( 'mysql' ) );
	echo '<table class="widefat striped"><thead><tr>';
	echo '<th>Карточка</th><th>Заявитель</th><th>E-mail</th><th>Телефон заявителя</th><th>Телефон из карточки</th><th>Роль</th><th>Статус</th><th>Дата</th><th>Действия</th>';
	echo '</tr></thead><tbody>';
	foreach ( $rows as $r ) {
		$title  = get_the_title( (int) $r->post_id );
		$cphone = function_exists( 'get_field' ) ? (string) get_field( 'field_bc_phone', (int) $r->post_id ) : '';
		$is_st  = ( 'new' === $r->status && ( $now - strtotime( $r->created_at ) ) > 14 * DAY_IN_SECONDS );
		$rowbg  = 'verified' === $r->status ? ' style="background:#f1f5ec"' : '';
		echo '<tr' . $rowbg . '>';
		echo '<td><a href="' . esc_url( get_edit_post_link( (int) $r->post_id ) ) . '" target="_blank">' . esc_html( $title ) . '</a></td>';
		echo '<td>' . esc_html( $r->name ) . '</td>';
		echo '<td>' . esc_html( $r->email ) . '</td>';
		echo '<td>' . esc_html( $r->phone ? $r->phone : '—' ) . '</td>';
		echo '<td>';
		if ( $cphone ) {
			echo '<a href="tel:' . esc_attr( preg_replace( '/[^0-9+]/', '', $cphone ) ) . '" style="font-size:16px;font-weight:600">' . esc_html( $cphone ) . '</a>';
		} else {
			echo '<span style="color:#b3261e">нет телефона в карточке</span>';
		}
		echo '</td>';
		echo '<td>' . esc_html( isset( $roles[ $r->role ] ) ? $roles[ $r->role ] : $r->role ) . '</td>';
		echo '<td>' . esc_html( $r->status ) . ( $is_st ? '<br><span style="color:#8c8f94">просрочена</span>' : '' ) . '</td>';
		echo '<td>' . esc_html( mysql2date( 'd.m.Y H:i', $r->created_at ) ) . '</td>';
		echo '<td>';
		if ( 'new' === $r->status || 'verified' === $r->status ) {
			$bind = add_query_arg( array( 'bc_claim_action' => 'bind', 'cid' => $r->id, '_wpnonce' => $nonce ), $base );
			echo '<a class="button button-primary" href="' . esc_url( $bind ) . '">Подтвердить звонком → Привязать</a><br style="margin-bottom:4px">';
			echo '<form method="get" style="display:inline">';
			echo '<input type="hidden" name="page" value="bc-claims">';
			echo '<input type="hidden" name="bc_claim_action" value="reject">';
			echo '<input type="hidden" name="cid" value="' . (int) $r->id . '">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $nonce ) . '">';
			echo '<select name="bc_reason" style="margin-top:4px"><option value="">— причина —</option><option>не удалось подтвердить по телефону</option><option>заявка не подтверждена организацией</option><option>дубликат</option><option>не относится к организации</option></select> ';
			echo '<button class="button">Отклонить</button>';
			echo '</form>';
		} else {
			echo '—';
		}
		echo '</td>';
		echo '</tr>';
	}
	echo '</tbody></table>';
}

/**
 * Таблица организаций по источнику: витрина (без владельца) / клейменные.
 *
 * @param bool $showcase true — витрина, false — клейменные.
 */
function bc_claims_render_orgs( $showcase ) {
	$q = new WP_Query(
		array(
			'post_type'      => 'organizations',
			'post_status'    => array( 'publish', 'pending', 'draft' ),
			'posts_per_page' => 200,
			'orderby'        => 'date',
			'order'          => 'DESC',
		)
	);
	echo '<table class="widefat striped"><thead><tr><th>Карточка</th><th>Статус WP</th><th>Автор</th><th>Тариф</th><th>Источник</th></tr></thead><tbody>';
	$shown = 0;
	if ( $q->have_posts() ) {
		while ( $q->have_posts() ) {
			$q->the_post();
			$id      = get_the_ID();
			$claimed = function_exists( 'bc_is_claimed' ) && bc_is_claimed( $id );
			if ( $claimed !== ( ! $showcase ) ) {
				continue;
			}
			$shown++;
			$author = (int) get_post_field( 'post_author', $id );
			$u      = get_userdata( $author );
			$plan   = function_exists( 'bc_plan' ) ? bc_plan( $id ) : 'free';
			echo '<tr>';
			echo '<td><a href="' . esc_url( get_edit_post_link( $id ) ) . '" target="_blank">' . esc_html( get_the_title() ) . '</a></td>';
			echo '<td>' . esc_html( get_post_status() ) . '</td>';
			echo '<td>' . esc_html( $u ? $u->user_login : '—' ) . '</td>';
			echo '<td>' . esc_html( $plan ) . '</td>';
			echo '<td>' . ( $claimed ? 'клейменная' : 'витрина' ) . '</td>';
			echo '</tr>';
		}
		wp_reset_postdata();
	}
	if ( ! $shown ) {
		echo '<tr><td colspan="5">Нет карточек в этом состоянии.</td></tr>';
	}
	echo '</tbody></table>';
}

/**
 * Экран «Отзывы» — модерация отзывов организаций (Этап 4.5, D40).
 */
function bc_reviews_page() {
	global $wpdb;
	$notice = '';
	if ( isset( $_GET['bc_review_action'], $_GET['crid'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'bc_review_admin' ) ) {
		$crid   = (int) $_GET['crid'];
		$action = sanitize_key( wp_unslash( $_GET['bc_review_action'] ) );
		if ( get_comment( $crid ) ) {
			if ( 'approve' === $action ) {
				wp_set_comment_status( $crid, 'approve' );
				$notice = 'Отзыв одобрен и опубликован.';
			} elseif ( 'reject' === $action ) {
				wp_set_comment_status( $crid, 'trash' );
				$notice = 'Отзыв отклонён.';
			}
		}
	}
	$rows  = $wpdb->get_results( "SELECT c.* FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID WHERE p.post_type = 'organizations' AND c.comment_approved = '0' ORDER BY c.comment_date DESC LIMIT 100" );
	$nonce = wp_create_nonce( 'bc_review_admin' );
	$base  = admin_url( 'admin.php?page=bc-reviews' );
	echo '<div class="wrap"><h1>Отзывы на модерации</h1>';
	if ( $notice ) {
		echo '<div class="notice notice-success is-dismissible"><p>' . esc_html( $notice ) . '</p></div>';
	}
	echo '<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:14px 16px;margin:12px 0">';
	echo '<strong style="font-size:14px">Отзывы — как модерировать</strong>';
	echo '<ul style="margin:8px 0 0;padding-left:18px;color:#50575e">';
	echo '<li style="margin:3px 0">«Одобрить» публикует отзыв на карточке, «Отклонить» — в корзину.</li>';
	echo '<li style="margin:3px 0">Организация на тарифе Стандарт+ может ответить на отзыв — ответ виден под отзывом.</li>';
	echo '<li style="margin:3px 0">Оценка 1–5 идёт в средний рейтинг карточки.</li>';
	echo '</ul></div>';
	if ( ! $rows ) {
		echo '<p>Очередь пуста — отзывов на модерации нет.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Организация</th><th>Автор</th><th>Оценка</th><th>Отзыв</th><th>Дата</th><th>Действия</th></tr></thead><tbody>';
		foreach ( $rows as $r ) {
			$title = get_the_title( (int) $r->comment_post_ID );
			$rate  = function_exists( 'bc_review_rating' ) ? bc_review_rating( $r->comment_ID ) : 0;
			$appr  = add_query_arg( array( 'bc_review_action' => 'approve', 'crid' => $r->comment_ID, '_wpnonce' => $nonce ), $base );
			$rej   = add_query_arg( array( 'bc_review_action' => 'reject', 'crid' => $r->comment_ID, '_wpnonce' => $nonce ), $base );
			echo '<tr>';
			echo '<td><a href="' . esc_url( get_edit_post_link( (int) $r->comment_post_ID ) ) . '" target="_blank">' . esc_html( $title ) . '</a></td>';
			echo '<td>' . esc_html( $r->comment_author ) . '</td>';
			echo '<td>' . ( $rate ? esc_html( $rate . ' / 5' ) : '—' ) . '</td>';
			echo '<td>' . esc_html( wp_trim_words( $r->comment_content, 25 ) ) . '</td>';
			echo '<td>' . esc_html( mysql2date( 'd.m.Y H:i', $r->comment_date ) ) . '</td>';
			echo '<td><a class="button button-primary" href="' . esc_url( $appr ) . '">Одобрить</a> <a class="button" href="' . esc_url( $rej ) . '">Отклонить</a></td>';
			echo '</tr>';
		}
		echo '</tbody></table>';
	}
	echo '</div>';
}
