# P0 Mobile Header / Cookie / Promo / Hero — Verification & Acceptance

Proof по трём блокам для приёмки.

---

## 1. Cookie banner — complete functionality

**Defect:** Cookie bar nie działał — kliki nie zapisywały consent, banner nie znikał; po reload stan się nie utrzymywał.

**Proof (code):**

| Wymaganie | Dowód w kodzie |
|-----------|----------------|
| **„Akceptuję wszystkie”** | `footer.php` L198–204: delegacja `bar.addEventListener('click', …)`, `target.classList.contains('mnsk7-cookie-bar-accept')` → `onChoice(e, valAccept)`. `onChoice` wywołuje `setConsent(value)`, `hide()`, `dispatchEvent('mnsk7-cookie-consent')`. |
| **„Tylko niezbędne”** | Ten sam handler: `target.classList.contains('mnsk7-cookie-bar-reject')` → `onChoice(e, valReject)`. |
| **„Ustawienia”** | Przycisk to `<a href="<?php echo esc_url( $cookie_settings_url ); ?>">` (L159). Handler bierze tylko `e.target.closest('button')` — dla `<a>` zwraca `null`, więc **nie** wywołuje `onChoice` ani `preventDefault`. Link działa domyślnie (nawigacja do polityki prywatności #cookies). |
| **Consent zapisywany** | `setConsent()` (L174–177): `localStorage.setItem(key, value)` oraz `document.cookie = key + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; SameSite=Lax'`. |
| **Banner znika** | `hide()` (L171): `bar.setAttribute('hidden', '')`, `aria-hidden="true"`, `document.body.classList.remove('mnsk7-cookie-bar-visible')`. |
| **Po reload stan OK** | Przed `show()` (L184–188): `getStored()` czyta `localStorage` i cookie; jeśli `stored === valAccept || stored === valReject` → `hide()`, `return` — banner w ogóle się nie pokazuje. |

**Changed files:** `footer.php` (bar poza `#page`, init w DOMContentLoaded, delegacja click).

**Verification result (manual):**
- [ ] Otwórz stronę bez consent (incognito / wyczyść cookie `mnsk7_cookie_consent`).
- [ ] Klik „Akceptuję wszystkie” → banner znika; reload → banner się nie pokazuje.
- [ ] Wyczyść cookie/localStorage, odśwież; klik „Tylko niezbędne” → banner znika; reload → banner się nie pokazuje.
- [ ] Wyczyść consent; klik „Ustawienia” → przejście do strony polityki (np. /polityka-prywatnosci/#cookies).

**Accepted:** [ ] TAK / [ ] NIE (wymaga wykonania kroków manualnych powyżej).

---

## 2. Mobile „Sklep” submenu

**Defect:** Na mobile „Sklep” nie rozwijał podmenu; użytkownik tracił strukturę katalogu z mega menu.

**Proof (code):**

| Wymaganie | Dowód w kodzie |
|-----------|----------------|
| **W DOM na mobile jest `.sub-menu`** | `header.php` L85–98: megamenu renderowane **zawsze** (usunięty warunek `mnsk7_is_mobile_request()`). Jeśli `! empty( $top_cats ) \|\| ! empty( $top_tags )` → output `<ul class="sub-menu mnsk7-megamenu">` z grupami i linkami. |
| **Tap po „Sklep” rozwija podpunkty** | `functions.php` L813–817: dla `window.innerWidth <= 1024` na klik w `li.menu-item-has-children > a`: `e.preventDefault()`, `li.classList.toggle('is-open')`, `a.setAttribute('aria-expanded', …)`. |
| **Podpunkty są klikalne** | Podmenu to zwykłe `<a href="...">` (header.php L114, L128, L144). Po rozwinięciu nie ma overlay; klik w link (L856–862) zamyka nav i następuje nawigacja. |
| **Layout menu się nie psuje** | `04-header.css` L448–506 (mobile ≤1024px): `.sub-menu` domyślnie `display: none`; przy `li.menu-item-has-children.is-open .sub-menu` → `display: flex`; układ pionowy, `margin-left`, `min-height: 44px` dla linków. L1268–1271: `display: none !important` / `display: flex !important` dla `.is-open`. |
| **Desktop bez zmian** | Desktop (≥1025px): brak `e.preventDefault()` przy kliku w „Sklep” (L813 — tylko `if (window.innerWidth <= 1024)`); megamenu otwierane hoverem (L834–847) i `.mnsk7-megamenu-open`; submenu pozycjonowane absolutnie jak wcześniej. |

**Changed files:** `header.php` (zawsze render megamenu), `functions.php` (toggle `is-open` na mobile), `04-header.css` (mobile submenu przy `.is-open`, desktop bez zmian).

**Verification result (manual):**
- [ ] Mobile viewport (np. 375px): w DevTools Elements sprawdź, że w `#mnsk7-primary-menu` jest `li.menu-item-has-children` z wewnętrznym `ul.sub-menu.mnsk7-megamenu`.
- [ ] Tap „Sklep” → podmenu się rozwijają (kategorie / tagi / „Wszystkie produkty”).
- [ ] Tap w link kategorii → przejście do kategorii, menu się zamyka.
- [ ] Desktop viewport (≥1025px): hover „Sklep” → megamenu; klik „Sklep” → przejście do /sklep/.

**Accepted:** [ ] TAK / [ ] NIE (wymaga wykonania kroków manualnych powyżej).

---

## 3. Hero full-bleed regression check

**Defect:** Po zmianach przy hero pojawiły się białe pionowe pasy (padding `#content`); na wąskich szerokościach możliwy overflow.

**Proof (code):**

| Szerokość | Odpowiednie reguły |
|-----------|--------------------|
| 320, 360, 390, 430 | `08-home-sections.css` L11–22: `.mnsk7-hero` ma `margin-left: -1.5rem`, `margin-right: -1.5rem`, `width: calc(100% + 3rem)`. `#content` w `25-global-layout.css` ma `padding-left/right: 1.5rem` do 768px — hero „wychodzi” o 1.5rem w lewo i prawo, więc tło wypełnia całą szerokość. |
| Brak scrollu poziomego | `21-responsive-mobile.css` L4–14 (`@media (max-width: 768px)`): `body`, `.site`, `#page`, `#content`, `.site-content`, `.mnsk7-content` mają `overflow-x: hidden` — zawartość wychodząca (np. hero z ujemnym marginesem) jest przycinana, bez paska przewijania. |
| Hero nie obcięty wizualnie | Hero ma `overflow: visible` (L18); `width: calc(100% + 3rem)` z marginesami −1.5rem daje efektywnie pełną szerokość kontenera nadrzędnego; tło gradientu wypełnia cały blok. |

**Changed files:** `08-home-sections.css` (full-bleed: ujemne marginesy, `width: calc(100% + 3rem)`; od 768px: 2rem i `calc(100% + 4rem)`).

**Verification result (manual — viewport):**
- [ ] **320px:** brak białych pasów, brak scrollu poziomego, hero od krawędzi do krawędzi.
- [ ] **360px:** jw.
- [ ] **390px:** jw.
- [ ] **430px:** jw.

**Accepted:** [ ] TAK / [ ] NIE (wymaga sprawdzenia na wskazanych szerokościach).

---

## Podsumowanie

| # | Blok | Changed files | Accepted |
|---|------|---------------|----------|
| 1 | Cookie banner functionality | footer.php | [ ] |
| 2 | Mobile Sklep submenu | header.php, functions.php, 04-header.css | [ ] |
| 3 | Hero full-bleed | 08-home-sections.css (plus 21-responsive-mobile.css — overflow) | [ ] |

Po wykonaniu kroków w „Verification result" dla każdego bloku można odhaczyć **Accepted: TAK** i zamknąć P0.

---

## Browser verification (staging.mnsk7-tools.pl)

Sprawdzenie na żywej stronie (viewport 390px, potem 320px):

| # | Co sprawdzono | Wynik |
|---|----------------|--------|
| **1. Cookie bar** | Bar niewidoczny — consent już zapisany w sesji. Proof tylko z kodu; do przyjęcia wymagane ręczne kroki z sekcji 1. | Code proof OK |
| **2. Mobile Sklep** | W DOM: link „Sklep" oraz podpunkty megamenu (Frez do drewna, Frez do metalu, …, Wszystkie produkty) — `.sub-menu` w drzewie. Toggle i layout: ręcznie. | Submenu w DOM: TAK |
| **3. Hero** | Viewport 320px; overflow-x: hidden i full-bleed w CSS. Wizualnie 320/360/390/430: ręcznie. | Code proof OK |

**Wniosek:** Code proof kompletny. Przyjęcie P0: uzupełnić ręczną weryfikację i odhaczyć Accepted: TAK w tabeli.
