<?php
/**
 * БерегСити — «Организации по домам» (bc-core/admin/class-bc-houses.php)
 *
 * Что делает:
 *  1) Подменю «Организации по домам» в меню «БерегСити»: все организации,
 *     сгруппированные по улицам и домам микрорайона (для фото-обхода и правок).
 *  2) Колонка «Дом» + фильтр по дому в стандартном списке Organizations.
 *  3) CSV-выгрузка «по домам» одним кликом.
 *
 * Группировка построена на разборе ACF-поля адреса. Если у карточки заполнено
 * отдельное поле bc_house (см. ТЗ ниже) — используется оно.
 *
 * ПОДКЛЮЧЕНИЕ:
 *   1. Скопируйте файл в wp-content/plugins/bc-core/admin/class-bc-houses.php
 *   2. В главном файле плагина (bc-core.php) рядом с другими require:
 *        require_once BC_CORE_DIR . 'admin/class-bc-houses.php';
 *   3. Проверьте константы ниже (parent slug меню, slug CPT, ключи ACF).
 *
 * Совместимо: WP 6.x, PHP 8.2+, ACF Free (local fields).
 * Роль org_manager экран по умолчанию не видит (manage_options) —
 * при необходимости замените константу CAP.
 */

if ( ! defined( 'ABSPATH' ) ) exit;

class BC_Houses {

