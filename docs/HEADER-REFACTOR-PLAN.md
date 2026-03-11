# План рефакторинга header (только header)

**Ограничение:** работаем только с header. Footer, cards, filters, остальной сайт не трогаем.

**Декомпозиция:** 9 этапов, выполнять последовательно. После каждого этапа — проверка, без монолитного рефакторинга.

---

## Этап 1. Discovery (текущее состояние)

### 1.1 Текущие состояния header

| Состояние | Где задаётся | Поведение |
|-----------|--------------|-----------|
| **Mobile** | header.php critical `max-width: 768px`; 04-header.css `max-width: 992px` (общие стили) | Burger виден, меню скрыто (.mnsk7-header__menu display:none), по .is-open — выезд. Search = иконка, по клику — панель под header (Pattern B). Account/cart — иконки. Inner overflow: hidden. |
| **Tablet** | Нет отдельного «tablet» — те же стили, что mobile в 04-header: `max-width: 992px` | Визуально то же, что mobile: одна строка logo \| burger, search, account, cart. Search не в отдельной строке; панель поиска под header при открытии. |
| **Desktop** | header.php critical `min-width: 769px`; 04-header `min-width: 993px` (search inline); 25-global-layout `min-width: 1025px` (hide toggles) | Burger/search-toggle скрыты (769px critical, 1025px layout). Nav горизонтально. Search inline в одной строке с nav. Mega menu «Sklep» по hover. |
| **Desktop mega menu open** | 04-header.css .menu-item-has-children:hover .sub-menu | Dropdown под «Sklep»: 4 колонки grid, только категории (product_cat), без тегов/доп. групп. |

### 1.2 Конфликт breakpoint’ов (источник broken state)

| Источник | Breakpoint | Что делает |
|----------|------------|------------|
| **header.php** (critical) | **769px** | ≥769: burger и search-toggle скрыты, search dropdown = static (inline). ≤768: burger виден, menu скрыто. |
| **04-header.css** | **993px** | ≥993: search inline (dropdown static, toggle hidden). ≤992: «tablet+mobile» — одна строка, иконки, overflow hidden. |
| **04-header.css** | **992px** | ≤992: единый «mobile/tablet» layout. |
| **25-global-layout.css** | **1025px** | ≥1025: menu-toggle, search-toggle display:none; search dropdown static. |
| **functions.php** (JS) | **1024 / 1025** | 1024: меню «Sklep» клик ведёт на sklep (submenu скрыт). 1025: поиск/cart desktop логика. |
| **functions.php** (PHP) | **MNSK7_BREAKPOINT_MOBILE = 1024** | Не используется в CSS напрямую; в JS 1024. |

**Проблемный диапазон:**  
**769px–992px** (и частично до 1024px): critical уже переключил на «desktop» (нет бургера, поиск inline), но ширина экрана недостаточна — в одну строку пытаются поместиться logo + полный nav + inline search + account + cart → наезды и broken state.  
Отдельного **tablet layout** (своя строка для поиска, без сжатого desktop) нет.

### 1.3 Defects только по header

1. **Burger button**  
   - Иконка внутри не оптически центрирована.  
   - Линии бургера: разная длина/толщина (сейчас box-shadow 0 6px 0, 0 12px 0 — визуально могут быть неровности).  
   - Отступы вокруг иконки не равномерные.  
   - Open/active state сдвигает иконку (или контейнер).

2. **Tablet state**  
   - Нет отдельного tablet layout; между desktop и tablet — промежуточное состояние с наездами.  
   - Переключение в «tablet» должно происходить раньше, до начала overlap.

3. **Tablet search**  
   - На tablet поиск не в отдельной строке.  
   - Input и кнопка не выглядят как один составной control (стык/щель, разная высота/radius/border).

4. **Desktop**  
   - Наезды logo/nav/search/account/cart в промежуточных ширинах.  
   - Broken state между desktop и tablet.  
   - Нужно переключать layout на tablet до конфликта элементов.

5. **Mega menu «Sklep»**  
   - Синий контур active/open допустим.  
   - Хаотичное расположение пунктов.  
   - Только категории; нет тегов/доп. групп.  
   - Нужны: структурированные колонки, категории + теги/группы, нормальные переносы и сетка.

### 1.4 Файлы и стили, участвующие в header

