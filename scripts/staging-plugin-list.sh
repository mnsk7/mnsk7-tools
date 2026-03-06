#!/usr/bin/env bash
# Lista pluginów na stagingu przez WP-CLI (SSH + 512M memory).
set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"
[[ -f .env ]] || { echo "Brak .env"; exit 1; }
SSH_HOST=$(grep '^cyberfolks_ssh_host=' .env | cut -d= -f2)
SSH_PORT=$(grep '^cyberfolks_ssh_port=' .env | cut -d= -f2)
SSH_USER=$(grep '^cyberfolks_ssh_user=' .env | cut -d= -f2)
SSH_PASS=$(grep '^cyberfolks_ssh_password=' .env | cut -d= -f2-)
WP_PATH="domains/mnsk7-tools.pl/public_html/staging"
WP_CMD="cd $WP_PATH && /opt/alt/php82/usr/bin/php -d memory_limit=512M /usr/local/bin/wp"
sshpass -p "$SSH_PASS" ssh -p "$SSH_PORT" -o StrictHostKeyChecking=no "${SSH_USER}@${SSH_HOST}" "$WP_CMD plugin list --format=table" 2>/dev/null
