<p align="center"><a href="https://cecom.com/"><img src="https://cecom.com/logo.png" alt="cecom.com"></a></p>

<p align="center">
<img src="https://img.shields.io/github/v/release/ozkanugur/smarttr-address?label=stable" alt="Latest release">
<img src="https://img.shields.io/wordpress/plugin/v/smarttr-address" alt="WordPress.org version">
<img src="https://img.shields.io/wordpress/plugin/installs/smarttr-address" alt="Active installs">
<img src="https://img.shields.io/github/license/cecom/smarttr-address" alt="License">
</p>

Welcome to the SmartTR Address repository on GitHub. Here you can browse the source, look at open issues, and keep track of development.

If you are not a developer, please use the [SmartTR Address plugin page](https://wordpress.org/plugins/smarttr-address/) on WordPress.org.

---

SmartTR Address GitHub deposuna hoş geldiniz. Burada kaynak koduna göz atabilir, açık sorunlara bakabilir ve geliştirme sürecini takip edebilirsiniz.

Geliştirici değilseniz lütfen WordPress.org'daki [SmartTR Address eklenti sayfasını](https://wordpress.org/plugins/smarttr-address/) kullanın.

---

## About plugin / Eklenti Hakkında

**SmartTR Address** replaces the generic WooCommerce address fields with accurate, data-driven cascading dropdowns for all 81 Turkish provinces and ~970 districts. When a customer selects Turkey as their country, the standard text inputs are instantly replaced with intelligent, linked selects that guide them through their Province and District selection.

The plugin integrates with the **Classic Checkout** (`[woocommerce_checkout]` shortcode).

[Free version >](https://cecom.in/smarttr-address-turkish-address)
[Documentation >](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill)

### Basic features

* **Province + District cascade dropdowns** — cascading dropdowns for all 81 Turkish provinces and ~970 districts
* **Classic Checkout** — integrates with the WooCommerce shortcode checkout (`[woocommerce_checkout]`)
* **Background data sync** — address data is fetched from a remote API after activation; no bloated ZIP files bundled in the plugin
* **GDPR-compliant** — built-in privacy exporter and eraser for all Turkish address data
* **HPOS-compatible** — fully supports WooCommerce High-Performance Order Storage
* **Accessible** — ARIA live regions, keyboard navigation, noscript fallback
* **Internationalized** — Turkish translation included; fully translatable via standard `.pot` file

---

**SmartTR Address**, genel WooCommerce adres alanlarını, Türkiye'nin 81 ili ve ~970 ilçesine ait doğru, veri odaklı kademeli açılır menülerle değiştirir. Bir müşteri ülke olarak Türkiye'yi seçtiğinde, standart metin girişleri anında akıllı, bağlantılı açılır menülerle değiştirilerek müşteriye il ve ilçe seçimi sırasında rehberlik eder.

Eklenti, **Klasik Ödeme** (`[woocommerce_checkout]` kısayolu) ile entegre çalışır.

[Ücretsiz sürüm >](https://cecom.in/smarttr-address-turkish-address)
[Belgeler >](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill)

### Temel özellikler

* **İl + İlçe kademeli açılır menüler** — Türkiye'nin 81 ili ve ~970 ilçesi için kademeli açılır menüler
* **Klasik Ödeme** — WooCommerce kısayol ödeme (`[woocommerce_checkout]`) ile entegre olur
* **Arka plan veri senkronizasyonu** — Adres verileri aktivasyondan sonra uzak bir API'den alınır; eklentiye şişirilmiş ZIP dosyaları eklenmez
* **GDPR uyumlu** — Tüm Türk adres verileri için yerleşik gizlilik dışa aktarıcı ve silici
* **HPOS uyumlu** — WooCommerce Yüksek Performanslı Sipariş Depolama ile tam uyumluluk
* **Erişilebilir** — ARIA canlı bölgeler, klavye navigasyonu, noscript yedekleme
* **Uluslararasılaştırılmış** — Türkçe çeviri dahildir; standart `.pot` dosyası üzerinden tamamen çevrilebilir

---

## Getting started / Başlarken

* [Prerequisites / Ön Koşullar](#prerequisites--ön-koşullar)
* [Installation guide / Kurulum Kılavuzu](#installation-guide--kurulum-kılavuzu)
* [Configuration / Yapılandırma](#configuration--yapılandırma)
* [Verifying the plugin works / Eklentinin Çalıştığını Doğrulama](#verifying-the-plugin-works--eklentinin-çalıştığını-doğrulama)
* [Available Languages / Mevcut Diller](#available-languages--mevcut-diller)
* [Documentation / Belgeler](#documentation--belgeler)
* [FAQ](#faq)
* [Changelog / Sürüm Geçmişi](#changelog--sürüm-geçmişi)
* [Support / Destek](#support--destek)
* [Reporting Security Issues / Güvenlik Açığı Bildirme](#reporting-security-issues--güvenlik-açığı-bildirme)

## Prerequisites / Ön Koşullar

> **WooCommerce must be installed and active before you activate SmartTR Address.**
> If WooCommerce is not active when you click Activate, activation will fail with a clear error message.

> **SmartTR Address'i etkinleştirmeden önce WooCommerce kurulu ve etkin olmalıdır.**
> WooCommerce etkin değilken Etkinleştir'e tıklarsanız, etkinleştirme açık bir hata mesajıyla başarısız olur.

| Requirement / Gereksinim | Minimum version / Minimum sürüm |
|--------------------------|--------------------------------|
| WordPress | 6.4 |
| WooCommerce | 7.0 |
| PHP | 8.1 |

## Installation guide / Kurulum Kılavuzu

**Step 1** — Install and activate WooCommerce (7.0 or higher) if you haven't already.

**Step 2** — Install SmartTR Address using one of the methods below.

#### Via WordPress admin (recommended)

```
Plugins → Add New → Search "SmartTR Address" → Install Now → Activate
```

#### Manual upload

1. Download the plugin ZIP from the WordPress Plugin Directory.
2. Go to **Plugins → Add New → Upload Plugin**.
3. Select the ZIP file and click **Install Now**, then **Activate Plugin**.

#### Via Git (for developers)

Clone the repository directly into your `wp-content/plugins/` directory:

```bash
git clone https://github.com/cecom/smarttr-address.git wp-content/plugins/smarttr-address
```

Then activate SmartTR Address from the Plugins page.

---

**1. Adım** — Henüz yapmadıysanız WooCommerce'i (7.0 veya üstü) kurun ve etkinleştirin.

**2. Adım** — Aşağıdaki yöntemlerden birini kullanarak SmartTR Address'i kurun.

#### WordPress yöneticisi aracılığıyla (önerilen)

```
Eklentiler → Yeni Ekle → "SmartTR Address" ara → Şimdi Yükle → Etkinleştir
```

#### Manuel yükleme

1. Eklenti ZIP dosyasını WordPress Eklenti Dizini'nden indirin.
2. **Eklentiler → Yeni Ekle → Eklenti Yükle**'ye gidin.
3. ZIP dosyasını seçin ve **Şimdi Yükle**'ye, ardından **Eklentiyi Etkinleştir**'e tıklayın.

#### Git aracılığıyla (geliştiriciler için)

Depoyu doğrudan `wp-content/plugins/` dizininize klonlayın:

```bash
git clone https://github.com/cecom/smarttr-address.git wp-content/plugins/smarttr-address
```

Ardından SmartTR Address'i Eklentiler sayfasından etkinleştirin.

## Configuration / Yapılandırma

After activating the plugin:

1. Go to **WooCommerce → SmartTR Address**
2. On the **General** tab, make sure **Enable Plugin** is checked and click **Save Changes**
3. Switch to the **Data** tab — you will see the import status. Wait for address data to finish importing (usually takes less than a minute)
4. Once the Province and District record counts are greater than zero, the plugin is fully operational

---

Eklentiyi etkinleştirdikten sonra:

1. **WooCommerce → SmartTR Address**'e gidin
2. **Genel** sekmesinde **Eklentiyi Etkinleştir** seçeneğinin işaretli olduğundan emin olun ve **Değişiklikleri Kaydet**'e tıklayın
3. **Veri** sekmesine geçin — içe aktarma durumunu göreceksiniz. Adres verilerinin içe aktarılmasını bekleyin (genellikle bir dakikadan az sürer)
4. İl ve İlçe kayıt sayıları sıfırdan büyük gösterildiğinde eklenti tamamen hazırdır

## Verifying the plugin works / Eklentinin Çalıştığını Doğrulama

1. Open your WooCommerce checkout page (find the URL at **WooCommerce → Settings → Advanced → Checkout page**)
2. Select **Turkey (TR)** as the billing country
3. Confirm that:
   - A **Province** dropdown appears in place of the standard State/County text field
   - Selecting a province immediately populates the **District** dropdown
   - The checkout can be completed with the selected Province and District values

If the cascade does not appear, visit the **Data** tab and check that record counts are above zero. If they show 0, click **Reimport Data**.

---

1. WooCommerce ödeme sayfanızı açın (**WooCommerce → Ayarlar → Gelişmiş → Ödeme sayfası** üzerinden URL'yi bulabilirsiniz)
2. Fatura ülkesi olarak **Türkiye (TR)**'yi seçin
3. Şunları doğrulayın:
   - Standart Eyalet/İlçe metin alanı yerine bir **İl** açılır menüsünün göründüğünü
   - Bir il seçildiğinde **İlçe** açılır menüsünün hemen dolduğunu
   - Ödemenin seçilen İl ve İlçe değerleriyle tamamlanabildiğini

Kademeleme görünmüyorsa **Veri** sekmesini ziyaret edin ve kayıt sayılarının sıfırın üzerinde olduğunu kontrol edin. 0 gösteriyorsa **Veriyi Yeniden İçe Aktar**'a tıklayın.

## Available Languages / Mevcut Diller

* English — United Kingdom (Default / Varsayılan)
* Turkish — Turkey (`tr_TR`, bundled / paketlenmiş)

## Documentation / Belgeler

You can find the official documentation of the plugin [here](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill).

Eklentinin resmi belgelerine [buradan](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill) ulaşabilirsiniz.

## FAQ

**Does this plugin require WooCommerce?**

Yes. WooCommerce 7.0 or higher must be installed and active before you activate SmartTR Address.

**Bu eklenti WooCommerce gerektiriyor mu?**

Evet. Bu eklentiyi etkinleştirmeden önce WooCommerce 7.0 veya üstü kurulu ve etkin olmalıdır.

---

**What checkout type does this support?**

SmartTR Address integrates with the WooCommerce **Classic Checkout** — the page that uses the `[woocommerce_checkout]` shortcode.

**Bu eklenti hangi ödeme türünü destekliyor?**

SmartTR Address, `[woocommerce_checkout]` kısayolunu kullanan sayfa olan WooCommerce **Klasik Ödeme** ile entegre olur.

---

**Will this slow down my checkout page?**

No. Province and district data is embedded directly in the page (no extra requests). The cascade adds negligible overhead.

**Bu eklenti ödeme sayfamı yavaşlatır mı?**

Hayır. İl ve ilçe verileri doğrudan sayfaya gömülüdür (ekstra istek gerekmez). Kademeleme ihmal edilebilir düzeyde yük getirir.

---

**What happens if a customer does not select Turkey?**

The plugin does not interfere with any non-Turkey order. Standard WooCommerce fields remain completely unchanged for all other countries.

**Müşteri Türkiye'yi seçmezse ne olur?**

Eklenti, Türkiye dışındaki hiçbir siparişe müdahale etmez. Diğer tüm ülkeler için standart WooCommerce alanları tamamen değişmeden kalır.

---

**Is this compatible with WooCommerce HPOS?**

Yes. The plugin is declared compatible with WooCommerce High-Performance Order Storage (HPOS).

**WooCommerce HPOS ile uyumlu mu?**

Evet. Eklenti, WooCommerce Yüksek Performanslı Sipariş Depolama (HPOS) ile uyumlu olarak beyan edilmiştir.

---

**Does uninstalling remove all data?**

Yes. Deleting the plugin removes all custom tables, plugin options, and cached data. Deactivating does not remove any data.

**Eklentinin kaldırılması tüm verileri siler mi?**

Evet. Eklentiyi silmek tüm özel tabloları, eklenti seçeneklerini ve önbelleğe alınmış verileri kaldırır. Devre dışı bırakmak hiçbir veriyi kaldırmaz.

---

**Is this GDPR-compliant?**

Yes. The plugin registers a personal data exporter and eraser with WordPress's privacy tools.

**GDPR uyumlu mu?**

Evet. Eklenti, WordPress'in gizlilik araçlarına bir kişisel veri dışa aktarıcı ve silici kaydeder.

## Changelog / Sürüm Geçmişi

### 1.3.2 - Released on 10 April 2026

* Update: Front-end fixes.
* Update: Minor bugs.
* Güncelleme: Ön yüz düzeltmeleri.
* Güncelleme: Küçük hatalar.

### 1.3.1 - Released on 01 April 2026

* Update: Translation corrections — regenerated POT from source, completed all missing Turkish translations
* Güncelleme: Çeviri düzeltmeleri — POT kaynaktan yeniden oluşturuldu, eksik Türkçe çeviriler tamamlandı

### 1.3.0 - Released on 01 January 2026

* Tweak: Internal improvements and stability fixes
* Dev: Activation error now uses HTTP 500 so the standard WordPress error banner is shown when requirements are not met
* İyileştirme: İç iyileştirmeler ve kararlılık düzeltmeleri
* Geliştirici: Aktivasyon hatası artık HTTP 500 kullanıyor; böylece gereksinimler karşılanmadığında standart WordPress hata başlığı gösteriliyor

[View full changelog / Sürüm geçmişinin tamamını görüntüle](https://wordpress.org/plugins/smarttr-address/#developers)

## Developer Reference

### Directory structure

```
smarttr-address/
├── smarttr-address.php            # Plugin bootstrap, constants, hook registration
├── uninstall.php                  # Full data cleanup on plugin deletion
│
├── includes/
│   ├── class-cecomsmarad-activator.php       # Activation: requirements check, tables, sync
│   ├── class-cecomsmarad-deactivator.php     # Deactivation: transient/schedule cleanup
│   ├── class-cecomsmarad-autoloader.php      # PSR-0 autoloader for Cecomsmarad_* classes
│   ├── class-cecomsmarad-i18n.php            # Internationalization
│   ├── class-cecomsmarad-privacy.php         # Privacy policy suggestion + exporter/eraser
│   │
│   ├── models/
│   │   ├── class-cecomsmarad-province.php         # Province DB queries
│   │   ├── class-cecomsmarad-district.php         # District DB queries
│   │   ├── class-cecomsmarad-data-importer.php    # Schema creation + data import
│   │   └── class-cecomsmarad-remote-sync.php      # Remote API sync client
│   │
│   ├── controllers/
│   │   ├── class-cecomsmarad-admin-controller.php      # Admin page + AJAX handlers
│   │   ├── class-cecomsmarad-checkout-controller.php   # Classic checkout integration
│   │   └── class-cecomsmarad-order-controller.php      # Validation + meta storage
│   │
│   └── views/
│       ├── admin/
│       │   └── settings.php               # Admin settings page template
│       └── checkout/
│           └── index.php
│
├── assets/
│   ├── css/
│   │   ├── cecomsmarad-admin.css / .min.css
│   │   └── cecomsmarad-checkout.css / .min.css
│   └── js/
│       ├── cecomsmarad-admin.js / .min.js
│       └── cecomsmarad-checkout.js / .min.js
│
├── languages/
│   ├── cecomsmarad-address.pot
│   ├── cecomsmarad-address-tr_TR.po
│   └── cecomsmarad-address-tr_TR.mo
│
└── tests/
    ├── unit/          # PHPUnit unit tests (no WordPress/WooCommerce install required)
    └── integration/   # Integration tests (require real WP + WC environment)
```

### Database schema

Two custom tables created on activation via `dbDelta()`:

```sql
wp_cecomsmarad_provinces
  id    BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  code  VARCHAR(4)   UNIQUE   -- e.g. 'TR34'
  name  VARCHAR(100)          -- e.g. 'İstanbul'

wp_cecomsmarad_districts
  id            BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY
  province_code VARCHAR(4)           -- references provinces.code
  name          VARCHAR(100)
```

### WordPress options

| Option key | Description |
|-----------|-------------|
| `cecomsmarad_enabled` | Plugin enabled flag (`'0'` or `'1'`) |
| `cecomsmarad_last_import` | Timestamp of last successful data import |
| `cecomsmarad_record_counts` | JSON: row counts per table |
| `cecomsmarad_data_version` | Remote data version string |
| `cecomsmarad_sync_needed` | `'1'` when a sync is queued |

### Admin AJAX endpoints

Require `manage_woocommerce` capability.

| Action | Description |
|--------|-------------|
| `cecomsmarad_save_general` | Save general settings |
| `cecomsmarad_reset_fields` | Reset field customizations to defaults |
| `cecomsmarad_reimport_data` | Trigger manual address data sync |
| `cecomsmarad_submit_deactivation_feedback` | Submit deactivation reason |

### Running tests

```bash
composer install
./vendor/bin/phpunit --testsuite unit
```

### Code standards

```bash
./vendor/bin/phpcs   # Check
./vendor/bin/phpcbf  # Auto-fix
```

## Support / Destek

This repository is a development tool. For end-user support, please post on the [WordPress.org support forum](https://wordpress.org/support/plugin/smarttr-address/).

Bu depo bir geliştirme aracıdır. Son kullanıcı desteği için lütfen [WordPress.org destek forumuna](https://wordpress.org/support/plugin/smarttr-address/) yazın.

## Reporting Security Issues / Güvenlik Açığı Bildirme

To disclose a security issue to our team, please contact us via our [security contact form](https://cecom.com/contact/).

Ekibimize bir güvenlik açığı bildirmek için lütfen [güvenlik iletişim formumuzu](https://cecom.com/contact/) kullanarak bizimle iletişime geçin.
