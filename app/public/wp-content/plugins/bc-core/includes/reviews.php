<?php
/**
 * Отзывы организаций (Этап 4.5, D40): форма + модерация + вывод + ответы организации.
 *
 * Отзыв = комментарий к организации (comment meta bc_rating 1-5).
 * Модерация: hold -> approve. Форма защищена bc_antispam_*(review) и bc_consent_*.
 * Ответ организации — comment meta bc_org_reply (тариф Стандарт+).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Разрешаем комментарии у организаций (отзывы живут в wp_comments).
 */
function bc_reviews_enable_comments() {
	add_post_type_support( 'organizations', 'comments' );
}
add_action( 'init', 'bc_reviews_enable_comments', 20 );

/**
 * Рейтинг отзыва (1-5) или 0.
 */
function bc_review_rating( $comment_id ) {
	$r = (int) get_comment_meta( $comment_id, 'bc_rating', true );
	return ( $r >= 1 && $r <= 5 ) ? $r : 0;
}

/**
 * Сводка по отзывам: количество и средний рейтинг.
 */
function bc_reviews_summary( $post_id ) {
	$comments = get_comments( array( 'post_id' => $post_id, 'status' => 'approve', 'type' => 'comment' ) );
	$sum = 0;
	$n   = 0;
	foreach ( (array) $comments as $cm ) {
		$r = bc_review_rating( $cm->comment_ID );
		if ( $r ) {
			$sum += $r;
			$n++;
		}
	}
	return array( 'count' => $n, 'avg' => $n ? round( $sum / $n, 1 ) : 0 );
}

/**
 * SVG-звёзды рейтинга.
 */
function bc_review_stars( $rating ) {
	$out = '';
	for ( $i = 1; $i <= 5; $i++ ) {
		$cls  = ( $i <= (int) round( $rating ) ) ? '' : ' class="off"';
		$out .= '<svg' . $cls . ' viewBox="0 0 24 24"><path d="M12 2l2.9 6.6 7.1.6-5.4 4.7 1.6 7-6.2-3.7-6.2 3.7 1.6-7L2 9.2l7.1-.6z"/></svg>';
	}
	return $out;
}

/**
 * Подпись оценки.
 */
function bc_review_label( $n ) {
	$map = array( 1 => 'Плохо', 2 => 'Так себе', 3 => 'Нормально', 4 => 'Хорошо', 5 => 'Отлично' );
	return isset( $map[ $n ] ) ? $map[ $n ] : '';
}

/**
 * Склонение слова «отзыв».
 */
function bc_review_word( $n ) {
	$n = (int) $n;
	if ( 1 === $n % 10 && 11 !== $n % 100 ) {
		return 'отзыв';
	}
	if ( in_array( $n % 10, array( 2, 3, 4 ), true ) && ! in_array( $n % 100, array( 12, 13, 14 ), true ) ) {
		return 'отзыва';
	}
	return 'отзывов';
}

/**
 * Ответ организации (comment meta).
 */
function bc_review_reply( $comment_id ) {
	return (string) get_comment_meta( $comment_id, 'bc_org_reply', true );
}

/**
 * Может ли текущий пользователь отвечать (владелец организации, тариф Стандарт+).
 */
function bc_review_can_reply( $post_id ) {
	if ( ! is_user_logged_in() || get_current_user_id() !== (int) get_post_field( 'post_author', $post_id ) ) {
		return false;
	}
	$plan = function_exists( 'bc_plan' ) ? bc_plan( $post_id ) : 'free';
	return in_array( $plan, array( 'standard', 'premium' ), true );
}

/**
 * Количество отзывов на модерации (для дашборда).
 */
function bc_reviews_queue_count() {
	global $wpdb;
	return (int) $wpdb->get_var( "SELECT COUNT(*) FROM {$wpdb->comments} c INNER JOIN {$wpdb->posts} p ON p.ID = c.comment_post_ID WHERE p.post_type = 'organizations' AND c.comment_approved = '0'" );
}

/**
 * Блок отзывов: сводка + список + форма + ответ организации.
 */
