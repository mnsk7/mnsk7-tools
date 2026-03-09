# Аудит мобильной и планшетной версий — staging.mnsk7-tools.pl (март 2026)

Проверка выполнена на viewport **375×667** (мобильный), **768×1024** (планшет) и **360×800** (узкий мобильный). Страницы: главная, Sklep (категория), PDP, корзина, checkout, Moje konto. Цель — один файл со всеми багами и рекомендациями по исправлению.

---

## 1. Сводка: что исправить

### Критичные (блокируют конверсию / использование)

| # | Где | Проблема | Viewport |
|---|-----|----------|----------|
| 1 | **Header** | На узких экранах (360px) возможны переполнение, вертикальный скролл в шапке, налезание пунктов меню и иконок; логотип обрезан. Меню должно быть только бургером (&lt;768px), без списка в одну строку. | Mobile ≤430px |
| 2 | **Корзина** | Кнопка «Przejdź do płatności» далеко внизу; на 375px клик может перехватываться (element outside viewport / sticky). Переход на checkout должен быть доступен без избыточного скролла и без перехвата клика. | Mobile |
| 3 | **Корзина → Checkout** | По отчётам: клик «Przejdź do płatności» иногда не ведёт на /zamowienie/ — остаёмся на /koszyk/. Проверить ссылку (wc_get_checkout_url) и отсутствие JS/overlay, перехватывающего клик. | Mobile |

### Высокий приоритет (UX, доверие, «сырой» вид)

| # | Где | Проблема | Viewport |
|---|-----|----------|----------|
| 4 | **Cookie** | Два баннера о cookie в DOM (тема + плагин): «Przyjmuję… Ustawienia» и «Przyjmuję… Więcej informacji». Визуальный мусор и путаница. | Все |
| 5 | **Footer** | В колонке «Dostawa» текст «Darmowa dostawa od 300 zł. Tylko Polska.» обрезается («Tylko Polska» не видно). В футере есть `white-space: nowrap` на элементах — проверить блок с этим текстом. | Mobile, Tablet |
| 6 | **Hero (главная)** | Trust-чип «3 500+ zamówień rocznie» обрезается справа на части экранов. У контейнера чипсов разрешить wrap, убрать обрезку. | Tablet, Desktop |
| 7 | **Checkout** | В блоке «Twoje zamówienie» длинное название товара обрезается (напр. «Frez do kompozytów 2P |»). В теме уже есть стили `white-space: normal` для `.product-name` — убедиться, что они применяются и нет переопределения с ellipsis. | Mobile, Tablet |
| 8 | **PDP** | Миниатюры галереи обрезаны по краям контейнера. Сделать overflow: hidden с чёткой границей или карусель, чтобы не было «половинчатых» превью. | Mobile, Tablet |
| 9 | **PDP** | Водяной знак и размеры на основном изображении товара (логотип, подписи D=3,1, 22, 38). Убрать с главного фото; размеры вынести в «Kluczowe parametry» или описание. | Все |

### Средний приоритет (дубли, доступность, консистентность)

| # | Где | Проблема | Viewport |
|---|-----|----------|----------|
| 10 | **PLP (Sklep / kategoria)** | Дубли: два селекта сортировки, две пагинации, несколько блоков «Filtruj» и «Szukaj produktów». Усложняет интерфейс и скринридеры. Один селект, одна пагинация, одна область фильтров. | Mobile, Tablet |
| 11 | **Header** | При пустой корзине индикатор «0 Produkt» без явной иконки корзины — слабее соответствие ожиданиям. На mobile только иконка + число. | Mobile |
| 12 | **Header** | Ссылка «Moje konto» при залогиненном пользователе показывает display_name — на узком экране может сдвигать иконки. На &lt;768px текст уже скрыт (только иконка); проверить 768–900px. | Tablet |
| 13 | **Корзина** | Блок «Oblicz koszty wysyłki» (collapsed): при раскрытии проверить, что поля (Kraj, Miejscowość, Kod) не ломают layout и не вылезают за экран на 360px. | Mobile |
| 14 | **Checkout** | Баннер «Kliknij, aby się zalogować» и зона купона — разная высота для гостя/логина. Убедиться, что не перекрывают кнопку «Kupuję i płacę» и поля формы на 360px. | Mobile |
| 15 | **Checkout** | Кнопка «Kupuję i płacę» должна быть видна без избыточного скролла и не перекрыта sticky-хедером или баннерами на 360px. | Mobile |

