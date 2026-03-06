# Путь клиента: staging vs production

**Дата:** 2026-03-05  
**Цель:** зафиксировать различия UX между staging.mnsk7-tools.pl и mnsk7-tools.pl (prod) и привязать реализацию к файлам репозитория.

---

## Staging (staging.mnsk7-tools.pl) — текущее состояние

### Главная страница

- Шапка с логотипом, горизонтальное меню категорий (дерево/металл/пластик и т.д.), кнопка **«Przejdź do sklepu»**.
- Сразу отображаются основные категории.
- Ниже — блок доверия (доставка на следующий день, VAT-фактура, бесплатная доставка от 300 zł и т.п.).
- Быстрый переход в каталог.

**Файлы:**  
- Тема с `front-page.php`: `wp-content/themes/tech-storefront/front-page.php` (hero, CTA «Przejdź do sklepu», секция категорий, секция trust `mnsk7-section--trust`).
- Стили: `tech-storefront` и/или `mnsk7-storefront` (main.css) — trust stats, hero, kategorie.

### Страница категории

- Пример: «Frez spiralny CNC».
- Вверху голубой градиентный баннер с названием категории и количеством товаров.
- Под ним — сетка карточек.
- Карточки: изображение фрезы с размерами, название, цена.
- **Фильтров по материалу/диаметру пока нет.** Навигация по типам фрез — в меню.

**Файлы:**  
- WooCommerce archive: переопределения в child theme `woocommerce/archive-product.php` (tech-storefront или mnsk7-storefront).
- Стили баннера/сетки в `assets/css/` темы.

### Карточка товара (PDP)

- Крупное фото-схема (инфографика размеров).
- Таблица ключевых параметров (диаметр, шанковая часть, длина рабочей части, общая длина).
- Вариантные «чипы» для выбора длины, рабочей высоты, диаметра хвостовика.
- Количество, кнопка **«Dodaj do koszyka»**.
- Под кнопкой — блок доверия (доставка, VAT, возврат) и счётчик **«191 osób kupiło»** (или аналог).
- Описание во вкладках: Opis, Informacje dodatkowe, Opinie.

**Файлы:**  
- **MU-plugin:** `mu-plugins/inc/product-card.php`:
  - `mnsk7_single_product_key_params()` — таблица ключевых параметров (priority 21).
  - `mnsk7_single_product_availability()` — статус наличия (priority 8).
  - `mnsk7_single_product_trust_badges()` — trust (Dostawa jutro, Faktura VAT, Darmowa od 300 zł, Zwroty 30 dni) + «X osób kupiło» из `$product->get_total_sales()` (priority 32).
- Атрибуты ключевых параметров: `mnsk7_get_key_param_attributes()` (średnica, fi/trzpienie, długość, typ, materiał, pokrycie, liczba zębów, chwyt).
- Шаблон single product: child theme `woocommerce/content-single-product.php` (tech-storefront) и стили в `mnsk7-product.css` / `main.css` (trust badges, key params).

### Добавление в корзину

- После «Dodaj do koszyka» — всплывающее окно с краткой информацией о товаре, кнопки «Zobacz koszyk» и «Zamówienie».
- Счётчик корзины в шапке обновляется, отображается итоговая сумма.

**Файлы:** стандартное поведение WooCommerce + возможно кастомизация в теме / `mu-plugins/inc/woo-ux.php`.

### Корзина (Koszyk)

- Напоминание о бесплатной доставке от 300 zł.
- Список товаров (изменение количества), поле купона, кнопка обновления корзины.
- «Podsumowanie koszyka»: способы доставки (InPost Paczkomaty, DPD kurier, DPD za pobraniem, odbiór osobisty), сообщение «do darmowej dostawy brakuje 236 zł», итоговая сумма.
- Кнопка «Przejdź do płatności» → оформление заказа.

**Файлы:**  
- **MU-plugin:** `mu-plugins/inc/delivery.php` — `mnsk7_cart_free_shipping_notice()` (hook `woocommerce_before_cart`, `woocommerce_before_checkout_form`). Константа `MNK7_FREE_SHIPPING_MIN` в `inc/constants.php` (300).

### Оформление заказа (Zamówienie)

- Имя, фамилия, адрес, телефон, email.
- Выбор доставки и оплаты, применение купона.
- Дальше — переход к оплате (на staging без реальной оплаты).

**Файлы:** `mu-plugins/inc/checkout.php` — trust-строка перед submit, поля firma/NIP opcjonalne.

---

## Production (mnsk7-tools.pl) — текущее состояние

### Главная страница

- Сетка из 12 ярких плиток категорий (тип фрезы) на белом фоне: «Frez spiralny», «Frez prosty», «Frez z wymiennymi płytkami» и т.д.
- **Нет** блока «wysyłka 24h / 30 dni zwrotu» (trust na głównej).
- Пользователь сразу идёт по типу фрезы.

### Страница категории

- Пример: «Frez spiralny» — 77 товаров.
- Карточка: фото с размерами, **длинное название** (np. «Frez do kompozytów 2P | fi 3.175 mm x 50 mm | TiN CNC»), цена, кнопка «Dodaj do koszyka».
- Сортировки и фильтров нет.

### Карточка товара (PDP)

