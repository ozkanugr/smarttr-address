---
title: "Quick Start"
description: "Get SmartTR Address working on your WooCommerce checkout in under 5 minutes"
sidebar_position: 3
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# Quick Start

Get SmartTR Address working in 5 minutes:

1. **Install & Activate** — See [Installation](./installation.md). Address data is imported automatically in the background.
2. **Open Settings** — Go to **CECOM → SmartTR Address** in your WordPress admin sidebar.
3. **Verify the plugin is enabled** — On the **General** tab, confirm that *Enable SmartTR Address* is checked (it is enabled by default).
4. **Check your data** — Switch to the **Data** tab and confirm province and district counts are shown (e.g. "81 provinces, 973 districts"). If counts are zero, click **Reimport Data**.
5. **Test on checkout** — Visit your WooCommerce checkout page, set the billing country to **Turkey**, and confirm the Province and District dropdowns appear.

## What to Expect on Checkout

- Customer selects **Turkey** → Province dropdown activates
- Customer selects a Province → District dropdown populates instantly (client-side, no page reload)
- Customer selects a District → Neighborhood field is a plain text input (type manually)
- Postal code is a standard editable text field

For any **non-Turkey country**, the plugin is completely invisible — standard WooCommerce fields are shown unchanged.

## Next Steps

- [Review action hooks for developers](./developer/hooks.md)
