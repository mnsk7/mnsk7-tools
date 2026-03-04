---
name: team_audit
description: Ревизия агентной команды под конкретный проект: убрать лишнее, добавить нужное, собрать readiness pack.
---

# Team audit checklist

## Удалить/объединить
- дублирующие skills (одно и то же разными словами)
- skills без применимых шагов (нет команды/нет артефакта)
- агенты с пересекающимися зонами ответственности

## Добавить (минимум)
- START_HERE.md
- шаблон DISCOVERY.md / REQUIREMENTS.md / SEO_PLAN.md
- staging-safety чеклист
- deploy+rollback playbook
- definition of done (DoD) для задач

## Проверки
- есть ли единая структура артефактов (docs/tasks)
- есть ли правила «что НЕ делаем» (plugin policy, no core edits)
- есть ли гарантии безопасности staging (noindex, mail/payments block)
