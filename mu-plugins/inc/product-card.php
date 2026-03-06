<?php
/**
 * MNK7 Tools — bloki karty produktu (parametry, zastosowanie, dostępność, trust badges).
 * Podpięte przez hooki WooCommerce — nie wymaga edycji szablonów.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Krótkie etykiety dla tabeli key params (design contract: Średnica robocza, Trzpień).
 *
 * @return array [ 'długa etykieta' => 'krótka etykieta' ]
 */
function mnsk7_get_key_param_short_labels() {
	return array(
		__( 'Średnica części roboczej', 'mnsk7-tools' ) => __( 'Średnica robocza', 'mnsk7-tools' ),
		__( 'Średnica trzpienia', 'mnsk7-tools' )       => __( 'Trzpień', 'mnsk7-tools' ),
		__( 'Liczba zębów', 'mnsk7-tools' )             => __( 'Ilość ostrzy', 'mnsk7-tools' ),
		__( 'Materiał obróbki', 'mnsk7-tools' )         => __( 'Materiał', 'mnsk7-tools' ),
		__( 'Typ operacji', 'mnsk7-tools' )             => __( 'Typ', 'mnsk7-tools' ),
		__( 'Chwyt / trzpienie', 'mnsk7-tools' )        => __( 'Trzpień', 'mnsk7-tools' ),
		__( 'Trzpienie / chwyt', 'mnsk7-tools' )        => __( 'Trzpień', 'mnsk7-tools' ),
	);
}

/**
 * Key product attributes for catalog display (content_catalog_rules).
 * Min set: material, typ operacji, średnica, długość, chwyt/trzpienia, pokrycie, liczba zębów.
 */
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
		'material'                => __( 'Materiał obróbki', 'mnsk7-tools' ),
		'pa_material'             => __( 'Materiał obróbki', 'mnsk7-tools' ),
		'typ-operacji'            => __( 'Typ operacji', 'mnsk7-tools' ),
		'pa_typ-operacji'         => __( 'Typ operacji', 'mnsk7-tools' ),
		'pokrycie'                => __( 'Pokrycie', 'mnsk7-tools' ),
		'pa_pokrycie'             => __( 'Pokrycie', 'mnsk7-tools' ),
		'liczba-zebow'            => __( 'Liczba zębów', 'mnsk7-tools' ),
		'pa_liczba-zebow'         => __( 'Liczba zębów', 'mnsk7-tools' ),
		'chwyt'                   => __( 'Chwyt / trzpienie', 'mnsk7-tools' ),
		'pa_chwyt'                => __( 'Chwyt / trzpienie', 'mnsk7-tools' ),
		'trzpienie'               => __( 'Trzpienie / chwyt', 'mnsk7-tools' ),
		'pa_trzpienie'            => __( 'Trzpienie / chwyt', 'mnsk7-tools' ),
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

	$labels = mnsk7_get_key_param_attributes();
	$found  = array(); // slug => [ 'label' => long label, 'value' => value ]
	foreach ( array_keys( $labels ) as $slug ) {
		$val = $product->get_attribute( $slug );
		if ( $val !== '' && $val !== null && ! isset( $found[ $slug ] ) ) {
			$found[ $slug ] = array( 'label' => $labels[ $slug ], 'value' => $val );
		}
	}

	if ( empty( $found ) ) {
		$parsed = mnsk7_parse_excerpt_params( $product );
		foreach ( $parsed as $label => $value ) {
			$found[ 'excerpt_' . sanitize_title( $label ) ] = array( 'label' => $label, 'value' => $value );
		}
	}

	if ( empty( $found ) ) {
		return;
	}

	$is_variable = $product->is_type( 'variable' );
	$var_attrs   = $is_variable ? $product->get_variation_attributes() : array();
	$short_labels = function_exists( 'mnsk7_get_key_param_short_labels' ) ? mnsk7_get_key_param_short_labels() : array();

	echo '<div class="mnsk7-product-key-params">';
	echo '<h4 class="mnsk7-product-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-tools' ) . '</h4>';
	echo '<dl class="mnsk7-product-key-params__list">';
	foreach ( $found as $slug => $item ) {
		$long_label = $item['label'];
		$value      = $item['value'];
		$display_label = isset( $short_labels[ $long_label ] ) ? $short_labels[ $long_label ] : $long_label;
		echo '<dt>' . esc_html( $display_label ) . '</dt>';
		$is_var_attr = $is_variable && strpos( $slug, 'excerpt_' ) !== 0 && isset( $var_attrs[ $slug ] );
		if ( $is_var_attr && ! empty( $var_attrs[ $slug ] ) ) {
			$name = 'attribute_' . esc_attr( $slug );
			echo '<dd class="mnsk7-product-key-params__dd--select">';
			echo '<select name="' . $name . '" data-attribute_name="' . esc_attr( $slug ) . '" class="mnsk7-key-param-select">';
			echo '<option value="">' . esc_html__( 'Wybierz', 'mnsk7-tools' ) . '</option>';
			foreach ( $var_attrs[ $slug ] as $opt_val ) {
				$opt_label = get_term_by( 'slug', $opt_val, $slug ) ? get_term_by( 'slug', $opt_val, $slug )->name : $opt_val;
				$selected  = ( $opt_val === $value || sanitize_title( $opt_label ) === sanitize_title( $value ) ) ? ' selected' : '';
				echo '<option value="' . esc_attr( $opt_val ) . '"' . $selected . '>' . esc_html( $opt_label ) . '</option>';
			}
			echo '</select></dd>';
		} else {
			echo '<dd>' . esc_html( $value ) . '</dd>';
		}
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
	echo '<div class="mnsk7-product-availability-row">';
	echo '<p class="mnsk7-product-availability ' . esc_attr( $class ) . '">'
		. '<i class="mnsk7-product-trust__badge-icon">&#10003;</i> '
		. esc_html( $text )
		. '</p>';
	$sales = (int) $product->get_total_sales();
	if ( $sales >= 5 ) {
		echo '<span class="mnsk7-product-trust__badge mnsk7-product-trust__badge--sales">'
			. '<i class="mnsk7-product-trust__badge-icon" aria-hidden="true">&#9733;</i> '
			. sprintf( _n( '%d osoba kupiła', '%d osób kupiło', $sales, 'mnsk7-tools' ), $sales )
			. '</span>';
	}
	echo '</div>';
}

