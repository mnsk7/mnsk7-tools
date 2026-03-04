#!/usr/bin/env bash
# Проверка каталога/контента по БД (атрибуты, SKU, alt). Запуск НА СЕРВЕРЕ из каталога стейджа или с передачей учётных данных.
# Использование на сервере: mysql -u llojjlcemq_fdvz1 -p llojjlcemq_stg < /tmp/queries.sql
# Или: ./scripts/check-db-catalog.sh (читает .env, подключается по SSH и запускает mysql на сервере с паролем из .env)

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

DB_NAME="${DB_NAME:-llojjlcemq_stg}"
DB_USER="${DB_USER:-llojjlcemq_fdvz1}"
PRE="cmee_"

# Если передан пароль — используем SSH и выполняем на сервере
if [[ -n "$DB_PASS" ]] && [[ -f .env ]]; then
  SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
  SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
  SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
  echo "=== Атрибуты WooCommerce ==="
  ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "export MYSQL_PWD='${DB_PASS}'; mysql -u $DB_USER -h localhost $DB_NAME -e \"SELECT attribute_name, attribute_label FROM ${PRE}woocommerce_attribute_taxonomies;\"" 2>/dev/null || echo "(Ошибка: проверь DB_PASS)"
  echo ""
  echo "=== SKU: всего записей, пустых, примеры ==="
  ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "export MYSQL_PWD='${DB_PASS}'; mysql -u $DB_USER -h localhost $DB_NAME -e \"SELECT COUNT(*) AS total_sku FROM ${PRE}postmeta WHERE meta_key = '_sku'; SELECT COUNT(*) AS empty_sku FROM ${PRE}postmeta WHERE meta_key = '_sku' AND (meta_value IS NULL OR meta_value = ''); SELECT meta_value AS sample_sku FROM ${PRE}postmeta WHERE meta_key = '_sku' AND meta_value != '' LIMIT 5;\"" 2>/dev/null || echo "(Ошибка)"
  echo ""
  echo "=== Вложения: с alt, без alt ==="
  ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "export MYSQL_PWD='${DB_PASS}'; mysql -u $DB_USER -h localhost $DB_NAME -e \"SELECT COUNT(*) AS attachments_with_alt FROM ${PRE}postmeta pm JOIN ${PRE}posts p ON p.ID = pm.post_id WHERE pm.meta_key = '_wp_attachment_image_alt' AND p.post_type = 'attachment'; SELECT COUNT(*) AS attachments_empty_alt FROM ${PRE}postmeta pm JOIN ${PRE}posts p ON p.ID = pm.post_id WHERE pm.meta_key = '_wp_attachment_image_alt' AND (pm.meta_value IS NULL OR pm.meta_value = '');\"" 2>/dev/null || echo "(Ошибка)"
  exit 0
fi

# Иначе выводим SQL для ручного запуска в phpMyAdmin
echo "Задай пароль БД: export DB_PASS='...' и снова запусти скрипт."
echo "Или выполни в phpMyAdmin (база $DB_NAME) следующие запросы и подставь результаты в docs/AS_IS_AUDIT.md:"
echo ""
echo "-- 1) Атрибуты"
echo "SELECT attribute_name, attribute_label FROM ${PRE}woocommerce_attribute_taxonomies;"
echo ""
echo "-- 2) SKU: всего, пустых"
echo "SELECT COUNT(*) AS total FROM ${PRE}postmeta WHERE meta_key = '_sku';"
echo "SELECT COUNT(*) AS empty FROM ${PRE}postmeta WHERE meta_key = '_sku' AND (meta_value IS NULL OR meta_value = '');"
echo "SELECT meta_value FROM ${PRE}postmeta WHERE meta_key = '_sku' AND meta_value != '' LIMIT 5;"
echo ""
echo "-- 3) Alt у вложений"
echo "SELECT COUNT(*) FROM ${PRE}postmeta pm JOIN ${PRE}posts p ON p.ID = pm.post_id WHERE pm.meta_key = '_wp_attachment_image_alt' AND p.post_type = 'attachment';"
echo "SELECT COUNT(*) FROM ${PRE}postmeta pm JOIN ${PRE}posts p ON p.ID = pm.post_id WHERE pm.meta_key = '_wp_attachment_image_alt' AND (pm.meta_value IS NULL OR pm.meta_value = '');"
