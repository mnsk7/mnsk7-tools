<?php
/**
 * Plugin Name: MNK7 Catalog Core (MU)
 * Description: Filtry katalogu bez pluginów (URL params + WP_Query), helper key spec line dla kart produktu.
 * Author: mnsk7-tools.pl
 *
 * @package mnsk7-catalog-core
 */

defined( 'ABSPATH' ) || exit;

/**
 * Mapowanie parametrów URL na taksonomie WooCommerce (pa_*).
 * Klucz = nazwa GET, wartość = taxonomy.
 */
function mnsk7_catalog_filter_param_taxonomies() {
	return array(
		'diameter' => 'pa_srednica',
		'shank'    => 'pa_fi',
		'type'     => 'pa_typ',
		'material' => 'pa_material',
	);
}

/**
 * Pobiera przefiltrowane parametry z URL (sanitized, allowlist przez term_exists).
 *
 * @return array [ 'param_name' => 'term_slug' ]
 */
function mnsk7_catalog_get_active_filters() {
	$param_tax = mnsk7_catalog_filter_param_taxonomies();
	$out      = array();

	foreach ( $param_tax as $param => $taxonomy ) {
		$raw = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
		if ( $raw === '' ) {
			continue;
		}
		// Allowlist: tylko jeśli taki term istnieje w danej taksonomii
		if ( term_exists( $raw, $taxonomy ) ) {
			$out[ $param ] = $raw;
		}
	}

	return $out;
}

/**
 * Buduje tax_query dla WooCommerce product archive na podstawie aktywnych filtrów.
 *
 * @return array|null tax_query fragment lub null jeśli brak filtrów.
 */
function mnsk7_catalog_build_filter_tax_query() {
	$filters   = mnsk7_catalog_get_active_filters();
	$param_tax = mnsk7_catalog_filter_param_taxonomies();

	if ( empty( $filters ) ) {
		return null;
	}

	$tax_query = array( 'relation' => 'AND' );

	foreach ( $filters as $param => $term_slug ) {
		$taxonomy = $param_tax[ $param ];
		$tax_query[] = array(
			'taxonomy' => $taxonomy,
			'field'    => 'slug',
			'terms'    => array( $term_slug ),
		);
	}

	return $tax_query;
}

/**
 * Hook: pre_get_posts — nakładamy filtry tylko na główne zapytanie archiwum produktów.
 */
add_action( 'pre_get_posts', 'mnsk7_catalog_filter_main_query', 20 );
function mnsk7_catalog_filter_main_query( $query ) {
	if ( ! $query->is_main_query() ) {
		return;
	}
	if ( ! function_exists( 'is_shop' ) ) {
		return;
	}
	if ( ! is_shop() && ! is_product_taxonomy() && ! is_product_category() ) {
		return;
	}

	$tax_query = mnsk7_catalog_build_filter_tax_query();
	if ( $tax_query === null ) {
		return;
	}

	$existing = $query->get( 'tax_query' );
	if ( ! is_array( $existing ) ) {
		$existing = array();
	}
	$new = array( 'relation' => 'AND' );
	foreach ( $existing as $k => $q ) {
		if ( $k === 'relation' ) {
			continue;
		}
		if ( is_array( $q ) ) {
			$new[] = $q;
		}
	}
	foreach ( $tax_query as $k => $q ) {
		if ( $k === 'relation' ) {
			continue;
		}
		if ( is_array( $q ) ) {
			$new[] = $q;
		}
	}
	$query->set( 'tax_query', $new );
}

/**
 * URL archiwum bez parametrów filtrów („Clear filters”).
 *
 * @return string
 */
function mnsk7_catalog_clear_filters_url() {
	$param_tax = mnsk7_catalog_filter_param_taxonomies();
	$base     = wc_get_page_permalink( 'shop' );
	if ( is_product_category() ) {
		$queried = get_queried_object();
		if ( $queried && isset( $queried->slug ) && isset( $queried->taxonomy ) ) {
			$link = get_term_link( $queried );
			if ( ! is_wp_error( $link ) ) {
				$base = $link;
			}
		}
	}
	$params = array();
	foreach ( array_keys( $_GET ) as $key ) {
		if ( ! isset( $param_tax[ $key ] ) ) {
			$params[ $key ] = isset( $_GET[ $key ] ) ? sanitize_text_field( wp_unslash( $_GET[ $key ] ) ) : '';
		}
	}
	if ( empty( $params ) ) {
		return $base;
	}
	return add_query_arg( $params, $base );
}

