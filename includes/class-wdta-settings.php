<?php
defined( 'ABSPATH' ) || exit;

class WDTA_Settings {

	const OPTION = 'wdta_settings';

	private static $cache = null;

	public static function defaults() {
		return array(
			'enabled'          => 1,
			'apply_billing'    => 1,
			'apply_shipping'   => 1,
			'sokak_mode'       => 'list_manual',
			'include_rural'    => 1,
			'kapi_required'    => 1,
			'daire_required'   => 0,
			'show_bina'        => 1,
			'show_tarif'       => 1,
			'tarif_to_address' => 1,
			'show_preview'     => 1,
			'auto_advance'     => 1,
			'popular_iller'    => '34,6,35,16,7',
			'client_validate'  => 1,
			'theme'            => 'auto',
			'accent'           => '#1f6f5c',
			'radius'           => 12,
			'density'          => 'comfortable',
		);
	}

	public static function all() {
		if ( null === self::$cache ) {
			$saved       = get_option( self::OPTION, array() );
			self::$cache = wp_parse_args( is_array( $saved ) ? $saved : array(), self::defaults() );
		}
		return self::$cache;
	}

	public static function get( $key ) {
		$all = self::all();
		return isset( $all[ $key ] ) ? $all[ $key ] : null;
	}

	public static function flush() {
		self::$cache = null;
	}

	public static function sanitize( $input ) {
		$d   = self::defaults();
		$out = array();
		$input = is_array( $input ) ? $input : array();

		foreach ( array( 'enabled', 'apply_billing', 'apply_shipping', 'include_rural', 'kapi_required', 'daire_required', 'show_bina', 'show_tarif', 'tarif_to_address', 'show_preview', 'auto_advance', 'client_validate' ) as $k ) {
			$out[ $k ] = empty( $input[ $k ] ) ? 0 : 1;
		}

		$out['sokak_mode'] = in_array( $input['sokak_mode'] ?? '', array( 'list_manual', 'list_only', 'manual' ), true ) ? $input['sokak_mode'] : $d['sokak_mode'];
		$out['theme']      = in_array( $input['theme'] ?? '', array( 'auto', 'light', 'dark' ), true ) ? $input['theme'] : $d['theme'];
		$out['density']    = in_array( $input['density'] ?? '', array( 'comfortable', 'compact' ), true ) ? $input['density'] : $d['density'];

		$accent        = sanitize_hex_color( $input['accent'] ?? '' );
		$out['accent'] = $accent ? $accent : $d['accent'];

		$out['radius'] = max( 0, min( 24, (int) ( $input['radius'] ?? $d['radius'] ) ) );

		$ids = array_filter( array_map( 'absint', explode( ',', (string) ( $input['popular_iller'] ?? '' ) ) ), function ( $v ) {
			return $v >= 1 && $v <= 81;
		} );
		$out['popular_iller'] = implode( ',', array_slice( array_unique( $ids ), 0, 8 ) );

		self::flush();
		return $out;
	}
}
