<?php
defined( 'ABSPATH' ) || exit;

class WDTA_Data {

	const STATS = array(
		'il'      => 81,
		'ilce'    => 970,
		'mahalle' => 74276,
		'sokak'   => 1148699,
		'surum'   => 'NVİ 2021.04 / PTT 2021.11',
	);

	const MAHALLE_TYPES = array(
		'm' => 'Mahalle',
		'k' => 'Köy',
		'v' => 'Mevki',
		'z' => 'Mezra',
		'e' => 'Evler',
	);

	private static $tr        = null;
	private static $mahalle   = array();
	private static $sokak     = array();

	public static function data_dir() {
		return WDTA_DIR . 'data/';
	}

	public static function tr() {
		if ( null === self::$tr ) {
			$cached = wp_cache_get( 'tr', 'wdta' );
			if ( is_array( $cached ) ) {
				self::$tr = $cached;
			} else {
				$json     = file_get_contents( self::data_dir() . 'tr.json' ); // phpcs:ignore
				self::$tr = json_decode( $json, true );
				wp_cache_set( 'tr', self::$tr, 'wdta', DAY_IN_SECONDS );
			}
		}
		return self::$tr;
	}

	public static function il( $il_id ) {
		$il_id = (int) $il_id;
		foreach ( self::tr()['iller'] as $row ) {
			if ( (int) $row[0] === $il_id ) {
				return array( 'id' => $il_id, 'ad' => $row[1] );
			}
		}
		return null;
	}

	public static function ilce( $il_id, $ilce_id ) {
		$list    = self::tr()['ilceler'][ (string) (int) $il_id ] ?? array();
		$ilce_id = (int) $ilce_id;
		foreach ( $list as $row ) {
			if ( (int) $row[0] === $ilce_id ) {
				return array( 'id' => $ilce_id, 'ad' => $row[1], 'pk' => $row[2] );
			}
		}
		return null;
	}

	public static function ilce_by_name( $il_id, $name ) {
		$needle = self::fold( $name );
		foreach ( self::tr()['ilceler'][ (string) (int) $il_id ] ?? array() as $row ) {
			if ( self::fold( $row[1] ) === $needle ) {
				return array( 'id' => (int) $row[0], 'ad' => $row[1], 'pk' => $row[2] );
			}
		}
		return null;
	}

	public static function mahalleler( $ilce_id ) {
		$ilce_id = (int) $ilce_id;
		if ( ! isset( self::$mahalle[ $ilce_id ] ) ) {
			$file = self::data_dir() . 'mahalle/' . $ilce_id . '.json';
			self::$mahalle[ $ilce_id ] = is_readable( $file ) ? json_decode( file_get_contents( $file ), true ) : array(); // phpcs:ignore
		}
		return self::$mahalle[ $ilce_id ];
	}

	public static function mahalle( $ilce_id, $mahalle_id ) {
		$mahalle_id = (int) $mahalle_id;
		foreach ( self::mahalleler( $ilce_id ) as $row ) {
			if ( (int) $row[0] === $mahalle_id ) {
				return array( 'id' => $mahalle_id, 'ad' => $row[1], 'tip' => $row[2], 'pk' => $row[3] );
			}
		}
		return null;
	}

	public static function sokaklar( $ilce_id, $mahalle_id ) {
		$ilce_id    = (int) $ilce_id;
		$mahalle_id = (int) $mahalle_id;
		$key        = $ilce_id . ':' . $mahalle_id;

		if ( isset( self::$sokak[ $key ] ) ) {
			return self::$sokak[ $key ];
		}

		$cache_file = self::cache_dir() . $ilce_id . '/' . $mahalle_id . '.json';
		if ( is_readable( $cache_file ) ) {
			$rows = json_decode( file_get_contents( $cache_file ), true ); // phpcs:ignore
			if ( is_array( $rows ) ) {
				return self::$sokak[ $key ] = $rows;
			}
		}

		$gz = self::data_dir() . 'sokak/' . $ilce_id . '.json.gz';
		if ( ! is_readable( $gz ) || ! function_exists( 'gzdecode' ) ) {
			return self::$sokak[ $key ] = array();
		}

		$all  = json_decode( gzdecode( file_get_contents( $gz ) ), true ); // phpcs:ignore
		$rows = isset( $all[ (string) $mahalle_id ] ) ? $all[ (string) $mahalle_id ] : array();

		if ( self::ensure_cache_dir( $ilce_id ) ) {
			file_put_contents( $cache_file, wp_json_encode( $rows, JSON_UNESCAPED_UNICODE ), LOCK_EX ); // phpcs:ignore
		}

		return self::$sokak[ $key ] = $rows;
	}

