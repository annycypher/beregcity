<?php
/**
 * CPT «организации» + таксономии каталога (Этап 3.1a).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CPT «organizations» (карточка организации).
 */
function bc_register_cpt_organizations() {
	register_post_type(
		'organizations',
		array(
			'labels'       => array(
				'name'          => 'Организации',
				'singular_name' => 'Организация',
				'add_new'       => 'Добавить',
				'add_new_item'  => 'Добавить организацию',
				'edit_item'     => 'Редактировать организацию',
				'new_item'      => 'Новая организация',
				'search_items'  => 'Искать организации',
				'not_found'     => 'Организаций не найдено',
			),
			'public'       => true,
			'show_in_rest' => true,
			'menu_icon'    => 'dashicons-store',
			'supports'     => array( 'title', 'editor', 'thumbnail', 'author' ),
			'rewrite'      => array( 'slug' => 'katalog/organization', 'with_front' => false ),
			'has_archive'  => 'katalog',
		)
	);
}
add_action( 'init', 'bc_register_cpt_organizations' );

/**
 * Таксономия «Категории» (bc_cat) — иерархическая, 12 категорий (СПРАВОЧНИК §4).
 */
function bc_register_taxonomy_bc_cat() {
	register_taxonomy(
		'bc_cat',
		'organizations',
		array(
			'labels'            => array(
				'name'          => 'Категории',
				'singular_name' => 'Категория',
			),
			'hierarchical'      => true,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'katalog', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'bc_register_taxonomy_bc_cat' );

/**
 * Таксономия «Снипеты» (features) — плоская; группа термина — term meta `bc_group` (3.1c).
 */
function bc_register_taxonomy_features() {
	register_taxonomy(
		'features',
		'organizations',
		array(
			'labels'            => array(
				'name'          => 'Снипеты',
				'singular_name' => 'Снипет',
			),
			'hierarchical'      => false,
			'show_in_rest'      => true,
			'show_admin_column' => true,
			'rewrite'           => array( 'slug' => 'katalog/feature', 'with_front' => false ),
		)
	);
}
add_action( 'init', 'bc_register_taxonomy_features' );
