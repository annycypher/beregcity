<?php
/**
 * Карта (Этап 6.3): Яндекс JS API под BC_MAP_ON (D11) + заглушка до переноса.
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

function bc_map_on() {
	return defined( 'BC_MAP_ON' ) && BC_MAP_ON;
}

/**
 * Карта по координатам: Яндекс (если BC_MAP_ON) или SVG-заглушка.
 */
function bc_map_markup( $lat = 0, $lng = 0, $address = '' ) {
	if ( bc_map_on() ) {
		$key = defined( 'BC_YMAP_KEY' ) ? BC_YMAP_KEY : '';
		$id  = 'ymap_' . wp_rand( 1000, 9999 );
		ob_start();
		?>
		<div id="<?php echo esc_attr( $id ); ?>" style="width:100%;height:100%;min-height:280px"></div>
		<script src="https://api-maps.yandex.ru/2.1/?apikey=<?php echo esc_attr( $key ); ?>&lang=ru_RU"></script>
		<script>
		ymaps.ready(function () {
			var m = new ymaps.Map('<?php echo esc_js( $id ); ?>', { center: [<?php echo (float) $lat; ?>, <?php echo (float) $lng; ?>], zoom: 16 });
			m.geoObjects.add(new ymaps.Placemark([<?php echo (float) $lat; ?>, <?php echo (float) $lng; ?>], { balloonContent: '<?php echo esc_js( $address ); ?>' }));
		});
		</script>
		<?php
		return ob_get_clean();
	}
	return '<div class="map-ph"><svg class="mark" viewBox="0 0 24 24" style="width:38px;height:38px"><path class="ico" style="width:38px;height:38px;stroke:currentColor;stroke-width:1.6;fill:none" d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle style="stroke:currentColor;stroke-width:1.6;fill:none" cx="12" cy="10" r="3"/></svg></div>';
}