### Низкий приоритет

| # | Где | Проблема | Viewport |
|---|-----|----------|----------|
| 16 | **Moje konto** | Нет H1 на странице (только H2 «Logowanie» / «Zarejestruj się»). Добавить один H1 «Moje konto». | Все |
| 17 | **PLP карточки** | Длинные названия товаров — проверить перенос и обрезку на 360px (карточки в одну колонку на ≤480px). | Mobile |
| 18 | **PDP «Podobne produkty»** | Длинные названия и цены — читаемость и перенос на 360px. | Mobile |
| 19 | **Breadcrumbs** | На PDP и категории — не обрезать критично на узком экране; текущая страница не ссылка. | Mobile, Tablet |
| 20 | **Sticky / баннеры** | Баннер «Darmowa dostawa od 300 zł» и sticky header не должны перекрывать друг друга и CTA (напр. «Przejdź do płatności»). | Mobile |

---

## 2. По страницам и компонентам

### 2.1 Главная (mobile 375×667, tablet 768×1024)

- **Header:** На 375px хедер уже в режиме бургера (по коду breakpoint 768px). На 360px проверить: нет ли горизонтального overflow, логотип не обрезан (max-height: 36px в 04-header.css для ≤430px), бургер открывает меню, поиск/аккаунт/корзина кликабельны.
- **Hero:** Чипы USP — обрезка последнего («3 500+ zamó»). В 08-home-sections.css у `.mnsk7-hero__usps` стоит `flex-wrap: wrap` и `overflow: visible` — проверить, не переопределяется ли где-то; на планшете при одной строке возможен overflow.
- **Cookie:** Два баннера — отключить один (плагин или тема).
- **Футер:** Колонка Dostawa — обрезка текста; в 09-footer.css проверить блок с «Darmowa dostawa od 300 zł» (нет ли max-width + ellipsis или nowrap на контейнере).

### 2.2 Sklep / Kategoria (PLP)

- **Дубли:** Два селекта сортировки, две пагинации, несколько «Filtruj» и «Szukaj produktów» — в разметке/шаблонах оставить по одному экземпляру или логически одну пагинацию сверху/снизу.
- **Карточки товаров:** На ≤480px одна колонка (21-responsive-mobile.css). Проверить длинные названия — перенос, не обрезка без возможности «читать далее».
- **Кнопки «Dodaj do koszyka» и поле Ilość:** На 360px не обрезаны и не перекрыты; touch-target ≥44px.

### 2.3 PDP (карточка товара)

- **Галерея:** Миниатюры по краям обрезаны — задать контейнеру overflow: hidden и корректную ширину или карусель.
- **Основное фото:** Убрать водяной знак и размеры с изображения; вынести размеры в описание/атрибуты.
- **Sticky CTA (mobile):** В 06-single-product.css есть `.mnsk7-pdp-sticky-cta` для ≤768px — убедиться, что не перекрывает контент и что скрывается, когда основной блок с кнопкой в viewport.
- **Kluczowe parametry:** На 480px в 21-responsive-mobile уже `grid-template-columns: 1fr` — читаемость на малом экране.

### 2.4 Корзиna

- **«Przejdź do płatności»:** Ссылка должна вести на `wc_get_checkout_url()`. На mobile кнопка далеко внизу — при скролле возможен перехват клика (другой элемент поверх); проверить z-index и порядок слоёв. Рассмотреть sticky-кнопку «Przejdź do płatności» на mobile внизу экрана (как на PDP).
- **Podsumowanie koszyka:** Методы доставки и итог — на 375px блок не ломается; радиокнопки и «Oblicz koszty wysyłki» при раскрытии — поля в одну колонку, без горизонтального скролла.

### 2.5 Checkout (Zamówienie)

