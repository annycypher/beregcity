<?php
/**
 * Сид терминов каталога (Этап 3.1c): 12 категорий + 34 снипета.
 * Идемпотентно: если термин уже есть — не дублируем.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * 12 категорий (bc_cat) — имена и slug'и по СПРАВОЧНИКУ §4.
 */
function bc_seed_categories() {
	$cats = array(
		'Еда'         => 'eda',
		'Медицина'    => 'medicina',
		'Учёба'       => 'ucheba',
		'Дети'        => 'deti',
		'Авто'        => 'avto',
		'Магазины'    => 'magaziny',
		'Декор'       => 'dekor',
		'Ремонт'      => 'remont',
		'Красота'     => 'krasota',
		'Услуги'      => 'uslugi',
		'Развлечения' => 'razvlecheniya',
		'Недвижимость' => 'nedvizhimost',
	);

	foreach ( $cats as $name => $slug ) {
		if ( ! term_exists( $slug, 'bc_cat' ) ) {
			wp_insert_term( $name, 'bc_cat', array( 'slug' => $slug ) );
		}
	}
}

/**
 * 34 снипета (features) + группа термина в term meta `bc_group`.
 * Имена — дословно из СПРАВОЧНИКА §3 (D29).
 */
function bc_seed_features() {
	$groups = array(
		'Оплата и заказ' => array( 'Оплата картой', 'Онлайн-оплата', 'Наличный расчёт', 'Рассрочка', 'Онлайн-заявка', 'Доставка', 'Самовывоз', 'Под заказ' ),
		'Режим работы'   => array( 'Круглосуточно', 'Без выходных', 'Выезд к клиенту', 'Онлайн-консультация' ),
		'Еда'            => array( 'Кофе с собой', 'Завтраки', 'Бизнес-ланч', 'Детское меню', 'Вегетарианское меню', 'Своя пекарня', 'Банкеты' ),
		'Комфорт'        => array( 'Wi-Fi', 'Парковка', 'Детская комната', 'Можно с животными', 'Летняя веранда', 'Доступная среда', 'Постамат на месте' ),
		'Доверие'        => array( 'Гарантия на работы', 'Своя продукция', 'Работаем с юрлицами', 'Опыт от 5 лет', 'Программа лояльности' ),
		'Медицина'       => array( 'Приём по ДМС', 'Диагностика на месте', 'Вызов на дом' ),
	);

	foreach ( $groups as $group => $terms ) {
		foreach ( $terms as $name ) {
			$slug = sanitize_title( $name );
			// Ищем по имени (кириллический slug хранится URL-encoded —
			// term_exists по slug его не находит и дёргает wp_insert_term,
			// который возвращает WP_Error «термин уже есть»).
			$term = term_exists( $name, 'features' );
			if ( ! $term ) {
				$term = wp_insert_term( $name, 'features', array( 'slug' => $slug ) );
			}
			if ( is_wp_error( $term ) ) {
				continue;
			}
			$term_id = is_array( $term ) ? (int) $term['term_id'] : (int) $term;
			if ( $term_id ) {
				update_term_meta( $term_id, 'bc_group', $group );
			}
		}
	}
}

/**
 * Полный сид каталога + сброс rewrite.
 */
function bc_seed_catalog() {
	// Активация происходит до 'init', где регистрируются таксономии —
	// вызываем регистрацию явно, чтобы wp_insert_term нашёл типы.
	if ( function_exists( 'bc_register_taxonomy_features' ) ) {
		bc_register_cpt_organizations();
		bc_register_taxonomy_bc_cat();
		bc_register_taxonomy_features();
	}
	bc_seed_categories();
	bc_seed_features();
	flush_rewrite_rules();
}
