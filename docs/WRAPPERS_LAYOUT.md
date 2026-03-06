# Обёртки и разметка (mnsk7-storefront)

Переход с tech-storefront (parent: best-shop) на mnsk7-storefront (parent: Storefront). Чтобы верстка не ломалась, все обёртки должны открываться и закрываться согласованно.

---

## Текущая структура HTML

### Страницы с WooCommerce (архив, товар, корзина, чекаут)

```
#page
  #masthead.site-header
    .col-full
  #content.site-content
    #primary.content-area          ← Woo wrapper-start
      main#main.site-main          ← Woo wrapper-start
        ... контент ...
      /main                        ← Woo wrapper-end
    /#primary                      ← Woo wrapper-end
  /#content                        ← footer.php, первый </div>
  #colophon.site-footer
  /#page                           ← footer.php, второй </div>
```

- **header.php** открывает: `#page`, затем `#content` (один `</div>` в footer закрывает `#content`).
- **WooCommerce** (wrapper-start/end в теме): открывает `#primary` и `main`, закрывает их в `woocommerce_after_main_content`.
- **footer.php** закрывает: один раз перед `<footer>` — `#content`; один раз после `</footer>` — `#page`.

### Страницы без WooCommerce (front-page, страницы-шаблоны)

- **header.php** открывает: `#page`, `#content`.
- Шаблон (например front-page.php) выводит свой `<main>` и закрывает его.
- **footer.php** закрывает `#content` и `#page` так же, как выше.

---

## Файлы

| Файл | Роль |
|------|------|
| `header.php` | Открывает `#page`, внутри — хедер, затем открывает `#content`. |
| `footer.php` | Закрывает `#content`, выводит `<footer>`, закрывает `#page`. |
| `woocommerce/global/wrapper-start.php` | Выводит `#primary` и `main` (внутри уже открытого `#content`). |
| `woocommerce/global/wrapper-end.php` | Закрывает `main` и `#primary`. |

---

## Что проверять при поломке верстки

1. В DevTools посмотреть, внутри какого блока рендерится `<footer id="colophon">` — он должен быть прямым соседом `#content`, оба внутри `#page`.
2. Не должно быть лишних или недостающих `</div>`: после wrapper-end открыты только `#content` и `#page`.
3. Если родительская тема Storefront заменена или отключена, Woo по умолчанию выводит тот же default wrapper; переопределения в дочерней теме сохраняют ту же структуру.
