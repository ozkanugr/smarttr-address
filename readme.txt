=== SmartTR Address ===

Contributors: cecom
Tags: woocommerce, turkey, address, checkout, province
Requires at least: 6.4
Tested up to: 6.9
Stable tag: 1.3.0
Requires PHP: 8.1
License: GPLv2 or later
License URI: https://www.gnu.org/licenses/gpl-2.0.html

Turkish address auto-fill for WooCommerce checkout — cascading Province and District dropdowns for Turkish orders.
WooCommerce 7.0 or higher compatible.

== Description ==

**SmartTR Address** replaces the generic WooCommerce address fields with accurate, data-driven cascading dropdowns for all 81 Turkish provinces and ~970 districts. When a customer selects Turkey as their country, the standard text inputs are instantly replaced with intelligent, linked selects that guide them through their Province and District selection.

The plugin integrates with the **Classic Checkout** (`[woocommerce_checkout]` shortcode).

[Free version live demo >](https://cecom.in/smarttr-address-turkish-address)
[Documentation >](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill)

= Basic features =

* **Province + District cascade dropdowns** — cascading dropdowns for all 81 Turkish provinces and ~970 districts
* **Classic Checkout** — integrates with the WooCommerce shortcode checkout (`[woocommerce_checkout]`)
* **Background data sync** — address data is fetched from a remote API after activation; no bloated ZIP files bundled in the plugin
* **GDPR-compliant** — built-in privacy exporter and eraser for all Turkish address data
* **HPOS-compatible** — fully supports WooCommerce High-Performance Order Storage
* **Accessible** — ARIA live regions, keyboard navigation, noscript fallback
* **Internationalized** — Turkish translation included; fully translatable via standard `.pot` file

== Installation ==

= Prerequisites =

**WooCommerce must be installed and active before you activate SmartTR Address.** If WooCommerce is not active when you click Activate, activation will fail with an error message.

1. Install and activate **WooCommerce 7.0 or higher** first
2. Then install and activate **SmartTR Address**

= Automatic Installation (Recommended) =

1. Make sure **WooCommerce 7.0 or higher** is already installed and active
2. Log in to your WordPress admin panel and go to **Plugins > Add New**
3. Search for **SmartTR Address**
4. Click **Install Now**, then **Activate**
5. Navigate to **WooCommerce > SmartTR Address** to configure the plugin
6. Address data will be imported automatically in the background — this usually takes less than a minute

= Manual Installation =

1. Make sure **WooCommerce 7.0 or higher** is already installed and active
2. Download the plugin ZIP from the WordPress Plugin Directory
3. Go to **Plugins > Add New > Upload Plugin**
4. Upload the ZIP file and click **Install Now**
5. Click **Activate Plugin**
6. Navigate to **WooCommerce > SmartTR Address** to configure

= Configuration =

After activating the plugin, follow these steps to verify everything is working:

1. Go to **WooCommerce > SmartTR Address**
2. On the **General** tab, make sure **Enable Plugin** is checked and click **Save Changes**
3. Switch to the **Data** tab — you will see the import status. Wait for the address data to finish importing (usually takes less than a minute after activation)
4. Once the record counts show numbers greater than 0 for Provinces and Districts, the plugin is ready
5. Visit your checkout page (you can use **WooCommerce > Settings > Advanced > Checkout page** to find the URL), select **Turkey (TR)** as the billing country, and confirm that:
   - A **Province** dropdown appears in place of the standard State/County field
   - Selecting a province immediately populates the **District** dropdown
   - The checkout can be completed with the selected Province and District values

= Troubleshooting =

* **Cascade does not appear:** Confirm the plugin is enabled on the General tab. Check the Data tab to ensure at least one province and district record has been imported. If counts show 0, click **Reimport Data**.
* **Reimport Data is greyed out:** A cooldown of 30 days applies between manual syncs to prevent excessive API requests. If you need to sync immediately (e.g. right after a fresh install), deactivate and reactivate the plugin to trigger the background sync again.
* **Activation error — WooCommerce not found:** Install and activate WooCommerce 7.0 or higher first, then activate SmartTR Address.

== Frequently Asked Questions ==

= Does this plugin require WooCommerce? =

Yes. SmartTR Address is a WooCommerce extension. WooCommerce 7.0 or higher must be installed and active before you activate this plugin. Attempting to activate without WooCommerce will show an error and the plugin will not be activated.

= How do I verify the plugin is working after installation? =

Go to **WooCommerce > SmartTR Address > Data** and confirm that the Province and District record counts are greater than zero. Then open your checkout page, select Turkey as the billing country, and a Province dropdown should appear. Selecting a province will load the matching District dropdown instantly.

= How is the address data kept up to date? =

Address data is fetched from a secure remote API hosted at cecom.in. You can manually trigger a sync at any time from the **Data** tab. A 30-day cooldown applies between manual syncs.

= What checkout type does this support? =

SmartTR Address integrates with the WooCommerce **Classic Checkout** block — the page that uses the `[woocommerce_checkout]` shortcode. The cascade activates automatically whenever a customer selects Turkey (TR) as their billing or shipping country.

= Will this slow down my checkout page? =

No. Province and district data is embedded directly in the page (no extra requests). The entire cascade adds negligible overhead to checkout performance.

= What happens if a customer does not select Turkey? =

The plugin does not interfere with any non-Turkey order. Standard WooCommerce fields remain completely unchanged for all other countries.

= Does this work with my theme? =

SmartTR Address hooks into WooCommerce's standard field rendering pipeline. It is compatible with any WooCommerce-compatible theme, including Storefront, Flatsome, Astra, OceanWP, and page builders like Elementor and Divi.

= Is this compatible with WooCommerce HPOS? =

Yes. The plugin is declared compatible with WooCommerce High-Performance Order Storage (HPOS) and uses the correct APIs to read and write order meta.

= What data is stored in the database? =

The plugin creates two custom tables: `wp_cecomsmarad_provinces` and `wp_cecomsmarad_districts`. These contain only public geographic data (no personal information). Customer address selections are stored as standard WooCommerce order meta.

= Does uninstalling the plugin remove all data? =

Yes. Deleting the plugin (not just deactivating it) removes all custom tables, all plugin options, and all cached data. Deactivating the plugin does not remove any data.

= Is this GDPR-compliant? =

Yes. The plugin registers a personal data exporter and eraser with WordPress's privacy tools. All Turkish address data (province, district) stored on orders is included in privacy exports and can be erased on request.

= Can I translate the plugin? =

Yes. All user-facing strings are translatable. A `.pot` template file is included in the `languages/` folder. A complete Turkish (`tr_TR`) translation is bundled. You can use Loco Translate or any standard WordPress translation workflow to add other languages.

= How can I report security bugs? =

You can report security bugs through the CECOM security contact form. [Report a security vulnerability.](https://cecom.in/contact)

== Screenshots ==

1. Cascading Province and District dropdowns on the WooCommerce checkout
2. Admin settings — **General** tab for enabling/disabling the plugin
3. Admin settings — **Data** tab for managing address data sync

== Changelog ==

= 1.3.0 - Released on 01 January 2026 =

* Tweak: Internal improvements and stability fixes
* Dev: Activation error now uses HTTP 500 so the standard WordPress error banner is shown when requirements are not met

= 1.2.0 - Released on 01 October 2025 =

* Update: The Neighborhood field is now a plain text input for standard address entry

= 1.1.0 - Released on 01 July 2025 =

* Tweak: Internal improvements and stability fixes

= 1.0.0 - Released on 01 April 2025 =

* New: Cascading Province → District dropdowns for the Classic Checkout
* New: Background address data import via WP-Cron after activation
* New: Manual address data sync from the Data tab
* New: WooCommerce HPOS compatibility declaration
* New: GDPR privacy exporter and eraser
* New: Turkish translation (tr_TR)
* New: Accessibility — ARIA live regions, keyboard navigation, noscript fallback

== Upgrade Notice ==

= 1.3.0 =
Internal improvements. No upgrade steps required.

= 1.2.0 =
The Neighborhood field is now a plain text input. No upgrade steps required.

= 1.1.0 =
No upgrade steps required.

= 1.0.0 =
Initial release. No upgrade steps required.

== External Services ==

This plugin connects to two external services: the WordPress.org Plugins API and an address data API hosted at cecom.in.

= WordPress.org Plugins API (api.wordpress.org) =

**Purpose:** Retrieve the plugin's public rating and review count from WordPress.org to display a star-rating row beneath the plugin entry on the WordPress **Plugins** list page.

**When the connection is made:**

* Once per 12 hours when an administrator views the WordPress **Plugins** list page, via a cached background request. No request is made if a valid cached value already exists.

**What data is sent:**

* The plugin slug (`smarttr-address`) is included in the request URL as a public identifier — no personal data, no site URL, and no user data is transmitted.

**Service provider:** WordPress.org
* Terms of Service: https://wordpress.org/about/tos/
* Privacy Policy: https://wordpress.org/about/privacy/

= Address Data Service (cecom.in) =

**Purpose:** Retrieve Turkish administrative address data — provinces and districts — used to populate the checkout cascade dropdowns.

**When the connection is made:**

* Once in the background immediately after plugin activation (via WP-Cron)
* When an administrator clicks **Reimport Data** in the **WooCommerce > SmartTR Address > Data** tab

**What data is sent:**

* Read-only API credentials (consumer key and consumer secret) sent as URL query parameters for authentication — these are fixed credentials that identify this plugin, not the site or its users
* No personal data, no customer data, and no site-specific data is transmitted

**Service provider:** CECOM (cecom.in)
* Terms of Service: https://cecom.in/terms-conditions
* Privacy Policy: https://cecom.in/privacy-policy
