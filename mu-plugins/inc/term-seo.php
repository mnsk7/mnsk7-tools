<?php
/**
 * Versioned SEO content for selected WooCommerce product categories.
 *
 * The migration stores copy in the category description and term meta. Runtime
 * templates only read term data; prices, stock, SKUs and products stay owned by
 * WooCommerce.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_get_term_seo_profiles' ) ) {
	/**
	 * Returns verified category SEO profiles keyed by product_cat slug.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	function mnsk7_get_term_seo_profiles() {
		return array(
			'frezy-kompresyjne-updown-cut' => array(
				'title' => 'Frezy kompresyjne do drewna CNC — UP&DOWN CUT',
				'meta'  => 'Frezy kompresyjne CNC do sklejki, MDF, HDF i płyt laminowanych. Dobierz średnicę i trzpień, sprawdź dostępność oraz zamów online.',
				'intro' => '<p>Frezy kompresyjne UP&amp;DOWN CUT łączą spiralę wznoszącą i opadającą, dzięki czemu ograniczają wyrywanie obu powierzchni płyty. Stosuje się je przede wszystkim do pełnego cięcia sklejki, MDF, HDF oraz płyt laminowanych na frezarkach CNC. Przy wyborze sprawdź średnicę roboczą, średnicę trzpienia, długość części roboczej i liczbę ostrzy. Geometria kompresyjna działa prawidłowo dopiero wtedy, gdy punkt zmiany kierunku spirali znajduje się wewnątrz materiału.</p>',
				'after' => '<h2>Jak dobrać frez kompresyjny do płyty</h2><p>Najpierw dopasuj trzpień do tulei zaciskowej i dopuszczalnego zakresu wrzeciona. Następnie wybierz średnicę roboczą odpowiednią do promienia narożników oraz sztywności maszyny. Mniejsza średnica pozwala wykonywać drobniejsze kontury, ale jest bardziej wrażliwa na bicie, zbyt agresywny posuw i niewłaściwe mocowanie materiału. Długość części roboczej powinna wystarczać do przejścia przez płytę z niewielkim zapasem; nie warto wybierać znacznie dłuższego narzędzia bez potrzeby, ponieważ zmniejsza to sztywność zestawu.</p><p>Kluczowa jest głębokość pierwszego przejścia. Dolny fragment spirali ciągnie wiór w jednym kierunku, a górny w przeciwnym. Jeśli strefa kompresji pozostanie nad powierzchnią płyty, górna krawędź może nadal się strzępić. Ustawienie powinno więc uwzględniać grubość materiału, długość dolnego nacięcia i możliwości maszyny. Przy niepewnych parametrach wykonaj próbę na odpadzie tego samego materiału.</p><h2>Materiał, mocowanie i odprowadzanie wióra</h2><p>Frezy kompresyjne są szczególnie przydatne przy sklejce i płytach laminowanych, gdzie ważna jest czysta krawędź po obu stronach. W MDF i HDF trzeba zadbać o skuteczny odciąg, ponieważ drobny pył podnosi temperaturę i przyspiesza zużycie ostrza. Materiał musi leżeć płasko i być stabilnie zamocowany. Nawet prawidłowo dobrana geometria nie skompensuje drgań płyty, zabrudzonej tulei ani nadmiernego bicia.</p><p>Prędkość obrotową, posuw i zagłębienie dobieraj do konkretnego modelu, średnicy oraz maszyny. Obserwuj jakość wióra, dźwięk pracy i temperaturę narzędzia. Przypalenia zwykle wskazują na zbyt mały posuw, tępe ostrze albo problemy z usuwaniem wióra, natomiast wyrwania mogą wynikać z nieprawidłowej głębokości wejścia lub niewystarczającego podparcia płyty.</p><h2>Kontrola efektu i trwałość narzędzia</h2><p>Po próbnym przejściu oceń osobno górną krawędź, dolną krawędź i powierzchnię boczną. Odpryski tylko po jednej stronie pomagają rozpoznać, czy problem dotyczy położenia strefy kompresji, podparcia materiału czy kierunku wejścia. Sprawdź również wymiar gotowego elementu: ugięcie cienkiego frezu może dawać pozornie czystą, lecz niedokładną krawędź.</p><p>Przed każdą serią oczyść tuleję i trzpień, a narzędzie obejrzyj pod dobrym światłem. Osad z żywicy lub kleju pogarsza odprowadzanie ciepła. Nie próbuj kompensować stępionego ostrza samym zwiększaniem obrotów. Zapisuj ustawienia udanych prób razem z nazwą materiału, jego grubością i sposobem mocowania; ułatwi to powtarzalną produkcję bez zgadywania przy kolejnym zleceniu.</p>',
				'faq'   => array(
					array( 'q' => 'Do czego służy frez kompresyjny?', 'a' => 'Do cięcia materiałów płytowych z ograniczeniem odprysków na górnej i dolnej powierzchni, szczególnie sklejki oraz płyt laminowanych.' ),
					array( 'q' => 'Czy frez kompresyjny nadaje się do MDF?', 'a' => 'Tak. Trzeba jednak zapewnić skuteczny odciąg pyłu i dobrać parametry do średnicy frezu oraz sztywności maszyny.' ),
					array( 'q' => 'Dlaczego górna krawędź nadal się strzępi?', 'a' => 'Najczęstszą przyczyną jest zbyt płytkie wejście, przez które strefa zmiany kierunku spirali nie znajduje się wewnątrz materiału.' ),
				),
			),
			'frezy-diamentowe' => array(
				'title' => 'Frezy diamentowe do granitu i kamienia CNC — MNSK7',
				'meta'  => 'Frezy diamentowe do granitu, marmuru i kamienia: proste oraz stożkowo-kulowe. Porównaj średnicę, promień, trzpień i dostępność.',
				'intro' => '<p>Frezy diamentowe do kamienia są przeznaczone do obróbki materiałów mineralnych, między innymi granitu i marmuru. W tej kategorii znajdziesz modele proste oraz stożkowo-kulowe do detali 3D, napisów, ornamentów i powierzchni krzywoliniowych. Dobierając narzędzie, porównaj geometrię, promień końcówki, średnicę roboczą, długość części roboczej, trzpień oraz całkowity wysięg. Parametry pracy muszą odpowiadać materiałowi, chłodzeniu i sztywności maszyny CNC.</p>',
				'after' => '<h2>Jaki frez diamentowy wybrać do kamienia</h2><p>O wyborze decyduje przede wszystkim geometria detalu. Mały promień końcówki pozwala odwzorować drobniejsze litery i ornamenty, lecz jest bardziej podatny na przeciążenie. Większy promień jest odpowiedni do łagodniejszych powierzchni i może efektywniej zbierać materiał, jeżeli maszyna oraz strategia obróbki na to pozwalają. Sprawdź też średnicę trzpienia i maksymalny wysięg: długie narzędzie ułatwia dostęp do głębokiego reliefu, ale wymaga ograniczenia obciążenia i bardzo dobrego mocowania.</p><p>Granitu, marmuru i innych kamieni nie należy traktować identycznie. Różnią się twardością, kruchością i zachowaniem podczas skrawania. Bezpiecznym punktem wyjścia jest strategia zgrubna pozostawiająca niewielki naddatek, a następnie przejście wykańczające narzędziem dopasowanym do oczekiwanej szczegółowości. Parametry zawsze weryfikuj próbą na tym samym materiale.</p><h2>Chłodzenie i stabilność obróbki</h2><p>Podczas obróbki kamienia szczególnie ważne jest odprowadzanie ciepła i urobku. Zastosuj sposób chłodzenia przewidziany dla narzędzia, obrabiarki i materiału oraz zabezpiecz strefę pracy przed pyłem lub rozbryzgiem. Nie uruchamiaj obróbki na podstawie uniwersalnego zestawu obrotów i posuwu: wartości zależą od średnicy, promienia, głębokości przejścia, mocowania i konkretnego kamienia.</p><p>Przed rozpoczęciem sprawdź bicie trzpienia, stan tulei oraz sztywność zamocowania elementu. Drgania pogarszają powierzchnię i zwiększają ryzyko uszkodzenia drobnej końcówki. Przy wykańczaniu reliefu warto utrzymywać równomierne obciążenie i unikać gwałtownych zmian kierunku. Aktualne ceny i stany magazynowe są prezentowane bezpośrednio przy produktach w kategorii.</p><h2>Obróbka zgrubna i wykańczająca</h2><p>Nie używaj delikatnej końcówki do usuwania całego naddatku, jeżeli detal można wcześniej przygotować mocniejszym narzędziem. Rozdzielenie etapów skraca czas pracy frezu wykańczającego i ogranicza ryzyko przeciążenia. Naddatek po obróbce zgrubnej powinien być równomierny, aby końcówka nie trafiała naprzemiennie w puste miejsce i pełny materiał.</p><p>Przy reliefach dobierz odstęp ścieżek do promienia końcówki i oczekiwanej jakości powierzchni. Zbyt duży krok pozostawi widoczne grzbiety, natomiast bardzo mały nie zawsze przyniesie proporcjonalną poprawę, a wydłuży program. Po zakończeniu umyj narzędzie zgodnie z zasadami dla użytego chłodziwa, osusz je i skontroluj powierzchnię roboczą. Nie odkładaj frezu luzem razem z innymi narzędziami, ponieważ drobna końcówka może zostać uszkodzona jeszcze przed kolejnym użyciem.</p>',
				'faq'   => array(
					array( 'q' => 'Jaki promień wybrać do drobnego reliefu?', 'a' => 'Mniejszy promień odwzoruje drobniejsze szczegóły, ale wymaga lżejszych parametrów i stabilnej maszyny.' ),
					array( 'q' => 'Czy jednym ustawieniem można obrabiać granit i marmur?', 'a' => 'Nie należy tego zakładać. Materiały różnią się strukturą i twardością, dlatego parametry trzeba sprawdzić próbą.' ),
					array( 'q' => 'Co jest ważniejsze: długość czy sztywność?', 'a' => 'Należy wybrać najkrótszy wysięg, który zapewnia dostęp do detalu. Nadmierna długość zwiększa ugięcie i drgania.' ),
				),
			),
			'fazowniki-i-poglebiacze' => array(
				'title' => 'Fazowniki 45° i 90° do metalu i CNC — MNSK7',
				'meta'  => 'Fazowniki i pogłębiacze 45°/90° do metalu oraz CNC. Porównaj średnice, trzpienie, liczbę ostrzy, zastosowanie i dostępność.',
				'intro' => '<p>Fazowniki i pogłębiacze służą do wykonywania faz, gratowania krawędzi oraz przygotowania gniazd pod łby śrub. W katalogu dostępne są geometrie 45° i 90° przeznaczone do pracy w metalu na maszynach CNC lub w odpowiednich obrabiarkach. Przed zakupem sprawdź kąt, średnicę roboczą, średnicę trzpienia, długość narzędzia, liczbę ostrzy i obsługiwany materiał. Kąt narzędzia należy dopasować do rysunku technicznego, a nie tylko do nazwy operacji.</p>',
				'after' => '<h2>Fazownik 45° czy pogłębiacz 90°</h2><p>Kąt dobiera się do wymaganej geometrii. Narzędzie 90° jest typowym wyborem do wielu gniazd pod stożkowe łby śrub, natomiast 45° stosuje się między innymi do fazowania krawędzi i wybranych operacji przygotowawczych. Zawsze porównaj oznaczenie elementu z dokumentacją wykonawczą. Ta sama nazwa handlowa nie oznacza, że każde narzędzie wykona identyczny profil.</p><p>Średnica robocza określa maksymalny rozmiar fazy lub pogłębienia. Trzpień musi pasować do uchwytu, a całkowita długość powinna zapewniać dostęp bez niepotrzebnego zwiększania wysięgu. Większa liczba ostrzy może poprawić równomierność pracy, ale wymaga odpowiedniego odprowadzania wióra i parametrów dopasowanych do materiału.</p><h2>Jak ograniczyć drgania i ślady na powierzchni</h2><p>Najpierw upewnij się, że detal jest sztywno zamocowany, a tuleja i trzpień są czyste. Wprowadź narzędzie osiowo i unikaj zbyt dużego zagłębienia w jednym przejściu. Drgania, wielokątne ślady lub piszczenie zwykle wskazują na nieprawidłowy posuw, nadmierny wysięg, bicie albo zbyt małą sztywność układu. Przy nowym materiale wykonaj próbę i koryguj parametry stopniowo.</p><p>Do stali, stali nierdzewnej, aluminium i metali kolorowych mogą być potrzebne inne geometrie, powłoki i chłodzenie. Informacja „do metalu” nie zastępuje sprawdzenia konkretnego zastosowania na stronie produktu. Jeżeli narzędzie ma wymienne płytki, porównaj także typ oraz wymiary płytki. Ceny, stany magazynowe i możliwe warianty są pobierane na bieżąco z WooCommerce.</p><h2>Fazowanie krawędzi i pogłębianie otworów</h2><p>Przy fazowaniu zewnętrznej krawędzi zaplanuj stabilny najazd i stałe zaangażowanie ostrza. Przy pogłębianiu otworu szczególnie ważne jest współosiowe prowadzenie. Jeśli otwór wstępny ma zadzior lub nieregularną krawędź, narzędzie może wejść nierówno i pozostawić niesymetryczne gniazdo. W produkcji seryjnej kontroluj średnicę pogłębienia, a nie tylko jego wygląd.</p><p>Ostre narzędzie powinno tworzyć przewidywalny wiór bez nadmiernego nacisku. Po pracy usuń wióry z korpusu i skontroluj krawędzie skrawające. W modelach z wymiennymi płytkami oczyść gniazdo przed montażem nowej płytki i dokręć elementy zgodnie z zaleceniami producenta. Nie mieszaj zużytych i nowych ostrzy w jednym korpusie bez sprawdzenia wysokości, ponieważ nierówny podział obciążenia może pogorszyć powierzchnię i trwałość zestawu.</p>',
				'faq'   => array(
					array( 'q' => 'Jaki kąt wybrać pod łeb śruby?', 'a' => 'Kąt pogłębienia musi odpowiadać geometrii łba podanej w dokumentacji śruby; często jest to 90°, ale nie jest to reguła dla każdego elementu.' ),
					array( 'q' => 'Czy fazowniki nadają się do aluminium?', 'a' => 'Wybrane modele tak. Trzeba sprawdzić zastosowanie konkretnego produktu i dobrać parametry zapewniające dobre odprowadzanie wióra.' ),
					array( 'q' => 'Skąd biorą się drgania podczas fazowania?', 'a' => 'Najczęściej z nadmiernego wysięgu, bicia, słabego mocowania albo niedopasowania obrotów i posuwu.' ),
				),
			),
			'pilniki-obrotowe' => array(
				'title' => 'Pilniki obrotowe do metalu VHM — typy A–L | MNSK7',
				'meta'  => 'Pilniki obrotowe VHM do metalu: typy A, C, D, F, G i L. Dobierz kształt oraz średnicę, sprawdź cenę i aktualną dostępność.',
				'intro' => '<p>Pilniki obrotowe VHM służą do gratowania, kształtowania, wyrównywania spoin i miejscowej obróbki metalu. W kategorii dostępne są różne kształty, między innymi typy A, C, D, F, G i L. Dobierz kształt do powierzchni, promienia i dostępności miejsca, a średnicę części roboczej oraz trzpień do uchwytu i wymaganej kontroli. Przed pracą sprawdź zalecane zastosowanie konkretnego produktu, prędkość narzędzia oraz sposób mocowania detalu.</p>',
				'after' => '<h2>Jak dobrać kształt pilnika obrotowego</h2><p>Kształt części roboczej decyduje o tym, gdzie narzędzie pracuje najwygodniej. Formy walcowe stosuje się do powierzchni i krawędzi, kuliste do promieni oraz zagłębień, a stożkowe i ostrołukowe do miejsc o ograniczonym dostępie. Oznaczenie typu ułatwia porównanie, lecz przed zakupem warto obejrzeć geometrię na zdjęciu produktu i zestawić ją z obrabianym detalem.</p><p>Średnica robocza wpływa na tempo zbierania materiału i możliwość dotarcia do małych promieni. Mniejsze narzędzie daje większą kontrolę w drobnych miejscach, natomiast większe może szybciej obrabiać rozległą powierzchnię. Trzpień musi być wsunięty i zaciśnięty zgodnie z wymaganiami urządzenia. Nadmierny wysięg zwiększa bicie oraz ryzyko uszkodzenia.</p><h2>Bezpieczna praca i jakość powierzchni</h2><p>Detal powinien być unieruchomiony, a operator musi stosować ochronę oczu i środki odpowiednie do powstających wiórów. Pilnika obrotowego nie należy dociskać jak ściernicy. Narzędzie powinno skrawać, a nie trzeć w jednym miejscu. Zbyt duży nacisk podnosi temperaturę, pogarsza kontrolę i może powodować wykruszanie ostrzy.</p><p>Jeśli pilnik podskakuje, zostawia głębokie bruzdy albo szybko się nagrzewa, sprawdź bicie, kierunek prowadzenia, obroty i stan ostrzy. Zabrudzenie wiórem może wymagać korekty parametrów lub doboru geometrii odpowiedniej do konkretnego metalu. Do obróbki narożników prowadź narzędzie płynnie i nie blokuj go w szczelinie.</p><p>Typ, średnica, cena i dostępność każdego wariantu są prezentowane przy karcie produktu. Oferta może się zmieniać, dlatego ostateczny wybór opieraj na aktualnych danych produktu, a nie wyłącznie na ogólnym opisie kategorii.</p><h2>Prowadzenie i konserwacja</h2><p>Pracuj ruchem płynnym, wykorzystując tylko tę część głowicy, która odpowiada obrabianej geometrii. Nie podważaj narzędzia bokiem i nie dopuszczaj do gwałtownego zakleszczenia. Przy usuwaniu spoiny lepiej wykonać kilka kontrolowanych przejść niż próbować zebrać cały nadmiar jednym ruchem. Końcowe przejście z mniejszym obciążeniem ułatwia uzyskanie równomiernej powierzchni.</p><p>Po pracy usuń luźne wióry metodą bezpieczną dla VHM i sprawdź, czy uzębienie nie jest wykruszone. Narzędzia przechowuj oddzielnie, aby głowice nie uderzały o siebie. Jeżeli pilnik był używany w materiale powodującym zalepianie, nie stosuj przypadkowych agresywnych środków czyszczących. Regularna kontrola trzpienia i uchwytu jest równie ważna jak stan samej części skrawającej, ponieważ nawet dobre ostrze nie pracuje prawidłowo przy nadmiernym biciu.</p>',
				'faq'   => array(
					array( 'q' => 'Jaki kształt wybrać do zaokrąglonego zagłębienia?', 'a' => 'Najczęściej sprawdzi się kształt kulisty lub zaokrąglony, ale promień narzędzia trzeba dopasować do detalu.' ),
					array( 'q' => 'Czy pilnik obrotowy należy mocno dociskać?', 'a' => 'Nie. Nadmierny nacisk zwiększa temperaturę i pogarsza kontrolę; narzędzie powinno swobodnie skrawać.' ),
					array( 'q' => 'Co oznaczają typy A, C, D, F, G i L?', 'a' => 'Są to oznaczenia kształtów części roboczej. Konkretną geometrię i wymiary należy sprawdzić na stronie wariantu.' ),
				),
			),
			'frezy-do-planowania' => array(
				'title' => 'Frezy do planowania drewna i slabów — MNSK7',
				'meta'  => 'Frezy do planowania drewna i wyrównywania slabów z wymiennymi płytkami. Porównaj średnicę, trzpień, liczbę ostrzy i dostępność.',
				'intro' => '<p>Frezy do planowania służą do wyrównywania drewna, blatów i slabów na frezarkach CNC. Modele z wymiennymi płytkami pozwalają obrabiać szeroką ścieżkę bez wymiany całego korpusu po zużyciu ostrza. Wybór zacznij od średnicy trzpienia obsługiwanej przez tuleję, a następnie porównaj średnicę roboczą, liczbę ostrzy, maksymalny wysięg i możliwości wrzeciona. Im większa średnica, tym większe wymagania wobec sztywności maszyny i prawidłowego ustawienia osi.</p>',
				'after' => '<h2>Jak wybrać frez do planowania drewna</h2><p>Najpierw sprawdź średnicę tulei i dopuszczalny rozmiar narzędzia dla wrzeciona. W aktualnym katalogu występują warianty z różnymi trzpieniami, średnicami roboczymi i liczbą ostrzy, dlatego porównuj pełną specyfikację produktu. Większy korpus obejmuje szerszą ścieżkę, ale zwiększa obciążenie. Do lżejszej lub mniej sztywnej maszyny rozsądniejszy może być mniejszy frez oraz płytsze przejścia.</p><p>Wysięg ustaw możliwie krótko, zachowując bezpieczny prześwit nad mocowaniami. Przed obróbką sprawdź dokręcenie płytek i ich prawidłowe osadzenie. Wszystkie ostrza muszą znajdować się na tej samej wysokości; zabrudzone gniazdo albo uszkodzona płytka pozostawi ślady na powierzchni.</p><h2>Planowanie bez progów i przypaleń</h2><p>Regularne progi pomiędzy ścieżkami często oznaczają nieprawidłowy tram, czyli brak prostopadłości osi wrzeciona do stołu. Przed dokładnym wykończeniem wyrównaj maszynę i wykonaj próbne przejście. Ustaw zakładkę ścieżek tak, aby narzędzie pracowało równomiernie. Głębokie zbieranie w jednym przejściu zwiększa obciążenie, drgania i ryzyko wyrwania włókien.</p><p>Przypalenia mogą wynikać z tępych płytek, zbyt małego posuwu, nadmiernych obrotów lub wielokrotnego tarcia w tym samym miejscu. Skuteczny odciąg usuwa wióry i pył, szczególnie podczas obróbki MDF. Parametry startowe zawsze dopasuj do konkretnego korpusu, materiału i obrabiarki, a następnie koryguj je na podstawie próby.</p><p>Przed uruchomieniem programu sprawdź wysokość wszystkich uchwytów i śrub mocujących slab. Zaplanuj bezpieczne najazdy oraz wyjazdy poza detal. Aktualne ceny, zapasy i warianty są pobierane bezpośrednio z WooCommerce, więc lista produktów pozostaje zgodna z bieżącym katalogiem.</p><h2>Płytki wymienne i powtarzalność</h2><p>Zużycie jednej płytki może powodować smugi, zwiększony hałas i nierównomierne obciążenie korpusu. Oglądaj wszystkie krawędzie przed rozpoczęciem pracy. Jeśli płytka jest wielostronna, obracaj ją zgodnie z kolejnością i zapisuj wykorzystane krawędzie. Gniazdo musi być czyste; drobny wiór pod płytką zmienia jej wysokość i może od razu pozostawić stopień na drewnie.</p><p>Po ustawieniu maszyny wykonaj przejście testowe na małej głębokości. Zmierz różnicę wysokości między sąsiednimi ścieżkami i skontroluj powierzchnię pod światło. Dopiero po tej kontroli uruchom planowanie całego slabu. Dla powtarzalnych prac zapisz wysięg, zakładkę, głębokość, obroty i posuw razem z oznaczeniem płytek. Dzięki temu kolejne ustawienie opiera się na sprawdzonym procesie, a nie na samej średnicy frezu.</p>',
				'faq'   => array(
					array( 'q' => 'Jaka średnica frezu do planowania będzie najlepsza?', 'a' => 'Największa nie zawsze jest najlepsza. Średnicę dobierz do trzpienia, mocy wrzeciona, sztywności maszyny i szerokości obrabianej powierzchni.' ),
					array( 'q' => 'Dlaczego po planowaniu zostają regularne progi?', 'a' => 'Najczęściej przyczyną jest nieprawidłowe ustawienie osi wrzeciona względem stołu, czyli błąd tram.' ),
					array( 'q' => 'Czy frez do planowania nadaje się do MDF?', 'a' => 'Wybrane modele tak. Należy sprawdzić zastosowanie produktu, ograniczyć pylenie skutecznym odciągiem i wykonać próbę parametrów.' ),
				),
			),
		);
	}
}

if ( ! function_exists( 'mnsk7_get_product_tag_seo_profiles' ) ) {
	/** Curated, controlled material facets backed by the live WooCommerce catalog. */
	function mnsk7_get_product_tag_seo_profiles() {
		return array(
			'aluminium' => array(
				'title' => 'Frezy do aluminium CNC — dobór narzędzi | MNSK7',
				'meta'  => 'Frezy i narzędzia do aluminium CNC. Porównaj geometrię, średnicę, trzpień, liczbę ostrzy oraz aktualną dostępność w MNSK7.',
				'intro' => '<p>Ta kategoria zbiera frezy i narzędzia oznaczone w aktualnym katalogu WooCommerce jako odpowiednie do obróbki aluminium. Przy wyborze porównaj geometrię ostrza, średnicę roboczą, trzpień, długość części roboczej oraz liczbę ostrzy. Aluminium wymaga skutecznego odprowadzania wióra, dlatego znaczenie mają także przestrzeń między ostrzami, stan krawędzi skrawającej i sposób chłodzenia. Zastosowanie zawsze potwierdź w specyfikacji konkretnego produktu; lista, ceny, warianty i dostępność poniżej są pobierane bezpośrednio z katalogu sklepu.</p>',
				'after' => '<h2>Jak wybrać frez do aluminium</h2><p>Dobór zacznij od operacji: wycinania konturu, wykonywania kieszeni, rowkowania, fazowania albo wykańczania powierzchni. Następnie sprawdź, czy trzpień pasuje do tulei, a średnica i długość robocza zapewniają wymagany zasięg. Najkrótszy praktyczny wysięg poprawia sztywność układu i ogranicza drgania. Nie wybieraj długiego narzędzia wyłącznie na zapas, ponieważ większe ugięcie może pogorszyć wymiar i powierzchnię.</p><p>Geometria powinna ułatwiać usuwanie wióra z miejsca skrawania. W aluminium wiór może przyklejać się do krawędzi, dlatego tępe ostrze, zbyt mały posuw lub niewłaściwe chłodzenie szybko pogarszają pracę. Liczba ostrzy wpływa na ilość miejsca na wiór oraz wymagany posuw. Ostateczne parametry zależą od maszyny, zamocowania, gatunku aluminium i konkretnego frezu, więc ustawienia startowe trzeba zweryfikować próbą.</p><h2>Stabilne mocowanie i czysta krawędź</h2><p>Przed uruchomieniem programu oczyść trzpień i tuleję, skontroluj bicie oraz pewnie zamocuj detal. Cienkie elementy wymagają podparcia, aby nie wpadały w drgania. Dla przejścia przez materiał zaplanuj podkład lub stół ofiarny i bezpieczny sposób wyjścia narzędzia. Przy wycinaniu małych części pozostaw mostki albo zastosuj inne mocowanie, które nie dopuści do przesunięcia elementu.</p><p>Jeżeli powierzchnia jest poszarpana, wiór się skleja albo narzędzie nadmiernie się nagrzewa, przerwij pracę i sprawdź krawędź, odprowadzanie wióra, wysięg, obroty oraz posuw. Nie kompensuj problemu samym zwiększaniem obrotów. Po obróbce usuń pozostałości materiału metodą bezpieczną dla narzędzia i skontroluj ostrze. Aktualne dane techniczne, warianty, ceny i stany magazynowe są prezentowane przy produktach, dlatego przed zamówieniem porównaj pełną kartę wybranego modelu.</p>',
				'faq'   => array(
					array( 'q' => 'Ile ostrzy powinien mieć frez do aluminium?', 'a' => 'Nie ma jednej liczby dla każdej operacji. Dobór zależy od geometrii, średnicy, odprowadzania wióra, maszyny i parametrów konkretnego produktu.' ),
					array( 'q' => 'Dlaczego aluminium przykleja się do frezu?', 'a' => 'Najczęstsze przyczyny to tępa lub niedopasowana geometria, niewłaściwy posuw, słabe odprowadzanie wióra albo nieodpowiednie chłodzenie.' ),
					array( 'q' => 'Czy każdy produkt na tej stronie obrabia każdy gatunek aluminium?', 'a' => 'Nie. Strona jest kontrolowanym wyborem katalogowym, ale zgodność materiałową należy potwierdzić w aktualnej specyfikacji konkretnego produktu.' ),
				),
			),
			'mdf'       => array(
				'title' => 'Frezy do MDF i płyt drewnopochodnych CNC | MNSK7',
				'meta'  => 'Frezy do MDF i płyt drewnopochodnych CNC. Dobierz geometrię, średnicę, trzpień i długość roboczą; sprawdź aktualną ofertę MNSK7.',
				'intro' => '<p>W tej kategorii znajdują się frezy oznaczone w bieżącym katalogu jako przeznaczone do MDF. Narzędzie dobierz do rodzaju operacji, grubości płyty, wymaganej jakości krawędzi oraz możliwości wrzeciona. Sprawdź średnicę i długość części roboczej, trzpień, kierunek wyrzutu wióra i geometrię ostrza. MDF jest materiałem pylącym i ściernym, dlatego ważne są sprawny odciąg, stabilne mocowanie oraz regularna kontrola zużycia. Lista produktów, ceny, warianty i dostępność są generowane dynamicznie przez WooCommerce.</p>',
				'after' => '<h2>Frez do wycinania, rowkowania i obróbki krawędzi MDF</h2><p>Do wycinania konturu potrzebna jest część robocza obejmująca planowaną głębokość, ale nadmierna długość zwiększa ugięcie. Do płyt laminowanych lub fornirowanych istotny jest kierunek sił działających na obie powierzchnie. Geometria kompresyjna może ograniczać wyrywanie górnej i dolnej warstwy, jeżeli strefa zmiany kierunku ostrza zostanie prawidłowo ustawiona względem płyty. Do zwykłego MDF wybór może być inny, dlatego nie należy kierować się wyłącznie nazwą materiału.</p><p>Średnicę dobierz do minimalnego promienia narożników, ilości usuwanego materiału i sztywności maszyny. Mniejszy frez umożliwia wykonanie drobniejszych detali, ale jest bardziej podatny na przeciążenie. Większa średnica może poprawić stabilność i tempo pracy, lecz wymaga odpowiedniego wrzeciona oraz przestrzeni w ścieżce. Trzpień musi dokładnie pasować do czystej, niezużytej tulei.</p><h2>Jak ograniczyć pył, przypalenia i poszarpaną krawędź</h2><p>Skuteczny odciąg chroni strefę skrawania przed ponownym mieleniem pyłu i pomaga kontrolować temperaturę. Przed pracą sprawdź szczotkę odciągu, drożność przewodu i mocowanie płyty. Jeżeli detal unosi się podczas przejścia, nawet dobry frez nie utrzyma wymiaru. Zaplanuj podparcie, podciśnienie, dociski lub mostki odpowiednie do kształtu elementu.</p><p>Przypalenia i ciemne ślady mogą wskazywać na tępe ostrze, zbyt mały posuw, nadmierne obroty albo kilkukrotne tarcie w tym samym miejscu. Poszarpana krawędź może wynikać z luzu, bicia lub niewłaściwego kierunku prowadzenia. Parametry zmieniaj pojedynczo i oceniaj rezultat na próbce tego samego materiału. Po zakończeniu oczyść narzędzie i obejrzyj krawędzie skrawające; MDF potrafi zużywać je stopniowo, zanim pojawi się oczywiste uszkodzenie. Przed zakupem sprawdź aktualną specyfikację i zastosowanie na karcie produktu.</p>',
				'faq'   => array(
					array( 'q' => 'Jaki frez ogranicza wyrywanie laminatu na płycie?', 'a' => 'Często stosuje się geometrię kompresyjną, ale jej strefa pracy musi być dopasowana do grubości płyty i głębokości przejścia.' ),
					array( 'q' => 'Dlaczego frez przypala MDF?', 'a' => 'Może odpowiadać za to zużyte ostrze, zbyt mały posuw, nadmierne obroty, bicie lub ponowne skrawanie pyłu.' ),
					array( 'q' => 'Czy długość robocza powinna być większa od grubości płyty?', 'a' => 'Musi wystarczyć do zaplanowanej operacji i bezpiecznego przejścia, ale zbędny wysięg obniża sztywność układu.' ),
				),
			),
			'stal'      => array(
				'title' => 'Frezy i narzędzia do stali CNC — VHM | MNSK7',
				'meta'  => 'Frezy i narzędzia VHM do obróbki stali. Porównaj geometrię, średnicę, trzpień, powłokę i aktualną dostępność w MNSK7.',
				'intro' => '<p>Ta strona prezentuje narzędzia oznaczone w aktualnym katalogu WooCommerce jako przeznaczone do obróbki stali. Wybór zależy od gatunku i twardości materiału, rodzaju operacji, geometrii ostrza, powłoki, średnicy, trzpienia oraz sztywności obrabiarki. Innych warunków wymaga wykonywanie rowka, innych frezowanie boczne, fazowanie lub gratowanie pilnikiem obrotowym. Przed użyciem potwierdź zastosowanie i parametry na karcie konkretnego produktu. Lista, warianty, ceny i dostępność poniżej są zawsze pobierane z bieżącego katalogu.</p>',
				'after' => '<h2>Dobór narzędzia do gatunku stali i operacji</h2><p>Określenie „do stali” nie opisuje jednego materiału. Stale konstrukcyjne, narzędziowe i nierdzewne różnią się skrawalnością, dlatego zastosowanie trzeba zestawić z dokumentacją produktu. Najpierw ustal operację i wymagany wymiar, a następnie dobierz średnicę, długość roboczą, liczbę ostrzy, geometrię oraz ewentualną powłokę. Przy głębokich kieszeniach i długim wysięgu szczególnie ważne są sztywność oraz sposób odprowadzania wióra.</p><p>Trzpień powinien być czysty i zamocowany możliwie głęboko zgodnie z wymaganiami uchwytu, bez chwytania za część roboczą. Nadmierny wysięg zwiększa ugięcie i może powodować drgania, wykruszanie krawędzi oraz błąd wymiaru. Jeżeli operacja wymaga długiego narzędzia, zmniejsz obciążenie i sprawdź stabilność na przejściu próbnym.</p><h2>Chłodzenie, wiór i kontrola zużycia</h2><p>Sposób chłodzenia musi odpowiadać narzędziu, materiałowi i maszynie. Niewłaściwe podawanie chłodziwa lub jego brak w procesie, który go wymaga, może prowadzić do gwałtownych zmian temperatury i zużycia ostrza. Równie ważne jest usuwanie wióra: ponowne skrawanie materiału pogarsza powierzchnię i zwiększa obciążenie. Nie stosuj uniwersalnych obrotów i posuwu dla wszystkich produktów oznaczonych tym samym tagiem.</p><p>Obserwuj dźwięk, kształt wióra, temperaturę i jakość powierzchni. Narastające drgania, smugi, zadziory albo zmiana wymiaru są sygnałem do zatrzymania procesu i sprawdzenia ostrza, uchwytu oraz parametrów. Pilniki obrotowe i frezy trzpieniowe prowadzi się inaczej, dlatego zalecenia dla jednego typu nie powinny być przenoszone bezpośrednio na drugi. Po pracy oczyść narzędzie, skontroluj krawędzie i przechowuj je tak, aby części robocze nie uderzały o siebie. Aktualną zgodność materiałową, warianty i dane handlowe zawsze potwierdź na stronie produktu.</p>',
				'faq'   => array(
					array( 'q' => 'Czy jedno narzędzie nadaje się do każdej stali?', 'a' => 'Nie. Zgodność zależy między innymi od gatunku, twardości, operacji i geometrii; należy ją potwierdzić w specyfikacji produktu.' ),
					array( 'q' => 'Co najczęściej powoduje drgania podczas frezowania stali?', 'a' => 'Typowe przyczyny to nadmierny wysięg, bicie, słabe mocowanie, przeciążenie ostrza albo niedopasowane obroty i posuw.' ),
					array( 'q' => 'Czy powłoka zawsze jest potrzebna?', 'a' => 'Nie zawsze. Powłokę i geometrię dobiera się do materiału oraz warunków procesu, a nie jako samodzielną cechę gwarantującą wynik.' ),
				),
			),
		);
	}
}

