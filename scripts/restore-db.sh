#!/usr/bin/env bash
set -euo pipefail

backup_file="${1:-backups/pre-parent-child-variants-2026-06-07.sql}"

if [[ ! -f "$backup_file" ]]; then
  echo "Backup file not found: $backup_file" >&2
  exit 1
fi

docker compose exec -T db mysql -u woodtools -pwoodtools_pass woodtools < "$backup_file"

echo "Database restored from $backup_file"
