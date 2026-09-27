<?php
/**
 * Güncelleme ve destek bağlantıları.
 *
 * - GitHub deposundaki main dalından WordPress paneline güncelleme bildirimi (Update URI: github.com).
 * - Güncelleme paketi eksik dosya içeriyorsa kurulmaz; site bozulmaz.
 * - Eklenti listesinde destek (oblifex.com), geliştirici ve "Güncellemeleri denetle" bağlantıları.
 */

defined( 'ABSPATH' ) || exit;

class WDTA_Oblifex {

	const REPO   = 'webdanismani/wd-turkiye-adress';
	const NAME   = 'WD Türkiye Adres';
	const PAGE   = 'wd-turkiye-adres';
	const CACHE  = 'wdta_guncelleme';
	const SITE   = 'https://oblifex.com';
	const DEV    = 'https://webdanismani.com';

	public static function init() {
		add_filter( 'update_plugins_github.com', array( __CLASS__, 'guncelleme' ), 10, 3 );
		add_filter( 'plugins_api', array( __CLASS__, 'bilgi' ), 20, 3 );
		add_filter( 'upgrader_source_selection', array( __CLASS__, 'klasor' ), 10, 4 );
		add_action( 'upgrader_process_complete', array( __CLASS__, 'temizle' ) );

		if ( is_admin() ) {
			add_filter( 'plugin_row_meta', array( __CLASS__, 'satir' ), 10, 2 );
			add_action( 'admin_init', array( __CLASS__, 'denetle' ) );
			add_action( 'admin_notices', array( __CLASS__, 'bildirim' ) );
			add_filter( 'admin_footer_text', array( __CLASS__, 'alt_metin' ), 20 );
		}
	}

	/** Etkinleştirmede çalışır. */
	public static function etkinlestirildi() {
		add_option( 'wdta_kurulum', time(), '', false );
		delete_transient( self::CACHE );
		set_transient( 'wdta_hosgeldin', 1, 120 );
	}

	public static function temizle() {
		delete_transient( self::CACHE );
	}

	public static function basename() {
		return plugin_basename( WDTA_FILE );
	}

	public static function url( $medium ) {
		return add_query_arg(
			array(
				'utm_source'   => 'wp-plugin',
				'utm_medium'   => $medium,
				'utm_campaign' => 'wd-turkiye-adress',
			),
			self::SITE . '/'
		);
	}

	/* ------------------------------------------------------------------ */
	/* Güncelleme                                                          */
	/* ------------------------------------------------------------------ */

	/** GitHub main dalındaki sürüm numarası (12 saat önbellekli). */
	public static function uzak_surum() {
		$cached = get_transient( self::CACHE );
		if ( false !== $cached ) {
			return (string) $cached;
		}
		$file = basename( WDTA_FILE );
		$res  = wp_remote_get(
			'https://raw.githubusercontent.com/' . self::REPO . '/main/' . $file,
			array(
				'timeout' => 8,
				'headers' => array( 'Range' => 'bytes=0-2047' ),
			)
		);
		$version = '';
		$code    = (int) wp_remote_retrieve_response_code( $res );
		if ( ! is_wp_error( $res ) && ( 200 === $code || 206 === $code ) ) {
			if ( preg_match( '/^[ \t\/*#@]*Version:\s*([0-9][0-9a-zA-Z.\-]*)/mi', wp_remote_retrieve_body( $res ), $m ) ) {
				$version = $m[1];
			}
		}
		set_transient( self::CACHE, $version, $version ? 12 * HOUR_IN_SECONDS : HOUR_IN_SECONDS );
		return $version;
	}

	public static function paket() {
		return 'https://github.com/' . self::REPO . '/archive/refs/heads/main.zip';
	}

	public static function guncelleme( $update, $plugin_data, $plugin_file ) {
		if ( self::basename() !== $plugin_file ) {
			return $update;
		}
		$version = self::uzak_surum();
		if ( '' === $version ) {
			return $update;
		}
		return array(
			'id'           => 'github.com/' . self::REPO,
			'slug'         => dirname( self::basename() ),
			'plugin'       => $plugin_file,
			'version'      => $version,
			'new_version'  => $version,
			'url'          => 'https://github.com/' . self::REPO,
			'package'      => version_compare( $version, WDTA_VERSION, '>' ) ? self::paket() : '',
			'requires_php' => '7.4',
		);
	}

	public static function bilgi( $result, $action, $args ) {
		if ( 'plugin_information' !== $action || empty( $args->slug ) || dirname( self::basename() ) !== $args->slug ) {
			return $result;
		}
		$version = self::uzak_surum();
		$repo    = 'https://github.com/' . self::REPO;
		return (object) array(
			'name'          => self::NAME,
			'slug'          => $args->slug,
			'version'       => $version ? $version : WDTA_VERSION,
			'author'        => '<a href="' . esc_url( self::DEV ) . '">Web Danışmanı</a>',
			'homepage'      => self::url( 'plugin-info' ),
			'requires'      => '6.0',
			'requires_php'  => '7.4',
			'download_link' => self::paket(),
			'sections'      => array(
				'description' => '<p>' . esc_html( self::NAME ) . ' ücretsiz bir eklentidir. Destek, soru ve öneriler için ve diğer ücretsiz yazılımlarımız için <a href="' . esc_url( self::url( 'plugin-info' ) ) . '" target="_blank">oblifex.com</a>.</p><p>Geliştirici: <a href="' . esc_url( self::DEV ) . '" target="_blank">Web Danışmanı</a></p>',
				'changelog'   => '<p>Değişiklikler için <a href="' . esc_url( $repo . '/blob/main/CHANGELOG.md' ) . '" target="_blank">CHANGELOG.md</a> dosyasına bakın.</p>',
			),
		);
	}