- Упор на **длинное текстовое описание** (несколько абзацев).
- Ключевые параметры — в буллетах в «Opis» или в таблице «Informacje dodatkowe».
- **Нет** вариантов (чипов) для длины/диаметра — только поле количества и «Dodaj do koszyka».
- **Нет** блока «Kupione X razy», **нет** trust-иконок у кнопки.

### Корзина

- Таблица товаров, количество, купон, «Podsumowanie koszyka», способы доставки, итог.
- **Нет** сообщения о пороге бесплатной доставки («brakuje X zł»).

### Оформление заказа

- Аналогично staging: форма, «Twoje zamówienie», способы доставки, Przelewy24, чекбокс согласия, «Kupuję i płacę».

---

## Сводная таблица различий

| Элемент | Staging | Production |
|--------|---------|------------|
| Главная | Hero + CTA «Przejdź do sklepu», kategorie, trust block | 12 плиток kategorii, bez trust |
| Kategoria | Bannery, siatka kart | Listing 77 produktów, bez filtrów/sortowania |
| Karta w PLP | Zdjęcie, nazwa, cena (krótsza nazwa + spec line na staging w docelowym kontrakcie) | Długie nazwy, bez key spec line |
| PDP | Tabela key params, warianty chips, trust pod CTA, «X osób kupiło» | Długi opis, parametry w Opis/Dodatkowe, brak chipsów, brak trust/social proof |
| Koszyk | Komunikat „do darmowej dostawy brakuje X zł” | Brak takiego komunikatu |
| Checkout | Trust line, firma/NIP opcjonalne | Standard Woo |

---

## Реализация в репозитории (что даёт staging-поведение)

| Funkcjonalność | Gdzie zaimplementowane |
|----------------|------------------------|
| Trust na głównej, CTA «Przejdź do sklepu», kategorie | `tech-storefront/front-page.php` |
| Key params table na PDP | `mu-plugins/inc/product-card.php` — `mnsk7_single_product_key_params()`, `mnsk7_get_key_param_attributes()` |
| Trust badges + «X osób kupiło» na PDP | `mu-plugins/inc/product-card.php` — `mnsk7_single_product_trust_badges()` |
| Komunikat „do darmowej dostawy brakuje X zł” w koszyku/checkout | `mu-plugins/inc/delivery.php` — `mnsk7_cart_free_shipping_notice()`, `MNK7_FREE_SHIPPING_MIN` w `constants.php` |
| Checkout: trust, opcjonalne firma/NIP | `mu-plugins/inc/checkout.php` |
| Warianty (chips) na PDP | WooCommerce variations + szablony/theme (child theme single product) |
| Stylowanie PDP, trust, karty | `tech-storefront/assets/css/mnsk7-product.css`, `mnsk7-storefront/assets/css/main.css` |

**Важно:** на staging активна может быть тема **tech-storefront** (parent best-shop) или **mnsk7-storefront** (parent Storefront). W repozytorium `front-page.php` z hero i trust jest w **tech-storefront**. Jeśli staging używa **mnsk7-storefront**, tam może nie być własnego `front-page.php` — wtedy główna idzie z parent Storefront lub innego szablonu.

---

## Co brakuje na staging (vs design contract / blueprint)

- **Filtry** po średnicy/materiale/typie — bez pluginów, URL params + WP_Query (plan: `mnsk7-catalog-core`).
- **Key spec line** na kartach w PLP/related (jedna linia typu „D=38 mm • S=8 mm”) — częściowo w kontrakcie, do dokończenia w theme/MU-plugin.
- **SEO** struktura katalogu (landingi, Title/H1 szablony) — w planach.
- **Kolejność zdjęć** produktu: 1 czysty instrument, 2 wymiary, 3 zestaw, 4 zastosowanie — zależy od danych w media, nie od kodu.

---

## Bezpieczeństwo staging vs prod (DB, motyw)

- **Krytyczne:** staging musi mieć **osobną bazę** (inny `DB_NAME` w wp-config). W przeciwnym razie zmiana motywu/opcji na staging zmienia prod.
- Przed dalszymi zmianami: **sprawdzić** na serwerze staging `wp-config.php` (DB_NAME) i upewnić się, że prod ma inną bazę.
- Nie wdrażać zmian prod (theme/plugins) dopóki nie ma potwierdzenia rozdzielenia DB i poprawnej ścieżki deploy (staging = osobny katalog lub subdomena z własnym config).

---

## Checklist: przed zmianą motywu na staging

- [ ] Na serwerze staging: sprawdź `wp-config.php` — `DB_NAME` musi być **inny** niż na prod (np. `llojjlcemq_stg` vs `llojjlcemq`).
- [ ] Jeśli DB jest wspólna: zatrzymaj zmiany motywu; sklonuj prod DB do nowej bazy, zaktualizuj staging wp-config (DB_NAME, DB_USER, DB_PASSWORD), dopiero potem zmieniaj motyw.
- [ ] Po potwierdzeniu: zmiana motywu/opcji na staging nie wpływa na prod.

---

## Źródła

- Opis drogi użytkownika: przekazane przez użytkownika (staging + prod).
- Mapowanie plików: przeszukanie `wp-content/themes`, `mu-plugins/inc/*.php`, dokumentów w `docs/`.
