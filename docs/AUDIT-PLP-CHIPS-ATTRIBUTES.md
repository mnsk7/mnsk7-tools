# Аудит: чипсы фильтров по атрибутам на PLP (категории/теги)

**Дата:** март 2026  
**Цель:** инвентаризация атрибутов, чипсов и фильтрации; выявление несоответствий между карточкой товара и списком в категории.

---

## 1. Текущая реализация (инвентаризация)

### 1.1 Где заданы атрибуты и параметры фильтра

| Место | Файл | Что задано |
|-------|------|------------|
| Список таксономий для фильтрации запроса | `functions.php` ~1211 | `pa_srednica`, `pa_srednica-trzpienia`, `pa_dlugosc-calkowita-l`, `pa_dlugosc-robocza-h` — **жёстко 4 штуки** |
| Чипсы на PLP (какие строки показывать) | `functions.php` ~1303 | Те же 4 таксономии; подпись берётся из `wc_attribute_label()` (после правки) |
| «Wybrane» и «Wyczyść filtry» (какие GET-параметры сбрасывать) | `archive-product.php` ~94, ~238 | `filter_srednica`, `filter_srednica-trzpienia`, `filter_dlugosc-calkowita-l`, `filter_dlugosc-robocza-h` — **жёстко** |

Итого: **один фиксированный набор из 4 атрибутов** используется везде. Другие атрибуты WooCommerce (например Promień R, Średnica robocza, Dł. robocza, Dł. całkowita, если они заведены отдельными таксономиями) в чипсах и в фильтрации **не участвуют**.

### 1.2 Логика показа чипсов (`mnsk7_get_archive_attribute_filter_chips`)

1. Берутся ID товаров текущего архива (категория/тег), в наличии, с учётом уже выбранных фильтров.
2. Для каждой из 4 таксономий вызывается `get_terms( taxonomy, object_ids => product_ids, hide_empty => true )`.
3. **FB-03 bis (проблема):** если с `object_ids` терминов не нашлось, делается повторный вызов `get_terms` **без** `object_ids` — подставляются **все термины таксономии по всему магазину**.
4. Если термины есть (после одного из двух вызовов) — рисуется строка чипсов с подписью из WooCommerce.

Следствие: в категориях, где у товаров **нет** этих атрибутов (например **Zestawy**), всё равно показываются строки «Długość L», «Długość H» и т.д. с терминами **из других категорий**. Клик по чипсу ведёт к пустому результату: «Brak produktów dla wybranych filtrów».

### 1.3 Карточка товара (PDP) vs PLP

- **На PDP** блок «Kluczowe parametry» строится по реальным атрибутам товара через `$product->get_attributes()` и `wc_attribute_label( $attr->get_name() )`.  
  Пример: у фреза могут быть **Dł. robocza**, **Dł. całkowita**, **Promień R** — названия и набор атрибутов заданы в WooCommerce и могут отличаться от категории к категории.
- **На PLP** всегда показываются только 4 зашитых таксономии (и при fallback — их глобальные термины).  
  Итог: на карточке пользователь видит одни названия параметров (и, возможно, другие слаги атрибутов), а в категории — фиксированные «Średnica», «Długość L», «Długość H»; часть атрибутов с PDP в фильтрах категории вообще не отображается (например Promień R), а часть фильтров на PLP не соответствует реальным атрибутам товаров в этой категории (Zestawy).

### 1.4 Сводка проблем

| # | Проблема | Пример | Severity |
|---|----------|--------|----------|
| 1 | Fallback «все термины» при пустом результате по товарам архива | Zestawy: показываются Długość L/H с терминами по всему магазину; клик → «Brak produktów» | **High** |
| 2 | Фиксированный набор из 4 атрибутов | Атрибуты, реально используемые в товарах (напр. Dł. robocza, Dł. całkowita, Promień R), не все попадают в чипсы; лишние атрибуты могут показываться там, где у товаров их нет | **High** |
| 3 | Жёсткий список `filter_*` в archive-product.php | «Wybrane» и «Wyczyść filtry» завязаны на те же 4 параметра; при смене на динамический набор атрибутов список надо синхронизировать | **Medium** |
| 4 | Разные названия на PDP и в чипсах | На карточке «Dł. robocza», в категории может быть «Długość H» (если это одна таксономия с разными label в разных контекстах — тогда вопрос консистентности настроек WC) | **Medium** (частично решается использованием `wc_attribute_label` везде) |

