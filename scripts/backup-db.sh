#!/usr/bin/env bash
set -euo pipefail

backup_dir="${1:-backups}"
mkdir -p "$backup_dir"

timestamp="$(date +%Y%m%d-%H%M%S)"
out_file="$backup_dir/woodtools-$timestamp.sql"

docker compose exec -T db mysqldump --no-tablespaces -u woodtools -pwoodtools_pass woodtools > "$out_file"

echo "Database backup written to $out_file"
