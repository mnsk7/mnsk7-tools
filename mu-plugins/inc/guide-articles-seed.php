<?php
/**
 * Seed Przewodnik SEO articles with internal product/category links.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

const MNSK7_GUIDE_ARTICLES_SEED_VERSION = '2026-07-14-docx-v2';

add_action( 'init', 'mnsk7_seed_guide_articles', 25 );

add_filter( 'mnsk7_faq_items_custom', function ( $items, $set ) {
	if ( $set !== 'seeded_article' || ! is_singular( 'post' ) ) {
		return $items;
	}

	$raw = get_post_meta( get_the_ID(), '_mnsk7_article_faq_items', true );
	$faq = json_decode( (string) $raw, true );
	if ( ! is_array( $faq ) ) {
		return $items;
	}

	$clean = array();
	foreach ( $faq as $item ) {
		if ( empty( $item['q'] ) || empty( $item['a'] ) ) {
			continue;
		}
		$clean[] = array(
			'q' => sanitize_text_field( $item['q'] ),
			'a' => wp_kses_post( $item['a'] ),
		);
	}

	return $clean ?: $items;
}, 10, 2 );

/**
 * Creates or updates seeded Przewodnik articles.
 *
 * Existing editor-created posts with the same slug are left untouched unless they
 * carry our seed marker.
 */
