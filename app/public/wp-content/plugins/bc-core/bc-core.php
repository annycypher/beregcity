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
require_once plugin_dir_path( __FILE__ ) . 'includes/plans.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/roles.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/register.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/lk-routes.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-bc-admin.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-bc-approval.php';
require_once plugin_dir_path( __FILE__ ) . 'admin/class-bc-payments.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/db.php';
require_once plugin_dir_path( __FILE__ ) . 'includes/invoice.php';

function bc_activate() {
	bc_seed_catalog();
	bc_create_payments_table();
}
register_activation_hook( __FILE__, 'bc_activate' );
