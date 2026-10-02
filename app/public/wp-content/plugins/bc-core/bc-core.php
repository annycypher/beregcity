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
