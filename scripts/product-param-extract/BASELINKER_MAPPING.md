# BaseLinker → WooCommerce (prod): mapowanie pól

Na produkcji źródłem prawdy jest BaseLinker; żeby nie rozjechać się ze stagingiem i motywem, warto ustawić mapowanie eksportu na te same **globalne atrybuty Woo** (`pa_*`), co na staging.

## Wymiary (priorytet)

| Logika (PL) | Slug atrybutu Woo (bez `pa_`) | Uwagi |
|-------------|----------------------------------|-------|
| Średnica części roboczej / D / Ø | `srednica` | Termy typu `3.15 mm` |
| Trzpień / SHK / średnica trzpienia | `srednica-trzpienia` lub `fi` | Motyw grupuje filtry — wystarczy jeden spójny slug w całym katalogu |
| Długość robocza / H | `dlugosc-robocza-h` (lub `dlugosc-robocza`) | |
| Długość całkowita / L | `dlugosc-calkowita-l` (lub `dlugosc-calkowita`) | |
| Skok gwintu / TPI (mm) | `skok-gwintu` | Utwórz atrybut w WC jeśli brak; importer musi wysyłać **mm** jeśli tak jest w opisie |

## Pełna lista „kluczowych parametrów” w PDP

Zgodnie z `mnsk7_get_key_param_attributes()` w [`mu-plugins/inc/product-card.php`](../../mu-plugins/inc/product-card.php): m.in. `material`, `typ-operacji`, `pokrycie`, `liczba-zebow`, `zastosowanie` — warto mapować z pól tekstowych BaseLinker tam, gdzie są stałe słowniki.

## Parser tekstu w repozytorium

Słownik regexów i mapowanie etykiet linii: [`mu-plugins/inc/product-param-extract-mapping.json`](../../mu-plugins/inc/product-param-extract-mapping.json) — ten sam plik jest używany przez skrypt `extract-product-params.php` i przez front (merge w karcie produktu).
