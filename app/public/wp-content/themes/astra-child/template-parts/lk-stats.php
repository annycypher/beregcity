<?php
/**
 * Вкладка «Статистика» ЛК (Этап 3а.6): счётчики + bars14 (CSS, без библиотек).
 *
 * @package BC
 */

$user   = wp_get_current_user();
$orgs   = get_posts( array( 'post_type' => 'organizations', 'author' => $user->ID, 'post_status' => 'any', 'numberposts' => 1 ) );
$org_id = $orgs ? (int) $orgs[0]->ID : 0;
if ( ! $org_id || ! function_exists( 'bc_stats' ) ) {
	return;
}
$t7   = bc_stats_totals( $org_id, 7 );
$t30  = bc_stats_totals( $org_id, 30 );
$rows = bc_stats( $org_id, 14 );
$max  = 1;
foreach ( $rows as $r ) {
	if ( $r['views'] > $max ) {
		$max = $r['views'];
	}
}
?>
<div class="stat-row">
	<div class="stat"><div class="num"><?php echo (int) $t7['views']; ?></div><div class="lbl">просмотров · 7 дней</div></div>
	<div class="stat"><div class="num"><?php echo (int) $t7['clicks']; ?></div><div class="lbl">кликов · 7 дней</div></div>
	<div class="stat"><div class="num"><?php echo (int) $t30['views']; ?></div><div class="lbl">просмотров · 30 дней</div></div>
</div>
<div class="bars14">
	<?php foreach ( $rows as $r ) : $h = (int) round( $r['views'] / $max * 100 ); ?>
	<i style="height:<?php echo $h; ?>%"></i>
	<?php endforeach; ?>
</div>
<div class="hint" style="margin-top:10px">Просмотры карточки по дням · последние 14 дней.</div>
