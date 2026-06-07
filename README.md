# DMSPHD WooCommerce Store

This repository contains the local WordPress + WooCommerce DMSPHD storefront, including the custom theme, product import scripts, Amazon parent-child variation mapping, uploaded product images, and a database backup for fast migration.

## Local Setup

1. Start WordPress, MySQL, and phpMyAdmin:

```bash
docker compose up -d
```

2. Open the storefront:

```text
http://localhost:8080
```

3. Open phpMyAdmin:

```text
http://localhost:8081
```

## Restore The Current Store

After cloning on a new machine/server, restore the included database snapshot:

```bash
./scripts/restore-db.sh
```

See [docs/migration.md](docs/migration.md) for the full migration checklist.

## Included Store Assets

- Custom theme: `wp-content/themes/edgeturn-tools`
- WooCommerce plugin used locally: `wp-content/plugins/woocommerce`
- Product images: `wp-content/uploads`
- Database backup: `backups/pre-parent-child-variants-2026-06-07.sql`
- Amazon import data: `wp-content/import-data`
- Import and variant scripts: `wp-content/import-scripts`

## Amazon Product Import

The current products were imported from the Amazon active listing/category reports. Parent-child variants are based on Amazon's `Parentage Level` and `Parent SKU` fields:

- Amazon parent rows become virtual WooCommerce variable products.
- Amazon child rows become WooCommerce variations.
- Child source products are kept as draft records after merging.

Useful scripts:

```bash
wp-content/import-scripts/import-amazon-active-listings.php
wp-content/import-scripts/import-amazon-product-images.php
wp-content/import-scripts/extract-amazon-parent-child-map.py
wp-content/import-scripts/merge-parent-child-variants.php
wp-content/import-scripts/rollback-title-variant-merge.php
```

## Backup

Create a fresh database backup:

```bash
./scripts/backup-db.sh
```

Before public deployment, update database passwords, configure HTTPS, set the production WordPress URL, and complete payment, tax, shipping, and email settings.