/**
 * Jedna linia key spec dla karty produktu (PLP / related): np. "D=38 mm • S=8 mm • 4P".
 * Używa atrybutów Woo; fallback z mnsk7_get_key_param_attributes jeśli istnieje.
 *
 * @param WC_Product $product
 * @return string
 */
function mnsk7_get_product_key_spec_line( $product ) {
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return '';
	}

	$parts = array();

	// Priorytet: średnica, trzpień (fi), typ / liczba zębów
	$attrs = array( 'pa_srednica', 'srednica', 'pa_fi', 'fi', 'pa_typ', 'typ', 'pa_liczba-zebow', 'liczba-zebow' );
	$labels_short = array(
		'pa_srednica' => 'D',
		'srednica'    => 'D',
		'pa_fi'       => 'S',
		'fi'          => 'S',
	);

	foreach ( $attrs as $attr ) {
		$val = $product->get_attribute( $attr );
		if ( $val === '' || $val === null ) {
			continue;
		}
		$val = trim( $val );
		if ( $val === '' ) {
			continue;
		}
		$prefix = isset( $labels_short[ $attr ] ) ? $labels_short[ $attr ] . '=' : '';
		$parts[] = $prefix . $val;
	}

	if ( empty( $parts ) ) {
		return '';
	}

	return implode( ' • ', array_slice( $parts, 0, 4 ) );
}

/**
 * Filter UI: chips nad siatką (woocommerce_before_shop_loop).
 * v1: diameter + shank (pobierz termy z aktualnej kategorii / sklepu).
 */
add_action( 'woocommerce_before_shop_loop', 'mnsk7_catalog_render_filter_ui', 5 );
function mnsk7_catalog_render_filter_ui() {
	if ( ! function_exists( 'wc_get_loop_prop' ) ) {
		return;
	}

	$param_tax = mnsk7_catalog_filter_param_taxonomies();
	$active    = mnsk7_catalog_get_active_filters();
	$clear_url = mnsk7_catalog_clear_filters_url();

	$param_labels = array(
		'diameter' => __( 'Średnica', 'mnsk7-catalog-core' ),
		'shank'    => __( 'Trzpień', 'mnsk7-catalog-core' ),
		'type'     => __( 'Typ', 'mnsk7-catalog-core' ),
		'material' => __( 'Materiał', 'mnsk7-catalog-core' ),
	);

	echo '<div class="mnsk7-catalog-filters">';
	echo '<span class="mnsk7-catalog-filters__label">' . esc_html__( 'Filtruj:', 'mnsk7-catalog-core' ) . '</span> ';

	foreach ( array( 'diameter', 'shank', 'type', 'material' ) as $param ) {
		$taxonomy = $param_tax[ $param ];
		$terms = get_terms( array(
			'taxonomy'   => $taxonomy,
			'hide_empty' => true,
		) );

		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}

		$label = isset( $param_labels[ $param ] ) ? $param_labels[ $param ] : $param;
		echo '<span class="mnsk7-catalog-filters__group">';
		echo '<span class="mnsk7-catalog-filters__group-label">' . esc_html( $label ) . ':</span> ';

		foreach ( $terms as $term ) {
			$url = add_query_arg( $param, $term->slug );
			$is_active = isset( $active[ $param ] ) && $active[ $param ] === $term->slug;
			$class = 'mnsk7-catalog-filters__chip' . ( $is_active ? ' mnsk7-catalog-filters__chip--active' : '' );
			echo '<a href="' . esc_url( $url ) . '" class="' . esc_attr( $class ) . '">' . esc_html( $term->name ) . '</a> ';
		}
		echo '</span>';
	}

	if ( ! empty( $active ) ) {
		echo ' <a href="' . esc_url( $clear_url ) . '" class="mnsk7-catalog-filters__clear">' . esc_html__( 'Wyczyść filtry', 'mnsk7-catalog-core' ) . '</a>';
	}

	echo '</div>';
}
