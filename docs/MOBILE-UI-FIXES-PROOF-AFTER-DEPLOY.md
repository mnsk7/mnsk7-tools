# Mobile/Tablet UI Fixes — proof after deploy

**Push wykonany:** tak (`git push origin main` → `fc00c85..0643b54`)  
**Deploy:** trigger przez GitHub Actions (push na `main` → workflow „Deploy to Staging” rsync mu-plugins + theme).

---

## 1. Co jest realnie wdrożone na staging

Po zakończeniu workflow na stagingu są:

- **Theme** `mnsk7-storefront`: CSS (04-header, 25-global-layout, 21-responsive-mobile, 06-single-product, main.css), content-single-product.php (sticky CTA + stock), **functions.php** (pre_get_posts — normalizacja wyszukiwania).
- **mu-plugins** (z repo): **inc/woo-ux.php** (pre_get_posts — normalizacja wyszukiwania), **inc/product-card.php** (już zawiera availability row — bez zmian).

Staging może jeszcze serwować cache (WP Rocket). Workflow czyści `wp-content/cache/min/` i `wp-content/cache/wp-rocket/`. Jeśli coś jest w CDN, może być potrzebne ręczne „Clear cache” po deployu.

---

## 2. Co jest realnie zapushowane

- **Zdalna gałąź:** `origin/main`
- **Zakres push:** `fc00c85..0643b54` (dwa commity):
  1. **334ce67** — fix(mobile): header, megamenu Sklep, search UX, gap, stripes, related cols, stock CTA (+ handoff)
  2. **0643b54** — fix(search): search normalization in theme + mu-plugins; remove duplicate availability wrapper

**Potwierdzenie push:**  
`To https://github.com/mnsk7/mnsk7-tools.git`  
`   fc00c85..0643b54  main -> main`

---

## 3. Co przeniesiono z „lokalnego mu-plugina” do reproducible kodu

- **Normalizacja wyszukiwania** (`fi 4mm` → `fi 4 mm`):
  - **Theme:** `wp-content/themes/mnsk7-storefront/functions.php` — hook `pre_get_posts` (priority 5). Deploy z theme.
  - **mu-plugins (tracked):** `mu-plugins/inc/woo-ux.php` — ten sam hook. Deploy z repo (rsync mu-plugins).
- **Availability row:** nie była w nieśledzonym mu-pluginie — jest od początku w **mu-plugins/inc/product-card.php** (tracked). Z theme usunięto duplikat wrappera (priority 7/9), żeby nie owijać dwa razy.

Katalog **wp-content/mu-plugins/** jest w .gitignore; źródłem prawdy do deployu jest **mu-plugins/** w rootcie repo (tam jest woo-ux.php z normalizacją).

---

## 4. Proof screenshots after deploy

Po zakończeniu GitHub Actions i ewentualnym wyczyszczeniu cache:

1. Otwórz staging: `https://staging.mnsk7-tools.pl/`
2. Dla każdego viewportu zrób zrzut ekranu (nagłówek + pierwszy blok):
   - **319** × ~700
   - **360** × ~800
   - **375** × ~812
   - **390** × ~844
   - **414** × ~896
   - **549** × ~1100
   - **768** × ~1024
   - **1280+** (desktop)
3. Sprawdzenia:
   - **Header:** bez obcięcia, jedna siatka przycisków (burger, Szukaj, konto, koszyk).
   - **Sklep (mobile):** po tapie — hierarchia (Rodzaje frezów, Zastosowanie…, Wszystkie produkty →).
   - **Search:** w wyszukiwarce wpisać `fi 4mm` i `fi 4 mm` — ten sam zestaw wyników.
   - **Gap:** mniejszy odstęp pod headerem (padding-top 1.25rem na mobile).
   - **Paski:** brak jasnych/beżowych pasków po bokach (tło content = białe).
   - **Search seam:** input + przycisk jako jeden control, focus bez fioletowego glow.
   - **Related/recommended:** na wąskich (≤400px) 1 kolumna; 2 kolumny gdy szersze.
   - **Stock:** przy CTA w buyboxie oraz w sticky CTA na mobile (PDP).

Screenshots z automatu zapisane w `/var/folders/.../cursor/screenshots/` (lokalnie). Do akceptacji warto zapisać kopie w `docs/proof-319.png` itd. po własnym przejściu powyższych kroków.

---

## 5. Final commit hash

- **Ostatni commit na main:** `0643b54`  
  `fix(search): search normalization in theme + mu-plugins (repo); remove duplicate availability wrapper`

- **Pełny zestaw zmian mobile UI:** commity `334ce67` + `0643b54`.

---

## 6. Push confirmation

```
To https://github.com/mnsk7/mnsk7-tools.git
   fc00c85..0643b54  main -> main
```

Push wykonany przez agenta. Deploy na staging odbywa się przez GitHub Actions po pushu do `main`.
