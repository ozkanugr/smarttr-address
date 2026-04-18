=== SmartTR Address ===

Contributors: cecom
Tags: Checkout Manager, Checkout Address Suggession, Custom Fields, WooCommerce Checkout, Checkout Address Autocomplete
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.4.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turkish address auto-fill for WooCommerce checkout — cascading Province and District dropdowns for Turkish orders.

== Description ==

**SmartTR Address** replaces the generic WooCommerce address fields with accurate, data-driven cascading dropdowns for all 81 Turkish provinces and ~970 districts. When a customer selects Turkey as their country, the standard text inputs are instantly replaced with intelligent, linked selects that guide them through their Province and District selection.

The plugin integrates with the **Classic Checkout** (`[woocommerce_checkout]` shortcode).

[Plugin page >](https://cecom.in/smarttr-address-turkish-address)
[Documentation >](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill)

= Basic features =

* **Province + District cascade dropdowns** — cascading dropdowns for all 81 Turkish provinces and ~970 districts
* **Classic Checkout** — integrates with the WooCommerce shortcode checkout (`[woocommerce_checkout]`)
* **Background data sync** — address data is fetched from a remote API after activation; no bloated ZIP files bundled in the plugin
* **GDPR-compliant** — built-in privacy exporter and eraser for all Turkish address data
* **HPOS-compatible** — fully supports WooCommerce High-Performance Order Storage
* **Accessible** — ARIA live regions, keyboard navigation, noscript fallback
* **Internationalized** — Turkish translation included; fully translatable via standard `.pot` file

= Türkçe =

**SmartTR Address**, genel WooCommerce adres alanlarını, Türkiye'nin 81 ili ve ~970 ilçesine ait doğru, veri odaklı kademeli açılır menülerle değiştirir. Bir müşteri ülke olarak Türkiye'yi seçtiğinde, standart metin girişleri anında akıllı, bağlantılı açılır menülerle değiştirilerek müşteriye il ve ilçe seçimi sırasında rehberlik eder.

Eklenti, **Klasik Ödeme** (`[woocommerce_checkout]` kısayolu) ile entegre çalışır.

[Eklenti sayfası >](https://cecom.in/smarttr-address-turkish-address)
[Belgeler >](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill)

= Temel özellikler =

* **İl + İlçe kademeli açılır menüler** — Türkiye'nin 81 ili ve ~970 ilçesi için kademeli açılır menüler
* **Klasik Ödeme** — WooCommerce kısayol ödeme (`[woocommerce_checkout]`) ile entegre olur
* **Arka plan veri senkronizasyonu** — Adres verileri aktivasyondan sonra uzak bir API'den alınır; eklentiye şişirilmiş ZIP dosyaları eklenmez
* **GDPR uyumlu** — Tüm Türk adres verileri için yerleşik gizlilik dışa aktarıcı ve silici
* **HPOS uyumlu** — WooCommerce Yüksek Performanslı Sipariş Depolama ile tam uyumluluk
* **Erişilebilir** — ARIA canlı bölgeler, klavye navigasyonu, noscript yedekleme
* **Uluslararasılaştırılmış** — Türkçe çeviri dahildir; standart `.pot` dosyası üzerinden tamamen çevrilebilir

== Installation ==

= Prerequisites =

**WooCommerce must be installed and active before you activate SmartTR Address.** If WooCommerce is not active when you click Activate, activation will fail with an error message.

1. Install and activate **WooCommerce 7.0 or higher** first
2. Then install and activate **SmartTR Address**

= Ön Koşullar =

**SmartTR Address'i etkinleştirmeden önce WooCommerce kurulu ve etkin olmalıdır.** WooCommerce etkin değilken Etkinleştir'e tıklarsanız, etkinleştirme hata mesajıyla başarısız olur.

1. Önce **WooCommerce 7.0 veya üstünü** kurun ve etkinleştirin
2. Ardından **SmartTR Address**'i kurun ve etkinleştirin

= Automatic Installation (Recommended) =

1. Make sure **WooCommerce 7.0 or higher** is already installed and active
2. Log in to your WordPress admin panel and go to **Plugins > Add New**
3. Search for **SmartTR Address**
4. Click **Install Now**, then **Activate**
5. Navigate to **CECOM > SmartTR Address** to configure the plugin
6. Address data will be imported automatically in the background — this usually takes less than a minute

= Otomatik Kurulum (Önerilen) =

1. **WooCommerce 7.0 veya üstünün** zaten kurulu ve etkin olduğundan emin olun
2. WordPress yönetici panelinize giriş yapın ve **Eklentiler > Yeni Ekle**'ye gidin
3. **SmartTR Address** arayın
4. **Şimdi Yükle**'ye tıklayın, ardından **Etkinleştir**'e tıklayın
5. Eklentiyi yapılandırmak için **CECOM > SmartTR Address**'e gidin
6. Adres verileri arka planda otomatik olarak içe aktarılacaktır — bu genellikle bir dakikadan az sürer

= Manual Installation =

1. Make sure **WooCommerce 7.0 or higher** is already installed and active
2. Download the plugin ZIP from the WordPress Plugin Directory
3. Go to **Plugins > Add New > Upload Plugin**
4. Upload the ZIP file and click **Install Now**
5. Click **Activate Plugin**
6. Navigate to **CECOM > SmartTR Address** to configure

= Manuel Kurulum =

1. **WooCommerce 7.0 veya üstünün** zaten kurulu ve etkin olduğundan emin olun
2. Eklentiyi WordPress Eklenti Dizini'nden indirin
3. **Eklentiler > Yeni Ekle > Eklenti Yükle**'ye gidin
4. ZIP dosyasını yükleyin ve **Şimdi Yükle**'ye tıklayın
5. **Eklentiyi Etkinleştir**'e tıklayın
6. Eklentiyi yapılandırmak için **CECOM > SmartTR Address**'e gidin

= Configuration =

After activating the plugin, follow these steps to verify everything is working:

1. Go to **CECOM > SmartTR Address**
2. On the **General** tab, make sure **Enable Plugin** is checked and click **Save Changes**
3. Switch to the **Data** tab — you will see the import status. Wait for the address data to finish importing (usually takes less than a minute after activation)
4. Once the record counts show numbers greater than 0 for Provinces and Districts, the plugin is ready
5. Visit your checkout page (you can use **WooCommerce > Settings > Advanced > Checkout page** to find the URL), select **Turkey (TR)** as the billing country, and confirm that:
   - A **Province** dropdown appears in place of the standard State/County field
   - Selecting a province immediately populates the **District** dropdown
   - The checkout can be completed with the selected Province and District values

= Yapılandırma =

Eklentiyi etkinleştirdikten sonra her şeyin düzgün çalıştığını doğrulamak için şu adımları izleyin:

1. **CECOM > SmartTR Address**'e gidin
2. **Genel** sekmesinde **Eklentiyi Etkinleştir** seçeneğinin işaretli olduğundan emin olun ve **Değişiklikleri Kaydet**'e tıklayın
3. **Veri** sekmesine geçin — içe aktarma durumunu göreceksiniz. Adres verilerinin içe aktarılmasını bekleyin (genellikle aktivasyondan sonra bir dakikadan az sürer)
4. İller ve İlçeler için kayıt sayıları sıfırdan büyük gösterildiğinde eklenti hazırdır
5. Ödeme sayfanızı ziyaret edin (**WooCommerce > Ayarlar > Gelişmiş > Ödeme sayfası** üzerinden URL'yi bulabilirsiniz), fatura ülkesi olarak **Türkiye (TR)**'yi seçin ve şunları doğrulayın:
   - Standart Eyalet/İl alanı yerine bir **İl** açılır menüsünün göründüğünü
   - Bir il seçildiğinde **İlçe** açılır menüsünün hemen dolduğunu
   - Ödemenin seçilen İl ve İlçe değerleriyle tamamlanabildiğini

= Troubleshooting =

* **Cascade does not appear:** Confirm the plugin is enabled on the General tab. Check the Data tab to ensure at least one province and district record has been imported. If counts show 0, click **Reimport Data**.
* **Reimport Data is greyed out:** A cooldown of 30 days applies between manual syncs to prevent excessive API requests. If you need to sync immediately (e.g. right after a fresh install), deactivate and reactivate the plugin to trigger the background sync again.
* **Activation error — WooCommerce not found:** Install and activate WooCommerce 7.0 or higher first, then activate SmartTR Address.

= Sorun Giderme =

* **Kademeli menü görünmüyor:** Eklentinin Genel sekmesinde etkin olduğunu doğrulayın. En az bir il ve ilçe kaydının içe aktarıldığından emin olmak için Veri sekmesini kontrol edin. Sayılar 0 gösteriyorsa **Veriyi Yeniden İçe Aktar**'a tıklayın.
* **Veriyi Yeniden İçe Aktar grileşmiş durumda:** Aşırı API isteklerini önlemek için elle senkronizasyonlar arasında 30 günlük bekleme süresi uygulanır. Hemen senkronizasyon yapmanız gerekiyorsa (örneğin, yeni bir kurulumun hemen ardından) arka plan senkronizasyonunu yeniden tetiklemek için eklentiyi devre dışı bırakıp tekrar etkinleştirin.
* **Aktivasyon hatası — WooCommerce bulunamadı:** Önce WooCommerce 7.0 veya üstünü kurun ve etkinleştirin, ardından SmartTR Address'i etkinleştirin.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

**Does this plugin require WooCommerce?**

Yes. SmartTR Address is a WooCommerce extension. WooCommerce 7.0 or higher must be installed and active before you activate this plugin. Attempting to activate without WooCommerce will show an error and the plugin will not be activated.

**Bu eklenti WooCommerce gerektiriyor mu?**

Evet. SmartTR Address bir WooCommerce uzantısıdır. Bu eklentiyi etkinleştirmeden önce WooCommerce 7.0 veya üstü kurulu ve etkin olmalıdır. WooCommerce olmadan etkinleştirmeye çalışmak hata mesajı gösterecek ve eklenti etkinleştirilmeyecektir.


= How do I verify the plugin is working after installation? =

**How do I verify the plugin is working after installation?**

Go to **CECOM > SmartTR Address > Data** and confirm that the Province and District record counts are greater than zero. Then open your checkout page, select Turkey as the billing country, and a Province dropdown should appear. Selecting a province will load the matching District dropdown instantly.

**Kurulumdan sonra eklentinin çalıştığını nasıl doğrularım?**

**CECOM > SmartTR Address > Veri**'ye gidin ve İl ile İlçe kayıt sayılarının sıfırdan büyük olduğunu doğrulayın. Ardından ödeme sayfanızı açın, fatura ülkesi olarak Türkiye'yi seçin; bir İl açılır menüsü görünmelidir. Bir il seçmek, ilgili İlçe açılır menüsünü anında yükleyecektir.


= How is the address data kept up to date? =

**How is the address data kept up to date?**

Address data is fetched from a secure remote API hosted at cecom.in. You can manually trigger a sync at any time from the **Data** tab. A 30-day cooldown applies between manual syncs.

**Adres verileri nasıl güncel tutulur?**

Adres verileri, cecom.in adresinde barındırılan güvenli bir uzak API'den alınır. **Veri** sekmesinden istediğiniz zaman elle senkronizasyon tetikleyebilirsiniz. Elle senkronizasyonlar arasında 30 günlük bekleme süresi uygulanır.


= What checkout type does this support? =

**What checkout type does this support?**

SmartTR Address integrates with the WooCommerce **Classic Checkout** block — the page that uses the `[woocommerce_checkout]` shortcode. The cascade activates automatically whenever a customer selects Turkey (TR) as their billing or shipping country.

**Bu eklenti hangi ödeme türünü destekliyor?**

SmartTR Address, `[woocommerce_checkout]` kısayolunu kullanan sayfa olan WooCommerce **Klasik Ödeme** ile entegre olur. Kademeleme, bir müşteri fatura veya kargo ülkesi olarak Türkiye (TR)'yi seçtiğinde otomatik olarak etkinleşir.


= Will this slow down my checkout page? =

**Will this slow down my checkout page?**

No. Province and district data is embedded directly in the page (no extra requests). The entire cascade adds negligible overhead to checkout performance.

**Bu eklenti ödeme sayfamı yavaşlatır mı?**

Hayır. İl ve ilçe verileri doğrudan sayfaya gömülüdür (ekstra istek gerekmez). Kademelemenin tamamı ödeme performansına ihmal edilebilir düzeyde yük getirir.


= What happens if a customer does not select Turkey? =

**What happens if a customer does not select Turkey?**

The plugin does not interfere with any non-Turkey order. Standard WooCommerce fields remain completely unchanged for all other countries.

**Müşteri Türkiye'yi seçmezse ne olur?**

Eklenti, Türkiye dışındaki hiçbir siparişe müdahale etmez. Diğer tüm ülkeler için standart WooCommerce alanları tamamen değişmeden kalır.


= Does this work with my theme? =

**Does this work with my theme?**

SmartTR Address hooks into WooCommerce's standard field rendering pipeline. It is compatible with any WooCommerce-compatible theme, including Storefront, Flatsome, Astra, OceanWP, and page builders like Elementor and Divi.

**Temamla uyumlu mu?**

SmartTR Address, WooCommerce'in standart alan oluşturma sürecine bağlanır. Storefront, Flatsome, Astra, OceanWP ve Elementor ile Divi gibi sayfa oluşturucular dahil olmak üzere WooCommerce uyumlu herhangi bir tema ile uyumludur.


= Is this compatible with WooCommerce HPOS? =

**Is this compatible with WooCommerce HPOS?**

Yes. The plugin is declared compatible with WooCommerce High-Performance Order Storage (HPOS) and uses the correct APIs to read and write order meta.

**WooCommerce HPOS ile uyumlu mu?**

Evet. Eklenti, WooCommerce Yüksek Performanslı Sipariş Depolama (HPOS) ile uyumlu olarak beyan edilmiştir ve sipariş meta verilerini okumak ve yazmak için doğru API'leri kullanır.


= What data is stored in the database? =

**What data is stored in the database?**

The plugin creates two custom tables: `wp_cecomsmarad_provinces` and `wp_cecomsmarad_districts`. These contain only public geographic data (no personal information). Customer address selections are stored as standard WooCommerce order meta.

**Veritabanında hangi veriler saklanır?**

Eklenti iki özel tablo oluşturur: `wp_cecomsmarad_provinces` ve `wp_cecomsmarad_districts`. Bunlar yalnızca genel coğrafi verileri içerir (kişisel bilgi içermez). Müşteri adres seçimleri, standart WooCommerce sipariş meta verisi olarak saklanır.


= Does uninstalling the plugin remove all data? =

**Does uninstalling the plugin remove all data?**

Yes. Deleting the plugin (not just deactivating it) removes all custom tables, all plugin options, and all cached data. Deactivating the plugin does not remove any data.

**Eklentinin kaldırılması tüm verileri siler mi?**

Evet. Eklentiyi silmek (yalnızca devre dışı bırakmak değil) tüm özel tabloları, tüm eklenti seçeneklerini ve önbelleğe alınmış tüm verileri kaldırır. Eklentiyi devre dışı bırakmak hiçbir veriyi kaldırmaz.


= Is this GDPR-compliant? =

**Is this GDPR-compliant?**

Yes. The plugin registers a personal data exporter and eraser with WordPress's privacy tools. All Turkish address data (province, district) stored on orders is included in privacy exports and can be erased on request.

**GDPR uyumlu mu?**

Evet. Eklenti, WordPress'in gizlilik araçlarına bir kişisel veri dışa aktarıcı ve silici kaydeder. Siparişlerde saklanan tüm Türk adres verileri (il, ilçe) gizlilik dışa aktarmalarına dahil edilir ve talep üzerine silinebilir.


= Can I translate the plugin? =

**Can I translate the plugin?**

Yes. All user-facing strings are translatable. A `.pot` template file is included in the `languages/` folder. A complete Turkish (`tr_TR`) translation is bundled. You can use Loco Translate or any standard WordPress translation workflow to add other languages.

**Eklentiyi çevirebilir miyim?**

Evet. Kullanıcıya yönelik tüm metinler çevrilebilir. `languages/` klasöründe bir `.pot` şablon dosyası bulunmaktadır. Tam bir Türkçe (`tr_TR`) çeviri paketlenmiştir. Başka diller eklemek için Loco Translate veya herhangi bir standart WordPress çeviri iş akışını kullanabilirsiniz.


= How can I report security bugs? =

**How can I report security bugs?**

You can report security bugs through the CECOM security contact form. [Report a security vulnerability.](https://cecom.in/contact)

**Güvenlik açıklarını nasıl bildirebilirim?**

Güvenlik açıklarını CECOM güvenlik iletişim formu üzerinden bildirebilirsiniz. [Bir güvenlik açığı bildirin.](https://cecom.in/contact)

== Screenshots ==

1. Cascading Province and District dropdowns on the WooCommerce checkout
2. Admin settings — **General** tab for enabling/disabling the plugin
3. Admin settings — **Data** tab for managing address data sync

= Ekran Görüntüleri =

1. WooCommerce ödeme sayfasında kademeli İl ve İlçe açılır menüleri
2. Yönetici ayarları — eklentiyi etkinleştirme/devre dışı bırakma için **Genel** sekmesi
3. Yönetici ayarları — adres verisi senkronizasyonunu yönetmek için **Veri** sekmesi

== Changelog ==

= 1.4.0 - Released on 18 April 2026 =

* New: CECOM Ecosystem page — cross-promotional admin page listing all CECOM plugins with install-state badges and purchase links.
* Fix: Incorrect text domain in free edition settings view corrected.
* Yeni: CECOM Ekosistem sayfası — tüm CECOM eklentilerini kurulum durumu rozetleri ve satın alma bağlantılarıyla listeleyen çapraz tanıtım yönetici sayfası.
* Düzeltme: Ücretsiz sürüm ayarlar görünümündeki hatalı metin alanı düzeltildi.

= 1.3.3 - Released on 11 April 2026 =

* Fix: Admin Bootstrap CSS not loading — prefixed all asset handles to prevent collisions with other plugins.
* Fix: Admin asset paths aligned to use the vendor directory structure for consistent deployment.
* Düzeltme: Yönetici Bootstrap CSS'in yüklenmemesi — diğer eklentilerle çakışmayı önlemek için tüm varlık tanıtıcıları ön eklendi.
* Düzeltme: Yönetici varlık yolları, tutarlı dağıtım için vendor dizin yapısına uyumlu hale getirildi.

= 1.3.2 - Released on 10 April 2026 =

* Update: Front-end fixes.
* Update: Minor bugs.
* Güncelleme: Ön yüz düzeltmeleri.
* Güncelleme: Küçük hatalar.

= 1.3.1 - Released on 01 April 2026 =

* Update: Translation corrections — regenerated POT from source, completed all missing Turkish translations
* Güncelleme: Çeviri düzeltmeleri — POT kaynaktan yeniden oluşturuldu, eksik Türkçe çeviriler tamamlandı

= 1.3.0 - Released on 01 January 2026 =

* Tweak: Internal improvements and stability fixes
* Dev: Activation error now uses HTTP 500 so the standard WordPress error banner is shown when requirements are not met
* İyileştirme: İç iyileştirmeler ve kararlılık düzeltmeleri
* Geliştirici: Aktivasyon hatası artık HTTP 500 kullanıyor; böylece gereksinimler karşılanmadığında standart WordPress hata başlığı gösteriliyor

= 1.2.0 - Released on 01 October 2025 =

* Update: The Neighborhood field is now a plain text input for standard address entry
* Güncelleme: Mahalle alanı artık standart adres girişi için düz metin girişi olarak gösteriliyor

= 1.1.0 - Released on 01 July 2025 =

* Tweak: Internal improvements and stability fixes
* İyileştirme: İç iyileştirmeler ve kararlılık düzeltmeleri

= 1.0.0 - Released on 01 April 2025 =

* New: Cascading Province → District dropdowns for the Classic Checkout
* New: Background address data import via WP-Cron after activation
* New: Manual address data sync from the Data tab
* New: WooCommerce HPOS compatibility declaration
* New: GDPR privacy exporter and eraser
* New: Turkish translation (tr_TR)
* New: Accessibility — ARIA live regions, keyboard navigation, noscript fallback
* Yeni: Klasik Ödeme için İl → İlçe kademeli açılır menüleri
* Yeni: Aktivasyondan sonra WP-Cron aracılığıyla arka plan adres verisi içe aktarma
* Yeni: Veri sekmesinden elle adres verisi senkronizasyonu
* Yeni: WooCommerce HPOS uyumluluk beyanı
* Yeni: GDPR gizlilik dışa aktarıcı ve silici
* Yeni: Türkçe çeviri (tr_TR)
* Yeni: Erişilebilirlik — ARIA canlı bölgeler, klavye navigasyonu, noscript yedekleme

== Upgrade Notice ==

= 1.4.0 =
CECOM Ecosystem page added. Minor text domain fix. No upgrade steps required.
CECOM Ekosistem sayfası eklendi. Küçük metin alanı düzeltmesi. Güncelleme adımı gerekmez.

= 1.3.3 =
Admin CSS fix — Bootstrap now loads correctly on the settings page. No upgrade steps required.
Yönetici CSS düzeltmesi — Bootstrap artık ayarlar sayfasında doğru şekilde yükleniyor. Güncelleme adımı gerekmez.

= 1.3.2 =
Front-end fixes and minor bug fixes. No upgrade steps required.
Ön yüz düzeltmeleri ve küçük hata düzeltmeleri. Güncelleme adımı gerekmez.

= 1.3.1 =
Translation corrections. No upgrade steps required.
Çeviri düzeltmeleri. Güncelleme adımı gerekmez.

= 1.3.0 =
Internal improvements. No upgrade steps required.
İç iyileştirmeler. Güncelleme adımı gerekmez.

= 1.2.0 =
The Neighborhood field is now a plain text input. No upgrade steps required.
Mahalle alanı artık düz metin girişi. Güncelleme adımı gerekmez.

= 1.1.0 =
No upgrade steps required.
Güncelleme adımı gerekmez.

= 1.0.0 =
Initial release. No upgrade steps required.
İlk sürüm. Güncelleme adımı gerekmez.

== External Services ==

This plugin connects to two external services: the WordPress.org Plugins API and an address data API hosted at cecom.in.

Bu eklenti iki harici servise bağlanır: WordPress.org Eklentiler API'si ve cecom.in adresinde barındırılan bir adres verisi API'si.

= WordPress.org Plugins API (api.wordpress.org) =

**Purpose:** Retrieve the plugin's public rating and review count from WordPress.org to display a star-rating row beneath the plugin entry on the WordPress **Plugins** list page.

**When the connection is made:**

* Once per 12 hours when an administrator views the WordPress **Plugins** list page, via a cached background request. No request is made if a valid cached value already exists.

**What data is sent:**

* The plugin slug (`smarttr-address`) is included in the request URL as a public identifier — no personal data, no site URL, and no user data is transmitted.

**Service provider:** WordPress.org
* Terms of Service: https://wordpress.org/about/tos/
* Privacy Policy: https://wordpress.org/about/privacy/

= WordPress.org Eklentiler API'si (api.wordpress.org) =

**Amaç:** WordPress.org'daki eklentinin genel puan ve inceleme sayısını alarak WordPress **Eklentiler** listesi sayfasında eklenti girişinin altında yıldız derecelendirmesi satırı olarak görüntülemek.

**Bağlantı ne zaman kurulur:**

* Bir yönetici WordPress **Eklentiler** listesi sayfasını görüntülediğinde 12 saatte bir, önbelleğe alınmış bir arka plan isteği aracılığıyla. Geçerli önbelleğe alınmış bir değer zaten mevcutsa istek yapılmaz.

**Hangi veriler gönderilir:**

* Genel bir tanımlayıcı olarak eklenti slug'ı (`smarttr-address`) istek URL'sine eklenir — kişisel veri, site URL'si veya kullanıcı verisi iletilmez.

**Hizmet sağlayıcı:** WordPress.org
* Kullanım Koşulları: https://wordpress.org/about/tos/
* Gizlilik Politikası: https://wordpress.org/about/privacy/

= Address Data Service (cecom.in) =

**Purpose:** Retrieve Turkish administrative address data — provinces and districts — used to populate the checkout cascade dropdowns.

**When the connection is made:**

* Once in the background immediately after plugin activation (via WP-Cron)
* When an administrator clicks **Reimport Data** in the **CECOM > SmartTR Address > Data** tab

**What data is sent:**

* Read-only API credentials (consumer key and consumer secret) sent as URL query parameters for authentication — these are fixed credentials that identify this plugin, not the site or its users
* No personal data, no customer data, and no site-specific data is transmitted

**Service provider:** CECOM (cecom.in)
* Terms of Service: https://cecom.in/terms-conditions
* Privacy Policy: https://cecom.in/privacy-policy

= Adres Verisi Servisi (cecom.in) =

**Amaç:** Ödeme sayfası kademeli açılır menülerini doldurmak için kullanılan Türk idari adres verilerini (iller ve ilçeler) almak.

**Bağlantı ne zaman kurulur:**

* Eklenti aktivasyonunun hemen ardından arka planda bir kez (WP-Cron aracılığıyla)
* Bir yönetici **CECOM > SmartTR Address > Veri** sekmesindeki **Veriyi Yeniden İçe Aktar**'a tıkladığında

**Hangi veriler gönderilir:**

* Kimlik doğrulama için URL sorgu parametreleri olarak gönderilen salt okunur API kimlik bilgileri (tüketici anahtarı ve tüketici sırrı) — bunlar sitenizi veya kullanıcılarınızı değil, bu eklentiyi tanımlayan sabit kimlik bilgileridir
* Kişisel veri, müşteri verisi veya siteye özgü veri iletilmez

**Hizmet sağlayıcı:** CECOM (cecom.in)
* Kullanım Koşulları: https://cecom.in/terms-conditions
* Gizlilik Politikası: https://cecom.in/privacy-policy