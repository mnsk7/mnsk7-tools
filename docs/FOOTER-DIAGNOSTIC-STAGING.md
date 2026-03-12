# Диагностика mobile footer на staging — proof и root cause

---

## Кратко по-русски

**Статус:** задача не закрыта, пока нет proof на staging (Network, DOM, Console, скрины).

**Сейчас известно:** CSS и разметка футера на месте (есть trigger, panel, mobile layout, +/−). Проблема не в «не том main.css», а в **runtime**:

1. **Битый JS:** в консоли `Uncaught SyntaxError: Unexpected token '<'` (источник типа inter-latin-wght-norm…) — по URL скрипта сервер отдаёт HTML (404/403). Часто из-за плагина cache/minify (`/wp-content/cache/min/...`). В теме шрифт только в CSS и `<link rel="preload" as="font">`, не как `<script>`.
2. **Аккордеон не раскрывается:** разметка правильная; в консоли проверить §A.3 и §B.2 — меняются ли после клика `aria-expanded` и `is-open` (id кнопки: **footer-trigger-kategorie**, не "categories").
3. **Панель Newsletter пустая в DOM:** в `footer.php` контент без условия. Если на staging пусто — смотреть кэш страницы или другую версию шаблона; в View Source проверить, есть ли внутри `#footer-panel-newsletter` дети (в т.ч. комментарий `<!-- mnsk7-footer newsletter -->`).
4. **Cookie bar нет в DOM:** если consent уже сохранён (cookie/localStorage), PHP не выводит блок. Иначе проверить условие `$show_cookie_bar_markup` и шаблон.

**Чеклист проверки (именно в таком порядке):**

| Где | Что сделать |
|-----|-------------|
| **View Source** (не инспектор) | Найти `footer-panel-newsletter`. Проверить: есть ли `<!-- mnsk7-footer newsletter -->`; есть ли внутри форма и текст. |
| **Network** | Найти битый asset, по которому в консоли `Unexpected token '<'`. Открыть Response. Если там HTML вместо JS — это конкретный баг minify/cache. |
| **Console** | Выполнить тест с `#footer-trigger-kategorie`: смотреть, меняются ли `aria-expanded`, `is-open`, `display` у панели после клика. |
| **Cookie bar** | Проверить, есть ли его DOM-узел вообще (`#mnsk7-cookie-bar`). Если нет — смотреть PHP-условие в `footer.php`. Если есть — смотреть computed styles (display, visibility, hidden). |

**Фикс в коде:** скрипт аккордеона вынесен в отдельный файл `assets/js/footer-accordion.js` и подключается через `wp_enqueue_script` (в footer). Так minify/combine не объединяет его с битым ассетом; добавлены try-catch и retry при отсутствии `#colophon` в DOM.

**Итог:** Мыслить начали в правильную сторону; в код добавлен полезный маркер (`<!-- mnsk7-footer newsletter -->`). **Принимать тут пока нечего — пользовательский результат всё ещё сломан.** Фикс считается принятым только после proof по чеклисту и работающего accordion/cookie bar на staging.

**Что делать:** выполнить §A и §B (команды в Console + Network), зафиксировать результаты как proof. Дальше чинить runtime: minify/asset, при необходимости toggle, пустой Newsletter (кэш/деплой), cookie bar.

---

**Status (PL):** nie zamknięta, dopóki nie ma proof na staging (Network, DOM, Console, Computed, skriny).

**Gdy style już działają (layout mobile, +/−), a accordion nie otwiera się i cookie bar nie widać:** root cause to runtime (broken JS asset, toggle, pusty Newsletter, cookie bar), nie „który CSS”. **Wykonać §A (Runtime debugging)** i **§B (Potwierdzone problemy)** — Console + Network, wyniki jako proof. Kolejna robota: rozkminić broken runtime (minify/asset, accordion toggle, pusty panel, cookie bar), nie „pofixować style”.

---

## 0. Схема подключения CSS (после рефактора)

В runtime **всегда** грузится один файл: `assets/css/main.css`. Папка `parts/` используется только как источник для сборки (`scripts/build-main-css.sh`). Двойная схема «если есть parts — parts, иначе main» убрана — она и создавала баг (staging мог отдавать старый main или разъехавшиеся parts).

---

## 1. Какой шаблон footer реально рендерится

**Ожидание:** используется `wp-content/themes/mnsk7-storefront/footer.php` (дочерняя тема).

**Проверка на staging:**

1. В DevTools → Elements найти `<footer id="colophon" class="mnsk7-footer">`.
2. Проверить наличие в DOM:
   - `button.mnsk7-footer__accordion-trigger` (не `h3.mnsk7-footer__title`);
   - `div.mnsk7-footer__accordion-panel`;
   - `#footer-panel-klient`, `#footer-panel-kategorie` и т.д.

