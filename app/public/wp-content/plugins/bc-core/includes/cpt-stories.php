<?php
/**
 * CPT «сторис» + ACF-поля (Этап 5.1, stories.md §64–66).
 *
 * Сторис: title, owner (post_author), слайды (repeater), показ (show_until), sort_order.
 * Слайд: image 1080×1920, caption ≤90, btn_text ≤20, link_url, is_ad, erid.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_register_cpt_stories() {
	register_post_type(
		'stories',
		array(
			'labels'       => array(
				'name'          => 'Сторис',
				'singular_name' => 'Сторис',
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-format-gallery',
			'supports'     => array( 'title', 'author' ),
			'show_in_rest' => false,
		)
	);
}
add_action( 'init', 'bc_register_cpt_stories' );

function bc_acf_stories_fields() {
	if ( ! function_exists( 'acf_add_local_field_group' ) ) {
		return;
	}
	acf_add_local_field_group(
		array(
			'key'      => 'group_bc_stories',
			'title'    => 'Слайды сторис',
			'fields'   => array(
				array(
					'key'          => 'field_bc_slides',
					'label'        => 'Слайды',
					'name'         => 'slides',
					'type'         => 'repeater',
					'layout'       => 'table',
					'button_label' => 'Добавить слайд',
					'max'          => 5,
					'sub_fields'   => array(
						array( 'key' => 'field_bc_slide_image', 'label' => 'Фото (1080×1920)', 'name' => 'image', 'type' => 'image', 'return_format' => 'id', 'preview_size' => 'thumbnail' ),
						array( 'key' => 'field_bc_slide_caption', 'label' => 'Подпись (≤90)', 'name' => 'caption', 'type' => 'text', 'maxlength' => 90 ),
						array( 'key' => 'field_bc_slide_btn', 'label' => 'Текст кнопки (≤20)', 'name' => 'btn_text', 'type' => 'text', 'maxlength' => 20 ),
						array( 'key' => 'field_bc_slide_link', 'label' => 'Ссылка', 'name' => 'link_url', 'type' => 'url' ),
						array( 'key' => 'field_bc_slide_ad', 'label' => 'Рекламный слайд', 'name' => 'is_ad', 'type' => 'true_false' ),
						array( 'key' => 'field_bc_slide_erid', 'label' => 'erid (обязателен для рекламы)', 'name' => 'erid', 'type' => 'text' ),
					),
				),
				array( 'key' => 'field_bc_show_until', 'label' => 'Показывать до (пусто — вечно)', 'name' => 'show_until', 'type' => 'date_picker', 'display_format' => 'd.m.Y', 'return_format' => 'Y-m-d' ),
				array( 'key' => 'field_bc_sort_order', 'label' => 'Порядок', 'name' => 'sort_order', 'type' => 'number', 'default_value' => 0 ),
			),
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'stories' ) ) ),
		)
	);
}
add_action( 'acf/init', 'bc_acf_stories_fields' );
