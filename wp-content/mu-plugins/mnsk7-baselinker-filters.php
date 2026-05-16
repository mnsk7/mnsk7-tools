<?php
/**
 * Plugin Name: MNSK7 — BaseLinker-ready PLP filters
 * Description: Issue #27 v1: Allegro-like faceted filters prepared for BaseLinker feature data synced into WooCommerce attributes.
 *
 * @package mnsk7
 */

defined( 'ABSPATH' ) || exit;

function mnsk7_bl_filters_is_plp() {
	if ( is_admin() || ! function_exists( 'is_shop' ) ) {
		return false;
	}
	return ( is_shop() || is_product_taxonomy() ) && ! is_search();
}

function mnsk7_bl_filters_get_param_value( $param ) {
	if ( empty( $_GET[ $param ] ) || is_array( $_GET[ $param ] ) ) {
		return '';
	}
	return sanitize_title( wp_unslash( $_GET[ $param ] ) );
}

/**
 * BaseLinker feature aliases -> Woo attribute taxonomy candidates.
 * BaseLinker product features should be synced into these Woo attributes.
 */
function mnsk7_bl_filters_definitions() {
	return apply_filters( 'mnsk7_bl_filters_definitions', array(
		'material' => array(
			'label'       => __( 'Materiał', 'mnsk7-storefront' ),
			'param'       => 'blf_material',
			'priority'    => 10,
			'taxonomies'  => array( 'pa_material', 'pa_material-obrobki', 'pa_materialy', 'pa_zastosowanie', 'pa_obrabiany-material' ),
			'bl_features' => array( 'Materiał', 'Material', 'Obrabiany materiał', 'Zastosowanie' ),
		),
		'shank' => array(
			'label'       => __( 'Średnica trzpienia', 'mnsk7-storefront' ),
			'param'       => 'blf_trzpien',
			'priority'    => 20,
			'taxonomies'  => array( 'pa_srednica-trzpienia', 'pa_srednica_trzpienia', 'pa_wymiary-trzpienia', 'pa_trzpien' ),
			'bl_features' => array( 'Średnica trzpienia', 'Srednica trzpienia', 'Trzpień', 'Trzpien' ),
		),
		'work_diameter' => array(
			'label'       => __( 'Średnica robocza', 'mnsk7-storefront' ),
			'param'       => 'blf_srednica',
			'priority'    => 30,
			'taxonomies'  => array( 'pa_srednica', 'pa_srednica-robocza', 'pa_fi' ),
			'bl_features' => array( 'Średnica robocza', 'Srednica robocza', 'Średnica', 'Srednica', 'Fi', 'FI' ),
		),
		'work_length' => array(
			'label'       => __( 'Długość robocza', 'mnsk7-storefront' ),
			'param'       => 'blf_dlugosc_robocza',
			'priority'    => 40,
			'taxonomies'  => array( 'pa_dlugosc-robocza', 'pa_dlugosc-robocza-h', 'pa_dlugosc-czesci-roboczej' ),
			'bl_features' => array( 'Długość robocza', 'Dlugosc robocza', 'Długość części roboczej', 'Dlugosc czesci roboczej' ),
		),
		'overall_length' => array(
			'label'       => __( 'Długość całkowita', 'mnsk7-storefront' ),
			'param'       => 'blf_dlugosc_calkowita',
			'priority'    => 50,
			'taxonomies'  => array( 'pa_dlugosc-calkowita', 'pa_dlugosc-calkowita-l' ),
			'bl_features' => array( 'Długość całkowita', 'Dlugosc calkowita', 'Długość całkowita L', 'Dlugosc calkowita L' ),
		),
		'type' => array(
			'label'       => __( 'Typ narzędzia', 'mnsk7-storefront' ),
			'param'       => 'blf_typ',
			'priority'    => 60,
			'taxonomies'  => array( 'pa_typ', 'pa_typ-frezu', 'pa_typ-pilnika', 'pa_rodzaj' ),
			'bl_features' => array( 'Typ', 'Typ frezu', 'Typ pilnika', 'Rodzaj' ),
		),
		'teeth' => array(
			'label'       => __( 'Liczba ostrzy', 'mnsk7-storefront' ),
			'param'       => 'blf_ostrza',
			'priority'    => 70,
			'taxonomies'  => array( 'pa_liczba-ostrz', 'pa_liczba-zebow', 'pa_ilosc-ostrz' ),
			'bl_features' => array( 'Liczba ostrzy', 'Ilość ostrzy', 'Ilosc ostrzy', 'Liczba zębów', 'Liczba zebow' ),
		),
	) );
}