- **«Twoje zamówienie»:** Название товара не обрезать — в 18-cart-checkout.css уже `white-space: normal` для `.product-name`; проверить, что нет более специфичного правила с ellipsis (в т.ч. из WooCommerce).
- **Форма:** Поля и кнопка «Kupuję i płacę» не перекрыты sticky-хедером; баннер логина и купон не занимают весь экран на 360px.
- **Ссылка «Kliknij, aby się zalogować»:** Ведёт на /moje-konto/ с return_url на checkout.

### 2.6 Moje konto

- **H1:** Добавить один H1 «Moje konto» (или «Logowanie» для гостя).
- **Формы и дашборд:** На mobile/tablet блоки не наползают; стили темы применяются (не только дефолтный WooCommerce).

### 2.7 Footer (все страницы)

- **Колонка Dostawa:** Текст «Darmowa dostawa od 300 zł. Tylko Polska.» отображается полностью; убрать обрезку (overflow/line-clamp/nowrap на этом блоке).
- **Accordion на ≤768px:** В 09-footer.css колонки сворачиваются в аккордеон; заголовки с «+» — кликабельны, контент раскрывается.

---

## 3. Breakpoints и файлы

- **Mobile:** ≤480px (21-responsive-mobile.css), узкие ≤430px и ≤360px (04-header.css, 09-footer.css).
- **Tablet:** ≤768px (20-responsive-tablet.css, 04-header.css), ≤900px (часть header/hero).

Ключевые файлы для правок:

- `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css` — хедер, бургер, overflow.
- `wp-content/themes/mnsk7-storefront/assets/css/parts/08-home-sections.css` — hero, чипы.
- `wp-content/themes/mnsk7-storefront/assets/css/parts/09-footer.css` — футер, колонка Dostawa.
- `wp-content/themes/mnsk7-storefront/assets/css/parts/18-cart-checkout.css` — корзина, checkout, кнопка перехода, название товара.
- `wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css` — PDP, галерея, sticky CTA.
- Шаблоны archive/shop, cart, checkout — дубли сортировки/пагинации/фильтров.
- Cookie: настройки темы и плагина — один баннер.

---

## 4. Чек-лист исправлений (готовые задачи)

- [x] **Header mobile:** На &lt;768px только бургер и иконки; без вертикального overflow в `.mnsk7-header__inner`; на 360px логотип не обрезан (max-height: 36px).
- [x] **Корзина → Checkout:** Ссылка «Przejdź do płatności» ведёт на checkout; на mobile sticky-кнопка u dołu (z-index 100).
- [x] **Cookie:** Один баннер — bank temy ukryty gdy Cookie Law Info aktywny.
- [x] **Footer Dostawa:** Убрать обрезку текста «Darmowa dostawa od 300 zł. Tylko Polska.».
- [x] **Hero chips:** Убрать обрезку последнего чипа; overflow: visible na hero i __inner.
- [x] **Checkout product name:** Полное название в «Twoje zamówienie» (white-space: normal !important, overflow-wrap).
- [x] **PDP gallery:** Миниатюры — overflow-x: hidden (brak obcięcia po brzegach).
- [ ] **PDP image:** Убрать водяной знак и размеры с основного фото (kontekst/treść, nie kod temy).
- [x] **PLP:** Один toolbar — usunięcie sortowania/paginacji z before_shop_loop.
- [x] **Header cart empty:** Klasa --empty, ikona i „0” zawsze widoczne.
- [x] **Oblicz koszty wysyłki:** Na 360px form-row w kolumnę.
- [x] **Checkout:** padding-bottom na mobile; przycisk «Kupuję i płacę» widoczny.
- [x] **Moje konto:** H1 dla zalogowanych i gości (before_customer_login_form).
- [x] **Breadcrumbs:** Na mobile last-item bez nadmiernego obcięcia.
- [x] **Sticky/баннеры:** Promo 1001, header 1000, cart sticky 100 — bez nakładania na CTA.

---

## 5. Рекомендуемые viewport для повторной проверки

После исправлений проверить:

- **360×800** — узкий мобильный (header, корзина, checkout).
- **375×667** — типичный мобильный.
- **390×844** — iPhone 14 Pro.
- **768×1024** — планшет портрет.
- **820×1180** — планшет (iPad Air).

---

*Аудит mobile/tablet выполнен 2026-03-09. Дополняет общий аудит в `AUDIT-STAGING-2026-03.md`.*
