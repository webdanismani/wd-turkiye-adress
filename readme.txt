=== WD Türkiye Adres — İl İlçe Mahalle Sokak Seçimi ===
Contributors: oblifex
Tags: woocommerce, türkiye, adres, il ilçe, mahalle
Requires at least: 6.0
Tested up to: 7.0
Requires PHP: 7.4
WC requires at least: 7.0
WC tested up to: 11.1
Stable tag: 1.0.0
License: GPLv3 or later
License URI: https://www.gnu.org/licenses/gpl-3.0.html

WooCommerce ödeme ve hesap sayfalarında il → ilçe → mahalle → cadde/sokak zincirleme seçimi, otomatik posta kodu ve canlı gönderi etiketi önizlemesi.

== Description ==

Türkiye'deki mağazalar için tasarlanmış adres seçim bileşeni. Müşteri adresini serbest metin yerine resmi adres kayıtlarından seçer; eksik veya hatalı adres kaynaklı kargo iadeleri azalır.

**Özellikler**

* 81 il, 970 ilçe, 74.276 mahalle/köy, 1.148.699 cadde/sokak — eklentiyle birlikte gelir, API anahtarı gerekmez
* Aranabilir açılır listeler: Türkçe karakter duyarsız arama (cankaya → Çankaya), eşleşme vurgusu, klavye ile tam kontrol
* Otomatik ilerleme: seçim yapıldıkça bir sonraki alan açılır
* Mahalle bazında otomatik posta kodu
* Listede olmayan yeni sokaklar için elle giriş seçeneği
* Kapı no, daire no, bina/site adı ve kurye için adres tarifi
* Canlı "gönderi etiketi" önizlemesi
* Mobilde alttan açılan tam ekran seçim paneli
* Siteye göre otomatik açık/koyu tema, vurgu rengi ve köşe ayarı
* Ödeme sayfası, farklı teslimat adresi ve Hesabım › Adresler desteği
* Sunucu tarafı doğrulama (il-ilçe-mahalle-sokak tutarlılığı)
* WooCommerce standart adres alanlarını da doldurur; kargo, e-fatura ve ödeme eklentileri (iyzico, PayTR vb.) ek ayar gerektirmez
* Sipariş detayında yapılandırılmış adres kutusu
* HPOS uyumlu
* Blok ödeme sayfasını tek tıkla klasik yapıya dönüştürme (yedekli, geri alınabilir)

**Bilinmesi gerekenler**

* Kapı ve daire numaraları için açık bir veri kaynağı bulunmadığından bu alanlar doğrulamalı metin alanıdır.
* Adres verisi 2021 tarihlidir; sonradan açılan sokaklar için elle giriş seçeneği bulunur.
* Zincirleme seçim klasik ödeme sayfasında (`[woocommerce_checkout]`) çalışır.

== Destek, özellik isteği ve güncellemeler ==

Bu eklenti Oblifex (https://oblifex.com) tarafından ücretsiz sunulur. Güncellemeler GitHub sürümlerinden (https://github.com/oblifex/wd-turkiye-adres) WordPress paneline otomatik gelir; Eklentiler ekranındaki "Güncellemeleri denetle" bağlantısı denetimi hemen yapar.

* Özellik isteği: https://oblifex.com/ozellik-istegi
* Destek: https://oblifex.com/destek
* Özel geliştirme: https://oblifex.com

== Installation ==

1. Eklentiyi Eklentiler › Yeni Ekle › Eklenti Yükle bölümünden ZIP olarak yükleyin ve etkinleştirin.
2. WooCommerce › Türkiye Adres sayfasını açın.
3. Ödeme sayfanız blok tabanlıysa Uyumluluk sekmesinden klasik yapıya dönüştürün.

== Frequently Asked Questions ==

= Sokak listesi sunucumu yorar mı? =

Hayır. Sokaklar ilçe bazında sıkıştırılmış dosyalarda tutulur; yalnızca seçilen mahallenin listesi açılıp önbelleğe (uploads/wd-turkiye-adres) yazılır.

= Sokak listesi gelmiyor =

Sunucunuzda PHP zlib (gzdecode) etkin olmalıdır. Durumu Veri sekmesinden görebilirsiniz.

== Credits ==

* Adres verisi: emreuenal/turkiye-il-ilce-sokak-mahalle-veri-tabani (NVİ Adres Kayıt Sistemi dökümü, 5 Nisan 2021, GPL-3.0)
* Posta kodları: PTT posta kodu listesi (16.11.2021), serhatmorkoc/PTT-il-ilce-semt-mahalle-jsondata

== Changelog ==

= 1.0.0 =
* İlk sürüm.
* GitHub sürümlerinden otomatik güncelleme.
* Oblifex destek ve özellik isteği bağlantıları.
