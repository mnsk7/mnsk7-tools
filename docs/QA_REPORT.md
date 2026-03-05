# QA Report — mnsk7-tools.pl

*(Создаётся и обновляется агентом 08_qa_security)*

Чеклисты smoke/regression, безопасность, производительность. Задачи и фиксы — в [tasks/000_inbox.md](../tasks/000_inbox.md).

---

## 1. Smoke / regression (WooCommerce)

Проверять после деплоя на staging и перед/после релиза на prod. Источник: `qa_smoke_woo`.

| # | Проверка | Ожидание | Статус |
|---|----------|----------|--------|
| S1 | Add to cart из страницы категории | Товар попадает в корзину, счётчик обновляется | ☐ |
| S2 | Add to cart из карточки товара | То же; при смене вариации (если есть) — корректный товар | ☐ |
| S3 | Изменение количества в корзине | Qty меняется, пересчёт суммы корзины | ☐ |
| S4 | Применение купона/скидки (если используется) | Скидка применяется, итог пересчитывается | ☐ |
| S5 | Checkout end-to-end | Заполнение полей → выбор доставки/оплаты → размещение заказа без ошибок | ☐ |
| S6 | Письмо клиенту после заказа | Email уходит (на prod; на staging — см. staging-safety, maile blokowane) | ☐ |
| S7 | Статус заказа | W panelu: zamówienie w odpowiednim statusie; brak duplikatów | ☐ |

**Uwagi:** Na staging płatności są wyłączone (staging-safety); S5 można sprawdzić do momentu wyboru metody płatności lub z testową metodą (np. COD jeśli włączona). S6 na staging — nie testować (maile blokowane).

---

## 2. Bezpieczeństwo (security baseline)

Źródło: `security_wp_baseline`, [AS_IS_RISKS.md](AS_IS_RISKS.md).

| # | Punkt | Działanie / kryterium | Status |
|---|--------|------------------------|--------|
| SEC-1 | Minimum pluginów | Usunięcie duplikatów (filtr, wishlist, builder, GTM, schema, Pixel) — według AS_IS_BACKLOG | ☐ |
| SEC-2 | Aktualizacje WP i pluginów | Jądro i krytyczne pluginy na aktualnych wersjach; test na staging przed prod | ☐ |
| SEC-3 | Ograniczenie logowania | limit-login-attempts-reloaded aktywny i skonfigurowany | ☐ |
| SEC-4 | xmlrpc.php | Zablokowany (403) w mu-plugin mnsk7-tools.php — P0-03 | ☐ (zweryfikować po deploy) |
| SEC-5 | Backupy | Harmonogram backupów BД + plików (UpdraftPlus lub inny) — P0-04 | ☐ |
| SEC-6 | Uprawnienia plików | Katalogi 755, pliki 644; wp-config poza web root lub z odpowiednimi prawami | ☐ |

---

## 3. Wydajność i Core Web Vitals

Źródło: `performance_corewebvitals`, [AS_IS_AUDIT.md](AS_IS_AUDIT.md) D.

| # | Punkt | Działanie / kryterium | Status |
|---|--------|------------------------|--------|
| PERF-1 | Cache stron | Jeden plugin cache (LiteSpeed lub WP Rocket); Seraphinite i drugi wyłączone — P0-02 | ☐ |
| PERF-2 | Wyłączenia cache | cart, checkout, my-account wykluczone z cache — P1-05 | ☐ |
| PERF-3 | LCP | Obrazy hero/ produktów nie blokują LCP; rozmiary/ lazy load poprawne | ☐ |
| PERF-4 | Obrazy produktów | Konwersja PNG→WebP, kompresja (P1-03); unikanie 3–4 MB PNG | ☐ |
| PERF-5 | CLS | Stałe wymiary obrazów, brak przesuwania layoutu przy ładowaniu | ☐ |
| PERF-6 | JS na checkout | Minimum skryptów na stronie checkout (usunięcie dubli pluginów — P1-04) | ☐ |

---

## 4. Zależności od zadań Sprint 01

Po wykonaniu zadań S1-02, S1-03, S1-04, S1-05:

- **SEC-4:** S1-04 (xmlrpc) → odhaczyć SEC-4 po weryfikacji `curl -I .../xmlrpc.php` → 403.
- **PERF-1, PERF-2:** S1-02, S1-03 (jeden cache, wyłączenia) → przed testem smoke S5 upewnić się, że checkout nie jest cache’owany.
- **SEC-5:** S1-05 (backupy) → po konfiguracji odhaczyć SEC-5.

Rekomendacja: najpierw S1-02, S1-03, potem pełny smoke (S1–S7) na staging.

---

## 5. Otwarte punkty / inbox

- Przed wyłączeniem 3 pluginów filtrów (S1-08): sprawdzić w Google Search Console URL-e filtrów w indeksie; zaplanować przekierowania (R02).
- Po wdrożeniu overrides karty produktu (S1-07): dodać do smoke weryfikację wyświetlania pojedynczego produktu (strona 200, brak białego ekranu, hooki Woo działają).

W razie znalezionych błędów: opisać w [tasks/000_inbox.md](../tasks/000_inbox.md) i przypisać do sprintu.

---

## 6. Historia zmian

| Data | Opis |
|------|------|
| 2026-03-05 | Utworzenie raportu przez 08_qa_security: checklists smoke, security, performance; powiązanie ze Sprint 01 i AS_IS. |
