<?php
/**
 * CPT «баннеры» + ACF (Этап 6.1, карта данных ПРОТОКОЛа стр. 117).
 *
 * Баннер: зона (hero/wide/duo/sidebar/native), картинка (+ мобильная),
 * заголовок/текст/текст кнопки (+ короткий мобильный текст), ссылка,
 * период (until), is_ad, erid.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

function bc_register_cpt_banners() {
register_post_type(
'banners',
array(
'labels'       => array(
'name'          => 'Баннеры',
'singular_name' => 'Баннер',
'add_new_item'  => 'Добавить баннер',
'edit_item'     => 'Редактировать баннер',
),
'public'       => false,
'show_ui'      => true,
'menu_icon'    => 'dashicons-images-alt2',
'supports'     => array( 'title', 'page-attributes' ),
'show_in_rest' => false,
)
);
}
add_action( 'init', 'bc_register_cpt_banners' );

function bc_acf_banners_fields() {
if ( ! function_exists( 'acf_add_local_field_group' ) ) {
return;
}
acf_add_local_field_group(
array(
'key'      => 'group_bc_banner',
'title'    => 'Параметры баннера',
'fields'   => array(
array(
'key'     => 'field_bc_banner_zone',
'label'   => 'Зона',
'name'    => 'banner_zone',
'type'    => 'select',
					'instructions' => 'Размеры: главный баннер — 1920×740 (моб. 800×800); видео-фон — 1280×640 на ПК и 720×900 на телефоне (6–8 с, без звука, до 2 МБ / 1 МБ); под шапкой — 970×90; после главного баннера — 1200×200; под блоками разделов и низ карточки — 970×120; в конце главной — 1200×160; сайдбар — 300×600 или 300×250; баннер карты — десктоп 1200×300, мобильный 720×280.',
'choices' => array(
						'hero'            => 'Главный баннер (слайдер)',
						'under_header'    => 'Под шапкой',
						'after_hero'      => 'После главного баннера',
						'after_sections'  => 'Под блоками разделов',
						'home_end'        => 'В конце главной',
						'catalog_sidebar' => 'Сайдбар каталога',
						'org_bottom'      => 'Низ карточки организации',
						'map'             => 'Баннер карты',
),
),
array( 'key' => 'field_bc_banner_image', 'label' => 'Изображение (компьютер)', 'name' => 'banner_image', 'type' => 'image', 'return_format' => 'id', 'instructions' => 'Широкая картинка для слайдера главной: 1920×740 px, WebP/JPG, до 200 КБ.' ),
array( 'key' => 'field_bc_banner_image_mobile', 'label' => 'Изображение для мобильных', 'name' => 'banner_image_mobile', 'type' => 'image', 'return_format' => 'id', 'instructions' => 'Квадратная картинка для телефона: 800×800 px. Если пусто — на телефоне покажется картинка для компьютера (обрежется по краям).' ),
array(
	'key'           => 'field_bc_banner_video',
	'label'         => 'Видео (компьютер)',
	'name'          => 'banner_video',
	'type'          => 'file',
	'return_format' => 'url',
	'library'       => 'all',
	'mime_types'    => 'mp4,webm',
	'instructions'  => 'Фон вместо картинки: MP4 (H.264), 1280×640 (2:1), 6–8 секунд, без звука, до 2 МБ. Постер берётся из «Изображение (компьютер)» — заполните и его (это картинка до старта видео и запасной вариант).',
),
array(
	'key'           => 'field_bc_banner_video_mobile',
	'label'         => 'Видео (мобильные)',
	'name'          => 'banner_video_mobile',
	'type'          => 'file',
	'return_format' => 'url',
	'library'       => 'all',
	'mime_types'    => 'mp4,webm',
	'instructions'  => 'Вертикальное видео для телефона: 720×900 (4:5), 6–8 секунд, без звука, до 1 МБ. Если пусто — на телефоне покажется компьютерное видео (обрежется по краям).',
),
				array( 'key' => 'field_bc_banner_html', 'label' => 'HTML/код (для рекламных сетей)', 'name' => 'banner_html', 'type' => 'textarea', 'rows' => 4, 'instructions' => 'Код рекламной сети. Используется, только если не выбрана картинка.' ),
				array( 'key' => 'field_bc_banner_show', 'label' => 'Показывать', 'name' => 'banner_show', 'type' => 'true_false', 'default_value' => 0, 'ui' => 1, 'instructions' => 'По умолчанию выключено: баннер не выводится, пока галочка не стоит.' ),
array( 'key' => 'field_bc_banner_title', 'label' => 'Заголовок (крупно)', 'name' => 'banner_title', 'type' => 'text', 'instructions' => '1 строка, до ~50 символов — больше на телефоне не влезет.' ),
array( 'key' => 'field_bc_banner_text', 'label' => 'Текст', 'name' => 'banner_text', 'type' => 'textarea', 'rows' => 3, 'instructions' => 'Для компьютера: 2–3 короткие строки.' ),
array( 'key' => 'field_bc_banner_text_mobile', 'label' => 'Короткий текст для мобильных', 'name' => 'banner_text_mobile', 'type' => 'textarea', 'rows' => 2, 'instructions' => '1–2 фразы, на мобиле места мало. Если пусто — покажется обычный текст.' ),
array( 'key' => 'field_bc_banner_button_text', 'label' => 'Текст кнопки', 'name' => 'banner_button_text', 'type' => 'text', 'instructions' => 'Например: «Смотреть каталог». Если пусто — «Подробнее».' ),
array( 'key' => 'field_bc_banner_link', 'label' => 'Ссылка (куда ведёт кнопка)', 'name' => 'banner_link', 'type' => 'url', 'instructions' => 'Своя страница: /katalog/ . Внешний сайт: https://пример.рф/ . Пусто — переход на главную.' ),
array( 'key' => 'field_bc_banner_until', 'label' => 'Показывать до (пусто — вечно)', 'name' => 'banner_until', 'type' => 'date_picker', 'display_format' => 'd.m.Y', 'return_format' => 'Y-m-d' ),
array( 'key' => 'field_bc_banner_ad', 'label' => 'Рекламный', 'name' => 'banner_ad', 'type' => 'true_false' ),
array( 'key' => 'field_bc_banner_erid', 'label' => 'erid (обязателен для рекламы)', 'name' => 'banner_erid', 'type' => 'text' ),
),
'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'banners' ) ) ),
)
);
}
add_action( 'acf/init', 'bc_acf_banners_fields' );