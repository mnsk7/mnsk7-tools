# Orchestrator (ручная оркестрация в Cursor)

Правило: все результаты фиксируем файлами в /docs и /tasks.  
Каждый агент работает только со своей областью.

## Порядок

0) **_ceo_team_audit** (разово / при смене команды, вне нумерации) → TEAM_AUDIT.md, TEAM_FIX_PLAN.md, TEAM_READINESS.md, START_HERE.md. Не трогает боевой код, только агенты/skills/rules/docs.

1) **00_as_is_audit** → /docs/AS_IS_AUDIT.md + /docs/AS_IS_BACKLOG.md + /docs/AS_IS_RISKS.md  
   _(снимает реальность: атрибуты, плагины, тема, SEO, performance — до любого планирования)_

2) **00_client_discovery** → /docs/DISCOVERY_GAP_ANALYSIS.md + /docs/DISCOVERY.md + /docs/REQUIREMENTS.md  
   _(вход: CLIENT_INTERVIEW_SUMMARY.md; проверяет, что интервью закрывает все вопросы дискавери; фиксирует открытые пункты)_

3) **02_growth_seo** → /docs/SEO_PLAN.md + /docs/CONTENT_PLAN.md + /docs/TRACKING.md

4) **03_wp_architect** → /docs/ARCHITECTURE.md + /docs/BACKLOG.md

5) **01_product_manager** → /tasks/010_epics.md + /tasks/020_sprint_01.md

6) **05_theme_ux_frontend** + **04_woo_engineer** → код + коммиты

7) **08_qa_security** → QA отчёт в /docs/QA_REPORT.md

8) **06_devops_github** + **07_server_ops_cyberfolks** → staging deploy

## Формат вывода агентов

- **Если пишем документ:** вставь готовый markdown и скажи «Сохранить в &lt;path&gt;».
- **Если задача:** добавь item в /tasks/000_inbox.md или /tasks/020_sprint_01.md.