	public static function sokak( $ilce_id, $mahalle_id, $sokak_id ) {
		$sokak_id = (int) $sokak_id;
		foreach ( self::sokaklar( $ilce_id, $mahalle_id ) as $row ) {
			if ( (int) $row[0] === $sokak_id ) {
				return array( 'id' => $sokak_id, 'ad' => self::sokak_label( $row[1], $row[2] ), 'tip' => $row[2] );
			}
		}
		return null;
	}

	public static function sokak_label( $name, $type ) {
		$numeric = (bool) preg_match( '/^\d+[A-Za-zÇĞİÖŞÜçğıöşü]?$/u', $name );
		switch ( $type ) {
			case 'c':
				return $numeric ? $name . '. Cadde' : $name . ' Caddesi';
			case 'b':
				return $numeric ? $name . '. Bulvar' : $name . ' Bulvarı';
			case 'y':
				return $name . ' Meydanı';
			case 'k':
				return $name . ' Küme Evleri';
			case 'o':
				return $name . ' Köy Sokağı';
			default:
				return $numeric ? $name . '. Sokak' : $name . ' Sokak';
		}
	}

	public static function state_code( $il_id ) {
		return 'TR' . str_pad( (string) (int) $il_id, 2, '0', STR_PAD_LEFT );
	}

	public static function il_from_state( $state ) {
		if ( preg_match( '/^TR(\d{2})$/', (string) $state, $m ) ) {
			return (int) $m[1];
		}
		return 0;
	}

	public static function fold( $s ) {
		$s = str_replace( array( 'I', 'İ' ), array( 'ı', 'i' ), (string) $s );
		$s = mb_strtolower( $s, 'UTF-8' );
		$s = strtr( $s, array( 'ç' => 'c', 'ğ' => 'g', 'ı' => 'i', 'ö' => 'o', 'ş' => 's', 'ü' => 'u', 'â' => 'a', 'î' => 'i', 'û' => 'u' ) );
		return trim( preg_replace( '/\s+/', ' ', $s ) );
	}

	public static function cache_dir() {
		$up = wp_upload_dir( null, false );
		return trailingslashit( $up['basedir'] ) . 'wd-turkiye-adres/';
	}

	public static function ensure_cache_dir( $sub = '' ) {
		$base = self::cache_dir();
		if ( ! is_dir( $base ) ) {
			if ( ! wp_mkdir_p( $base ) ) {
				return false;
			}
			file_put_contents( $base . 'index.php', "<?php\n// Silence is golden.\n" ); // phpcs:ignore
		}
		if ( '' !== $sub ) {
			$dir = $base . (int) $sub . '/';
			if ( ! is_dir( $dir ) && ! wp_mkdir_p( $dir ) ) {
				return false;
			}
		}
		return is_writable( $base );
	}

	public static function cache_stats() {
		$dir   = self::cache_dir();
		$files = 0;
		$bytes = 0;
		if ( is_dir( $dir ) ) {
			$it = new RecursiveIteratorIterator( new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ) );
			foreach ( $it as $f ) {
				if ( $f->isFile() && 'json' === $f->getExtension() ) {
					$files++;
					$bytes += $f->getSize();
				}
			}
		}
		return array( 'files' => $files, 'bytes' => $bytes );
	}

	public static function clear_cache() {
		$dir = self::cache_dir();
		if ( ! is_dir( $dir ) ) {
			return;
		}
		$it = new RecursiveIteratorIterator(
			new RecursiveDirectoryIterator( $dir, FilesystemIterator::SKIP_DOTS ),
			RecursiveIteratorIterator::CHILD_FIRST
		);
		foreach ( $it as $f ) {
			if ( $f->isDir() ) {
				@rmdir( $f->getPathname() ); // phpcs:ignore
			} elseif ( 'index.php' !== $f->getFilename() || $f->getPath() !== rtrim( $dir, '/' ) ) {
				@unlink( $f->getPathname() ); // phpcs:ignore
			}
		}
	}
}
