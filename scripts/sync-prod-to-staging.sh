#!/usr/bin/env bash
# Rsync ПРОД → СТЕЙДЖ на сервере (одна команда с твоего Mac).
# Копирует все файлы прода в папку стейджа, кроме wp-config.php (его не трогаем).
# После этого стейдж будет с тем же кодом/темами/плагинами/загрузками, что и прод.

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

[[ -f .env ]] || { echo "Нет .env"; exit 1; }
SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)

PROD_DIR="domains/mnsk7-tools.pl/public_html"
# Поддомен staging: каталог внутри основного домена (DirectAdmin)
STAGING_DIR="domains/mnsk7-tools.pl/public_html/staging"

echo "Rsync прод → стейдж на сервере (wp-config.php на стейдже не трогаем)..."
ssh -p "$SSH_PORT" "${SSH_USER}@${SSH_HOST}" "rsync -av --delete \
  --exclude=wp-config.php \
  --filter 'P wp-config.php' \
  --exclude=wp-content/cache/ \
  --exclude=wp-content/debug.log \
  --exclude=wp-content/advanced-cache.php \
  --exclude=wp-content/object-cache.php \
  ~/${PROD_DIR}/ ~/${STAGING_DIR}/"
echo "Готово. Staging: https://staging.mnsk7-tools.pl"
echo "Проверь, что в стейдже уже настроены wp-config (DB_NAME=llojjlcemq_stg, WP_HOME/WP_SITEURL)."
