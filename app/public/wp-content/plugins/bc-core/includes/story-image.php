<?php
/**
 * Сторис организации: одно вертикальное фото (Этап 5.4, ACF free).
 *
 * Валидация вертикали, оптимизация веса без обрезки, рамка 9:16 с подложкой,
 * лимиты и уведомление редакции о новой сторис на модерации.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Требования к фото сторис (единый источник для валидации и подсказок).
 *
 * @return array
 */
function bc_story_spec() {
	return array(
		'min_w'  => 720,
		'min_h'  => 1080,
		'ratio'  => 1.2,   // высота / ширина: меньше — значит фото не вертикальное.
		'max_w'  => 1440,  // шире — ужмём при сохранении (без обрезки).
		'max_kb' => 300,
		'mimes'  => array( 'image/jpeg', 'image/png', 'image/webp' ),
	);
}

/**
 * Лимиты сторис на организацию (pending — на модерации, publish — активные).
 *
 * @return array
 */
function bc_story_limits() {
	return wp_parse_args(
		(array) apply_filters( 'bc_stories_limits', array( 'pending' => 1, 'publish' => 3 ) ),
		array( 'pending' => 1, 'publish' => 3 )
	);
}

/**
 * Организация пользователя (ID) или 0.
 *
 * @param int $user_id Пользователь (0 — текущий).
 * @return int
 */
function bc_story_org_id( $user_id = 0 ) {
	$user_id = $user_id ? (int) $user_id : get_current_user_id();
	if ( ! $user_id ) {
		return 0;
	}
	$orgs = get_posts(
		array(
			'post_type'     => 'organizations',
			'author'        => $user_id,
			'post_status'   => 'any',
			'numberposts'   => 1,
			'fields'        => 'ids',
			'no_found_rows' => true,
		)
	);
	return $orgs ? (int) $orgs[0] : 0;
}

/**
 * Текст ошибки для фото сторис ('' — всё в порядке).
 *
 * @param int $attachment_id Вложение.
 * @return string
 */
function bc_story_image_error( $attachment_id ) {
	$attachment_id = (int) $attachment_id;
	if ( ! $attachment_id || 'attachment' !== get_post_type( $attachment_id ) ) {
		return 'Прикрепите фото с компьютера или телефона.';
	}

	$spec = bc_story_spec();
	$mime = (string) get_post_mime_type( $attachment_id );
	if ( ! in_array( $mime, $spec['mimes'], true ) ) {
		return 'Формат не подходит: нужен JPG, PNG или WebP.';
	}

	$meta = wp_get_attachment_metadata( $attachment_id );
	$w    = isset( $meta['width'] ) ? (int) $meta['width'] : 0;
	$h    = isset( $meta['height'] ) ? (int) $meta['height'] : 0;
	if ( ! $w || ! $h ) {
		$file = get_attached_file( $attachment_id );
		if ( $file && file_exists( $file ) ) {
			$size = @getimagesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
			$w    = $size ? (int) $size[0] : 0;
			$h    = $size ? (int) $size[1] : 0;
		}
	}
	if ( ! $w || ! $h ) {
		return 'Не удалось прочитать изображение — загрузите другой файл.';
	}
	if ( $w >= $h || ( $h / $w ) < $spec['ratio'] ) {
		return 'Нужно вертикальное фото (например, 1080×1920). Горизонтальные и квадратные не подходят.';
	}
	if ( $w < $spec['min_w'] || $h < $spec['min_h'] ) {
		return sprintf(
			'Фото слишком маленькое: минимум %d×%d, рекомендуем 1080×1920.',
			(int) $spec['min_w'],
			(int) $spec['min_h']
		);
	}

	return '';
}

/**
 * ACF: проверка поля «Вертикальное фото» до сохранения.
 *
 * @param mixed $valid Результат проверки.
 * @param mixed $value Значение поля.
 * @param array $field Поле.
 * @param mixed $input Ввод.
 * @return mixed
 */
function bc_story_acf_validate_image( $valid, $value, $field, $input ) {
	if ( true !== $valid || '' === $value || null === $value ) {
		return $valid;
	}
	$error = bc_story_image_error( (int) $value );
	return $error ? $error : $valid;
}
add_filter( 'acf/validate_value/key=field_bc_story_image', 'bc_story_acf_validate_image', 10, 4 );

/**
 * Оптимизация веса при сохранении: только уменьшение ширины и качества, без обрезки.
 *
 * @param int|string $post_id ID записи.
 */
