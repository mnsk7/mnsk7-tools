# .agents

Агенты и скиллы для проекта mnsk7-tools.pl (WordPress + WooCommerce, GitHub, VPS/SSH, staging).

## Три слоя skills

1. **Официальные WP skills** (скачиваемые) — чтобы агент не генерировал устаревший WP-код.  
   Установлены в `.agents/skills/`: wordpress-router, wp-project-triage, wp-plugin-development, wp-wpcli-and-ops, wp-performance, wp-phpstan, wpds.  
   Повторная установка (в корне проекта):
   ```bash
   npx skills add https://github.com/WordPress/agent-skills --agent cursor -y \
     --skill wordpress-router --skill wp-project-triage --skill wp-plugin-development \
     --skill wp-wpcli-and-ops --skill wp-performance --skill wp-phpstan --skill wpds
   ```
   Флаг `-y` — без интерактивных вопросов.

2. **Ops skills** — wp-cli, миграции, деплой на VPS (часть в WP repo, часть project).

3. **Project skills** — фрезы, каталог, SEO, контент, конверсия, staging, certbot, ssh key auth (в `.agents/skills/`).

Правила написания кода в репо — в **.cursorrules** (не путать со skills).

## Структура

```
.agents/
  README.md           — этот файл
  orchestrator.md     — порядок запуска агентов, формат вывода
  agents/            — описания агентов (00 CEO audit + 00–08)
  skills/            — SKILL.md project (фрезы, каталог, SEO, deploy, certbot, requirements_to_tasks, …)
docs/                — выходы агентов: DISCOVERY, REQUIREMENTS, ARCHITECTURE, планы, отчёты
tasks/               — эпики, спринты, inbox
```

## Агенты

| # | Агент | Выход |
|---|--------|--------|
| — | CEO / Team Audit (_ceo) | TEAM_AUDIT, TEAM_FIX_PLAN, TEAM_READINESS, START_HERE (разовый, вне пайплайна) |
| 00 | As-Is Audit | AS_IS_AUDIT, AS_IS_BACKLOG, AS_IS_RISKS |
| 00 | Client Discovery | DISCOVERY_GAP_ANALYSIS.md, DISCOVERY.md, REQUIREMENTS.md (проверка закрытости интервью) |
| 01 | Product Manager | epics, sprint_01, sprint_02 |
| 02 | Growth & SEO | SEO_PLAN, CONTENT_PLAN, TRACKING |
| 03 | WP Architect | ARCHITECTURE, BACKLOG |
| 04 | Woo Engineer | **код:** PHP (mu-plugin, хуки Woo), шаблоны Woo (чекаут, каталог, карточка); задачи в sprint |
| 05 | Theme & UX Frontend | **код:** тема (overrides, CSS/JS), вёрстка категории и карточки, мобильный UX |
| 09 | UI Designer | **не код:** UI_SPEC, гайды header/footer/карточка/главная, визуальные рекомендации |
| 06 | DevOps GitHub | ветки, PR, Actions |
| 07 | Server Ops CyberFolks | playbook, инструкции, бэкапы |
| 08 | QA & Security | QA_REPORT, чеклисты, inbox |

Порядок работы — см. **orchestrator.md**.

**Кто пишет код:** 04_woo_engineer (логика Woo, шаблоны); 05_theme_ux_frontend (тема, вёрстка). **09_ui_designer** только спецификация и гайды (UI_SPEC), без кода. 06 — CI (workflows, PR template).
