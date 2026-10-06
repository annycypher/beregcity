<?php
/**
 * Подсказки и инструкции (шаг «3а-подсказки»): единая система У1/У2/У3.
 *
 * У1 — подсказка у поля (bc_hint для медиа + acf/load_field для всех полей).
 * У2 — инфо-блок экрана (bc_info_block).
 * У3 — ссылка «Подробнее в руководстве» → ADMINGUIDE.
 *
 * Медиа-требования — единый источник bc_media_specs() (правка в одном месте).
 * Тексты — в коде (bc-core), НЕ в БД: переносятся при деплое.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Требования к медиа (единый источник).
 */
function bc_media_specs() {
	return array(
		'banner_header'  => array( 'size' => '970×90', 'format' => 'jpg/png/webp', 'weight' => 'до 150 КБ' ),
		'banner_sidebar' => array( 'size' => '240×400', 'format' => 'jpg/png/webp', 'weight' => 'до 150 КБ' ),
		'banner_duo'     => array( 'size' => '470×120', 'format' => 'jpg/png/webp', 'weight' => 'до 150 КБ' ),
		'banner_slide'   => array( 'size' => '1920×1080', 'format' => 'jpg/png/webp', 'weight' => 'до 150 КБ' ),
		'story_slide'    => array( 'size' => '1080×1920 (вертикаль)', 'format' => 'jpg/png/webp', 'weight' => 'до 300 КБ' ),
		'org_photo'      => array( 'size' => 'до 1600px (горизонт)', 'format' => 'jpg/png/webp', 'weight' => 'до 200 КБ' ),
		'news_cover'     => array( 'size' => '1200×630', 'format' => 'jpg/png/webp', 'weight' => 'до 200 КБ' ),
		'event_cover'    => array( 'size' => '1200×630', 'format' => 'jpg/png/webp', 'weight' => 'до 200 КБ' ),
		'avatar'         => array( 'size' => '200×200', 'format' => 'jpg/png/webp', 'weight' => 'до 100 КБ' ),
	);
}

/**
 * У1: hint-строка «Размер: … · Формат: … · Вес: …».
 *
 * @param string $key Ключ из bc_media_specs().
 * @return string
 */
function bc_hint( $key ) {
	$s = bc_media_specs();
	if ( ! isset( $s[ $key ] ) ) {
		return '';
	}
	return 'Размер: ' . $s[ $key ]['size'] . ' · Формат: ' . $s[ $key ]['format'] . ' · Вес: ' . $s[ $key ]['weight'];
}

/**
 * У1: подсказки для всех ACF-полей (орг/баннеры/сторис/новости/события).
 * Применяются через acf/load_field — на админ-экранах и в ЛК (acf_form).
 */
function bc_acf_field_hints() {
	return array(
		// Организация (fields.php)
		'field_bc_phone'          => 'Формат: +7 900 000-00-00. Показывается на карточке.',
		'field_bc_excerpt'        => 'До 300 знаков. Кратко для списка каталога.',
		'field_bc_address'        => 'Улица и дом — нужны для карты.',
		'field_bc_lat'            => 'Широта. ПКМ на Яндекс.Картах → «Что здесь?» → первое число.',
		'field_bc_lng'            => 'Долгота. ПКМ на Яндекс.Картах → «Что здесь?» → второе число.',
		'field_bc_schedule'       => 'График по дням. Из него считается «открыто/закрыто» на карточке.',
		'field_bc_schedule_day'   => 'День недели.',
		'field_bc_schedule_from'  => 'Время открытия.',
		'field_bc_schedule_to'    => 'Время закрытия.',
		'field_bc_exceptions'     => 'Особые дни (праздники) — приоритет над обычным графиком.',
		'field_bc_exception_date' => 'Дата особого дня.',
		'field_bc_exception_note' => 'Часы или примечание (например, «выходной»).',
		'field_bc_website'        => 'Полный адрес с https://.',
		'field_bc_whatsapp'       => 'Номер +7… или ссылка wa.me.',
		'field_bc_telegram'       => 'Имя с @ или ссылка t.me.',
		'field_bc_vk'             => 'Ссылка на страницу или сообщество.',
		'field_bc_plan'           => 'Тариф: free — без снипетов и соцсетей.',
		'field_bc_plan_until'     => 'Дата окончания тарифа. Пусто — бессрочно.',
		'field_bc_trial_until'    => 'Дата окончания триала (7 дней).',
		'field_bc_is_verified'    => 'Галочка «проверено» — доверие посетителей.',
		'field_bc_keywords'       => 'Слова через запятую — для SEO-страниц каталога.',
		'field_bc_gallery'        => bc_hint( 'org_photo' ) . '. Горизонтальные, первое фото — обложка.',
		// Баннеры (cpt-banners.php)
		'field_bc_banner_zone'    => 'Куда показывается: header 970×90 · sidebar 240×400 · duo 470×120 · native.',
		'field_bc_banner_image'   => bc_hint( 'banner_header' ) . '. Размер — по зоне (см. выше).',
		'field_bc_banner_link'    => 'Куда ведёт клик.',
		'field_bc_banner_until'   => 'До какой даты. Пусто — всегда.',
		'field_bc_banner_ad'      => 'Рекламный — появится плашка «Реклама».',
		'field_bc_banner_erid'    => 'Токен рекламы — обязателен для рекламного баннера.',
		// Сторис (cpt-stories.php)
		'field_bc_slides'         => 'До 5 слайдов. Первый — обложка.',
		'field_bc_slide_image'    => bc_hint( 'story_slide' ),
		'field_bc_slide_caption'  => 'До 90 знаков, коротко и крупно.',
		'field_bc_slide_btn'      => 'До 20 знаков, например «Узнать больше».',
		'field_bc_slide_link'     => 'Куда ведёт кнопка.',
		'field_bc_slide_ad'       => 'Рекламный слайд — обязателен erid.',
		'field_bc_slide_erid'     => 'Токен рекламы. Без него рекламный слайд не сохранится.',
		'field_bc_show_until'     => 'До какой даты показывать. Пусто — вечно.',
		'field_bc_sort_order'     => 'Число: меньше — раньше в ленте.',
		// Новости (cpt-news.php)
		'field_bc_news_type'      => 'Новость / Акция (реклама) / Вакансия.',
		'field_bc_is_pinned'      => 'Закрепить вверху ленты.',
		'field_bc_erid'           => 'Токен рекламы — для типа «Акция · реклама».',
		// События (cpt-news.php)
		'field_bc_event_date'     => 'Дата события.',
		'field_bc_event_time'     => 'Время начала.',
		'field_bc_event_place'    => 'Адрес или место.',
		'field_bc_event_price'    => 'Цена, или пусто + «Бесплатно».',
		'field_bc_is_free'        => 'Бесплатное событие.',
	);
}

