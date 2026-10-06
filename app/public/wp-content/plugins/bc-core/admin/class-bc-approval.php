<?php
/**
 * «Утверждение карточек» — очередь организаций pending (D24, Этап 3.5b).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Письмо организации о смене статуса.
 */
function bc_notify_org( $post_id, $action, $reason = '' ) {
	$author_id = (int) get_post_field( 'post_author', $post_id );
	$email     = get_the_author_meta( 'user_email', $author_id );
	if ( ! $email ) {
		return;
	}
	$subject = 'БерегСити: статус вашей карточки';
	if ( 'approved' === $action ) {
		$body = 'Ваша карточка «' . get_the_title( $post_id ) . '» одобрена и опубликована.';
	} elseif ( 'draft' === $action ) {
		$body = 'Ваша карточка «' . get_the_title( $post_id ) . '» возвращена на доработку. Проверьте заполнение полей в личном кабинете.';
	} else {
		$body = 'Ваша карточка «' . get_the_title( $post_id ) . '» отклонена. Причина: ' . ( $reason ? $reason : 'данных не хватает' ) . '.';
	}
	wp_mail( $email, $subject, $body );
}

/**
 * Страница очереди утверждения.
 */
function bc_approval_page() {
	// Обработка действия (одобрить/на доработку/отклонить) — только по одной, без массовых.
	if ( isset( $_GET['bc_action'], $_GET['post'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( $_GET['_wpnonce'] ), 'bc_approve' ) ) {
		$post_id = (int) $_GET['post'];
		$action  = sanitize_key( $_GET['bc_action'] );
		$reason  = isset( $_GET['bc_reason'] ) ? sanitize_text_field( $_GET['bc_reason'] ) : '';
		if ( 'organizations' === get_post_type( $post_id ) ) {
			if ( 'approve' === $action ) {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
				if ( class_exists( 'BC_Plans' ) && method_exists( 'BC_Plans', 'start_trial' ) ) {
					BC_Plans::start_trial( $post_id );
				}
				bc_notify_org( $post_id, 'approved' );
			} elseif ( 'draft' === $action ) {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
				bc_notify_org( $post_id, 'draft' );
			} elseif ( 'reject' === $action ) {
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'trash' ) );
				bc_notify_org( $post_id, 'rejected', $reason );
			}
			echo '<div class="notice notice-success is-dismissible"><p>Действие выполнено.</p></div>';
		}
	}

	$q = new WP_Query( array( 'post_type' => 'organizations', 'post_status' => 'pending', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );

	echo '<div class="wrap"><h1>Утверждение карточек</h1>';
	if ( ! $q->have_posts() ) {
		echo '<p>Очередь пуста — новых карточек на утверждении нет.</p>';
	} else {
		echo '<p>Карточки просматриваются целиком в предпросмотре «как на сайте». Одобрение — по одной, массового одобрения нет (D24).</p>';
		echo '<table class="widefat striped"><thead><tr><th>Название</th><th>Категория</th><th>Тариф</th><th>Действия</th></tr></thead><tbody>';
		while ( $q->have_posts() ) {
			$q->the_post();
			$id   = get_the_ID();
			$cat  = get_the_terms( $id, 'bc_cat' );
			$cat  = ( $cat && ! is_wp_error( $cat ) ) ? $cat[0]->name : '—';
			$plan = function_exists( 'bc_plan' ) ? bc_plan( $id ) : 'free';
			$url  = admin_url( 'admin.php?page=bc-approval' );
			$n    = wp_create_nonce( 'bc_approve' );
			echo '<tr>';
			echo '<td><strong>' . esc_html( get_the_title() ) . '</strong><br><a href="' . esc_url( get_preview_post_link( $id ) ) . '" target="_blank">предпросмотр «как на сайте»</a></td>';
			echo '<td>' . esc_html( $cat ) . '</td>';
			echo '<td>' . esc_html( $plan ) . '</td>';
			echo '<td>';
			echo '<a class="button button-primary" href="' . esc_url( add_query_arg( array( 'bc_action' => 'approve', 'post' => $id, '_wpnonce' => $n ), $url ) ) . '">Одобрить</a> ';
			echo '<a class="button" href="' . esc_url( add_query_arg( array( 'bc_action' => 'draft', 'post' => $id, '_wpnonce' => $n ), $url ) ) . '">На доработку</a> ';
			echo '<form method="get" style="display:inline">';
			echo '<input type="hidden" name="page" value="bc-approval">';
			echo '<input type="hidden" name="bc_action" value="reject">';
			echo '<input type="hidden" name="post" value="' . (int) $id . '">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $n ) . '">';
			echo '<select name="bc_reason"><option>нет фото</option><option>данных не хватает</option><option>не относится к району</option><option>дубликат</option></select> ';
			echo '<button class="button">Отклонить</button>';
			echo '</form>';
			echo '</td></tr>';
		}
		wp_reset_postdata();
		echo '</tbody></table>';
	}
	echo '</div>';
}
