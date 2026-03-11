# Multi-Agent Workflow: Mobile UX/UI + Responsive — план работ

**Цель:** довести mobile до адаптивного, визуально чистого, функционально стабильного состояния; внести fixes; regression; commit + push в main.

**Домен:** staging.mnsk7-tools.pl  
**Репозиторий:** текущий worktree (did)

---

## Этап 1. Plan Mode (текущий документ)

### Агенты и роли

| Агент | Роль | Результат |
|-------|------|------------|
| **ORCHESTRATOR** | Держит план, делегирует, не закрывает без fixes + regression + push | План, координация, финальный отчёт |
| **STACK_DISCOVERY** | Стек, темы, overrides, plugins, CSS/JS entry points; render paths (homepage, cart, PDP, archive, tag, ?filter_*) | STACK MAP + render paths + legacy branches |
| **MOBILE_BROWSER_QA** | Browser tool, viewports 320/360/390/430/768; customer flow; воспроизводимые дефекты | MOBILE QA REPORT |
| **DESKTOP_BROWSER_QA** | Desktop/tablet; header, dropdown, search, cart; отловить mobile controls на desktop, старый header | DESKTOP QA REPORT |
| **DESIGN_SYSTEM** | Качество UI: header, menu, search, cards, chips, footer; системные дефекты (кустарные состояния, артефакты) | DESIGN REPORT |
| **CODE_REVIEW** | Root causes: templates, hooks, conditionals, body classes, CSS cascade, JS state, plugin collisions, legacy | CODE REVIEW REPORT |
| **FIX** | Исправления после discovery + QA + review; не симптомы, не overflow-mask | FIX REPORT |
| **REGRESSION** | Повторный прогон mobile + desktop по тест-матрице | REGRESSION REPORT |
| **GIT** | Commit, push main, hash + summary | GIT RESULT |

### Обязательные Rules для всех

1. Не перекладывать диагностику на пользователя.
2. Не считать «один get_header()» доказательством одинакового header — анализ final render (DOM, classes, hooks, conditionals, plugin output, JS init).
3. Не лечить layout через overflow-x: auto, overflow: scroll, внутренние scrollbars, hidden как маскировку.
4. Не добавлять desktop burger / новые controls без запроса.
5. Не оставлять мёртвые controls.
6. Не возвращать старый broken header.
7. Не закрывать задачу без Browser-проверки, regression, commit + push.

### Тест-матрица

**URL:** /, /koszyk/, PDP, category archive, tag/taxonomy archive, ?filter_..., search, checkout/pre-checkout.

**Viewports:** 320×568, 360×800, 390×844, 430×932, 768×1024, 1280+, 1440+.

---

## Этап 2. Stack discovery + render path discovery

- Определить: WordPress + WooCommerce, child theme mnsk7-storefront (parent Storefront).
- Map: theme templates, WooCommerce overrides, mu-plugins, CSS parts 00–24, JS inline.
- Выявить: где final DOM/state отличается (home vs cart vs archive vs ?filter_*).
- Legacy branches: старый header, разные conditionals по URL.

---

## Этап 3. Browser QA mobile + desktop

- MOBILE_BROWSER_QA: пройти flow homepage → menu → search → archive → filtered → PDP → cart → checkout; viewports из матрицы.
- DESKTOP_BROWSER_QA: header, dropdown, search, cart, PDP, archive; отсутствие mobile controls на desktop.

---

## Этап 4. Code review

- Привязка дефектов к стеку.
- Root causes: файлы, хуки, conditionals, body_class, CSS specificity, JS init/state, plugin markup.
- Отдельно: почему на части страниц другой header; почему ?filter_* меняет state; почему cart/home/archive визуально отличаются.

---

## Этап 5. Fix implementation

- Только после discovery + QA + code review.
- Системные исправления; не маскировка overflow; не оставлять старые render paths.

---

## Этап 6. Regression

- Повторный прогон по тест-матрице (URL + viewports).
- Подтверждение: старый header не всплывает; ?filter_* не ломает; mobile/desktop устойчивы; customer flow жив.

---

## Этап 7. Commit + push

- Понятный commit message.
- Push в main.
- Выдать commit hash и summary.

---

## Acceptance criteria (задача выполнена только если)

- Mobile customer flow проверен через browser.
- Mobile layout адаптивен на всей test matrix.
- Desktop не сломан mobile fixes.
- Старый broken header не всплывает.
- Нет мёртвых controls, нет случайных внутренних scrollbars в UI-блоках.
- Search/menu/header states чистые и системные.
- Cards/buttons/chips/footer консистентны.
- Code review с указанием стека и root causes.
- Fixes внесены, regression выполнен, commit и push в main сделаны.
