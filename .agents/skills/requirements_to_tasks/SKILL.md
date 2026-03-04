---
name: requirements_to_tasks
description: Превращает REQUIREMENTS/SEO_PLAN в backlog и спринты в /tasks/*.md
---

# Requirements -> Tasks

## Input
- /docs/REQUIREMENTS.md
- /docs/SEO_PLAN.md
- /docs/ARCHITECTURE.md (если есть)

## Output
- /tasks/010_epics.md
- /tasks/020_sprint_01.md
- /tasks/030_sprint_02.md

## Правила декомпозиции
- Каждая задача: цель, критерии готовности, файлы/папки, как тестировать, риск.
- Отдельно задачи: theme, woo, seo-content, ops, qa.
- Приоритет: (1) покупка/чекаут (2) подбор/каталог (3) SEO категорий (4) лояльность.
