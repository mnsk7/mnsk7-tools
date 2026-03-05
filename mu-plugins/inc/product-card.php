<?php
/**
 * MNK7 Tools — bloki karty produktu (parametry, zastosowanie, dostępność, trust badges).
 * Podpięte przez hooki WooCommerce — nie wymaga edycji szablonów.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

function mnsk7_get_key_param_attributes() {
	return array(
		'srednica'                => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'pa_srednica'             => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'fi'                      => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'pa_fi'                   => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'dlugosc-robocza-h'       => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-calkowita-l'     => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-calkowita'       => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-robocza'         => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-czesci-roboczej' => __( 'Długość części roboczej', 'mnsk7-tools' ),
		'r'                       => __( 'Promień R', 'mnsk7-tools' ),
		'pa_r'                    => __( 'Promień R', 'mnsk7-tools' ),
		'typ'                     => __( 'Typ', 'mnsk7-tools' ),
		'pa_typ'                  => __( 'Typ', 'mnsk7-tools' ),
		'ksztalt'                 => __( 'Kształt', 'mnsk7-tools' ),
		'zastosowanie'            => __( 'Zastosowanie', 'mnsk7-tools' ),
		'pa_zastosowanie'         => __( 'Zastosowanie', 'mnsk7-tools' ),
	);
}

/**
 * Parsuje krótki opis produktu i wyciąga parametry w formacie "Nazwa (X) = wartość"
 * lub "Nazwa = wartość". Fallback gdy WC-atrybuty nie są ustawione.
 *
 * @param WC_Product $product
 * @return array Tablica ['etykieta' => 'wartość']
 */
function mnsk7_parse_excerpt_params( $product ) {
	$excerpt = $product->get_short_description();
	if ( empty( $excerpt ) ) {
		return array();
	}

	$text   = wp_strip_all_tags( $excerpt );
	$lines  = preg_split( '/[\n\r<br>]+/', $text );
	$params = array();

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		/* Format: "Etykieta (X) = wartość mm" lub "Etykieta = wartość" */
		if ( preg_match( '/^([^\=]+?)\s*=\s*(.+)$/', $line, $m ) ) {
			$label = trim( preg_replace( '/\s*\([A-Z]+\)\s*$/', '', trim( $m[1] ) ) );
			$value = trim( $m[2] );
			if ( $label !== '' && $value !== '' && strlen( $label ) < 60 ) {
				/* Unikamy duplikatów */
				$params[ $label ] = $value;
			}
		}
	}

	return $params;
}

function mnsk7_single_product_key_params() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	/* 1. Próbujemy WC-atrybuty (priorytet) */
	$labels = mnsk7_get_key_param_attributes();
	$found  = array();
	foreach ( array_keys( $labels ) as $slug ) {
		$val = $product->get_attribute( $slug );
		if ( $val !== '' && $val !== null && ! isset( $found[ $labels[ $slug ] ] ) ) {
			$found[ $labels[ $slug ] ] = $val;
		}
	}

	/* 2. Fallback: parsowanie krótkiego opisu */
	if ( empty( $found ) ) {
		$found = mnsk7_parse_excerpt_params( $product );
	}

	if ( empty( $found ) ) {
		return;
	}

	echo '<div class="mnsk7-product-key-params">';
	echo '<h4 class="mnsk7-product-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-tools' ) . '</h4>';
	echo '<dl class="mnsk7-product-key-params__list">';
	foreach ( $found as $label => $value ) {
		echo '<dt>' . esc_html( $label ) . '</dt><dd>' . esc_html( $value ) . '</dd>';
	}
	echo '</dl></div>';
}

function mnsk7_single_product_zastosowanie() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$val = $product->get_attribute( 'zastosowanie' ) ?: $product->get_attribute( 'pa_zastosowanie' );
	if ( empty( $val ) ) {
		return;
	}
	echo '<div class="mnsk7-product-zastosowanie">';
	echo '<h4 class="mnsk7-product-zastosowanie__title">' . esc_html__( 'Do czego / Zastosowanie', 'mnsk7-tools' ) . '</h4>';
	echo '<p class="mnsk7-product-zastosowanie__text">' . esc_html( $val ) . '</p>';
	echo '</div>';
}

