<?php
/**
 * Astra Child — функции дочерней темы проекта «БерегСити».
 *
 * Тонкий functions.php: собственная логика подключается из includes/ (Этап 2).
 * Правки ядра WordPress и родительской темы Astra запрещены (см. DECISIONS D1).
 *
 * @package Astra_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Прямой доступ запрещён.
}

require_once get_stylesheet_directory() . '/includes/security.php';
