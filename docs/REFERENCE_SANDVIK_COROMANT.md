# Референс: Sandvik Coromant (katalog narzędzi)

**Źródło:** [Sandvik Coromant PL](https://www.sandvik.coromant.com/pl-pl) — branża obróbki skrawaniem, katalog narzędzi przemysłowych.  
**Cel:** wzorzec UX dla mnsk7-tools.pl — tabela na kategorii, struktura strony produktu.

---

## Linki (PL)

| Strona | URL |
|--------|-----|
| Strona główna | https://www.sandvik.coromant.com/pl-pl |
| Kategoria (narzędzia tokarskie) | https://www.sandvik.coromant.com/pl-pl/tools/turning-tools |
| Szczegóły produktu | https://www.sandvik.coromant.com/pl-pl/product-details?c=HT06-DDMNL-00130-15C&m=8626955&listName=assortment&listIndex=0 |

---

## Strona główna

- **Nawigacja:** Narzędzia, Wiedza, Usługi, Wsparcie; logowanie, koszyk.
- **Hero:** „Najnowsze narzędzia i rozwiązania do obróbki skrawaniem” + CTA „Zobacz nowe produkty”.
- **Kategorie:** lista kart z chevron — Nowe produkty, Płytki skrawające, Toczenie, Frezowanie, Wiertła, Gwintowanie, Systemy narzędziowe, Silent Tools™, Obróbka cyfrowa, Dostosowane narzędzia, Inne. Każda karta → podstrona kategorii.
- **Dalej:** wiadomości, rozwiązania branżowe, CTA „Utwórz konto”, baza wiedzy, katalogi, usługi, Tool Guide.

**Do adaptacji na mnsk7:** główne kategorie jako kafelki z jasnym CTA; bez przeładowania; szybki dostęp do typu narzędzia (frezy, toczenie itd.).

---

## Strona kategorii (np. Narzędzia tokarskie)

- **H1:** „Wszystkie narzędzia tokarskie” + krótki opis (kontrola wiórów, trwałość, drgania).
- **Podkategorie:** linki w karty — Toczenie zewnętrzne, Toczenie wewnętrzne.
- **Akcje:** „Porównaj”, „Pobierz dane produktu” (wybór produktów → eksport/porównanie).
- **Treść:** lista/tabela produktów (w naszym przypadku docelowo **tabela** z kolumnami: zdjęcie, nazwa/kod, kluczowe parametry, cena, akcja). Na Sandvik tabela/listingu jest głównym elementem po wyborze podkategorii.
- **Baza wiedzy:** artykuły powiązane z kategorią (np. centra tokarskie, toczenie zewnętrzne, frezowanie).

**Do adaptacji na mnsk7:**
- Kategoria = H1 + krótki opis + **tabela produktów** (oprócz lub obok siatki kart).
- Filtry (średnica, trzpień, materiał, typ) nad tabelą; URL params.
- Opcjonalnie: widok tabela vs siatka (grid/table toggle).
- „Porównaj” / „Pobierz” — w backlogu (eksport CSV, porównanie 2–3 produktów).

---

## Strona produktu (product-details)

- **Breadcrumb:** np. Płytka według ISO → Płytka → Narzędzia → Start.
- **Tytuł:** kod produktu jako H1 (np. HT06-DDMNL-00130-15C) + przycisk kopiuj.
- **Podtytuł:** nazwa serii/rodzaju (np. T-Max® P, oprawka tokarska).
- **Akcje:** Zapisz do listy, Porównaj produkt.
- **Wizualizacja:** rysunek poglądowy, „Pokaż model 3D”.
- **Blok zakupu:**
  - Cena katalogowa (np. 144.00 PLN).
  - Status: Dostępny.
  - Liczba sztuk w opakowaniu: 10.
  - Identyfikatory: ISO, Material Id, EAN, ANSI (z przyciskami kopiuj).
  - Ilość (stepper) + **Dodaj do koszyka**.
- **Wartości początkowe (parametry skrawania):** tabela wg materiału (np. ap, fn, vc) — „Wartości początkowe (KAPR 95 deg)” z wierszami dla różnych twardości/ materiałów.
- **Ilustracje techniczne.**
- **Dane produktu:** duża tabela specyfikacji (Metryczne/Calowe):
  - np. Średnica otworu (D1), Wielkość i kształt (CN1906), Liczba krawędzi (2), Średnica okręgu (IC), Kształt płytki (Rhombic 80), Promień naroża (RE), Gatunek, Pokrycie, itd.

**Do adaptacji na mnsk7:**
- **Kod / krótka nazwa** jako główny tytuł; seria/typ jako podtytuł.
- **Jedna tabela „Dane produktu”** (key specs) — średnica, trzpień, liczba ostrzy, materiał, pokrycie, długość itd. — bez rozrzucania po wielu sekcjach.
- Cena, dostępność, ilość w opakowaniu, **jedno wyraźne CTA „Dodaj do koszyka”**.
- Opcjonalnie: „Parametry skrawania” (prędkości, posuwy) jako druga tabela, jeśli mamy dane.
- Brak długiego opisu na górze — najpierw dane, potem ewentualnie opis/zastosowanie.

---

## Zasady do DESIGN_CONTRACT / UI

1. **Kategoria:** tabela produktów z kolumnami (obraz, nazwa/kod, key spec line, cena, akcja); filtry w URL; H1 + krótki opis.
2. **Produkt:** kolejność — obraz → tytuł (kod) → cena → dostępność → key specs (tabela) → warianty (jeśli są) → CTA → trust → opis/dane dodatkowe.
3. **Key specs:** jedna czytelna tabela (2 kolumny: parametr | wartość), możliwość Metryczne/Calowe jeśli dotyczy.
4. **Nawigacja:** kategorie narzędzi na głównej jako kafelki; w kategorii — podkategorie lub filtry, potem tabela.

---

## Powiązane dokumenty

- [DESIGN_CONTRACT_CNC.md](DESIGN_CONTRACT_CNC.md) — sztywna kolejność bloków PDP i karty.
- [UI_SPEC.md](UI_SPEC.md) — header, footer, główna, karta produktu.
- [IMPLEMENTATION_BLUEPRINT_CNC.md](IMPLEMENTATION_BLUEPRINT_CNC.md) — filtry, catalog core, widok tabeli (v2).
