---
title: "Action Hooks"
description: "Developer reference for all action hooks in SmartTR Address"
sidebar_position: 50
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
tags: ["developer", "hooks", "actions", "api"]
---

# Action Hooks

SmartTR Address integrates with WordPress and WooCommerce via standard hooks. The plugin does not currently expose custom `do_action()` hooks with a `cecomsmarad_` prefix for third-party use. Extension is achieved via the [Filter Hooks](./filters.md) listed in the next section, and via the standard WordPress/WooCommerce hooks the plugin attaches to.

## WordPress & WooCommerce Hooks Used

The table below lists the WordPress and WooCommerce hooks that SmartTR Address attaches to. These are documented to help developers understand integration points and avoid conflicts with other plugins.

### Admin Hooks

| Hook | Priority | Description | Edition |
|------|----------|-------------|---------|
| `admin_menu` | 10 | Registers the CECOM parent menu and SmartTR Address submenu page | Both |
| `admin_init` | 10 | Handles admin form submissions | Both |
| `admin_enqueue_scripts` | 10 | Enqueues Bootstrap, plugin CSS/JS on the settings page | Both |

### Checkout Hooks

| Hook | Priority | Description | Edition |
|------|----------|-------------|---------|
| `woocommerce_checkout_fields` | 10 | Injects Turkey-specific cascade field definitions | Both |
| `woocommerce_default_address_fields` | 10 | Modifies default WooCommerce address field properties | Both |
| `wp_enqueue_scripts` | 10 | Enqueues checkout JS and localizes `smarttrData` global | Both |

### Order Hooks

| Hook | Priority | Description | Edition |
|------|----------|-------------|---------|
| `woocommerce_checkout_order_created` | 10 | Saves province/district codes and names to order meta | Both |

### AJAX Hooks

| Hook | Nopriv | Description | Edition |
|------|--------|-------------|---------|
| `wp_ajax_cecomsmarad_save_general` | No | Saves General tab settings | Both |
| `wp_ajax_cecomsmarad_reset_fields` | No | Resets all field settings to defaults | Both |
| `wp_ajax_cecomsmarad_reimport_data` | No | Triggers a fresh address data import | Both |
| `wp_ajax_cecomsmarad_submit_deactivation_feedback` | No | Submits deactivation survey response | Both |

### Activation / Deactivation Hooks

| Hook | Description | Edition |
|------|-------------|---------|
| `register_activation_hook` | Creates database tables, schedules background sync, runs version compatibility check | Both |
| `register_deactivation_hook` | Clears transients only (data is preserved) | Both |

## Usage Example — Hooking After Data Import

To run custom logic after SmartTR Address completes a data reimport, you can hook into the WooCommerce Action Scheduler job or watch the `cecomsmarad_last_import` option change:

```php
add_action( 'update_option_cecomsmarad_last_import', function( $old_value, $new_value ): void {
    // Runs whenever the last import timestamp is updated
    error_log( 'SmartTR Address data imported at: ' . $new_value );
}, 10, 2 );
```

## See Also

- [Filter Hooks](./filters.md) — modify plugin behavior via `apply_filters()`
- [Advanced Settings](../settings/advanced.md) — option keys and transient cache details
