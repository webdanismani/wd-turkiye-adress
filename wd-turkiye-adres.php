<?php
/**
 * Plugin Name:       WD Türkiye Adres — İl İlçe Mahalle Sokak Seçimi
 * Plugin URI:        https://oblifex.com/?utm_source=wp-plugin&utm_medium=header&utm_campaign=wd-turkiye-adres
 * Description:       WooCommerce ödeme ve hesap sayfalarında il → ilçe → mahalle → cadde/sokak zincirleme seçimi, kapı/daire alanları, otomatik posta kodu ve canlı adres etiketi önizlemesi.
 * Version:           1.0.0
 * Author:            Oblifex
 * Author URI:        https://oblifex.com
 * License:           GPL-3.0-or-later
 * License URI:       https://www.gnu.org/licenses/gpl-3.0.html
 * Text Domain:       wd-turkiye-adres
 * Update URI:        https://github.com/oblifex/wd-turkiye-adres
 * Requires at least: 6.0
 * Requires PHP:      7.4
 * WC requires at least: 7.0
 * WC tested up to:   11.1
 */

defined( 'ABSPATH' ) || exit;

define( 'WDTA_VERSION', '1.0.0' );
define( 'WDTA_FILE', __FILE__ );
define( 'WDTA_DIR', plugin_dir_path( __FILE__ ) );
define( 'WDTA_URL', plugin_dir_url( __FILE__ ) );

require_once WDTA_DIR . 'includes/class-wdta-data.php';
require_once WDTA_DIR . 'includes/class-wdta-settings.php';
require_once WDTA_DIR . 'includes/class-wdta-rest.php';
require_once WDTA_DIR . 'includes/class-wdta-address.php';
require_once WDTA_DIR . 'includes/class-wdta-admin.php';
require_once WDTA_DIR . 'includes/class-wdta-oblifex.php';

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