**Если в DOM есть `h3.mnsk7-footer__title` и нет `button.mnsk7-footer__accordion-trigger`** → на сервере старая версия `footer.php`. Нужно задеплоить актуальный `footer.php`.

**Путь к файлу в теме:**  
`get_footer()` без аргументов загружает `footer.php` из активной темы. Для `get_footer('shop')` (архив/товар) подключается `footer-shop.php`, который делает `require get_stylesheet_directory() . '/footer.php'`. Итого: **всегда используется один файл — `mnsk7-storefront/footer.php`.**

---

## 2. Подключён ли нужный CSS

**Логика в `functions.php`:** в runtime подключается только `main.css` (jeden plik, jedna strategia).

**Проверка на staging:**

1. **Network:** отфильтровать по CSS. Должен загружаться один файл: `.../assets/css/main.css` (bez mnsk7-parts-*).
2. **Sources:** открыть `main.css` и поискать:
   - `mnsk7-footer__accordion-trigger`
   - `mnsk7-footer__accordion-panel`
   - `@media (max-width: 768px)` и внутри — `.mnsk7-footer__col.is-open`.

**Если этих селекторов в загруженном main.css нет** → на сервере старый main.css; задеплоить пересобранный (run `scripts/build-main-css.sh`, commit + deploy).

---

## 3. Root cause (итог по коду)

Раньше: две стратегии — «есть parts → грузим parts, нет parts → main.css». Staging мог отдавать старый main.css (без 09-footer) или разъехавшиеся parts → футер/header/PDP получали не тот CSS.

Сейчас: одна стратегия — всегда грузится только `main.css`. Parts — только источник для сборки. На staging нужно задеплоить актуальный пересобранный `main.css`.

---

## A. Runtime debugging (live staging) — obowiązkowy proof

**Gdy style już dojechały, a accordion nie otwiera się i cookie bar nie widać:** problem to JS bind / DOM / logika, nie „który CSS”. W DevTools Console wykonać poniższe i zapisać wyniki.

### A.1 Footer — liczba triggerów i paneli

```js
document.querySelectorAll('.mnsk7-footer__accordion-trigger').length
// Oczekiwane: 4. Jeśli 0 — na staging inna (stara) markup lub cache.

document.querySelectorAll('.mnsk7-footer__accordion-panel').length
// Oczekiwane: 4. Jeśli mniej — rozjechana struktura footer.
```

### A.2 Footer — struktura jednej sekcji (Kategorie)

ID w kodzie to **footer-trigger-kategorie** (nie „categories”):

```js
var btn = document.querySelector('#footer-trigger-kategorie') || document.querySelector('.mnsk7-footer__accordion-trigger');
btn;
btn && btn.closest('.mnsk7-footer__col');
btn && btn.nextElementSibling;
// nextElementSibling musi być .mnsk7-footer__accordion-panel. W przeciwnym razie DOM nie zgadza się z JS/CSS.
```

### A.3 Footer — czy klik zmienia state

```js
var btn = document.querySelector('#footer-trigger-kategorie') || document.querySelector('.mnsk7-footer__accordion-trigger');
var col = btn && btn.closest('.mnsk7-footer__col');
if (btn && col) { btn.click(); }
console.log({
  expanded: btn ? btn.getAttribute('aria-expanded') : null,
  isOpen: col ? col.classList.contains('is-open') : null,
  next: btn ? (btn.nextElementSibling && btn.nextElementSibling.className) : null
});
// Oczekiwane po kliku: expanded "true", isOpen true, next zawiera "mnsk7-footer__accordion-panel".
// Jeśli expanded/isOpen się nie zmieniają — JS toggle nie działa (błąd wyżej, zły bind, inna logika).
```

### A.4 Footer — ręczny test CSS (czy panel w ogóle reaguje na is-open)

```js
var btn = document.querySelector('#footer-trigger-kategorie') || document.querySelector('.mnsk7-footer__accordion-trigger');
var col = btn && btn.closest('.mnsk7-footer__col');
if (col) { col.classList.add('is-open'); btn.setAttribute('aria-expanded', 'true'); }
// Jeśli po tym panel się pojawił — problem w JS (bind/toggle). Jeśli nie — problem w CSS lub DOM (selektor / struktura).
```

### A.5 Cookie bar — czy w ogóle jest w DOM

