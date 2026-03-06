# Staging: что в репо, деплой при пуше, секреты

## 0. Если репозитория на GitHub ещё нет (максимум автоматом)

У аккаунта [github.com/mnsk7](https://github.com/mnsk7) репозиториев пока нет. Создать репо и сразу запушить одной командой:

```bash
cd /Users/imac/staging.mnsk7-tools.pl
gh auth login   # один раз, если ещё не логинился
make github-create-repo REPO=mnsk7-tools
```

Или с именем репо по умолчанию (`mnsk7-tools`):

```bash
make github-create-repo
```

Скрипт создаст приватный репо, привяжет его как `origin` и выполнит `git push`. Дальше добавь секреты (раздел 3) — и при пуше в `main` будет деплой на стейдж.

**Без gh:** установи `brew install gh`, затем `gh auth login`, после чего снова `make github-create-repo`.

---

## 1. Что лежит в репо (редактируемое → деплой на стейдж)

В Git коммитим только то, что правим и что должно уезжать на стейдж при пуше:

| В репо | Куда деплоится при пуше в `main` |
|--------|-----------------------------------|
| `mu-plugins/` | `staging.../wp-content/mu-plugins/` |
| `wp-content/themes/` (best-shop, mnsk7-storefront; twenty* в .gitignore) | `staging.../wp-content/themes/` |
| `scripts/`, `docs/`, `Makefile`, `.agents/`, `tasks/`, `.cursorrules` | только в репо (на сервер не копируются) |

**Не в репо:** ядро WP, `wp-config.php`, `.env`, `wp-content/uploads/`, `wp-content/plugins/` (плагины не версионируем; при локальном `make deploy-files` можно гнать плагины с машины на стейдж отдельно).

---

## 2. Пуш в `main` = деплой на стейдж

Включён **GitHub Actions** (`.github/workflows/deploy-staging.yml`): при каждом **push в ветку `main`** воркфлоу поднимается и по SSH делает rsync **mu-plugins** и **themes** на стейдж. Локально `make deploy-files` после пуша вызывать не обязательно.

---

## 3. Секреты в GitHub (обязательно)

Без них воркфлоу не сможет зайти на сервер. В репо: **Settings → Secrets and variables → Actions → New repository secret**. Добавь:

| Имя секрета | Значение | Пример |
|-------------|----------|--------|
| `STAGING_SSH_KEY` | Приватный SSH-ключ целиком (с `-----BEGIN ... END-----`, с переносами строк) | `cat ~/.ssh/id_rsa_mnsk7_deploy` → скопировать всё в секрет |
| `STAGING_SSH_HOST` | Хост сервера | `s56.cyber-folks.pl` |
| `STAGING_SSH_USER` | SSH-пользователь | `llojjlcemq` |
| `STAGING_SSH_PORT` | Порт SSH | `222` |
| `STAGING_REMOTE_PATH` | Путь до каталога стейджа на сервере (без `~/`) | `domains/mnsk7-tools.pl/public_html/staging` |

Публичный ключ должен быть добавлен на сервер (DirectAdmin → Klucze SSH или `ssh-copy-id`). В секрет **STAGING_SSH_KEY** вставь приватный ключ целиком: выполни `cat ~/.ssh/id_rsa_mnsk7_deploy`, скопируй вывод (включая строки -----BEGIN ... и -----END ...) и вставь в значение секрета — **сохрани переносы строк**.

---

## 4. Локальный деплой (если нужно)

Если хочешь гнать на стейдж с компа без пуша (в т.ч. плагины):

```bash
make deploy-files   # mu-plugins + themes + plugins из локальной папки
```

Плагины в репо не хранятся — на стейдж они попадают только при таком ручном деплое.

---

## 5. Поисковики, почта, оплата на стейдже

- **Поисковики:** в БД стейджа `blog_public = 0` (через `make staging-fix-db` или вручную в phpMyAdmin).
- **Почта и оплата:** MU-плагин `staging-safety.php` (блокирует отправку писем и отключает платёжные методы на стейдже).

После пуша в `main` воркфлоу заливает актуальный `mu-plugins/`, в том числе `staging-safety.php`.

---

## 6. Ветки и PR

- **main** — основная ветка; push в `main` запускает деплой на стейдж (GitHub Actions).
- **feature/*** — фичи; мердж через PR в `main`. Шаблон PR: [.github/PULL_REQUEST_TEMPLATE.md](../.github/PULL_REQUEST_TEMPLATE.md).
- Перед мерджем: PHP Lint (workflow `php-lint.yml`) и smoke по [QA_REPORT.md](QA_REPORT.md).

Полный чеклист деплоя, отката и бэкапов: [DEPLOY_PLAYBOOK.md](DEPLOY_PLAYBOOK.md).
