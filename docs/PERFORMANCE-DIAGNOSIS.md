# Targeted performance diagnosis (source: docs/lighthouse-*.json)

**Source of truth:** `docs/lighthouse-archive-before.json`, `lighthouse-archive-after.json`, `lighthouse-home-before.json`, `lighthouse-home-after.json`.

---

## 1. Archive-after diagnosis

### Метрики vs archive-before

| Метрика       | before | after | Δ    |
|---------------|--------|-------|------|
| Performance   | 54     | 51    | −3   |
| LCP           | 3.2 s  | 3.7 s | +0.5 s |
| TBT           | 2 220 ms | 4 396 ms | +2 176 ms |
| FCP           | 2.3 s  | 2.2 s | −0.1 s |

### LCP: promo bar как доминанта

- **Affected element:** `span.mnsk7-promo-bar__text`  
  **Selector:** `div#page > div#mnsk7-promo-bar > div.mnsk7-promo-bar__inner > span.mnsk7-promo-bar__text`
- **Root cause:** На archive первый крупный видимый контент в viewport — текст промо-бара. Выше него только header (меньше по площади). Карточки/таблица товаров появляются ниже и/или позже, поэтому LCP «выбирает» промо-бар.
- **Breakdown (archive-after):**
  - TTFB: **~1 951 ms**
  - Element render delay: **~1 509 ms**
- **Likely source in code:**
  - Promo bar в начале `#page` в `header.php` (строки ~60–64), уже с inline critical CSS в `<style id="mnsk7-header-critical">`.
  - Render delay ~1.5 s: ожидание `main.css`, шрифтов, либо блокировка main thread (TBT 4.4 s) откладывает paint.

### TTFB ~1 950 ms (archive)

- **Root cause:** Серверная задержка отдачи HTML (Root document 1 610 ms в `server-response-time`).
- **Likely source:** PHP/WooCommerce на archive (запросы к БД, шаблоны, плагины). Не исправляется только темой.
- **Suggested fixes (приоритет):** full‑page cache для `/sklep/`, оптимизация запросов в archive template, проверка плагинов; хостинг/OPcache.

### TBT 4 396 ms (archive-after)

- **Root cause:** 20 long tasks; main thread занят >50 ms на задачу многократно.
- **Worst contributors (long-tasks):**
  - **Unattributable,** 1 448 ms (start ~1.05 s) — самый тяжёлый блок.
  - **jquery.min.js,** 1 147 ms (start ~6.5 s).
  - **Document `/sklep/`** (inline/HTML): 533, 403, 392, 377 ms в разных задачах.
  - Дальше: Unattributable 468 ms, jQuery 227 ms, wc-blocks.css 200 ms, order-attribution.min.js 175 ms, pwaforwp.min.js 121 ms.
- **Likely source in code:**
  - Тема: большой inline script в `functions.php` (runCritical/runDeferred), выполнение при загрузке.
  - Плагины: jQuery, WooCommerce blocks CSS, order-attribution, PWA — все в критическом пути.
- **Suggested fixes:** уменьшить/разбить inline init; отложить некритичный JS (cookie bar, PWA, order-attribution); не грузить блоки Woo на archive, если не нужны.

---

## 2. Home-after diagnosis

### Метрики vs home-before

| Метрика       | before | after | Δ     |
|---------------|--------|-------|-------|
| Performance   | 54     | 36    | −18   |
| LCP           | 3.1 s  | 6.9 s | +3.8 s |
| TBT           | 2 994 ms | 2 148 ms | −846 ms |
| FCP           | 2.4 s  | 2.5 s | +0.1 s |

### LCP: cookie bar как доминанта

- **Affected element:** `p.mnsk7-cookie-bar__text`  
  **Selector:** `body.home > div#mnsk7-cookie-bar > div.mnsk7-cookie-bar__inner > p.mnsk7-cookie-bar__text`
- **Snippet (nodeLabel):** «Ta strona używa plików cookie — niezbędnych do działania sklepu oraz opcjonalny…»
- **boundingRect:** top 640, height 90, width 380 — элемент в нижней части viewport (на типичном mobile ~640px по высоте).

**Почему именно он стал LCP**

- На home нет одного явного «героя» (hero image/блок) в начале страницы, который был бы крупнее остальных.
- Cookie bar в DOM с самого начала (`footer.php`), но изначально скрыт: `hidden`, `aria-hidden="true"`.
- Он показывается **после выполнения JS**: скрипт в `footer.php` снимает `hidden` и добавляет `mnsk7-cookie-bar-visible`.
- Момент снятия `hidden` — момент «paint» этого блока. К этому времени основной контент страницы уже отрисован, но по площади текст cookie bar (крупный блок текста 380×90) оказывается **largest** среди видимых к моменту 6.9 s.
- Итог: LCP = момент появления cookie bar, а не более ранний контент.

**Почему LCP вырос до 6.9 s**

- **Breakdown (home-after):**
  - TTFB: **~3 938 ms**
  - Element render delay: **~4 513 ms**
- Root document: **3 776 ms** (`server-response-time`) — очень высокий TTFB на home.
- Cookie bar «рисуется» только после:
  1. Получения HTML (после 3.8 s),
  2. Парсинга, загрузки CSS/JS,
  3. Выполнения JS, снимающего `hidden`.
- Element render delay ~4.5 s = задержка между готовностью документа/ресурсов и отрисовкой cookie bar (ожидание CSS, шрифтов, длинные задачи, затем выполнение скрипта показа бара).

**Likely source in code**

