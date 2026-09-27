<?php
defined( 'ABSPATH' ) || exit;

class WDTA_Address {

	const SUBKEYS = array( 'il', 'ilce', 'mahalle', 'mahalle_ad', 'sokak', 'sokak_ad', 'kapi', 'daire', 'bina', 'tarif' );

	private static $errors = array();

	public static function init() {
		if ( WDTA_Settings::get( 'apply_billing' ) ) {
			add_filter( 'woocommerce_billing_fields', array( __CLASS__, 'billing_fields' ), 20, 2 );
		}
		if ( WDTA_Settings::get( 'apply_shipping' ) ) {
			add_filter( 'woocommerce_shipping_fields', array( __CLASS__, 'shipping_fields' ), 20, 2 );
		}

		add_filter( 'woocommerce_form_field_wdta_address', array( __CLASS__, 'render_panel' ), 10, 4 );
		add_filter( 'woocommerce_form_field_wdta_part', '__return_empty_string', 10 );

		add_filter( 'woocommerce_checkout_posted_data', array( __CLASS__, 'checkout_posted_data' ), 20 );
		add_action( 'woocommerce_after_checkout_validation', array( __CLASS__, 'checkout_validation' ), 20, 2 );

		add_action( 'template_redirect', array( __CLASS__, 'prepare_edit_address' ), 5 );

		add_action( 'wp_enqueue_scripts', array( __CLASS__, 'assets' ) );

		add_action( 'woocommerce_admin_order_data_after_billing_address', function ( $order ) {
			self::admin_order_box( $order, 'billing' );
		} );
		add_action( 'woocommerce_admin_order_data_after_shipping_address', function ( $order ) {
			self::admin_order_box( $order, 'shipping' );
		} );
	}

	/* ------------------------------------------------------------------ */
	/* Alan tanımları                                                      */
	/* ------------------------------------------------------------------ */

	public static function billing_fields( $fields ) {
		return self::inject( $fields, 'billing' );
	}

	public static function shipping_fields( $fields ) {
		return self::inject( $fields, 'shipping' );
	}

	private static function inject( $fields, $group ) {
		$priority = isset( $fields[ $group . '_country' ]['priority'] ) ? (int) $fields[ $group . '_country' ]['priority'] + 5 : 45;

		foreach ( self::SUBKEYS as $i => $sub ) {
			$fields[ $group . '_wdta_' . $sub ] = array(
				'type'     => 'il' === $sub ? 'wdta_address' : 'wdta_part',
				'label'    => '',
				'required' => false,
				'priority' => $priority + ( $i / 100 ),
				'class'    => array( 'wdta-hidden-part' ),
			);
		}
		return $fields;
	}

