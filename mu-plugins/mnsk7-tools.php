<?php
/**
 * Plugin Name: MNK7 Tools (MU)
 * Description: Biznesowa logika projektu mnsk7-tools.pl — filtry, helpery, customizacje Woo. Nie zależy od motywu.
 * Author: Projekt mnsk7-tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// P0-03: blokada xmlrpc.php (bezpieczeństwo)
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit;
}

define( 'MNK7_TOOLS_VERSION', '1.0.0' );

/**
 * Lista atrybutów wyświetlanych w bloku "Kluczowe parametry" w karcie produktu.
 * Klucz = slug atrybutu (Woo: pa_* dla globalnych), wartość = etykieta.
 */
function mnsk7_get_key_param_attributes() {
	return array(
		'srednica'       => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'pa_srednica'    => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'fi'             => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'pa_fi'          => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'dlugosc-robocza-h' => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-calkowita-l' => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-calkowita'   => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-robocza'    => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-czesci-roboczej' => __( 'Długość części roboczej', 'mnsk7-tools' ),
		'r'              => __( 'Promień R', 'mnsk7-tools' ),
		'pa_r'           => __( 'Promień R', 'mnsk7-tools' ),
		'typ'            => __( 'Typ', 'mnsk7-tools' ),
		'pa_typ'         => __( 'Typ', 'mnsk7-tools' ),
		'ksztalt'        => __( 'Kształt', 'mnsk7-tools' ),
		'zastosowanie'   => __( 'Zastosowanie', 'mnsk7-tools' ),
		'pa_zastosowanie' => __( 'Zastosowanie', 'mnsk7-tools' ),
	);
}

/**
 * Wyświetla blok kluczowych parametrów w karcie produktu (S2-04).
 */
function mnsk7_single_product_key_params() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$labels = mnsk7_get_key_param_attributes();
	$found  = array();

	foreach ( array_keys( $labels ) as $slug ) {
		$val = $product->get_attribute( $slug );
		if ( $val !== '' && $val !== null ) {
			$label = $labels[ $slug ];
			if ( ! isset( $found[ $label ] ) ) {
				$found[ $label ] = $val;
			}
		}
	}

	if ( empty( $found ) ) {
		return;
	}

	echo '<div class="mnsk7-product-key-params">';
	echo '<h4 class="mnsk7-product-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-tools' ) . '</h4>';
	echo '<dl class="mnsk7-product-key-params__list">';
	foreach ( $found as $label => $value ) {
		echo '<dt>' . esc_html( $label ) . '</dt>';
		echo '<dd>' . esc_html( $value ) . '</dd>';
	}
	echo '</dl></div>';
}

/**
 * Wyświetla blok "Podstaw dla" w karcie produktu (S2-05).
 */
function mnsk7_single_product_zastosowanie() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$val = $product->get_attribute( 'zastosowanie' );
	if ( $val === '' || $val === null ) {
		$val = $product->get_attribute( 'pa_zastosowanie' );
	}
	if ( $val === '' || $val === null ) {
		return;
	}

	echo '<div class="mnsk7-product-zastosowanie">';
	echo '<h4 class="mnsk7-product-zastosowanie__title">' . esc_html__( 'Do czego / Zastosowanie', 'mnsk7-tools' ) . '</h4>';
	echo '<p class="mnsk7-product-zastosowanie__text">' . esc_html( $val ) . '</p>';
	echo '</div>';
}

/**
 * Wyświetla dostępność w magazynie (S2-10).
 */
function mnsk7_single_product_availability() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$availability = $product->get_availability();
	$class        = ! empty( $availability['class'] ) ? $availability['class'] : '';
	$text         = ! empty( $availability['availability'] ) ? $availability['availability'] : ( $product->is_in_stock() ? __( 'W magazynie', 'mnsk7-tools' ) : __( 'Na zamówienie', 'mnsk7-tools' ) );

	echo '<p class="mnsk7-product-availability ' . esc_attr( $class ) . '">' . esc_html( $text ) . '</p>';
}

