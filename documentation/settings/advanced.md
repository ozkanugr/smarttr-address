---
title: "Advanced Settings"
description: "Advanced and developer settings for SmartTR Address"
sidebar_position: 21
sidebar_label: "Advanced"
edition: "free"
last_updated: "2026-03-25"
generated_by: "cecom-schema/generate-documentation"
plugin_version: "1.3.0"
slug: "smarttr-address"
---

# Advanced Settings

These settings are managed internally by SmartTR Address and are not exposed in the admin UI. They are documented here for developer reference and diagnostics.

## Internal Option Keys

| Option Key | Type | Description |
|-----------|------|-------------|
| `cecomsmarad_last_import` | string (`Y-m-d H:i:s`) | Timestamp of the most recent successful data import |
| `cecomsmarad_record_counts` | JSON string | Number of records in each table: `{"provinces": 81, "districts": 973}` |
| `cecomsmarad_data_version` | string | Version identifier returned by the remote address data API |

## Diagnostic Queries

```bash
# View last import timestamp
wp option get cecomsmarad_last_import

# View record counts
wp option get cecomsmarad_record_counts

# View data version
wp option get cecomsmarad_data_version
```

## wp-config.php Constants

These constants can be defined in `wp-config.php` to override plugin defaults without touching plugin files:

| Constant | Default | Description |
|----------|---------|-------------|
| `CECOMSMARAD_API_CONSUMER_KEY` | *(built-in)* | Address data API consumer key |
| `CECOMSMARAD_API_CONSUMER_SECRET` | *(built-in)* | Address data API consumer secret |

## Database Tables

| Table | Records | Description |
|-------|---------|-------------|
| `{prefix}cecomsmarad_provinces` | 81 | Turkish provinces (code + name) |
| `{prefix}cecomsmarad_districts` | ~973 | Turkish districts (province FK + name) |
