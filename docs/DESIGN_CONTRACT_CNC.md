# DESIGN CONTRACT — CNC Catalog (WooCommerce / Storefront)

**Version:** 2.0  
**Traffic:** mostly Allegro → user lands on category/product pages (not home)  
**Goal:** catalog UX. Users choose by specs (diameter/shank/material), not by marketing.

**Reference (UX katalogu narzędzi):** [Sandvik Coromant PL](https://www.sandvik.coromant.com/pl-pl) — kategoria z tabelą [turning-tools](https://www.sandvik.coromant.com/pl-pl/tools/turning-tools), strona produktu [product-details](https://www.sandvik.coromant.com/pl-pl/product-details?c=HT06-DDMNL-00130-15C&m=8626955&listName=assortment&listIndex=0). Szczegóły w [REFERENCE_SANDVIK_COROMANT.md](REFERENCE_SANDVIK_COROMANT.md).

---

## 0) Non-negotiables

- No changes to production DB, production theme settings, production plugins.
- Staging MUST use a separate database. If not confirmed, STOP and report risk.
- Do not add filter plugins. Filters must be native (WP_Query + tax_query/meta_query).
- Do not edit parent theme files. Only child theme overrides + custom plugin/MU-plugin.

---

## 1) Definition of Done (DoD)

Task is DONE only if:

- All Acceptance Criteria (AC) on affected screens are met.
- Mobile 360px verified (notes + selectors) and no layout regressions.
- Performance: no new heavy JS libs, no visual builders, no filter plugins.
- Output includes: files changed, AC pass/fail list, how to revert.

---

## 2) Global page structure (product page)

Страница товара — **строго** такой порядок. Любые отклонения = ошибка.

| # | Block |
|---|--------|
| 1 | Product image (hero) |
| 2 | Title |
| 3 | Price |
| 4 | Stock |
| 5 | Key specs (table) |
| 6 | Variants |
| 7 | Add to cart |
| 8 | Trust (chips) |
| 9 | Description |
| 10 | Related products |

---

## 3) Hero / product image block

- **Height:** max 55vh.
- **Photo:** `object-fit: contain`, `background: white`.
- **Zakazane:** tekst na zdjęciu, kolaże, 3+ obrazy w jednym.

**Product images rule:** każdy produkt min. 4 zdjęcia w kolejności:
1. Product clean (czysty instrument)
2. Dimensions (infografika wymiarów)
3. Inserts / zestaw
4. Zastosowanie

---

## 4) Title format

**Format:** TYPE + DIAMETER + SHANK + EXTRA

**Przykłady:**
- `Frez planujący CNC 38 mm | trzpień 8 mm | 4 ostrza`
- `Frez do planowania drewna 38,1 mm | trzpień 8 mm | 4P`

**Max:** 2 linie. Ważne parametry na początku/końcu, nie w środku. Bez nadmiaru symboli.

---

## 5) Key specs block (obligatory)

Pod ceną — tabela key specs:

- Średnica robocza
- Trzpień
- Ilość ostrzy
- Materiał
- Typ

**CSS:** `display: grid`, `grid-template-columns: 1fr 1fr`, `gap: 8px`. Etykiety krótkie (np. „Średnica robocza”, „Trzpień”), nie długie opisy.

---

## 6) Variants

**Dozwolone tylko dla:** średnica, trzpień (jeden z nich jako wariant, drugi jako atrybut).

**Zakazane:** wariant = zdjęcie. Warianty = **button chips**, np. `[ 35 mm ] [ 38 mm ] [ 42 mm ]`.

Trzpień 8 mm / 12 mm — jako parametr, nie jako osobne warianty mieszane z „Średnica części roboczej”. Jeden blok „Wariant” z wartościami średnicy (lub trzpienia), reszta w key specs.

---

## 7) CTA (Add to cart)

- **Wysokość:** 48px.
- **Szerokość:** 100%.
- **Kolor:** primary.

**Kolejność:** `[ Ilość ] [ Dodaj do koszyka ]`. Serce (wishlist) poniżej, nie obok — standard e‑commerce.

**Zakazane:** małe przyciski, dwa primary CTA obok siebie.

---

## 8) Trust block

Po przycisku — obowiązkowy blok:

- ✓ Wysyłka 24h
- ✓ Faktura VAT
- ✓ Zwrot 30 dni
- ✓ Darmowa dostawa od 300 zł

Ikony mniejsze, w jednej linii. To obowiązkowy element e‑commerce.

---

## 9) Description format

Zamiast jednego długiego SEO‑tekstu — 3 bloki:

**Zastosowanie** (punkty)
- planowanie powierzchni, MDF, drewno, CNC

**Parametry** (punkty)
- średnica 38 mm, trzpień 8 mm, 4 ostrza

**Zalety** (punkty)
- wymienne płytki, wysoka wydajność, gładkie wykończenie

Oryginalny długi tekst można zostawić poniżej jako „Szczegóły”.

---

## 10) Related products card

Karta musi zawierać:

- Image (1:1)
- Title
- **Key spec line** (1 linia, np. „R1.5 | 8 mm”)
- Price
- Button

**Przykład:**
```
Frez kulowy
R1.5 | 8 mm
154 zł
[Dodaj]
```

Bez długiego tytułu bez skrótu parametru — szybsze skanowanie.

---

## 11) Social proof placement

„132 osób kupiło” (lub odpowiednik) — **bezpośrednio pod ceną**, nad key specs. To bardzo silny social proof; nie chować na dole.

---

## 12) Information architecture & filters

Nawigacja: Material → Type → Diameter/Shank → Product.

**Category page:** filtry + siatka + paginacja.

**Filtry (bez pluginów):** diameter, shank, material, type (opcjonalnie flutes, coating później). URL params + WP_Query.

---

## 13) Product card (category / related / bestsellers)

Strict order:

1. Image (square 1:1, `object-fit: contain`, białe tło)
2. Title (max 2 linie)
3. **Key spec line** (1 linia, np. „D=38 mm • S=8 mm • VHM”)
4. Price
5. CTA full width: „Dodaj do koszyka”

**AC:** CTA height ≥ 44px, padding karty 16px, radius ~14px, delikatny cień. **Nie** infografika/wymiary jako główne zdjęcie, jeśli istnieje czyste zdjęcie produktu.

---

## 14) Mobile layout

- **Breakpoint:** 768px.
- **Mobile grid:** 2 kolumny (karty).
- **Image ratio:** 1:1.
- Wszystkie interaktywne elementy ≥ 44px (tap target).

---

## 15) CSS rules

Wszystkie style wyłącznie w **child theme**. Zakaz edycji Storefront (parent).

---

## 16) Plugin policy

- Cel: ≤ 15 pluginów łącznie.
- **Dozwolone:** WooCommerce, BaseLinker, jeden SEO, jeden cache, jeden security.
- **Usunąć/zastąpić:** pluginy filtrów (dowolne), page buildery (Elementor itd.), „pomocnicze” shortcode’y duplikujące prosty kod.

---

## 17) Performance

- Brak nowych ciężkich bibliotek JS.
- Unikać nadmiaru w DOM.
- Obrazy: ustawione width/height (ograniczenie CLS).
- CSS podzielony logicznie: catalog / product / filters / buttons.

---

## 18) Verification checklist (output agenta)

- Mobile 360px: strona produktu + kategoria
- Product card: struktura / kolejność / rozmiar CTA
- Filtry: zastosowanie, persystencja w URL, clear
- Brak edycji parent theme
- Brak nowych pluginów
- Lista zmienionych plików