	private static function value( $key ) {
		if ( isset( $_POST[ $key ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
			return wc_clean( wp_unslash( $_POST[ $key ] ) ); // phpcs:ignore
		}
		if ( function_exists( 'is_checkout' ) && is_checkout() && WC()->checkout() ) {
			$v = WC()->checkout()->get_value( $key );
			return is_scalar( $v ) ? (string) $v : '';
		}
		if ( is_user_logged_in() ) {
			return (string) get_user_meta( get_current_user_id(), $key, true );
		}
		return '';
	}

	/* ------------------------------------------------------------------ */
	/* Panel HTML                                                          */
	/* ------------------------------------------------------------------ */

	public static function render_panel( $html, $key, $args, $value ) {
		$group = strpos( $key, 'shipping_' ) === 0 ? 'shipping' : 'billing';
		$s     = WDTA_Settings::all();
		$v     = array();
		foreach ( self::SUBKEYS as $sub ) {
			$v[ $sub ] = self::value( $group . '_wdta_' . $sub );
		}

		$p      = $group . '_wdta_';
		$req    = '<abbr class="wdta-req" title="zorunlu">*</abbr>';
		$opt    = '<span class="wdta-opt">isteğe bağlı</span>';
		$title  = 'billing' === $group ? 'Fatura adresi' : 'Teslimat adresi';
		$chev   = '<svg class="wdta-cb__chev" viewBox="0 0 20 20" aria-hidden="true"><path d="M5.5 7.5 10 12l4.5-4.5" fill="none" stroke="currentColor" stroke-width="1.6" stroke-linecap="round" stroke-linejoin="round"/></svg>';

		$combo = function ( $level, $label, $placeholder, $wide ) use ( $p, $v, $req, $chev ) {
			$id = $p . $level . '_q';
			return sprintf(
				'<div class="wdta-f%1$s" data-level="%2$s">
					<label class="wdta-f__label" for="%3$s">%4$s %5$s</label>
					<div class="wdta-cb" data-state="idle">
						<span class="wdta-cb__idx" aria-hidden="true"></span>
						<input type="text" id="%3$s" class="wdta-cb__input" placeholder="%6$s" autocomplete="off" autocapitalize="off" spellcheck="false" role="combobox" aria-autocomplete="list" aria-expanded="false" aria-controls="%3$s_list">
						<span class="wdta-cb__spin" aria-hidden="true"></span>%7$s
						<input type="hidden" name="%8$s" value="%9$s" data-role="value">
					</div>
					<p class="wdta-f__hint" aria-live="polite"></p>
				</div>',
				$wide ? ' wdta-f--wide' : '',
				esc_attr( $level ),
				esc_attr( $id ),
				esc_html( $label ),
				$req,
				esc_attr( $placeholder ),
				$chev,
				esc_attr( $p . $level ),
				esc_attr( $v[ $level ] )
			);
		};

		ob_start();
		?>
		<div class="form-row form-row-wide wdta-row" id="<?php echo esc_attr( $key ); ?>_field" data-priority="<?php echo esc_attr( $args['priority'] ?? 45 ); ?>">
			<div class="wdta" lang="tr" data-wdta data-group="<?php echo esc_attr( $group ); ?>" data-density="<?php echo esc_attr( $s['density'] ); ?>" data-theme-pref="<?php echo esc_attr( $s['theme'] ); ?>">
				<div class="wdta__head">
					<div class="wdta__title">
						<svg class="wdta__pin" viewBox="0 0 24 24" aria-hidden="true"><path d="M12 21s-6.5-5.6-6.5-11a6.5 6.5 0 0 1 13 0c0 5.4-6.5 11-6.5 11Z" fill="none" stroke="currentColor" stroke-width="1.5"/><circle cx="12" cy="10" r="2.3" fill="currentColor"/></svg>
						<span><?php echo esc_html( $title ); ?></span>
					</div>
					<ol class="wdta__trail" aria-label="Adres adımları">
						<li data-step="il">İl</li><li data-step="ilce">İlçe</li><li data-step="mahalle">Mahalle</li><li data-step="sokak">Sokak</li><li data-step="kapi">No</li>
					</ol>
				</div>

				<div class="wdta__grid">
					<?php
					echo $combo( 'il', 'İl', 'İl arayın veya seçin', false ); // phpcs:ignore
					echo $combo( 'ilce', 'İlçe', 'Önce il seçin', false ); // phpcs:ignore
					echo $combo( 'mahalle', 'Mahalle / Köy', 'Önce ilçe seçin', true ); // phpcs:ignore
					echo $combo( 'sokak', 'Cadde / Sokak', 'Önce mahalle seçin', true ); // phpcs:ignore
					?>
					<input type="hidden" name="<?php echo esc_attr( $p . 'mahalle_ad' ); ?>" value="<?php echo esc_attr( $v['mahalle_ad'] ); ?>" data-role="mahalle_ad">
					<input type="hidden" name="<?php echo esc_attr( $p . 'sokak_ad' ); ?>" value="<?php echo esc_attr( $v['sokak_ad'] ); ?>" data-role="sokak_ad">

					<div class="wdta-f wdta-f--third" data-level="kapi">
						<label class="wdta-f__label" for="<?php echo esc_attr( $p . 'kapi' ); ?>">Bina / Kapı No <?php echo $s['kapi_required'] ? $req : $opt; // phpcs:ignore ?></label>
						<div class="wdta-in">
							<span class="wdta-in__pre" aria-hidden="true">No</span>
							<input type="text" id="<?php echo esc_attr( $p . 'kapi' ); ?>" name="<?php echo esc_attr( $p . 'kapi' ); ?>" value="<?php echo esc_attr( $v['kapi'] ); ?>" maxlength="12" inputmode="text" autocomplete="off" placeholder="12/A" data-role="kapi">
						</div>
						<p class="wdta-f__hint"></p>
					</div>
					<div class="wdta-f wdta-f--third" data-level="daire">
						<label class="wdta-f__label" for="<?php echo esc_attr( $p . 'daire' ); ?>">Daire No <?php echo $s['daire_required'] ? $req : $opt; // phpcs:ignore ?></label>
						<div class="wdta-in">
							<span class="wdta-in__pre" aria-hidden="true">D</span>
							<input type="text" id="<?php echo esc_attr( $p . 'daire' ); ?>" name="<?php echo esc_attr( $p . 'daire' ); ?>" value="<?php echo esc_attr( $v['daire'] ); ?>" maxlength="12" inputmode="numeric" autocomplete="off" placeholder="5" data-role="daire">
						</div>
						<p class="wdta-f__hint"></p>
					</div>
					<?php if ( $s['show_bina'] ) : ?>
					<div class="wdta-f wdta-f--third" data-level="bina">
						<label class="wdta-f__label" for="<?php echo esc_attr( $p . 'bina' ); ?>">Bina / Site Adı <?php echo $opt; // phpcs:ignore ?></label>
						<div class="wdta-in">
							<input type="text" id="<?php echo esc_attr( $p . 'bina' ); ?>" name="<?php echo esc_attr( $p . 'bina' ); ?>" value="<?php echo esc_attr( $v['bina'] ); ?>" maxlength="80" autocomplete="off" placeholder="Güneş Sitesi B Blok" data-role="bina">
						</div>
					</div>
					<?php endif; ?>
					<?php if ( $s['show_tarif'] ) : ?>
					<div class="wdta-f wdta-f--wide wdta-f--tarif" data-level="tarif">
						<button type="button" class="wdta-toggle" aria-expanded="<?php echo $v['tarif'] ? 'true' : 'false'; ?>" data-role="tarif-toggle">
							<svg viewBox="0 0 20 20" aria-hidden="true"><path d="M10 4.5v11M4.5 10h11" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/></svg>
							Kurye için adres tarifi ekle
						</button>
						<div class="wdta-in wdta-in--area" <?php echo $v['tarif'] ? '' : 'hidden'; ?>>
							<textarea id="<?php echo esc_attr( $p . 'tarif' ); ?>" name="<?php echo esc_attr( $p . 'tarif' ); ?>" rows="2" maxlength="250" placeholder="Örn. Eczanenin yanındaki mavi kapı, zil çalışmıyor" data-role="tarif"><?php echo esc_textarea( $v['tarif'] ); ?></textarea>
							<span class="wdta-in__count" aria-hidden="true">0/250</span>
						</div>
					</div>
					<?php endif; ?>
				</div>

				<?php if ( $s['show_preview'] ) : ?>
				<div class="wdta-label" data-role="preview" data-complete="false" aria-live="polite">
					<div class="wdta-label__body">
						<div class="wdta-label__top">
							<span class="wdta-label__tag">Gönderi etiketi</span>
							<span class="wdta-label__stamp">
								<svg viewBox="0 0 20 20" aria-hidden="true"><path d="m5 10.5 3.2 3L15 6.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/></svg>
								Adres tamam
							</span>
						</div>
						<div class="wdta-label__name" data-role="pv-name">&nbsp;</div>
						<div class="wdta-label__line" data-role="pv-line1"><span class="wdta-label__ph">Mahalle, cadde/sokak ve kapı numarası</span></div>
						<div class="wdta-label__line wdta-label__line--muted" data-role="pv-line2"></div>
						<div class="wdta-label__city" data-role="pv-city"><span class="wdta-label__ph">İlçe / İl</span></div>
					</div>
					<div class="wdta-label__side">
						<span class="wdta-label__pkcap">Posta kodu</span>
						<span class="wdta-label__pk" data-role="pv-pk">·····</span>
						<svg class="wdta-label__bars" data-role="pv-bars" viewBox="0 0 120 34" preserveAspectRatio="none" aria-hidden="true"></svg>
					</div>
				</div>
				<?php endif; ?>
			</div>
		</div>
		<?php
		return ob_get_clean();
	}

	/* ------------------------------------------------------------------ */
	/* Doğrulama + çekirdek alanları üretme                                */
	/* ------------------------------------------------------------------ */

	private static function clean_text( $v, $max ) {
		$v = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( (string) $v ) ) );
		return mb_substr( $v, 0, $max, 'UTF-8' );
	}

