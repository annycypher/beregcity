<?php
/**
 * Бэкфилл улицы и дома из свободного адреса — `wp bc-house-fill` (ТЗ §2.2-B).
 *
 * @package bc-core
 */

if ( ! defined( 'ABSPATH' ) ) {
exit;
}

/**
 * Текст адреса карточки (ключи те же, что перебирает BC_Houses::parse()).
 *
 * @param int $post_id Карточка организации.
 * @return string
 */
function bc_house_fill_address( $post_id ) {
foreach ( array( 'address', 'адрес', 'bc_address', 'org_address' ) as $key ) {
$v = function_exists( 'get_field' ) ? get_field( $key, $post_id ) : '';
if ( ! $v ) {
$v = get_post_meta( $post_id, $key, true );
}
if ( $v && is_string( $v ) ) {
return $v;
}
}
return '';
}

/**
 * Заполняет bc_street / bc_house у организаций, где они пусты.
 *
 * Дом пишется только если он есть в реестре BC_Houses::houses_of_street();
 * иначе карточка попадает в отчёт «нераспознанные» и мета не меняется.
 *
 * @param bool $dry_run Только отчёт, без записи в БД.
 * @return array {total, already, filled, filled_ids, unrecognized[], dry_run}
 */
function bc_house_fill_run( $dry_run = false ) {
$report = array(
'total'        => 0,
'already'      => 0,
'filled'       => 0,
'filled_ids'   => array(),
'unrecognized' => array(),
'dry_run'      => (bool) $dry_run,
);

if ( ! class_exists( 'BC_Houses' ) ) {
return $report;
}

$ids = get_posts(
array(
'post_type'              => BC_Houses::CPT,
'post_status'            => 'any',
'posts_per_page'         => -1,
'fields'                 => 'ids',
'no_found_rows'          => true,
'update_post_term_cache' => false,
'update_post_meta_cache' => false,
'orderby'                => 'ID',
'order'                  => 'ASC',
)
);

foreach ( $ids as $id ) {
$id = (int) $id;
$report['total']++;

$street = (string) get_post_meta( $id, BC_Houses::META_STREET, true );
$house  = (string) get_post_meta( $id, BC_Houses::META_HOUSE, true );

if ( '' !== $street && '' !== $house ) {
$report['already']++;
continue;
}

$address        = bc_house_fill_address( $id );
list( $st, $h ) = BC_Houses::parse( $id );

$reason = '';
if ( '' === (string) $st ) {
$reason = '' === $address ? 'адрес не заполнен' : 'улица не из реестра: ' . $address;
} elseif ( '' === (string) $h ) {
$reason = 'нет номера дома: ' . $address;
} elseif ( ! in_array( (string) $h, BC_Houses::houses_of_street( $st ), true ) ) {
$reason = sprintf( 'дом %s вне реестра (%s): %s', $h, $st, $address );
}

if ( '' !== $reason ) {
$report['unrecognized'][] = array(
'id'      => $id,
'title'   => get_the_title( $id ),
'address' => $address,
'reason'  => $reason,
);
continue;
}

if ( ! $dry_run ) {
if ( '' === $street ) {
update_post_meta( $id, BC_Houses::META_STREET, $st );
}
update_post_meta( $id, BC_Houses::META_HOUSE, (string) $h );
}

$report['filled']++;
$report['filled_ids'][] = $id;
}

return $report;
}

/**
 * `wp bc-house-fill [--dry-run]`
 *
 * @param array $args       Позиционные аргументы.
 * @param array $assoc_args Флаги.
 */
function bc_house_fill_cli( $args, $assoc_args ) {
$dry = isset( $assoc_args['dry-run'] );
$r   = bc_house_fill_run( $dry );

if ( ! $r['total'] ) {
WP_CLI::warning( 'Карточек organizations не найдено.' );
return;
}

WP_CLI::log( sprintf( 'Всего карточек: %d', $r['total'] ) );
WP_CLI::log( sprintf( 'Уже с улицей и домом: %d', $r['already'] ) );
WP_CLI::log( sprintf( '%s: %d', $dry ? 'Будет заполнено (dry-run)' : 'Заполнено', $r['filled'] ) );
WP_CLI::log( sprintf( 'Нераспознанных адресов: %d', count( $r['unrecognized'] ) ) );

if ( $r['unrecognized'] ) {
$rows = array();
foreach ( $r['unrecognized'] as $u ) {
$rows[] = array(
'ID'       => $u['id'],
'Название' => $u['title'],
'Адрес'    => $u['address'],
'Причина'  => $u['reason'],
);
}
WP_CLI::log( '' );
WP_CLI\Utils\format_items( 'table', $rows, array( 'ID', 'Название', 'Адрес', 'Причина' ) );
}

WP_CLI::success( $dry ? 'Dry-run завершён (мета не изменялась).' : 'Бэкфилл завершён: bc_street / bc_house заполнены.' );
}

if ( defined( 'WP_CLI' ) && WP_CLI ) {
WP_CLI::add_command( 'bc-house-fill', 'bc_house_fill_cli' );
}