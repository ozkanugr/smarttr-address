---
title: "FAQ"
description: "Frequently asked questions about SmartTR Address"
sidebar_position: 90
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# Frequently Asked Questions

## What are the system requirements?

SmartTR Address requires PHP 8.1+, WordPress 6.4+, and WooCommerce 7.0+. Block-based checkout is not supported; the plugin works with the Classic Checkout shortcode (`[woocommerce_checkout]`) only.

## How is the address data kept up to date?

Address data is fetched from a secure remote API hosted at cecom.in. You can manually trigger a sync at any time from the **Data** tab.

## Will this slow down my checkout page?

No. Province and district data is preloaded with the page (no extra AJAX request, approximately 12 KB). The entire cascade adds negligible overhead to checkout performance.

## What happens if a customer does not select Turkey?

The plugin does not interfere with any non-Turkey order. Standard WooCommerce fields (State, City, Postal Code) remain completely unchanged for all other countries. The cascade activates only when "Turkey" (TR) is selected as the billing or shipping country.

## Does this work with my theme?

SmartTR Address hooks into WooCommerce's standard field rendering pipeline. It is compatible with any WooCommerce-compatible theme, including Storefront, Flatsome, Astra, OceanWP, and page builders like Elementor and Divi.

## Is this compatible with WooCommerce HPOS?

Yes. The plugin is declared compatible with WooCommerce High-Performance Order Storage (HPOS) and uses the correct `$order->update_meta_data()` API for all order meta writes.

## What data is stored in the database?

The plugin creates two custom tables:

- `wp_cecomsmarad_provinces` — 81 provinces (public geographic data)
- `wp_cecomsmarad_districts` — ~973 districts (public geographic data)

Customer address selections are stored as standard WooCommerce order meta. No personal data is stored in the plugin's custom tables.

## Does uninstalling the plugin remove all data?

Yes. Deleting the plugin (not just deactivating) removes both custom tables, all plugin options, and all cached data. Deactivating the plugin does not remove any data.

## Is this GDPR-compliant?

Yes. The plugin registers a personal data exporter and eraser with WordPress's privacy tools. All address data is included in privacy exports and can be erased on request.

## I activated the plugin but the cascade is not showing. What should I do?

Address data is imported in the background after activation. If the cascade does not appear within a couple of minutes:

1. Visit **CECOM → SmartTR Address → Data**
2. Click **Reimport Data** to manually trigger the sync
3. Verify your server allows outbound HTTPS connections to `cecom.in` (port 443)

## Can I translate the plugin?

Yes. All user-facing strings are translatable. A `.pot` template file is included in the `languages/` folder. A complete Turkish (`tr_TR`) translation is bundled. You can use Loco Translate or any standard WordPress translation workflow to add other languages.

## Does the plugin support Block Checkout?

Not in v1.x. Only the Classic Checkout (`[woocommerce_checkout]` shortcode) is supported. Block checkout support is planned for a future release. If your store uses the WooCommerce Block Checkout, you must switch to Classic Checkout to use SmartTR Address.