function mnsk7_seed_guide_articles() {
	if ( get_option( 'mnsk7_guide_articles_seed_version', '' ) === MNSK7_GUIDE_ARTICLES_SEED_VERSION ) {
		return;
	}

	$author_id   = 1;
	$category_id = mnsk7_get_or_create_guide_category_id();
	$articles    = mnsk7_get_seed_guide_articles();

	foreach ( $articles as $article ) {
		$existing = get_page_by_path( $article['slug'], OBJECT, 'post' );
		$postarr  = array(
			'post_title'     => $article['title'],
			'post_name'      => $article['slug'],
			'post_content'   => $article['content'],
			'post_excerpt'   => $article['excerpt'],
			'post_status'    => 'publish',
			'post_type'      => 'post',
			'post_author'    => $author_id,
			'ping_status'    => 'closed',
			'comment_status' => 'closed',
		);

		if ( $category_id > 0 ) {
			$postarr['post_category'] = array( $category_id );
		}

		if ( $existing instanceof WP_Post ) {
			if ( get_post_meta( $existing->ID, '_mnsk7_seeded_guide_article', true ) !== '1' ) {
				continue;
			}
			$postarr['ID'] = $existing->ID;
			$post_id       = wp_update_post( $postarr, true );
		} else {
			$post_id = wp_insert_post( $postarr, true );
		}

		if ( is_wp_error( $post_id ) || ! $post_id ) {
			continue;
		}

		update_post_meta( $post_id, '_mnsk7_seeded_guide_article', '1' );
		update_post_meta( $post_id, '_mnsk7_seed_version', MNSK7_GUIDE_ARTICLES_SEED_VERSION );
		update_post_meta( $post_id, '_mnsk7_article_faq_items', wp_json_encode( $article['faq'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) );
		update_post_meta( $post_id, 'mnsk7_faq_set', 'seeded_article' );
		update_post_meta( $post_id, 'mnsk7_faq_title', $article['faq_title'] );
		update_post_meta( $post_id, '_yoast_wpseo_metadesc', $article['meta_description'] );
		update_post_meta( $post_id, '_yoast_wpseo_focuskw', $article['focus_keyphrase'] );
	}

	update_option( 'mnsk7_guide_articles_seed_version', MNSK7_GUIDE_ARTICLES_SEED_VERSION );
}

/**
 * Returns the WordPress category ID for Przewodnik posts.
 *
 * @return int
 */
function mnsk7_get_or_create_guide_category_id() {
	$term = get_term_by( 'slug', 'przewodnik', 'category' );
	if ( $term && ! is_wp_error( $term ) ) {
		return (int) $term->term_id;
	}

	$result = wp_insert_term( 'Przewodnik', 'category', array( 'slug' => 'przewodnik' ) );
	if ( is_wp_error( $result ) || empty( $result['term_id'] ) ) {
		return 0;
	}

	return (int) $result['term_id'];
}

/**
 * Seed article definitions.
 *
 * @return array<int,array<string,mixed>>
 */
function mnsk7_get_seed_guide_articles() {
	return array(
		array(
			'slug' => 'frezy-kompresyjne-do-czego-sluza',
			'title' => 'Frez kompresyjny — do czego służy i kiedy go wybrać?',
			'excerpt' => 'Frez kompresyjny ma dwie przeciwne strefy skrawania: dolna kieruje wiór w górę, a górna w dół. Dzięki temu przy przelotowym cięciu można uzyskać czystą krawędź po obu stronach płyty. To rozwiązanie do sklejki, MDF, laminatu i płyt meblowych, gdy liczy się brak odprysków.',
			'meta_description' => 'Frez kompresyjny ma dwie przeciwne strefy skrawania: dolna kieruje wiór w górę, a górna w dół. Dzięki temu przy przelotowym cięciu można uzyskać czystą krawę...',
			'focus_keyphrase' => 'frez kompresyjny',
			'faq_title' => 'FAQ - frez kompresyjny',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Frez kompresyjny ma dwie przeciwne strefy skrawania: dolna kieruje wiór w górę, a górna w dół. Dzięki temu przy przelotowym cięciu można uzyskać czystą krawędź po obu stronach płyty. To rozwiązanie do sklejki, MDF, laminatu i płyt meblowych, gdy liczy się brak odprysków.</p>
<h2>Jak działa geometria kompresyjna</h2>
<p>W zwykłym frezie spiralnym kierunek skrawania pomaga jednej stronie materiału, ale może pogorszyć drugą. Frez kompresyjny łączy dolną część UP CUT i górną DOWN CUT. Siły skrawania spotykają się bliżej środka materiału, dlatego krawędzie są mniej narażone na wyrywanie włókien.</p>
<p>Najlepszy efekt pojawia się wtedy, gdy wysokość wejścia frezu jest poprawnie ustawiona względem grubości płyty. Jeżeli część kompresyjna nie pracuje na obu powierzchniach, rezultat może przypominać pracę zwykłym frezem spiralnym.</p>
<h2>Kiedy wybrać frez kompresyjny</h2>
<ul>
<li>Rozkrój laminowanej płyty wiórowej i MDF.</li>
<li>Sklejka fornirowana, gdzie odprysk jest widoczny na obu stronach.</li>
<li>Formatowanie elementów meblowych, frontów i paneli.</li>
<li>Cięcie na przelot, gdy detal po obu stronach ma pozostać estetyczny.</li>
</ul>
<h2>Jak dobrać narzędzie</h2>
<p>Najpierw dobierz średnicę do wymaganej szerokości rowka i sztywności narzędzia. Następnie sprawdź długość roboczą: musi pokryć grubość materiału oraz pozwolić strefie kompresyjnej pracować we właściwym miejscu. Liczba ostrzy wpływa na miejsce na wiór i stabilność. Przy materiałach płytowych nie ignoruj odciągu — zapchane rowki szybko psują krawędź i skracają życie frezu.</p>
<p>Przed serią warto wykonać próbę na odpadowym fragmencie tej samej płyty. Sprawdź górną i dolną krawędź, temperaturę narzędzia oraz jakość wióra. Parametry obrotów i posuwu powinien potwierdzić operator zgodnie z maszyną, materiałem i mocowaniem.</p>
<h2>Najczęstsze błędy</h2>
<ul>
<li>Zbyt płytkie wejście, przez które działa tylko jedna część geometrii.</li>
<li>Dobór zbyt małej średnicy do długiego wysięgu i zbyt agresywnego posuwu.</li>
<li>Praca bez skutecznego odciągu przy MDF i płycie wiórowej.</li>
<li>Oczekiwanie idealnej krawędzi przy zużytym narzędziu lub słabym podparciu płyty.</li>
</ul>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy kompresyjne UP&amp;DOWN CUT.</li>
<li>Produkty według średnicy i długości roboczej.</li>
<li>Przewodnik: Frezy CNC do drewna — dobór do materiału.</li>
</ul>

[mnsk7_guide_products categories="frezy-kompresyjne-updown-cut,frez-kompresyjny-vhm,frezy-do-drewna,frezy-do-mdf" title="Powiazane frezy i kategorie" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy frez kompresyjny nadaje się do MDF?', 'a' => 'Tak, szczególnie do przelotowego cięcia płyt, gdy trzeba ograniczyć strzępienie krawędzi.' ),
				array( 'q' => 'Czy zastąpi frez jednopiórowy?', 'a' => 'Nie zawsze. Frez jednopiórowy ma inne zalety przy odprowadzaniu wióra i wybranych materiałach.' ),
				array( 'q' => 'Czy można nim frezować nieprzelotowo?', 'a' => 'Można, lecz efekt kompresji zależy od głębokości i ustawienia stref skrawających.' ),
			),
		),
		array(
			'slug' => 'frez-do-wyrownania-sleba-i-planowania-powierzchni',
			'title' => 'Jaki frez do planowania drewna i wyrównywania slabów?',
			'excerpt' => 'Do wyrównywania powierzchni drewna i slabów najczęściej wybiera się frez do planowania o dużej średnicy roboczej, stabilnej konstrukcji i wymiennych płytkach. Pozwala szybko usunąć nierówności, ale dobór zależy od szerokości przejścia, sztywności CNC, rodzaju drewna oraz jakości oczekiwanej po obróbce.',
			'meta_description' => 'Do wyrównywania powierzchni drewna i slabów najczęściej wybiera się frez do planowania o dużej średnicy roboczej, stabilnej konstrukcji i wymiennych płytkach...',
			'focus_keyphrase' => 'frez do planowania drewna',
			'faq_title' => 'FAQ - frez do planowania drewna',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Do wyrównywania powierzchni drewna i slabów najczęściej wybiera się frez do planowania o dużej średnicy roboczej, stabilnej konstrukcji i wymiennych płytkach. Pozwala szybko usunąć nierówności, ale dobór zależy od szerokości przejścia, sztywności CNC, rodzaju drewna oraz jakości oczekiwanej po obróbce.</p>
<h2>Co robi frez do planowania</h2>
<p>Planowanie to przejście całej powierzchni narzędziem o szerokim obszarze skrawania. Celem nie jest wycięcie detalu, lecz zbudowanie równej płaszczyzny przed klejeniem, olejowaniem, żywicą lub dalszą obróbką. W przypadku slabów narzędzie usuwa falowanie, ślady po pile i różnice wysokości.</p>
<h2>Jak dobrać średnicę i płytki</h2>
<p>Większa średnica skraca liczbę przejść, ale zwiększa wymagania wobec wrzeciona, mocowania i sztywności bramy. Nie należy wybierać największego frezu wyłącznie dla szybkości. Lepiej dobrać średnicę do realnej maszyny i wykonywać powtarzalne, stabilne przejścia.</p>
<p>Frezy z wymiennymi płytkami ułatwiają eksploatację: po stępieniu obraca się lub wymienia płytkę zamiast kupować całe narzędzie. Przed obróbką sprawdź, czy płytki są czyste, dokręcone i ustawione równo.</p>
<h2>Proces pracy krok po kroku</h2>
<ul>
<li>Zamocuj materiał tak, by nie drgał i nie unosił się po przejściu frezu.</li>
<li>Ustal punkt zerowy w najwyższym miejscu powierzchni.</li>
<li>Wykonaj próbne przejście oraz obejrzyj ślad narzędzia.</li>
<li>Zostaw niewielki naddatek na przejście wykańczające.</li>
<li>Po planowaniu oceń powierzchnię pod światło; ślady mogą wynikać z niewypoziomowanej bramy, a nie z frezu.</li>
</ul>
<h2>Czego unikać</h2>
<p>Za głęboki zbiór przy dużej średnicy może przeciążyć wrzeciono. Widoczne pasy często oznaczają bicie, źle ustawioną maszynę albo zużyte płytki. Nie ukrywaj problemu pod większym posuwem — najpierw sprawdź geometrię i mocowanie.</p>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy do planowania.</li>
<li>Frez do planowania z wymiennymi płytkami — produkty.</li>
<li>Artykuł uzupełniający: Jak przygotować slab do obróbki CNC.</li>
</ul>

[mnsk7_guide_products categories="frezy-do-planowania,frezy-do-drewna,frezy-do-mdf,frez-z-wymiennymi-plytkami" title="Frezy do planowania i dalszej obrobki" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy frez do planowania nadaje się do MDF?', 'a' => 'Tak, o ile odciąg wióra jest skuteczny, a parametry są dobrane do maszyny.' ),
				array( 'q' => 'Dlaczego po planowaniu zostają fale?', 'a' => 'Najczęściej przez luz, bicie narzędzia, źle wypoziomowaną bramę albo zbyt agresywne przejście.' ),
				array( 'q' => 'Czy potrzebne jest przejście wykańczające?', 'a' => 'Zwykle tak — poprawia jednolitość powierzchni przed szlifowaniem.' ),
			),
		),
		array(
			'slug' => 'borfrezy-do-metalu-zastosowanie',
			'title' => 'Pilniki obrotowe i frezy trzpieniowe do metalu — jak wybrać?',
			'excerpt' => 'Pilniki obrotowe, zwane też frezami trzpieniowymi lub borfrezami, służą do zdzierania, fazowania, obróbki spoin i pracy w trudno dostępnych miejscach. Wybiera się je według kształtu głowicy, rodzaju nacięcia, materiału obrabianego oraz średnicy trzpienia zgodnej z narzędziem napędowym.',
			'meta_description' => 'Pilniki obrotowe, zwane też frezami trzpieniowymi lub borfrezami, służą do zdzierania, fazowania, obróbki spoin i pracy w trudno dostępnych miejscach. Wybier...',
			'focus_keyphrase' => 'pilniki obrotowe',
			'faq_title' => 'FAQ - pilniki obrotowe',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Pilniki obrotowe, zwane też frezami trzpieniowymi lub borfrezami, służą do zdzierania, fazowania, obróbki spoin i pracy w trudno dostępnych miejscach. Wybiera się je według kształtu głowicy, rodzaju nacięcia, materiału obrabianego oraz średnicy trzpienia zgodnej z narzędziem napędowym.</p>
<h2>Do czego służą</h2>
<p>To narzędzia do szybkiej obróbki punktowej: usuwania nadlewek, wygładzania spoin, pracy na krawędziach i korekty kształtu. W przeciwieństwie do frezów CNC nie są zwykle wyborem do precyzyjnego planowania całej powierzchni, tylko do kontroli lokalnego miejsca.</p>
<h2>Kształt ma znaczenie</h2>
<p>Typy A, C, D, F, G, K i L różnią się kształtem roboczym. Kulisty pomaga przy promieniach i wnętrzach, walcowy przy krawędziach oraz rowkach, stożkowy przy fazach, a płomieniowy w miejscach o trudnym dostępie. Najpierw określ geometrię obrabianego obszaru, dopiero potem wybieraj średnicę.</p>
<h2>Nacięcie i materiał</h2>
<p>Do stali potrzebna jest geometria pozwalająca na stabilne skrawanie bez nadmiernego grzania. Aluminium i inne miękkie metale mogą zapychać narzędzie, dlatego wymagają odpowiedniego nacięcia oraz odprowadzania wióra. Nie wybieraj narzędzia wyłącznie po cenie: nieodpowiedni typ szybko się zalepi albo zostawi chropowatą powierzchnię.</p>
<h2>Bezpieczna praca</h2>
<ul>
<li>Zawsze sprawdź dopuszczalne obroty frezu i narzędzia napędowego.</li>
<li>Używaj okularów oraz osłony przed odpryskami.</li>
<li>Nie dociskaj narzędzia na siłę — pozwól ostrzom ciąć.</li>
<li>Nie pracuj zużytym frezem, gdy powierzchnia zaczyna się przegrzewać.</li>
</ul>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Pilniki obrotowe.</li>
<li>Podkategorie według typu: A, C, D, F, G, K i L.</li>
<li>Produkty do stali oraz do metali kolorowych.</li>
</ul>

[mnsk7_guide_products categories="frez-pilnik-obrotowy,pilniki-obrotowe,frezy-do-stali,frezy-do-aluminium" title="Pilniki obrotowe i frezy do metalu" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy pilnik obrotowy jest do szlifierki?', 'a' => 'Najczęściej pracuje w narzędziu obrotowym o odpowiednim uchwycie i dopuszczalnych obrotach; sprawdź średnicę trzpienia.' ),
				array( 'q' => 'Jaki kształt wybrać do spoiny?', 'a' => 'Zależy od miejsca; do szerokiej powierzchni sprawdza się walec, a do przejść i narożników kształt stożkowy lub płomieniowy.' ),
				array( 'q' => 'Czy jednym frezem obrobię stal i aluminium?', 'a' => 'Technicznie czasem tak, ale lepszy rezultat daje narzędzie dobrane do materiału.' ),
			),
		),
		array(
			'slug' => 'frezy-do-3d-obrobki-cnc',
			'title' => 'Frezy do obróbki 3D CNC — jak dobrać narzędzie do reliefów i detali?',
			'excerpt' => 'W obróbce 3D narzędzie dobiera się przede wszystkim do najmniejszego promienia w modelu, materiału i etapu pracy. Frezy kulowe sprawdzają się przy łagodnych powierzchniach, a stożkowo-kulowe oferują większą sztywność przy pracy w głębszych detalach. Najlepszy rezultat zwykle wymaga osobnego narzędzia do zgrubnej i wykańczającej obróbki.',
			'meta_description' => 'W obróbce 3D narzędzie dobiera się przede wszystkim do najmniejszego promienia w modelu, materiału i etapu pracy. Frezy kulowe sprawdzają się przy łagodnych...',
			'focus_keyphrase' => 'frezy do obróbki 3D CNC',
			'faq_title' => 'FAQ - frezy do obróbki 3D CNC',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>W obróbce 3D narzędzie dobiera się przede wszystkim do najmniejszego promienia w modelu, materiału i etapu pracy. Frezy kulowe sprawdzają się przy łagodnych powierzchniach, a stożkowo-kulowe oferują większą sztywność przy pracy w głębszych detalach. Najlepszy rezultat zwykle wymaga osobnego narzędzia do zgrubnej i wykańczającej obróbki.</p>
<h2>Zgrubnie i wykańczająco</h2>
<p>Zgrubnie usuwa materiał szybko, zostawiając naddatek. Wykańczanie prowadzi po gęstszej ścieżce i buduje jakość powierzchni. Próba wykonania wszystkiego jednym drobnym frezem wydłuża czas pracy i zwiększa ryzyko złamania narzędzia.</p>
<h2>Frez kulowy czy stożkowo-kulowy</h2>
<p>Frez kulowy tworzy przewidywalne przejścia na powierzchniach 3D. Średnica kuli decyduje o tym, czy narzędzie wejdzie w małe promienie. Frez stożkowo-kulowy ma stożkowy korpus, dlatego przy podobnym promieniu końcówki może być sztywniejszy. Jest szczególnie użyteczny w reliefach, napisach 3D i głębszych formach.</p>
<h2>Materiał decyduje o geometrii</h2>
<p>Drewno, tworzywa, aluminium i kamień wymagają innego podejścia do wióra, chłodzenia oraz trwałości ostrza. Nie kopiuj parametrów z filmu pokazującego inny materiał. Użyj danych producenta narzędzia jako punktu startowego, a później wykonaj próbę na odpadowym fragmencie.</p>
<h2>Jak ocenić efekt</h2>
<p>Widoczne schodki mogą oznaczać zbyt duży krok boczny, nie za mały frez. Przypalenia i zmatowienie są sygnałem problemu z parametrami, odciągiem albo zużyciem. Przed zmianą całej strategii CAM sprawdź bicie narzędzia i mocowanie materiału.</p>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy stożkowo-kulowe.</li>
<li>Kategoria: Frezy kulowe.</li>
<li>Frezy diamentowe do granitu i kamienia.</li>
</ul>

[mnsk7_guide_products categories="frez-kulowy-vhm,frez-stozkowy-kulowy-vhm,frezy-do-drewna,frezy-do-mdf" title="Frezy do obrobki 3D" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy mały promień zawsze daje lepszą jakość?', 'a' => 'Daje dostęp do mniejszych detali, ale może znacząco wydłużyć pracę i obniżyć sztywność.' ),
				array( 'q' => 'Czy frez stożkowo-kulowy nadaje się do 3D?', 'a' => 'Tak, to częsty wybór do detali wymagających długiego i stabilnego narzędzia.' ),
				array( 'q' => 'Po co frez zgrubny?', 'a' => 'Szybciej usuwa materiał i zostawia bezpieczny naddatek dla narzędzia wykańczającego.' ),
			),
		),
		array(
			'slug' => 'frezy-raszplowe-kukurydza',
			'title' => 'Frez „kukurydza” — do czego służy i kiedy go używać?',
			'excerpt' => 'Frez „kukurydza” to frez wieloostrzowy o charakterystycznej, gęstej geometrii. Stosuje się go głównie do szybkiego zdzierania materiału i obróbki zgrubnej, gdy ważne jest stabilne usuwanie dużej ilości materiału. Nie jest automatycznie najlepszym narzędziem do końcowego, gładkiego wykończenia.',
			'meta_description' => 'Frez „kukurydza” to frez wieloostrzowy o charakterystycznej, gęstej geometrii. Stosuje się go głównie do szybkiego zdzierania materiału i obróbki zgrubnej, g...',
			'focus_keyphrase' => 'frez kukurydza',
			'faq_title' => 'FAQ - frez kukurydza',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Frez „kukurydza” to frez wieloostrzowy o charakterystycznej, gęstej geometrii. Stosuje się go głównie do szybkiego zdzierania materiału i obróbki zgrubnej, gdy ważne jest stabilne usuwanie dużej ilości materiału. Nie jest automatycznie najlepszym narzędziem do końcowego, gładkiego wykończenia.</p>
<h2>Kiedy ma przewagę</h2>
<p>Gęste ostrza rozbijają wiór na mniejsze fragmenty i pozwalają kontrolować obciążenie przy obróbce wybranych tworzyw, drewna oraz materiałów kompozytowych. Frez może być dobrym wyborem na etapie zgrubnym, przed użyciem frezu prostego, spiralnego albo kulowego do wykończenia.</p>
<h2>Dobór do zadania</h2>
<p>Sprawdź średnicę roboczą, długość ostrza, średnicę trzpienia oraz maksymalny wysięg. Długi, cienki frez będzie podatny na drgania nawet przy dobrej geometrii. Przy obróbce materiału pylącego odciąg nie jest dodatkiem — ma bezpośredni wpływ na temperaturę i jakość pracy.</p>
<h2>Czego nie obiecywać</h2>
<p>Nazwa „kukurydza” nie oznacza jednego uniwersalnego zastosowania. Różne wykonania mają inną geometrię i mogą być przeznaczone do innych materiałów. Zawsze porównuj opis konkretnego produktu z własnym materiałem i maszyną.</p>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy wieloostrzowe (kukurydza).</li>
<li>Frezy proste i spiralne do przejścia wykańczającego.</li>
</ul>

[mnsk7_guide_products categories="frez-wieloostrzowy-vhm,frezy-do-drewna,frezy-do-mdf,frez-prosty-vhm" title="Frezy do obrobki zgrubnej i wykonczeniowej" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy frez kukurydza zostawia gładką powierzchnię?', 'a' => 'Zwykle jest narzędziem bardziej do zgrubnej obróbki; jakość końcową często poprawia kolejne przejście.' ),
				array( 'q' => 'Czy nadaje się do metalu?', 'a' => 'Tylko jeśli dany model jest wyraźnie przeznaczony do tego materiału.' ),
				array( 'q' => 'Jak zmniejszyć drgania?', 'a' => 'Skróć wysięg, popraw mocowanie i rozpocznij od bezpiecznej próby.' ),
			),
		),
		array(
			'slug' => 'rodzaje-frezow-do-recznego-frezera-do-drewna',
			'title' => 'Rodzaje frezów do ręcznej frezarki po drewnie — praktyczny przewodnik',
			'excerpt' => 'Do ręcznej frezarki po drewnie dobiera się frez według efektu: rowka, krawędzi, fazy, zaokrąglenia albo kopiowania po szablonie. Kluczowe są średnica trzpienia, dopuszczalne obroty oraz prowadzenie narzędzia. Frez z łożyskiem pomaga prowadzić narzędzie po krawędzi lub szablonie, ale nie zastępuje bezpiecznego mocowania materiału.',
			'meta_description' => 'Do ręcznej frezarki po drewnie dobiera się frez według efektu: rowka, krawędzi, fazy, zaokrąglenia albo kopiowania po szablonie. Kluczowe są średnica trzpien...',
			'focus_keyphrase' => 'frezy do frezarki ręcznej po drewnie',
			'faq_title' => 'FAQ - frezy do frezarki ręcznej po drewnie',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Do ręcznej frezarki po drewnie dobiera się frez według efektu: rowka, krawędzi, fazy, zaokrąglenia albo kopiowania po szablonie. Kluczowe są średnica trzpienia, dopuszczalne obroty oraz prowadzenie narzędzia. Frez z łożyskiem pomaga prowadzić narzędzie po krawędzi lub szablonie, ale nie zastępuje bezpiecznego mocowania materiału.</p>
<h2>Najczęstsze typy</h2>
<ul>
<li>Frez prosty — rowki, kieszenie i proste krawędzie.</li>
<li>Frez z łożyskiem — kopiowanie po szablonie lub krawędzi.</li>
<li>Frez fazujący — łamanie ostrej krawędzi.</li>
<li>Frez zaokrąglający — miękki promień na krawędzi.</li>
<li>Frez profilowy — dekoracyjne wykończenie.</li>
</ul>
<h2>Jak bezpiecznie dobrać frez</h2>
<p>Sprawdź zgodność trzpienia z tuleją frezarki. Nie zakładaj, że każdy frez CNC można bezpiecznie wykorzystać w narzędziu ręcznym. Zwróć uwagę na dopuszczalne obroty, średnicę frezu oraz kierunek prowadzenia. Materiał powinien być stabilnie unieruchomiony, a ruch frezarki kontrolowany.</p>
<h2>Kiedy użyć łożyska</h2>
<p>Łożysko jest pomocne przy powtarzalnym kopiowaniu kształtu, ale wymaga czystej, równej krawędzi odniesienia. Jeśli szablon jest krzywy albo łożysko ma luz, błąd zostanie powielony na każdym elemencie.</p>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy z łożyskiem.</li>
<li>Kategoria: Frezy proste.</li>
<li>Kategoria: Frezy zaokrąglające.</li>
</ul>

[mnsk7_guide_products categories="frez-z-lozyskiem-stal-vhm,frez-prosty-vhm,frezy-do-drewna,frezy-do-mdf" title="Frezy do frezarki recznej i drewna" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy frez z łożyskiem nadaje się do CNC?', 'a' => 'Może mieć zastosowanie, ale jego podstawowa rola jest często związana z kopiowaniem i prowadzeniem po krawędzi.' ),
				array( 'q' => 'Jaki frez do rowka?', 'a' => 'Najczęściej frez prosty o średnicy dopasowanej do szerokości rowka.' ),
				array( 'q' => 'Dlaczego krawędź się przypala?', 'a' => 'Przyczyną może być zbyt wolny posuw, tępy frez, niewłaściwe obroty lub wielokrotne tarcie.' ),
			),
		),
		array(
			'slug' => 'rodzaje-frezow-cnc-do-drewna',
			'title' => 'Frezy CNC do drewna — podstawowe rodzaje i zastosowania',
			'excerpt' => 'Dobór frezu CNC do drewna zaczyna się od zadania: cięcie konturu, kieszeń, grawerowanie, planowanie albo 3D. Następnie należy uwzględnić materiał — lite drewno, MDF, sklejkę lub laminat — oraz sposób odprowadzania wióra. Nie ma jednego frezu idealnego do wszystkich prac.',
			'meta_description' => 'Dobór frezu CNC do drewna zaczyna się od zadania: cięcie konturu, kieszeń, grawerowanie, planowanie albo 3D. Następnie należy uwzględnić materiał — lite drew...',
			'focus_keyphrase' => 'frezy CNC do drewna',
			'faq_title' => 'FAQ - frezy CNC do drewna',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Dobór frezu CNC do drewna zaczyna się od zadania: cięcie konturu, kieszeń, grawerowanie, planowanie albo 3D. Następnie należy uwzględnić materiał — lite drewno, MDF, sklejkę lub laminat — oraz sposób odprowadzania wióra. Nie ma jednego frezu idealnego do wszystkich prac.</p>
<h2>Podział według zadania</h2>
<p>Frezy proste i spiralne stosuje się do konturów oraz kieszeni. Frezy kompresyjne pomagają ograniczyć odpryski na płytach. Frezy do planowania wyrównują powierzchnie. Frezy kulowe i stożkowo-kulowe obsługują reliefy 3D, a frezy z łożyskiem są przydatne w zadaniach kopiujących.</p>
<h2>Materiał ma znaczenie</h2>
<p>MDF daje dużo pyłu, więc wymaga skutecznego odciągu. Sklejka potrafi wyrywać fornir na krawędzi, dlatego warto rozważyć geometrię kompresyjną. Lite drewno ma zmienny układ włókien i może wymagać spokojniejszej strategii w miejscach problematycznych. Laminat wymaga szczególnej dbałości o czystość cięcia.</p>
<h2>Szybka checklista przed zakupem</h2>
<ul>
<li>Jaki materiał i jaka jego grubość?</li>
<li>Czy cięcie jest przelotowe?</li>
<li>Jaki jest maksymalny wysięg i uchwyt wrzeciona?</li>
<li>Czy ważniejsza jest szybkość zgrubna, czy jakość krawędzi?</li>
<li>Czy masz odciąg i stabilne mocowanie?</li>
</ul>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Frezy do drewna.</li>
<li>Frezy kompresyjne.</li>
<li>Frezy do planowania.</li>
<li>Frezy kulowe i stożkowo-kulowe.</li>
</ul>

[mnsk7_guide_products categories="frezy-do-drewna,frezy-do-mdf,frezy-kompresyjne-updown-cut,frez-kulowy-vhm" title="Frezy CNC do drewna" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Jaki frez do MDF?', 'a' => 'Często sprawdza się frez spiralny lub kompresyjny, zależnie od wymagań dla obu krawędzi.' ),
				array( 'q' => 'Jaki frez do sklejki?', 'a' => 'Przy widocznym fornirze warto rozważyć frez kompresyjny i wykonać próbę.' ),
				array( 'q' => 'Czy liczba ostrzy ma znaczenie?', 'a' => 'Tak, wpływa na miejsce na wiór, stabilność i charakter obróbki.' ),
			),
		),
		array(
			'slug' => 'frezy-koncowe-do-aluminium',
			'title' => 'Jaki frez do aluminium CNC? Liczba ostrzy, geometria i dobór',
			'excerpt' => 'Aluminium wymaga frezu, który skutecznie odprowadza lepki wiór i ogranicza jego przywieranie do ostrza. Częstym wyborem jest frez jednopiórowy lub inna geometria z dużą przestrzenią na wiór, ale ostateczny dobór zależy od stopu aluminium, mocy wrzeciona, chłodzenia i strategii CAM.',
			'meta_description' => 'Aluminium wymaga frezu, który skutecznie odprowadza lepki wiór i ogranicza jego przywieranie do ostrza. Częstym wyborem jest frez jednopiórowy lub inna geome...',
			'focus_keyphrase' => 'frez do aluminium CNC',
			'faq_title' => 'FAQ - frez do aluminium CNC',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Aluminium wymaga frezu, który skutecznie odprowadza lepki wiór i ogranicza jego przywieranie do ostrza. Częstym wyborem jest frez jednopiórowy lub inna geometria z dużą przestrzenią na wiór, ale ostateczny dobór zależy od stopu aluminium, mocy wrzeciona, chłodzenia i strategii CAM.</p>
<h2>Dlaczego aluminium jest wymagające</h2>
<p>Aluminium jest miękkie w porównaniu ze stalą, ale jego wiór potrafi przylegać do narzędzia. Gdy rowki się zapychają, rośnie temperatura, pogarsza się powierzchnia i zwiększa się ryzyko złamania frezu. Dlatego ważne są geometria, czystość narzędzia i odprowadzanie wióra.</p>
<h2>Jedno czy więcej ostrzy</h2>
<p>Frez jednopiórowy daje dużo miejsca na wiór i jest popularny przy wielu zastosowaniach w aluminium. Większa liczba ostrzy może być uzasadniona w konkretnych warunkach, lecz zmniejsza przestrzeń między krawędziami. Nie wybieraj wyłącznie po liczbie P — porównaj pełną specyfikację produktu.</p>
<h2>Kontrola procesu</h2>
<p>Utrzymuj stabilne mocowanie, unikaj pracy tępym frezem i obserwuj wiór. Jeśli przykleja się do ostrza, nie kontynuuj bez korekty. Chłodzenie lub mgła mogą być potrzebne zależnie od maszyny i procesu; stosuj rozwiązanie bezpieczne dla stanowiska.</p>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy do aluminium.</li>
<li>Frezy jednopiórowe.</li>
<li>Frezy dwupiórowe i polerowane.</li>
</ul>

[mnsk7_guide_products category="frezy-do-aluminium" title="Frezy do aluminium w ofercie" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy frez do stali nadaje się do aluminium?', 'a' => 'Może ciąć, ale geometria do aluminium zwykle lepiej radzi sobie z odprowadzaniem lepkiego wióra.' ),
				array( 'q' => 'Czy potrzebne jest chłodzenie?', 'a' => 'Zależy od maszyny i obciążenia; przy trudnych operacjach pomaga ograniczać nagrzewanie.' ),
				array( 'q' => 'Co oznacza nalipek aluminium?', 'a' => 'To przywierający materiał na krawędzi frezu, który pogarsza jakość i trwałość.' ),
			),
		),
		array(
			'slug' => 'frezy-do-metalu-stal-i-metale-kolorowe',
			'title' => 'Frezy CNC do stali i metali kolorowych — jak dobrać narzędzie?',
			'excerpt' => 'Frezy do stali i metali kolorowych różnią się geometrią, liczbą ostrzy i przeznaczeniem. Stal wymaga odporności na obciążenie oraz temperaturę, a aluminium, miedź czy mosiądz — sprawnego odprowadzania wióra i ochrony przed zalepianiem. Najpierw wybierz materiał, później typ frezu.',
			'meta_description' => 'Frezy do stali i metali kolorowych różnią się geometrią, liczbą ostrzy i przeznaczeniem. Stal wymaga odporności na obciążenie oraz temperaturę, a aluminium...',
			'focus_keyphrase' => 'frezy do metalu CNC',
			'faq_title' => 'FAQ - frezy do metalu CNC',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Frezy do stali i metali kolorowych różnią się geometrią, liczbą ostrzy i przeznaczeniem. Stal wymaga odporności na obciążenie oraz temperaturę, a aluminium, miedź czy mosiądz — sprawnego odprowadzania wióra i ochrony przed zalepianiem. Najpierw wybierz materiał, później typ frezu.</p>
<h2>Stal i stal nierdzewna</h2>
<p>Przy stali ważne są sztywność układu, stabilne mocowanie i narzędzie przeznaczone do wymaganej twardości. Zbyt agresywne warunki powodują drgania, przegrzewanie i szybkie zużycie. Frez wieloostrzowy może być korzystny przy wykańczaniu, ale tylko gdy maszyna oraz odprowadzanie wióra to udźwigną.</p>
<h2>Metale kolorowe</h2>
<p>Aluminium, miedź i mosiądz mają inne zachowanie wióra. Szczególnie aluminium może oblepiać narzędzie. Dobieraj frez według konkretnego materiału, a nie wyłącznie pod hasło „do metalu”.</p>
<h2>Przed uruchomieniem</h2>
<ul>
<li>Sprawdź materiał i jego twardość lub stop.</li>
<li>Dobierz produkt do wymaganej operacji: rowek, kontur, kieszeń, wykańczanie.</li>
<li>Ogranicz wysięg narzędzia do koniecznego minimum.</li>
<li>Ustal parametry zgodnie z zaleceniami producenta i wykonaj próbę.</li>
</ul>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Kategoria: Frezy do stali.</li>
<li>Kategoria: Frezy do metali kolorowych.</li>
<li>Kategoria: Frezy do aluminium.</li>
<li>Pilniki obrotowe do obróbki ręcznej.</li>
</ul>

[mnsk7_guide_products categories="frezy-do-stali,frezy-do-aluminium,frez-pilnik-obrotowy,frez-palcowy-do-metalu" title="Frezy do metalu i obrobki recznej" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy jeden frez będzie dobry do każdego metalu?', 'a' => 'Nie — właściwości stali i aluminium są zbyt różne, by optymalny dobór był taki sam.' ),
				array( 'q' => 'Co skraca życie frezu?', 'a' => 'Drgania, zapychanie wiórem, przegrzewanie, zbyt duży wysięg i praca narzędziem nieprzeznaczonym do materiału.' ),
				array( 'q' => 'Czy liczba ostrzy jest najważniejsza?', 'a' => 'To ważny parametr, ale trzeba go oceniać razem z geometrią, powłoką i zadaniem.' ),
			),
		),
		array(
			'slug' => 'liczba-ostrz-frezu-jednopiorowe-dwupiorowe-czteropiorowe',
			'title' => 'Ile ostrzy ma mieć frez: 1P, 2P, 3P czy 4P?',
			'excerpt' => 'Liczba ostrzy wpływa na miejsce na wiór, sztywność oraz charakter obróbki. Frezy 1P często dobrze radzą sobie z dużą ilością wióra, 2P są wszechstronne, a 3P i 4P bywają wybierane do stabilniejszego skrawania lub wykańczania. Nie istnieje uniwersalna reguła: dobór zależy od materiału, operacji i możliwości maszyny.',
			'meta_description' => 'Liczba ostrzy wpływa na miejsce na wiór, sztywność oraz charakter obróbki. Frezy 1P często dobrze radzą sobie z dużą ilością wióra, 2P są wszechstronne, a 3P...',
			'focus_keyphrase' => 'frez jednopiórowy',
			'faq_title' => 'FAQ - frez jednopiórowy',
			'content' => <<<'HTML'
<h2>Krótka odpowiedź</h2>
<p>Liczba ostrzy wpływa na miejsce na wiór, sztywność oraz charakter obróbki. Frezy 1P często dobrze radzą sobie z dużą ilością wióra, 2P są wszechstronne, a 3P i 4P bywają wybierane do stabilniejszego skrawania lub wykańczania. Nie istnieje uniwersalna reguła: dobór zależy od materiału, operacji i możliwości maszyny.</p>
<h2>Frez 1P</h2>
<p>Jedna krawędź daje dużą przestrzeń na wiór. To cecha przydatna m.in. przy materiałach, które tworzą większy albo lepki wiór. Jednocześnie narzędzie może być mniej sztywne niż wariant o większej liczbie ostrzy o tej samej średnicy.</p>
<h2>Frez 2P</h2>
<p>Dwa ostrza to częsty kompromis między odprowadzaniem wióra a stabilnością. W wielu zastosowaniach do drewna, tworzyw i wybranych metali jest punktem startowym, ale nie zastępuje analizy konkretnego produktu.</p>
<h2>Frez 3P i 4P</h2>
<p>Więcej ostrzy może zwiększyć liczbę kontaktów z materiałem i pomóc uzyskać dobrą powierzchnię w odpowiednich warunkach. Równocześnie zostaje mniej miejsca na wiór. Dlatego w głębokim rowku lub przy dużej ilości materiału do usunięcia wybór nie zawsze będzie oczywisty.</p>
<h2>Jak podejmować decyzję</h2>
<p>Zacznij od materiału i rodzaju operacji. Następnie porównaj średnicę, długość roboczą, wysięg oraz geometrię produktu. Dopiero na końcu traktuj liczbę ostrzy jako ważny, ale nie jedyny filtr.</p>
<h2>Co zobaczyć w sklepie</h2>
<ul>
<li>Frezy jednopiórowe.</li>
<li>Frezy dwupiórowe.</li>
<li>Frezy trzypiórowe.</li>
<li>Frezy czteropiórowe.</li>
</ul>

[mnsk7_guide_products categories="frezy-do-aluminium,frezy-do-stali,frezy-do-drewna,frezy-do-mdf" title="Dobierz frez wedlug materialu" format="grid" limit="6"]
HTML
			,
			'faq' => array(
				array( 'q' => 'Czy więcej ostrzy oznacza lepiej?', 'a' => 'Nie. Więcej ostrzy może pomóc przy pewnych operacjach, ale zmniejsza miejsce na wiór.' ),
				array( 'q' => 'Jaki frez do aluminium?', 'a' => 'Często rozważa się geometrię z dużą przestrzenią na wiór, np. 1P, ale wybór zależy od procesu.' ),
				array( 'q' => 'Czy 2P to dobry wybór na start?', 'a' => 'Bywa wszechstronny, ale zawsze sprawdź materiał i opis konkretnego narzędzia.' ),
			),
		),
	);
}
