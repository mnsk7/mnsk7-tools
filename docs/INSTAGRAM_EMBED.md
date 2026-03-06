# Instagram na stronie głównej

Shortcode `[mnsk7_instagram_feed limit="6" title="Instagram @mnsk7tools"]` jest zdefiniowany w **mu-plugins/mnsk7-tools.php**.

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

Jeśli opcja `mnsk7_instagram_post_urls` jest pusta, shortcode i tak wyświetla przycisk CTA „Instagram @mnsk7tools →” do profilu.

## Parent theme (Storefront) i przewodnik

Komunikat **„The parent theme is missing. Please install the Storefront parent theme.”** na np. `https://staging.mnsk7-tools.pl/przewodnik/` oznacza, że na serwerze **nie ma zainstalowanej rodzimej tematy Storefront**.

Zgodnie z zasadami repozytorium: rodzica Storefront należy trzymać w repo (`wp-content/themes/storefront`) i wdrażać razem z dzieckiem. Sprawdź:

1. Czy w repozytorium jest katalog `wp-content/themes/storefront`.
2. Czy workflow deployu (np. `.github/workflows/deploy-staging.yml`) kopiuje też `storefront` na serwer.

Bez Storefront child theme nie ładuje pełnego layoutu i mogą pojawić się „pływające” strony (np. pusta główna treść, tylko sidebar). Rozwiązanie: dołączyć Storefront do deployu lub zainstalować go na stagingu ręcznie.
