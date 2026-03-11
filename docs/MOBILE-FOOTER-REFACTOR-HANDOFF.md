# Mobile Footer + Cookie Bar — refaktor i handoff

**Data:** 2026-03-11  
**Zakres:** mobile footer (accordion), cookie bar (safe-area, z-index), porządkowanie kodu.

---

## 1. Root cause (dlaczego sekcje nie działały)

- **Trigger nie był przyciskiem:** używano `<h3 role="button" tabindex="0">`. Na części urządzeń/przeglądarek tap nie był traktowany jako akcja (semantyka, focus, double-fire przy touch+click).
- **Dwa mechanizmy:** JS nasłuchiwał na `.mnsk7-footer__title` i ustawiał `is-open` na kolumnie; CSS pokazywał/ukrywał treść przez wiele osobnych selektorów (`.mnsk7-footer__col .mnsk7-footer__links`, `.mnsk7-footer__col.is-open .mnsk7-footer__links` itd.). Brak jednego kontenera „panel” powodował rozjazd stanu i duplikaty reguł.
- **Brak jednego panelu:** treść (listy, kontakt, newsletter) nie była w jednym elemencie z `id` dla `aria-controls` — dostępność i przejrzystość były słabe.
- **Focus/outline:** `[tabindex]:focus` w reset dawał outline na :focus; na mobile po tapie często zostawał widoczny „niebieski frame”. Brak jawnego `:focus { outline: none }` i `:focus-visible` tylko na triggerze.
- **Cookie bar:** brak `env(safe-area-inset-bottom)` na fixed bottom i przy `#page` padding-bottom — na iPhone pasek mógł wchodzić w safe area; z-index 9999 mógł konfliktować z innymi warstwami (ustawiono 10000).

---

## 2. Co zostało zrobione

### 2.1 Footer (PHP + JS)

- **Struktura:** każda kolumna ma:
  - `<button type="button" class="mnsk7-footer__accordion-trigger">` z `<span class="mnsk7-footer__accordion-title">` + `<span class="mnsk7-footer__accordion-icon">`;
  - `<div class="mnsk7-footer__accordion-panel" id="footer-panel-*">` z całą treścią (linki, kontakt, newsletter).
- **ARIA:** `aria-expanded`, `aria-controls` na przycisku; panel ma `role="region"` i `aria-labelledby` na trigger.
- **JS:** jeden mechanizm — nasłuch na `.mnsk7-footer__accordion-trigger`, toggle klasy `is-open` na `.mnsk7-footer__col`, ustawienie `aria-expanded`. Tylko `click` + `keydown` (Enter/Space); bez osobnego `touchend` (unikamy podwójnego wywołania).
- **Stan początkowy:** pierwsza kolumna (Klient) ma w HTML `is-open` i `aria-expanded="true"` — na mobile domyślnie otwarta; pozostałe zamknięte.

### 2.2 Footer (CSS)

- **Desktop:** trigger wygląda jak nagłówek (margin, font, bez kursora pointer); ikona ukryta; panel zawsze `display: block`.
- **Mobile (≤768px):** wszystkie style accordionu w jednym bloku w `09-footer.css`:
  - trigger: cały wiersz klikalny, min-height 44px, `:focus` bez outline, `:focus-visible` z outline;
  - ikona `+` / `−` przez `::before` na `.mnsk7-footer__accordion-icon`;
  - panel: `display: none` domyślnie, `display: block` gdy `.mnsk7-footer__col.is-open`;
  - jednolite paddingi (`--space-16`, `--space-12`), dzielniki między sekcjami.
- **Dolny pasek (copyright):** na mobile ujednolicone paddingi w `mnsk7-footer__bottom-inner`; na ≤360px mniejsze paddingi.

### 2.3 Cookie bar

