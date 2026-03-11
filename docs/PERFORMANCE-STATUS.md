# Performance — status i bazowa linia

**Aktualna bazowa linia:** **Pass 1.**

| Pass   | Status    | Uwagi |
|--------|-----------|--------|
| **Pass 1** | **current best baseline** | Home: 58 / 1,79 s / 3,13 s / 1668 ms TBT. Archive: 69 / 1,78 s / 2,68 s / 1087 ms TBT. CLS ~0,004. |
| Pass 2 | **rejected** | Regresje: home TBT 1668→2990 ms, archive LCP 2,68→4,4 s. |
| Pass 2b | **rejected** | Lepszy niż Pass 2, ale nadal gorszy niż Pass 1 (home TBT 2410 ms, archive słabszy). Brak czystej korzyści. |

---

## Rekomendacja

- **Przyjąć Pass 1 jako działającą bazę.** Nie kontynuować ogólnych eksperymentów „w ciemno”.
- **Odrollować do Pass 1:** przywrócić stan kodu z przed Pass 2 / Pass 2b (w tym `functions.php` — init headera, ewentualnie `content-product-table-row.php`), tak aby metryki odpowiadały Pass 1. Szczegóły zmian do cofnięcia: [PERFORMANCE-PASS-2.md](PERFORMANCE-PASS-2.md), [PERFORMANCE-PASS-2b.md](PERFORMANCE-PASS-2b.md).
- **Dalsze prace tylko jako osobne, celowane zadania:**
  1. **Zadanie: home TBT** — profilowanie long tasks (Lighthouse / DevTools), rozbicie dużego inline/init, **bez pogorszenia archive**.
  2. **Zadanie: archive LCP** — ustalenie realnego LCP elementu w aktualnym buildzie, działania **tylko** pod ten element, **bez pogorszenia TBT**.

Metryki Pass 1 (Lighthouse mobile, staging): [PERFORMANCE-AUDIT-AND-PLAN.md](PERFORMANCE-AUDIT-AND-PLAN.md).