	/**
	 * GitHub arşivi "<repo>-main/" klasörüyle gelir; kurulu klasör adına taşı.
	 * Paket eksikse (ör. includes/ yoksa) güncellemeyi durdur.
	 */
	public static function klasor( $source, $remote_source, $upgrader, $hook_extra = array() ) {
		global $wp_filesystem;
		if ( empty( $hook_extra['plugin'] ) || self::basename() !== $hook_extra['plugin'] || is_wp_error( $source ) ) {
			return $source;
		}
		$main = trailingslashit( $source ) . basename( WDTA_FILE );
		if ( ! $wp_filesystem->exists( $main ) || ! $wp_filesystem->is_dir( trailingslashit( $source ) . 'includes' ) ) {
			return new WP_Error( 'wdta_eksik_paket', self::NAME . ' güncelleme paketi eksik görünüyor, güncelleme yapılmadı. Mevcut sürüm çalışmaya devam ediyor.' );
		}
		$target = trailingslashit( $remote_source ) . dirname( self::basename() );
		if ( untrailingslashit( $source ) === untrailingslashit( $target ) ) {
			return $source;
		}
		if ( $wp_filesystem->move( untrailingslashit( $source ), $target, true ) ) {
			return trailingslashit( $target );
		}
		return new WP_Error( 'wdta_klasor', self::NAME . ' güncelleme klasörü taşınamadı.' );
	}

	/* ------------------------------------------------------------------ */
	/* Yönetim ekranı                                                      */
	/* ------------------------------------------------------------------ */

	public static function satir( $links, $file ) {
		if ( self::basename() !== $file ) {
			return $links;
		}
		$links[] = '<a href="' . esc_url( self::url( 'plugin-row' ) ) . '" target="_blank" rel="noopener"><strong>Destek: oblifex.com</strong></a>';
		if ( current_user_can( 'update_plugins' ) ) {
			$links[] = '<a href="' . esc_url( wp_nonce_url( admin_url( 'plugins.php?wdta_denetle=1' ), 'wdta_denetle' ) ) . '">Güncellemeleri denetle</a>';
		}
		return $links;
	}

	public static function denetle() {
		if ( empty( $_GET['wdta_denetle'] ) || ! current_user_can( 'update_plugins' ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return;
		}
		check_admin_referer( 'wdta_denetle' );
		delete_transient( self::CACHE );
		$version = self::uzak_surum();
		delete_site_transient( 'update_plugins' );
		wp_update_plugins();
		$state = '' === $version ? 'hata' : ( version_compare( $version, WDTA_VERSION, '>' ) ? 'var' : 'yok' );
		wp_safe_redirect( admin_url( 'plugins.php?wdta_denetim=' . $state ) );
		exit;
	}

	public static function bildirim() {
		if ( ! current_user_can( 'activate_plugins' ) ) {
			return;
		}
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || 'plugins' !== $screen->id ) {
			return;
		}
		if ( get_transient( 'wdta_hosgeldin' ) ) {
			delete_transient( 'wdta_hosgeldin' );
			printf(
				'<div class="notice notice-success is-dismissible"><p><strong>%1$s etkinleştirildi.</strong> <a href="%2$s">Ayarları açın</a>. Sorularınız ve diğer ücretsiz eklentilerimiz için <a href="%3$s" target="_blank" rel="noopener">oblifex.com</a>.</p></div>',
				esc_html( self::NAME ),
				esc_url( admin_url( 'admin.php?page=' . self::PAGE ) ),
				esc_url( self::url( 'activation' ) )
			);
		}
		$state = isset( $_GET['wdta_denetim'] ) ? sanitize_key( wp_unslash( $_GET['wdta_denetim'] ) ) : ''; // phpcs:ignore WordPress.Security.NonceVerification
		$msgs  = array(
			'var'  => array( 'warning', 'yeni bir sürüm var. Aşağıdaki "Şimdi güncelle" bağlantısını kullanabilirsiniz.' ),
			'yok'  => array( 'success', 'güncel.' ),
			'hata' => array( 'error', 'için GitHub\'a ulaşılamadı. Daha sonra tekrar deneyin.' ),
		);
		if ( isset( $msgs[ $state ] ) ) {
			printf( '<div class="notice notice-%1$s is-dismissible"><p><strong>%2$s</strong> %3$s</p></div>', esc_attr( $msgs[ $state ][0] ), esc_html( self::NAME ), esc_html( $msgs[ $state ][1] ) );
		}
	}

	public static function alt_metin( $text ) {
		$screen = function_exists( 'get_current_screen' ) ? get_current_screen() : null;
		if ( ! $screen || false === strpos( (string) $screen->id, self::PAGE ) ) {
			return $text;
		}
		return sprintf(
			'%1$s ücretsizdir · Destek ve diğer yazılımlar: <a href="%2$s" target="_blank" rel="noopener">oblifex.com</a> · Geliştirici: <a href="%3$s" target="_blank" rel="noopener">Web Danışmanı</a>',
			esc_html( self::NAME ),
			esc_url( self::url( 'admin-footer' ) ),
			esc_url( self::DEV )
		);
	}
}
