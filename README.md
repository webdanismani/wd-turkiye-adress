<p align="center">
  <a href="https://oblifex.com/?utm_source=github&utm_medium=readme-hero&utm_campaign=wd-turkiye-adres">
    <img src=".github/assets/banner.svg" alt="WD Türkiye Adres — İl, ilçe, mahalle, cadde/sokak zincirleme adres seçimi" width="100%">
  </a>
</p>

<p align="center">
  <a href="https://github.com/oblifex/wd-turkiye-adres/releases/latest"><img alt="Sürüm" src="https://img.shields.io/github/v/release/oblifex/wd-turkiye-adres?label=s%C3%BCr%C3%BCm&color=c9a45c&labelColor=0e0f11"></a>
  <a href="https://github.com/oblifex/wd-turkiye-adres/releases"><img alt="İndirme" src="https://img.shields.io/github/downloads/oblifex/wd-turkiye-adres/total?label=indirme&color=c9a45c&labelColor=0e0f11"></a>
  <img alt="WordPress 6.0+" src="https://img.shields.io/badge/WordPress-6.0%2B-0e0f11?logo=wordpress&logoColor=c9a45c">
  <img alt="WooCommerce 7.0+" src="https://img.shields.io/badge/WooCommerce-7.0%2B%20%C2%B7%20HPOS-0e0f11?logo=woocommerce&logoColor=c9a45c">
  <img alt="PHP 7.4+" src="https://img.shields.io/badge/PHP-7.4%2B-0e0f11?logo=php&logoColor=c9a45c">
  <a href="LICENSE"><img alt="GPL-3.0" src="https://img.shields.io/badge/lisans-GPL--3.0-0e0f11"></a>
  <a href="https://github.com/oblifex/wd-turkiye-adres/actions/workflows/denetim.yml"><img alt="Denetim" src="https://img.shields.io/github/actions/workflow/status/oblifex/wd-turkiye-adres/denetim.yml?label=denetim&labelColor=0e0f11"></a>
</p>

<p align="center">
  <b>WooCommerce ödeme ve hesap sayfalarında il → ilçe → mahalle → cadde/sokak zincirleme adres seçimi.</b><br>
  Otomatik posta kodu, canlı gönderi etiketi önizlemesi, mobil seçim paneli. Ücretsiz ve açık kaynak.
</p>

<p align="center">
  <a href="https://github.com/oblifex/wd-turkiye-adres/releases/latest/download/wd-turkiye-adres.zip"><b>⬇️ Son sürümü indir (ZIP)</b></a>
  &nbsp;·&nbsp;
  <a href="#kurulum">Kurulum</a>
  &nbsp;·&nbsp;
  <a href="#özellikler">Özellikler</a>
  &nbsp;·&nbsp;
  <a href="#sss">SSS</a>
  &nbsp;·&nbsp;
  <a href="https://oblifex.com/?utm_source=github&utm_medium=readme-nav&utm_campaign=wd-turkiye-adres"><b>oblifex.com</b></a>
</p>

<br>

<a href="https://oblifex.com/?utm_source=github&utm_medium=readme-cta&utm_campaign=wd-turkiye-adres">
  <img src=".github/assets/oblifex.svg" alt="Özellik isteği, destek ve güncellemeler için oblifex.com" width="100%">
</a>