function bc_story_optimize_image( $post_id ) {
	if ( ! is_numeric( $post_id ) || 'stories' !== get_post_type( (int) $post_id ) || ! function_exists( 'get_field' ) ) {
		return;
	}
	$id = (int) get_field( 'bc_story_image', (int) $post_id );
	if ( ! $id ) {
		return;
	}
	$file = get_attached_file( $id );
	if ( ! $file || ! file_exists( $file ) ) {
		return;
	}

	$spec   = bc_story_spec();
	$budget = (int) $spec['max_kb'] * 1024;
	$bytes  = (int) @filesize( $file ); // phpcs:ignore WordPress.PHP.NoSilencedErrors
	$meta   = wp_get_attachment_metadata( $id );
	$width  = isset( $meta['width'] ) ? (int) $meta['width'] : 0;

	if ( $bytes <= $budget && ( ! $width || $width <= $spec['max_w'] ) ) {
		return;
	}

	$editor = wp_get_image_editor( $file );
	if ( is_wp_error( $editor ) ) {
		return;
	}
	if ( $width > $spec['max_w'] ) {
		$editor->resize( (int) $spec['max_w'], null, false );
	}
	foreach ( array( 82, 72, 62 ) as $quality ) {
		$editor->set_quality( $quality );
		$saved = $editor->save( $file );
		if ( is_wp_error( $saved ) ) {
			return;
		}
		clearstatcache( true, $file );
		if ( (int) @filesize( $file ) <= $budget ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors
			break;
		}
	}

	require_once ABSPATH . 'wp-admin/includes/image.php';
	$new_meta = wp_generate_attachment_metadata( $id, $file );
	if ( $new_meta ) {
		wp_update_attachment_metadata( $id, $new_meta );
	}
}
add_action( 'acf/save_post', 'bc_story_optimize_image', 20 );

/**
 * Письмо редакции о новой сторис на модерации (однократно).
 *
 * @param int|string $post_id ID записи.
 */
function bc_story_notify_admin( $post_id ) {
	if ( ! is_numeric( $post_id ) ) {
		return;
	}
	$post_id = (int) $post_id;
	if ( 'stories' !== get_post_type( $post_id ) || 'pending' !== get_post_status( $post_id ) ) {
		return;
	}
	if ( get_post_meta( $post_id, 'bc_story_admin_notified', true ) ) {
		return;
	}

	$org   = bc_story_org_id( (int) get_post_field( 'post_author', $post_id ) );
	$name  = $org ? get_the_title( $org ) : 'организация №' . (int) get_post_field( 'post_author', $post_id );
	$body  = "Новая сторис на модерации.\n\n";
	$body .= 'Организация: ' . $name . "\n";
	$body .= 'Название: ' . get_the_title( $post_id ) . "\n";
	$body .= 'Модерация: ' . admin_url( 'admin.php?page=bc-stories' ) . "\n";

	wp_mail( get_option( 'admin_email' ), 'БерегСити: сторис на модерации', $body );
	update_post_meta( $post_id, 'bc_story_admin_notified', 1 );
}
add_action( 'acf/save_post', 'bc_story_notify_admin', 30 );

/**
 * Слайд из одного фото (ACF free). null — фото нет.
 *
 * @param int $post_id Сторис.
 * @return array|null
 */
function bc_story_single_slide( $post_id ) {
	$id = (int) get_field( 'bc_story_image', $post_id );
	if ( ! $id ) {
		return null;
	}
	$img = wp_get_attachment_image_url( $id, 'full' );
	if ( ! $img ) {
		return null;
	}
	return array(
		'img'     => $img,
		'caption' => (string) get_field( 'bc_story_caption', $post_id ),
		'link'    => (string) get_field( 'bc_story_link', $post_id ),
		'btn'     => (string) get_field( 'bc_story_btn', $post_id ),
		'ad'      => (bool) get_field( 'bc_story_ad', $post_id ),
		'thumb'   => (string) wp_get_attachment_image_url( $id, 'thumbnail' ),
	);
}

/**
 * Рамка 9:16 с подложкой из размытой копии (фото не обрезается).
 *
 * @param int    $attachment_id Вложение.
 * @param string $alt           Alt ('' — из вложения).
 * @param string $size          Размер WP.
 * @param string $class         Доп. класс.
 * @return string
 */
function bc_story_frame( $attachment_id, $alt = '', $size = 'large', $class = '' ) {
	$url = wp_get_attachment_image_url( (int) $attachment_id, $size );
	if ( ! $url ) {
		return '';
	}
	if ( '' === $alt ) {
		$alt = (string) get_the_title( (int) $attachment_id );
	}
	return '<div class="story-frame' . ( $class ? ' ' . esc_attr( $class ) : '' ) . '">'
		. '<img class="story-bg" src="' . esc_url( $url ) . '" alt="" aria-hidden="true" loading="lazy" decoding="async">'
		. '<img class="story-img" src="' . esc_url( $url ) . '" alt="' . esc_attr( $alt ) . '" loading="lazy" decoding="async">'
		. '</div>';
}
