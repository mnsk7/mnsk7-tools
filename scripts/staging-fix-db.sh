#!/usr/bin/env bash
# Замена siteurl/home и blog_public=0 в базе staging (префикс таблиц cmee_).
# Запуск с локального Mac (пароль в кавычках один раз):
#   ./scripts/staging-fix-db.sh
# Или с явным паролем:
#   DB_PASS='твой_пароль' ./scripts/staging-fix-db.sh

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

DB_NAME="llojjlcemq_stg"
DB_USER="llojjlcemq_fdvz1"

if [[ -z "$DB_PASS" ]]; then
  if [[ -f .env ]]; then
    DB_PASS=$(awk -F= '/^cyberfolks_ssh_password=/ {gsub(/^[" ]|[" ]$/, "", $2); print $2}' .env)
  fi
fi
if [[ -z "$DB_PASS" ]]; then
  echo "Задай пароль БД: export DB_PASS='пароль' и снова запусти скрипт"
  exit 1
fi

SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)

echo "Обновляю siteurl, home и blog_public на сервере..."
ssh -p "${SSH_PORT}" "${SSH_USER}@${SSH_HOST}" "export MYSQL_PWD='${DB_PASS}'; mysql -u ${DB_USER} -h localhost ${DB_NAME} -e \"UPDATE cmee_options SET option_value = 'https://staging.mnsk7-tools.pl' WHERE option_name IN ('siteurl', 'home'); UPDATE cmee_options SET option_value = '0' WHERE option_name = 'blog_public';\"; unset MYSQL_PWD"
echo "Готово."
