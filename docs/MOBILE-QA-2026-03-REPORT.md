# Mobile QA — отчёт и исправления (март 2026)

## 1. Диагностика архитектуры child theme

### 1.1 Assets (enqueue)
- **Порядок:** `storefront-style` → `mnsk7-storefront-style` → parts 00..24 → inline (footer, insta, clearfix). Корректно.
- **Зависимость от parent:** при отсутствии Storefront (`mnsk7_parent_storefront_available() === false`) child не подключает parent style; style.css child грузится без зависимости. Устойчиво.
- **Риск:** тройное дублирование clearfix для `ul.products::before` (inline в theme, в woocommerce-layout, в wp_footer) — хрупко при обновлении WooCommerce.
- **Conditionals:** нет условной загрузки по URL; все части грузятся всегда. Ок.

### 1.2 Template overrides
- **WooCommerce:** archive-product.php, single-product.php, content-single-product.php, loop/price.php, loop/no-products-found.php, cart (proceed-to-checkout-button.php, cart-empty.php), global (quantity-input, wrapper-start/end), single-product (related, up-sells). Override proceed-to-checkout обоснован (audit).
- **Собственные:** header.php, footer.php, front-page.php, single.php, page-*.php. Parent не трогаем.

### 1.3 Hooks
- Storefront header/footer сняты при наличии parent; child выводит свой header. Корректно.
- PLP: сортировка/result_count только в after_shop_loop (один toolbar). Ок.
- Breadcrumbs: на PDP перенесены в summary; на archive — before_main_content. Ок.
- Риск: много remove_action в init — при смене версии Storefront нужно проверять.

### 1.4 CSS
- Части 00..24 загружаются по цепочке; 21-responsive-mobile идёт после 20-responsive-tablet. Ок.
- **04-header:** mobile breakpoint 768px — меню скрыто, бургер показан. Класс `.mnsk7-footer__inner` в 21 — корректен (footer использует его).
- **Специфичность:** местами !important (clearfix, footer bg). Для переопределения WooCommerce/Storefront приемлемо.
- **21-responsive-mobile.css:** в начале файла повреждён комментарий (нет открывающего `/*`), возможна некорректная интерпретация первой строки.

### 1.5 JS
- Всё inline в wp_footer: menu toggle, search, cart dropdown, promo, header shrink, PDP sticky CTA, cart checkout fallback, PLP load more, chips. Нет конфликтов с WooCommerce fragments (cart count обновляется через стандартные fragments).
- Cart checkout: fallback с setTimeout 100ms и проверкой pathname — при перехвате клика плагином может не сработать. Усилить: при клике по кнопке принудительно переходить по href.

---

## 2. Найденные проблемы (по приоритету)

### Critical
| # | Проблема | Root cause | Где исправить |
|---|----------|------------|----------------|
| 1 | Mobile header: при 360px возможны переполнение/скролл (audit) | Специфичность или порядок media; на части viewports меню могло не скрываться | 04-header.css: усилить скрытие меню до 768px, гарантировать overflow-x: hidden |
| 2 | Кнопка «Przejdź do płatności» не ведёт на checkout (audit) | Перехват клика плагином или WC; fallback 100ms недостаточен | functions.php: при клике по #mnsk7-cart-checkout-button принудительно location.href |

### Major
| # | Проблема | Root cause | Где исправить |
|---|----------|------------|----------------|
| 3 | Checkout: обрезка названия товара в «Twoje zamówienie» | Однострочный вывод | 18-cart-checkout.css — уже есть правило; проверить селектор |
| 4 | Два баннера cookie | Тема + Cookie Law Info | Фильтр mnsk7_show_cookie_bar при CLI — уже есть; на staging возможно CLI не активен |
| 5 | Footer: обрезка текста «Darmowa dostawa…» (audit) | Колонка с ограничением ширины без min-width: 0 | 09-footer.css: .mnsk7-footer__col min-width: 0, overflow-wrap |

### Minor
| # | Проблема | Root cause | Где исправить |
|---|----------|------------|----------------|
| 6 | 21-responsive-mobile.css: сломанный комментарий в начале | Обрезанный блок комментария | 21-responsive-mobile.css: исправить комментарий |
| 7 | PDP: миниатюры галереи (overflow) | Уже есть overflow в 06-single-product.css | Проверено; при необходимости усилить |

---

## 3. План исправлений

1. **04-header.css** — мобильный хедер: явно скрыть меню до 769px, запретить overflow в .mnsk7-header__inner.
2. **functions.php** — cart checkout: по клику на #mnsk7-cart-checkout-button делать preventDefault + window.location.href.
3. **18-cart-checkout.css** — убедиться, что селектор для product-name на checkout покрывает таблицу zamówienia.
4. **09-footer.css** — колонки футера: min-width: 0, overflow-wrap для текста.
5. **21-responsive-mobile.css** — исправить первый комментарий.

---

## 4. Изменённые файлы (после фиксов)

- wp-content/themes/mnsk7-storefront/functions.php — принудительный переход на checkout по клику; открытие mini-cart по клику na mobile
- wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css — mobile: overflow-x hidden, display none/flex !important dla menu
- wp-content/themes/mnsk7-storefront/assets/css/parts/09-footer.css — min-width: 0 i overflow-wrap dla kolumn
- wp-content/themes/mnsk7-storefront/assets/css/parts/18-cart-checkout.css — szersze selektory dla product-name na checkout
- wp-content/themes/mnsk7-storefront/assets/css/parts/21-responsive-mobile.css — naprawiony komentarz
- docs/MOBILE-QA-2026-03-REPORT.md — raport

---

## 5. Остаточные риски

- После переустановки parent theme нужно проверить: enqueue storefront-style, визуал хедера/футера.
- Cookie Law Info: если на проде включён, второй баннер не показывается (фильтр). Если нет — оставить один баннер в теме.
- PLP дублирование сортировки/пагинации уже убрано в functions.php (before_shop_loop); при обновлении WooCommerce проверить хуки.

---

## 6. Рекомендации перед продом

- Вручную пройти: główna → menu (otwórz/zamknij) → kategoria → produkt → dodaj do koszyka → koszyk → Przejdź do płatności → checkout.
- Проверить viewports: 360, 375, 390, 414 px.
- Сравнить guest vs zalogowany: wysokość headera, liczba linków, brak „skoku” layoutu.
- Sprawdzić jeden bank cookie (wyłączyć CLI na staging lub potwierdzić filtr).
