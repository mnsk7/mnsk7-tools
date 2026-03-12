# PDP: Stock availability placement — handoff

## Zadanie
- **PDP:** stock (✓ X w magazynie) **obok** przycisku „Dodaj do koszyka”, **po lewej** od CTA; jeden compact inline blok, nie osobny badge nad ceną.
- **Mobile sticky CTA:** availability **wewnątrz** sticky CTA (cena + stock + przycisk); bez łamania layoutu.

## Zmienione pliki

| Plik | Zmiana |
|------|--------|
| `mu-plugins/inc/product-card.php` | `mnsk7_single_product_availability()` — nie renderuje już bloku (pusty). Nowa `mnsk7_single_product_availability_inline()` na `woocommerce_before_add_to_cart_button` (priority 5): output `<span class="mnsk7-product-availability mnsk7-product-availability--inline ...">✓ text</span>`. |
| `wp-content/themes/mnsk7-storefront/assets/css/parts/06-single-product.css` | Usunięto order 19 dla `.mnsk7-product-availability-row`. Dodano style dla `.mnsk7-product-availability--inline` (compact, status, nie CTA) oraz `.woocommerce-variation-add-to-cart` (flex, gap — quantity + availability + button w jednym rzędzie). |
| `wp-content/themes/mnsk7-storefront/assets/css/main.css` | Zbudowany ponownie z parts (`scripts/build-main-css.sh`). |
| `woocommerce/content-single-product.php` | **Bez zmian** — sticky CTA już zawiera `.mnsk7-pdp-sticky-cta__stock` w `__left`. |

## Gdzie teraz renderuje się availability

1. **PDP (desktop/tablet/mobile):** wewnątrz `form.cart`, **przed** przyciskiem „Dodaj do koszyka” (hook `woocommerce_before_add_to_cart_button`). Kolejność w rzędzie: quantity → **availability** → button. Dla produktu z wariacjami: wewnątrz `.woocommerce-variation-add-to-cart` — to samo.
2. **Mobile sticky CTA:** w `.mnsk7-pdp-sticky-cta__left` obok ceny (`.mnsk7-pdp-sticky-cta__stock`) — bez zmian od poprzedniej wersji.

## Wymagania / acceptance criteria

- [x] Na PDP stock stoi po lewej od „Dodaj do koszyka” (w tym samym rzędzie co quantity + button).
- [x] Stock nie jest osobnym badge’em nad ceną (stary blok na priority 8 nie renderuje treści).
- [x] W mobile sticky CTA availability jest widoczne (w __left).
- [x] Layout CTA nie łamie się (flex, gap).
- [x] Jeden główny stock przy CTA + jeden w sticky; brak duplikatu w tej samej strefie.

## Do zrobienia po deployu

- Zrzut ekranu: PDP (simple lub variable) — stock **na lewo** od „Dodaj do koszyka”.
- Zrzut ekranu: mobile sticky CTA z widocznym availability.
- Commit hash i potwierdzenie push — poniżej.
