<?php
defined( 'ABSPATH' ) || exit;

class WDTA_Rest {

	const NS = 'wdta/v1';

	public static function init() {
		add_action( 'rest_api_init', array( __CLASS__, 'routes' ) );
	}

	public static function routes() {
		register_rest_route( self::NS, '/mahalle/(?P<ilce>\d{1,6})', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'mahalle' ),
		) );

		register_rest_route( self::NS, '/sokak/(?P<ilce>\d{1,6})/(?P<mahalle>\d{1,8})', array(
			'methods'             => 'GET',
			'permission_callback' => '__return_true',
			'callback'            => array( __CLASS__, 'sokak' ),
		) );
	}

	private static function respond( $data ) {
		$res = new WP_REST_Response( $data, 200 );
		$res->header( 'Cache-Control', 'public, max-age=2592000, immutable' );
		$res->header( 'X-WDTA-Data', WDTA_Data::STATS['surum'] );
		return $res;
	}

	public static function mahalle( WP_REST_Request $req ) {
		$rows = WDTA_Data::mahalleler( (int) $req['ilce'] );
		if ( ! $rows ) {
			return new WP_Error( 'wdta_not_found', 'İlçe bulunamadı.', array( 'status' => 404 ) );
		}
		return self::respond( $rows );
	}

	public static function sokak( WP_REST_Request $req ) {
		$ilce    = (int) $req['ilce'];
		$mahalle = (int) $req['mahalle'];

		if ( ! WDTA_Data::mahalle( $ilce, $mahalle ) ) {
			return new WP_Error( 'wdta_not_found', 'Mahalle bulunamadı.', array( 'status' => 404 ) );
		}
		return self::respond( WDTA_Data::sokaklar( $ilce, $mahalle ) );
	}
}
