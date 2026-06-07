# Cloudflare + Host Deployment

Recommended architecture:

```text
Visitor
 -> Cloudflare DNS / SSL / CDN / WAF
 -> Host server running Docker WordPress + WooCommerce + MySQL
```

This keeps WooCommerce checkout, orders, admin, plugins, and product management on the host server. Cloudflare handles DNS, HTTPS, edge caching, and basic protection.

## 1. Prepare The Host

Install Docker, Docker Compose, and Git on the server, then clone the repository:

```bash
git clone https://github.com/fording1688/dmsphd-store.git
cd dmsphd-store
```

Create a production environment file:

```bash
cp .env.example .env
```

Edit `.env`:

```text
COMPOSE_FILE=docker-compose.prod.yml
MYSQL_DATABASE=woodtools
MYSQL_USER=woodtools
MYSQL_PASSWORD=use_a_strong_password
MYSQL_ROOT_PASSWORD=use_a_different_strong_password
WORDPRESS_PORT=80
WORDPRESS_DEBUG=false
WORDPRESS_SITE_URL=https://store.dmsphdabrasives.com
```

## 2. Start WordPress

```bash
docker compose -f docker-compose.prod.yml up -d
```

Restore the included store database:

```bash
./scripts/restore-db.sh backups/pre-parent-child-variants-2026-06-07.sql
```

## 3. Point Cloudflare To The Server

In Cloudflare DNS:

- Add an `A` record for `store`, pointing to `97.64.29.123`.
- Add a `CNAME` record for `www`, pointing to the root domain.
- Turn on the orange cloud proxy.

SSL/TLS settings:

- Use `Full (strict)` after installing an origin certificate or a valid server certificate.
- Use `Full` only as a temporary setup mode.
- Enable Always Use HTTPS.

## 4. Update WordPress Domain

After DNS works, update the WordPress site URL in the database:

```bash
docker compose -f docker-compose.prod.yml exec -T wordpress php -r 'require_once "/var/www/html/wp-load.php"; update_option("home", "https://store.dmsphdabrasives.com"); update_option("siteurl", "https://store.dmsphdabrasives.com"); echo "Updated site URL\n";'
```

## 5. Cloudflare Cache Rules

Cache static assets aggressively:

- `/wp-content/uploads/*`
- `/wp-content/themes/*`
- `/wp-content/plugins/*`

Bypass cache for dynamic WooCommerce and admin routes:

- `/cart*`
- `/checkout*`
- `/my-account*`
- `/wp-admin*`
- `/wp-login.php`
- Requests with WooCommerce cart/session cookies

## 6. Operations

Create a fresh database backup:

```bash
./scripts/backup-db.sh
```

Update code on the server:

```bash
git pull
docker compose -f docker-compose.prod.yml up -d
```

View logs:

```bash
docker compose -f docker-compose.prod.yml logs -f wordpress
```

## Notes

Before accepting real orders, configure payment gateways, shipping, tax, email delivery, backups, and a real admin password. Keep `.env` private and do not commit it.
