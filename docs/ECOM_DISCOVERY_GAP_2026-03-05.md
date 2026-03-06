# Discovery: branch e-commerce (CNC tools)

Data: 2026-03-05  
Cel: zmapować stan obecny vs stan docelowy dla sklepu branżowego CNC (B2B + B2C), z naciskiem na UX, konwersję i nowoczesny wizual.

## 1) Co już jest (as-is)

- Jest custom logic w `mu-plugins` (dostawa, loyalty, trust, shortcodes, SEO, checkout hints).
- Jest przebudowana główna i własne template stron (`front-page.php`, `page-dostawa.php`, `page-kontakt.php`, SEO landing pages).
- Jest integracja płatności i kurierów (Przelewy24, InPost, Furgonetka).
- Jest baza trust content (Allegro, loyalty tiers, delivery rules).
- Jest podstawowy stack SEO (Yoast + custom metadata/FAQ schema).

## 2) Co musi być dla tej branży (to-be)

### P0 — must-have (konwersja i wiarygodność)

1. Czytelny i spójny visual system (bez chaosu komponentów).
2. Bardzo mocna karta produktu (PDP): cena, stock, CTA, zastosowanie, parametry techniczne.
3. Realny social proof per produkt (opinie, ile kupiło, trust badges bez duplikatów).
4. Szybki i prosty flow zakupowy mobile-first.
5. Spójna nawigacja IA: brak dubli stron i etykiet, jasny podział kategorii.

### P1 — ważne dla wzrostu

1. Lepszy PLP: filtry branżowe i porównywalne karty.
2. Lepsze treści techniczne: dobór freza do materiału i zastosowania.
3. Lepsze merchandising blocks: cross-sell/up-sell pod konkretny typ obróbki.
4. Lepszy checkout trust (dostawa, zwroty, VAT) bez przeciążania.

### P2 — skalowanie i przewaga

1. Ekspercki content hub (guides/how-to pod materiały i geometrie frezów).
2. Segmentacja B2B (progi cenowe, lead capture dla stałych klientów).
3. Automatyczny feed social proof z marketplace + własne UGC.

## 3) Gap matrix (jest vs powinno być)

1. **Visual consistency**  
   - Jest: częściowo, nadal niespójne sekcje i kontrast.  
   - Powinno: jednolity design system + tokeny + komponenty.

2. **PDP conversion hierarchy**  
   - Jest: dużo elementów, miejscami konkurują o uwagę.  
   - Powinno: 1 główne CTA, jasna hierarchia informacji technicznej.

3. **Navigation clarity**  
   - Jest: poprawione, ale nadal wymaga porządku IA i cleanup dubli menu/sekcji.  
   - Powinno: jedna wersja każdej strony i jednoznaczna taksonomia kategorii.

4. **Instagram / social freshness**  
   - Jest: CTA + shortcode.  
   - Powinno: 3-6 świeżych postów (auto + fallback) na home i/lub footer.

5. **Speed perception**  
   - Jest: performance pluginy i optymalizacje są.  
   - Powinno: szybki perceived loading + czysty above-the-fold bez szumu.

## 4) Co jest najważniejsze wizualnie dla tej branży

- Profesjonalny, techniczny styl (precyzja > ozdobniki).
- Wysoki kontrast i czytelność parametrów.
- Produkt i jego zastosowanie muszą dominować nad dekoracją.
- Użytkownik musi szybko zrozumieć:
  - do jakiego materiału jest frez,
  - czy jest dostępny,
  - kiedy dojdzie,
  - ile kosztuje i gdzie kliknąć.

## 5) Plan wdrożenia (P0/P1)

### P0 (teraz)

1. Final cleanup dubli IA/menu i stron pomocniczych.
2. Pełna normalizacja kontrastu i komponentów krytycznych.
3. Uproszczenie home/PDP do jednego flow konwersyjnego.
4. Stabilny social/Instagram block (auto + fallback).

### P1 (kolejna fala)

1. PLP refinement pod porównywanie produktów.
2. PDP refinement pod decyzję techniczną (dobór freza).
3. Checkout refinement pod finalizację i zaufanie.

## 6) KPI do monitorowania (realne dla branży)

- CTR z home do PLP/PDP.
- Add-to-cart rate na PDP.
- Bounce rate PDP (mobile i desktop).
- Checkout completion rate.
- Revenue per session.
