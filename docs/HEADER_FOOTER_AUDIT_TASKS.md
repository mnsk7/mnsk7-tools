# Header & Footer — Visual Audit Tasks (MNsk7-Tools)

Reference: *Visual Audit & Recommendations for the MNsk7-Tools Staging Site*.  
Breakdown of tasks for the developer (Cursor) with status.

---

## Header

### Structure & Layout

| Task | Status | Notes |
|------|--------|--------|
| Logo prominently top-left, link to homepage | ✅ | `header.php` — `.mnsk7-header__brand` |
| Sticky header that shrinks on scroll | ✅ | `.mnsk7-header--scrolled` + scroll listener; `min-height` 52px → 44px |
| Promo bar above header (dismissible) | ✅ | Filter `mnsk7_header_promo_text`; sessionStorage dismiss |
| Search bar **visible** on desktop (not behind icon) | ✅ | Inline form `.mnsk7-header__search-form--inline` on ≥769px; icon + dropdown on mobile |
| Navigation: megamenu / grouped categories | ✅ | Sklep dropdown: 2-column grid, min-width 320px |
| Clear CTAs: Moje konto, cart icon + counter, Sklep | ✅ | Account link, cart with badge, menu Sklep |
| Hamburger on mobile, full-height panel with close | ✅ | `.mnsk7-header__menu-toggle`, `.mnsk7-header__nav.is-open` |

### Behaviour & Accessibility

| Task | Status | Notes |
|------|--------|--------|
| Hover & focus states on menu and buttons | ✅ | `:hover`, `:focus-visible` in `04-header.css` |
| Accessible dropdowns (keyboard, aria) | ⚠️ | `aria-expanded`, `aria-controls` on toggle; consider full keyboard nav for sub-menu |
| Cart icon with numeric badge | ✅ | `.mnsk7-header__cart-count`, fragments update |
| Language switcher (if needed) | ➖ | Optional; not implemented |

---

## Footer

### Structure & Content

| Task | Status | Notes |
|------|--------|--------|
| Multi-column layout with clear labels | ✅ | Newsletter, Klient, Kategorie, Dostawa, Kontakt |
| Essential links (Sklep, Dostawa, Regulamin, Polityka, Moje konto) | ✅ | In “Klient” column |
| Kategorie column (product categories) | ✅ | Top-level terms, 8 max |
| Kontakt: email, phone, hours, Instagram with icons | ✅ | `.mnsk7-footer__contact-list` + icons |
| Newsletter + privacy disclaimer | ✅ | “Możesz w każdej chwili wypisać się…” |
| Shipping & returns summary | ✅ | “Dostawa” column |
| Cookie banner: Ustawienia, Więcej informacji | ✅ | In `footer.php` / theme; link to polityka#cookies |
| Copyright line | ✅ | `.mnsk7-footer__copy` |

### Mobile

| Task | Status | Notes |
|------|--------|--------|
| Stack columns vertically | ✅ | `grid-template-columns: 1fr` at ≤768px |
| Accordions per column | ✅ | Click title toggles `.is-open`; first column (Newsletter) open by default |
| Touch targets ≥44px | ✅ | Title area padding, button size |

### Optional / Future

| Task | Status | Notes |
|------|--------|--------|
| Trust badges (SSL, payment icons, Allegro) | ➖ | Placeholder or block in footer |
| Physical address in Kontakt | ➖ | Add to template if needed |

---

## Testing Checklist

- [ ] **Breakpoints**: 320px, 480px, 768px, 1024px — header and footer layout and behaviour.
- [ ] **Sticky**: Scroll page — header stays visible and shrinks after ~50px.
- [ ] **Promo bar**: If `mnsk7_header_promo_text` returns non-empty, bar shows; close hides for session.
- [ ] **Search**: Desktop — inline field visible; mobile — icon opens dropdown.
- [ ] **Cart**: Add to cart — badge updates; click opens dropdown on desktop.
- [ ] **Footer accordions**: Mobile — only Newsletter open; click other titles to expand/collapse.
- [ ] **WCAG**: Contrast, focus visible, aria where used.

---

## Enabling the promo bar

In theme `functions.php` or a plugin:

```php
add_filter( 'mnsk7_header_promo_text', function() {
	return 'Darmowa dostawa od 300 zł · Kliknij <a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">Sklep</a>';
} );
```

Leave filter unused or return `''` to hide the promo bar.
