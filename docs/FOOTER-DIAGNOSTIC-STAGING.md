# Диагностика mobile footer на staging — proof и root cause

**Цель:** установить, почему на странице результат другой (accordion не работает, дефолтные стили кнопки, desktop layout на mobile).

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

**Логика в `functions.php` (около 672–686):**

- Перебираются файлы из папки `assets/css/parts/` (00-fonts-inter.css … 25-global-layout.css).
- Для **каждого существующего** файла подключается отдельный `<link>` (handle `mnsk7-parts-09-footer` и т.д.).
- **main.css подключается только если ни один part не найден** (`$parts_loaded === false`), т.е. когда папка `parts/` пуста или отсутствует.

**Проверка на staging:**

1. **Network:** отфильтровать по CSS. Проверить, что загружаются либо:
   - `.../assets/css/parts/09-footer.css`, `10-cookie-bar.css`, `21-responsive-mobile.css`,  
   либо
   - один файл `main.css` (если parts не задеплоены).
2. **Sources:** открыть загруженный CSS (09-footer.css или main.css) и поискать по тексту:
   - `mnsk7-footer__accordion-trigger`
   - `mnsk7-footer__accordion-panel`
   - `@media (max-width: 768px)` и внутри — правила для `.mnsk7-footer__col.is-open`.

**Если в загруженном CSS этих селекторов нет** → на сервере либо старая версия parts, либо старый main.css.  
**Решение:** задеплоить актуальные `parts/09-footer.css`, `10-cookie-bar.css`, `21-responsive-mobile.css` **или** один пересобранный `main.css` (см. ниже).

---

## 3. Root cause (итог по коду)

| Ситуация на сервере | Что грузится | Результат |
|---------------------|--------------|-----------|
| Есть папка `parts/` с актуальными файлами | Отдельные parts, в т.ч. 09-footer.css | Стили и accordion должны работать. |
| Папка `parts/` пуста или отсутствует | Только `main.css` | Раньше main.css **не содержал** 09-footer (не был собран из parts) → футер без стилей, дефолтные кнопки, нет mobile accordion. |
| Задеплоен старый main.css / старые parts | Старый CSS | Селекторы `.mnsk7-footer__accordion-*` отсутствуют, остаются старые правила под `.mnsk7-footer__title` или вообще без футера. |

**Исправление в репозитории:**

1. **main.css пересобран из всех parts** скриптом `scripts/build-main-css.sh` — в нём есть и 09-footer, и mobile accordion.
2. После деплоя при отсутствии parts будет подключаться актуальный main.css со всеми стилями футера.

**На staging нужно:** задеплоить либо обновлённые `parts/` (в т.ч. 09, 10, 21), либо новый `main.css`, либо и то и другое.

---

## 4. Есть ли на странице нужные селекторы (DOM)

В DevTools → Elements в дереве футера проверить:

- Есть ли элементы с классами `mnsk7-footer__accordion-trigger`, `mnsk7-footer__accordion-panel`.
- Есть ли у колонок класс `mnsk7-footer__col` и при открытии — `is-open` на родителе.

Если структура с `button` и `panel` есть, а поведение неверное — дальше смотреть пункты 5–6.

---

## 5. Работает ли JS

1. **Console:** при загрузке и при тапе по секции футера не должно быть ошибок.
2. **Проверка listener:** в Console выполнить:
   ```js
   document.querySelectorAll('.mnsk7-footer__accordion-trigger').length
   ```
   Должно быть 4. Если 0 — в HTML нет новых триггеров (старый footer.php).
3. **Проверка toggle:** при клике по заголовку секции в Elements проверить у соответствующего `div.mnsk7-footer__col` появление/исчезновение класса `is-open`.

Если класс `is-open` не ставится — проблема в JS (не срабатывает или не тот элемент). Если ставится, а вид не меняется — проблема в CSS (правила не применяются или перебиты).

---

## 6. Конфликтующие правила (computed styles)

Для элемента `button.mnsk7-footer__accordion-trigger` (в мобильном виде):

- Во вкладке **Computed** проверить, откуда берутся `background`, `border`, `box-shadow`, `padding`.
- Если там фигурируют стили из Storefront, WooCommerce или глобальных кнопок — они перебивают наши. В 09-footer.css для триггера заданы `appearance: none`, `box-shadow: none`, `background: transparent`/`none`; при необходимости усилить селектор или порядок загрузки.

Для `.mnsk7-footer__accordion-panel` и `.mnsk7-footer__col.is-open`:

- В мобильном media query должно быть: панель по умолчанию `display: none`, при `.mnsk7-footer__col.is-open` — `display: block`. В Computed проверить, какое правило в итоге применяется и какой файл его задаёт.

---

## 7. Сборка main.css (для деплоя без parts)

Если на staging подключается только main.css, он должен быть собран из актуальных parts:

```bash
cd wp-content/themes/mnsk7-storefront
bash scripts/build-main-css.sh
```

Скрипт конкатенирует все parts в порядке из `functions.php` и перезаписывает `assets/css/main.css`. После этого задеплоить обновлённый `main.css`.

---

## 8. Checklist после исправления

- [ ] В DOM есть `button.mnsk7-footer__accordion-trigger` и `div.mnsk7-footer__accordion-panel` (не старый h3).
- [ ] В загруженном CSS есть селекторы `mnsk7-footer__accordion-trigger`, `mnsk7-footer__accordion-panel`, mobile `@media (max-width: 768px)` с `.mnsk7-footer__col.is-open`.
- [ ] При тапе по секции класс `is-open` появляется/снимается с `.mnsk7-footer__col`, панель открывается/закрывается.
- [ ] Нет синего выделения текста при тапе (есть `user-select: none`, `-webkit-tap-highlight-color: transparent`).
- [ ] Нет дефолтного вида кнопки (есть `appearance: none`, `box-shadow: none`, нужный background/border).
- [ ] На ширине ≤768px футер в одну колонку (accordion), не 4 колонки как на desktop.
