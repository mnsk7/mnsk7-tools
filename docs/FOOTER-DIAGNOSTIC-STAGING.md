# Диагностика mobile footer на staging — proof и root cause

**Статус задачи:** не закрыта, пока нет proof на самом staging (Network, DOM, Console, Computed, скриншоты после деплоя).

**Цель:** установить, какой CSS реально грузится и что в DOM; подтвердить рабочий accordion после деплоя.

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
