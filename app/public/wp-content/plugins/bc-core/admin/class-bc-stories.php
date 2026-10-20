<?php
/**
 * Админ «Сторис» (Этап 5.3): модерация сторис организаций.
 *
 * Статусы организаций: pending → publish (одобрено) | draft + причина (отклонено).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_stories_admin_menu() {
	add_submenu_page( 'beregcity', 'Сторис', 'Сторис', 'manage_options', 'bc-stories', 'bc_stories_moderation_page' );
}
add_action( 'admin_menu', 'bc_stories_admin_menu' );

/**
 * Рекламный слайд без erid → false (§9 stories.md).
 */
function bc_stories_validate_erid( $post_id ) {
	foreach ( (array) get_field( 'slides', $post_id ) as $sl ) {
		if ( ! empty( $sl['is_ad'] ) && empty( $sl['erid'] ) ) {
			return false;
		}
	}
	// ACF free: одно фото из ЛК — реклама требует erid отдельным полем.
	if ( function_exists( 'bc_story_single_slide' ) ) {
		$single = bc_story_single_slide( $post_id );
		if ( $single && ! empty( $single['ad'] ) && '' === trim( (string) get_field( 'bc_story_erid', $post_id ) ) ) {
			return false;
		}
	}
	return true;
}

function bc_stories_moderation_page() {
	if ( isset( $_GET['bc_st'], $_GET['post'], $_GET['_wpnonce'] ) && wp_verify_nonce( sanitize_key( wp_unslash( $_GET['_wpnonce'] ) ), 'bc_stories' ) ) {
		$post_id = (int) $_GET['post'];
		$action  = sanitize_key( wp_unslash( $_GET['bc_st'] ) );
		if ( 'stories' === get_post_type( $post_id ) ) {
			if ( 'approve' === $action ) {
				if ( bc_stories_validate_erid( $post_id ) ) {
					wp_update_post( array( 'ID' => $post_id, 'post_status' => 'publish' ) );
					$bc_author = (int) get_post_field( 'post_author', $post_id );
					$bc_email  = get_the_author_meta( 'user_email', $bc_author );
					if ( $bc_email ) {
						$bc_org = function_exists( 'bc_story_org_id' ) ? bc_story_org_id( $bc_author ) : 0;
						wp_mail(
							$bc_email,
							'БерегСити: сторис опубликовано',
							"Ваша сторис «" . get_the_title( $post_id ) . "» опубликована.\n\nСмотреть: " . ( $bc_org ? get_permalink( $bc_org ) : home_url( '/' ) )
						);
					}
					echo '<div class="notice notice-success"><p>Сторис одобрено и опубликовано.</p></div>';
				} else {
					echo '<div class="notice notice-error"><p>Нельзя одобрить: рекламный слайд без erid (§9 stories.md).</p></div>';
				}
			} elseif ( 'reject' === $action ) {
				$reason = isset( $_GET['bc_reason'] ) ? sanitize_text_field( wp_unslash( $_GET['bc_reason'] ) ) : '';
				wp_update_post( array( 'ID' => $post_id, 'post_status' => 'draft' ) );
				update_post_meta( $post_id, 'bc_reject_reason', $reason );
				$email = get_the_author_meta( 'user_email', (int) get_post_field( 'post_author', $post_id ) );
				if ( $email ) {
					wp_mail( $email, 'БерегСити: сторис отклонено', 'Сторис отклонено. Причина: ' . ( $reason ? $reason : 'не указана' ) . '.' );
				}
				echo '<div class="notice notice-success"><p>Сторис отклонено.</p></div>';
			}
		}
	}

	$q = new WP_Query( array( 'post_type' => 'stories', 'post_status' => 'pending', 'posts_per_page' => 50, 'orderby' => 'date', 'order' => 'DESC' ) );

	echo '<div class="wrap"><h1>Сторис — модерация</h1>';
	bc_info_block( 'stories' );
	if ( ! $q->have_posts() ) {
		echo '<p>Очередь пуста — сторис на модерации нет.</p>';
	} else {
		echo '<table class="widefat striped"><thead><tr><th>Сторис</th><th>Автор</th><th>Слайды</th><th>Предпросмотр</th><th>Действия</th></tr></thead><tbody>';
		while ( $q->have_posts() ) {
			$q->the_post();
			$id        = get_the_ID();
			$author_id = (int) get_post_field( 'post_author', $id );
			$author    = get_the_author_meta( 'display_name', $author_id );
			$org_id    = function_exists( 'bc_story_org_id' ) ? bc_story_org_id( $author_id ) : 0;
			$slides    = (array) get_field( 'slides', $id );
			$count     = count( $slides );
			$thumbs    = '';
			$img_id    = (int) get_field( 'bc_story_image', $id );

			// ACF free: одно вертикальное фото из ЛК.
			if ( $img_id ) {
				$thumbs .= wp_get_attachment_image( $img_id, 'medium', false, array( 'style' => 'width:64px;height:114px;object-fit:cover;border-radius:6px;margin-right:4px' ) );
				if ( ! $count ) {
					$count = 1;
				}
			}
			foreach ( array_slice( $slides, 0, 3 ) as $sl ) {
				if ( ! empty( $sl['image'] ) ) {
					$thumbs .= wp_get_attachment_image( (int) $sl['image'], 'thumbnail', false, array( 'style' => 'width:48px;height:85px;object-fit:cover;border-radius:4px;margin-right:4px' ) );
				}
			}
			$n   = wp_create_nonce( 'bc_stories' );
			$url = admin_url( 'admin.php?page=bc-stories' );
			echo '<tr>';
			echo '<td><strong>' . esc_html( get_the_title() ) . '</strong></td>';
			echo '<td>' . esc_html( $author ) . ( $org_id ? '<br><span class="description">' . esc_html( get_the_title( $org_id ) ) . '</span>' : '' ) . '</td>';
			echo '<td>' . (int) $count . '</td>';
			echo '<td>' . $thumbs . '</td>';
			echo '<td>';
			echo '<a class="button button-primary" href="' . esc_url( add_query_arg( array( 'bc_st' => 'approve', 'post' => $id, '_wpnonce' => $n ), $url ) ) . '">Одобрить</a> ';
			echo '<form method="get" style="display:inline">';
			echo '<input type="hidden" name="page" value="bc-stories">';
			echo '<input type="hidden" name="bc_st" value="reject">';
			echo '<input type="hidden" name="post" value="' . (int) $id . '">';
			echo '<input type="hidden" name="_wpnonce" value="' . esc_attr( $n ) . '">';
			echo '<select name="bc_reason"><option>не подходит по формату</option><option>фото не вертикальное</option><option>текст не соответствует</option><option>дубликат</option></select> ';
			echo '<button class="button">Отклонить</button>';
			echo '</form>';
			echo '</td></tr>';
		}
		wp_reset_postdata();
		echo '</tbody></table>';
	}
	echo '</div>';
}
