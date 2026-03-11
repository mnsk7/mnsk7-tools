# Performance — status i bazowa linia

**Aktualny status:**

| Strona   | Status    | Uwagi |
|----------|-----------|--------|
| **Home** | do weryfikacji | Po archive LCP pass: TBT 910→1310 ms (regresja). Corrective pass: cofnięto rAF w runDeferred; critical CSS zostaje. Wymaga ponownego pomiaru. |
| **Archive** | do weryfikacji | LCP poprawiony 4,8→2,8 s (critical CSS promo bar), ale TBT 0→1830 ms. Cofnięto rAF; critical CSS zostaje. Wymaga ponownego pomiaru. |

- **Archive LCP pass:** nie przyjęty jako finalny. Potwierdzenie: promo bar / critical CSS **poprawia LCP**. Regresja TBT powiązana z wprowadzonym w passie **requestAnimationFrame** w runDeferred — cofnięta (corrective pass). Szczegóły: [PERFORMANCE-ARCHIVE-LCP-PASS.md](PERFORMANCE-ARCHIVE-LCP-PASS.md) §9.
- **Kolejny krok:** Lighthouse home + archive po corrective pass; potwierdzić, że TBT wraca przy zachowaniu LCP archive ~2,8 s.

---

## Historia (dla kontekstu)

| Pass   | Status    | Uwagi |
|--------|-----------|--------|
| Pass 1 | baseline (historyczny) | Home: 58 / 1,79 s / 3,13 s / 1668 ms TBT. Archive: 69 / 1,78 s / 2,68 s / 1087 ms TBT. |
| Pass 2 | rejected | Regresje: home TBT, archive LCP. |
| Pass 2b | rejected | Brak czystej korzyści vs Pass 1. |
| Action plan (P1.1, P1.3, P2.7, P2.8) | wdrożone | fragments off home, mobile megamenu skip, transient get_terms. Home improved; archive TBT 0, LCP 4,8 s. |
| Archive LCP pass (critical CSS + rAF) | nie przyjęty | LCP archive 4,8→2,8 s ✓; TBT home/archive regresja. Corrective: cofnięto rAF w runDeferred; critical CSS zostaje. |
