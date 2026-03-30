<p align="center"><a href="https://cecom.com/"><img src="https://cecom.com/logo.png" alt="cecom.com"></a></p>

<p align="center">
<img src="https://img.shields.io/github/v/release/ozkanugur/smarttr-address?label=stable" alt="Latest release">
<img src="https://img.shields.io/wordpress/plugin/v/smarttr-address" alt="WordPress.org version">
<img src="https://img.shields.io/wordpress/plugin/installs/smarttr-address" alt="Active installs">
<img src="https://img.shields.io/github/license/cecom/smarttr-address" alt="License">
</p>

Welcome to the SmartTR Address repository on GitHub. Here you can browse the source, look at open issues, and keep track of development.

If you are not a developer, please use the [SmartTR Address plugin page](https://wordpress.org/plugins/smarttr-address/) on WordPress.org.

## About plugin

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

## Getting started

* [Prerequisites](#prerequisites)
* [Installation guide](#installation-guide)
* [Configuration](#configuration)
* [Verifying the plugin works](#verifying-the-plugin-works)
* [Available Languages](#available-languages)
* [Documentation](#documentation)
* [FAQ](#faq)
* [Changelog](#changelog)
* [Support](#support)
* [Reporting Security Issues](#reporting-security-issues)

## Prerequisites

> **WooCommerce must be installed and active before you activate SmartTR Address.**
> If WooCommerce is not active when you click Activate, activation will fail with a clear error message.

| Requirement | Minimum version |
|-------------|----------------|
| WordPress | 6.4 |
| WooCommerce | 7.0 |
| PHP | 8.1 |

## Installation guide

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

## Configuration

After activating the plugin:

1. Go to **WooCommerce → SmartTR Address**
2. On the **General** tab, make sure **Enable Plugin** is checked and click **Save Changes**
3. Switch to the **Data** tab — you will see the import status. Wait for address data to finish importing (usually takes less than a minute)
4. Once the Province and District record counts are greater than zero, the plugin is fully operational

## Verifying the plugin works

1. Open your WooCommerce checkout page (find the URL at **WooCommerce → Settings → Advanced → Checkout page**)
2. Select **Turkey (TR)** as the billing country
3. Confirm that:
   - A **Province** dropdown appears in place of the standard State/County text field
   - Selecting a province immediately populates the **District** dropdown
   - The checkout can be completed with the selected Province and District values

If the cascade does not appear, visit the **Data** tab and check that record counts are above zero. If they show 0, click **Reimport Data**.

## Available Languages

* English — United Kingdom (Default)
* Turkish — Turkey (`tr_TR`, bundled)

## Documentation

You can find the official documentation of the plugin [here](https://cecom.in/docs-category/smarttr-address-turkish-address-auto-fill).

## FAQ

**Does this plugin require WooCommerce?**

Yes. WooCommerce 7.0 or higher must be installed and active before you activate SmartTR Address.

**What checkout type does this support?**

SmartTR Address integrates with the WooCommerce **Classic Checkout** — the page that uses the `[woocommerce_checkout]` shortcode.

**Will this slow down my checkout page?**

No. Province and district data is embedded directly in the page (no extra requests). The cascade adds negligible overhead.

**What happens if a customer does not select Turkey?**

The plugin does not interfere with any non-Turkey order. Standard WooCommerce fields remain completely unchanged for all other countries.

**Is this compatible with WooCommerce HPOS?**

Yes. The plugin is declared compatible with WooCommerce High-Performance Order Storage (HPOS).

**Does uninstalling remove all data?**

Yes. Deleting the plugin removes all custom tables, plugin options, and cached data. Deactivating does not remove any data.

**Is this GDPR-compliant?**

Yes. The plugin registers a personal data exporter and eraser with WordPress's privacy tools.

## Changelog

### 1.3.0 - Released on 01 January 2026

* Tweak: Internal improvements and stability fixes
* Dev: Activation error now uses HTTP 500 so the standard WordPress error banner is shown when requirements are not met

### 1.2.0 - Released on 01 October 2025

* Update: The Neighborhood field is now a plain text input for standard address entry

[View full changelog](https://wordpress.org/plugins/smarttr-address/#developers)

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

## Support

This repository is a development tool. For end-user support, please post on the [WordPress.org support forum](https://wordpress.org/support/plugin/smarttr-address/).

## Reporting Security Issues

To disclose a security issue to our team, please contact us via our [security contact form](https://cecom.com/contact/).
