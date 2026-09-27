<?php
defined( 'ABSPATH' ) || exit;

class WDTA_Admin {

	const SLUG = 'wd-turkiye-adres';

	public static function init() {
		add_action( 'admin_menu', array( __CLASS__, 'menu' ), 60 );
		add_action( 'admin_init', array( __CLASS__, 'register' ) );
		add_action( 'admin_post_wdta_action', array( __CLASS__, 'handle_action' ) );
		add_action( 'admin_enqueue_scripts', array( __CLASS__, 'assets' ) );
		add_action( 'admin_notices', array( __CLASS__, 'block_notice' ) );
	}

	public static function menu() {
		$parent = class_exists( 'WooCommerce' ) ? 'woocommerce' : 'options-general.php';
		add_submenu_page( $parent, 'WD Türkiye Adres', 'Türkiye Adres', 'manage_woocommerce', self::SLUG, array( __CLASS__, 'page' ) );
	}

	public static function register() {
		register_setting( 'wdta_group', WDTA_Settings::OPTION, array(
			'type'              => 'array',
			'sanitize_callback' => array( 'WDTA_Settings', 'sanitize' ),
			'default'           => WDTA_Settings::defaults(),
		) );
	}

	public static function assets( $hook ) {
		if ( false === strpos( (string) $hook, self::SLUG ) ) {
			return;
		}
		wp_enqueue_style( 'wdta-admin', WDTA_URL . 'assets/css/admin.css', array(), WDTA_VERSION );
		wp_enqueue_script( 'wdta-admin', WDTA_URL . 'assets/js/admin.js', array(), WDTA_VERSION, true );
	}

	/* ------------------------------------------------------------------ */

	public static function checkout_page_state() {
		if ( ! function_exists( 'wc_get_page_id' ) ) {
			return array( 'id' => 0, 'block' => false, 'backup' => false );
		}
		$id   = (int) wc_get_page_id( 'checkout' );
		$post = $id > 0 ? get_post( $id ) : null;
		return array(
			'id'     => $id,
			'block'  => $post ? has_block( 'woocommerce/checkout', $post ) : false,
			'backup' => $post ? (bool) get_post_meta( $id, '_wdta_block_backup', true ) : false,
		);
	}

	public static function block_notice() {
		if ( ! current_user_can( 'manage_woocommerce' ) || ! WDTA_Settings::get( 'enabled' ) ) {
			return;
		}
		$screen = get_current_screen();
		if ( $screen && false !== strpos( (string) $screen->id, self::SLUG ) ) {
			return;
		}
		$st = self::checkout_page_state();
		if ( ! $st['block'] ) {
			return;
		}
		printf(
			'<div class="notice notice-warning"><p><strong>WD Türkiye Adres:</strong> Ödeme sayfanız blok tabanlı. Adres seçimi klasik ödeme sayfasında çalışır. <a href="%s">Tek tıkla dönüştür</a></p></div>',
			esc_url( admin_url( 'admin.php?page=' . self::SLUG . '#uyumluluk' ) )
		);
	}

	public static function handle_action() {
		if ( ! current_user_can( 'manage_woocommerce' ) ) {
			wp_die( 'Yetkiniz yok.' );
		}
		check_admin_referer( 'wdta_action' );
		$do  = isset( $_POST['do'] ) ? sanitize_key( wp_unslash( $_POST['do'] ) ) : '';
		$msg = '';

		if ( 'clear_cache' === $do ) {
			WDTA_Data::clear_cache();
			$msg = 'cache';
		} elseif ( 'to_classic' === $do ) {
			$st = self::checkout_page_state();
			if ( $st['id'] && $st['block'] ) {
				$post = get_post( $st['id'] );
				update_post_meta( $st['id'], '_wdta_block_backup', wp_slash( $post->post_content ) );
				wp_update_post( array(
					'ID'           => $st['id'],
					'post_content' => "<!-- wp:shortcode -->\n[woocommerce_checkout]\n<!-- /wp:shortcode -->",
				) );
				$msg = 'classic';
			}
		} elseif ( 'restore_block' === $do ) {
			$st = self::checkout_page_state();
			$bk = $st['id'] ? get_post_meta( $st['id'], '_wdta_block_backup', true ) : '';
			if ( $bk ) {
				wp_update_post( array( 'ID' => $st['id'], 'post_content' => wp_slash( $bk ) ) );
				delete_post_meta( $st['id'], '_wdta_block_backup' );
				$msg = 'restored';
			}
		}

		wp_safe_redirect( admin_url( 'admin.php?page=' . self::SLUG . '&wdta_msg=' . $msg . '#' . ( 'clear_cache' === $do ? 'veri' : 'uyumluluk' ) ) );
		exit;
	}

