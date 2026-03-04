# Team Fix Plan — mnsk7-tools.pl

Дата: 2026-03-04  
Источник: TEAM_AUDIT.md

Порядок по приоритету: сначала критичное, потом среднее.

---

## Приоритет 1 — КРИТИЧНО (делать сразу)

### Fix A: переместить staging-safety.php в wp-content/mu-plugins/
- **Что**: `mu-plugins/staging-safety.php` → `wp-content/mu-plugins/staging-safety.php`
- **Почему**: WP не видит MU-плагины вне `wp-content/mu-plugins/`
- **Статус**: ✅ Выполнено (см. ниже)

### Fix B: обновить .gitignore
- Добавить: `wp-content/uploads/`, `wp-content/cache/`, `wp-content/debug.log`, `cgi-bin/`, `*.backup.*`, `seraphinite-accelerator-*.htaccess`
- **Статус**: ✅ Выполнено

---

## Приоритет 2 — СРЕДНЕ

### Fix C: переименовать CEO-агент (убрать "00_")
- **Что**: `agents/00_ceo_team_audit.md` → `agents/_ceo_team_audit.md`
- **Почему**: конфликт с `00_client_discovery.md`, путаница в пайплайне; CEO — разовый агент вне нумерации
- **Статус**: ✅ Выполнено

### Fix D: разграничить deploy-скиллы
- `cyberfolks_deploy_playbook` — оставить как общий чеклист "до/после деплоя" (backup, cache flush, проверки)
- `vps_deploy_rsync` — только команды rsync/SSH (конкретные команды)
- `staging_bootstrap` — только инициализация нового staging с нуля
- Обновить заголовки/описания чтобы границы были явными
- **Статус**: ✅ Выполнено

### Fix E: убрать дубль VPS из ssl_cyberfolks
- Из `ssl_cyberfolks` удалить VPS-раздел (он уже есть в `ssl_certbot_playbook`)
- Добавить ссылку: "Для VPS: см. ssl_certbot_playbook"
- **Статус**: ✅ Выполнено

### Fix F: убрать мусорные файлы из root
- `PORADKEK-DZIALAN.txt`, `instalacje.txt`, `staging-plan.txt` → добавить в .gitignore
- `ssh-run.sh`, `sync-from-server.sh` → добавить в .gitignore (устаревшие скрипты с expect)
- `cgi-bin/` → в .gitignore
- **Статус**: ✅ Выполнено

---

## Приоритет 3 — НИЗКО

### Fix I: добавить definition of done
- Создать `/docs/DEFINITION_OF_DONE.md`
- **Статус**: ✅ Выполнено

### Fix J: обновить оркестратор
- Уточнить роль CEO-агента (разовый, вне нумерации)
- Убедиться, что шаги 1–7 согласованы
- **Статус**: ✅ Выполнено

---

## Что НЕ трогаем
- Боевой код сайта (wp-admin/, wp-includes/, wp-content/ — кроме добавления mu-plugin)
- wp-config.php (уже в .gitignore)
- scripts/deploy-rsync.sh и scripts/staging-refresh.sh — рабочие скрипты, не трогаем
- Makefile — рабочий, не трогаем
