# BaseLinker filters v1

Issue: #27

## Cel

Dodać pierwszy bezpieczny etap filtrowania produktów jak na Allegro:

- szybkie grupy filtrów nad listą produktów;
- opcje widoczne tylko wtedy, gdy istnieją w aktualnym zakresie produktów;
- po kliknięciu filtrów lista produktów zawęża się przez `tax_query`;
- aktywne filtry można usuwać pojedynczo albo wszystkie naraz;
- rozwiązanie jest przygotowane pod dane z BaseLinkera, ale nie robi automatycznego importu w requestach użytkownika.

## Co robi obecny PR

Dodaje mu-plugin:

`wp-content/mu-plugins/mnsk7-baselinker-filters.php`

Plugin:

1. Definiuje mapę filtrów:
   - `Materiał`
   - `Średnica trzpienia`
   - `Średnica robocza`
   - `Długość robocza`
   - `Długość całkowita`
   - `Typ narzędzia`
   - `Liczba ostrzy`

2. Szuka istniejących WooCommerce attribute taxonomies, np.:
   - `pa_material`
   - `pa_srednica-trzpienia`
   - `pa_srednica`
   - `pa_dlugosc-robocza`
   - `pa_typ-frezu`

3. Renderuje osobny blok `Filtry techniczne` na PLP.

4. Używa własnych GET-parametrów:
   - `blf_material`
   - `blf_trzpien`
   - `blf_srednica`
   - `blf_dlugosc_robocza`
   - `blf_dlugosc_calkowita`
   - `blf_typ`
   - `blf_ostrza`

5. Nakłada filtry przez `woocommerce_product_query`.

6. Buduje facet options z aktualnego zakresu produktów, więc np. po wyborze kategorii lub materiału inne opcje zawężają się do tego, co realnie jest dostępne.

## Dlaczego bez bezpośredniego API w pierwszym PR

Nie należy wołać BaseLinker API w czasie normalnego wejścia użytkownika na kategorię, bo:

- PLP musi być szybki;
- API token nie powinien być częścią kodu ani requestu frontowego;
- BaseLinker nie powinien być runtime dependency dla renderowania sklepu;
- dane filtrów powinny być lokalnie w WooCommerce jako atrybuty/taksonomie.

Prawidłowa architektura:

`BaseLinker features -> sync job -> WooCommerce attributes -> PLP filters`

## Następny PR: sync BaseLinker -> Woo attributes

Do zrobienia osobno:

1. Dodać bezpieczny klient API używany tylko w adminie/WP-CLI/cron.
2. Pobrać listę produktów z BaseLinker po `inventory_id`.
3. Pobrać szczegółowe dane produktów batchami.
4. Wziąć `text_fields.features`.
5. Dopasować produkt WooCommerce po SKU.
6. Utworzyć brakujące atrybuty/termy.
7. Przypisać termy do produktu.
8. Zapisać meta:
   - `_mnsk7_bl_product_id`
   - `_mnsk7_bl_features_synced_at`
   - `_mnsk7_bl_features_raw`
9. Dodać raport: ile produktów zsynchronizowano, ile pominięto, ile nie miało SKU, ile miało nieznane cechy.

## Acceptance criteria dla v1

- Na stronie sklepu/kategorii/tagu pokazuje się blok `Filtry techniczne`, jeżeli istnieją odpowiednie atrybuty WooCommerce.
- Filtry nie pokazują pustych opcji.
- Kliknięcie filtra zmienia URL i zawęża listę produktów.
- Aktywny filtr jest widoczny jako chip.
- Można usunąć pojedynczy filtr.
- Można wyczyścić wszystkie filtry BL.
- Brak filtrów nie psuje obecnego PLP.
- Brak danych BaseLinker nie psuje obecnego PLP.
- Wtyczka nie wywołuje zewnętrznego API na froncie.

## Ryzyka

1. Jeśli atrybuty WooCommerce mają inne slug niż przewidziane, blok może nie pokazać części filtrów.
   Rozwiązanie: rozszerzyć mapę przez filtr `mnsk7_bl_filters_definitions`.

2. Obecny theme ma już własny blok filtrów po `Średnica trzpienia`.
   Ten PR dodaje osobny blok techniczny, więc po deployu trzeba sprawdzić, czy UI nie wygląda jak duplikat. Jeśli wygląda, następny krok: zastąpić stary blok jednym nowym.

3. Jeżeli w WooCommerce nie ma uzupełnionych atrybutów, filtrów będzie mało albo nie będzie wcale.
   To normalne dla v1. Pełna wartość pojawi się po syncu BaseLinker features.
