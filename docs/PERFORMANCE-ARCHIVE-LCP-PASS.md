# Archive LCP — osobny pass (bez zmian home)

**Status:** Home uznany za poprawiony. Archive zablokowany przez **LCP 4,8 s**. Kolejny fokus: **tylko archive LCP**, bez zmiany logiki home i bez wzrostu TBT.

---

## 1. Aktualne pomiary

| Strona   | Performance | FCP   | LCP   | TBT   | CLS   |
|----------|-------------|-------|-------|-------|-------|
| **Home** | 69          | 2,0 s | 2,9 s | 910 ms | 0,004 |
| **Archive** | 79      | 1,8 s | **4,8 s** | 0 ms | 0,004 |

- **Home:** TBT spadł, score wyższy, CLS stabilny. LCP 2,9 s i TBT 910 ms nadal do poprawy, ale **nie ruszać** bez osobnego powodu.
- **Archive:** TBT świetny (0 ms). **LCP 4,8 s = główny bottleneck.** Archive w obecnej formie nie jest akceptowany ze względu na LCP.

---

## 2. Cel passu

- Jeden cel: **obniżyć LCP na archive** (dążyć do &lt; 2,5 s).
- **Nie zmieniać** logiki home (fragments, init, megamenu na desktop).
- **Nie pogorszyć** TBT na archive (utrzymać 0 lub niski poziom).
- Wszystkie decyzje oparte na **zmierzonych danych** (Lighthouse/trace), nie na przypuszczeniach.

---

## 3. Krok 0: ustalenie realnego LCP elementu

**Nie zakładać.** Potwierdzić z trace / Lighthouse.

### Jak wyciągnąć LCP z Lighthouse JSON

Po uruchomieniu Lighthouse na archive (`/sklep/`):

```bash
npx lighthouse https://staging.mnsk7-tools.pl/sklep/ --only-categories=performance --output=json --output-path=docs/lighthouse-archive-lcp.json
```

Element LCP i breakdown są w audycie **`lcp-breakdown-insight`** (nie tylko `largest-contentful-paint`). W pliku JSON:

- `audits["lcp-breakdown-insight"].details.items` — tabela z subpart (timeToFirstByte, elementRenderDelay) oraz **węzeł** (selector, snippet, nodeLabel).
- Ten węzeł to **faktyczny LCP element** w tym przebiegu.

Przykład (Node/script do jednorazowego sprawdzenia):

```bash
node -e "
const j = require('./docs/lighthouse-archive-lcp.json');
const a = j.audits && j.audits['lcp-breakdown-insight'];
if (!a || !a.details || !a.details.items) { console.log('Brak lcp-breakdown-insight'); process.exit(1); }
const nodeItem = a.details.items.find(i => i.selector || i.snippet);
if (nodeItem) console.log(JSON.stringify({ selector: nodeItem.selector, snippet: nodeItem.snippet, nodeLabel: nodeItem.nodeLabel }, null, 2));
const table = a.details.items.find(i => i.items);
if (table && table.items) table.items.forEach(i => console.log(i.subpart || i.label, i.duration));
"
```

W poprzednim przebiegu (Pass 2b) LCP na archive był **promo bar** (`span.mnsk7-promo-bar__text`). Po ostatnich zmianach (mobile megamenu skip, transient get_terms, fragments off home) **trzeba to zweryfikować na nowo** — LCP element mógł się zmienić.

---

## 4. Lista kontrolna (co sprawdzić)

### 4.1 Czy LCP jest od razu w HTML?

- Czy element LCP jest w pierwszych fragmentach dokumentu (np. promo/header w `header.php`), czy dopiero głęboko w treści (np. tabela, pierwszy produkt)?
- Czy zależy od: późno ładującego się CSS, fontu, JS, przełożenia layoutu (header/content)?

### 4.2 Promo bar / header / łańcuch renderu

Jeśli LCP = promo bar / tekst w headerze:

- Czy **blokuje go** duży `main.css` (np. brak stylów dla promo/header dopóki nie załaduje się cały plik)?
- Czy **inline critical CSS** w `<head>` (np. `#mnsk7-header-critical`) obejmuje **wystarczająco** promo bar i okolice, żeby pierwszy paint był szybki?
- Czy **font** (Inter) opóźnia render tekstu — preload w headerze jest; czy to ten sam font co dla promo bar?
- Czy **reflow** po init headera (np. `runDeferred`: `promoBar.offsetHeight`, ustawienie `--mnsk7-promo-h`) nie przesuwa LCP w czasie?

### 4.3 TTFB

- Jaka część z 4,8 s to **serwer** (Time to First Byte)?  
- Jeśli TTFB jest duży (np. &gt; 1,5 s), front-end sam nie naprawi LCP — trzeba optymalizacji serwera/cache/CDN.
- W poprzednim pomiarze TTFB archive było ~1,64 s; element render delay ~0,76 s. Warto powtórzyć breakdown na aktualnym buildzie.

### 4.4 Megamenu / duży DOM w headerze