---

## 2. Предложение по исправлению

### 2.1 Убрать fallback на «все термины»

- В `mnsk7_get_archive_attribute_filter_chips()`: если `get_terms( ..., object_ids => product_ids )` вернул пусто (или ошибку), **не** вызывать повторно `get_terms` без `object_ids`.
- Следствие: строка чипсов по атрибуту выводится **только если** у товаров текущего архива есть хотя бы один термин этой таксономии. В Zestawy не будет строк Długość L / Długość H, если у товаров Zestawy этих атрибутов нет.

### 2.2 Динамический набор атрибутов для чипсов

- Не хардкодить 4 таксономии. Получать список таксономий атрибутов из WooCommerce (например `wc_get_attribute_taxonomies()` → для каждой `pa_{attribute_name}` проверить `taxonomy_exists`).
- Для каждой такой таксономии: `get_terms( taxonomy, object_ids => product_ids, hide_empty => true )`. Добавлять строку чипсов **только если** есть хотя бы один термин (без fallback на все термины).
- Подписи — по-прежнему `wc_attribute_label( attribute_name )`, чтобы совпадать с PDP и настройками WC.

Итог: в каждой категории/теге показываются только те атрибуты, которые реально есть у товаров этого архива; названия — как в WooCommerce (в т.ч. «Średnica robocza», «Dł. robocza», «Dł. całkowita», «Promień R» и т.д.).

### 2.3 Синхронизация «Wybrane» и «Wyczyść filtry»

- Список `filter_*` для блока «Wybrane» и для кнопки «Wyczyść filtry» (и для empty state) формировать из фактически отображённых фильтров: т.е. из `mnsk7_get_archive_attribute_filter_chips()['filters']` собирать массив `param` (или завести общую функцию/фильтр, возвращающую список имён параметров по текущему архиву).
- В `archive-product.php` использовать этот динамический список вместо захардкоженного `filter_srednica`, ….

### 2.4 Фильтрация запроса товаров (`woocommerce_product_query`)

- Не привязываться к фиксированным 4 таксономиям. По всем GET-параметрам вида `filter_*` проверять: соответствует ли параметр какой-либо зарегистрированной таксономии атрибутов (`pa_*`). Для совпадающих — добавлять условие в `tax_query`. Так будут работать любые атрибуты, заведённые в WooCommerce, а не только текущие четыре.

### 2.5 Спецправило для Zestawy

- Сейчас для категории «Zestawy» скрывается только строка «Średnica». При переходе на динамический набор атрибутов по товарам архива строка по диаметру в Zestawy просто не появится (если у товаров Zestawy нет атрибута диаметра). Отдельное правило `is_zestawy && tax === 'pa_srednica'` можно оставить как дополнительную защиту или снять, если поведение и так будет корректным.

---

## 2.6 WP Rocket: кеш и URL с `?filter_*`

При наличии в URL параметров фильтров (`?filter_srednica=8-mm` и т.п.) WP Rocket может отдавать **старую** закешированную версию страницы (старый хедер, кнопки, «слипшиеся» бейджи доверия). Причина — кеш по полному URL с query string.

**Решение (mu-plugin `mnsk7-tools.php`):**

1. **`rocket_cache_query_strings`** — из списка кешируемых GET-параметров исключаются все, чьё имя начинается с `filter_`. При следующем сохранении настроек WP Rocket конфиг перегенерируется без них.
2. **`do_rocket_generate_caching_files`** — для запросов, в которых есть любой GET-параметр `filter_*`, кеш не создаётся (страницы с фильтрами всегда генерируются заново).
3. **Одноразовая очистка** — при первом заходе админа в админку после деплоя вызывается `rocket_clean_domain()`, чтобы удалить уже сохранённые кеш-файлы для URL с `?filter_*`.

После деплоя достаточно один раз зайти в админку (или вручную очистить кеш WP Rocket), чтобы старые варианты страниц с фильтрами перестали отдаваться.

---

## 3. Файлы для правок

