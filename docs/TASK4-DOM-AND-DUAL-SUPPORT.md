# Task 4: пруф DOM и dual-support

## Красный флаг №1 — дублирующийся `#content`

**Проверка по фактическому коду (не по отчёту):**

### header.php

В файле **одна** строка с открывающим `#content`:

```php
<div id="content" class="site-content mnsk7-content">
```

(Строка 168, конец файла. Перед ней нет второго `<div id="content">`.)

### wrapper-start.php

Открывает только `#primary` и `<main id="main">` — **не** открывает новый `#content`:

```php
<div id="primary" class="content-area mnsk7-content-area">
	<main id="main" class="site-main mnsk7-main" role="main">
```

### footer.php

Закрывает один контейнер:

```php
	</div><!-- #content -->
```

**Итог:** В DOM ровно один элемент с `id="content"`. Вложенного второго `#content` нет. Цепочка: header открывает `#content` → wrapper-start добавляет внутрь `#primary` и `main` → контент → footer закрывает `#content`.

Если где-то отображалось «три варианта» — это могла быть интерпретация diff (было / конфликт / стало), а не три реальных div в разметке.

---

## Красный флаг №2 — dual-support

По задумке задача 4: **добавить** классы `.mnsk7-content`, `.mnsk7-content-area`, `.mnsk7-main` и **оставить старые селекторы параллельно** (#content, #primary), чтобы не ловить регрессию на cart / account / PDP / filter / checkout.

Факт: в теме был сделан массовый переход на селекторы по классам, старые #content/#primary в CSS убраны. Это увеличивает риск регрессии.

**Что сделано:** В CSS части темы восстановлен dual-support: везде, где используются `.mnsk7-content` / `.mnsk7-content-area` / `.mnsk7-main`, добавлены эквиваленты по `#content`, `#primary`, `main` (или `#main`) через запятую. Один и тот же стиль применяется и к старой разметке (только id), и к новой (id + класс). После проверки ключевых страниц старые селекторы можно будет убрать отдельным шагом.

---

## Что проверить вручную (регрессия Task 4)

- [ ] Cart — вёрстка, отступы
- [ ] Checkout — то же
- [ ] Account (Moje konto) — контент, ширина
- [ ] PDP (single product) — без сайдбара, ширина
- [ ] PLP (sklep, kategoria, tag) — сетка, сайдбар при наличии
- [ ] Strony z ?filter_* — layout jak wcześniej
- [ ] Search results (product search) — nie archive-product layout

После проверки — można uznać Task 4 za zamknięty z dual-support; ewentualne usunięcie starych selektorów — osobna, późniejsza zmiana.