function bc_apply_field_hint( $field ) {
	$hints = bc_acf_field_hints();
	if ( ! empty( $field['key'] ) && isset( $hints[ $field['key'] ] ) ) {
		$field['instructions'] = $hints[ $field['key'] ];
	}
	return $field;
}
add_filter( 'acf/load_field', 'bc_apply_field_hint' );

/**
 * У2: инфо-блок экрана (заголовок + строки + опциональная ссылка на руководство).
 *
 * @param string $screen Ключ экрана.
 */
function bc_info_block( $screen ) {
	$b = bc_info_block_data( $screen );
	if ( ! $b ) {
		return;
	}
	echo '<div style="background:#f6f7f7;border:1px solid #dcdcde;border-radius:8px;padding:14px 16px;margin:12px 0">';
	echo '<strong style="font-size:14px">' . esc_html( $b['title'] ) . '</strong>';
	echo '<ul style="margin:8px 0 0;padding-left:18px;color:#50575e">';
	foreach ( $b['lines'] as $line ) {
		echo '<li style="margin:3px 0">' . wp_kses( $line, array( 'b' => array(), 'a' => array( 'href' => array() ), 'code' => array() ) ) . '</li>';
	}
	echo '</ul>';
	if ( ! empty( $b['guide'] ) ) {
		echo '<p style="margin:8px 0 0;color:#8c8f94;font-size:12px">Подробнее в руководстве: ' . esc_html( $b['guide'] ) . '</p>';
	}
	echo '</div>';
}

/**
 * Данные инфо-блоков по экранам.
 */
function bc_info_block_data( $screen ) {
	$blocks = array(
		'dashboard' => array(
			'title' => 'Что здесь',
			'lines' => array( 'Счётчики показывают, что ждёт вашего решения.', 'Карточка «Напоминания» ниже — автоматические задачи и ритуалы.', 'Ноль действий — «Всё чисто».' ),
			'guide' => 'ADMINGUIDE §«Напоминания и ритуалы»',
		),
		'approval' => array(
			'title' => 'Утверждение карточек',
			'lines' => array( 'Просматривайте карточку целиком через «предпросмотр как на сайте».', 'Колонка «Фрод» — подсказки, решение всегда за вами.', 'Одобрение — по одной, массового нет.' ),
			'guide' => 'ADMINGUIDE §«Каталог»',
		),
		'payments' => array(
			'title' => 'Платежи и счета',
			'lines' => array( 'Подтверждение активирует тариф и создаёт счёт.', 'Отклонение — только с причиной, организации придёт письмо.', 'Массовых действий нет.' ),
			'guide' => 'ADMINGUIDE §«Платежи»',
		),
		'stories' => array(
			'title' => 'Сторис — модерация',
			'lines' => array( 'Фото строго 1080×1920 (вертикаль), иначе стория выглядит обрезанной.', 'Рекламный слайд без erid не сохраняется (закон о рекламе).', 'Первая стория организации — всегда на проверке.' ),
			'guide' => 'ADMINGUIDE §«Сторис»',
		),
	);
	return isset( $blocks[ $screen ] ) ? $blocks[ $screen ] : null;
}

/**
 * Метабокс «Требования к медиа» на CPT-экранах (баннеры/сторис/новости/события/орг).
 */
function bc_hints_meta_boxes() {
	$map = array(
		'banners'       => array( 'banner_header', 'banner_duo', 'banner_sidebar' ),
		'stories'       => array( 'story_slide' ),
		'news'          => array( 'news_cover' ),
		'events'        => array( 'event_cover' ),
		'organizations' => array( 'avatar', 'org_photo' ),
	);
	foreach ( $map as $cpt => $keys ) {
		add_meta_box( 'bc-media-specs', 'Требования к медиа', function () use ( $keys ) {
			echo '<ul style="margin:0;padding-left:16px">';
			foreach ( $keys as $k ) {
				echo '<li style="margin:4px 0">' . esc_html( bc_hint( $k ) ) . '</li>';
			}
			echo '</ul>';
		}, $cpt, 'side', 'low' );
	}
}
add_action( 'add_meta_boxes', 'bc_hints_meta_boxes' );
