# Performance Pass 2b — Analiza regresji i plan naprawy

**Kontekst:** Pass 2 nie został przyjęty. Wyniki po wdrożeniu dały regresje na home (TBT) i archive (LCP). Cel Pass 2b: zidentyfikować przyczyny na podstawie danych z Lighthouse i przygotować bezpieczny plan naprawy.

---

## 1. Faktyczne wyniki Pass 2 (nie przyjęte)

| Strona   | Metryka     | Przed Pass 2 | Po Pass 2 |
|----------|-------------|--------------|-----------|
| **Home** | Performance | 58           | 54        |
| Home     | FCP         | 1,79 s       | 2,4 s     |
| Home     | LCP         | 3,13 s       | 3,1 s     |
| Home     | **TBT**     | **1668 ms**  | **2990 ms** |
| **Archive** | Performance | 69        | 80        |
| Archive  | FCP         | 1,78 s       | 2,0 s     |
| Archive  | **LCP**     | **2,68 s**   | **4,4 s** |
| Archive  | TBT         | 1087 ms      | 20 ms     |

**Wniosek:** home — wyraźna regresja TBT; archive — duża poprawa TBT, ale silna regresja LCP. Nie można uznać Pass 2 za sukces.

---

## 2. Analiza regresji (dane z Lighthouse JSON)

Źródło: `docs/lighthouse-home-after.json`, `docs/lighthouse-archive-after.json` (Lighthouse 13.x, mobile simulation).

### 2.1 Dlaczego TBT na home się pogorszyło

**Dane z audytu (home):**

- **Long tasks (główne):** 515 ms, 502 ms, 476 ms, 448 ms, 439 ms, 429 ms, 284 ms, 214 ms, 205 ms, 194 ms — większość z URL `https://staging.mnsk7-tools.pl/` (document = inline) oraz „Unattributable”.
- **Bootup (document URL):** total **4334 ms**, scripting 222 ms, scriptParseCompile 48 ms — czyli cały inline w dokumencie (footer) odpowiada za ~4,3 s pracy main thread.

**Przyczyna:** W Pass 2 blok inline podzielono na `runCritical()` (wywołane **od razu** w footerze) i `runDeferred()` (w requestIdleCallback). `runCritical()` zawiera: menu toggle, primary menu (parent items, mega menu, mobile link close), search (pełna logika), cart (dropdown, hover, click). To ok. 220 linii JS wykonywanych **synchronicznie** w momencie parsowania footera. Wcześniej całość była w jednym `run()` w requestIdleCallback — wykonanie przesunięte w czasie, mniej blokowania przy parse. Po zmianie `runCritical()` uruchamia się natychmiast i tworzy długie zadania (setki ms), co podbija TBT.

**Wniosek:** Główny wkład w TBT 2990 ms na home daje **synchroniczne wykonanie `runCritical()`** w footerze (document URL). Nie „requestIdleCallback” sam w sobie — tylko to, że critical block jest zbyt duży i uruchamiany od razu.

**Co zrobić:** Nie uruchamiać całego `runCritical()` synchronicznie. Opcje: (a) zaplanować `runCritical` przez `setTimeout(fn, 0)` lub `requestAnimationFrame`, żeby oddać kontrolę po parse; (b) rozbić init na mniejsze kawałki (np. tylko przywiązanie pierwszego listenera na menu/search/cart), resztę w idle; (c) pozostawić jeden blok w requestIdleCallback (jak przed Pass 2) i zaakceptować możliwe opóźnienie pierwszego kliku — wtedy TBT na home może wrócić do poziomu sprzed Pass 2.

---

### 2.2 Jaki element jest realnym LCP na archive

**Dane z audytu (archive):** `lcp-breakdown-insight` w `lighthouse-archive-after.json` zawiera **konkretny węzeł LCP**:

- **Selector:** `div#page > div#mnsk7-promo-bar > div.mnsk7-promo-bar__inner > span.mnsk7-promo-bar__text`
- **Snippet:** `<span class="mnsk7-promo-bar__text">`
- **Treść (nodeLabel):** „Darmowa dostawa od 300 zł. Tylko Polska. Warunki dostawy →”
- **LCP breakdown:** Time to first byte **1637,6 ms**, Element render delay **757,4 ms**.

**Wniosek:** Obecnie **LCP na archive to nie pierwsza miniatura produktu**, tylko **tekst paska promocyjnego (promo bar)** na górze strony. Hipoteza z Pass 2 („pierwsza miniatura w tabeli = LCP”) **nie jest potwierdzona** w tym przebiegu — Lighthouse wskazuje promo bar jako largest contentful paint.

