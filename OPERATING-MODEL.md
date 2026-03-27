# Operating Model (mnsk7-tools.pl)

## Что это

Этот файл — **единый источник истины** о том, **как мы делаем изменения** в этом репозитории так, чтобы результат был **готов к деплою** и не ломал конверсию WooCommerce.

Код и “кажется ок” не являются доказательством качества. В owner-flow predeploy решение принимается Critic+Verifier по diff+контексту, а истина по регрессиям закрепляется **post-deploy verify на staging**.

## Runtime stance

- Основной путь — **Cursor-native execution** (агенты/скиллы/инструменты).
- Репозиторий добавляет **тонкий governance слой**: контракты, проверочные уровни, гейты, петли.
- Ручной “дисковый” пайплайн (артефакты на диске) допустим как **fallback** для аудита, но не заменяет нативные возможности.

## Канонические источники (SSOT)

| Тема | Канонично |
| --- | --- |
| Инженерные ограничения WP/Woo + что можно деплоить | `.cursorrules` |
| Quality gates / кто блокирует релиз | `docs/QUALITY_GATES.md` |
| Definition of Done | `docs/DEFINITION_OF_DONE.md` |
| Bug discovery acceptance (process vs product) | `docs/BUG_DISCOVERY_ACCEPTANCE.md` |
| E2E запуск/репорты | `docs/E2E-WORKFLOW.md` |
| Staging/deploy safety | `docs/DEPLOY_SAFETY.md`, `docs/DEPLOY_PLAYBOOK.md`, `.github/workflows/deploy-staging.yml` |
| Multi-level verify (L0/L1/L2) + blocking rules | `.cursor/rules/60-verify-levels.mdc` |
| Mandatory verify+critic loop | `.cursor/rules/85-verify-critic-loop.mdc` |
| Owner pipeline autostart (orchestrator->...->post-deploy verify) | `.cursor/rules/10-autostart-pipeline.mdc` |
| Agent roles / ожидания поведения | `AGENTS.md` + `.cursor/agents/*` |

## Минимальные роли (v3.0 core)

- **Orchestrator**: классифицирует задачу (режим/скоуп), собирает контекст, запускает петли, держит лимиты итераций/эскалацию.
- **Analyzer (multi-domain)**: возвращает **строго структурированный issue map** с evidence и списком тестов, которые надо добавить.
- **Critic+Scorer (phase 1)**: gate полноты анализа — “не починил 1 баг, оставив 10”.
- **Doer**: делает изменения **только** по финальному issue list, минимальным безопасным diff.
- **Critic + Verifier (predeploy, practical+technical, no-tests)**: оценивает diff/контекст/логи без локального e2e/`verify:*`.
- **Technical Verify (L0/L1/L2, post-deploy)**: истина о качестве из инструментов на staging.
- **Product Verifier (post-deploy)**: истина о продукте (owner-баги и новые дефекты, найденные агентом).
- **Critic+Scorer (phase 2)**: оценивает остатки/регрессии по staging verify-отчёту; применяет blocking rules и score gate.

## Default task flow (с петлями)

1. **Raw TASK** (в любой форме).
2. **Orchestrator** → собирает контекст + определяет `task_mode`, `task_scope`, targets (URLs), ограничения (что можно/нельзя трогать).
3. **Analyzer** → `issue_map` (JSON) + `tests_to_add`.
4. **Critic+Scorer phase 1** → completeness gate:
   - если `ok_to_proceed=false` → возврат к Analyzer.
5. **Final Issue List** (приоритизированный).
6. **Doer** → код (минимальный безопасный diff) без обязательных локальных e2e/verify.
7. **Critic + Verifier (predeploy, practical+technical, no-tests)**:
   - проверка только по diff/контексту/логам
   - решение: готово/не готово к push/deploy
8. **Push/merge в main** -> деплой на staging.
9. **Technical Verify multi-level на staging**:
   - L0: линты/статические/быстрые аудиты
   - L1: Playwright критические user flows Woo
   - L2: visual/perf budgets и т.п.
10. **Product Verifier (post-deploy)**:
   - owner bug ledger с явными статусами `fixed|partially_fixed|not_fixed`
   - unknown bug hunt: обязательный список новых дефектов без owner hints
11. **Critic+Scorer phase 2** → score + blocking:
   - если blocking → **REJECT (score=0)** и возвращаемся к финальному issue list
   - иначе: считаем `PROCESS_ACCEPT=true`
12. **Dual verdict**:
   - `PROCESS_ACCEPT` (пайплайн и verify-gates)
   - `PRODUCT_ACCEPT` (реальное закрытие owner-багов + discovery-блок)
   - финальный `ACCEPT` возможен только если оба true
13. **Snapshot governance**:
   - baseline update разрешён только после product signoff, что это целевой UI-state
14. **Если REJECT/ESCALATE/major** -> зафиксировать postmortem в `docs/CRITIC_POSTMORTEMS.md`.

## Принципы (что создаёт качество)

- строгие **контракты данных** между этапами (JSON)
- многоуровневые **проверки инструментами**
- **блокирующие правила** (конверсионные флоу)
- честная фиксация gaps и ограничений

## Анти-принципы

- “побольше агентов” вместо доказательств
- принятие по vibes
- prompt-only бизнес-логика
- маскировка дефекта (scroll/hidden/“вроде стало лучше”) вместо исправления причины