function mnsk7_bl_filters_resolved_definitions() {
	$resolved = array();
	foreach ( mnsk7_bl_filters_definitions() as $key => $definition ) {
		if ( empty( $definition['param'] ) || empty( $definition['taxonomies'] ) || ! is_array( $definition['taxonomies'] ) ) {
			continue;
		}
		$taxonomy = '';
		foreach ( $definition['taxonomies'] as $candidate ) {
			$candidate = sanitize_key( (string) $candidate );
			if ( taxonomy_exists( $candidate ) ) {
				$taxonomy = $candidate;
				break;
			}
		}
		if ( '' === $taxonomy ) {
			continue;
		}
		$definition['key']      = (string) $key;
		$definition['taxonomy'] = $taxonomy;
		$definition['param']    = sanitize_key( (string) $definition['param'] );
		$definition['priority'] = isset( $definition['priority'] ) ? (int) $definition['priority'] : 100;
		$resolved[ $key ]       = $definition;
	}
	uasort( $resolved, function ( $left, $right ) { return (int) $left['priority'] <=> (int) $right['priority']; } );
	return $resolved;
}

function mnsk7_bl_filters_active_params() {
	$active = array();
	foreach ( mnsk7_bl_filters_resolved_definitions() as $definition ) {
		$value = mnsk7_bl_filters_get_param_value( $definition['param'] );
		if ( '' !== $value ) {
			$active[ $definition['param'] ] = $value;
		}
	}
	return $active;
}

function mnsk7_bl_filters_scope_product_ids( $exclude_param = '' ) {
	if ( ! mnsk7_bl_filters_is_plp() ) {
		return array();
	}
	$tax_query = array( 'relation' => 'AND' );
	$term = get_queried_object();
	if ( $term instanceof WP_Term && in_array( $term->taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
		$tax_query[] = array( 'taxonomy' => $term->taxonomy, 'field' => 'term_id', 'terms' => array( (int) $term->term_id ) );
	}
	foreach ( mnsk7_bl_filters_resolved_definitions() as $definition ) {
		$param = $definition['param'];
		if ( $exclude_param === $param ) {
			continue;
		}
		$value = mnsk7_bl_filters_get_param_value( $param );
		if ( '' === $value ) {
			continue;
		}
		$tax_query[] = array( 'taxonomy' => $definition['taxonomy'], 'field' => 'slug', 'terms' => array( $value ) );
	}
	$query = new WP_Query( array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => 1000,
		'no_found_rows'  => true,
		'tax_query'      => $tax_query,
		'meta_query'     => array( array( 'key' => '_stock_status', 'value' => 'instock' ) ),
	) );
	return $query->posts ? array_map( 'intval', $query->posts ) : array();
}

function mnsk7_bl_filters_sort_chips( $chips ) {
	uasort( $chips, function ( $left, $right ) {
		$left_num  = preg_match( '/\d+(?:[\.,]\d+)?/u', (string) $left, $m1 ) ? (float) str_replace( ',', '.', $m1[0] ) : null;
		$right_num = preg_match( '/\d+(?:[\.,]\d+)?/u', (string) $right, $m2 ) ? (float) str_replace( ',', '.', $m2[0] ) : null;
		if ( null !== $left_num && null !== $right_num && $left_num !== $right_num ) {
			return $left_num <=> $right_num;
		}
		return strnatcasecmp( (string) $left, (string) $right );
	} );
	return $chips;
}