```js
document.querySelector('#mnsk7-cookie-bar') || document.querySelector('.mnsk7-cookie-bar') || document.querySelector('[class*="cookie"]');
// null = banku nie ma w HTML. Powód: PHP nie renderuje go, gdy consent już ustawiony (cookie/localStorage),
// albo inny szablon strony / błąd przed get_footer(). W footer.php bank jest w if ($show_cookie_bar_markup),
// a $show_cookie_bar_markup = ( consent !== 'accept' && consent !== 'reject' ). Do testu: incognito bez wcześniejszego Accept.
```

Jeśli element **jest** w DOM:

```js
var bar = document.querySelector('#mnsk7-cookie-bar');
bar && getComputedStyle(bar).display;
bar && getComputedStyle(bar).visibility;
bar && getComputedStyle(bar).opacity;
bar && getComputedStyle(bar).getPropertyValue('z-index');
// oraz bar.getAttribute('hidden') — jeśli hidden, JS mógł nie wywołać show() (błąd w skrypcie wyżej / consent już zapisany).
```

**Gdzie bank jest w kodzie:** `footer.php` po `</footer>`, przed skryptem accordionu; warunek `$show_theme_cookie_bar && ( consent !== 'accept' && consent !== 'reject' )`. Jeśli consent w cookie/localStorage jest ustawiony, PHP w ogóle nie wypisuje diva — wtedy w DOM go nie ma.

---

## B. Potwierdzone problemy (staging): broken asset, toggle, pusty Newsletter, cookie bar

**Co już wiadomo:** DOM footera jest poprawny (trigger + panel), style dojechały; problem to runtime: błąd JS, toggle, pusty panel Newsletter, brak cookie bar.

### B.1 Błąd JS: `Uncaught SyntaxError: Unexpected token '<'` (źródło: inter-latin-wght-norm...)

**Znaczenie:** Przeglądarka dostała HTML zamiast JavaScriptu (np. 404/403, strona błędu). Często przy skryptach z `/wp-content/cache/min/...` — pipeline minify/cache zwraca zły plik.

**W temacie:** Font Inter jest tylko w CSS (`@font-face` w `00-fonts-inter.css`) i w `header.php` jako `<link rel="preload" as="font">`. Tema **nie** ładuje tego adresu jako `<script>`. Źródło błędu to najpewniej plugin cache/minify.

**Na staging:** W Network znaleźć request z błędem (URL z konsoli). Sprawdzić Request URL, Response headers (Content-Type), body. Jeśli body to HTML — naprawić plugin cache/minify lub wyłączyć minifikację JS.

### B.2 Sprawdzenie toggle (id = **footer-trigger-kategorie**, nie "categories")

```js
var btn = document.querySelector('#footer-trigger-kategorie');
var col = btn && btn.closest('.mnsk7-footer__col');
var panel = btn && btn.nextElementSibling;
console.log({ beforeExpanded: btn && btn.getAttribute('aria-expanded'), beforeOpen: col && col.classList.contains('is-open'), beforeDisplay: panel && getComputedStyle(panel).display });
if (btn) btn.click();
console.log({ afterExpanded: btn && btn.getAttribute('aria-expanded'), afterOpen: col && col.classList.contains('is-open'), afterDisplay: panel && getComputedStyle(panel).display });
```

**Interpretacja:** Brak zmiany `afterExpanded`/`afterOpen` → JS nie działa (np. przez broken asset). State się zmienia, `afterDisplay` nadal `"none"` → problem w CSS. State i display się zmieniają → accordion OK po naprawie assetu.

### B.3 Pusty panel Newsletter

W `footer.php` zawartość `#footer-panel-newsletter` (opis, formularz, link) jest **bez warunku** — zawsze w szablonie. Pusty panel na stagingu → cache strony (stary HTML), inna wersja `footer.php`, lub plugin usuwa treść. Sprawdzić: View Source / response HTML — czy wewnątrz `#footer-panel-newsletter` są dzieci; wyłączyć cache strony.

### B.4 Cookie bar nie w DOM

Patrz §A.5. `null` = PHP nie renderuje (consent już ustawiony) lub inny szablon. Sprawdzić warunek `$show_cookie_bar_markup` w `footer.php`.

---

## 4. Referencja: DOM jednej sekcji i reguła otwierająca

**Oczekiwany fragment HTML jednej kolumny (np. Newsletter):**

