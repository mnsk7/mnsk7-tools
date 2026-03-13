# Root cause: biała wstęga na stronie głównej (header → hero)

## Struktura DOM (header.php, front-page.php)

```
#page
├── header.mnsk7-header
├── div.mnsk7-header-search-panel   ← na tablet (769–1024px) widoczny; na desktop ukryty
└── div#content.site-content.mnsk7-content
    └── main
        └── section.mnsk7-hero
```

## Źródła białej wstęgi

### 1. Główny pas (między search bar a niebieskim hero) — **padding-top #content**

- **Gdzie:** `25-global-layout.css` — `#content, .site-content, .mnsk7-content { padding-top: var(--space-page-top); }` (desktop: 2rem).
- **Dlaczego:** Jedyny element między headerem/search panelem a `<main>` to `#content`. Hero nie ma margin-top; main nie ma margin-top. Biała przestrzeń = wyłącznie ten padding.
- **Uwaga:** Na desktopie zerowanie (body.home #content { padding-top: 0 }) działało. Na **mobile** wstęga zostawała — reguła w media `@media (max-width: 768px)` ustawia `#content { padding-top: 1.25rem }` i mogła wygrywać (kolejność/specyficzność). Fix: dodać w tym samym bloku media jawną regułę `body.home #content { padding-top: 0 }`, żeby na mobile override był gwarantowany.

### 2. Ewentualna wstęga nad search barem (tablet)

- **Gdzie:** `04-header.css` — `.mnsk7-header-search-panel { padding: 0.5rem 1rem; }` (tablet).
- **Dlaczego:** Górny padding panela dodaje odstęp między dolną krawędzią headera a górną krawędzią pola wyszukiwania. Na desktopie panel jest ukryty, więc ten odstęp nie występuje.

## Poprawka (jedna przyczyna, jeden fix)

- **#content:** Dla `body.home` ustawić `padding-top: 0` w `25-global-layout.css`: (1) reguła globalna poza media; (2) **w tym samym** `@media (max-width: 768px)` co `#content { padding-top: 1.25rem }` dodać `body.home #content, body.home .site-content, body.home .mnsk7-content { padding-top: 0 }`, żeby na mobile override był po regułe 1.25rem i działał.
- **Search panel (tablet):** Dla `body.home` w `04-header.css` w tablet: `.mnsk7-header-search-panel { padding-top: 0 }`.

## Pliki do zmiany

- `wp-content/themes/mnsk7-storefront/assets/css/parts/25-global-layout.css` — reguła globalna `body.home #content { padding-top: 0 }` + wewnątrz `@media (max-width: 768px)` po regułach `#content { padding-top: 1.25rem }` dodać tę samą regułę dla body.home (override na mobile).