> [!TIP]
> **Bu eklenti [Oblifex](https://oblifex.com/?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-turkiye-adres) tarafından ücretsiz sunulur.**
> Eklenti WordPress'e kurulduğunda yeni sürümleri **kendisi bulur**: GitHub'da yayınlanan her sürüm, WordPress › Eklentiler ekranında normal bir güncelleme gibi görünür ve tek tıkla kurulur.
>
> | | |
> |---|---|
> | ✨ **Özellik isteği** | Eklentide görmek istediğiniz her şeyi [oblifex.com/ozellik-istegi](https://oblifex.com/ozellik-istegi?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-turkiye-adres) üzerinden iletin. Öncelikli olarak değerlendirilir. |
> | 🛟 **Destek** | Kurulum, tema ve uyumluluk soruları: [oblifex.com/destek](https://oblifex.com/destek?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-turkiye-adres) |
> | 🔄 **Güncellemeler** | Otomatik. Elle denetlemek için Eklentiler ekranındaki **Güncellemeleri denetle** bağlantısını kullanın. |
> | 🧩 **Özel geliştirme** | Mağazanıza özel WordPress / WooCommerce çözümleri için [oblifex.com](https://oblifex.com/?utm_source=github&utm_medium=readme-tip&utm_campaign=wd-turkiye-adres) |

<br>

## Neden?

Türkiye'de serbest metin adres alanı, kargo iadelerinin ve "adres bulunamadı" aramalarının başlıca kaynağıdır. Bu eklenti müşteriye adresini **resmi adres kayıtlarından seçtirir**: 81 il, 970 ilçe, 74.276 mahalle/köy ve 1.148.699 cadde/sokak eklentiyle birlikte gelir. Harici servis, API anahtarı veya aylık ücret yoktur.

## Özellikler

- **Zincirleme seçim:** il → ilçe → mahalle → cadde/sokak; seçim yapıldıkça bir sonraki alan açılır
- **Aranabilir listeler:** Türkçe karakter duyarsız arama (`cankaya` → Çankaya), eşleşme vurgusu, tam klavye desteği
- **Otomatik posta kodu:** mahalle bazında
- **Listede olmayan sokaklar** için elle giriş seçeneği
- **Kapı no, daire no, bina/site adı, kurye tarifi** alanları (her biri ayarlardan açılıp kapatılabilir)
- **Canlı gönderi etiketi** önizlemesi
- **Mobilde** alttan açılan tam ekran seçim paneli
- **Tema uyumu:** otomatik açık/koyu, vurgu rengi ve köşe yuvarlaklığı ayarı
- Ödeme sayfası, farklı teslimat adresi ve **Hesabım › Adresler**
- **Sunucu tarafı doğrulama:** il-ilçe-mahalle-sokak tutarlılığı
- **WooCommerce standart alanlarını da doldurur** (`state` `TR16`, `city`, `address_1/2`, `postcode`) → kargo, e-fatura ve ödeme eklentileri (iyzico, PayTR vb.) ek ayar gerektirmez
- Sipariş detayında **yapılandırılmış adres kutusu**
- **HPOS** uyumlu
- Blok ödeme sayfasını **tek tıkla klasik yapıya dönüştürme** (yedekli, geri alınabilir)

## Kurulum

**Seçenek 1 — ZIP (önerilen)**

1. [Son sürümü indirin](https://github.com/oblifex/wd-turkiye-adres/releases/latest/download/wd-turkiye-adres.zip).
2. WordPress › Eklentiler › Yeni Ekle › **Eklenti Yükle** ile ZIP'i yükleyip etkinleştirin.
3. **WooCommerce › Türkiye Adres** sayfasını açın.
4. Ödeme sayfanız blok tabanlıysa **Uyumluluk** sekmesinden klasik yapıya dönüştürün.

**Seçenek 2 — git**

```bash
cd wp-content/plugins
git clone https://github.com/oblifex/wd-turkiye-adres.git
```

> Güncellemeler her iki kurulumda da WordPress panelinden gelir. Deponun `main` dalı geliştirme sürümüdür; kararlı sürümler için [Releases](https://github.com/oblifex/wd-turkiye-adres/releases) sayfasına bakın.

## Gereksinimler

| | En az |
|---|---|
| WordPress | 6.0 |
| WooCommerce | 7.0 (HPOS destekli) |
| PHP | 7.4, `zlib` etkin (`gzdecode`) |
| Ödeme sayfası | Klasik `[woocommerce_checkout]` — blok sayfa tek tıkla dönüştürülür |

## Ayarlar

**WooCommerce › Türkiye Adres** altında beş sekme:

| Sekme | İçerik |
|---|---|
| Genel | Etkinleştirme, fatura/teslimat adresinde kullanım, otomatik ilerleme, anlık doğrulama, sokak modu |
| Alanlar | Kırsal birimler, kapı/daire zorunluluğu, bina ve tarif alanları, canlı etiket önizlemesi |
| Görünüm | Açık/koyu/otomatik tema, vurgu rengi, köşe, yoğunluk |
| Veri | Veri seti bilgisi, sokak önbelleği durumu ve temizleme |
| Uyumluluk | Ödeme sayfası yapısı, HPOS, blok → klasik dönüştürme |

## Sipariş meta anahtarları

Kargo ve muhasebe entegrasyonları için yapılandırılmış veriler siparişte saklanır:

```
_billing_wdta_il          _billing_wdta_ilce        _billing_wdta_mahalle
_billing_wdta_mahalle_ad  _billing_wdta_sokak       _billing_wdta_sokak_ad
_billing_wdta_kapi        _billing_wdta_daire       _billing_wdta_bina
_billing_wdta_tarif
```

Teslimat adresi için `_shipping_` önekli eşdeğerleri vardır. `_*_wdta_sokak` boşsa sokak adı müşteri tarafından elle girilmiştir.

## REST

```
GET /wp-json/wdta/v1/mahalle/{ilce_id}
GET /wp-json/wdta/v1/sokak/{ilce_id}/{mahalle_id}
```

## SSS

<details>
<summary><b>Sokak listesi sunucumu yorar mı?</b></summary>

Hayır. Sokaklar ilçe bazında sıkıştırılmış dosyalarda tutulur; yalnızca seçilen mahallenin listesi açılıp `uploads/wd-turkiye-adres` altına önbelleğe yazılır.
</details>

<details>
<summary><b>Sokak listesi gelmiyor.</b></summary>

Sunucunuzda PHP `zlib` (`gzdecode`) etkin olmalıdır. Durumu **Veri** sekmesinden görebilirsiniz.
</details>

<details>
<summary><b>Kapı ve daire numaraları neden listeden seçilmiyor?</b></summary>

Kapı/daire için açık bir veri kaynağı bulunmadığından bu alanlar doğrulamalı metin alanıdır.
</details>

<details>
<summary><b>Yeni açılan sokaklar listede yok.</b></summary>

Adres verisi 2021 tarihlidir; listede olmayan sokaklar için elle giriş seçeneği bulunur. Daha güncel bir veri kaynağı bildirmek için [özellik isteği](https://oblifex.com/ozellik-istegi?utm_source=github&utm_medium=readme-faq&utm_campaign=wd-turkiye-adres) açabilirsiniz.
</details>

<details>
<summary><b>Güncellemeler nasıl geliyor?</b></summary>

Eklenti, `Update URI` başlığı sayesinde bu deponun **Releases** sayfasını 12 saatte bir denetler ve yeni sürümü WordPress'in standart güncelleme akışına ekler. Eklentiler ekranındaki **Güncellemeleri denetle** bağlantısı denetimi hemen yapar. wordpress.org ile hiçbir bağlantısı yoktur.
</details>

## Veri kaynakları

- [emreuenal/turkiye-il-ilce-sokak-mahalle-veri-tabani](https://github.com/emreuenal/turkiye-il-ilce-sokak-mahalle-veri-tabani) — NVİ Adres Kayıt Sistemi dökümü, Nisan 2021, GPL-3.0
- [serhatmorkoc/PTT-il-ilce-semt-mahalle-jsondata](https://github.com/serhatmorkoc/PTT-il-ilce-semt-mahalle-jsondata) — PTT posta kodları, Kasım 2021

## Katkı

Hata bildirimi ve PR'lar için [CONTRIBUTING.md](CONTRIBUTING.md). Güvenlik açıkları için [SECURITY.md](SECURITY.md). Değişiklikler: [CHANGELOG.md](CHANGELOG.md).

## Lisans

[GPL-3.0-or-later](LICENSE) © [Oblifex](https://oblifex.com/?utm_source=github&utm_medium=readme-license&utm_campaign=wd-turkiye-adres)

---

<p align="center">
  <a href="https://oblifex.com/?utm_source=github&utm_medium=readme-footer&utm_campaign=wd-turkiye-adres">
    <img alt="oblifex.com — destek & özellik isteği" src="https://img.shields.io/badge/oblifex.com-destek%20%26%20%C3%B6zellik%20iste%C4%9Fi-c9a45c?style=for-the-badge&labelColor=0e0f11">
  </a>
  <br><br>
  <sub>WordPress &amp; WooCommerce için ücretsiz eklentiler, özel geliştirme ve destek — <b>oblifex.com</b></sub>
</p>