function mnsk7_single_product_availability() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$availability = $product->get_availability();
	$class        = ! empty( $availability['class'] ) ? $availability['class'] : ( $product->is_in_stock() ? 'in-stock' : 'out-of-stock' );
	$text         = ! empty( $availability['availability'] ) ? $availability['availability'] : ( $product->is_in_stock() ? __( 'W magazynie', 'mnsk7-tools' ) : __( 'Na zamówienie', 'mnsk7-tools' ) );
	echo '<p class="mnsk7-product-availability ' . esc_attr( $class ) . '">'
		. '<i class="mnsk7-product-trust__badge-icon">&#10003;</i> '
		. esc_html( $text )
		. '</p>';
}

function mnsk7_single_product_trust_badges() {
	global $product;
	$min    = number_format_i18n( MNK7_FREE_SHIPPING_MIN, 0 );
	$badges = array(
		__( 'Dostawa jutro', 'mnsk7-tools' ),
		__( 'Faktura VAT', 'mnsk7-tools' ),
		sprintf( __( 'Darmowa dostawa od %s zł', 'mnsk7-tools' ), $min ),
		__( 'Zwroty 30 dni', 'mnsk7-tools' ),
	);
	echo '<div class="mnsk7-product-trust">';
	foreach ( $badges as $badge ) {
		echo '<span class="mnsk7-product-trust__badge"><i class="mnsk7-product-trust__badge-icon" aria-hidden="true">&#10003;</i>' . esc_html( $badge ) . '</span>';
	}
	if ( is_a( $product, 'WC_Product' ) ) {
		$sales = (int) $product->get_total_sales();
		if ( $sales >= 5 ) {
			echo '<span class="mnsk7-product-trust__badge mnsk7-product-trust__badge--sales">'
				. '<i class="mnsk7-product-trust__badge-icon" aria-hidden="true">&#9733;</i>'
				. sprintf( _n( '%d osoba kupiła', '%d osób kupiło', $sales, 'mnsk7-tools' ), $sales )
				. '</span>';
		}
	}
	echo '</div>';
}

function mnsk7_single_product_schema_video_placeholder() {
	return '';
}

/* Hooki WooCommerce summary (priority):
 *  8  → availability
 * 21  → key_params
 * 23  → zastosowanie
 * 32  → trust_badges
 */
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_availability', 8 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_key_params', 21 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_zastosowanie', 23 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_trust_badges', 32 );

/**
 * Jeśli parametry są wyciągane z krótkiego opisu przez nasz fallback parser,
 * zastępujemy wyjście krótkiego opisu (excerpt) aby uniknąć duplikatu "Wymiary: = ..."
 * Tylko gdy parsowanie znalazło ≥2 wiersze "label = value".
 */
add_filter( 'woocommerce_short_description', 'mnsk7_clean_excerpt_when_params_parsed', 10 );
function mnsk7_clean_excerpt_when_params_parsed( $excerpt ) {
	if ( ! is_singular( 'product' ) ) {
		return $excerpt;
	}
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return $excerpt;
	}

	/* Sprawdź czy WC-atrybuty są dostępne — jeśli tak, excerpt pozostaje bez zmian */
	$labels = mnsk7_get_key_param_attributes();
	foreach ( array_keys( $labels ) as $slug ) {
		if ( $product->get_attribute( $slug ) !== '' ) {
			return $excerpt;
		}
	}

	/* Fallback był użyty — sprawdź czy excerpt zawiera głównie linie "X = Y" */
	$parsed = mnsk7_parse_excerpt_params( $product );
	if ( count( $parsed ) < 2 ) {
		return $excerpt;
	}

	/*
	 * Usuwamy blok wymiarów z excerpta (linie "coś = wartość"),
	 * zostawiając tylko sekcje takie jak "Kluczowe cechy:" i inne teksty.
	 */
	$text  = wp_strip_all_tags( $excerpt );
	$lines = preg_split( '/[\n\r]+/', $text );
	$keep  = array();
	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		/* Opuść linie w stylu "Etykieta = wartość" i "Wymiary:" */
		if ( preg_match( '/^[^\=]+=\s*.+$/', $line ) ) {
			continue;
		}
		if ( preg_match( '/^Wymiary\s*:?\s*$/i', $line ) ) {
			continue;
		}
		$keep[] = $line;
	}

	if ( empty( $keep ) ) {
		return '';
	}

	return '<p>' . implode( '</p><p>', array_map( 'esc_html', $keep ) ) . '</p>';
}