- Na **desktop** megamenu jest w DOM (get_terms z cache). Czy ten duży blok **nie opóźnia** pierwszego renderu górnej części strony (parse HTML, layout)?
- Na **mobile** megamenu nie jest renderowane — czy w aktualnym Lighthouse (mobile sim) LCP nadal jest promo bar, czy coś innego?

### 4.5 Krytyczny CSS dla archive first screen

Czy potrzebne są **archive-specific** critical styles dla:

- promo bar,
- header (sticky, wewnętrzny layout),
- breadcrumbs,
- tytuł strony (np. `.page-title`),
- pierwszy widoczny fragment listingu (np. nagłówek tabeli / pierwszy wiersz)?

Obecnie jeden **globalny** `main.css`; inline critical w headerze dotyczy głównie `#masthead`. Jeśli LCP to promo bar, czy jego style są w tym inline, czy dopiero w `main.css`?

### 4.6 Font

Jeśli LCP to tekst:

- Czy **preload** w `header.php` wskazuje na ten sam plik fontu, którego używa LCP element? (Obecnie: Inter, jeden woff2.)
- Czy **font-display: swap** nie pogarsza metryki (np. niewidoczny tekst do momentu swap)?
- Czy nie ma zbędnego opóźnienia renderu tekstu (np. drugi font, FOUT).

---

## 5. Proponowany minimalny pass (kolejność)

1. **Pomiar**  
   Uruchomić Lighthouse na archive (mobile), zapisać JSON. Wyciągnąć z `lcp-breakdown-insight` **aktualny LCP element** i **breakdown** (TTFB, element render delay).

2. **Analiza**  
   Na podstawie wyniku przejść listę §4: czy LCP w HTML od razu, czy blokuje go CSS/font/JS; jaki udział TTFB; czy megamenu/header DOM przeszkadza; czy critical CSS obejmuje LCP; czy font jest OK.

3. **Zmiany tylko pod archive LCP**  
   W zależności od wyniku, np.:
   - **Jeśli LCP = promo bar:** rozszerzyć inline critical CSS o minimalne style promo bar (np. rozmiar, kolor, widoczność), żeby nie czekał na `main.css`; sprawdzić, czy runDeferred nie powoduje zbędnego reflow w okolicy LCP.
   - **Jeśli duży TTFB:** przekazać wynik do zespołu serwera/cache; na froncie tylko ewentualne ułatwienia (np. wcześniejszy discovery zasobów).
   - **Jeśli LCP = inny element** (np. pierwszy produkt, tabela): optymalizacja pod ten element (eager, fetchpriority, critical CSS dla tego fragmentu), **bez** zmiany logiki home.

4. **Ponowny pomiar**  
   Lighthouse archive: LCP, TBT, FCP. Sprawdzenie, że TBT nie rośnie i że home (jeśli coś się globalnie zmieniło) nie uległ pogorszeniu.

---

## 6. Pliki do ewentualnych zmian (tylko archive LCP)

| Obszar              | Plik(-y)                    | Uwagi |
|---------------------|-----------------------------|--------|
| Critical CSS        | `header.php` (inline style) | Dodać minimalne style dla LCP (np. promo bar), jeśli to on. |
| Promo / header init | `functions.php` (runDeferred) | Unikać reflow w okolicy LCP (np. odczyt offsetHeight dopiero po paint). |
| Font preload        | `header.php`                | Upewnić się, że preload = font LCP. |
| Archive-specific CSS | `functions.php` (enqueue)  | Tylko jeśli uzasadnione: osobny critical lub deferred reszty na archive. |

**Nie zmieniać w tym passie:** logika fragments (home), init headera (runCritical/runDeferred) na home, megamenu na desktop, mobile skip megamenu.

---

## 7. Kryteria sukcesu

- **Archive LCP** &lt; 2,5 s (lub wyraźna redukcja przy jasnym pozostałym bottlenecku, np. TTFB).
- **Archive TBT** bez regresji (nadal 0 lub niski).
- **Home** bez regresji (nie mierzyć obowiązkowo przy każdej zmianie archive, ale przy zmianach globalnych — sprawdzić).

Po passie zaktualizować ten dokument: aktualny LCP element, breakdown, wykonane zmiany i wynik pomiaru.

---

## 8. Wykonane zmiany (Archive LCP pass)

| Zmiana | Plik | Cel |
|--------|------|-----|
| **Critical CSS dla promo bar** | `header.php` — blok `#mnsk7-header-critical` | Promo bar (LCP candidate na archive) maluje się bez czekania na `main.css`. Dodane: .mnsk7-promo-bar, __inner, __text z wartościami literalnymi (#0c7ddb, #fff, 0.8125rem, 1200px) oraz media dla 1024px i 480px. |
| **Odczyt offsetHeight po rAF** | `functions.php` — runDeferred(), promo bar | Ustawienie `--mnsk7-promo-h` i podpięcie przycisku close wykonuje się w requestAnimationFrame, żeby nie blokować pierwszego paint. |
| — | Home, init headera, megamenu | Bez zmian. |

**Oczekiwany efekt:** niższy LCP na archive (promo bar widoczny wcześniej). **Weryfikacja:** Lighthouse archive po deployu; porównać LCP i TBT.
