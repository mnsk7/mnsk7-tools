#!/usr/bin/env bash
# Создать репозиторий на GitHub и запушить текущую ветку (максимум автоматом).
# Требуется: GitHub CLI (gh), авторизация (gh auth login).
# Использование: ./scripts/github-create-repo.sh [ИМЯ_РЕПО]
# Пример: ./scripts/github-create-repo.sh mnsk7-tools

set -e
SCRIPT_DIR="$(cd "$(dirname "${BASH_SOURCE[0]}")" && pwd)"
ROOT="$(dirname "$SCRIPT_DIR")"
cd "$ROOT"

REPO_NAME="${1:-mnsk7-tools}"

if ! command -v gh &>/dev/null; then
  echo "Установи GitHub CLI: brew install gh"
  echo "Потом: gh auth login"
  exit 1
fi

if ! gh auth status &>/dev/null; then
  echo "Сначала авторизуйся: gh auth login"
  exit 1
fi

# Если origin уже есть — удаляем, чтобы gh привязал свой репо
if git remote get-url origin &>/dev/null; then
  echo "Удаляю текущий origin: $(git remote get-url origin)"
  git remote remove origin
fi

# Создать репо (private), привязать к текущей папке и запушить
gh repo create "$REPO_NAME" --private --source=. --remote=origin --description "mnsk7-tools.pl: WordPress/WooCommerce, staging, deploy" --push

echo ""
echo "Готово. Репо: https://github.com/$(gh repo view --json nameWithOwner -q .nameWithOwner)"
echo "Дальше: добавь секреты для деплоя (см. docs/STAGING_AND_GITHUB.md) и при пуше в main будет деплой на стейдж."
