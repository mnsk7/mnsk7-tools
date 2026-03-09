# Przewodnik — SEO artykuły (baza wiedzy)

Rekomendacje dla treści w dziale **Przewodnik** (/przewodnik/): struktura artykułu, linki do produktów, FAQ, filtracja.

---

## 1. Cel SEO

- **Trafik:** długi ogon (np. „frez diamentowy do granitu”, „jak dobrać frez do aluminium”).
- **Ekspertność i zaufanie:** poradniki, porównania, rekomendacje.
- **Wewnętrzna linkowanie:** do kategorii i kart produktów; użytkownik po lekturze trafia do sklepu i może skorzystać z **filtrów** (średnica, długość robocza itd.) na stronie kategorii.

---

## 2. Struktura pojedynczego artykułu

- **H1** — tytuł artykułu (słowa kluczowe, czytelny).
- **Wstęp** — 1–2 akapity (o czym tekst, dla kogo).
- **Sekcje H2** — logiczny podział (rodzaje, zalety, dobór, parametry).
- **Linki do kategorii/produktów** — w treści lub w bloku „Polecane produkty” (shortcode).
- **FAQ** — na dole strony (harmonijka + schema FAQPage).

Na stronie kategorii produktów użytkownik ma **filtry atrybutów** (Średnica, Długość robocza itd.) — w artykułach warto linkować do kategorii, a nie tylko do pojedynczych produktów, żeby odbiorca mógł od razu zawęzić wyniki.

---

## 3. Shortcode: linki do produktów i kategorii

W treści artykułu używaj shortcode **`[mnsk7_guide_products]`**.

### Jedna kategoria (link + opcjonalnie siatka produktów)

```
[mnsk7_guide_products category="frez-diamentowy" title="Frezy diamentowe w ofercie" format="links"]
```

- **format="links"** — link do kategorii + liczba produktów (domyślnie).
- **format="grid"** — link + siatka produktów z kategorii (np. limit="6").

### Wiele kategorii (lista linków)

```
[mnsk7_guide_products categories="frez-diamentowy,frez-spiralny,frez-kulowy" title="Powiązane kategorie"]
```

### Konkretne produkty (ID)

```
[mnsk7_guide_products ids="123,456,789" title="Polecane frezy"]
```

Slugi kategorii możesz podejrzeć w menu **Sklep** (np. frez-diamentowy, frez-spiralny) lub w **WooCommerce → Kategorie**.

---

## 4. FAQ na dole artykułu

Pod treścią wyświetlana jest **harmonijka FAQ** (akordeon). Domyślnie zestaw **„produkt”** (dobór frezu, HRC, 1P vs 4P itd.).

**Własny zestaw lub tytuł** — w edytorze wpisu (Custom Fields) ustaw:

- **mnsk7_faq_set** — `produkt` | `dostawa` | `sklep` (lub pusty = produkt).
- **mnsk7_faq_title** — np. „FAQ — frezy diamentowe”.

FAQ jest wyprowadzane w **schema.org FAQPage** (rich results w Google).

---

## 5. Filtracja a linki w artykułach

- Strony **kategorii** (/kategoria-produktu/frez-diamentowy/) mają blok **Filtruj** (chipy: Średnica, Długość robocza itd.).
- W artykułach **linkuj do kategorii** — użytkownik wejdzie w katalog i od razu może zawęzić wyniki filtrami.
- W tekście możesz wspomnieć np.: „W kategorii [Frezy diamentowe](/kategoria-produktu/frez-diamentowy/) możesz zawęzić listę po średnicy i długości roboczej.”

---

## 6. Rekomendacje treściowe

| Element | Działanie |
|--------|-----------|
| **Tytuł (H1)** | Słowa kluczowe + jasny przekaz (np. „Frez diamentowy — kompletny przewodnik”). |
| **Meta description** | 150–160 znaków, zachęta do kliknięcia (Yoast / inna wtyczka). |
| **Wstęp** | 1–2 zdania: dla kogo tekst, co czytelnik zyska. |
| **H2** | Sekcje: rodzaje, zalety, dobór, przykłady produktów. |
| **Linki w tekście** | Do kategorii („zobacz frezy diamentowe”) i ewentualnie do 1–2 konkretnych produktów. |
| **Shortcode** | Jeden blok „Polecane produkty” lub „Powiązane kategorie” (links lub grid). |
| **FAQ** | Domyślnie zestaw „produkt”; przy temacie dostawy — „dostawa”. |

---

## 7. Przykład treści (frez diamentowy)

- **H1:** Frez diamentowy — kompletny przewodnik dla użytkowników CNC  
- **Wstęp:** Krótko: do czego frezy diamentowe, dla kogo.  
- **H2:** Zalety frezów diamentowych (trwałość, precyzja, wszechstronność).  
- **H2:** Rodzaje (proste, stożkowe) + w tekście linki do kategorii.  
- **H2:** Jak wybrać frez (materiał, typ obróbki) + link do kategorii.  
- Wstawka: `[mnsk7_guide_products category="frez-diamentowy" title="Frezy diamentowe w ofercie"]`.  
- Na dole: FAQ (domyślnie produkt; można ustawić **mnsk7_faq_title** „FAQ — frezy diamentowe”).

---

## 8. Pliki w projekcie

| Miejsce | Opis |
|--------|------|
| `wp-content/themes/mnsk7-storefront/single.php` | Szablon pojedynczego wpisu: okruszki, H1, treść, FAQ. |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/13-seo-landing.css` | Style .mnsk7-guide-single, .mnsk7-guide-products, FAQ. |
| `mu-plugins/inc/guide-seo.php` | Shortcode `[mnsk7_guide_products]`. |
| `mu-plugins/inc/faq.php` | Shortcode `[mnsk7_faq]`, schema FAQPage, JS harmonijki (również na single post). |

---

---

## 9. Aktualizacja istniejącego artykułu (WP-CLI)

Na serwerze staging (gdzie jest wp-cli w PATH):

```bash
cd ~/domains/mnsk7-tools.pl/public_html/staging
wp eval-file /ścieżka/do/repo/scripts/update-przewodnik-article.php
```

Albo skopiuj `scripts/update-przewodnik-article.php` na serwer i uruchom:

```bash
wp eval-file scripts/update-przewodnik-article.php
```

Skrypt: znajduje post z „Frez diamentowy” w tytule, dopisuje blok z shortcode `[mnsk7_guide_products category="frez-diamentowy" ...]` oraz ustawia meta `mnsk7_faq_set`, `mnsk7_faq_title`.

Alternatywa (bash): `scripts/update-przewodnik-article.sh` — uruchomić w katalogu WP na serwerze.

---

*Dokument utworzony 2026-03-09. Zgodne z CONTENT_PLAN, SEO_PLAN i regułami repozytorium (tylko theme / mu-plugins / custom plugin).*
