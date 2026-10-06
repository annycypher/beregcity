<?php
/**
 * CPT «баннеры» + ACF (Этап 6.1, карта данных ПРОТОКОЛа стр. 117).
 *
 * Баннер: зона (wide/duo/sidebar/native), image, link_url, период (until), is_ad, erid.
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
			),
			'public'       => false,
			'show_ui'      => true,
			'menu_icon'    => 'dashicons-images-alt2',
			'supports'     => array( 'title' ),
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
				array( 'key' => 'field_bc_banner_zone', 'label' => 'Зона', 'name' => 'banner_zone', 'type' => 'select', 'choices' => array( 'wide' => 'Широкая (header)', 'duo' => 'Дуо-слот', 'sidebar' => 'Сайдбар', 'native' => 'Нативная (в ленте)' ) ),
				array( 'key' => 'field_bc_banner_image', 'label' => 'Изображение', 'name' => 'banner_image', 'type' => 'image', 'return_format' => 'id' ),
				array( 'key' => 'field_bc_banner_link', 'label' => 'Ссылка', 'name' => 'banner_link', 'type' => 'url' ),
				array( 'key' => 'field_bc_banner_until', 'label' => 'Показывать до (пусто — вечно)', 'name' => 'banner_until', 'type' => 'date_picker', 'display_format' => 'd.m.Y', 'return_format' => 'Y-m-d' ),
				array( 'key' => 'field_bc_banner_ad', 'label' => 'Рекламный', 'name' => 'banner_ad', 'type' => 'true_false' ),
				array( 'key' => 'field_bc_banner_erid', 'label' => 'erid (обязателен для рекламы)', 'name' => 'banner_erid', 'type' => 'text' ),
			),
			'location' => array( array( array( 'param' => 'post_type', 'operator' => '==', 'value' => 'banners' ) ) ),
		)
	);
}
add_action( 'acf/init', 'bc_acf_banners_fields' );
