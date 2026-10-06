<?php
/**
 * QR-генератор (чистый PHP, без библиотек и внешних API) — Этап 3а.7.
 * Режим byte, уровень коррекции L, версии 1–10 (хватает под URL карточки).
 *
 * @package BC_Core
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/* ── Таблицы ёмкости (version => [data codewords, ec codewords, total]) для EC L ── */
function bc_qr_capacity( $ver ) {
	$caps = array(
		1 => array( 19, 7 ),  2 => array( 34, 10 ), 3 => array( 55, 15 ),
		4 => array( 80, 20 ), 5 => array( 108, 26 ), 6 => array( 136, 18 ),
		7 => array( 156, 20 ), 8 => array( 194, 24 ), 9 => array( 232, 30 ),
		10 => array( 274, 18 ),
	);
	return isset( $caps[ $ver ] ) ? $caps[ $ver ] : array( 19, 7 );
}

function bc_qr_size( $ver ) {
	return 17 + 4 * $ver;
}

/* ── GF(256) и Reed-Solomon ── */
function bc_qr_gf() {
	static $gf = null;
	if ( null !== $gf ) {
		return $gf;
	}
	$exp = array_fill( 0, 512, 0 );
	$log = array_fill( 0, 256, 0 );
	$x   = 1;
	for ( $i = 0; $i < 255; $i++ ) {
		$exp[ $i ] = $x;
		$log[ $x ] = $i;
		$x         = ( $x << 1 ) ^ ( ( $x & 0x80 ) ? 0x11D : 0 );
	}
	for ( $i = 255; $i < 512; $i++ ) {
		$exp[ $i ] = $exp[ $i - 255 ];
	}
	$gf = array( 'exp' => $exp, 'log' => $log );
	return $gf;
}

function bc_qr_gmul( $a, $b ) {
	if ( 0 === $a || 0 === $b ) {
		return 0;
	}
	$gf  = bc_qr_gf();
	$sum = $gf['log'][ $a ] + $gf['log'][ $b ];
	return $gf['exp'][ $sum % 255 ];
}

function bc_qr_rs_poly( $n ) {
	$p = array( 1 );
	for ( $i = 0; $i < $n; $i++ ) {
		$p[] = 0;
		for ( $j = count( $p ) - 1; $j > 0; $j-- ) {
			$p[ $j ] = $p[ $j - 1 ] ^ bc_qr_gmul( $p[ $j ], $i );
		}
		$p[0] = bc_qr_gmul( $p[0], $i );
	}
	return $p;
}

function bc_qr_rs_remainder( $data, $poly ) {
	$rem = array_fill( 0, count( $poly ), 0 );
	foreach ( $data as $b ) {
		$factor = $b ^ array_shift( $rem );
		$rem[]  = 0;
		$p      = $poly;
		$gf     = bc_qr_gf();
		foreach ( $p as $i => $coef ) {
			if ( 0 === $factor ) {
				break;
			}
			$rem[ $i ] = $rem[ $i ] ^ bc_qr_gmul( $coef, $factor );
		}
	}
	return $rem;
}

/* ── Кодирование данных (byte mode) ── */
function bc_qr_data_codewords( $text, $ver ) {
	$bytes = array_values( unpack( 'C*', $text ) );
	$n     = count( $bytes );
	$bits  = '0100'; // byte mode
	$bits .= str_pad( decbin( $n ), 8, '0', STR_PAD_LEFT );
	foreach ( $bytes as $b ) {
		$bits .= str_pad( decbin( $b ), 8, '0', STR_PAD_LEFT );
	}
	$cap = bc_qr_capacity( $ver );
	$data_cap = $cap[0] * 8;
	if ( strlen( $bits ) > $data_cap ) {
		$bits = substr( $bits, 0, $data_cap );
	}
	$bits .= '0000';
	while ( strlen( $bits ) % 8 !== 0 ) {
		$bits .= '0';
	}
	$pad = array( 0xEC, 0x11 );
	$i   = 0;
	while ( strlen( $bits ) < $data_cap ) {
		$bits .= str_pad( decbin( $pad[ $i % 2 ] ), 8, '0', STR_PAD_LEFT );
		$i++;
	}
	$cw = array();
	for ( $k = 0; $k < strlen( $bits ); $k += 8 ) {
		$cw[] = bindec( substr( $bits, $k, 8 ) );
	}
	return $cw;
}
function bc_qr_choose_version( $text ) {
	$n = strlen( $text );
	for ( $v = 1; $v <= 10; $v++ ) {
		$cap = bc_qr_capacity( $v );
		if ( $n <= $cap[0] - 3 ) {
			return $v;
		}
	}
	return 10;
}

