# Cookie consent — RODO/GDPR (motyw mnsk7-storefront)

## Co robi bank w temacie

- **Wyświetlanie:** pasek na dole strony (fixed), dopóki użytkownik nie wybierze opcji.
- **Przyciski:**
  - **„Akceptuję wszystkie”** — zapisuje zgodę na wszystkie pliki cookie (w tym opcjonalne, np. analityka).
  - **„Tylko niezbędne”** — zapisuje odrzucenie opcjonalnych; tylko cookie niezbędne do działania sklepu (sesja, koszyk itd.) są dopuszczalne.
  - **„Ustawienia”** — link do Polityki prywatności (#cookies), gdzie użytkownik może przeczytać szczegóły i ewentualnie zmienić wybór (np. wyczyścić cookie i odświeżyć).
- **Zapis:** wartość `accept` lub `reject` w `localStorage` (klucz `mnsk7_cookie_consent`) oraz w pliku cookie o tej samej nazwie (path=/, 1 rok, SameSite=Lax).
- **Kompatybilność wsteczna:** stara wartość `1` w cookie/localStorage jest traktowana jak „accept”.
- **API dla skryptów:**
  - `window.mnsk7CookieConsent` — po załadowaniu strony: `'accept'` | `'reject'` | `null` (gdy brak wyboru).
  - Zdarzenie `mnsk7-cookie-consent` na `document` po wyborze (detail: `'accept'` lub `'reject'`).
- **PHP:** funkcja `mnsk7_get_cookie_consent()` zwraca `'accept'` | `'reject'` | `null` (odczyt z cookie; przydatne przy warunkowym wyświetlaniu skryptów w HTML).

## Zgodność z RODO/GDPR

- **Informacja:** tekst w banku mówi, że strona używa cookie niezbędnych i opcjonalnych oraz odwołuje do Polityki prywatności — to spełnia wymóg informacji.
- **Wybór:** użytkownik może zaakceptować wszystkie lub wybrać „Tylko niezbędne” (odpowiednik odrzucenia opcjonalnych) — spełnienie zasady zgody.
- **Brak zgody domyślnej:** pasek jest widoczny do momentu wyboru; skrypty opcjonalne (analityka, reklamy) **nie powinny** być ładowane przed wyborem „Akceptuję wszystkie” — to trzeba zaimplementować po stronie integracji (patrz niżej).
- **Ustawienia:** link „Ustawienia” prowadzi do Polityki prywatności (sekcja #cookies); zmiana wyboru = np. wyczyszczenie cookie + ponowne wejście na stronę lub przyszła strona „Preferencje cookie”.

## Co trzeba zrobić po stronie integracji

1. **Analityka (GA, Matomo, Facebook itd.):** ładować skrypt dopiero gdy `window.mnsk7CookieConsent === 'accept'` albo po zdarzeniu `mnsk7-cookie-consent` z `detail === 'accept'`. Przed wyborem lub przy „Tylko niezbędne” tych skryptów nie uruchamiać.
2. **Polityka prywatności:** w sekcji „Pliki cookie” opisać, które cookie są niezbędne (sesja, koszyk, bezpieczeństwo), a które opcjonalne (analityka, reklamy), oraz że wybór zapisujemy w `mnsk7_cookie_consent`.
3. **Opcjonalnie:** strona „Preferencje cookie” (np. /polityka-prywatnosci/#cookies) z możliwością zmiany wyboru (np. przycisk „Zmień ustawienia” czyści `mnsk7_cookie_consent` i przeładowuje stronę, żeby bank pojawił się ponownie).

## Wyłączenie banku tematu (np. przy Cookie Law Info)

Gdy używany jest zewnętrzny plugin do zgód (np. Cookie Law Info), bank tematu można wyłączyć filtrem:

```php
add_filter( 'mnsk7_show_cookie_bar', '__return_false' );
```

Wtedy to plugin odpowiada za RODO; w temacie pozostaje tylko stylowanie strony po zgodzie, jeśli plugin tego wymaga.

## Podsumowanie

- Bank w temacie **nie jest** tylko kosmetyczny: zapisuje wyraźny wybór (accept/reject), udostępnia go w JS i umożliwia zgodne z RODO warunkowe ładowanie skryptów.
- **Pełna „systemka”** = ten bank + Polityka prywatności z opisem cookie + ładowanie analityki/reklam tylko przy `mnsk7CookieConsent === 'accept'` (w theme/mu-plugin/plugin).
