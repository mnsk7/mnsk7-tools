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
- **Poprawka bez !important:** Zamiast nadpisywać `#content { padding-top: 1.25rem }` na mobile przez `body.home #content { padding-top: 0 }`, regułę mobile ograniczyć do stron innych niż główna: `body:not(.home) #content { padding-top: 1.25rem }`. Wtedy główna w ogóle nie dostaje 1.25rem — jedyna obowiązująca reguła to globalna `body.home #content { padding-top: 0 }`. Brak potrzeby override’u i !important.

### 2. Ewentualna wstęga nad search barem (tablet)

- **Gdzie:** `04-header.css` — `.mnsk7-header-search-panel { padding: 0.5rem 1rem; }` (tablet).
- **Dlaczego:** Górny padding panela dodaje odstęp między dolną krawędzią headera a górną krawędzią pola wyszukiwania. Na desktopie panel jest ukryty, więc ten odstęp nie występuje.

## Poprawka (jedna przyczyna, bez !important)

- **#content:** (1) Globalnie `body.home #content { padding-top: 0 }`. (2) W `@media (max-width: 768px)` **nie** ustawiać `padding-top: 1.25rem` dla wszystkich — tylko dla `body:not(.home) #content`. Dzięki temu na głównej na mobile nigdy nie jest ustawiane 1.25rem; obowiązuje wyłącznie globalne zerowanie. Nie trzeba override’u ani !important.
- **Search panel (tablet):** Dla `body.home` w `04-header.css` w tablet: `.mnsk7-header-search-panel { padding-top: 0 }`.

## Pliki

- `25-global-layout.css` — reguła globalna `body.home #content { padding-top: 0 }`; w media (max-width: 768px) reguła `body:not(.home) #content, body:not(.home) .site-content, body:not(.home) .mnsk7-content { padding-top: 1.25rem }` (padding-left/right bez zmian dla wszystkich).
