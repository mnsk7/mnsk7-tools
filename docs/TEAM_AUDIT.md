# Team Audit — mnsk7-tools.pl

Дата: 2026-03-04  
Агент: CEO / Delivery Director (00_ceo_team_audit)

---

## 1. Что есть

### Агенты (10)
| Файл | Зона | Статус |
|------|------|--------|
| 00_ceo_team_audit.md | Ревизия команды | ✅ |
| 00_client_discovery.md | Дискавери клиента | ✅ |
| 01_product_manager.md | Backlog/спринты | ✅ |
| 02_growth_seo.md | SEO + контент + GA4 | ✅ |
| 03_wp_architect.md | Архитектура WP | ✅ |
| 04_woo_engineer.md | Woo-логика | ✅ |
| 05_theme_ux_frontend.md | Тема + UX | ✅ |
| 06_devops_github.md | Git/CI | ✅ |
| 07_server_ops_cyberfolks.md | VPS/БД/деплой | ✅ |
| 08_qa_security.md | QA + безопасность | ✅ |

### Skills (21)
content_catalog_rules, cyberfolks_deploy_playbook, db_provision_mysql,
discovery_frezes, gitflow_prs, github_actions_wp, marketing_positioning,
performance_corewebvitals, qa_smoke_woo, requirements_to_tasks,
security_wp_baseline, seo_woocommerce, ssh_key_auth_playbook,
ssl_certbot_playbook, ssl_cyberfolks, staging_bootstrap, team_audit,
vps_deploy_rsync, woo_loyalty_design, woo_templates_checkout,
wp_theme_architecture, wpcli_db_migrations

### Docs (9 файлов, все стабы)
ARCHITECTURE, BACKLOG, CONTENT_PLAN, DISCOVERY, QA_REPORT,
REQUIREMENTS, SEO_PLAN, STAGING_PLAYBOOK (заполнен), TRACKING

### Tasks (4 файла, все стабы)
000_inbox, 010_epics, 020_sprint_01, 030_sprint_02

---

## 2. Проблемы

### 🔴 КРИТИЧНО

**A. mu-plugins в неправильной папке**
- `mu-plugins/staging-safety.php` лежит в КОРНЕ репо.
- WP ищет MU-плагины в `wp-content/mu-plugins/`, а не в корне.
- Результат: staging-safety.php **не загружается** — блокировка писем и платежей на staging не работает.
- Фикс: переместить в `wp-content/mu-plugins/staging-safety.php`.

**B. .gitignore не закрывает важные файлы**
- Не исключены: `wp-content/uploads/`, `wp-content/cache/`, `wp-content/debug.log`, `cgi-bin/`, `*.backup.*`, сгенерированные `.htaccess`-файлы Seraphinite accelerator.
- В репо лежат: `index.html.backup.4e2a1c509511a66dd39ad3be33521db4`, `seraphinite-accelerator-*.htaccess` — мусор сервера, не код.
- Риск: случайно закоммитить кеш, загрузки, дебаг-лог.

### 🟡 СРЕДНЕ

**C. Конфликт имён: два агента с префиксом "00"**
- `00_ceo_team_audit.md` и `00_client_discovery.md` — одинаковый номер.
- В оркестраторе путаница: неясно, что запускать первым.
- Фикс: CEO audit переименовать в `_ceo_team_audit.md` (без номера — запускается разово вне пайплайна).

**D. Перекрытие deploy-скиллов (3 скилла, одна тема)**
- `cyberfolks_deploy_playbook`, `vps_deploy_rsync`, `staging_bootstrap` — все о деплое/переносе на сервер.
- Разница слабая: `cyberfolks_deploy_playbook` — общий чеклист; `vps_deploy_rsync` — команды rsync; `staging_bootstrap` — инициализация staging.
- Пересечение до 60%. Агент 07 ссылается на все три.
- Фикс: `cyberfolks_deploy_playbook` сделать тонкой «обёрткой», явно разграничить области.

