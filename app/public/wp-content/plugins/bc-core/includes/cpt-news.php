<?php
/**
 * CPT «новости» и «события» + ACF-поля (Этап 4.1, карта данных ПРОТОКОЛа стр. 117).
 *
 * news: тип (новость/акция/вакансия), is_pinned, erid.
 * events: дата, место, цена, is_free.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_register_cpt_news() {
	register_post_type(
		'news',
		array(
			'labels'      => array(
				'name'          => 'Новости',
				'singular_name' => 'Новость',
			),
			'public'      => true,
			'show_in_rest'=> true,
			'menu_icon'   => 'dashicons-megaphone',
			'supports'    => array( 'title', 'editor', 'thumbnail', 'author' ),
			'rewrite'     => array( 'slug' => 'news', 'with_front' => false ),
			'has_archive' => 'news',
		)
	);
}
add_action( 'init', 'bc_register_cpt_news' );

function bc_register_cpt_events() {
	register_post_type(
		'events',
		array(
			'labels'      => array(
				'name'          => 'События',
				'singular_name' => 'Событие',
			),
			'public'      => true,
			'show_in_rest'=> true,
			'menu_icon'   => 'dashicons-calendar-alt',
			'supports'    => array( 'title', 'editor', 'thumbnail' ),
			'rewrite'     => array( 'slug' => 'afisha', 'with_front' => false ),
			'has_archive' => 'afisha',
		)
	);
}
add_action( 'init', 'bc_register_cpt_events' );

function bc_acf_news_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_local_field_group(
		array(
			'key'      => 'group_bc_news',
			'title'    => 'Параметры новости',
			'fields'   => array(
				array( 'key' => 'field_bc_news_type', 'label' => 'Тип', 'name' => 'news_type', 'type' => 'select', 'choices' => array( 'news' => 'Новость', 'promo' => 'Акция · реклама', 'job' => 'Вакансия' ) ),
				array( 'key' => 'field_bc_is_pinned', 'label' => 'Закрепить в ленте', 'name' => 'is_pinned', 'type' => 'true_false' ),
				array( 'key' => 'field_bc_erid', 'label' => 'erid (токен рекламы)', 'name' => 'erid', 'type' => 'text' ),
			),
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'news' ) ) ),
		)
	);

	acf_add_local_field_group(
		array(
			'key'      => 'group_bc_event',
			'title'    => 'Параметры события',
			'fields'   => array(
				array( 'key' => 'field_bc_event_date', 'label' => 'Дата', 'name' => 'event_date', 'type' => 'date_picker', 'display_format' => 'd.m.Y', 'return_format' => 'Y-m-d' ),
				array( 'key' => 'field_bc_event_time', 'label' => 'Время', 'name' => 'event_time', 'type' => 'time_picker', 'return_format' => 'H:i' ),
				array( 'key' => 'field_bc_event_place', 'label' => 'Место', 'name' => 'event_place', 'type' => 'text' ),
				array( 'key' => 'field_bc_event_price', 'label' => 'Цена', 'name' => 'event_price', 'type' => 'text' ),
				array( 'key' => 'field_bc_is_free', 'label' => 'Бесплатно', 'name' => 'is_free', 'type' => 'true_false' ),
			),
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'events' ) ) ),
		)
	);
}
add_action( 'acf/init', 'bc_acf_news_fields' );