**Skutek:** Ustawienie `eager` + `fetchpriority="high"` na pierwszej miniaturce w tabeli nie mogło poprawić LCP, bo LCP to inny element. Dodatkowo TTFB dla strony (1,6 s) i opóźnienie renderu (0,75 s) składają się na ~4,4 s. Optymalizacja LCP na archive musi być skierowana na **promo bar** (np. wcześniejszy render, unikanie blokowania, ewentualnie preload fontu dla tego tekstu) oraz na ogólne TTFB/render, a nie tylko na pierwszą miniaturkę.

**Co zrobić:** (1) W dokumentacji usunąć/zmienić założenie, że LCP na archive = pierwsza miniatura. (2) Dla Pass 2b: ustalić strategię pod **promo bar** (np. nie opóźniać jego renderu przez JS/CSS, sprawdzić czy `runDeferred()` lub inne skrypty nie blokują); ewentualnie rozważyć conditional load promo bar tylko tam, gdzie nie psuje LCP. (3) Eager/fetchpriority na pierwszym wierszu tabeli można zostawić jako wsparcie dla perceived load listingu, ale nie liczyć na to jako na główny lever LCP archive.

---

## 3. Co zostaje z Pass 2, co odwołujemy / przepracowujemy

| Zmiana Pass 2 | Status | Uzasadnienie |
|---------------|--------|--------------|
| **wc-cart-fragments:** enqueue tylko gdy `! is_cart() && ! is_checkout()` | **Zostaje** | Mniej JS na cart/checkout, brak dowodów że to źródło regresji. |
| **runCritical() od razu, runDeferred() w requestIdleCallback** | **Przepracować** | Synchroniczne runCritical() na home daje długie zadania i podbija TBT. Trzeba zmienić sposób/czas uruchomienia. |
| **eager + fetchpriority na pierwszym wierszu tabeli (archive)** | **Zostaje, ale** | Nie jest to aktualny LCP (LCP = promo bar). Zostawiamy dla UX listingu; nie uznajemy za fix LCP. |
| Założenie „LCP archive = pierwsza miniatura” | **Cofamy w dokumentacji** | Lighthouse wskazuje LCP = promo bar. Dokumentacja musi to odzwierciedlać. |

---

## 4. Plan Pass 2b — listy zmian

### 4.1 Home: obniżenie TBT (bez regresji archive)

- **Cel:** Nie wykonywać dużego bloku init synchronicznie w footerze.
- **Propozycja:** Uruchamiać init headera **nie od razu**, tylko np. `setTimeout(runCritical, 0)` lub małe opóźnienie (np. 10 ms), żeby main thread mógł dokończyć parse/paint; albo przenieść z powrotem całość do jednego `run()` w requestIdleCallback (timeout 1–1,5 s) i zaakceptować ryzyko opóźnionego pierwszego kliku.
- **Plik:** `functions.php` (callback `wp_footer` z inline).
- **Ryzyko:** Pierwszy klik menu/search/cart może być opóźniony o dziesiątki–setki ms (setTimeout 0) lub do ~1,5 s (full rIC). Należy zweryfikować po wdrożeniu (Lighthouse + ręczny test).
- **Metryka:** TBT home powinno spaść w kierunku wartości sprzed Pass 2 (~1668 ms); FCP/LCP bez pogorszenia.

### 4.2 Archive: LCP pod realny element (promo bar)

- **Cel:** Skrócić LCP 4,4 s — elementem jest promo bar (tekst), nie miniatura.
- **Propozycja:** (1) Sprawdzić, czy promo bar jest blokowany przez JS/CSS (np. przez runDeferred lub inne). (2) Upewnić się, że promo bar jest w pierwszych elementach DOM i nie jest ukrywany/odkładany. (3) Nie dodawać dla niego dodatkowego opóźnienia (np. nie przenosić inicjalizacji promo bar do bardzo późnego idle). (4) Rozważyć preload fontu dla `.mnsk7-promo-bar__text` jeśli font opóźnia render tekstu. (5) Ogólna optymalizacja TTFB (serwer/cache) — po stronie tematu ograniczona.
- **Pliki:** szablon/header (gdzie renderowany jest promo bar), ewentualnie `functions.php` jeśli runDeferred wpływa na promo bar, CSS (font, display).
- **Ryzyko:** Zmiany fontu/preload mogą wpływać na inne strony; testy na home i archive.
- **Metryka:** LCP archive w dół (cel < 2,5 s jeśli możliwe); TBT archive nie pogorszyć (obecnie 20 ms).

### 4.3 Rozbicie init na mniejsze kawałki (opcjonalnie, średni priorytet)

