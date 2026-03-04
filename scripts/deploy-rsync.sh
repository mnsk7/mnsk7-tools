#!/usr/bin/env bash
# Rsync: mu-plugins (i opcjonalnie tema) na staging.
# Użycie: ./scripts/deploy-rsync.sh [staging|prod]
# Target domyślnie: staging (ścieżka z .env lub STAGING_REMOTE_PATH)

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
REMOTE_BASE="${STAGING_REMOTE_PATH:-domains/mnsk7-tools.pl/public_html/staging}"
TARGET="${1:-staging}"

if [[ "$TARGET" == "prod" ]]; then
  REMOTE_BASE="${STAGING_PROD_PATH:-domains/mnsk7-tools.pl/public_html}"
fi

RSYNC_SSH="ssh -p $SSH_PORT -o StrictHostKeyChecking=no"

if [[ -d "$ROOT/mu-plugins" ]]; then
  echo "Rsync mu-plugins -> $TARGET..."
  rsync -avz --delete -e "$RSYNC_SSH" "$ROOT/mu-plugins/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/mu-plugins/"
fi

if [[ -d "$ROOT/wp-content/themes" ]]; then
  echo "Rsync themes -> $TARGET..."
  rsync -avz --delete -e "$RSYNC_SSH" "$ROOT/wp-content/themes/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/themes/"
fi

if [[ -d "$ROOT/wp-content/plugins" ]]; then
  echo "Rsync plugins -> $TARGET..."
  rsync -avz --delete -e "$RSYNC_SSH" "$ROOT/wp-content/plugins/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/plugins/"
fi

echo "Deploy rsync done."
