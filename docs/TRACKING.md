# Tracking — mnsk7-tools.pl

*(Выход агента 02_growth_seo)*

События GA4, конверсии, микроцели, KPI. Один источник событий (AS_IS_BACKLOG P2-05: один GTM).

---

## 1. Цели отслеживания

- **Конверсия:** заказы с сайта (цель 10–20% от объёма продаж при росте канала — REQUIREMENTS).
- **Поведение в каталоге:** просмотры товаров, использование фильтров, добавление в корзину.
- **Качество трафика и страниц:** откуда приходят, какие категории/карточки конвертируют.

---

## 2. События GA4 (eCommerce + микроцели)

Рекомендуемый минимум (WooCommerce + GTM или плагин GA):

| Событие | Описание | Когда |
|---------|----------|--------|
| **view_item** | Просмотр карточки товара | Открытие single product |
| **view_item_list** | Просмотр списка (категория, wyszukiwarka) | Архив категории, поиск |
| **add_to_cart** | Добавление в корзину | Klik „Dodaj do koszyka” |
| **remove_from_cart** | Удаление из корзины | Opcjonalnie |
| **view_cart** | Просмотр корзины | Strona koszyka |
| **begin_checkout** | Начало оформления | Wejście na checkout |
| **add_payment_info** | Ввод платёжных данных | Opcjonalnie |
| **purchase** | Завершённый заказ | Thank-you / zamówienie opłacone |

**Микроцели (opcjonalnie, jeśli GTM/plugin to obsługuje):**

| Событие | Opis | Cel |
|---------|------|-----|
| filter_applied | Użycie filtra w katalogu | Zrozumienie, które filtry są używane |
| scroll_depth / view_block | Głębokość scrolla lub widoczność bloku (np. parametry, FAQ) | Jakość zaangażowania na karcie produktu |

Nazwy zdarzeń — zgodne z GA4 eCommerce (recommended events); w GTM mapowanie na parametry produktu (item_id, name, price, quantity, category).

---

## 3. Konwersje w GA4

W GA4 oznaczyć jako **konwersje** (Conversions):

- **purchase** — główna konwersja.
- **begin_checkout** — konwersja mikro (opcjonalnie).
- **add_to_cart** — do analizy lejka (opcjonalnie).

Reszta zdarzeń — tylko do analizy, bez oznaczania jako konwersja, chyba że cel biznesowy (np. lead).

---

## 4. KPI — lista bazowa

| KPI | Źródło | Cel (kierunkowy) |
|-----|--------|-------------------|
| Zamówienia z witryny (liczba / mies.) | WooCommerce / GA purchase | Wzrost udziału w sprzedaży (cel 10–20%) |
| Współczynnik konwersji (sesja → purchase) | GA4 | Wzrost po poprawie katalogu i karty |
| Sesje z katalogu / wyszukiwarki | GA4 | Wzrost ruchu organicznego i z kategorii |
| add_to_cart / view_item (stosunek) | GA4 | Skuteczność karty produktu |
| Odrzuty / czas na stronie (kategoria, karta) | GA4 | Jakość treści i UX |
| LCP / Core Web Vitals | PageSpeed / Search Console | Stabilność po optymalizacji obrazów (E6) |

Raportowanie: miesięcznie; przed/po wdrożeniu zmian (Sprint 01, 02) — snapshot metryk.

---

## 5. Techniczne uwagi

- **Jeden GTM, jeden GA4** — unikać dubli (AS_IS_BACKLOG P2-05). Facebook Pixel — osobno, też jeden (P2-06).
- **Staging:** events można nie wysyłać lub wysyłać do testowego property (np. filtr po URL lub parametr debug).
- **Prywatność:** zgodność z polityką cookies (informacja, zgoda jeśli wymagana); w GTM uwzględnić consent mode jeśli używany.

Po wdrożeniu: weryfikacja w GA4 DebugView / podgląd GTM, test purchase na staging (jeśli events są włączone).
