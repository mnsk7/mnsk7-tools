# FIX REPORT — Mobile UX / responsive

## Zmiany w kodzie

### 1. `assets/css/parts/04-header.css`

**Problem:** Na mobile (max-width: 768px) reguła `.mnsk7-header__inner { overflow: visible }` (na końcu pliku, bez media query) nadpisywała wcześniejszą regułę z bloku mobile `.mnsk7-header__inner { overflow: hidden }`. W efekcie na mobile wewnętrzny wiersz headera mógł powodować pionowy lub poziomy artefakt (overflow), zamiast pozostawać w jednej linii.

**Root cause:** Sekcja „Audit: dropdown Sklep nie może przycinać się” ustawiała `overflow: visible` na `.mnsk7-header`, `.mnsk7-header__inner`, `.mnsk7-header__nav` globalnie, żeby dropdown megamenu nie był obcinany. Na desktop jest to poprawne; na mobile chcemy zachować `overflow: hidden` na `.mnsk7-header__inner` (już ustawione w bloku @media (max-width: 768px)), żeby pasek headera nie miał wewnętrznego scrolla.

**Fix:** Ograniczenie `overflow: visible` dla `.mnsk7-header__inner` do desktopu: opakowanie w `@media (min-width: 769px) { .mnsk7-header__inner { overflow: visible; } }`. Na mobile pozostaje critical CSS z header.php oraz reguła z bloku max-width: 768px (overflow: hidden).

**Plik:** `wp-content/themes/mnsk7-storefront/assets/css/parts/04-header.css` (blok ok. linii 1128–1142).

---

## Pozostałe ustalenia (bez zmian w kodzie)

- **Header / template_include / body_class:** Zgodnie z CODE_REVIEW_REPORT — brak rozgałęzień headera; template_include i body_class zapewniają spójność przy ?filter_*. Żadnych zmian.
- **Search / menu / footer:** Logika i CSS są spójne; brak zidentyfikowanych root causes do naprawy.
- **Overflow-x na body/site:** W 21-responsive-mobile.css używane jako zabezpieczenie przed poziomym scrollem strony — nie jako „leczenie” pojedynczego komponentu; zgodne z regułami. Bez zmian.
