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

## Auto-check (jeden #content)

Skrypt **`scripts/task4-regression-check.sh`**: sprawdza, że na każdej stronie jest dokładnie jeden `id="content"`.

- `BASE_URL=https://staging.mnsk7-tools.pl ./scripts/task4-regression-check.sh`
- W skrypcie jest **stały PDP URL** (`FIXED_PDP_PATH`); można nadpisać: `PDP_URL=… ./scripts/...`

**Przyjęte jako dowiedzione:** na sprawdzonych stronach ровно один #content; product search nie jest podmien­niany przez nasz archive-product.php (mnsk7_is_plp() → false przy is_search()).

---

## Ręczna / Browser‑weryfikacja (wymagana do akceptacji Task 4)

**Task 4 NIE jest przyjęta**, dopóki nie przejdzie pełna weryfikacja wizualna po **deployu i cache purge**.

Dla każdej strony z listy wpisać w tabeli: layout / wrapper / spacing / header / sidebar.

| Strona | layout ok? | wrapper ok? | spacing ok? | header ok? | sidebar ok? |
|--------|------------|-------------|-------------|------------|-------------|
| `/` (home) | | | | | n/a |
| `/koszyk/` (cart) | | | | | n/a |
| `/zamowienie/` (checkout) | | | | | n/a |
| `/moje-konto/` (account) | | | | | n/a |
| `/sklep/` (PLP) | | | | | ok / n/a |
| search (`/?s=frezy&post_type=product`) | | | | | n/a |
| PLP + filter (`/sklep/?filter_...`) | | | | | ok / n/a |
| PDP (single product; URL ze skryptu) | | | | | n/a |

- **layout ok / not ok** — ogólny układ strony, bez rozjechanych bloków.
- **wrapper ok / not ok** — #content/#primary/main w jednym, prawidłowa hierarchia.
- **spacing ok / not ok** — odstępy, padding, max-width kontenera.
- **header ok / not ok** — nagłówek (logo, menu, koszyk) wyświetla się poprawnie.
- **sidebar ok / n/a** — tam gdzie sidebar jest (PLP z filtrami), że jest ok; na cart/checkout/PDP — n/a.

**Dopiero po wypełnieniu tej tabeli (wszystkie ok) i zapisie wyniku — Task 4 accepted.** Zadania 5–8 nie wykonywać do akceptacji Task 4.
