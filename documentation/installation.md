---
title: "Installation"
description: "How to install and activate SmartTR Address on your WordPress site"
sidebar_position: 2
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# Installation

## System Requirements

| Requirement | Minimum Version |
|------------|-----------------|
| PHP | 8.1 |
| WordPress | 6.4 |
| WooCommerce | 7.0 |

> **Checkout type:** SmartTR Address supports the **Classic Checkout** (`[woocommerce_checkout]` shortcode) only. Block-based checkout is not supported in v1.x. If your store uses the WooCommerce Block Checkout, switch to Classic Checkout to use this plugin.

## Install via WordPress Dashboard (Recommended)

1. Log in to your WordPress admin and go to **Plugins → Add New**.
2. Search for **SmartTR Address**.
3. Click **Install Now**, then **Activate**.
4. Navigate to **CECOM → SmartTR Address** to review settings.
5. Address data is imported automatically in the background — this usually takes less than a minute.

## Manual Install via FTP

1. Download and unzip the plugin archive.
2. Upload the `smarttr-address/` folder to `/wp-content/plugins/` on your server.
3. Go to **Plugins** in WordPress admin and click **Activate**.

## After Activation

1. The plugin creates its database tables and schedules a background data sync immediately on activation.
2. An admin notice appears while data is being imported — this is normal behavior.
3. Once data is ready, the notice disappears and the checkout cascade is active for all Turkish orders.
4. Navigate to **CECOM → SmartTR Address** to review or adjust settings.

## Troubleshooting Activation

If the cascade does not appear after a couple of minutes:

1. Go to **CECOM → SmartTR Address → Data**.
2. Click **Reimport Data** to manually trigger the background sync.
3. Verify your server can make outbound HTTPS requests to `cecom.in` (port 443).
