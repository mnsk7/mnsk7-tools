#!/usr/bin/env bash
# staging-refresh: дамп prod DB → импорт в staging → search-replace → отключить индексацию → flush
# Użycie: ./scripts/staging-refresh.sh (lub make staging-refresh)
# Wymaga: .env z cyberfolks_ssh_*, na serwerze wp-cli w PATH

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

if [[ ! -f .env ]]; then
  echo "Brak .env"
  exit 1
fi

SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
PROD_PATH="${STAGING_PROD_PATH:-domains/mnsk7-tools.pl/public_html}"
STAGING_PATH="${STAGING_STAGING_PATH:-domains/mnsk7-tools.pl/public_html/staging}"
PROD_URL="${STAGING_PROD_URL:-https://mnsk7-tools.pl}"
STAGING_URL="${STAGING_STAGING_URL:-https://staging.mnsk7-tools.pl}"

REMOTE_SCRIPT="
set -e
PROD_PATH=\"$PROD_PATH\"
STAGING_PATH=\"$STAGING_PATH\"
PROD_URL=\"$PROD_URL\"
STAGING_URL=\"$STAGING_URL\"
cd ~/\$PROD_PATH && wp db export /tmp/prod_mnsk7.sql --add-drop-table
cd ~/\$STAGING_PATH && wp db import /tmp/prod_mnsk7.sql
wp search-replace \"\$PROD_URL\" \"\$STAGING_URL\" --all-tables --precise
wp search-replace \"\${PROD_URL/https/http}\" \"\$STAGING_URL\" --all-tables --precise
wp option update blog_public 0
wp rewrite flush --hard
wp cache flush 2>/dev/null || true
rm -f /tmp/prod_mnsk7.sql
echo 'Staging DB refresh done.'
"

ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "bash -s" <<< "$REMOTE_SCRIPT"