/** Store the curated copy as term data once per content revision. */
add_action( 'init', function () {
	if ( ! taxonomy_exists( 'product_cat' ) ) {
		return;
	}

	$version = '2026-07-22.2';
	foreach ( mnsk7_get_term_seo_profiles() as $slug => $profile ) {
		$term = get_term_by( 'slug', $slug, 'product_cat' );
		if ( ! $term instanceof WP_Term || get_term_meta( $term->term_id, '_mnsk7_term_seo_version', true ) === $version ) {
			continue;
		}

		wp_update_term( $term->term_id, 'product_cat', array( 'description' => wp_kses_post( $profile['intro'] ) ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_after', wp_kses_post( $profile['after'] ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_faq', wp_json_encode( $profile['faq'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_title', sanitize_text_field( $profile['title'] ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_metadesc', sanitize_text_field( $profile['meta'] ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_version', $version );
	}
}, 30 );

/** Store controlled product-tag copy as reversible term data. */
add_action( 'init', function () {
	if ( ! taxonomy_exists( 'product_tag' ) ) {
		return;
	}

	$version = '2026-07-23.1';
	foreach ( mnsk7_get_product_tag_seo_profiles() as $slug => $profile ) {
		$term = get_term_by( 'slug', $slug, 'product_tag' );
		if ( ! $term instanceof WP_Term || get_term_meta( $term->term_id, '_mnsk7_term_seo_version', true ) === $version ) {
			continue;
		}
		if ( get_term_meta( $term->term_id, '_mnsk7_term_seo_backup_20260723', true ) === '' ) {
			update_term_meta(
				$term->term_id,
				'_mnsk7_term_seo_backup_20260723',
				array(
					'description' => $term->description,
					'after'       => get_term_meta( $term->term_id, '_mnsk7_term_seo_after', true ),
					'faq'         => get_term_meta( $term->term_id, '_mnsk7_term_seo_faq', true ),
					'title'       => get_term_meta( $term->term_id, '_mnsk7_term_seo_title', true ),
					'meta'        => get_term_meta( $term->term_id, '_mnsk7_term_seo_metadesc', true ),
					'version'     => get_term_meta( $term->term_id, '_mnsk7_term_seo_version', true ),
				)
			);
		}

		wp_update_term( $term->term_id, 'product_tag', array( 'description' => wp_kses_post( $profile['intro'] ) ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_after', wp_kses_post( $profile['after'] ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_faq', wp_json_encode( $profile['faq'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_title', sanitize_text_field( $profile['title'] ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_metadesc', sanitize_text_field( $profile['meta'] ) );
		update_term_meta( $term->term_id, '_mnsk7_term_seo_version', $version );
	}
}, 31 );
