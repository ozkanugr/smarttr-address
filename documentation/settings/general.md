---
title: "General Settings"
description: "General settings reference for SmartTR Address"
sidebar_position: 20
edition: "both"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# General Settings

Navigate to **CECOM → SmartTR Address → General** to configure these settings.

<!-- Screenshot placeholder: ![General settings tab](../assets/smarttr-address-settings-general.png) -->

## Settings Reference

| Setting | Option Key | Type | Default | Description | Edition |
|---------|-----------|------|---------|-------------|---------|
| Enable SmartTR Address | `cecomsmarad_enabled` | checkbox (`1`/`0`) | `1` (enabled) | When enabled, activates the Province → District cascade for Turkey checkout. When disabled, all address fields revert to standard WooCommerce behavior. | Both |

## Saving Settings

Click **Save Changes** at the bottom of the General tab. Settings are saved via an AJAX request (`cecomsmarad_save_general`) with nonce verification. A toast notification confirms success or displays an error.

## WP-CLI Access

```bash
# Check whether the cascade is enabled
wp option get cecomsmarad_enabled

# Disable the cascade
wp option update cecomsmarad_enabled 0

# Re-enable the cascade
wp option update cecomsmarad_enabled 1
```