- **10-cookie-bar.css:**  
  - `padding-bottom: max(0.75rem, env(safe-area-inset-bottom));`  
  - `z-index: 10000`  
  - `body.mnsk7-cookie-bar-visible #page { padding-bottom: max(56px, calc(56px + env(safe-area-inset-bottom))); }`  
  - przyciski: `:focus { outline: none }`, `:focus-visible` z outline.
- **21-responsive-mobile.css:** usunięto duplikat paddingów footer; dla cookie bar na wąskich ekranach dodano `padding-bottom` z safe-area.

---

## 3. Zmienione pliki

| Plik | Zmiany |
|------|--------|
| `wp-content/themes/mnsk7-storefront/footer.php` | Zamiana `h3` na `button` + `div.mnsk7-footer__accordion-panel` w każdej kolumnie; jeden skrypt accordionu (trigger = button, toggle `is-open`, `aria-expanded`). |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/09-footer.css` | Desktop: styl triggera jak nagłówek, panel zawsze widoczny. Mobile: jeden blok accordionu (trigger, ikona +/−, panel), focus-visible, paddingi i dolny pasek. |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/10-cookie-bar.css` | safe-area-inset-bottom, z-index 10000, padding-bottom dla `#page`, focus/focus-visible dla przycisków; połączenie zduplikowanych reguł `.mnsk7-cookie-bar__btn`. |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/21-responsive-mobile.css` | Usunięto duplikat `.mnsk7-footer__inner { padding }`; cookie bar tylko doprecyzowanie (flex, safe-area). |

---

## 4. Jak testować

1. **Mobile footer (viewport ≤768px):**
   - Otwórz stronę (np. staging.mnsk7-tools.pl), przewiń do stopki.
   - Klient: domyślnie otwarty (lista widoczna).
   - Kategorie, Kontakt, Newsletter: zamknięte; po tapie na nagłówek — otwarcie; drugi tap — zamknięcie.
   - Wszystkie sekcje: tap na cały wiersz (nie tylko „+”) otwiera/zamyka.
   - Ikona: „+” gdy zamknięte, „−” gdy otwarte.
   - Brak niebieskiej ramki po tapie (sprawdź focus-visible tylko przy Tab).
   - Klawiatura: Tab do triggera, Enter/Space — toggle.

2. **Cookie bar:**
   - Wyczyść cookie/localStorage (`mnsk7_cookie_consent`), odśwież stronę — pasek się pokazuje.
   - Na telefonie: pasek nad footerem, w całości widoczny, przyciski „Akceptuję wszystkie” / „Tylko niezbędne” działają.
   - Na iPhone: pasek nie wchodzi w safe area (notch/home indicator); po zaakceptowaniu/odrzuceniu pasek znika, footer nie nachodzi na pasek.

3. **Desktop (≥769px):**
   - Stopka w 4 kolumnach, bez accordionu; wszystkie sekcje od razu widoczne; trigger wygląda jak zwykły nagłówek.

4. **Build:** jeśli używany jest złożony `main.css` z parts, po zmianach w `09-footer.css` i `10-cookie-bar.css` należy przebudować `main.css` (np. skrypt łączenia parts).

---

## 5. Kryteria akceptacji (checklist)

- [ ] Na mobile wszystkie sekcje (Klient, Kategorie, Kontakt, Newsletter) otwierają się i zamykają po tapie.
- [ ] Trigger to cały wiersz; ikona +/− odzwierciedla stan.
- [ ] Brak podwójnego otwarcia/zamknięcia; brak „niebieskiej ramki” po samym tapie.
- [ ] Cookie bar widoczny, przyciski klikalne, zamykanie/akceptacja działa.
- [ ] Cookie bar nie jest zasłonięty przez footer; na iPhone uwzględniona safe area.
- [ ] Desktop footer bez zmian (4 kolumny, wszystko widoczne).
- [ ] Kod: jeden mechanizm accordionu (JS + CSS), brak duplikatów reguł mobile footer w wielu plikach.
