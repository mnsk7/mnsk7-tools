# As-is staging: top-5 + 3 defekty SCREEN_REVIEW — naprawione

Data: 2026-03-05. Staging: https://staging.mnsk7-tools.pl/

## Top-5 problemów

1. **Duplikat CTA Allegro** — dwa linki w sekcji trust. Naprawa: shortcode atrybut `allegro_link="0"`, jeden przycisk.
2. **Pusty tytuł dokumentu (front page)** — naprawa: filtr `document_title_parts` w theme, fallback tytuł.
3. **Tytuł karty produktu >2 linii** — naprawa: `-webkit-line-clamp: 2` w `05-plp-cards.css`.
4. **Ukrywanie duplikatu tylko przez CSS** — naprawa: shortcode nie renderuje linku gdy `allegro_link="0"`.
5. **Spójność .col-full i layoutu** — już w `02-reset-typography.css`.

## 3 defekty SCREEN_REVIEW

1. Home: duplikat CTA Allegro — shortcode `allegro_link="0"`, wywołanie w front-page.
2. Home: pusty `<title>` — `document_title_parts` w functions.php.
3. PLP/Home: tytuł karty bez limitu 2 linii — line-clamp w 05-plp-cards.css.

## Zmienione pliki

- mu-plugins/inc/shortcodes.php (allegro_link, show w pages_html)
- wp-content/themes/mnsk7-storefront/front-page.php (shortcode z allegro_link="0")
- wp-content/themes/mnsk7-storefront/functions.php (document_title_parts)
- wp-content/themes/mnsk7-storefront/assets/css/parts/05-plp-cards.css (line-clamp)
