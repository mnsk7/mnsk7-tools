# Performance — status i bazowa linia

**Aktualny status:**

| Strona   | Status    | Uwagi |
|----------|-----------|--------|
| **Home** | do weryfikacji | Wdrożono diagnosis fixes: cookie bar pokazywany z opóźnieniem 3,5 s (poza oknem LCP). Wymaga pomiaru Lighthouse. |
| **Archive** | do weryfikacji | Wdrożono: kompaktowy promo bar (mnsk7-archive), cały init w jednym rIC (TBT). Wymaga pomiaru Lighthouse. |

- **Diagnosis (docs/lighthouse-*.json):** [PERFORMANCE-DIAGNOSIS.md](PERFORMANCE-DIAGNOSIS.md) — archive LCP = promo bar, home LCP = cookie bar (6,9 s). Wdrożone zmiany: §4 tamże.
- **Kolejny krok:** Lighthouse home + archive (mobile); zapisać JSON; zweryfikować LCP element i metryki (Home LCP &lt; 4 s, Archive LCP &lt; 3 s, TBT bez regresji).
- **PSI:** prędkość bywa lepsza w PageSpeed Insights niż w lokalnym Lighthouse. Ręczny pomiar: [pagespeed.web.dev](https://pagespeed.web.dev/). Z API: [PERFORMANCE-DIAGNOSIS.md](PERFORMANCE-DIAGNOSIS.md) §6.

---

## Historia (dla kontekstu)

| Pass   | Status    | Uwagi |
|--------|-----------|--------|
| Pass 1 | baseline (historyczny) | Home: 58 / 1,79 s / 3,13 s / 1668 ms TBT. Archive: 69 / 1,78 s / 2,68 s / 1087 ms TBT. |
| Pass 2 | rejected | Regresje: home TBT, archive LCP. |
| Pass 2b | rejected | Brak czystej korzyści vs Pass 1. |
| Action plan (P1.1, P1.3, P2.7, P2.8) | wdrożone | fragments off home, mobile megamenu skip, transient get_terms. Home improved; archive TBT 0, LCP 4,8 s. |
| Archive LCP pass (critical CSS + rAF) | nie przyjęty | LCP archive 4,8→2,8 s ✓; TBT home/archive regresja. Corrective: cofnięto rAF w runDeferred; critical CSS zostaje. |
| Diagnosis fixes | wdrożone | Home: cookie bar deferred 3,5 s. Archive: mnsk7-archive, compact promo bar, init w jednym rIC. |
| Diagnosis fixes (PERFORMANCE-DIAGNOSIS) | wdrożone | Home: cookie bar deferred 3,5 s. Archive: mnsk7-archive + compact promo bar, init w jednym rIC. TTFB poza temą. |
