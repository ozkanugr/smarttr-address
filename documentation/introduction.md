---
title: "SmartTR Address"
description: "SmartTR Address — Turkish address auto-fill for WooCommerce checkout with cascading Province & District dropdowns"
sidebar_position: 1
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# SmartTR Address

SmartTR Address replaces the generic WooCommerce address fields with intelligent, data-driven cascading dropdowns for all 81 Turkish provinces and ~970 districts. When a customer selects Turkey as their country, the standard text inputs are instantly replaced with linked selects that guide them step-by-step through their address — eliminating typing errors, reducing failed deliveries, and improving checkout conversion for Turkish e-commerce stores.

Address data is fetched from a secure remote API on activation and stored locally in custom database tables — no bloated ZIP files bundled in the plugin.

## Features

- Province dropdown (81 provinces)
- District dropdown (cascading, ~970 districts)
- Neighborhood text input (manual entry)
- Billing & shipping address support
- Classic checkout support (`[woocommerce_checkout]` shortcode)
- Background data sync on activation
- Manual data re-import from the Data tab
- WooCommerce order meta (code + name)
- HPOS-compatible
- GDPR privacy exporter & eraser
- Turkish (tr_TR) translation
- Keyboard navigation & ARIA support

## Key Features

- **Guided address entry** — Province → District cascade using searchable selectWoo dropdowns; eliminates postal code lookups and misformatted city names
- **Country-aware** — activates only when Turkey (TR) is selected; zero impact on any other country
- **Both billing and shipping** — independent cascade for each address section; "Ship to different address" fully supported
- **Structured order data** — saves both province/district codes (e.g. `TR34`) and display names (e.g. `İstanbul`) to order meta for cargo API integrations
- **Security-first** — nonce verification, `$wpdb->prepare()` on all queries, `manage_woocommerce` capability checks, input sanitization, output escaping

## Support

- **Support Forum:** [WordPress.org Support Forum](https://wordpress.org/support/plugin/smarttr-address/)
- **Documentation:** You are here!
