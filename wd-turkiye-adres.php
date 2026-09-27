<?php
/**
 * Plugin Name:       WD Türkiye Adres — İl İlçe Mahalle Sokak Seçimi
 * Plugin URI:        https://oblifex.com/?utm_source=wp-plugin&utm_medium=header&utm_campaign=wd-turkiye-adres
 * Description:       WooCommerce ödeme ve hesap sayfalarında il → ilçe → mahalle → cadde/sokak zincirleme seçimi, kapı/daire alanları, otomatik posta kodu ve canlı adres etiketi önizlemesi.
 * Version:           1.0.1
 * Author:            Web Danışmanı
 * Author URI:        https://webdanismani.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wd-turkiye-adres
 * Update URI:        https://github.com/webdanismani/wd-turkiye-adress
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   11.1
 */

defined( 'ABSPATH' ) || exit;

define( 'WDTA_VERSION', '1.0.1' );
define( 'WDTA_FILE', __FILE__ );
define( 'WDTA_DIR', plugin_dir_path( __FILE__ ) );
define( 'WDTA_URL', plugin_dir_url( __FILE__ ) );

// Eksik yükleme koruması: dosyalar eksikse (ör. GitHub web yüklemesinde klasörler atlanmışsa)
// site çökmez; eklenti çalışmaz ve yöneticiye yeniden kurulum uyarısı gösterilir.
$wdta_required = array(
	'includes/class-wdta-data.php',
	'includes/class-wdta-settings.php',
	'includes/class-wdta-rest.php',
	'includes/class-wdta-address.php',
	'includes/class-wdta-admin.php',
	'includes/class-wdta-oblifex.php',
	'assets/css/admin.css',
	'assets/css/wdta.css',
	'assets/js/admin.js',
	'assets/js/wdta.js',
	'data/tr.json',
	'data/mahalle/',
	'data/sokak/',
);
$wdta_missing  = array();
foreach ( $wdta_required as $wdta_file ) {
	$wdta_path = WDTA_DIR . $wdta_file;
	$wdta_ok   = '/' === substr( $wdta_file, -1 ) ? ( is_dir( $wdta_path ) && (bool) glob( $wdta_path . '*' ) ) : is_readable( $wdta_path );
	if ( ! $wdta_ok ) {
		$wdta_missing[] = $wdta_file;
	}
}
if ( $wdta_missing ) {
	add_action(
		'admin_notices',
		function () use ( $wdta_missing ) {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			$list = implode( ', ', array_slice( $wdta_missing, 0, 5 ) ) . ( count( $wdta_missing ) > 5 ? ' …' : '' );
			printf(
				'<div class="notice notice-error"><p><strong>WD Türkiye Adres eksik yüklenmiş, bu yüzden çalıştırılmadı.</strong> Bulunamayan dosyalar: <code>%s</code></p><p>Eklentiyi silip <a href="%s" target="_blank" rel="noopener">GitHub sayfasından</a> (Code → Download ZIP) ya da <a href="https://oblifex.com" target="_blank" rel="noopener">oblifex.com</a> üzerindeki paketle yeniden kurun.</p></div>',
				esc_html( $list ),
				esc_url( 'https://github.com/webdanismani/wd-turkiye-adress' )
			);
		}
	);
	return;
}
foreach ( $wdta_required as $wdta_file ) {
	if ( '.php' === substr( $wdta_file, -4 ) ) {
		require_once WDTA_DIR . $wdta_file;
	}
}
unset( $wdta_required, $wdta_missing, $wdta_file, $wdta_path, $wdta_ok );

add_action( 'before_woocommerce_init', function () {
	if ( class_exists( \Automattic\WooCommerce\Utilities\FeaturesUtil::class ) ) {
		\Automattic\WooCommerce\Utilities\FeaturesUtil::declare_compatibility( 'custom_order_tables', WDTA_FILE, true );
	}
} );

add_action( 'plugins_loaded', function () {
	WDTA_Rest::init();

	WDTA_Oblifex::init();

	if ( is_admin() ) {
		WDTA_Admin::init();
	}

	if ( ! class_exists( 'WooCommerce' ) ) {
		add_action( 'admin_notices', function () {
			if ( ! current_user_can( 'activate_plugins' ) ) {
				return;
			}
			echo '<div class="notice notice-error"><p><strong>WD Türkiye Adres</strong> çalışmak için WooCommerce eklentisine ihtiyaç duyar.</p></div>';
		} );
		return;
	}

	if ( WDTA_Settings::get( 'enabled' ) ) {
		WDTA_Address::init();
	}
} );

register_activation_hook( __FILE__, function () {
	WDTA_Oblifex::etkinlestirildi();
	if ( false === get_option( WDTA_Settings::OPTION ) ) {
		add_option( WDTA_Settings::OPTION, WDTA_Settings::defaults() );
	}
	WDTA_Data::ensure_cache_dir();
} );

add_filter( 'plugin_action_links_' . plugin_basename( __FILE__ ), function ( $links ) {
	array_unshift( $links, '<a href="' . esc_url( admin_url( 'admin.php?page=wd-turkiye-adres' ) ) . '">Ayarlar</a>' );
	return $links;
} );
