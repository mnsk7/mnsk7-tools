# Instagram — embed na stronie głównej

Sekcja „Obserwuj nas na Instagramie” używa shortcode’u `[mnsk7_instagram_feed limit="6" title="Instagram @mnsk7tools"]`.

## Dlaczego posty mogą się nie wyświetlać

1. **Shortcode nie jest zarejestrowany** — w repozytorium (theme + mu-plugins) nie ma definicji `mnsk7_instagram_feed`. Prawdopodobnie shortcode pochodzi z zewnętrznego pluginu (np. Social Feed, Instagram Feed). Sprawdź w **Wtyczki** czy jest aktywna wtyczka do Instagrama i czy rejestruje ten shortcode.
2. **API Instagram / token** — od 2020 Meta ogranicza API Instagram; wiele wtyczek wymaga połączenia z kontem Facebook/Instagram i tokena. Bez poprawnej konfiguracji feed będzie pusty.
3. **Błąd PHP/JS** — w konsoli przeglądarki lub w logu PHP sprawdź, czy shortcode nie zwraca błędu.

## Co zrobić

- Zainstalować i skonfigurować wtyczkę do Instagram feed (np. „Instagram Feed” by Smash Balloon, „Social Feed Gallery”) i zmapować shortcode w theme na jej shortcode, **albo**
- W theme zmienić sekcję na zwykły CTA: przycisk „Obserwuj @mnsk7tools” z linkiem do profilu Instagram (obecnie link jest w stopce).

Jeśli chcesz fallback w kodzie: w `front-page.php` można sprawdzić, czy wynik `do_shortcode('[mnsk7_instagram_feed ...]')` jest pusty, i wtedy wyświetlić tylko tytuł + link do profilu.

---

**Header — Baza wiedzy / artykuły:** Link do artykułów (baza wiedzy) dodajesz w **Wygląd → Menu**: dodaj pozycję „Baza wiedzy” lub „Wiedza” z adresem np. `/blog/` lub strony z listą artykułów. Po przejściu na Storefront menu jest zarządzane w WordPressie; jeśli wcześniej było w tech-storefront, sprawdź czy strona bloga/artykułów istnieje i ma ten sam slug.
