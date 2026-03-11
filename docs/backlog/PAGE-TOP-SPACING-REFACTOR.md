# Backlog: PAGE-TOP-SPACING-REFACTOR

**Цель:** убрать дублирование правил для `#content` между `25-global-layout.css` и `05-plp-cards.css` и определить один canonical source of truth для global page-top spacing по всей теме.

**Scope:** не входит compact archive rhythm (архивы уже переопределены в 24-plp-table.css). Задача только про глобальный отступ «header → первый контент» для страниц: page, account, contact, WooCommerce (shop/cart/checkout/PDP), кроме архивов товаров, где действует свой контракт.

---

## Что сделать

1. **Убрать дубли**
   - Сейчас `padding-top` для `#content` / `.mnsk7-content` задаётся в двух местах:
     - **25-global-layout.css:** `#content`, `.site-content`, `.mnsk7-content` → `padding-top: var(--space-page-top)` (2rem).
     - **05-plp-cards.css:** `body.woocommerce #content`, `body.woocommerce .mnsk7-content` → `padding: var(--space-page-top) 1.5rem 1.5rem`.
   - Определить один файл как canonical (рекомендация: 25-global-layout как общий для всей темы; 05-plp-cards оставить только flex/gap/боковые отступы для Woo, без дублирования padding-top).
   - Второй источник удалить или перевести в явный fallback (если по какой-то причине нужен).

2. **Один canonical source of truth**
   - Все типы страниц (page, dostawa, kontakt, my-account, shop, cart, checkout, PDP) должны получать page-top из одного места (один селектор или одна точка расширения).
   - Архивы товаров не трогать: у них свой compact rhythm в 24-plp-table.css.

3. **Документация**
   - Описать решение отдельно от archive rhythm (не смешивать с PLP-ARCHIVE-VERTICAL-RHYTHM-HANDOFF.md).
   - Указать: какой файл/селектор считается canonical для global page-top; при необходимости обновить PAGE-TOP-SPACING-REFACTOR.md (или аналог) под новый контракт.

---

## Связь

- Текущее состояние page-top и токены: [PAGE-TOP-SPACING-REFACTOR.md](../PAGE-TOP-SPACING-REFACTOR.md) (если есть).
- Archive rhythm (вне scope этой задачи): [PLP-ARCHIVE-VERTICAL-RHYTHM-HANDOFF.md](../PLP-ARCHIVE-VERTICAL-RHYTHM-HANDOFF.md).

---

## Критерии готовности

- [x] В коде не более одного места, задающего `padding-top` для `#content`/`.mnsk7-content` в глобальном контексте (без учёта archive override в 24-plp-table). **Сделано:** canonical — 25-global-layout.css; в 05-plp-cards убрано задание padding-top для Woo.
- [ ] Визуально: page, account, contact, shop (без taxonomy), cart, checkout, PDP — без изменений отступов от текущего состояния (рекомендуется ручная проверка).
- [x] Документ с описанием canonical source: обновлён [PAGE-TOP-SPACING-REFACTOR.md](../PAGE-TOP-SPACING-REFACTOR.md).
