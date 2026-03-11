# P0 Mobile Sklep submenu — root cause and proof

## 1. DOM (przyczyna)

**Problem:** Na mobile wewnątrz пункта „Sklep” **nie było** w ogóle elementu `.sub-menu`.

W `header.php` (przed fixem):

```php
// Na mobile NIE renderowano megamenu — $render_megamenu = false gdy wp_is_mobile()
$render_megamenu = ! function_exists( 'wp_is_mobile' ) || ! wp_is_mobile();
if ( $render_megamenu && function_exists( 'mnsk7_get_megamenu_terms' ) ) {
    $terms = mnsk7_get_megamenu_terms();
    ...
}
if ( ! empty( $top_cats ) || ! empty( $top_tags ) ) {
    ?>
    <ul class="sub-menu mnsk7-megamenu"> ... </ul>
```

Gdy `wp_is_mobile()` === true: `$render_megamenu` = false → `$top_cats` i `$top_tags` pozostają puste → blok z `<ul class="sub-menu">` **nigdy nie jest wypisywany**. W DOM na mobile był tylko:

```html
<li class="menu-item-has-children">
  <a href="...">Sklep</a>
  <!-- BRAK .sub-menu -->
</li>
```

**Fix:** Megamenu renderujemy zawsze (desktop i mobile). Używamy `mnsk7_get_megamenu_terms()` (z transient cache), bez warunku `wp_is_mobile()`. Jedna struktura HTML, na mobile submenu jest w DOM i pokazywane/ukrywane przez JS (`.is-open`) i CSS.

---

## 2. JS state (było OK)

- **Toggle:** W `functions.php` pierwszy handler na `#mnsk7-primary-menu` przy kliku w `a` będący bezpośrednim dzieckiem `li.menu-item-has-children` (czyli link „Sklep”): `e.preventDefault()`, `li.classList.toggle('is-open')`, `a.setAttribute('aria-expanded', ...)`.
- **Zamykanie overlay:** Drugi handler zamyka nav (`nav.classList.remove('is-open')`) tylko gdy kliknięto w link **inny** niż parent „Sklep”: `if (parentLi && parentLi.querySelector(':scope > a') === a) return;` — tap na „Sklep” nie zamyka menu.

Bez `.sub-menu` w DOM toggle `li.is-open` i tak nie mógłby nic pokazać — stąd brak widocznego efektu.

---

## 3. CSS visibility (było OK)

W `04-header.css` w bloku `@media (max-width: 1024px)`:

- Domyślnie:  
  `.mnsk7-header__nav .mnsk7-header__menu li.menu-item-has-children .sub-menu { display: none !important; }`
- Po tap (JS dodaje `li.is-open`):  
  `.mnsk7-header__nav .mnsk7-header__menu li.menu-item-has-children.is-open .sub-menu { display: flex !important; }`

Selektory pasują do faktycznego DOM (`.mnsk7-header__nav` → `.mnsk7-header__menu` → `li.menu-item-has-children` → `.sub-menu`). Gdyby `.sub-menu` był w DOM i `li` dostał `is-open`, submenu byłoby widoczne. Problem był wyłącznie w braku `.sub-menu` na mobile.

---

## Proof — co sprawdzić po wdrożeniu

### HTML mobile menu dla Sklep (po fixie)

Oczekiwany fragment (po otwarciu menu burtgera):

```html
<ul id="mnsk7-primary-menu" class="mnsk7-header__menu">
  <li class="menu-item-has-children">
    <a href="..." aria-haspopup="true" aria-expanded="false">Sklep</a>
    <ul class="sub-menu mnsk7-megamenu">
      <li class="mnsk7-megamenu__group">...</li>
      <li class="mnsk7-megamenu__group">...</li>
      <li class="mnsk7-megamenu__footer">...</li>
    </ul>
  </li>
  ...
</ul>
```

W DevTools: wewnątrz `li` z tekstem „Sklep” musi być `ul.sub-menu.mnsk7-megamenu`.

### Przed tapem

- `li.menu-item-has-children` **bez** klasy `is-open`.
- `a` ma `aria-expanded="false"`.
- Dla `.sub-menu`: computed style `display: none` (z reguły z `!important`).

### Po tapie na „Sklep”

- Na `li` pojawia się klasa `is-open`.
- `a` ma `aria-expanded="true"`.
- Dla `.sub-menu`: computed style `display: flex`.
- Pod listą „Sklep” widać grupy (Rodzaje frezów, Zastosowanie…) i link „Wszystkie produkty”.

### Zmienione pliki

- `wp-content/themes/mnsk7-storefront/header.php` — usunięcie warunku `$render_megamenu` opartego na `wp_is_mobile()`, zawsze pobieranie termów i renderowanie `<ul class="sub-menu mnsk7-megamenu">` gdy są dane.
