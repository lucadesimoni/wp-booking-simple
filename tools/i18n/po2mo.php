<?php
/**
 * Minimal .po -> .mo compiler (no gettext tooling required).
 *
 * Usage: php tools/i18n/po2mo.php lang/wp-booking-simple-de_DE.po
 *        (writes the matching .mo next to it)
 *
 * Supports msgctxt, msgid, msgid_plural and msgstr[N], with the standard
 * multi-line "..." continuation syntax. This is the canonical way to rebuild
 * the .mo files after editing a .po translation.
 *
 * @package WP_Booking_Simple
 */

if ( $argc < 2 ) {
	fwrite( STDERR, "Usage: php po2mo.php <file.po> [out.mo]\n" );
	exit( 1 );
}

$po_file = $argv[1];
$mo_file = isset( $argv[2] ) ? $argv[2] : preg_replace( '/\.po$/', '.mo', $po_file );

$lines = file( $po_file, FILE_IGNORE_NEW_LINES );

function po_unescape( $s ) {
	return strtr(
		$s,
		array( '\\n' => "\n", '\\t' => "\t", '\\r' => "\r", '\\"' => '"', '\\\\' => '\\' )
	);
}

$entries = array();
$cur     = array(
	'ctxt'   => null,
	'id'     => null,
	'plural' => null,
	'str'    => array(),
);
$field = null; // which buffer the continuation lines append to.

$flush = function () use ( &$entries, &$cur ) {
	if ( $cur['id'] === null ) {
		return;
	}
	$key = ( $cur['ctxt'] !== null ? $cur['ctxt'] . "\4" : '' ) . $cur['id'];
	if ( $cur['plural'] !== null ) {
		$key .= "\0" . $cur['plural'];
		ksort( $cur['str'] );
		$val = implode( "\0", $cur['str'] );
	} else {
		$val = isset( $cur['str'][0] ) ? $cur['str'][0] : '';
	}
	$entries[ $key ] = $val;
};

foreach ( $lines as $line ) {
	if ( $line === '' || $line[0] === '#' ) {
		continue;
	}
	if ( preg_match( '/^msgctxt\s+"(.*)"$/', $line, $m ) ) {
		$flush();
		$cur   = array( 'ctxt' => po_unescape( $m[1] ), 'id' => null, 'plural' => null, 'str' => array() );
		$field = 'ctxt';
	} elseif ( preg_match( '/^msgid\s+"(.*)"$/', $line, $m ) ) {
		if ( $cur['id'] !== null && $field !== 'ctxt' ) {
			$flush();
			$cur = array( 'ctxt' => null, 'id' => null, 'plural' => null, 'str' => array() );
		}
		$cur['id'] = po_unescape( $m[1] );
		$field     = 'id';
	} elseif ( preg_match( '/^msgid_plural\s+"(.*)"$/', $line, $m ) ) {
		$cur['plural'] = po_unescape( $m[1] );
		$field         = 'plural';
	} elseif ( preg_match( '/^msgstr\[(\d+)\]\s+"(.*)"$/', $line, $m ) ) {
		$cur['str'][ (int) $m[1] ] = po_unescape( $m[2] );
		$field                     = 'str' . (int) $m[1];
	} elseif ( preg_match( '/^msgstr\s+"(.*)"$/', $line, $m ) ) {
		$cur['str'][0] = po_unescape( $m[1] );
		$field         = 'str0';
	} elseif ( preg_match( '/^"(.*)"$/', $line, $m ) ) {
		$piece = po_unescape( $m[1] );
		if ( $field === 'ctxt' ) {
			$cur['ctxt'] .= $piece;
		} elseif ( $field === 'id' ) {
			$cur['id'] .= $piece;
		} elseif ( $field === 'plural' ) {
			$cur['plural'] .= $piece;
		} elseif ( strpos( (string) $field, 'str' ) === 0 ) {
			$idx                 = (int) substr( $field, 3 );
			$cur['str'][ $idx ] .= $piece;
		}
	}
}
$flush();

// Build the .mo (originals sorted by byte order, as gettext requires).
ksort( $entries, SORT_STRING );
$ids  = array_keys( $entries );
$strs = array_values( $entries );
$n    = count( $entries );

$o_off = 28;
$t_off = $o_off + $n * 8;
$h_off = $t_off + $n * 8;

$ids_data  = '';
$id_table  = '';
foreach ( $ids as $id ) {
	$id_table .= pack( 'VV', strlen( $id ), $h_off + strlen( $ids_data ) );
	$ids_data .= $id . "\0";
}
$strs_data = '';
$str_table = '';
$base2     = $h_off + strlen( $ids_data );
foreach ( $strs as $s ) {
	$str_table .= pack( 'VV', strlen( $s ), $base2 + strlen( $strs_data ) );
	$strs_data .= $s . "\0";
}

$mo = pack( 'V', 0x950412de ) . pack( 'V', 0 ) . pack( 'V', $n )
	. pack( 'V', $o_off ) . pack( 'V', $t_off ) . pack( 'V', 0 ) . pack( 'V', $h_off )
	. $id_table . $str_table . $ids_data . $strs_data;

file_put_contents( $mo_file, $mo );
echo 'Compiled ' . $n . ' entries -> ' . $mo_file . "\n";
