# Orchestrator (ручная оркестрация в Cursor)

Правило: все результаты фиксируем файлами в /docs и /tasks.  
Каждый агент работает только со своей областью.

## Порядок

0) **_ceo_team_audit** (разово / при смене команды, вне нумерации) → TEAM_AUDIT.md, TEAM_FIX_PLAN.md, TEAM_READINESS.md, START_HERE.md. Не трогает боевой код, только агенты/skills/rules/docs.

1) **00_client_discovery** → /docs/DISCOVERY.md + /docs/REQUIREMENTS.md (черновик)
2) **02_growth_seo** → /docs/SEO_PLAN.md + /docs/CONTENT_PLAN.md + /docs/TRACKING.md
3) **03_wp_architect** → /docs/ARCHITECTURE.md + /docs/BACKLOG.md
4) **01_product_manager** → /tasks/010_epics.md + /tasks/020_sprint_01.md
5) **05_theme_ux_frontend** + **04_woo_engineer** → код + коммиты
6) **08_qa_security** → QA отчёт в /docs/QA_REPORT.md (создать при первом запуске)
7) **06_devops_github** + **07_server_ops_cyberfolks** → staging deploy

## Формат вывода агентов

- **Если пишем документ:** вставь готовый markdown и скажи «Сохранить в &lt;path&gt;».
- **Если задача:** добавь item в /tasks/000_inbox.md или /tasks/020_sprint_01.md.