function mnsk7_bl_filters_get_facets() {
	$facets = array();
	foreach ( mnsk7_bl_filters_resolved_definitions() as $definition ) {
		$product_ids = mnsk7_bl_filters_scope_product_ids( $definition['param'] );
		if ( empty( $product_ids ) ) {
			continue;
		}
		$terms = get_terms( array( 'taxonomy' => $definition['taxonomy'], 'hide_empty' => true, 'object_ids' => $product_ids, 'number' => 40, 'orderby' => 'name' ) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}
		$active_value = mnsk7_bl_filters_get_param_value( $definition['param'] );
		if ( count( $terms ) < 2 && '' === $active_value ) {
			continue;
		}
		$chips = array();
		foreach ( $terms as $term ) {
			$label = function_exists( 'mnsk7_normalize_archive_chip_label' ) ? mnsk7_normalize_archive_chip_label( $term->name, $definition['taxonomy'] ) : $term->name;
			if ( '' !== $label ) {
				$chips[ $term->slug ] = $label;
			}
		}
		if ( empty( $chips ) ) {
			continue;
		}
		$facets[] = array( 'label' => $definition['label'], 'param' => $definition['param'], 'active' => $active_value, 'chips' => mnsk7_bl_filters_sort_chips( $chips ) );
	}
	return $facets;
}

function mnsk7_bl_filters_chip_url( $param, $value ) {
	$url = remove_query_arg( array( 'paged', 'product-page' ) );
	$url = add_query_arg( $param, $value, $url );
	return function_exists( 'mnsk7_plp_anchor_results' ) ? mnsk7_plp_anchor_results( $url ) : $url;
}

function mnsk7_bl_filters_clear_url( $param = null ) {
	$params = array();
	foreach ( mnsk7_bl_filters_resolved_definitions() as $definition ) {
		$params[] = $definition['param'];
	}
	$params[] = 'paged';
	$params[] = 'product-page';
	if ( null !== $param ) {
		$params = array( $param, 'paged', 'product-page' );
	}
	$url = remove_query_arg( $params );
	return function_exists( 'mnsk7_plp_anchor_results' ) ? mnsk7_plp_anchor_results( $url ) : $url;
}

add_action( 'woocommerce_product_query', function ( $query ) {
	if ( ! mnsk7_bl_filters_is_plp() || ! is_object( $query ) || ! method_exists( $query, 'get' ) || ! method_exists( $query, 'set' ) ) {
		return;
	}
	$tax_query = $query->get( 'tax_query' );
	if ( ! is_array( $tax_query ) ) {
		$tax_query = array();
	}
	foreach ( mnsk7_bl_filters_resolved_definitions() as $definition ) {
		$value = mnsk7_bl_filters_get_param_value( $definition['param'] );
		if ( '' === $value ) {
			continue;
		}
		$tax_query[] = array( 'taxonomy' => $definition['taxonomy'], 'field' => 'slug', 'terms' => array( $value ) );
	}
	if ( ! empty( $tax_query ) ) {
		$query->set( 'tax_query', array_merge( array( 'relation' => 'AND' ), $tax_query ) );
	}
}, 30 );