function bc_qr_build( $text ) {
	$ver  = bc_qr_choose_version( $text );
	$size = bc_qr_size( $ver );
	$m    = array_fill( 0, $size, array_fill( 0, $size, 0 ) );
	$res  = array_fill( 0, $size, array_fill( 0, $size, false ) );

	// Finder patterns + separators (резервируем зоны).
	foreach ( array( array( 0, 0 ), array( 0, $size - 7 ), array( $size - 7, 0 ) ) as $fc ) {
		$fr = $fc[0];
		$fcc = $fc[1];
		for ( $i = -1; $i <= 7; $i++ ) {
			for ( $j = -1; $j <= 7; $j++ ) {
				$rr = $fr + $i;
				$cc = $fcc + $j;
				if ( $rr < 0 || $cc < 0 || $rr >= $size || $cc >= $size ) {
					continue;
				}
				$res[ $rr ][ $cc ] = true;
				$in = ( $i >= 0 && $i <= 6 && $j >= 0 && $j <= 6 );
				$dark = $in && ( 0 === $i || 6 === $i || 0 === $j || 6 === $j || ( $i >= 2 && $i <= 4 && $j >= 2 && $j <= 4 ) );
				$m[ $rr ][ $cc ] = $dark ? 1 : 0;
			}
		}
	}

	// Timing patterns.
	for ( $i = 8; $i < $size - 8; $i++ ) {
		$m[6][ $i ] = ( $i % 2 === 0 ) ? 1 : 0; $res[6][ $i ] = true;
		$m[ $i ][6] = ( $i % 2 === 0 ) ? 1 : 0; $res[ $i ][6] = true;
	}

	// Alignment patterns (версии >= 2).
	if ( $ver >= 2 ) {
		$pos = array(
			2 => array( 18 ), 3 => array( 22 ), 4 => array( 26 ), 5 => array( 30 ),
			6 => array( 34 ), 7 => array( 6, 22, 38 ), 8 => array( 6, 24, 42 ),
			9 => array( 6, 26, 46 ), 10 => array( 6, 28, 50 ),
		);
		foreach ( $pos[ $ver ] as $ar ) {
			foreach ( $pos[ $ver ] as $ac ) {
				$near = ( ( 6 === $ar && 6 === $ac ) || ( 6 === $ar && $size - 7 === $ac ) || ( $size - 7 === $ar && 6 === $ac ) );
				if ( $near ) {
					continue;
				}
				for ( $i = -2; $i <= 2; $i++ ) {
					for ( $j = -2; $j <= 2; $j++ ) {
						$rr = $ar + $i;
						$cc = $ac + $j;
						if ( $rr < 0 || $cc < 0 || $rr >= $size || $cc >= $size ) {
							continue;
						}
						$res[ $rr ][ $cc ] = true;
						$m[ $rr ][ $cc ]   = ( max( abs( $i ), abs( $j ) ) !== 1 ) ? 1 : 0;
					}
				}
			}
		}
	}

	// Dark module.
	$m[ $size - 8 ][8] = 1;
	$res[ $size - 8 ][8] = true;

	// Данные + ECC.
	$data   = bc_qr_data_codewords( $text, $ver );
	$cap    = bc_qr_capacity( $ver );
	$poly   = bc_qr_rs_poly( $cap[1] );
	$ec     = array_slice( bc_qr_rs_remainder( $data, $poly ), 0, $cap[1] );
	$stream = array_merge( $data, $ec );

	$bits = '';
	foreach ( $stream as $b ) {
		$bits .= str_pad( decbin( $b ), 8, '0', STR_PAD_LEFT );
	}

	$idx = 0;
	$up  = true;
	for ( $col = $size - 1; $col > 0; $col -= 2 ) {
		if ( 6 === $col ) {
			$col--;
		}
		for ( $k = 0; $k < $size; $k++ ) {
			$row = $up ? $size - 1 - $k : $k;
			foreach ( array( $col, $col - 1 ) as $c ) {
				if ( $c < 0 || $c >= $size || $res[ $row ][ $c ] ) {
					continue;
				}
				$bit = ( $idx < strlen( $bits ) ) ? ( '1' === $bits[ $idx ] ? 1 : 0 ) : 0;
				$m[ $row ][ $c ] = ( ( ( $row + $c ) % 2 === 0 ) ? 1 - $bit : $bit );
				$idx++;
			}
		}
		$up = ! $up;
	}

	// Format info (EC L + маска 0) = 0x77C4.
	$fmt = 0x77C4;
	for ( $i = 0; $i <= 5; $i++ ) {
		$m[8][ $i ] = ( $fmt >> $i ) & 1;
		$m[ $size - 1 - $i ][8] = ( $fmt >> $i ) & 1;
	}
	$m[8][7] = ( $fmt >> 6 ) & 1;   $m[ $size - 7 ][8] = ( $fmt >> 6 ) & 1;
	$m[8][8] = ( $fmt >> 7 ) & 1;   $m[ $size - 8 ][8] = ( $fmt >> 7 ) & 1;
	$m[7][8] = ( $fmt >> 8 ) & 1;   $m[8][ $size - 8 ] = ( $fmt >> 8 ) & 1;
	for ( $i = 9; $i <= 14; $i++ ) {
		$m[ $i - 9 ][8] = ( $fmt >> $i ) & 1;
		$m[8][ $size - 15 + $i ] = ( $fmt >> $i ) & 1;
	}

	return $m;
}

