# Аудит: Upsell, Cross-sell, Related, Bestsellery (март 2026)

Проверка блоков рекомендаций товаров: где должны быть, где находятся, цена/кнопка «Купить», вёрстка и мобильная версия.

---

## 1. Где блоки должны располагаться (best practice e-commerce)

| Блок | Где должен быть | Назначение |
|------|-----------------|------------|
| **Upsells** («Może spodoba się również…») | PDP, сразу под описанием (или под табами), **перед** Related | Товары из настроек «Upsells» у продукта — допродажи. Выше = выше конверсия. |
| **Related** («Podobne produkty» / «Z tej samej kategorii») | PDP, под Upsells | Товары из той же категории. Логичный порядок: сначала «допродажи», потом «похожие». |
| **Cross-sells** | Корзина (/koszyk/) | Товары «К этому товару часто докупают» — только на странице корзины. |
| **Bestsellery i polecane** | Главная страница | Блок хитов и рекомендаций после hero; не на PDP. |

**Рекомендуемый порядок на PDP:**  
Описание (accordion «Pokaż opis») → **Upsells** → **Related**.

---

## 2. Где блоки располагаются сейчас (по коду)

### PDP (single product)

- **Хуки** `woocommerce_after_single_product_summary`:
  - **10** — описание (accordion, вместо табов)
  - **15** — upsells (стандарт WooCommerce)
  - **25** — related (перенесён с 20 в теме)

Итог: **Описание → Upsells → Related** — порядок корректный.

### Корзина

- Cross-sells выводятся WooCommerce в сайдбаре или под списком товаров (зависит от темы). Стили в `12-related-products.css` и `06-single-product.css` для `.cross-sells` заданы.

### Главная

- Секция «Bestsellery i polecane» в `front-page.php`, короткокод `[mnsk7_bestsellers limit="6" title="Bestsellery i polecane"]` — второй блок после hero. Ок.

---

## 3. Заголовки и подзаголовки

| Блок | Заголовок (H2) | Подзаголовок | Где задаётся |
|------|----------------|---------------|--------------|
| Upsells | «Może spodoba się również…» (или из перевода Woo) | Нет в стандартном шаблоне | WooCommerce: фильтр `woocommerce_product_upsells_products_heading` (или аналог в переводах) |
| Related | «Podobne produkty» (Related products) | «Z tej samej kategorii» | Тема: `related.php` + фильтр `mnsk7_related_products_subtitle` |
| Bestsellers | «Bestsellery i polecane» | — | `front-page.php`: атрибут shortcode `title="Bestsellery i polecane"` |

Для единообразия у Upsells добавлен переопределённый шаблон в теме с подзаголовком (опционально) и фильтром заголовка.

---

## 4. Цена и кнопка «Купить»

| Место | Цена | Кнопка «Dodaj do koszyka» |
|-------|------|---------------------------|
| **Related** (PDP) | В `12-related-products.css`: `.related.products ... .price` — `display: block !important`, цвет, жирность | `.related.products .add_to_cart_button` — блок, ширина 100%, padding |
| **Upsells** (PDP) | Те же стили `.upsells.products ... .price` | `.upsells.products .add_to_cart_button` |
| **Cross-sells** (корзина) | `.cross-sells ... .price` | `.cross-sells .add_to_cart_button` |
| **Bestsellers** (главная) | В `08-home-sections.css`: `.mnsk7-section--bestsellers .price` — видимая, жирная | Кнопка наследуется от `.woocommerce ul.products li.product .button` (05-plp-cards) |

Проблемы, которые исправлены:

- Убедиться, что кнопка в блоках related/upsells не скрыта и не «сломана» (writing-mode, ширина).
- Bestsellers: сетка приведена к grid (как PLP/related) для ровных колонок и мобильной версии.

---

## 5. Вёрстка: размер, ровность, мобильная версия

### PDP (Related + Upsells)

- **Десктоп:** 4 колонки, `grid-template-columns: repeat(4, minmax(0, 1fr))`, gap 1.25rem (`06-single-product.css`).
- **Планшет (≤900px):** 2 колонки.
- **Мобильный (≤480px):** 1 колонка (также в `21-responsive-mobile.css` для общих селекторов).
- Карточки: flex, column, одинаковый стиль (05-plp-cards, 12-related-products). Один товар в upsells — одна колонка (`:has(li:only-child)`).

### Bestsellers (главная)

- **Было:** flex + `width: calc(25% - 0.75rem)`, max-width 280px — на части разрешений могло давать неровную сетку.
- **Стало:** grid как в PLP/related: 4 → 2 → 1 колонка, те же breakpoints, единый вид с PDP и категорией.

### Cross-sells (корзина)

- Стили общие с related/upsells; на корзине своя ширина и grid в `12-related-products.css`. Адаптив общий (2 колонки → 1).

---

## 6. Внесённые правки (кратко)

1. **Шаблон upsells:** добавлен `woocommerce/single-product/up-sells.php` в теме с подзаголовком «Dopasowane do tego produktu» (фильтр `mnsk7_upsells_subtitle`) и разметкой как у related.
2. **Заголовок upsells:** в `functions.php` темы добавлен фильтр `woocommerce_product_upsells_products_heading`: «Może spodoba się również…».
3. **Bestsellers:** в `08-home-sections.css` блок переведён на grid (4/2/1 колонки), убраны flex и `calc(25%)` — ровная сетка и консистентность с PLP/PDP; явные стили кнопки «Dodaj do koszyka».
4. **Подзаголовок upsells:** в `12-related-products.css` для `.upsells.products .related-products__subtitle` заданы те же отступы, что у related.
5. **PDP:** в `06-single-product.css` добавлен селектор `.up-sells.products` рядом с `.upsells.products` для единого отображения.
6. **Кнопка и цена:** в related/upsells/cross-sells уже заданы в 12-related-products (цена видима, кнопка с `writing-mode: horizontal-tb`, ширина 100%); bestsellers дополнены явным отображением кнопки.

---

## 7. Чеклист проверки после правок

- [ ] PDP с upsells: блок «Może spodoba się również…» идёт сразу под описанием, затем «Podobne produkty» с подзаголовком «Z tej samej kategorii».
- [ ] На PDP у карточек related/upsells видна цена и кнопка «Dodaj do koszyka».
- [ ] Главная: блок «Bestsellery i polecane» — 4 колонки на десктопе, 2 на планшете, 1 на мобильном; карточки ровные.
- [ ] Корзина: cross-sells (если есть) — цена и кнопка отображаются, сетка не ломается на 360px.
- [ ] 360px: все блоки в одну колонку, без горизонтального скролла и обрезания кнопки.
