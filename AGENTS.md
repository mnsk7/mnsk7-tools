# AGENTS.md (mnsk7-tools.pl — WordPress + WooCommerce)

## Назначение проекта

Репозиторий содержит код и операционные артефакты для **staging.mnsk7-tools.pl** (WordPress + WooCommerce).

## Глобальные правила проекта (обязательные)

1. **Не редактировать ядро WordPress и файлы сторонних плагинов.** Только:
   - `wp-content/themes/mnsk7-storefront`
   - `wp-content/themes/storefront` (держим в репо и деплоим вместе, если присутствует в репо)
   - `wp-content/mu-plugins`
   - `wp-content/plugins/<custom>` (если есть)
2. Любая кастом-логика WooCommerce — через hooks/filters либо в кастом-плагине проекта.
3. Для staging обязателен `mu-plugin` staging safety (почта/платежи/`blog_public=0`), см. `.cursorrules` и `docs/DEPLOY_SAFETY.md`.
4. Не принимать результат, если сломан основной конверсионный флоу Woo:
   - add to cart (из PLP и PDP)
   - cart update
   - checkout entry (страница оформления открывается и форма присутствует)
5. Предпочитать **маленькие, аудируемые изменения** вместо больших переписываний.
6. Любое изменение, влияющее на runtime/контракты/деплой, должно быть отражено в соответствующих документах (см. `OPERATING-MODEL.md`).

## Ожидаемое поведение агентов (встроенные гейты)

- После любого изменения в зоне действия `.cursor/rules/85-verify-critic-loop.mdc`:
  - выполнить predeploy review: critic + verifier (practical+technical) по diff/контексту/логам (без обязательного локального e2e/verify)
  - при OK сделать push/merge в `main` и деплой на staging
  - выполнить post-deploy technical verify уровней L0/L1/L2 по необходимости
  - выполнить post-deploy product verifier: owner bug ledger + agent-found bugs (без подсказок owner)
  - затем выполнить adversarial review (Critic+Scorer phase 2)
  - после фиксов **повторить** цикл predeploy review -> deploy -> post-deploy verify -> product verifier -> critic
  - финальный `ACCEPT` только при двух статусах: `PROCESS_ACCEPT=true` и `PRODUCT_ACCEPT=true`

## Инженерные ограничения

- Источник истины по деплою: `main` + GitHub Actions (`.github/workflows/deploy-staging.yml`).
- E2E: Playwright (`e2e/`, `playwright.config.js`).
- Деплой: rsync (тема + mu-plugins), без “докидывания” случайных файлов.