| Файл | Изменения |
|------|-----------|
| `wp-content/themes/mnsk7-storefront/functions.php` | 1) Убрать fallback в `mnsk7_get_archive_attribute_filter_chips`. 2) Формировать список таксономий из WC; показывать только атрибуты с терминами у товаров архива. 3) В `woocommerce_product_query` — применять любой `filter_*`, соответствующий `pa_*`. |
| `wp-content/themes/mnsk7-storefront/woocommerce/archive-product.php` | Получать список `filter_*` из результата `mnsk7_get_archive_attribute_filter_chips()` (или из общей функции) и использовать его для «Wybrane», «Wyczyść filtry» и empty state вместо захардкоженного массива. |

После правок: в Zestawy не будет лишних чипсов; в категориях вроде Frez kulowy будут только те атрибуты (и те же названия), что реально у товаров; фильтрация и сброс фильтров будут согласованы с отображаемыми чипсами.

---

## 4. Wykonane zmiany (implementacja)

- **functions.php**
  - Dodano `mnsk7_get_product_attribute_taxonomy_names()` — zwraca listę taksonomii atrybutów z WooCommerce (`pa_*`).
  - Dodano `mnsk7_get_all_attribute_filter_param_names()` — zwraca wszystkie nazwy parametrów `filter_*` (do wykrywania aktywnych filtrów i linku „Wyczyść filtry”).
  - `woocommerce_product_query`: zamiast stałej listy 4 taksonomii używana jest `mnsk7_get_product_attribute_taxonomy_names()` — filtrowanie działa dla dowolnego atrybutu WC.
  - `mnsk7_get_archive_attribute_filter_chips()`: lista taksonomii z WC; **usunięto fallback** na „wszystkie termy” — wiersz chipów jest tylko wtedy, gdy w archiwum są produkty z terminami tej taksonomii; zwracane jest też `filter_params` (lista parametrów wyświetlanych chipów).
- **archive-product.php**
  - Blok „Wybrane” i link resetu: `filter_params` z wyniku `mnsk7_get_archive_attribute_filter_chips()`.
  - Empty state (Brak produktów): wykrywanie aktywnych filtrów i `clear_url` oparte o `mnsk7_get_all_attribute_filter_param_names()`, żeby zawsze można było wyczyścić wszystkie filtry atrybutów.
  - Gdy w archiwum nie ma produktów (product_ids puste) — nie pokazywać żadnych chipów atrybutów (zwracać pustą listę).

---

## 5. Checklist weryfikacji po wdrożeniu — przejście po wszystkich kategoriach

Po wdrożeniu zmian należy przejść po każdej kategorii (i ewentualnie wybranych tagach) i sprawdzić:

- **Czy są chipy atrybutów** — tylko wtedy, gdy produkty w tej kategorii mają przypisane termy tych atrybutów.
- **Czy etykiety** (Średnica, Długość L itd.) zgadzają się z nazwami w WooCommerce (np. „Średnica robocza”, „Dł. robocza”, „Dł. całkowita”).
- **Klik w chip** — czy filtrowanie zwraca produkty (nie „Brak produktów dla wybranych filtrów” tam, gdzie chip nie powinien być widoczny).

### Lista kategorii (z głównej strony sklepu)

| # | Kategoria | URL (slug zwykle z małych liter, myślniki) | Oczekiwanie chipów | Sprawdzone |
|---|-----------|---------------------------------------------|--------------------|------------|
| 1 | Frez diamentowy | `/kategoria-produktu/frez-diamentowy/` | Tylko atrybuty używane w tej kategorii | ☐ |
| 2 | Frez diamentowy PCD | `/kategoria-produktu/frez-diamentowy-pcd/` | jw. | ☐ |
| 3 | Frez fazownik | `/kategoria-produktu/frez-fazownik/` | jw. | ☐ |
| 4 | Frez grawerski | `/kategoria-produktu/frez-grawerski/` | jw. | ☐ |
| 5 | Frez kulowy | `/kategoria-produktu/frez-kulowy/` | Np. Średnica, Dł. robocza, Dł. całkowita, Promień R (zgodnie z WC) | ☐ |
| 6 | Frez prosty | `/kategoria-produktu/frez-prosty/` | jw. | ☐ |
| 7 | Frez spiralny | `/kategoria-produktu/frez-spiralny/` | jw. | ☐ |
| 8 | Frez spiralny stożkowo kulowy | `/kategoria-produktu/frez-spiralny-stozkowo-kulowy/` | jw. | ☐ |
| 9 | Frez typ U | `/kategoria-produktu/frez-typ-u/` | jw. | ☐ |
| 10 | Frez typ V | `/kategoria-produktu/frez-typ-v/` | jw. | ☐ |
| 11 | Frez wiertło | `/kategoria-produktu/frez-wiertlo/` | jw. | ☐ |
| 12 | Frezy do szlifierki | `/kategoria-produktu/frezy-do-szlifierki/` | jw. | ☐ |
| 13 | Płytki wieloostrzowe | `/kategoria-produktu/plytki-wieloostrzowe/` | jw. | ☐ |
| 14 | Tuleje zaciskowe | `/kategoria-produktu/tuleje-zaciskowe/` | jw. | ☐ |
| 15 | **Zestawy** | `/kategoria-produktu/zestawy/` | **Brak chipów atrybutów** (produkty bez tych atrybutów) | ☐ |
| 16 | Frez do gwintów | `/kategoria-produktu/frez-do-gwintow/` | jw. | ☐ |
| 17 | Frez typ V z rowkiem | `/kategoria-produktu/frez-typ-v-z-rowkiem/` | jw. | ☐ |
| 18 | Frez z łożyskiem | `/kategoria-produktu/frez-z-lozyskiem/` | jw. | ☐ |
| 19 | Frez z wymiennymi płytkami | `/kategoria-produktu/frez-z-wymiennymi-plytkami/` | jw. | ☐ |

