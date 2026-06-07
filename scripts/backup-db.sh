#!/usr/bin/env bash
set -euo pipefail

backup_dir="${1:-backups}"
mkdir -p "$backup_dir"

if [[ -f .env ]]; then
  set -a
  source .env
  set +a
fi

db_name="${MYSQL_DATABASE:-woodtools}"
db_user="${MYSQL_USER:-woodtools}"
db_password="${MYSQL_PASSWORD:-woodtools_pass}"
compose_file="${COMPOSE_FILE:-docker-compose.yml}"

timestamp="$(date +%Y%m%d-%H%M%S)"
out_file="$backup_dir/$db_name-$timestamp.sql"

docker compose -f "$compose_file" exec -T db mysqldump --no-tablespaces -u "$db_user" "-p$db_password" "$db_name" > "$out_file"

echo "Database backup written to $out_file"
