# Multi-Agent Plan: Mobile UX/UI + Responsive → Fix → Regression → Push

**Цель:** Довести mobile версию staging.mnsk7-tools.pl до адаптивного, визуально чистого, функционально стабильного состояния; внести правки; прогнать регрессию; push в main.

**Домен:** staging.mnsk7-tools.pl  
**Репозиторий:** текущий worktree (dbb)

---

## Агенты и подзадачи

| # | Агент | Роль | Результат |
|---|--------|------|------------|
| 1 | **ORCHESTRATOR** | План, делегирование, приём результатов, не закрывать без fixes + regression + push | План (этот файл), координация, финальный отчёт |
| 2 | **STACK_DISCOVERY** | Стек, темы, overrides, plugins, CSS/JS, render paths, conditionals, legacy | STACK MAP + render paths |
| 3 | **MOBILE_BROWSER_QA** | Browser tool, viewports 320–430, 768, customer flow | MOBILE QA REPORT (дефекты) |
| 4 | **DESKTOP_BROWSER_QA** | Desktop/tablet, header, menu, search, cart, no mobile controls on desktop | DESKTOP QA REPORT |
| 5 | **DESIGN_SYSTEM** | Качество UI: header, menu, search, cards, chips, footer, spacing, typography | DESIGN REPORT |
| 6 | **CODE_REVIEW** | Root causes, шаблоны, хуки, CSS/JS, body classes, plugin collisions | CODE REVIEW REPORT |
| 7 | **FIX_AGENT** | Исправления по discovery + QA + review, без масок overflow | FIX REPORT |
| 8 | **REGRESSION_AGENT** | Повторная проверка mobile + desktop по матрице | REGRESSION REPORT |
| 9 | **GIT_AGENT** | Commit, push main, hash, summary | GIT RESULT |

---

## Порядок работ

1. **Этап 1 — Plan** ✅ План сформирован (этот документ). Правок до плана не вносить.
2. **Этап 2 — Stack discovery**  
   Стек, темы, WooCommerce overrides, plugins, CSS/JS entry points.  
   Render paths: homepage, cart, PDP, category/tag/taxonomy, URLs с `?filter_*`.  
   Divergent states, legacy branches.
3. **Этап 3 — Browser QA**  
   Mobile (320, 360, 390, 430, 768) + desktop (1280+, 1440+).  
   Customer flow: /, /koszyk/, PDP, archive, filtered archive, search, checkout.  
   Только воспроизводимые дефекты.
4. **Этап 4 — Code review**  
   Привязка дефектов к root cause: шаблоны, хуки, классы, селекторы, JS state.
5. **Этап 5 — Fix implementation**  
   Системные правки по отчётам. Без overflow/scroll как замены вёрстке.
6. **Этап 6 — Regression**  
   Повторный прогон по тест-матрице. Подтверждение: один header, стабильные states, без регрессий.
7. **Этап 7 — Commit + push**  
   Один осмысленный commit, push в main.

---

## Тест-матрица

**URLs:**  
`/`, `/koszyk/`, PDP, category archive, tag archive, taxonomy archive, URL с `?filter_...`, search results, checkout/pre-checkout (если есть).

**Viewports:**  
320×568, 360×800, 390×844, 430×932, 768×1024, 1280+ desktop, 1440+ desktop.

---

## Rules для всех агентов

- Не перекладывать диагностику на пользователя.
- Один `get_header()` не доказывает одинаковый final render — смотреть DOM, classes, hooks, conditionals, plugin output, JS.
- Не лечить layout через `overflow-x: auto`, внутренние scrollbars в UI-блоках, обрезку, `hidden` как маскировку.
- Не добавлять desktop burger и лишние controls без запроса.
- Не оставлять мёртвые controls.
- Не возвращать старый broken header.
- Не закрывать задачу без Browser-проверки, regression, commit и push.

---

## Критерии приёмки

- Mobile customer flow проверен в браузере.
- Адаптив на всей тест-матрице.
- Desktop не сломан mobile fixes.
- Старый broken header не всплывает.
- Нет мёртвых controls и случайных внутренних scrollbars в UI.
- Search/menu/header states чистые и системные.
- Cards/buttons/chips/footer консистентны.
- Code review с указанием стека и root causes.
- Fixes внесены, regression выполнен, commit и push в main сделаны.