	/**
	 * @return array{errors: array, core: array, meta: array}
	 */
	public static function compose( $group, $data ) {
		$s      = WDTA_Settings::all();
		$p      = $group . '_wdta_';
		$label  = 'billing' === $group ? 'Fatura adresi' : 'Teslimat adresi';
		$errors = array();
		$meta   = array();
		$get    = function ( $sub ) use ( $data, $p ) {
			return isset( $data[ $p . $sub ] ) ? (string) $data[ $p . $sub ] : '';
		};

		$il      = WDTA_Data::il( $get( 'il' ) );
		$ilce    = $il ? WDTA_Data::ilce( $il['id'], $get( 'ilce' ) ) : null;
		$mahalle = $ilce ? WDTA_Data::mahalle( $ilce['id'], $get( 'mahalle' ) ) : null;

		if ( ! $il ) {
			$errors[] = array( $p . 'il_q', sprintf( '%s: lütfen <strong>il</strong> seçin.', $label ) );
		} elseif ( ! $ilce ) {
			$errors[] = array( $p . 'ilce_q', sprintf( '%s: lütfen <strong>ilçe</strong> seçin.', $label ) );
		} elseif ( ! $mahalle ) {
			$errors[] = array( $p . 'mahalle_q', sprintf( '%s: lütfen <strong>mahalle / köy</strong> seçin.', $label ) );
		}

		$sokak_label = '';
		if ( $mahalle ) {
			$mode      = $s['sokak_mode'];
			$has_list  = 'manual' !== $mode && ! empty( WDTA_Data::sokaklar( $ilce['id'], $mahalle['id'] ) );
			$sokak_id  = absint( $get( 'sokak' ) );
			$manual    = self::clean_text( $get( 'sokak_ad' ), 120 );

			if ( $has_list && $sokak_id ) {
				$sokak = WDTA_Data::sokak( $ilce['id'], $mahalle['id'], $sokak_id );
				if ( $sokak ) {
					$sokak_label   = $sokak['ad'];
					$meta['sokak'] = $sokak_id;
				}
			}

			if ( '' === $sokak_label ) {
				$manual_allowed = ! $has_list || 'list_manual' === $mode;
				if ( $manual_allowed && mb_strlen( $manual, 'UTF-8' ) >= 2 ) {
					$sokak_label   = $manual;
					$meta['sokak'] = '';
				} else {
					$errors[] = array( $p . 'sokak_q', sprintf( '%s: lütfen <strong>cadde / sokak</strong> seçin.', $label ) );
				}
			}
		}

		$kapi  = self::clean_text( preg_replace( '/[^0-9A-Za-zÇĞİÖŞÜçğıöşü\/\-\s]/u', '', $get( 'kapi' ) ), 12 );
		$daire = self::clean_text( preg_replace( '/[^0-9A-Za-zÇĞİÖŞÜçğıöşü\/\-\s]/u', '', $get( 'daire' ) ), 12 );
		$bina  = $s['show_bina'] ? self::clean_text( $get( 'bina' ), 80 ) : '';
		$tarif = $s['show_tarif'] ? self::clean_text( $get( 'tarif' ), 250 ) : '';

		if ( $s['kapi_required'] && '' === $kapi ) {
			$errors[] = array( $p . 'kapi', sprintf( '%s: lütfen <strong>bina / kapı numarası</strong> girin.', $label ) );
		}
		if ( $s['daire_required'] && '' === $daire ) {
			$errors[] = array( $p . 'daire', sprintf( '%s: lütfen <strong>daire numarası</strong> girin.', $label ) );
		}

		$core = array();
		if ( ! $errors ) {
			$line1 = $mahalle['ad'] . ', ' . $sokak_label;
			if ( '' !== $kapi ) {
				$line1 .= ' No: ' . $kapi;
			}
			if ( '' !== $daire ) {
				$line1 .= ' D: ' . $daire;
			}

			$line2 = array();
			if ( '' !== $bina ) {
				$line2[] = $bina;
			}
			if ( '' !== $tarif && $s['tarif_to_address'] ) {
				$line2[] = '(' . $tarif . ')';
			}

			$core = array(
				'state'     => WDTA_Data::state_code( $il['id'] ),
				'city'      => $ilce['ad'],
				'address_1' => $line1,
				'address_2' => implode( ' ', $line2 ),
				'postcode'  => $mahalle['pk'] ? $mahalle['pk'] : $ilce['pk'],
			);

			$meta = array_merge( $meta, array(
				'il'         => $il['id'],
				'ilce'       => $ilce['id'],
				'mahalle'    => $mahalle['id'],
				'mahalle_ad' => $mahalle['ad'],
				'sokak_ad'   => $sokak_label,
				'kapi'       => $kapi,
				'daire'      => $daire,
				'bina'       => $bina,
				'tarif'      => $tarif,
			) );
		}

		return array( 'errors' => $errors, 'core' => $core, 'meta' => $meta );
	}

