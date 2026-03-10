# Mobile UX Workflow — финальный отчёт (2026-03-10)

## 1. STACK MAP

- **Стек:** WordPress + WooCommerce, child theme **mnsk7-storefront** (parent Storefront не в репо; деплой только child).
- **Критичные темы/плагины:** mnsk7-storefront (header.php, functions.php), WooCommerce overrides (archive-product, single-product, cart, global wrapper), mu-plugins (staging-safety — без разметки).
- **Render paths:** Один и тот же `get_header()` → header.php для: homepage (front-page.php), cart (page), PDP (single-product.php), PLP (archive-product.php), URL с ?filter_* (template_include + body_class нормализованы через mnsk7_is_plp_archive / mnsk7_is_plp_url_path).
- **Legacy branches:** Нет альтернативного header; один header.php (mnsk7-header). tech-storefront в репо не активна.
- **Файлы header/mobile:** header.php (разметка + critical inline CSS), functions.php (hooks, enqueue, wp_footer JS), 04-header.css, 21-responsive-mobile.css, 20-responsive-tablet.css.

## 2. MOBILE QA REPORT

- **Viewport 360×800:** На странице /koszyk/ при узком окне наблюдалось наложение элементов хедера: навигация (Przewodnik, Dostawa) и поле поиска в одну линию с обрезкой и перекрытием (описание скриншота: «поле поиска перекрывает навигационные ссылки», «header выглядит сломанным»).
- **Homepage 360px:** В snapshot видны все ссылки меню в DOM; визуально при 360px ожидается только бургер + иконки (search, account, cart). Причина визуального дефекта — глобальное правило `overflow: visible` для `.mnsk7-header__inner`, переопределяющее мобильное `overflow: hidden`.
- **Воспроизводимые дефекты:** (1) Наложение/переполнение хедера на mobile/tablet (особенно /koszyk/, узкие ширины). (2) Риск горизонтального скролла или «плывущего» хедера при загрузке до полного CSS.

## 3. DESKTOP QA REPORT

- Desktop (1280+) при первой загрузке: полное меню + поиск + аккаунт + корзина отображаются корректно.
- После фикса overflow: на desktop (min-width: 769px) сохранён overflow: visible для dropdown (Sklep, search, cart), чтобы выпадающие блоки не обрезались.

## 4. DESIGN REPORT

- **Было broken:** хедер на узких ширинах (в т.ч. страница корзины) — наложение навигации и поиска, визуально «кустарное» состояние.
- **Исправлено:** мобильный хедер — одна строка (logo | burger, search, account, cart) с overflow: hidden на .mnsk7-header__inner до 768px; desktop overflow: visible только от 769px, без регрессии dropdown.

## 5. CODE REVIEW REPORT

- **Root cause:** В `04-header.css` в конце файла блок «Audit: dropdown Sklep nie może przycinać się» задавал `.mnsk7-header`, `.mnsk7-header__inner`, `.mnsk7-header__nav` свойство `overflow: visible` без media query. Это переопределяло мобильное правило `@media (max-width: 768px) { .mnsk7-header__inner { overflow: hidden } }` (порядок каскада: более позднее правило выигрывало).
- **Файлы:** wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css.
- **Хуки/templates:** не менялись; проблема только в CSS cascade.
- **Селекторы:** .mnsk7-header__inner, .mnsk7-header, .mnsk7-header__nav.
- **Plugin/theme collisions:** не выявлены.

## 6. FIX REPORT

- **Изменённые файлы:**
  1. **04-header.css** — блок с `overflow: visible` для .mnsk7-header, .mnsk7-header__inner, .mnsk7-header__nav обёрнут в `@media (min-width: 769px)`, чтобы на mobile (≤768px) сохранялось overflow: hidden и не было наложения.
  2. **header.php** — в critical inline CSS для `@media (max-width:768px)` для .mnsk7-header__inner добавлено явное `overflow-x: hidden` (дублирование для надёжности при медленной загрузке полного CSS).
- **Почему так:** Устранение root cause (каскад), без маскировки overflow-x: auto/scroll и без добавления новых контролов. Один и тот же header на всех страницах; ?filter_* не затрагивается.

## 7. REGRESSION REPORT

- Проверено после фиксов: homepage и /koszyk/ на viewport 360px; desktop 1280+.
- Критерии: старый header не всплывает; на mobile хедер в одну строку (logo + бургер + иконки), без наложения; на desktop dropdown Sklep/search/cart не обрезаются (overflow: visible от 769px); customer flow (home → cart) корректен.

## 8. GIT RESULT

- Commit message: см. следующий commit.
- Push в main: выполняется после commit.
