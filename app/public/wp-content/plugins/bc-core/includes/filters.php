<?php
/**
 * Фильтры каталога по снипетам + сортировка (Этап 3.3b).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Живые счётчики: сколько организаций категории имеют каждый снипет.
 * Один SQL-запрос (не 34).
 *
 * @param int $cat_id ID категории bc_cat.
 * @return int[] term_id => count.
 */
function bc_feature_counts( $cat_id ) {
	global $wpdb;
	$rows = $wpdb->get_results(
		$wpdb->prepare(
			"SELECT tt2.term_id AS tid, COUNT(DISTINCT p.ID) AS cnt
			 FROM {$wpdb->posts} p
			 JOIN {$wpdb->term_relationships} tr1 ON p.ID = tr1.object_id
			 JOIN {$wpdb->term_taxonomy} tt1 ON tr1.term_taxonomy_id = tt1.term_taxonomy_id
			   AND tt1.taxonomy = 'bc_cat' AND tt1.term_id = %d
			 JOIN {$wpdb->term_relationships} tr2 ON p.ID = tr2.object_id
			 JOIN {$wpdb->term_taxonomy} tt2 ON tr2.term_taxonomy_id = tt2.term_taxonomy_id
			   AND tt2.taxonomy = 'features'
			 WHERE p.post_type = 'organizations' AND p.post_status = 'publish'
			 GROUP BY tt2.term_id",
			$cat_id
		)
	);
	$out = array();
	foreach ( (array) $rows as $r ) {
		$out[ (int) $r->tid ] = (int) $r->cnt;
	}
	return $out;
}

/**
 * Панель фильтров (разметка .filters из design/catalog.html).
 *
 * @param int $cat_id ID категории (0 — без категории).
 * @return string HTML.
 */
function bc_filters( $cat_id = 0 ) {
	$terms = get_terms( array( 'taxonomy' => 'features', 'hide_empty' => false ) );
	if ( ! $terms || is_wp_error( $terms ) ) {
		return '';
	}

	$counts = $cat_id ? bc_feature_counts( $cat_id ) : array();

	// Группируем по term meta bc_group.
	$groups = array();
	foreach ( $terms as $t ) {
		$g = (string) get_term_meta( $t->term_id, 'bc_group', true );
		$g = $g ? $g : 'Прочее';
		$groups[ $g ][] = $t;
	}

	$order  = array( 'Оплата и заказ', 'Режим работы', 'Еда', 'Комфорт', 'Доверие', 'Медицина' );
	$active = isset( $_GET['feat'] ) ? array_map( 'absint', (array) $_GET['feat'] ) : array(); // phpcs:ignore

	ob_start();
	?>
	<div class="filters" id="fl">
		<?php foreach ( $order as $gname ) : if ( empty( $groups[ $gname ] ) ) { continue; } ?>
		<div class="f-group"><b><?php echo esc_html( $gname ); ?></b>
			<?php foreach ( $groups[ $gname ] as $t ) : $c = isset( $counts[ $t->term_id ] ) ? $counts[ $t->term_id ] : 0; ?>
			<label class="f-check"><input type="checkbox" name="feat[]" value="<?php echo esc_attr( $t->term_id ); ?>"<?php checked( in_array( (int) $t->term_id, $active, true ) ); ?>> <?php echo esc_html( $t->name ); ?> <span class="n"><?php echo (int) $c; ?></span></label>
			<?php endforeach; ?>
		</div>
		<?php endforeach; ?>
		<div class="f-btns">
			<button class="btn btn-terra btn-sm" type="submit">Применить</button>
			<a class="btn btn-glass btn-sm" href="<?php echo esc_url( $cat_id ? get_term_link( $cat_id, 'bc_cat' ) : home_url( '/katalog/' ) ); ?>">Сбросить</a>
		</div>
	</div>
	<?php
	return ob_get_clean();
}

/**
 * Аргументы сортировки по параметру sort.
 * premium/rating — заглушки (реальная логика на 3.4/4).
 *
 * @param string $sort Ключ сортировки.
 * @return array WP_Query order args.
 */
function bc_sort_args( $sort ) {
	switch ( $sort ) {
		case 'name':
			return array( 'orderby' => 'title', 'order' => 'ASC' );
		case 'new':
			return array( 'orderby' => 'date', 'order' => 'DESC' );
		case 'rating':
			// ФАКТ-ПРОВЕРКА: рейтинг из отзывов — Этап 4.
		case 'premium':
		default:
			// ФАКТ-ПРОВЕРКА: premium↑ по тарифу — Этап 3.4.
			return array( 'orderby' => 'date', 'order' => 'DESC' );
	}
}