| Файл | Роль |
|------|------|
| **header.php** | Разметка header, promo bar, search panel (Pattern B); critical CSS `#mnsk7-header-critical` (769/768). |
| **assets/css/parts/04-header.css** | Все стили header: promo, inner, brand, nav, menu, hamburger, sub-menu (mega), menu-toggle, actions, search (toggle + dropdown + form), account, cart, search-panel, media 993/992/900/769/430/360. |
| **assets/css/parts/25-global-layout.css** | Фон #masthead; desktop 1025px: скрытие menu-toggle/search-toggle, search dropdown static. |
| **functions.php** | Inline JS: burger toggle, search toggle, cart dropdown, promo dismiss, sticky shrink; MNSK7_BREAKPOINT_MOBILE 1024; body_class (mnsk7-search-open); фрагменты корзины. |
| **main.css** | Токены --header-h, --header-h-scrolled; --breakpoint-mobile 768, --breakpoint-tablet 900. |
| **assets/css/parts/01-tokens.css** | Те же breakpoint-токены (если подключается). |

---

## Этап 2. Header state map (целевая логика)

### 2.1 Три варианта (variants)

| Variant | Ширина (целевые breakpoints) | Элементы видимы | Search | Nav | Account/Cart | Burger |
|---------|------------------------------|-----------------|--------|-----|--------------|--------|
| **Mobile** | до B1 (например ≤767px) | Logo, burger, search icon, account icon, cart | Иконка; по клику — панель под header (отдельный блок) | Скрыт в header; по клику burger — выезд (overlay/list) | Иконки | Есть |
| **Tablet** | B1–B2 (например 768px–1024px) | Logo, nav (или burger — см. ниже), search row, account, cart | **Отдельная строка**: один составной control (input + кнопка без щели) | Либо горизонтальный nav, либо burger — по выбранной стратегии | Текст + иконка или иконки | Зависит от стратегии: если «tablet = одна строка + вторая строка поиск» — burger может быть; если «tablet = nav в строке» — нет бургера |
| **Desktop** | ≥ B2 (например ≥1025px) | Logo, nav (полное меню), inline search, account, cart | Inline в одной строке с nav (input + кнопка) | Горизонтальное меню, mega «Sklep» по hover | Account с текстом, cart | Нет |

### 2.2 Фиксация (перед правками)

- **Search:**  
  - **Mobile:** иконка в header; по клику — панель под header (как сейчас).  
  - **Tablet:** отдельная строка; input + кнопка как один составной control (без щели, одна высота, общий border/radius).  
  - **Desktop:** inline в первой строке (input + кнопка).

- **Burger:**  
  - **Mobile:** есть.  
  - **Tablet:** по решению: либо есть (тогда nav скрыт, как на mobile), либо нет (тогда nav в строке). Для устранения наездов логично: **tablet = burger + вторая строка поиск** (без полного nav в первой строке).  
  - **Desktop:** нет.

- **Mega menu «Sklep»:** только на desktop (≥ B2); на mobile/tablet — ссылка «Sklep» без dropdown. Структура: колонки (категории + при наличии теги/доп. группы), аккуратные отступы и переносы.

### 2.3 Breakpoints (предварительно для плана)

- **B0 (mobile narrow):** например 360px — только мелкие отступы/лого.  
- **B1 (mobile / tablet граница):** например **768px** — выше: tablet (вторая строка — поиск).  
- **B2 (tablet / desktop граница):** например **1025px** — выше: полный desktop (nav в строке, search inline, mega menu).  

Итоговые значения B1/B2 будут зафиксированы на Этапе 3 после замера ширины, где начинаются наезды.

### 2.4 Решение по tablet (зафиксировано)

- **Tablet layout:** первая строка: logo | burger | search icon | account | cart (как на mobile). Вторая строка: **отдельная search row** — один составной control (input + кнопка), всегда видимая на tablet (не по клику). Так устраняются наезды и выполняется требование «search на tablet — отдельная строка».
- **Mobile:** первая строка та же; search — иконка, по клику открывается панель под header (текущий Pattern B). Второй строки поиска нет.
- **Desktop:** одна строка: logo | nav | inline search | account | cart; burger скрыт; mega menu по hover.

---

## Этап 3. Breakpoint strategy

- Замерить ширину, при которой начинаются наезды (logo/nav/search/account/cart).  
- Выбрать B2 так, чтобы переключение на tablet было **до** этой ширины.  
- Унифицировать все места: header.php critical, 04-header.css, 25-global-layout.css, JS (functions.php) — один и тот же B1/B2.  
- Объяснение в плане: почему выбран именно такой порог.