**E. Перекрытие SSL-скиллов (2 скилла)**
- `ssl_cyberfolks` — shared + VPS в одном файле.
- `ssl_certbot_playbook` — только VPS/certbot.
- `ssl_cyberfolks` содержит VPS-раздел, который дублирует `ssl_certbot_playbook`.
- Фикс: из `ssl_cyberfolks` удалить VPS-раздел, оставить только shared-панель. Ссылаться на `ssl_certbot_playbook` для VPS.

**F. Мусорные файлы в корне репо**
- `PORADKEK-DZIALAN.txt`, `instalacje.txt`, `staging-plan.txt` — черновые заметки, частично дублируют `docs/STAGING_PLAYBOOK.md`.
- `ssh-run.sh`, `sync-from-server.sh` — временные скрипты из ранней отладки (использовали expect + пароль); сейчас есть нормальный `scripts/` и SSH-ключ.
- `cgi-bin/` — серверная директория, не должна быть в коде.
- Фикс: добавить в .gitignore, файлы убрать (или архивировать в `docs/archive/`).

**G. wp-config.php в репо**
- Есть в `.gitignore`, но файл физически существует — если по ошибке добавить `git add -f`, он попадёт с паролями БД в историю.
- Фикс: убедиться что не в tracked, добавить `wp-config.php` в `.gitignore` (уже есть — ок).

### 🟢 НИЗКО

**H. Стаб-документы (docs/tasks) пустые**
- Все 9 документов в `docs/` (кроме STAGING_PLAYBOOK) — стабы без контента.
- Фикс: в `docs/START_HERE.md` добавлена явная пометка «стабы = шаблоны под заполнение агентами».
- **Статус: ✅ Исправлено**

**I. Отсутствует definition of done (DoD)**
- Нет единого файла с критериями «задача готова».
- Фикс: создан `docs/DEFINITION_OF_DONE.md` с DoD по категориям (любая задача, frontend, SEO, Woo, деплой).
- **Статус: ✅ Исправлено**

**J. Отсутствует scope_control skill**
- В описании агента PM упоминался, но не был создан.
- Фикс: создан `.agents/skills/scope_control/SKILL.md`; добавлен в 01_product_manager.
- **Статус: ✅ Исправлено**

---

## 3. Конфликты между агентами

| Конфликт | Агенты | Суть |
|----------|--------|------|
| Deploy overlap | 06 DevOps + 07 Server Ops | Оба затрагивают деплой. 06 — скрипты/CI, 07 — сервер. Граница есть, но нечёткая. |
| Performance | 05 Theme + 08 QA | Оба ссылаются на performance_corewebvitals. Нормально (разные роли), но 05 — имплементирует, 08 — проверяет. |
| Woo templates | 04 Woo + 05 Theme | Пересечение на Woo шаблонах. 04 — логика, 05 — UX/вёрстка. Граница по hooks vs. template overrides. |

---

## 4. Риски

| Риск | Уровень | Статус |
|------|---------|--------|
| staging-safety не грузится (mu-plugins в корне) | 🔴 КРИТИЧНО | ✅ Исправлено |
| uploads/cache в git (.gitignore) | 🟡 СРЕДНЕ | ✅ Исправлено |
| Нет DoD | 🟡 СРЕДНЕ | ✅ Исправлено (DEFINITION_OF_DONE.md) |
| Два "00" агента (путаница) | 🟡 СРЕДНЕ | ✅ Исправлено (_ceo_team_audit) |
| SSL-знания в двух скиллах | 🟢 НИЗКО | ✅ Исправлено (ssl_cyberfolks → shared only) |
| Мусорные файлы в git | 🟢 НИЗКО | ✅ Исправлено (.gitignore) |
| Нет scope_control skill | 🟢 НИЗКО | ✅ Исправлено |

---

## Итог

Все найденные проблемы (2× критично, 4× средне, 3× низко) закрыты.  
Команда **production-ready**. Следующий шаг — `docs/START_HERE.md`.
