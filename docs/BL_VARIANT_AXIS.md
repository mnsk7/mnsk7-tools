# BaseLinker: oś wariantów (MNSK7) vs parametry produktu

**Cel:** rozdzielić metadane grupowania SKU w BaseLinker od atrybutów widocznych na PDP.

## Pola wewnętrzne (nie mapować do Woo `pa_*`)

| Klucz BL | Przykład | Znaczenie |
|----------|----------|-----------|
| `MNSK7 grupa wariantu` | `mnsk7-offer-13` | Id grupy powiązanych SKU (linked products, nie variable product) |
| `MNSK7 os wariantu` | `model`, `srednica`, `srednica-trzpienia` | **Typ osi** — który atrybut (lub tryb `model`) steruje przełączaniem między SKU |

Te klucze trafiają do `unknown_features` w [baselinker_sync_products.py](../scripts/baselinker_sync_products.py) i zapisują meta Woo:

- `_mnsk7_bl_variant_group`
- `_mnsk7_bl_variant_axis`

**Nie są** eksportowane jako parametry w bloku „Kluczowe parametry”.

## Wartość `model` na osi

`MNSK7 os wariantu = model` **nie oznacza** „model narzędzia” ani numeru katalogowego.

Oznacza: **każdy SKU w grupie to osobny produkt**; użytkownik wybiera wariant przez **jeden blok „Wariant”** (chips / visual links), a nie przez chips przy każdym parametrze (średnica, trzpień, długość).

Implementacja PDP: [mu-plugins/inc/product-card.php](../mu-plugins/inc/product-card.php) — `mnsk7_is_model_variant_axis()`, `mnsk7_get_model_variant_links()`.

## Oś atrybutowa (np. `srednica`)

Gdy `MNSK7 os wariantu = srednica` (lub lista slugów):

- Chipsy pojawiają się **tylko** przy wskazanym atrybucie.
- Pozostałe parametry to wartości statyczne.

## Offer 13 (H0300901–H0800901)

- Grupa: `mnsk7-offer-13`
- Oś: `model`
- Etykiety wariantów na PDP: skrót z wymiarów (`fi X mm | SHK | L`) lub segmenty z tytułu — nie surowe `model` z BL.

## Skrypty utrzymania

- Ustawienie grupy/osi w BL: [apply_raw_offer_groups_wpcli.py](../scripts/apply_raw_offer_groups_wpcli.py)
- Sync atrybutów grupy BL → Woo: [sync_grouped_offer_attrs_from_bl.py](../scripts/sync_grouped_offer_attrs_from_bl.py)
- Push parametrów z parsera tytułu: [push_title_params_to_baselinker.py](../scripts/push_title_params_to_baselinker.py)
