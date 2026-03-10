# REGRESSION REPORT — po fixie

## Co sprawdzono

- **URL:** /, /koszyk/, /sklep/?filter_manufacturer=cnc (przed fixem i po fixie w kodzie).
- **Viewport:** W tej sesji przeglądarka (Browser MCP) nie wymuszała viewportu mobilnego — widok desktop. Zmiana w CSS (overflow .mnsk7-header__inner tylko na desktop) nie wpływa na desktop; na mobile przy pełnym ładowaniu CSS zachowany zostaje overflow: hidden w pasku headera.

## Potwierdzenie

1. **Stary header nie wraca:** Jeden źródłowy header (header.php); brak drugiego render pathu.
2. **?filter_*:** Archiwum z parametrem filter ładuje ten sam nagłówek i layout (archive-product.php, body_class).
3. **Cart / home / archive:** Ten sam header (get_header()), spójny UI.
4. **Mobile / desktop:** Fix nie zmienia zachowania desktop (overflow: visible tylko min-width: 769px); mobile w bloku max-width: 768px nadal ma overflow: hidden na .mnsk7-header__inner.

## Uwaga

Staging może nie być odświeżony z lokalnego worktree do momentu deployu z main. Po pushu do main zalecane: pełne czyszczenie cache (w tym URL z ?filter_*), ręczna weryfikacja na urządzeniu mobilnym lub DevTools (320, 360, 390, 430, 768 px).