function bc_qr_svg( $text, $scale = 8 ) {
	$m    = bc_qr_build( $text );
	$size = count( $m );
	$dim  = $size * $scale;
	$out  = '<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 ' . $dim . ' ' . $dim . '" shape-rendering="crispEdges"><rect width="' . $dim . '" height="' . $dim . '" fill="#fff"/>';
	for ( $r = 0; $r < $size; $r++ ) {
		for ( $c = 0; $c < $size; $c++ ) {
			if ( 1 === $m[ $r ][ $c ] ) {
				$out .= '<rect x="' . ( $c * $scale ) . '" y="' . ( $r * $scale ) . '" width="' . $scale . '" height="' . $scale . '" fill="#2b332e"/>';
			}
		}
	}
	$out .= '</svg>';
	return $out;
}
function bc_qr_png( $text, $scale = 8 ) {
	if ( ! function_exists( 'imagecreatetruecolor' ) ) {
		return null;
	}
	$m    = bc_qr_build( $text );
	$size = count( $m );
	$dim  = $size * $scale;
	$img  = imagecreatetruecolor( $dim, $dim );
	$white = imagecolorallocate( $img, 255, 255, 255 );
	$black = imagecolorallocate( $img, 43, 51, 46 );
	imagefilledrectangle( $img, 0, 0, $dim - 1, $dim - 1, $white );
	for ( $r = 0; $r < $size; $r++ ) {
		for ( $c = 0; $c < $size; $c++ ) {
			if ( 1 === $m[ $r ][ $c ] ) {
				imagefilledrectangle( $img, $c * $scale, $r * $scale, ( $c + 1 ) * $scale - 1, ( $r + 1 ) * $scale - 1, $black );
			}
		}
	}
	return $img;
}

/**
 * Эндпоинт /qr/{org_id}/ — PNG для скачивания.
 */
function bc_qr_rewrite() {
	add_rewrite_rule( '^qr/([0-9]+)/?$', 'index.php?bc_qr=$matches[1]', 'top' );
}
add_action( 'init', 'bc_qr_rewrite' );

function bc_qr_query_var( $vars ) {
	$vars[] = 'bc_qr';
	return $vars;
}
add_filter( 'query_vars', 'bc_qr_query_var' );

function bc_qr_output() {
	$org_id = (int) get_query_var( 'bc_qr' );
	if ( ! $org_id || 'organizations' !== get_post_type( $org_id ) ) {
		return;
	}
	$img = bc_qr_png( get_permalink( $org_id ), 8 );
	if ( ! $img ) {
		wp_die( 'Библиотека GD недоступна — PNG-выгрузка недоступна.' );
	}
	header( 'Content-Type: image/png' );
	header( 'Content-Disposition: attachment; filename="qr-' . $org_id . '.png"' );
	imagepng( $img );
	imagedestroy( $img );
	exit;
}
add_action( 'template_redirect', 'bc_qr_output' );