```html
<div id="footer-col-newsletter" class="mnsk7-footer__col mnsk7-footer__col--newsletter" aria-label="…">
  <button type="button" class="mnsk7-footer__accordion-trigger" id="footer-trigger-newsletter"
          aria-expanded="false" aria-controls="footer-panel-newsletter">
    <span class="mnsk7-footer__accordion-title">Newsletter</span>
    <span class="mnsk7-footer__accordion-icon" aria-hidden="true"></span>
  </button>
  <div id="footer-panel-newsletter" class="mnsk7-footer__accordion-panel" role="region" aria-labelledby="footer-trigger-newsletter">
    <p class="mnsk7-footer__newsletter-desc">…</p>
    <form class="mnsk7-footer__newsletter-form" …>…</form>
    <p class="mnsk7-footer__newsletter-privacy">…</p>
  </div>
</div>
```

**Reguła CSS, która faktycznie pokazuje panel (mobile):**

```css
@media (max-width: 768px) {
  .mnsk7-footer__accordion-panel { display: none !important; }
  .mnsk7-footer__col.is-open > .mnsk7-footer__accordion-panel { display: block !important; }
}
```

Klasa `is-open` wisi na **rodzicu** `.mnsk7-footer__col`, a nie na panelu. Panel musi być **bezpośrednim dzieckiem** (>) tej kolumny.

**W DevTools przed klikiem:** u `.mnsk7-footer__accordion-panel` computed `display` = none.  
**Po kliknięciu:** na `div.mnsk7-footer__col` pojawia się klasa `is-open`; u `.mnsk7-footer__accordion-panel` computed `display` = block.

---

## 5. Есть ли на странице нужные селекторы (DOM)

В DevTools → Elements в дереве футера проверить:

- Есть ли элементы с классами `mnsk7-footer__accordion-trigger`, `mnsk7-footer__accordion-panel`.
- Есть ли у колонок класс `mnsk7-footer__col` и при открытии — `is-open` на родителе.

Если структура с `button` и `panel` есть, а поведение неверное — дальше смотреть пункты 5–6.

---

## 6. Работает ли JS

1. **Console:** при загрузке и при тапе по секции футера не должно быть ошибок.
2. **Проверка listener:** в Console выполнить:
   ```js
   document.querySelectorAll('.mnsk7-footer__accordion-trigger').length
   ```
   Должно быть 4. Если 0 — в HTML нет новых триггеров (старый footer.php).
3. **Проверка toggle:** при клике по заголовку секции в Elements проверить у соответствующего `div.mnsk7-footer__col` появление/исчезновение класса `is-open`.

Если класс `is-open` не ставится — проблема в JS (не срабатывает или не тот элемент). Если ставится, а вид не меняется — проблема в CSS (правила не применяются или перебиты).

---

## 7. Конфликтующие правила (computed styles)

Для элемента `button.mnsk7-footer__accordion-trigger` (в мобильном виде):

- Во вкладке **Computed** проверить, откуда берутся `background`, `border`, `box-shadow`, `padding`.
- Если там фигурируют стили из Storefront, WooCommerce или глобальных кнопок — они перебивают наши. В 09-footer.css для триггера заданы `appearance: none`, `box-shadow: none`, `background: transparent`/`none`; при необходимости усилить селектор или порядок загрузки.

Для `.mnsk7-footer__accordion-panel` и `.mnsk7-footer__col.is-open`:

- В мобильном media query должно быть: панель по умолчанию `display: none`, при `.mnsk7-footer__col.is-open` — `display: block`. В Computed проверить, какое правило в итоге применяется и какой файл его задаёт.

---

## 8. Сборка main.css przed деплоем

main.css — jedyny plik CSS tematy w runtime. Po zmianach w `parts/*.css` trzeba go przebudować i wrzucić w deploy:

```bash
cd wp-content/themes/mnsk7-storefront
bash scripts/build-main-css.sh
```

Skrypt skleja wszystkie parts w kolejności i nadpisuje `assets/css/main.css`. Potem commit + deploy zaktualizowanego `main.css`.

---

## 9. Obowiązkowy proof na staging (zadanie nie zamknięte bez tego)

- [ ] **Network:** skrin — widać, że ładuje się `main.css` (i nie parts/*).
- [ ] **DOM:** skrin jednej sekcji footera — widać `button.mnsk7-footer__accordion-trigger` i `div.mnsk7-footer__accordion-panel` (nie h3).
- [ ] **Console/DOM:** po kliku sekcja dostaje `is-open` na `.mnsk7-footer__col`, panel się otwiera/zamyka.
- [ ] **Computed:** mobile rules (np. display dla panelu) wygrywają.
- [ ] **Skriny końcowe (mobile):** 2–3 z działającym accordionem — sekcje otwierają się/zamykają, brak domyślnego wyglądu przycisków, brak niebieskiego zaznaczenia tekstu.

**Cache:** przed proof sprawdzić i w razie potrzeby wyczyścić: cache przeglądarki, cache serwera, cache pluginu/minify, CDN.