	const PARENT_SLUG = 'beregcity';        // slug главного меню «БерегСити» (см. add_menu_page в class-bc-admin)
	const CPT         = 'organizations';  // slug CPT организаций
	const CAP         = 'manage_options'; // право доступа к экрану
	const META_STREET = 'bc_street';      // ACF-поле «Улица» (после внедрения ТЗ)
	const META_HOUSE  = 'bc_house';       // ACF-поле «Дом» (после внедрения ТЗ)

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 30 );
		add_filter( 'manage_' . self::CPT . '_posts_columns', array( __CLASS__, 'col_head' ) );
		add_action( 'manage_' . self::CPT . '_posts_custom_column', array( __CLASS__, 'col_body' ), 10, 2 );
		add_filter( 'manage_edit-' . self::CPT . '_sortable_columns', array( __CLASS__, 'col_sort' ) );
		add_action( 'pre_get_posts', array( __CLASS__, 'sort_by_house' ) );
		add_action( 'restrict_manage_posts', array( __CLASS__, 'house_filter' ) );
		add_filter( 'pre_get_posts', array( __CLASS__, 'house_filter_apply' ) );
		add_action( 'admin_post_bc_houses_csv', array( __CLASS__, 'csv_export' ) );
	}

	/* ---------- Реестр улиц и домов района ---------- */
	public static function streets() {
		return array( 'Капитанская', 'Навигационная', 'Парусная', 'Регатная', 'Южная набережная' );
	}

	public static function houses_of_street( $street ) {
		$map = array(
			'Капитанская'      => array( '6', '8', '10', '12', '14', '16' ),
			'Навигационная'    => array( '4', '5', '7' ),
			'Парусная'         => array( '3', '5', '7', '8', '9', '10', '12' ),
			'Регатная'         => array( '1', '2', '3', '4' ),
			'Южная набережная' => array( '6', '10', '12', '14' ),
		);
		return isset( $map[ $street ] ) ? $map[ $street ] : array();
	}

	/* ---------- Разбор адреса ---------- */
	public static function parse( $post_id ) {
		$street = get_post_meta( $post_id, self::META_STREET, true );
		$house  = get_post_meta( $post_id, self::META_HOUSE, true );
		if ( $street && $house ) {
			return array( $street, (string) $house );
		}
		$addr = '';
		foreach ( array( 'address', 'адрес', 'bc_address', 'org_address' ) as $key ) {
			$v = function_exists( 'get_field' ) ? get_field( $key, $post_id ) : '';
			if ( ! $v ) $v = get_post_meta( $post_id, $key, true );
			if ( $v ) { $addr = $v; break; }
		}
		if ( ! $addr ) return array( '', '' );
		foreach ( self::streets() as $st ) {
			$stem = mb_substr( $st, 0, 7 );
			if ( false !== mb_stripos( $addr, $stem ) ) {
				if ( preg_match( '/(\d{1,3}[А-Яа-я]?)\s*($|,|к)/u', $addr, $m ) ) {
					return array( $st, $m[1] );
				}
				return array( $st, '' );
			}
		}
		return array( '', '' );
	}

	/* ---------- Экран ---------- */
	public static function menu() {
		add_submenu_page(
			self::PARENT_SLUG,
			'Организации по домам', 'Организации по домам',
			self::CAP, 'bc-houses', array( __CLASS__, 'render' )
		);
	}

	private static function all_orgs() {
		$q = new WP_Query( array(
			'post_type' => self::CPT, 'post_status' => array( 'publish', 'pending', 'draft' ),
			'posts_per_page' => -1, 'orderby' => 'title', 'order' => 'ASC',
			'no_found_rows' => true, 'update_post_term_cache' => false,
		) );
		$out = array();
		foreach ( $q->posts as $p ) {
			list( $st, $h ) = self::parse( $p->ID );
			$thumb_id = get_post_thumbnail_id( $p->ID );
			$gallery  = function_exists( 'get_field' ) ? get_field( 'bc_gallery', $p->ID ) : '';
			$out[] = array(
				'id' => $p->ID, 'title' => $p->post_title, 'status' => $p->post_status,
				'street' => $st, 'house' => $h,
				'edit' => get_edit_post_link( $p->ID, 'raw' ),
				'view' => get_permalink( $p->ID ),
				'phone' => function_exists( 'get_field' ) ? get_field( 'bc_phone', $p->ID ) : get_post_meta( $p->ID, 'bc_phone', true ),
				'photo' => ( $thumb_id || ! empty( $gallery ) ) ? 'да' : 'нет',
			);
		}
		return $out;
	}

	public static function render() {
		if ( ! current_user_can( self::CAP ) ) wp_die( 'Недостаточно прав' );
		$orgs = self::all_orgs();
		$unknown = array();

		echo '<div class="wrap"><h1>Организации по домам</h1>';
		echo '<p class="description">Группировка по адресу карточки. Пока отдельные поля «Улица/Дом» не заполнены ' .
			'(см. ТЗ), дом распознаётся из текста адреса. ';

		$csv_url = wp_nonce_url( admin_url( 'admin-post.php?action=bc_houses_csv' ), 'bc_houses_csv' );
		echo '<a class="button button-secondary" href="' . esc_url( $csv_url ) . '">Скачать CSV «по домам»</a></p>';

		echo '<style>
			.bc-houses .bc-house-row{background:#14231f;color:#fff;font-weight:600;padding:6px 10px}
			.bc-houses table{border-collapse:collapse;width:100%;margin-bottom:18px}
			.bc-houses td{padding:5px 8px;border-bottom:1px solid #f0ece4}
			.bc-street{margin:26px 0 8px;font-size:15px}
		</style><div class="bc-houses">';

		foreach ( self::streets() as $street ) {
			echo '<h2 class="bc-street">' . esc_html( $street ) . '</h2><table>';
			foreach ( self::houses_of_street( $street ) as $h ) {
				$list = array_values( array_filter( $orgs, function ( $o ) use ( $street, $h ) {
					return $o['street'] === $street && $o['house'] === $h;
				} ) );
				echo '<tr><td colspan="4" class="bc-house-row">дом ' . esc_html( $h ) . ' — ' .
					count( $list ) . ' организаций</td></tr>';
				foreach ( $list as $o ) {
					$badge = 'publish' === $o['status'] ? '' : ' <em>[' . esc_html( $o['status'] ) . ']</em>';
					echo '<tr><td><a href="' . esc_url( $o['edit'] ) . '">' . esc_html( $o['title'] ) . '</a>' . $badge . '</td>' .
						'<td>' . esc_html( $o['phone'] ) . '</td>' .
						'<td>' . ( 'да' === $o['photo'] ? 'фото есть' : 'нет фото' ) . '</td>' .
						'<td><a href="' . esc_url( $o['view'] ) . '">просмотр</a></td></tr>';
				}
			}
			echo '</table>';
		}

		foreach ( $orgs as $o ) {
			if ( ! $o['street'] ) $unknown[] = $o;
		}
		if ( $unknown ) {
			echo '<h2 class="bc-street">Адрес вне реестра или не распознан (' . count( $unknown ) . ')</h2><table>';
			foreach ( $unknown as $o ) {
				echo '<tr><td><a href="' . esc_url( $o['edit'] ) . '">' . esc_html( $o['title'] ) . '</a></td>' .
					'<td>' . esc_html( $o['status'] ) . '</td></tr>';
			}
			echo '</table>';
		}
		echo '</div></div>';
	}

	/* ---------- Колонка «Дом» + фильтр + сортировка в общем списке ---------- */
	public static function col_head( $cols ) {
		$cols['bc_house_col'] = 'Дом';
		return $cols;
	}
	public static function col_body( $col, $post_id ) {
		if ( 'bc_house_col' !== $col ) return;
		list( $st, $h ) = self::parse( $post_id );
		echo esc_html( trim( $st . ', ' . $h, ', ' ) );
	}
	public static function col_sort( $cols ) {
		$cols['bc_house_col'] = 'bc_house';
		return $cols;
	}
	public static function sort_by_house( $q ) {
		if ( is_admin() && $q->get( 'orderby' ) === 'bc_house' ) {
			$q->set( 'meta_key', self::META_HOUSE );
			$q->set( 'orderby', 'meta_value_num' );
		}
	}

	public static function house_filter() {
		global $typenow;
		if ( self::CPT !== $typenow || ! current_user_can( self::CAP ) ) return;
		$cur = isset( $_GET['bc_house'] ) ? sanitize_text_field( wp_unslash( $_GET['bc_house'] ) ) : '';
		echo '<select name="bc_house"><option value="">Все дома</option>';
		foreach ( self::streets() as $st ) {
			foreach ( self::houses_of_street( $st ) as $h ) {
				$v = $st . '|' . $h;
				printf( '<option value="%s"%s>%s, %s</option>', esc_attr( $v ), selected( $cur, $v, false ), esc_html( $st ), esc_html( $h ) );
			}
		}
		echo '</select>';
	}
	public static function house_filter_apply( $q ) {
		if ( ! is_admin() || ! $q->is_main_query() ) return;
		if ( empty( $_GET['bc_house'] ) ) return;
		$pt = $q->get( 'post_type' );
		if ( self::CPT !== $pt && ( ! is_array( $pt ) || ! in_array( self::CPT, $pt, true ) ) ) return;
		$pair = explode( '|', sanitize_text_field( wp_unslash( $_GET['bc_house'] ) ) );
		if ( 2 !== count( $pair ) ) return;
		$q->set( 'meta_query', array(
			array( 'key' => self::META_STREET, 'value' => $pair[0] ),
			array( 'key' => self::META_HOUSE, 'value' => $pair[1] ),
		) );
	}

	/* ---------- CSV-выгрузка ---------- */
	public static function csv_export() {
		if ( ! current_user_can( self::CAP ) ) wp_die( 'Недостаточно прав' );
		check_admin_referer( 'bc_houses_csv' );
		$rows = array( array( 'Улица', 'Дом', 'Название', 'Статус', 'Телефон', 'Фото', 'Ссылка' ) );
		foreach ( self::all_orgs() as $o ) {
			$rows[] = array( $o['street'], $o['house'], $o['title'], $o['status'], $o['phone'], $o['photo'], $o['view'] );
		}
		nocache_headers();
		header( 'Content-Type: text/csv; charset=utf-8' );
		header( 'Content-Disposition: attachment; filename=bc-houses-' . gmdate( 'Y-m-d' ) . '.csv' );
		$f = fopen( 'php://output', 'w' );
		fwrite( $f, "\xEF\xBB\xBF" );
		foreach ( $rows as $r ) fputcsv( $f, $r, ';' );
		exit;
	}
}

BC_Houses::init();
