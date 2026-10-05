<?php
/**
 * Plugin Name: BC Core
 * Description: Ядро проекта «БерегСити»: шорткоды главной и каталога (мост с Elementor).
 * Version:     1.0.0
 * Author:      БерегСити
 * Text Domain: beregcity
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Прямой доступ запрещён.
}

require_once plugin_dir_path( __FILE__ ) . 'includes/shortcodes.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/taxonomies.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/fields.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/seed.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/schedule.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/rewrite.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/filters.php';

register_activation_hook( __FILE__, 'bc_seed_catalog' );
