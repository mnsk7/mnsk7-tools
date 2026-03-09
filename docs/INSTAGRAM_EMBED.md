# Instagram na stronie głównej

Shortcode `[mnsk7_instagram_feed limit="6" title="Instagram @mnsk7tools"]` jest zdefiniowany w **mu-plugins/inc/shortcodes.php** (ładowany przez mnsk7-tools.php).

## Jak wyświetlić linki do postów

Shortcode pokazuje siatkę linków do postów Instagram, jeśli w opcjach WP są zapisane URL-e.

**Dodanie linków (np. w wp-admin lub przez WP-CLI):**

```php
// W functions.php theme lub w „Fragmenty kodu” (WPCode):
update_option( 'mnsk7_instagram_post_urls', array(
  'https://www.instagram.com/p/ABC123/',
  'https://www.instagram.com/p/DEF456/',
  // … do 12 URL-i
) );
```

Albo przez WP-CLI na serwerze:

```bash
wp option patch insert mnsk7_instagram_post_urls 0 "https://www.instagram.com/p/ABC123/"
```

**Dlaczego posty mogą się nie wyświetlać:** (1) Źródła: atrybut `posts="..."`, opcja `mnsk7_instagram_post_urls`, scraping profilu (Instagram często blokuje), wreszcie lista domyślna (3 linki) — więc **zawsze** powinna być widoczna co najmniej siatka 3 kart. (2) Osadzenia: `wp_oembed_get()` dla Instagram często zwraca pusty wynik bez tokenu API; wtedy shortcode pokazuje karty z linkami (ikona IG + „Zobacz post") zamiast embedów — to normalne. Jeśli chcesz własną listę, ustaw opcję (WP-CLI: `wp option update mnsk7_instagram_post_urls '["url1","url2"]' --format=json`).

### Motyw mnsk7-storefront: oficjalny embed (blockquote + embed.js)

W **dziecięcej temacie** shortcode jest nadpisywany: zamiast siatki z mu-plugina wyświetlane są `blockquote.instagram-media` z `data-instgrm-permalink`. Skrypt `https://www.instagram.com/embed.js` jest wstrzykiwany w `wp_footer` i po załadowaniu zamienia blockquote na iframe z postem.

**Jeśli posty dalej się nie pokazują:**

- **`#mnsk7-instagram-embed-js` i `display: none`:** Ten ID ma znacznik `<script>` gdy skrypt był ładowany przez `wp_enqueue_script`. Ustawienie `display: none` na `<script>` **nie blokuje wykonania** skryptu — skrypt i tak się wykonuje. Problemem może być ukrycie **kontenera** z postami (np. przez plugin lazy load). W temacie dodano zapasowe reguły `.mnsk7-section--insta .mnsk7-instagram-feed__posts { display: grid !important; }`, żeby kontener nie był ukrywany.
- **embed.js zablokowany:** Sprawdź w DevTools → Network, czy `embed.js` z instagram.com ładuje się (200). Blokery reklam często blokują skrypty z Instagram/Facebook.
- **Brak iframe po chwili:** Otwórz konsolę (F12) — błędy CORS lub Content Security Policy mogą uniemożliwić wstawienie iframe. Tymczasowo wyłącz optymalizację/łączenie skryptów (np. WP Rocket, LiteSpeed).
- **Fallback:** Pod każdym postem jest link „Zobacz post”; jeśli embed się nie załaduje, link i tak prowadzi na Instagram.

Jeśli opcja i scraping są puste, shortcode używa **domyślnych 3 linków** i CTA do profilu.

## Alternatywa: embed z Instagram (iframe)

Na innych stronach (np. alesyatakun.by) można pokazać posty przez **kod osadzenia** z Instagram: w aplikacji Instagram → post → ⋮ → „Osadź” → skopiować HTML z iframe. Wkleić go w treść strony (blok „Własny kod HTML” w Gutenbergu lub w shortcode, który wyświetla HTML). W ten sposób post jest ładowany bezpośrednio przez Instagram (bez oEmbed WP) i często działa bez tokenu API. Shortcode `[mnsk7_instagram_feed]` tego nie robi automatycznie — można dodać atrybut `embed_html="..."` lub osobny blok „Instagram embed” z ręcznie wklejonym kodem z Instagram.

## Parent theme (Storefront) i przewodnik

Komunikat **„The parent theme is missing. Please install the Storefront parent theme.”** na np. `https://staging.mnsk7-tools.pl/przewodnik/` oznacza, że na serwerze **nie ma zainstalowanej rodzimej tematy Storefront**.

Zgodnie z zasadami repozytorium: rodzica Storefront należy trzymać w repo (`wp-content/themes/storefront`) i wdrażać razem z dzieckiem. Sprawdź:

1. Czy w repozytorium jest katalog `wp-content/themes/storefront`.
2. Czy workflow deployu (np. `.github/workflows/deploy-staging.yml`) kopiuje też `storefront` na serwer.

Bez Storefront child theme nie ładuje pełnego layoutu i mogą pojawić się „pływające” strony (np. pusta główna treść, tylko sidebar). Rozwiązanie: dołączyć Storefront do deployu lub zainstalować go na stagingu ręcznie.
