#!/usr/bin/env bash
# Rsync: mu-plugins, themes (i opcjonalnie plugins) na staging lub prod.
# Użycie: ./scripts/deploy-rsync.sh [staging|prod]
# Dry-run: DRY_RUN=1 ./scripts/deploy-rsync.sh [staging|prod]
# Nie kopiujemy nigdy: wp-config.php, .env (ich nie ma w repo).
# Dla prod: pracujemy tylko w public_html/wp-content/... — katalog staging/ nie jest dotykany.

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

# Jawne ścieżki (jedna prawda) — override przez .env: STAGING_REMOTE_PATH, STAGING_PROD_PATH
STAGE_PATH="${STAGING_REMOTE_PATH:-domains/mnsk7-tools.pl/public_html/staging}"
PROD_PATH="${STAGING_PROD_PATH:-domains/mnsk7-tools.pl/public_html}"

if [[ ! -f .env ]]; then
  echo "Brak .env"
  exit 1
fi

SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
TARGET="${1:-staging}"

if [[ "$TARGET" == "prod" ]]; then
  REMOTE_BASE="$PROD_PATH"
  if [[ "$REMOTE_BASE" == *"staging"* ]]; then
    echo "BŁĄD: Ścieżka prod nie może zawierać 'staging'. PROD_PATH=$PROD_PATH"
    exit 1
  fi
  echo ">>> DEPLOY DO PROD <<<"
else
  REMOTE_BASE="$STAGE_PATH"
  echo ">>> DEPLOY DO STAGING <<<"
fi

echo "TARGET=$TARGET"
echo "REMOTE_BASE=$REMOTE_BASE"
echo "Pełna ścieżka na serwerze: ~/$REMOTE_BASE/wp-content/..."
if [[ -n "${DRY_RUN:-}" ]]; then
  echo "DRY-RUN (nic nie zostanie zmienione, rsync -n)"
  RSYNC_EXTRA="-n"
else
  RSYNC_EXTRA=""
fi

RSYNC_SSH="ssh -p $SSH_PORT -o StrictHostKeyChecking=no"

if [[ -d "$ROOT/mu-plugins" ]]; then
  echo "Rsync mu-plugins -> $TARGET..."
  rsync -avz --delete $RSYNC_EXTRA -e "$RSYNC_SSH" "$ROOT/mu-plugins/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/mu-plugins/"
fi

# Deploy only child theme mnsk7-storefront — do NOT sync entire themes/ with --delete
# (would remove storefront parent on server; see docs/THEME-STACK-ROOT-CAUSE-AND-FIX.md)
if [[ -d "$ROOT/wp-content/themes/mnsk7-storefront" ]]; then
  if [[ -z "${DRY_RUN:-}" ]] && [[ -n "${DEPLOY_BACKUP_THEME:-}" ]]; then
    echo "Backup current child theme on server (for rollback)..."
    ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" \
      "cd ~/${REMOTE_BASE}/wp-content/themes && [ -d mnsk7-storefront ] && [ ! -d mnsk7-storefront_prev ] && cp -a mnsk7-storefront mnsk7-storefront_prev" 2>/dev/null || true
  fi
  echo "Rsync theme mnsk7-storefront -> $TARGET..."
  rsync -avz --delete $RSYNC_EXTRA -e "$RSYNC_SSH" "$ROOT/wp-content/themes/mnsk7-storefront/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/themes/mnsk7-storefront/"
fi

if [[ -d "$ROOT/wp-content/plugins" ]]; then
  echo "Rsync plugins -> $TARGET..."
  rsync -avz --delete $RSYNC_EXTRA -e "$RSYNC_SSH" "$ROOT/wp-content/plugins/" "${SSH_USER}@${SSH_HOST}:~/${REMOTE_BASE}/wp-content/plugins/"
fi

echo "Deploy rsync done."