- **footer.php:** блок `#mnsk7-cookie-bar` с `hidden`; инлайн-скрипт, который по условию (consent) вызывает `show()` и снимает `hidden`.
- **functions.php:** `body_class` добавляет `mnsk7-cookie-bar-visible` только при определённом состоянии; отображение же в run-time управляется JS в footer.
- Стили cookie bar: `10-cookie-bar.css` → `main.css`; без них блок не в нужной позиции/размере, что может сдвигать момент «стабильного» LCP.

---

## 3. Top 5 fixes by impact

| # | Fix | Affected page(s) | Impact | Likely effect |
|---|-----|------------------|--------|----------------|
| **1** | **Home: убрать cookie bar из кандидатов LCP** — не показывать его до после LCP (например, показывать по `requestIdleCallback` или после `document.load` с задержкой 2–3 s), либо рендерить бар только после первого взаимодействия / в футере без фиксированной позиции до согласия. | Home | LCP 6.9→~3.x s | Самый большой выигрыш: LCP переключится на контент выше (hero, заголовки, первый продукт). |
| **2** | **Снизить TTFB** — full‑page cache dla `/` i `/sklep/`, optymalizacja zapytań. **Poza zakresem temy** (serwer, cache). | Home, Archive | FCP, LCP | Wymaga FPC po stronie serwera. |
| **3** | **Archive: уменьшить влияние промо-бара на LCP** — либо сузить визуально (меньше высота/шрифт в first viewport), либо добавить выше в DOM более крупный LCP-кандидат (например, заголовок H1 + описание категории) с критическими стилями; сохранить текущий critical CSS для промо-бара. | Archive | LCP 3.7→~2.5–3 s | LCP сместится на более ранний/крупный элемент при той же TTFB. |
| **4** | **Снизить TBT на archive** — разбить/отложить большой inline init в `functions.php`; отключить или отложить ненужный на archive JS (order-attribution, PWA, блоки Woo), не грузить тяжёлый jQuery до взаимодействия, если возможно. | Archive | TBT 4.4→<2 s | Меньше блокировок → раньше paint → лучше LCP и отзывчивость. |
| **5** | **Home: не делать cookie bar «largest»** — альтернатива п.1: гарантировать, что на first viewport есть один чёткий, более крупный LCP-элемент (hero image или заголовок с стилями выше cookie bar), и грузить его с приоритетом (preload, critical CSS). Тогда даже при позднем показе cookie bar LCP останется у hero/заголовка. | Home | LCP | Страховка: если бар всё же показывают рано, LCP не переключится на него. |

---

## 4. Wdrożone zmiany (diagnosis fixes)

| Fix | Plik | Zmiana |
|-----|------|--------|
| 1. Home: cookie bar po LCP | `footer.php` | Pokazanie cookie bar odroczone: `requestIdleCallback` + `setTimeout(show, 3500)`. Bar ~3.5 s po load — poza oknem LCP. |
| 2. TTFB | — | Bez zmian w temie. Wymaga FPC / optymalizacji serwera. |
| 3. Archive: kompaktowy promo bar | `header.php`, `functions.php` | Klasa `mnsk7-archive` na body. Critical CSS: mniejszy promo bar na archive. |
| 4. Archive: TBT | `functions.php` | Na archive cały init w jednym `requestIdleCallback(..., { timeout: 150 })`. |
| 5 | — | Zabezpieczenie przez fix 1. |

---

## 5. What to test after fixes

1. **Lighthouse (mobile), ten sam URL i tryb:**  
   - Home: `https://staging.mnsk7-tools.pl/`  
   - Archive: `https://staging.mnsk7-tools.pl/sklep/`  
   Сохранять JSON в `docs/lighthouse-home-*.json`, `docs/lighthouse-archive-*.json`.

2. **Проверить LCP element:**  
   В новом JSON: `audits["lcp-breakdown-insight"].details.items` — убедиться, что LCP element изменился на ожидаемый (home: не cookie bar; archive: при желании — не промо-бар или тот же с меньшим временем).

3. **Метрики:**  
   - Home: Performance, LCP (цель <4 s), TBT, FCP.  
   - Archive: Performance, LCP (цель <3 s), TBT (цель <2 s), FCP.

4. **Регрессии:**  
   - Cookie bar на home по-прежнему появляется и принимает выбор (accept/reject).  
   - Promo bar на archive по-прежнему виден и закрывается.  
   - Корзина, чекаут, навигация работают без задержек.

5. **Сравнение до/после:**  
   Обновлять таблицы в этом документе или в `PERFORMANCE-STATUS.md` значениями из новых JSON (categories.performance.score, audits для FCP, LCP, TBT, server-response-time).

---

## 6. Pomiar przez PageSpeed Insights (PSI)

Przy ręcznym sprawdzaniu **prędkość często wychodzi w PSI lepiej** niż w lokalnym Lighthouse (inna sieć, warunki Google). Warto mierzyć też przez PSI.

**Ręczny pomiar:** wejdź na https://pagespeed.web.dev/ → wklej URL (home: `https://staging.mnsk7-tools.pl/`, archive: `https://staging.mnsk7-tools.pl/sklep/`) → Mobile → uruchom. Zapisz Performance, FCP, LCP, TBT.

**Z API (CLI):** bez klucza Google API wywołanie zwraca 429 Quota exceeded. Z kluczem (Google Cloud → PageSpeed Insights API):

```bash
KEY="${PAGESPEED_API_KEY:-YOUR_KEY}"
curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://staging.mnsk7-tools.pl/&strategy=mobile&key=${KEY}" -o docs/psi-home.json
curl -s "https://www.googleapis.com/pagespeedonline/v5/runPagespeed?url=https://staging.mnsk7-tools.pl/sklep/&strategy=mobile&key=${KEY}" -o docs/psi-archive.json
```

Metryki w `lighthouseResult.audits` (FCP, LCP, TBT).
