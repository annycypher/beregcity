<?php
/**
 * Меню проекта «БерегСити» (Этап 2.5-а).
 *
 * Walker печатает пункты как голые <a> — без <ul>/<li> (классы шапки = контракт).
 * Плюс fallback-разметка, если меню ещё не назначено в админке.
 *
 * @package Astra_Child
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Walker: пункт меню = один <a>.
 */
class BC_Nav_Walker extends Walker_Nav_Menu {

	public function start_lvl( &$output, $depth = 0, $args = null ) {}
	public function end_lvl( &$output, $depth = 0, $args = null ) {}

	public function start_el( &$output, $item, $depth = 0, $args = null, $id = 0 ) {
		$atts = array(
			'href'  => ! empty( $item->url ) ? $item->url : '',
			'title' => ! empty( $item->attr_title ) ? $item->attr_title : '',
		);
		$atts = apply_filters( 'nav_menu_link_attributes', $atts, $item, $args, $depth );

		$attributes = '';
		foreach ( $atts as $attr => $value ) {
			if ( ! empty( $value ) ) {
				$value       = ( 'href' === $attr ) ? esc_url( $value ) : esc_attr( $value );
				$attributes .= ' ' . $attr . '="' . $value . '"';
			}
		}

		$title   = apply_filters( 'the_title', $item->title, $item->ID );
		$output .= '<a' . $attributes . '>' . esc_html( $title ) . '</a>';
	}

	public function end_el( &$output, $item, $depth = 0, $args = null ) {}
}

/**
 * Fallback меню шапки (`bc_primary`), если область пуста.
 */
function bc_nav_fallback_primary() {
	$links = array(
		'/katalog' => 'Каталог',
		'/news'    => 'Новости',
		'/afisha'  => 'Афиша',
		'/karta'   => 'Карта',
		'/reklama' => 'Бизнесу',
		'/kabinet' => 'Личный кабинет',
	);
	foreach ( $links as $url => $label ) {
		echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}

/**
 * Fallback меню бургера (`bc_mobile`), если область пуста.
 */
function bc_nav_fallback_mobile() {
	$links = array(
		'/katalog' => 'Каталог организаций',
		'/news'    => 'Новости',
		'/afisha'  => 'Афиша',
		'/karta'   => 'Карта района',
		'/reklama' => 'Бизнесу',
		'/kabinet' => 'Личный кабинет',
		'/dobavit' => 'Добавить организацию',
	);
	foreach ( $links as $url => $label ) {
		echo '<a href="' . esc_url( $url ) . '">' . esc_html( $label ) . '</a>';
	}
}