- **Cel:** Zamiast jednego dużego runCritical — kilka małych bloków: np. (1) tylko menu toggle + zamykanie menu, (2) search toggle + panel, (3) cart dropdown, (4) mega menu (desktop), każdy wywołany np. setTimeout(..., 0) lub kolejno w requestIdleCallback z krótkim timeout.
- **Plik:** `functions.php`.
- **Ryzyko:** Więcej tasków = możliwy taki sam łączny czas, ale krótsze pojedyncze taski (lepsze dla TBT). Trzeba mierzyć.
- **Metryka:** TBT home, INP / pierwszy klik.

### 4.4 Dokumentacja

- W `PERFORMANCE-PASS-2.md` i ewentualnie `PERFORMANCE-AUDIT-AND-PLAN.md`: dopisać, że Pass 2 **nie został przyjęty**; że realny LCP na archive (w zmierzonym przebiegu) to **promo bar** (`span.mnsk7-promo-bar__text`); że optymalizacja pierwszej miniatury nie jest równoznaczna z optymalizacją LCP archive.
- W Pass 2b: po kolejnym pomiarze zaktualizować tabelę i wniosek.

---

## 4.5 Wdrożone w Pass 2b (kod)

| Plik | Zmiana | Cel |
|------|--------|-----|
| `functions.php` | **runCritical()** nie jest już wywoływane synchronicznie. Wywołanie: `requestIdleCallback(runCritical, { timeout: 100 })` lub `setTimeout(runCritical, 0)`. | Uniknięcie długiego synchronicznego bloku przy parsowaniu footera — TBT home w dół. Critical UI (menu, search, cart) i tak uruchamia się w ciągu ~100 ms lub przy pierwszym idle. |
| — | **runDeferred()** bez zmian (requestIdleCallback, timeout 2 s). | Promo bar, shrink, Instagram nadal odłożone. |
| — | **Archive LCP (promo bar):** brak zmian w kodzie. Promo bar jest na początku DOM (header.php), font Inter jest już preloadowany. Element render delay (757 ms) wymaga dalszej analizy po pomiarze 2b (np. TTFB po stronie serwera). | Nie dodajemy opóźnień; ewentualne dalsze lewery w Pass 3. |

---

## 5. Plan pomiaru Lighthouse (Pass 2b)

Przed wdrożeniem Pass 2b (opcjonalnie — baseline):

```bash
npx lighthouse https://staging.mnsk7-tools.pl --only-categories=performance --output=json --output-path=docs/lighthouse-home-before-2b.json
npx lighthouse https://staging.mnsk7-tools.pl/sklep/ --only-categories=performance --output=json --output-path=docs/lighthouse-archive-before-2b.json
```

Po wdrożeniu zmian Pass 2b (staging):

```bash
npx lighthouse https://staging.mnsk7-tools.pl --only-categories=performance --output=json --output-path=docs/lighthouse-home-after-2b.json
npx lighthouse https://staging.mnsk7-tools.pl/sklep/ --only-categories=performance --output=json --output-path=docs/lighthouse-archive-after-2b.json
```

Kryteria sukcesu Pass 2b:

- **Home:** TBT ≤ 1668 ms (przynajmniej nie gorsze niż przed Pass 2); Performance / FCP / LCP bez istotnej regresji.
- **Archive:** LCP < 4,4 s (dążenie do < 2,5 s); TBT ≤ 20 ms (utrzymać).
- Brak regresji UX: menu, search, cart, add-to-cart, filtry, logowanie.

---

## 6. Krótki uczciwy wniosek

- **Pass 2 nie jest przyjęty:** home ma wyraźnie gorsze TBT (1668 → 2990 ms), archive ma gorsze LCP (2,68 → 4,4 s), przy bardzo dobrej poprawie TBT na archive (1087 → 20 ms).
- **Home TBT:** Główną przyczyną jest **synchroniczne wykonanie dużego bloku `runCritical()`** w footerze (document URL). Naprawa: nie uruchamiać tego bloku synchronicznie; użyć setTimeout(0), rIC lub rozbicia na mniejsze kawałki.
- **Archive LCP:** W tym przebiegu **LCP to nie pierwsza miniatura, tylko tekst promo bar** (`span.mnsk7-promo-bar__text`). Optymalizacja pierwszej miniatury nie adresuje faktycznego LCP. Naprawa: optymalizacja pod promo bar (render, font, brak zbędnego opóźniania) oraz TTFB/render; dokumentacja zaktualizowana.
- **Pass 2b:** Skupienie na dwóch celach: (1) obniżenie TBT na home przez zmianę sposobu uruchomienia init, (2) poprawa LCP na archive przez działania pod realny element (promo bar). Bez przyrównywań do „teoretycznego” LCP i bez zmian „na wszelki wypadek”.