	/* ------------------------------------------------------------------ */

	private static function toggle( $key, $title, $desc ) {
		$s = WDTA_Settings::all();
		printf(
			'<label class="wa-switch"><span class="wa-switch__text"><strong>%1$s</strong><small>%2$s</small></span><input type="checkbox" name="%3$s[%4$s]" value="1" %5$s><i aria-hidden="true"></i></label>',
			esc_html( $title ),
			esc_html( $desc ),
			esc_attr( WDTA_Settings::OPTION ),
			esc_attr( $key ),
			checked( ! empty( $s[ $key ] ), true, false )
		);
	}

	private static function radio_cards( $key, $options ) {
		$s = WDTA_Settings::all();
		echo '<div class="wa-cards">';
		foreach ( $options as $val => $o ) {
			printf(
				'<label class="wa-card"><input type="radio" name="%1$s[%2$s]" value="%3$s" %4$s><span class="wa-card__in"><strong>%5$s</strong><small>%6$s</small></span></label>',
				esc_attr( WDTA_Settings::OPTION ),
				esc_attr( $key ),
				esc_attr( $val ),
				checked( $s[ $key ], $val, false ),
				esc_html( $o[0] ),
				esc_html( $o[1] )
			);
		}
		echo '</div>';
	}

	public static function page() {
		$s     = WDTA_Settings::all();
		$st    = self::checkout_page_state();
		$cache = WDTA_Data::cache_stats();
		$msg   = isset( $_GET['wdta_msg'] ) ? sanitize_key( wp_unslash( $_GET['wdta_msg'] ) ) : ''; // phpcs:ignore
		$opt   = WDTA_Settings::OPTION;
		$icon  = function ( $d ) {
			return '<svg viewBox="0 0 24 24" aria-hidden="true"><path d="' . esc_attr( $d ) . '" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';
		};
		?>
		<div class="wa" data-wa-theme="dark" lang="tr">
			<header class="wa-top">
				<div class="wa-brand">
					<span class="wa-brand__mark" aria-hidden="true">
						<svg viewBox="0 0 32 32"><path d="M16 29s-9-7.8-9-15.2a9 9 0 0 1 18 0C25 21.2 16 29 16 29Z" fill="none" stroke="currentColor" stroke-width="1.8"/><path d="M11.5 14h9M16 9.5v9" stroke="currentColor" stroke-width="1.8" stroke-linecap="round"/></svg>
					</span>
					<div>
						<h1>Türkiye Adres</h1>
						<p>İl · İlçe · Mahalle · Cadde/Sokak · Kapı — v<?php echo esc_html( WDTA_VERSION ); ?></p>
					</div>
				</div>
				<div class="wa-top__actions">
					<span class="wa-status <?php echo $s['enabled'] ? 'is-on' : ''; ?>"><i></i><?php echo $s['enabled'] ? 'Aktif' : 'Kapalı'; ?></span>
					<button type="button" class="wa-theme" data-wa-theme-toggle aria-label="Tema değiştir">
						<?php echo $icon( 'M20 14.5A8 8 0 0 1 9.5 4 8 8 0 1 0 20 14.5Z' ); // phpcs:ignore ?>
					</button>
				</div>
			</header>

			<?php if ( 'cache' === $msg ) : ?><div class="wa-flash">Sokak önbelleği temizlendi.</div><?php endif; ?>
			<?php if ( 'classic' === $msg ) : ?><div class="wa-flash">Ödeme sayfası klasik yapıya dönüştürüldü. Blok içerik yedeklendi.</div><?php endif; ?>
			<?php if ( 'restored' === $msg ) : ?><div class="wa-flash">Blok ödeme sayfası geri yüklendi.</div><?php endif; ?>
			<?php if ( isset( $_GET['settings-updated'] ) ) : // phpcs:ignore ?><div class="wa-flash">Ayarlar kaydedildi.</div><?php endif; ?>

			<?php if ( $st['block'] ) : ?>
			<div class="wa-alert">
				<?php echo $icon( 'M12 8v5M12 16.5v.01M10.3 3.9 2.6 17.2A2 2 0 0 0 4.3 20h15.4a2 2 0 0 0 1.7-2.8L13.7 3.9a2 2 0 0 0-3.4 0Z' ); // phpcs:ignore ?>
				<div><strong>Ödeme sayfanız blok tabanlı.</strong> Zincirleme adres seçimi klasik ödeme sayfasında çalışır. Uyumluluk sekmesinden tek tıkla dönüştürebilirsiniz.</div>
			</div>
			<?php endif; ?>

			<div class="wa-layout">
				<nav class="wa-nav" role="tablist">
					<a href="#genel" role="tab" class="is-active"><?php echo $icon( 'M4 6h16M4 12h16M4 18h10' ); // phpcs:ignore ?>Genel</a>
					<a href="#alanlar" role="tab"><?php echo $icon( 'M4 5h16v4H4zM4 11h7v8H4zM13 11h7v8h-7z' ); // phpcs:ignore ?>Alanlar</a>
					<a href="#gorunum" role="tab"><?php echo $icon( 'M12 3a9 9 0 1 0 0 18c1.2 0 1.8-.9 1.8-1.8 0-1.2-.9-1.6-.9-2.6 0-1 .8-1.6 1.8-1.6H17a4 4 0 0 0 4-4C21 6.6 17 3 12 3ZM7.5 11.5v.01M10 7.5v.01M14.5 7.5v.01' ); // phpcs:ignore ?>Görünüm</a>
					<a href="#veri" role="tab"><?php echo $icon( 'M4 6c0-1.7 3.6-3 8-3s8 1.3 8 3-3.6 3-8 3-8-1.3-8-3ZM4 6v12c0 1.7 3.6 3 8 3s8-1.3 8-3V6M4 12c0 1.7 3.6 3 8 3s8-1.3 8-3' ); // phpcs:ignore ?>Veri</a>
					<a href="#uyumluluk" role="tab"><?php echo $icon( 'M9 12l2 2 4-4M12 3l7 3v6c0 4.5-3 7.8-7 9-4-1.2-7-4.5-7-9V6l7-3Z' ); // phpcs:ignore ?>Uyumluluk</a>
					<div class="wa-nav__foot">
						<a class="wa-credit" href="https://webdanismani.com" target="_blank" rel="noopener">
							<small>Geliştirici</small><strong>Web Danışmanı</strong><span>Özel eklenti ve yazılım için iletişime geçin</span>
						</a>
					</div>
				</nav>

				<main class="wa-main">
					<form method="post" action="options.php" id="wa-form">
						<?php settings_fields( 'wdta_group' ); ?>

						<section class="wa-panel is-active" id="genel" role="tabpanel">
							<div class="wa-panel__head"><h2>Genel</h2><p>Adres seçiminin nerede ve nasıl çalışacağını belirleyin.</p></div>
							<div class="wa-group">
								<?php self::toggle( 'enabled', 'Eklentiyi etkinleştir', 'Kapatırsanız WooCommerce varsayılan adres alanlarına döner.' ); ?>
								<?php self::toggle( 'apply_billing', 'Fatura adresinde kullan', 'Ödeme sayfası ve Hesabım > Fatura adresi.' ); ?>
								<?php self::toggle( 'apply_shipping', 'Teslimat adresinde kullan', '“Farklı adrese gönder” seçildiğinde ve Hesabım > Teslimat adresi.' ); ?>
								<?php self::toggle( 'auto_advance', 'Otomatik ilerleme', 'Seçim yapılınca bir sonraki alan kendiliğinden açılır.' ); ?>
								<?php self::toggle( 'client_validate', 'Anlık doğrulama', 'Eksik alanlar sipariş gönderilmeden işaretlenir. Sunucu doğrulaması her zaman açıktır.' ); ?>
							</div>

							<div class="wa-sub"><h3>Cadde / sokak modu</h3></div>
							<?php
							self::radio_cards( 'sokak_mode', array(
								'list_manual' => array( 'Liste + elle giriş', 'Önerilen. Listede olmayan yeni sokaklar için müşteri elle yazabilir.' ),
								'list_only'   => array( 'Yalnızca liste', 'En katı mod. Veri olmayan mahallelerde elle girişe otomatik izin verilir.' ),
								'manual'      => array( 'Yalnızca elle giriş', 'Sokak listesi yüklenmez; müşteri cadde/sokak adını yazar.' ),
							) );
							?>

							<div class="wa-sub"><h3>Popüler iller</h3><p>İl listesinin en üstünde hızlı seçim olarak gösterilir (plaka kodları, virgülle).</p></div>
							<div class="wa-field">
								<input type="text" name="<?php echo esc_attr( $opt ); ?>[popular_iller]" value="<?php echo esc_attr( $s['popular_iller'] ); ?>" placeholder="34,6,35,16,7">
							</div>
						</section>

						<section class="wa-panel" id="alanlar" role="tabpanel">
							<div class="wa-panel__head"><h2>Alanlar</h2><p>Hangi detayların isteneceğini ve zorunluluk durumunu seçin.</p></div>
							<div class="wa-group">
								<?php self::toggle( 'include_rural', 'Mevki, mezra ve küme evleri listele', 'Kırsal adres birimleri mahalle listesinde ayrı etiketle gösterilir.' ); ?>
								<?php self::toggle( 'kapi_required', 'Kapı numarası zorunlu', 'Kargo firmalarının büyük kısmı kapı numarası ister.' ); ?>
								<?php self::toggle( 'daire_required', 'Daire numarası zorunlu', 'Müstakil evlerde sorun yaratabileceği için varsayılan olarak kapalıdır.' ); ?>
								<?php self::toggle( 'show_bina', 'Bina / site adı alanı', 'Site, rezidans ve blok adları için isteğe bağlı alan.' ); ?>
								<?php self::toggle( 'show_tarif', 'Adres tarifi alanı', 'Kurye için kısa not; açılır-kapanır alan olarak gösterilir.' ); ?>
								<?php self::toggle( 'tarif_to_address', 'Tarifi adres satırına ekle', 'Tarif, kargo etiketinde görünmesi için 2. adres satırına parantez içinde yazılır.' ); ?>
								<?php self::toggle( 'show_preview', 'Canlı gönderi etiketi önizlemesi', 'Müşteri seçim yaptıkça adres, etiket görünümünde oluşur.' ); ?>
							</div>
						</section>

						<section class="wa-panel" id="gorunum" role="tabpanel">
							<div class="wa-panel__head"><h2>Görünüm</h2><p>Bileşen sitenizin yazı tipini devralır; renk ve köşe yapısını buradan eşleştirin.</p></div>

							<div class="wa-sub"><h3>Tema</h3></div>
							<?php
							self::radio_cards( 'theme', array(
								'auto'  => array( 'Siteye uyum', 'Sayfa arka planını okuyup açık/koyu modu kendisi seçer.' ),
								'light' => array( 'Açık', 'Beyaz ve açık tonlu temalar için.' ),
								'dark'  => array( 'Koyu', 'Koyu arka planlı temalar için.' ),
							) );
							?>

							<div class="wa-sub"><h3>Vurgu rengi</h3></div>
							<div class="wa-colors">
								<?php foreach ( array( '#1f6f5c' => 'Zümrüt', '#b8893b' => 'Altın', '#1d4ed8' => 'Lacivert', '#c2410c' => 'Kiremit', '#18181b' => 'Mürekkep', '#7c2d5a' => 'Bordo' ) as $hex => $name ) : ?>
									<button type="button" class="wa-swatch" data-color="<?php echo esc_attr( $hex ); ?>" style="--sw:<?php echo esc_attr( $hex ); ?>" title="<?php echo esc_attr( $name ); ?>"><span></span><?php echo esc_html( $name ); ?></button>
								<?php endforeach; ?>
								<label class="wa-picker">
									<input type="color" name="<?php echo esc_attr( $opt ); ?>[accent]" value="<?php echo esc_attr( $s['accent'] ); ?>" data-wa-accent>
									<code data-wa-accent-code><?php echo esc_html( $s['accent'] ); ?></code>
								</label>
							</div>

							<div class="wa-row2">
								<div>
									<div class="wa-sub"><h3>Köşe yuvarlaklığı</h3></div>
									<div class="wa-range">
										<input type="range" min="0" max="24" name="<?php echo esc_attr( $opt ); ?>[radius]" value="<?php echo esc_attr( $s['radius'] ); ?>" data-wa-radius>
										<output data-wa-radius-out><?php echo (int) $s['radius']; ?>px</output>
									</div>
								</div>
								<div>
									<div class="wa-sub"><h3>Yoğunluk</h3></div>
									<?php
									self::radio_cards( 'density', array(
										'comfortable' => array( 'Ferah', 'Geniş dokunma alanları' ),
										'compact'     => array( 'Sıkı', 'Dar sütunlu temalar için' ),
									) );
									?>
								</div>
							</div>

							<div class="wa-sub"><h3>Önizleme</h3></div>
							<div class="wa-preview" data-wa-preview style="--pv-accent:<?php echo esc_attr( $s['accent'] ); ?>;--pv-radius:<?php echo (int) $s['radius']; ?>px">
								<div class="wa-preview__field"><span>İl</span><div><em>16</em><span>Bursa</span></div></div>
								<div class="wa-preview__field"><span>İlçe</span><div><span>Osmangazi</span></div></div>
								<div class="wa-preview__field is-open"><span>Mahalle / Köy</span><div><span>Alaad<mark>din</mark> Mahallesi</span></div></div>
								<div class="wa-preview__label">
									<div><small>Gönderi etiketi</small><b>Alaaddin Mahallesi, Atatürk Caddesi No: 12 D: 5</b><i>Osmangazi / Bursa</i></div>
									<strong>16020</strong>
								</div>
							</div>
						</section>

						<div class="wa-save" data-wa-save>
							<span>Değişiklikleri kaydetmeyi unutmayın.</span>
							<button type="submit" class="wa-btn wa-btn--primary">Ayarları kaydet</button>
						</div>
					</form>

					<section class="wa-panel" id="veri" role="tabpanel">
						<div class="wa-panel__head"><h2>Veri</h2><p>Adres veritabanı eklentiyle birlikte gelir; harici servis veya API anahtarı gerekmez.</p></div>
						<div class="wa-stats">
							<?php foreach ( array( 'il' => 'İl', 'ilce' => 'İlçe', 'mahalle' => 'Mahalle / köy', 'sokak' => 'Cadde / sokak' ) as $k => $l ) : ?>
								<div class="wa-stat"><strong><?php echo esc_html( number_format_i18n( WDTA_Data::STATS[ $k ] ) ); ?></strong><span><?php echo esc_html( $l ); ?></span></div>
							<?php endforeach; ?>
						</div>
						<div class="wa-kv">
							<div><span>Veri sürümü</span><b><?php echo esc_html( WDTA_Data::STATS['surum'] ); ?></b></div>
							<div><span>Sokak önbelleği</span><b><?php echo esc_html( number_format_i18n( $cache['files'] ) ); ?> dosya · <?php echo esc_html( size_format( $cache['bytes'] ) ); ?></b></div>
							<div><span>Önbellek klasörü</span><b><code><?php echo esc_html( str_replace( ABSPATH, '/', WDTA_Data::cache_dir() ) ); ?></code></b></div>
							<div><span>gzip desteği</span><b><?php echo function_exists( 'gzdecode' ) ? 'Var' : 'Yok — sokak listesi çalışmaz'; ?></b></div>
						</div>
						<p class="wa-note">Sokak listeleri ilçe bazında sıkıştırılmış olarak saklanır ve yalnızca bir müşteri o mahalleyi seçtiğinde açılıp önbelleğe yazılır. Böylece sunucuda gereksiz dosya birikmez.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>">
							<?php wp_nonce_field( 'wdta_action' ); ?>
							<input type="hidden" name="action" value="wdta_action"><input type="hidden" name="do" value="clear_cache">
							<button class="wa-btn">Önbelleği temizle</button>
						</form>
					</section>

					<section class="wa-panel" id="uyumluluk" role="tabpanel">
						<div class="wa-panel__head"><h2>Uyumluluk</h2><p>Ödeme sayfası yapısı ve entegrasyon durumu.</p></div>
						<div class="wa-kv">
							<div><span>Ödeme sayfası</span><b><?php echo $st['id'] > 0 ? '<a href="' . esc_url( get_edit_post_link( $st['id'] ) ) . '">' . esc_html( get_the_title( $st['id'] ) ) . '</a>' : 'Tanımlı değil'; ?></b></div>
							<div><span>Sayfa yapısı</span><b class="<?php echo $st['block'] ? 'is-warn' : 'is-ok'; ?>"><?php echo $st['block'] ? 'Blok (Checkout Block)' : 'Klasik — uyumlu'; ?></b></div>
							<div><span>HPOS</span><b class="is-ok">Uyumlu</b></div>
							<div><span>Hesabım › Adresler</span><b class="is-ok">Destekleniyor</b></div>
						</div>
						<p class="wa-note">Adres satırları WooCommerce'in standart alanlarına da yazılır (il kodu, ilçe, adres 1-2, posta kodu). Bu sayede kargo, e-fatura ve ödeme eklentileri (iyzico, PayTR vb.) ek ayar gerektirmeden çalışır; yapılandırılmış veriler ayrıca sipariş detayında gösterilir.</p>
						<form method="post" action="<?php echo esc_url( admin_url( 'admin-post.php' ) ); ?>" class="wa-inline">
							<?php wp_nonce_field( 'wdta_action' ); ?>
							<input type="hidden" name="action" value="wdta_action">
							<?php if ( $st['block'] ) : ?>
								<input type="hidden" name="do" value="to_classic">
								<button class="wa-btn wa-btn--primary">Klasik ödeme sayfasına dönüştür</button>
							<?php elseif ( $st['backup'] ) : ?>
								<input type="hidden" name="do" value="restore_block">
								<button class="wa-btn">Blok ödeme sayfasını geri yükle</button>
							<?php endif; ?>
						</form>
					</section>
				</main>
			</div>
		</div>
		<?php
	}
}
