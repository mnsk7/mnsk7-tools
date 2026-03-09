<?php
/**
 * MNK7 Tools — FAQ shortcode [mnsk7_faq] + FAQPage JSON-LD (Google rich results).
 *
 * Użycie:
 *   [mnsk7_faq set="dostawa"]   — dostawa, koszt, VAT, zwroty
 *   [mnsk7_faq set="produkt"]   — dobór frezu, HRC, 1P vs 4P
 *   [mnsk7_faq set="sklep"]     — firma, kontakt, rabaty
 *   [mnsk7_faq]                 — wszystkie zestawy
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

function mnsk7_get_faq_set( $set = '' ) {
	$sets = array(
		'dostawa' => array(
			array( 'q' => 'Ile czasu trwa dostawa?', 'a' => 'Zamówienia złożone w dni robocze do 15:00 (InPost) lub 17:00 (DPD) wysyłamy tego samego dnia — dostawa trafia do Ciebie następnego dnia roboczego. Dostarczamy wyłącznie na terenie Polski.' ),
			array( 'q' => 'Ile kosztuje dostawa?', 'a' => 'Zamówienia powyżej 300 zł — dostawa gratis. Poniżej tego progu koszt zależy od wybranego kuriera (InPost / DPD).' ),
			array( 'q' => 'Jakie formy dostawy są dostępne?', 'a' => 'Wysyłamy przez InPost (paczkomaty i kurier) oraz DPD. Wyboru dokonujesz przy składaniu zamówienia.' ),
			array( 'q' => 'Czy wystawiacie faktury VAT?', 'a' => 'Tak — faktura VAT jest wystawiana na życzenie. Podaj NIP i dane firmy w uwagach lub w polu NIP w koszyku.' ),
			array( 'q' => 'Czy mogę zwrócić towar?', 'a' => 'Tak, masz 30 dni na zwrot nieużywanego towaru. Skontaktuj się mailowo: office@mnsk7.pl.' ),
		),
		'produkt' => array(
			array( 'q' => 'Jak dobrać frez do materiału?', 'a' => 'Do drewna i MDF: frezy spiralne lub jednopiórowe. Do aluminium: 1P lub 2P z powłoką DLC/AlTiN. Do stali: 4P VHM HRC 65+. Do tworzyw: 1P z ostrą krawędzią.' ),
			array( 'q' => 'Co oznacza HRC 65 na frezie?', 'a' => 'HRC 65 to twardość materiału narzędzia (skala Rockwella). Im wyższe HRC, tym większa odporność na ścieranie — szczególnie ważna przy obróbce stali i metali.' ),
			array( 'q' => 'Czym różni się frez 1-piórowy od 2- lub 4-piórowego?', 'a' => 'Frez 1P: duże rowki na wiór — idealny do aluminium i tworzyw. Frez 2P: kompromis. Frez 4P: gładkie wykończenie, do stali i twardych metali, wolniejszy posuw.' ),
			array( 'q' => 'Czy frezy VHM nadają się do frezarek CNC?', 'a' => 'Tak, wszystkie frezy VHM w naszej ofercie są dedykowane do CNC. Parametry skrawania (RPM, posuw) podajemy w opisach produktów.' ),
			array( 'q' => 'Jakie frezy pasują do frezowania MDF?', 'a' => 'Do MDF najlepsze są frezy spiralne (2–3 pióra), jednopiórowe do szybkiego usuwania materiału lub kopiowarki do rowków i krawędzi.' ),
		),
		'sklep' => array(
			array( 'q' => 'Skąd pochodzi mnsk7-tools.pl?', 'a' => 'Jesteśmy polską firmą MNSK7 sp. z o.o. z siedzibą w Warszawie. Sprzedajemy przez sklep internetowy i Allegro (383+ pozytywne oceny, 100% satysfakcji).' ),
			array( 'q' => 'Jak skontaktować się ze sklepem?', 'a' => 'Email: office@mnsk7.pl, telefon: +48 451 696 511. Godziny: pn.–pt. 9:00–17:00, sob. 10:00–12:00.' ),
			array( 'q' => 'Czy możliwy jest zakup hurtowy?', 'a' => 'Tak, obsługujemy zamówienia hurtowe i oferujemy program rabatowy: od 1 000 zł/rok → 5%, od 3 000 zł → 10%, od 5 000 zł → 15%, od 10 000 zł → 20%.' ),
		),
	);
	if ( $set === '' ) {
		return array_merge( $sets['dostawa'], $sets['produkt'], $sets['sklep'] );
	}
	return $sets[ $set ] ?? array();
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_faq', function ( $atts ) {
		$atts  = shortcode_atts( array( 'set' => '', 'title' => '' ), $atts, 'mnsk7_faq' );
		$items = mnsk7_get_faq_set( sanitize_key( $atts['set'] ) );
		$items = apply_filters( 'mnsk7_faq_items_custom', $items, $atts['set'] );
		if ( empty( $items ) ) return '';

		$html = '<section class="mnsk7-faq">';
		if ( $atts['title'] ) {
			$html .= '<h2 class="mnsk7-faq__title">' . esc_html( sanitize_text_field( $atts['title'] ) ) . '</h2>';
		}
		$html .= '<dl class="mnsk7-faq__list">';
		foreach ( $items as $item ) {
			if ( empty( $item['q'] ) || empty( $item['a'] ) ) continue;
			$html .= '<div class="mnsk7-faq__item"><dt class="mnsk7-faq__q">' . esc_html( $item['q'] ) . '</dt><dd class="mnsk7-faq__a">' . wp_kses_post( $item['a'] ) . '</dd></div>';
		}
		$html .= '</dl></section>';

		$schema = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => array() );
		foreach ( $items as $item ) {
			if ( empty( $item['q'] ) || empty( $item['a'] ) ) continue;
			$schema['mainEntity'][] = array(
				'@type' => 'Question',
				'name'  => wp_strip_all_tags( $item['q'] ),
				'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $item['a'] ) ),
			);
		}
		$html .= '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>';
		return $html;
	} );
}, 6 );

/* Akordeon FAQ (vanilla JS) — strony, kategorie produktów, artykuły Przewodnik */
add_action( 'wp_footer', function () {
	if ( ! is_singular( 'page' ) && ! is_product_category() && ! is_singular( 'post' ) ) {
		return;
	}
	?>
	<script>
	(function(){
		document.querySelectorAll('.mnsk7-faq__item').forEach(function(item){
			var dt = item.querySelector('.mnsk7-faq__q');
			if ( dt ) dt.addEventListener('click', function(){ item.classList.toggle('is-open'); });
		});
	})();
	</script>
	<?php
}, 20 );
