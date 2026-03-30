---
title: "Filter Hooks"
description: "Developer reference for all filter hooks in SmartTR Address"
sidebar_position: 51
edition: "both"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
tags: ["developer", "filters", "api"]
---

# Filter Hooks

SmartTR Address exposes the following filter hooks for developers to modify plugin behavior.

## Filter Reference

| Filter | Parameters | Default Return | Description | Edition | Since |
|--------|-----------|---------------|-------------|---------|-------|
| `cecomsmarad_file_form_field_html` | `$html (string)`, `$key (string)`, `$args (array)` | Generated HTML string | Filter the rendered HTML for file-type custom checkout fields. Use to wrap, replace, or augment the file upload input markup. | Both | 1.0.0 |

## Usage Examples

### `cecomsmarad_file_form_field_html`

Wrap the file input in a custom container div:

```php
add_filter(
    'cecomsmarad_file_form_field_html',
    function( string $html, string $key, array $args ): string {
        return '<div class="my-file-upload-wrapper">' . $html . '</div>';
    },
    10,
    3
);
```

Replace the file input HTML entirely for a specific field:

```php
add_filter(
    'cecomsmarad_file_form_field_html',
    function( string $html, string $key, array $args ): string {
        if ( 'billing_my_custom_file' === $key ) {
            return '<div class="custom-uploader"><!-- custom markup --></div>';
        }
        return $html;
    },
    10,
    3
);
```

### Parameters

| Parameter | Type | Description |
|-----------|------|-------------|
| `$html` | `string` | The fully rendered HTML for the file field, including label and input |
| `$key` | `string` | The WooCommerce field key (e.g. `billing_my_document`) |
| `$args` | `array` | The full field arguments array (label, type, class, required, etc.) |

## WooCommerce Filters Used by the Plugin

SmartTR Address also attaches to these standard WooCommerce filters. You can hook in at a higher or lower priority to modify field definitions before or after the plugin processes them:

| Filter | Plugin Priority | Description |
|--------|----------------|-------------|
| `woocommerce_checkout_fields` | 10 | Inject Province/District/Neighborhood fields for TR |
| `woocommerce_default_address_fields` | 10 | Modify default WooCommerce address field properties |

```php
// Example: Run your field modifications AFTER SmartTR Address (priority > 10)
add_filter( 'woocommerce_checkout_fields', function( array $fields ): array {
    // Your modifications here — SmartTR Address has already run
    return $fields;
}, 20 );
```

## See Also

- [Action Hooks](./hooks.md) — WordPress/WooCommerce hooks the plugin attaches to
- [Advanced Settings](../settings/advanced.md) — internal option keys and transient cache