	private static function placeholder_core() {
		return array( 'state' => 'TR34', 'city' => '-', 'address_1' => '-', 'postcode' => '00000' );
	}

	private static function apply_to( array &$target, $group, array $result ) {
		$p = $group . '_';
		if ( $result['errors'] ) {
			foreach ( self::placeholder_core() as $k => $val ) {
				if ( empty( $target[ $p . $k ] ) ) {
					$target[ $p . $k ] = $val;
				}
			}
			return;
		}
		foreach ( $result['core'] as $k => $val ) {
			$target[ $p . $k ] = $val;
		}
		foreach ( $result['meta'] as $k => $val ) {
			$target[ $p . 'wdta_' . $k ] = $val;
		}
	}

	private static function strip_parts( array &$target, $group ) {
		foreach ( self::SUBKEYS as $sub ) {
			unset( $target[ $group . '_wdta_' . $sub ] );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Ödeme sayfası                                                       */
	/* ------------------------------------------------------------------ */

	public static function checkout_posted_data( $data ) {
		self::$errors = array();
		$s            = WDTA_Settings::all();

		if ( $s['apply_billing'] ) {
			if ( 'TR' === ( $data['billing_country'] ?? '' ) ) {
				$r = self::compose( 'billing', $data );
				self::apply_to( $data, 'billing', $r );
				self::$errors = array_merge( self::$errors, $r['errors'] );
			} else {
				self::strip_parts( $data, 'billing' );
			}
		}

		if ( $s['apply_shipping'] ) {
			$different = ! empty( $data['ship_to_different_address'] ) && WC()->cart && WC()->cart->needs_shipping_address();

			if ( $different && 'TR' === ( $data['shipping_country'] ?? '' ) ) {
				$r = self::compose( 'shipping', $data );
				self::apply_to( $data, 'shipping', $r );
				self::$errors = array_merge( self::$errors, $r['errors'] );
			} elseif ( ! $different && $s['apply_billing'] && 'TR' === ( $data['billing_country'] ?? '' ) ) {
				foreach ( self::SUBKEYS as $sub ) {
					$data[ 'shipping_wdta_' . $sub ] = $data[ 'billing_wdta_' . $sub ] ?? '';
				}
			} else {
				self::strip_parts( $data, 'shipping' );
			}
		}

		return $data;
	}

	public static function checkout_validation( $data, $errors ) {
		foreach ( self::$errors as $e ) {
			$errors->add( 'validation', $e[1], array( 'id' => $e[0] ) );
		}
	}

	/* ------------------------------------------------------------------ */
	/* Hesabım > Adres düzenle                                             */
	/* ------------------------------------------------------------------ */

	public static function prepare_edit_address() {
		if ( 'POST' !== ( $_SERVER['REQUEST_METHOD'] ?? '' ) || empty( $_POST['action'] ) || 'edit_address' !== $_POST['action'] ) { // phpcs:ignore
			return;
		}
		$nonce = isset( $_REQUEST['woocommerce-edit-address-nonce'] ) ? sanitize_key( wp_unslash( $_REQUEST['woocommerce-edit-address-nonce'] ) ) : '';
		if ( ! wp_verify_nonce( $nonce, 'woocommerce-edit_address' ) || ! is_user_logged_in() ) {
			return;
		}

		global $wp;
		if ( empty( $wp->query_vars['edit-address'] ) ) {
			return;
		}
		$group = wc_edit_address_i18n( sanitize_title( $wp->query_vars['edit-address'] ), true );
		if ( ! in_array( $group, array( 'billing', 'shipping' ), true ) || ! WDTA_Settings::get( 'apply_' . $group ) ) {
			return;
		}

		$posted = wc_clean( wp_unslash( $_POST ) ); // phpcs:ignore
		if ( 'TR' !== ( $posted[ $group . '_country' ] ?? '' ) ) {
			return;
		}

		$r = self::compose( $group, $posted );
		self::apply_to( $posted, $group, $r );

		foreach ( $r['errors'] as $e ) {
			wc_add_notice( $e[1], 'error', array( 'id' => $e[0] ) );
		}

		$keys = array( 'state', 'city', 'address_1', 'address_2', 'postcode' );
		foreach ( $keys as $k ) {
			if ( isset( $posted[ $group . '_' . $k ] ) ) {
				$_POST[ $group . '_' . $k ] = $posted[ $group . '_' . $k ];
			}
		}
		foreach ( self::SUBKEYS as $sub ) {
			if ( isset( $posted[ $group . '_wdta_' . $sub ] ) ) {
				$_POST[ $group . '_wdta_' . $sub ] = (string) $posted[ $group . '_wdta_' . $sub ];
			}
		}
	}

	/* ------------------------------------------------------------------ */
	/* Varlıklar                                                           */
	/* ------------------------------------------------------------------ */

	private static function should_load() {
		if ( function_exists( 'is_checkout' ) && is_checkout() && ! is_wc_endpoint_url( 'order-received' ) && ! is_wc_endpoint_url( 'order-pay' ) ) {
			return true;
		}
		return function_exists( 'is_wc_endpoint_url' ) && is_wc_endpoint_url( 'edit-address' );
	}

	public static function assets() {
		if ( ! self::should_load() ) {
			return;
		}
		$s = WDTA_Settings::all();

		wp_enqueue_style( 'wdta', WDTA_URL . 'assets/css/wdta.css', array(), WDTA_VERSION );
		wp_add_inline_style( 'wdta', sprintf(
			'.wdta{--wdta-accent:%1$s;--wdta-radius:%2$dpx}',
			esc_attr( $s['accent'] ),
			(int) $s['radius']
		) );

		wp_enqueue_script( 'wdta', WDTA_URL . 'assets/js/wdta.js', array( 'jquery' ), WDTA_VERSION, true );

		$tr = WDTA_Data::tr();
		wp_localize_script( 'wdta', 'WDTA_CFG', array(
			'iller'      => $tr['iller'],
			'ilceler'    => $tr['ilceler'],
			'mahalleUrl' => WDTA_URL . 'data/mahalle/',
			'restUrl'    => esc_url_raw( rest_url( WDTA_Rest::NS . '/' ) ),
			'ver'        => WDTA_VERSION,
			'sokakMode'  => $s['sokak_mode'],
			'rural'      => (int) $s['include_rural'],
			'kapiReq'    => (int) $s['kapi_required'],
			'daireReq'   => (int) $s['daire_required'],
			'auto'       => (int) $s['auto_advance'],
			'validate'   => (int) $s['client_validate'],
			'popular'    => array_map( 'intval', array_filter( explode( ',', $s['popular_iller'] ) ) ),
		) );
	}

	/* ------------------------------------------------------------------ */
	/* Yönetici sipariş ekranı                                             */
	/* ------------------------------------------------------------------ */

	public static function admin_order_box( $order, $group ) {
		if ( ! $order instanceof WC_Order ) {
			return;
		}
		$get = function ( $sub ) use ( $order, $group ) {
			return (string) $order->get_meta( '_' . $group . '_wdta_' . $sub );
		};
		if ( '' === $get( 'mahalle' ) ) {
			return;
		}
		$il   = WDTA_Data::il( $get( 'il' ) );
		$ilce = $il ? WDTA_Data::ilce( $il['id'], $get( 'ilce' ) ) : null;

		$rows = array(
			'İl'          => $il ? $il['ad'] : '',
			'İlçe'        => $ilce ? $ilce['ad'] : '',
			'Mahalle'     => $get( 'mahalle_ad' ),
			'Cadde/Sokak' => $get( 'sokak_ad' ) . ( '' === $get( 'sokak' ) ? ' (elle girildi)' : '' ),
			'Kapı No'     => $get( 'kapi' ),
			'Daire'       => $get( 'daire' ),
			'Bina/Site'   => $get( 'bina' ),
			'Tarif'       => $get( 'tarif' ),
		);

		echo '<div class="wdta-admin-box" style="margin-top:12px;padding:10px 12px;border:1px solid #dcdcde;border-left:3px solid #1f6f5c;border-radius:6px;background:#fbfbfa">';
		echo '<p style="margin:0 0 6px;font-weight:600">Yapılandırılmış adres</p><table style="width:100%;border-collapse:collapse;font-size:12px">';
		foreach ( $rows as $k => $val ) {
			if ( '' === trim( $val ) ) {
				continue;
			}
			printf( '<tr><td style="padding:2px 8px 2px 0;color:#646970;white-space:nowrap;vertical-align:top">%s</td><td style="padding:2px 0">%s</td></tr>', esc_html( $k ), esc_html( $val ) );
		}
		echo '</table></div>';
	}
}