function bc_reviews_block( $post_id ) {
	$codes   = array(
		'ok'      => array( 'ok', 'Спасибо! Отзыв отправлен — появится после проверки редакцией.' ),
		'replyok' => array( 'ok', 'Ответ опубликован.' ),
		'limit'   => array( 'err', 'Слишком много отправок, попробуйте позже.' ),
		'consent' => array( 'err', 'Отметьте согласие на обработку персональных данных.' ),
		'invalid' => array( 'err', 'Заполните имя, оценку и текст отзыва.' ),
		'session' => array( 'err', 'Сессия устарела, обновите страницу.' ),
	);
	$code    = isset( $_GET['bc_review'] ) ? sanitize_key( wp_unslash( $_GET['bc_review'] ) ) : '';
	$message = isset( $codes[ $code ] ) ? $codes[ $code ] : null;
	$sum     = bc_reviews_summary( $post_id );
	$list    = get_comments( array( 'post_id' => $post_id, 'status' => 'approve', 'type' => 'comment' ) );
	$can_rep = bc_review_can_reply( $post_id );
	$author  = is_user_logged_in() ? wp_get_current_user()->display_name : '';
	ob_start();
	?>
	<div class="revs" id="bc-reviews">
		<?php if ( $message ) : ?>
			<div class="rev-msg" style="margin-bottom:12px;font-size:13px;color:<?php echo 'ok' === $message[0] ? '#1a7a4a' : '#b0755c'; ?>"><?php echo esc_html( $message[1] ); ?></div>
		<?php endif; ?>
		<div class="rev-sum">
			<span class="num"><?php echo $sum['count'] ? esc_html( number_format_i18n( $sum['avg'], 1 ) ) : '—'; ?></span>
			<?php if ( $sum['count'] ) : ?><span class="stars"><?php echo bc_review_stars( $sum['avg'] ); ?></span><?php endif; ?>
			<span><?php echo $sum['count'] ? esc_html( $sum['count'] . ' ' . bc_review_word( $sum['count'] ) . ' · модерация перед публикацией' ) : 'Отзывов пока нет — станьте первым'; ?></span>
		</div>
		<?php foreach ( (array) $list as $cm ) : ?>
			<?php $r = bc_review_rating( $cm->comment_ID ); ?>
			<article class="rev">
				<div class="who"><span class="av"><?php echo esc_html( mb_substr( $cm->comment_author, 0, 1 ) ); ?></span><span><b><?php echo esc_html( $cm->comment_author ); ?></b><time><?php echo esc_html( human_time_diff( strtotime( $cm->comment_date ), strtotime( current_time( 'mysql' ) ) ) . ' назад' ); ?></time></span><?php if ( $r ) : ?><span class="stars"><?php echo bc_review_stars( $r ); ?></span><?php endif; ?></div>
				<p><?php echo nl2br( esc_html( $cm->comment_content ) ); ?></p>
				<?php $reply = bc_review_reply( $cm->comment_ID ); ?>
				<?php if ( $reply ) : ?><div class="rev-reply"><b>Ответ организации</b><?php echo nl2br( esc_html( $reply ) ); ?></div><?php endif; ?>
				<?php if ( $can_rep && ! $reply ) : ?>
					<details style="margin-top:10px">
						<summary class="btn btn-glass btn-sm">Ответить</summary>
						<form method="post" style="margin-top:10px">
							<div class="f"><textarea name="bc_reply_text" rows="2" required></textarea></div>
							<input type="hidden" name="bc_reply_cid" value="<?php echo (int) $cm->comment_ID; ?>">
							<?php wp_nonce_field( 'bc_reply', 'bc_reply_nonce' ); ?>
							<button class="btn btn-terra btn-sm" type="submit">Отправить ответ</button>
						</form>
					</details>
				<?php endif; ?>
			</article>
		<?php endforeach; ?>
		<details class="rev-add"<?php echo ( $message && 'err' === $message[0] ) ? ' open' : ''; ?>>
			<summary class="btn btn-glass btn-sm">Написать отзыв</summary>
			<form method="post" style="margin-top:12px;max-width:620px">
				<div class="f"><label>Ваше имя <b>*</b></label><input name="bc_review_name" value="<?php echo esc_attr( $author ); ?>" required></div>
				<div class="f"><label>Оценка <b>*</b></label>
					<select name="bc_review_rating">
						<?php for ( $i = 5; $i >= 1; $i-- ) : ?>
							<option value="<?php echo (int) $i; ?>"><?php echo (int) $i; ?> — <?php echo esc_html( bc_review_label( $i ) ); ?></option>
						<?php endfor; ?>
					</select>
				</div>
				<div class="f"><label>Отзыв <b>*</b></label><textarea name="bc_review_text" rows="4" required></textarea></div>
				<?php bc_consent_field(); ?>
				<?php bc_antispam_field( 'review' ); ?>
				<?php wp_nonce_field( 'bc_review', 'bc_review_nonce' ); ?>
				<button class="btn btn-terra" type="submit">Отправить отзыв</button>
			</form>
		</details>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * [bc_reviews] — блок отзывов на карточке организации.
 */
function bc_reviews_shortcode() {
	if ( ! is_singular( 'organizations' ) ) {
		return '';
	}
	return bc_reviews_block( (int) get_the_ID() );
}
add_shortcode( 'bc_reviews', 'bc_reviews_shortcode' );

/**
 * Редирект после действия (POST-Redirect-GET).
 */
function bc_review_redirect( $post_id, $code ) {
	$link = get_permalink( (int) $post_id );
	if ( ! $link || is_wp_error( $link ) ) {
		$link = home_url( '/' );
	}
	wp_safe_redirect( add_query_arg( 'bc_review', $code, $link ) . '#bc-reviews' );
	exit;
}

/**
 * Обработка отправки отзыва и ответа организации.
 */
function bc_review_template_redirect() {
	if ( ! is_singular( 'organizations' ) ) {
		return;
	}
	$post_id = (int) get_queried_object_id();

	if ( isset( $_POST['bc_reply_nonce'] ) ) {
		if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_reply_nonce'] ) ), 'bc_reply' ) || ! bc_review_can_reply( $post_id ) ) {
			bc_review_redirect( $post_id, 'session' );
		}
		$cid = isset( $_POST['bc_reply_cid'] ) ? (int) $_POST['bc_reply_cid'] : 0;
		$cm  = $cid ? get_comment( $cid ) : null;
		$txt = sanitize_textarea_field( wp_unslash( $_POST['bc_reply_text'] ?? '' ) );
		if ( ! $cm || (int) $cm->comment_post_ID !== $post_id || '' === $txt ) {
			bc_review_redirect( $post_id, 'invalid' );
		}
		update_comment_meta( $cid, 'bc_org_reply', $txt );
		bc_review_redirect( $post_id, 'replyok' );
	}

	if ( empty( $_POST['bc_review_nonce'] ) ) {
		return;
	}
	if ( ! wp_verify_nonce( sanitize_key( wp_unslash( $_POST['bc_review_nonce'] ) ), 'bc_review' ) ) {
		bc_review_redirect( $post_id, 'session' );
	}
	$as = bc_antispam_check( 'review' );
	if ( BC_AS_SILENT === $as ) {
		bc_review_redirect( $post_id, 'ok' );
	}
	if ( BC_AS_LIMIT === $as ) {
		bc_review_redirect( $post_id, 'limit' );
	}
	if ( ! bc_consent_check() ) {
		bc_review_redirect( $post_id, 'consent' );
	}
	$name   = sanitize_text_field( wp_unslash( $_POST['bc_review_name'] ?? '' ) );
	$rating = isset( $_POST['bc_review_rating'] ) ? (int) $_POST['bc_review_rating'] : 0;
	$text   = sanitize_textarea_field( wp_unslash( $_POST['bc_review_text'] ?? '' ) );
	if ( '' === $name || $rating < 1 || $rating > 5 || '' === $text ) {
		bc_review_redirect( $post_id, 'invalid' );
	}
	$cid = wp_insert_comment(
		array(
			'comment_post_ID'   => $post_id,
			'comment_author'    => $name,
			'comment_content'   => $text,
			'comment_approved'  => 0,
			'comment_type'      => 'comment',
			'comment_author_IP' => function_exists( 'bc_client_ip' ) ? bc_client_ip() : '',
			'comment_agent'     => '',
			'comment_date'      => current_time( 'mysql' ),
			'comment_date_gmt'  => current_time( 'mysql', 1 ),
		)
	);
	if ( ! $cid ) {
		bc_review_redirect( $post_id, 'invalid' );
	}
	update_comment_meta( $cid, 'bc_rating', $rating );
	if ( function_exists( 'bc_consent_log' ) ) {
		bc_consent_log( 'review', $cid, array( 'post_id' => $post_id ) );
	}
	if ( function_exists( 'bc_mail' ) ) {
		$body = 'Организация: ' . get_the_title( $post_id ) . ' | Автор: ' . $name . ' | Оценка: ' . $rating . ' | ' . $text;
		bc_mail( get_option( 'admin_email' ), 'БерегСити: новый отзыв на проверку', $body );
	}
	bc_review_redirect( $post_id, 'ok' );
}
add_action( 'template_redirect', 'bc_review_template_redirect' );