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
- **Błąd w poprzednim fixie:** Zerowano `padding-top` tylko w `@media (max-width: 768px)` (mobile). Na **tablet i desktop** (769px+) nadal obowiązywał `padding-top: 2rem` → wstęga zostawała.

### 2. Ewentualna wstęga nad search barem (tablet)

- **Gdzie:** `04-header.css` — `.mnsk7-header-search-panel { padding: 0.5rem 1rem; }` (tablet).
- **Dlaczego:** Górny padding panela dodaje odstęp między dolną krawędzią headera a górną krawędzią pola wyszukiwania. Na desktopie panel jest ukryty, więc ten odstęp nie występuje.

## Poprawka (jedna przyczyna, jeden fix)

- **#content:** Dla `body.home` ustawić `padding-top: 0` we **wszystkich** breakpointach (nie tylko mobile), w jednym miejscu — `25-global-layout.css`.
- **Search panel (opcjonalnie):** Dla `body.home` można ustawić `.mnsk7-header-search-panel { padding-top: 0 }` w tablet, żeby pasek wyszukiwania był tuż pod headerem (bez dodatkowej białej linii nad search barem).

## Pliki do zmiany

- `wp-content/themes/mnsk7-storefront/assets/css/parts/25-global-layout.css` — rozszerzyć regułę `body.home #content` na wszystkie szerokości ekranu (wyciągnąć poza `@media (max-width: 768px)` lub dodać drugą regułę dla min-width: 769px).
