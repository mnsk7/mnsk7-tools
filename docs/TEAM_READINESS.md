# Team Readiness — mnsk7-tools.pl

Дата: 2026-03-04

---

## Статус по зонам

| Зона | Агент | Skills | Готовность |
|------|-------|--------|-----------|
| Дискавери | 00_client_discovery | discovery_frezes, marketing_positioning, content_catalog_rules | ✅ Готов к запуску |
| PM / Backlog | 01_product_manager | requirements_to_tasks, marketing_positioning, seo_woocommerce | ✅ Готов (нужен REQUIREMENTS.md) |
| SEO + Контент | 02_growth_seo | seo_woocommerce, content_catalog_rules, performance_corewebvitals | ✅ Готов к запуску |
| Архитектура WP | 03_wp_architect | wp_theme_architecture, content_catalog_rules, security_wp_baseline | ✅ Готов (нужен REQUIREMENTS.md) |
| Woo-логика | 04_woo_engineer | woo_templates_checkout, woo_loyalty_design, content_catalog_rules | ✅ Готов (нужен ARCHITECTURE.md) |
| Тема / UX | 05_theme_ux_frontend | wp_theme_architecture, performance_corewebvitals, seo_woocommerce | ✅ Готов (нужен ARCHITECTURE.md) |
| DevOps / CI | 06_devops_github | gitflow_prs, github_actions_wp, security_wp_baseline | ✅ Готов |
| Сервер / Деплой | 07_server_ops_cyberfolks | cyberfolks_deploy_playbook, vps_deploy_rsync, staging_bootstrap | ✅ Готов |
| QA / Безопасность | 08_qa_security | qa_smoke_woo, security_wp_baseline, performance_corewebvitals | ✅ Готов |

---

## Инфраструктура

| Элемент | Статус | Заметка |
|---------|--------|---------|
| Репо с кодом сайта | ✅ | Файлы скачаны с сервера |
| .gitignore | ✅ | uploads, cache, cgi-bin, мусор закрыты |
| .cursorrules | ✅ | Правила кода (no core edits, hooks only) |
| staging-safety.php | ✅ | Лежит в wp-content/mu-plugins/ |
| Makefile + scripts/ | ✅ | make staging-refresh, make deploy-files |
| SSH-ключ | ⚠️ | Настроить вручную (см. ssh_key_auth_playbook) |
| staging.mnsk7-tools.pl | ⚠️ | Нужно создать в DirectAdmin + отдельная БД |
| SSL staging | ⚠️ | Включить в панели DirectAdmin (Let's Encrypt) |
| GitHub репо | ⚠️ | Создать + первый push |

---

## Skills — полный список (21)

**Project skills** (в .agents/skills/):  
content_catalog_rules, cyberfolks_deploy_playbook, db_provision_mysql, discovery_frezes,
gitflow_prs, github_actions_wp, marketing_positioning, performance_corewebvitals, qa_smoke_woo,
requirements_to_tasks, scope_control, security_wp_baseline, seo_woocommerce, ssh_key_auth_playbook,
ssl_certbot_playbook, ssl_cyberfolks, staging_bootstrap, team_audit, vps_deploy_rsync,
woo_loyalty_design, woo_templates_checkout, wp_theme_architecture, wpcli_db_migrations

**WP official skills** (установить через npx):  
`npx skills add https://github.com/WordPress/agent-skills --agent cursor --skill wordpress-router --skill wp-project-triage --skill wp-plugin-development --skill wp-wpcli-and-ops --skill wp-performance --skill wp-phpstan --skill wpds`

---

## Docs / Tasks статус

| Файл | Статус |
|------|--------|
| docs/DISCOVERY.md | 📋 Стаб — заполнит 00_client_discovery |
| docs/REQUIREMENTS.md | 📋 Стаб — заполнит 00_client_discovery |
| docs/SEO_PLAN.md | 📋 Стаб — заполнит 02_growth_seo |
| docs/CONTENT_PLAN.md | 📋 Стаб — заполнит 02_growth_seo |
| docs/TRACKING.md | 📋 Стаб — заполнит 02_growth_seo |
| docs/ARCHITECTURE.md | 📋 Стаб — заполнит 03_wp_architect |
| docs/BACKLOG.md | 📋 Стаб — заполнит 03_wp_architect |
| docs/STAGING_PLAYBOOK.md | ✅ Заполнен |
| docs/DEFINITION_OF_DONE.md | ✅ Создан |
| docs/QA_REPORT.md | 📋 Стаб — заполнит 08_qa_security |
| tasks/000_inbox.md | 📋 Пустой inbox |
| tasks/010_epics.md | 📋 Стаб — заполнит 01_product_manager |
| tasks/020_sprint_01.md | 📋 Стаб — заполнит 01_product_manager |
| tasks/030_sprint_02.md | 📋 Стаб — заполнит 01_product_manager |