/**
 * Wyświetla informację o dostawie i fakturze VAT (S2-11) — w karcie produktu i shortcode.
 */
function mnsk7_dostawa_vat_html() {
	return '<p class="mnsk7-dostawa-vat">'
		. esc_html__( 'Dostawa następnego dnia. Faktura VAT dostępna na życzenie.', 'mnsk7-tools' )
		. '</p>';
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_dostawa_vat', function () {
		return mnsk7_dostawa_vat_html();
	} );
}, 5 );

// W karcie produktu: po bloku "Do czego" pokazujemy dostawę i VAT
add_action( 'woocommerce_single_product_summary', function () {
	echo mnsk7_dostawa_vat_html();
}, 35 );

// W stopce: dostawa + VAT na stronach sklepu i produktu
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product() && ! is_cart() && ! is_checkout() ) ) {
		return;
	}
	echo '<div class="mnsk7-footer-dostawa-vat">' . mnsk7_dostawa_vat_html() . '</div>';
}, 5 );

/**
 * S2-06: placeholder na schemat parametrów lub wideo w karcie produktu.
 * Gdy dodasz zdjęcie schematu / wideo — wyświetl je tutaj lub usuń wywołanie.
 */
function mnsk7_single_product_schema_video_placeholder() {
	// Opcjonalnie: echo '<p class="mnsk7-schema-video-placeholder" style="color:#999;font-size:0.9em;">Miejsce na schemat parametrów lub wideo.</p>';
	// Na razie puste — blok jest w szablonie, można dodać treść per produkt (np. custom field).
}

/**
 * Kolejność atrybutów do filtrów katalogu (S2-02). Użyj w pluginie filtrów lub w sidebarze.
 * Typ → średnica → trzpień → długość → zastosowanie.
 */
function mnsk7_get_filter_attribute_order() {
	return array( 'typ', 'srednica', 'fi', 'dlugosc-robocza-h', 'dlugosc-calkowita-l', 'zastosowanie' );
}

/**
 * Shortcode: rating sklepu (S2-09). Placeholder pod Allegro lub przyszłe opinie.
 * Użycie: [mnsk7_rating] lub [mnsk7_rating url="https://allegro.pl/..." title="Nasz sklep"]
 */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_rating', function ( $atts ) {
		$atts = shortcode_atts( array(
			'url'   => '',
			'title' => __( 'Sprawdź opinie o naszym sklepie na Allegro', 'mnsk7-tools' ),
		), $atts, 'mnsk7_rating' );
		$url = esc_url( $atts['url'] );
		if ( $url === '' ) {
			return '<p class="mnsk7-store-rating">' . esc_html( $atts['title'] ) . '</p>';
		}
		return '<p class="mnsk7-store-rating"><a href="' . $url . '" target="_blank" rel="noopener">' . esc_html( $atts['title'] ) . '</a></p>';
	} );
}, 6 );

/**
 * Shortcode: blok popularnych / hitów (S2-07). Na głównej: [mnsk7_bestsellers].
 * Domyślnie 4 produkty po popularności (orderby=popularity); można nadpisać atrybutami.
 */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_bestsellers', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit'   => 4,
			'orderby' => 'popularity',
			'title'   => __( 'Polecane / Bestsellery', 'mnsk7-tools' ),
		), $atts, 'mnsk7_bestsellers' );
		$shortcode = sprintf(
			'[products limit="%d" orderby="%s" columns="4"]',
			(int) $atts['limit'],
			sanitize_key( $atts['orderby'] )
		);
		return '<section class="mnsk7-bestsellers">'
			. '<h2 class="mnsk7-bestsellers-title">' . esc_html( $atts['title'] ) . '</h2>'
			. do_shortcode( $shortcode )
			. '</section>';
	} );
}, 6 );
