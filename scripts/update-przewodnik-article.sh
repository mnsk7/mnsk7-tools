#!/usr/bin/env bash
# Aktualizacja artykułu Przewodnik „Frez diamentowy”: shortcode [mnsk7_guide_products] + meta FAQ.
# Uruchom na serwerze staging (gdzie jest wp-cli): bash scripts/update-przewodnik-article.sh
# Albo: ssh user@host "cd domains/.../staging && bash -s" < scripts/update-przewodnik-article.sh

set -e
# Katalog WordPress (staging) — dostosuj jeśli inny
WP_ROOT="${WP_ROOT:-.}"
if [[ ! -f "$WP_ROOT/wp-config.php" ]]; then
  echo "Błąd: w katalogu nie ma wp-config.php. Uruchom z katalogu WP lub ustaw WP_ROOT."
  exit 1
fi

cd "$WP_ROOT"
echo "Szukam posta 'Frez diamentowy'..."
ID=$(wp post list --post_type=post --title="Frez diamentowy" --field=ID --format=ids 2>/dev/null | tr -d ' ')
if [[ -z "$ID" ]]; then
  # Szukaj po slugzie
  ID=$(wp post list --post_type=post --name="frez-diamentowy" --field=ID --format=ids 2>/dev/null | tr -d ' ')
fi
if [[ -z "$ID" ]]; then
  echo "Nie znaleziono posta. Dostępne posty:"
  wp post list --post_type=post --fields=ID,post_title,post_name --format=table
  exit 1
fi

echo "Znaleziono post ID=$ID"
CONTENT=$(wp post get "$ID" --field=post_content 2>/dev/null)

# Wstawka ze shortcode (jeśli jeszcze nie ma)
SHORTCODE='[mnsk7_guide_products category="frez-diamentowy" title="Frezy diamentowe w ofercie"]'
if [[ "$CONTENT" != *"mnsk7_guide_products"* ]]; then
  echo "Dodaję shortcode do treści..."
  TMPF=$(mktemp)
  trap "rm -f $TMPF" EXIT
  {
    printf '%s\n\n' "$CONTENT"
    echo '<h3>Frezy diamentowe w ofercie</h3>'
    echo '<p>W naszej ofercie znajdziesz frezy diamentowe w różnych typach i średnicach:</p>'
    echo "<p>$SHORTCODE</p>"
  } > "$TMPF"
  wp post update "$ID" --post_content="$(cat "$TMPF")" --allow-root 2>/dev/null || wp post update "$ID" --post_content="$(cat "$TMPF")"
  rm -f "$TMPF"
  trap - EXIT
  echo "Treść zaktualizowana (shortcode dodany)."
else
  echo "Shortcode [mnsk7_guide_products] już jest w treści."
fi

echo "Ustawiam meta FAQ: mnsk7_faq_set=produkt, mnsk7_faq_title=FAQ — frezy diamentowe"
wp post meta update "$ID" mnsk7_faq_set "produkt" --allow-root 2>/dev/null || wp post meta update "$ID" mnsk7_faq_set "produkt"
wp post meta update "$ID" mnsk7_faq_title "FAQ — frezy diamentowe" --allow-root 2>/dev/null || wp post meta update "$ID" mnsk7_faq_title "FAQ — frezy diamentowe"

echo "Gotowe. Sprawdź: $(wp post get "$ID" --field=url 2>/dev/null || echo 'post URL')"