### 3.1 Результат замера и выбор порогов

- **Текущий конфликт:** critical (769px) скрывает бургер и включает inline search; 04-header до 992px даёт одну строку с иконками; с 993px — полное десктоп-меню + inline search. В диапазоне **993px–1024px** (и чуть выше) в одну строку попадают logo + nav (Sklep, Przewodnik, Dostawa, Kontakt) + search (240–260px) + account + cart → наезды.
- **B1 (mobile / tablet):** **768px.** Ниже — mobile (поиск по клику, панель). Выше до B2 — tablet (вторая строка поиска всегда видна).
- **B2 (tablet / desktop):** **1025px.** Выше — полный desktop (nav в строке, inline search, mega menu). Порог 1025 уже используется в 25-global-layout и в JS; оставляем его, чтобы переключение в tablet происходило до появления полного меню в одной строке и исключить broken state.
- **Унификация:** везде использовать 768 (B1) и 1025 (B2): header.php critical, 04-header.css, 25-global-layout.css, JS. Константа `MNSK7_BREAKPOINT_MOBILE` в PHP оставить 1024 для совместимости (JS уже использует 1024/1025).

---

## Этап 4. Burger button fix

- Контейнер: фиксированный размер, выравнивание.  
- Иконка: оптическое центрирование, три линии одинаковой длины и толщины, равномерные отступы.  
- Состояния: default, active (is-open), focus-visible — без сдвига иконки.  
- Не смешивать с mega menu и tablet search.

---

## Этап 5. Tablet search component

- Отдельная строка для поиска в tablet (между первой строкой header и контентом).  
- Один составной control: input + submit button; без визуального стыка (border-right input = 0 или общий wrapper с border).  
- Одинаковая высота, согласованные border-radius (например слева у input, справа у button).  
- Проверить отсутствие конфликтов с другими элементами header.

---

## Этап 6. Desktop / tablet collision fix

- Убрать промежуточный broken state.  
- До начала наездов включать tablet layout (или mobile).  
- Проверить по отдельности: logo, nav, search, account, cart.

---

## Этап 7. Mega menu «Sklep»

- Определить структуру колонок (категории / теги / быстрые ссылки).  
- Определить данные: категории (есть), теги и доп. группы — если доступны из WP/Woo.  
- Обновить разметку/стили: сетка колонок, отступы, переносы; убрать хаотичное распределение.

---

## Этап 8. Local verification after each subtask

После каждого этапа 4–7:

- Список изменённых файлов.  
- Кратко: что сделано.  
- Какие состояния header проверены.  
- Регрессии: да/нет.

---

## Этап 9. Final header verification

Проверить и зафиксировать результат для:

- Mobile closed  
- Tablet (search row видна)  
- Desktop normal  
- Desktop mega menu open  

---

## Файлы, которые будут изменены (сводка)

| Этап | Файлы |
|------|--------|
| 3. Breakpoints | header.php, 04-header.css, 25-global-layout.css, functions.php (JS + константа) |
| 4. Burger | 04-header.css, при необходимости header.php (разметка бургера) |
| 5. Tablet search | header.php (разметка/обёртка), 04-header.css (стили search row) |
| 6. Collision | 04-header.css, 25-global-layout.css, header.php critical |
| 7. Mega menu | header.php (вывод подменю), 04-header.css (сетка/колонки), при необходимости functions.php (данные для тегов/групп) |

---

*Документ создан для последовательного выполнения этапов. После Этапа 1 (Discovery) переходим к Этапу 2 (State map), затем 3 (Breakpoints), и только потом к правкам 4–7.*

---

## Wykonanie (2026-03-11)

| Etap | Status | Zmienione pliki |
|------|--------|-----------------|
| 3. Breakpoint strategy | ✅ | header.php (769→1025, 768→1024), 04-header.css (992/993→1024/1025, 769→1025) |
| 4. Burger button | ✅ | 04-header.css (hamburger 3 linie + pseudo, menu-toggle 44×44, open bez przesunięcia) |
| 5. Tablet search | ✅ | 04-header.css (769–1024 panel zawsze widoczny, mobile tylko po kliku, jeden control) |
| 6. Collision fix | ✅ | w ramach etapu 3 (desktop od 1025px) |
| 7. Mega menu Sklep | ✅ | header.php (sekcje Kategorie + Tagi + footer), 04-header.css (.mnsk7-megamenu, grid kolumn) |