add_action( 'woocommerce_shop_loop_header', function () {
	if ( ! mnsk7_bl_filters_is_plp() ) {
		return;
	}
	$facets = mnsk7_bl_filters_get_facets();
	if ( empty( $facets ) ) {
		return;
	}
	$active_params = mnsk7_bl_filters_active_params();
	echo '<section class="mnsk7-bl-filters col-full" aria-label="' . esc_attr__( 'Szybkie filtry produktów', 'mnsk7-storefront' ) . '">';
	echo '<div class="mnsk7-bl-filters__head"><h2 class="mnsk7-bl-filters__title">' . esc_html__( 'Filtry techniczne', 'mnsk7-storefront' ) . '</h2><p class="mnsk7-bl-filters__note">' . esc_html__( 'Opcje zawężają się dynamicznie do aktualnej listy produktów. Dane mogą pochodzić z cech BaseLinkera zsynchronizowanych do atrybutów WooCommerce.', 'mnsk7-storefront' ) . '</p></div>';
	foreach ( $facets as $facet ) {
		$active = isset( $facet['active'] ) ? (string) $facet['active'] : '';
		echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--attrs mnsk7-bl-filters__row col-full" role="navigation" aria-label="' . esc_attr( sprintf( __( 'Filtruj: %s', 'mnsk7-storefront' ), $facet['label'] ) ) . '">';
		echo '<span class="mnsk7-plp-chips__label">' . esc_html( $facet['label'] ) . '</span><div class="mnsk7-plp-chips__scroll">';
		foreach ( $facet['chips'] as $slug => $label ) {
			$is_active = $active === $slug;
			$url = $is_active ? mnsk7_bl_filters_clear_url( $facet['param'] ) : mnsk7_bl_filters_chip_url( $facet['param'], $slug );
			printf( '<a href="%1$s" class="mnsk7-plp-chip %2$s"%3$s>%4$s</a>', esc_url( $url ), $is_active ? 'mnsk7-plp-chip--active' : '', $is_active ? ' aria-current="page"' : '', esc_html( $label ) );
		}
		echo '</div></div>';
	}
	if ( ! empty( $active_params ) ) {
		echo '<div class="mnsk7-plp-selected mnsk7-bl-filters__selected col-full"><span class="mnsk7-plp-selected__label">' . esc_html__( 'Wybrane filtry BL:', 'mnsk7-storefront' ) . '</span>';
		foreach ( $facets as $facet ) {
			$param = $facet['param'];
			if ( empty( $active_params[ $param ] ) || empty( $facet['chips'][ $active_params[ $param ] ] ) ) {
				continue;
			}
			echo '<a href="' . esc_url( mnsk7_bl_filters_clear_url( $param ) ) . '" class="mnsk7-plp-chip mnsk7-plp-chip--active mnsk7-plp-chip--remove">' . esc_html( $facet['chips'][ $active_params[ $param ] ] ) . ' ×</a>';
		}
		echo '<a href="' . esc_url( mnsk7_bl_filters_clear_url() ) . '" class="button mnsk7-plp-reset">' . esc_html__( 'Wyczyść filtry BL', 'mnsk7-storefront' ) . '</a></div>';
	}
	echo '</section>';
}, 40 );

add_action( 'woocommerce_no_products_found', function () {
	if ( empty( mnsk7_bl_filters_active_params() ) ) {
		return;
	}
	remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
	echo '<div class="mnsk7-plp-empty col-full" role="status"><p class="mnsk7-plp-empty__text">' . esc_html__( 'Brak produktów dla wybranych filtrów technicznych.', 'mnsk7-storefront' ) . '</p><p class="mnsk7-plp-empty__hint">' . esc_html__( 'Usuń część parametrów albo wróć do pełnej listy.', 'mnsk7-storefront' ) . '</p><a href="' . esc_url( mnsk7_bl_filters_clear_url() ) . '" class="button mnsk7-plp-empty__cta">' . esc_html__( 'Wyczyść filtry BL', 'mnsk7-storefront' ) . '</a></div>';
}, 1 );

add_action( 'wp_enqueue_scripts', function () {
	$css = '.mnsk7-bl-filters{max-width:var(--content-max);margin:0 auto 1rem;padding:0 1.5rem;box-sizing:border-box}.mnsk7-bl-filters__head{display:flex;gap:.75rem;align-items:baseline;flex-wrap:wrap;margin:.5rem 0 .75rem}.mnsk7-bl-filters__title{margin:0;font-size:var(--fs-lg,1.125rem);line-height:1.25}.mnsk7-bl-filters__note{margin:0;color:var(--color-text-muted,#64748b);font-size:var(--fs-sm,.875rem)}.mnsk7-bl-filters__row{margin-bottom:.5rem}.mnsk7-bl-filters__selected{margin-top:.25rem}@media(max-width:768px){.mnsk7-bl-filters{padding-left:0;padding-right:0;margin-bottom:.75rem}.mnsk7-bl-filters__head{padding:0 1rem;display:block}.mnsk7-bl-filters__note{margin-top:.25rem}}';
	if ( wp_style_is( 'mnsk7-main', 'registered' ) || wp_style_is( 'mnsk7-main', 'enqueued' ) ) {
		wp_add_inline_style( 'mnsk7-main', $css );
	}
}, 35 );
