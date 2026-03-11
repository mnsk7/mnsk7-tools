# Performance — status i bazowa linia

**Aktualny status (po action plan P1/P2):**

| Strona   | Status    | Metryki (Lighthouse mobile) |
|----------|-----------|------------------------------|
| **Home** | **improved** | 69 / 2,0 s FCP / 2,9 s LCP / 910 ms TBT / 0,004 CLS. Nie ruszać bez osobnego powodu. |
| **Archive** | **blocked by LCP** | 79 / 1,8 s FCP / **4,8 s LCP** / 0 ms TBT / 0,004 CLS. Główny bottleneck: LCP. |

---

## Kolejny fokus: tylko archive LCP

- **Nie zmieniać** logiki home.
- **Osobny pass:** [PERFORMANCE-ARCHIVE-LCP-PASS.md](PERFORMANCE-ARCHIVE-LCP-PASS.md) — ustalenie realnego LCP elementu (trace/Lighthouse), lista kontrolna (promo/header, TTFB, critical CSS, font), minimalne zmiany tylko pod archive, bez wzrostu TBT.

---

## Historia (dla kontekstu)

| Pass   | Status    | Uwagi |
|--------|-----------|--------|
| Pass 1 | baseline (historyczny) | Home: 58 / 1,79 s / 3,13 s / 1668 ms TBT. Archive: 69 / 1,78 s / 2,68 s / 1087 ms TBT. |
| Pass 2 | rejected | Regresje: home TBT, archive LCP. |
| Pass 2b | rejected | Brak czystej korzyści vs Pass 1. |
| Action plan (P1.1, P1.3, P2.7, P2.8) | wdrożone | fragments off home, mobile megamenu skip, transient get_terms. Home improved; archive TBT 0, LCP 4,8 s. |
