#!/usr/bin/env bash
set -euo pipefail

# Preflight checks for verify pipeline.
#
# Usage:
#   bash scripts/verify/preflight.sh
#
# This script does NOT run tests, only validates prerequisites.

ROOT="$(cd "$(dirname "$0")/../.." && pwd)"
cd "$ROOT"

fail () {
  echo "ERROR: $1" >&2
  exit 1
}

command -v node >/dev/null 2>&1 || fail "node is required"
command -v npm >/dev/null 2>&1 || fail "npm is required"

NODE_MAJOR="$(node -p "process.versions.node.split('.')[0]")"
if [[ "${NODE_MAJOR}" -lt 18 ]]; then
  fail "Node.js 18+ required (found: $(node -v))"
fi

if [[ ! -d node_modules ]]; then
  echo "node_modules not found. Run: npm install"
fi

if command -v php >/dev/null 2>&1; then
  echo "php: OK ($(php -v | head -n 1))"
else
  echo "php: NOT FOUND (L0 php -l checks will fail). Install PHP or add it to PATH."
  echo "  macOS: brew install php"
  echo "  Debian/Ubuntu: sudo apt-get update && sudo apt-get install -y php-cli"
  echo "  Проверка: php -v"
fi

echo "Playwright browsers:"
if npx playwright --version >/dev/null 2>&1; then
  npx playwright install --dry-run || true
else
  echo "Playwright not installed yet (run npm install)."
fi

echo "OK (preflight complete)."

