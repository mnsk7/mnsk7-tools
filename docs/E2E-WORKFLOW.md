# E2E workflow: запуск тестов → результаты → предложения по исправлениям

## Как пользоваться

### 1. Запустить тесты и получить предложения

```bash
# Все E2E (header-layout + mobile-design), chromium
./scripts/e2e-run-and-suggest.sh

# Только хедер
./scripts/e2e-run-and-suggest.sh e2e/header-layout.spec.js

# Только mobile-design
./scripts/e2e-run-and-suggest.sh e2e/mobile-design.spec.js

# Свой BASE_URL
BASE_URL=https://staging.mnsk7-tools.pl ./scripts/e2e-run-and-suggest.sh
```

Скрипт:
1. Запускает `npx playwright test ... --reporter=list` и пишет вывод в `test-results/e2e-run.log`.
2. Запускает `node scripts/e2e-suggest.js`, который разбирает лог и пишет в `test-results/e2e-suggestions.md`:
   - сводку (passed / failed / skipped);
   - список упавших тестов;
   - **предложения по исправлениям** по ключевым словам (overlap, toHaveScreenshot, cart does not extend, и т.д.).

### 2. Только разобрать уже сохранённый лог

Если лог уже есть (например, после ручного запуска Playwright):

```bash
node scripts/e2e-suggest.js test-results/e2e-run.log test-results/e2e-suggestions.md
```

### 3. Обновить визуальные снапшоты

Если падают тесты `toHaveScreenshot`:

```bash
npx playwright test e2e/header-layout.spec.js --update-snapshots
```

## Где что лежит

| Файл | Назначение |
|------|------------|
| `test-results/e2e-run.log` | Полный вывод последнего прогона (list reporter). |
| `test-results/e2e-suggestions.md` | Сводка + предложения по исправлениям. |
| `e2e/header-layout.spec.js-snapshots/` | Базовые скриншоты для visual regression. |

## Что делают тесты

- **header-layout.spec.js** — layout хедера: одна строка controls на mobile, нет overlap, cart в viewport, desktop regression, скриншоты.
- **mobile-design.spec.js** — общий мобильный дизайн: хедер, hero, нет overlap с контентом, overflow-x, PLP, футер, порядок секций.

После прогона смотрите `test-results/e2e-suggestions.md` и применяйте предложенные правки (CSS/селекторы/допуски в тестах).