function mnsk7_single_product_trust_badges() {
	global $product;
	$min    = number_format_i18n( MNK7_FREE_SHIPPING_MIN, 0 );
	$eta    = function_exists( 'mnsk7_delivery_eta_badge_label' ) ? mnsk7_delivery_eta_badge_label() : __( 'Dostawa jutro', 'mnsk7-tools' );
	$badges = array(
		$eta,
		__( 'Faktura VAT', 'mnsk7-tools' ),
		sprintf( __( 'Darmowa dostawa od %s zł', 'mnsk7-tools' ), $min ),
		__( 'Zwroty 30 dni', 'mnsk7-tools' ),
	);
	echo '<div class="mnsk7-product-trust">';
	foreach ( $badges as $badge ) {
		echo '<span class="mnsk7-product-trust__badge"><i class="mnsk7-product-trust__badge-icon" aria-hidden="true">&#10003;</i>' . esc_html( $badge ) . '</span>';
	}
	echo '</div>';
}

function mnsk7_single_product_schema_video_placeholder() {
	return '';
}

/*
 * WooCommerce domyślnie wyświetla "X w magazynie" wewnątrz formularza "Dodaj do koszyka"
 * (woocommerce_get_stock_html). Wyłączamy to żeby nie duplikować z naszym badgem.
 */
add_filter( 'woocommerce_get_stock_html', function ( $html ) {
	if ( is_singular( 'product' ) ) {
		return ''; // nasz mnsk7_single_product_availability() obsługuje to na priority 8
	}
	return $html;
} );

add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_availability', 8 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_key_params', 21 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_zastosowanie', 23 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_trust_badges', 32 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_meta_chips', 40 );

add_action( 'woocommerce_before_single_product', function () {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 15 );
	// Bez listy zakładek (Opis | Opinie) — tylko harmonijka „Pokaż opis”.
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'mnsk7_single_product_description_accordion_block', 10 );
}, 5 );

function mnsk7_single_product_description_accordion_block() {
	mnsk7_product_description_accordion();
}

function mnsk7_single_product_meta_chips() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$cats = wc_get_product_category_list( $product->get_id(), '' );
	$tags = wc_get_product_tag_list( $product->get_id(), '' );
	// Zamień link "Sklep" na "Katalog" (czytelniejsza etykieta)
	if ( $cats ) {
		$cats = str_replace( '>Sklep<', '>' . esc_html__( 'Katalog', 'mnsk7-tools' ) . '<', $cats );
	}
	if ( ! $cats && ! $tags ) {
		return;
	}
	echo '<div class="mnsk7-product-meta-chips">';
	if ( $cats ) {
		echo '<div class="mnsk7-product-meta-chips__row">' . $cats . '</div>';
	}
	if ( $tags ) {
		echo '<div class="mnsk7-product-meta-chips__row">' . $tags . '</div>';
	}
	echo '</div>';
}

/**
 * On single product pages, completely suppress the short description (excerpt)
 * to avoid duplicating info already shown in key_params block and description tab.
 */
add_filter( 'woocommerce_short_description', function ( $excerpt ) {
	if ( is_singular( 'product' ) ) {
		return '';
	}
	return $excerpt;
}, 99 );

/**
 * Ukryj zakładkę "Informacje dodatkowe" (parametry są w bloku Kluczowe parametry przy zdjęciu).
 * Ukryj zakładkę "Opinie" gdy brak recenzji (0).
 */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['additional_information'] );
	$post = get_post();
	if ( $post && (int) $post->comment_count === 0 && isset( $tabs['reviews'] ) ) {
		unset( $tabs['reviews'] );
	}
	return $tabs;
}, 20 );

/**
 * Opis produktu jako harmonijka (domyślnie zwinięty).
 */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['callback'] = 'mnsk7_product_description_accordion';
	}
	return $tabs;
}, 25 );

function mnsk7_product_description_accordion() {
	$content = get_the_content( null, false, get_the_ID() );
	if ( trim( $content ) === '' ) {
		return;
	}
	echo '<details class="mnsk7-product-description-accordion">';
	echo '<summary class="mnsk7-product-description-accordion__summary">' . esc_html__( 'Pokaż opis', 'mnsk7-tools' ) . '</summary>';
	echo '<div class="mnsk7-product-description-accordion__content">' . apply_filters( 'the_content', $content ) . '</div>';
	echo '</details>';
}
