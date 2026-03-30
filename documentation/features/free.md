---
title: "Free Features"
description: "Complete list of features in SmartTR Address (Free Edition)"
sidebar_position: 10
sidebar_label: "Free Edition"
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# Free Edition Features

SmartTR Address free edition includes the following features — all fully functional with no artificial limitations on core address entry.

## Checkout — Cascading Address Dropdowns

### Province Dropdown

When Turkey (TR) is selected as the billing or shipping country, a searchable Province dropdown replaces the default WooCommerce State field. All 81 Turkish provinces are listed alphabetically and loaded from the local database (preloaded with the page — no extra AJAX request).

The dropdown uses WooCommerce's native selectWoo/Select2 component with type-to-filter search, scroll, and touch-friendly interaction consistent with WooCommerce's own dropdowns.

<!-- Screenshot placeholder: ![Province dropdown on WooCommerce checkout](../assets/smarttr-address-province-dropdown.png) -->

### District Dropdown

Selecting a Province immediately populates the District dropdown with the matching districts for that province — all client-side, no page reload. ~970 districts are preloaded alongside province data.

- Empty and disabled until a Province is selected
- Changing the Province resets District, Neighborhood, and Postal Code

### Neighborhood Text Input

The Neighborhood field (`address_2`) renders as a standard editable text input. Customers can type their neighborhood name manually.

## Country Detection & Non-Turkey Revert

The plugin activates only when Turkey is selected as the country — dynamically, via JavaScript, with no page reload. For any other country, the default WooCommerce State/City/Postal Code text fields are shown unchanged. The plugin has **zero impact** on non-Turkey orders.

Switching away from Turkey clears any Turkish address data already entered.

## Billing & Shipping Address Support

The Province → District cascade works independently for both the billing and shipping address sections. Enabling **Ship to a different address** activates a separate cascade for the shipping section; billing and shipping selections never interfere with each other.

## Classic Checkout Integration

Fully integrated with the WooCommerce **Classic Checkout** shortcode (`[woocommerce_checkout]`). Uses the standard `woocommerce_checkout_fields` and `woocommerce_default_address_fields` filters and handles all WooCommerce AJAX `update_checkout` events correctly.

> Block-based checkout is not supported in v1.x.

## Admin Settings — General & Data Tabs

### General Tab

A simple enable/disable toggle for the entire cascade. When disabled, the plugin reverts to standard WooCommerce fields for all countries.

<!-- Screenshot placeholder: ![General tab in SmartTR Address settings](../assets/smarttr-address-settings-general.png) -->

### Data Management Tab

- Displays the last data import date and the number of records in each table (provinces, districts)
- **Reimport Data** button with a confirmation dialog — triggers a fresh background sync from the remote API

<!-- Screenshot placeholder: ![Data tab in SmartTR Address settings](../assets/smarttr-address-settings-data.png) -->

## WooCommerce Order Meta

On every checkout, the plugin saves structured address data to WooCommerce order meta using the HPOS-compatible API:

- Province **code** (e.g. `TR34`) and **display name** (e.g. `İstanbul`)
- District **code** and **display name**

Both code and name are stored so that order data can be used for both human-readable admin views and machine-readable cargo API integrations.

## Accessibility

- ARIA live regions announce cascade updates (e.g., "Districts loaded for İstanbul") to screen readers
- Full keyboard navigation: Tab, Arrow keys, Enter, Escape work in all dropdowns
- Minimum 44×44 px touch targets for mobile
- WCAG 2.1 AA compliant for all plugin UI

## JavaScript Fallback (No-JS)

When JavaScript is unavailable, standard plain text inputs are provided via progressive enhancement. The address form remains functional and submits correctly without JS.

## Internationalization

- Turkish (`tr_TR`) translation bundled
- All user-facing strings are translatable via standard WordPress `.pot` workflow
- Compatible with Loco Translate and any standard WordPress i18n tooling

## Security

- All AJAX endpoints verify WordPress nonces
- All database queries use `$wpdb->prepare()`
- `manage_woocommerce` capability required for all admin actions
- Input sanitization and output escaping throughout
- `ABSPATH` check on every PHP file
