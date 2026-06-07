# Migration Guide

This repository is intended to contain everything needed to recreate the local DMSPHD WooCommerce store quickly:

- Docker Compose WordPress/MySQL/phpMyAdmin stack
- Custom storefront theme: `wp-content/themes/edgeturn-tools`
- WooCommerce plugin version used locally: `wp-content/plugins/woocommerce`
- Imported Amazon product reports and generated parent-child mapping
- Product images under `wp-content/uploads`
- Database backup under `backups`
- Import and variant merge scripts under `wp-content/import-scripts`

## Restore On A New Server

1. Clone the repository.

```bash
git clone https://github.com/fording1688/dmsphd-store.git
cd dmsphd-store
```

2. Start the containers.

```bash
docker compose up -d
```

3. Restore the database snapshot.

```bash
./scripts/restore-db.sh
```

4. Open the store.

```text
http://localhost:8080
```

Admin tools:

```text
http://localhost:8081
```

## Make A Fresh Database Backup

```bash
./scripts/backup-db.sh
```

## Product Import Assets

The current product state came from these files:

- `wp-content/import-data/amazon-active-listings-2026-06-07.txt`
- `wp-content/import-data/amazon-image-map-2026-06-07.csv`
- `wp-content/import-data/amazon-parent-child-map-2026-06-07.csv`

The parent-child variant import uses Amazon's `Parentage Level` and `Parent SKU` fields. Parent rows are treated as virtual WooCommerce variable products, and child rows become WooCommerce variations.

Relevant scripts:

- `wp-content/import-scripts/import-amazon-active-listings.php`
- `wp-content/import-scripts/import-amazon-product-images.php`
- `wp-content/import-scripts/extract-amazon-parent-child-map.py`
- `wp-content/import-scripts/merge-parent-child-variants.php`
- `wp-content/import-scripts/rollback-title-variant-merge.php`

## Production Notes

Before putting this on a public server, change the database passwords in `docker-compose.yml`, configure HTTPS, update the WordPress site URL/domain in the database, and add real payment, shipping, tax, and email settings.
