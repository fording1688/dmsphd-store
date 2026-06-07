#!/usr/bin/env bash
set -euo pipefail

backup_file="${1:-backups/pre-parent-child-variants-2026-06-07.sql}"

if [[ ! -f "$backup_file" ]]; then
  echo "Backup file not found: $backup_file" >&2
  exit 1
fi

if [[ -f .env ]]; then
  set -a
  source .env
  set +a
fi

db_name="${MYSQL_DATABASE:-woodtools}"
db_user="${MYSQL_USER:-woodtools}"
db_password="${MYSQL_PASSWORD:-woodtools_pass}"
compose_file="${COMPOSE_FILE:-docker-compose.yml}"

docker compose -f "$compose_file" exec -T db mysql -u "$db_user" "-p$db_password" "$db_name" < "$backup_file"

echo "Database restored from $backup_file"