### Tagi produktów (przykładowe)

| # | Tag | URL | Oczekiwanie | Sprawdzone |
|---|-----|-----|-------------|------------|
| 1 | Frez wieloostrzowy (kukurudza) | `/tag-produktu/frez-wieloostrzowy-kukurudza/` | Chipy z etykietami jak w WC (np. Średnica robocza, Dł. robocza, Dł. całkowita) | ☐ |

### Co wpisać w „Sprawdzone”

- **OK** — chipy tylko tam gdzie trzeba, etykiety zgodne z WC, klik w chip daje wyniki.
- **Problem** — np. Zestawy ma chipy / klik daje „Brak produktów” / etykiety niezgodne z kartą produktu.
- **N/A** — kategoria pusta lub bez atrybutów (wtedy brak chipów to OK).

Bazowy URL: `https://staging.mnsk7-tools.pl`

---

## 6. Weryfikacja logiki (przed deployem)

Sprawdzone w kodzie:

| Element | Plik | Logika |
|--------|------|--------|
| **Źródło taksonomii** | `mnsk7_get_product_attribute_taxonomy_names()` | Lista z `wc_get_attribute_taxonomies()` → tylko `pa_*` z `taxonomy_exists()`. Kolejność z WC. |
| **Zapytanie o produkty** | `woocommerce_product_query` | Dla każdego `filter_*` z GET, jeśli taksonomia `pa_*` istnieje — dodanie do `tax_query`. Działa dla dowolnego atrybutu WC. |
| **ID produktów w archiwum** | `mnsk7_get_archive_product_ids_for_chips()` | Tylko opublikowane, w stocku, w bieżącym termie (kategoria/tag) + uwzględnienie aktywnych filtrów z URL. |
| **Czy pokazywać chipy** | `mnsk7_get_archive_attribute_filter_chips()` | Tylko gdy `product_ids` niepuste. Dla każdej taksonomii: `get_terms( object_ids => product_ids )`; jeśli pusto — **brak** fallbacku (wiersz pomijany). Etykieta z `wc_attribute_label()`. |
| **Zestawy** | jw. | Jeśli `product_ids` puste — zwrot pustych filtrów. Dodatkowo `is_zestawy && pa_srednica` → pomijany. |
| **„Wybrane”** | `archive-product.php` | `filter_params` z `$attr_data['filter_params']` — tylko parametry faktycznie wyświetlonych chipów. |
| **Empty state** | `archive-product.php` | `has_filter` i `clear_url` po `mnsk7_get_all_attribute_filter_param_names()` — zawsze można wyczyścić **wszystkie** `filter_*` (np. stary link). |

Potencjalne edge case’y (zaakceptowane):

- Stary link z `?filter_dlugosc-calkowita-l=50` na Zestawy: chipów nie ma, „Wybrane” puste, lista 0 produktów → empty state z „Wyczyść filtry” (clear_url usuwa wszystkie `filter_*`). OK.
- Brak atrybutów w WC: `attrs_to_try` puste, brak chipów, `filter_params` puste. OK.
