<?php
/**
 * Template for product archives (shop and category). Override: mnsk7-storefront.
 * On category/tag: Sandvik-style table + chips (чипсы). Else: default grid.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header();

$plp_is_mobile_request = function_exists( 'mnsk7_is_mobile_request' ) && mnsk7_is_mobile_request();

/* Okruszki w szablonie — niezależnie od hooków, żeby nic ich nie nadpisywało (sklep, kategoria, tag, wyszukiwanie). Jedno źródło: mnsk7_is_plp(). */
if ( function_exists( 'woocommerce_breadcrumb' ) ) {
	$show_breadcrumb = ( function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp() )
		|| ( is_search() && get_query_var( 'post_type' ) === 'product' );
	if ( $show_breadcrumb ) {
		woocommerce_breadcrumb();
	}
}

do_action( 'woocommerce_before_main_content' );

$is_taxonomy = is_product_taxonomy();
$current_term = $is_taxonomy ? get_queried_object() : null;
$loop_total = isset( $GLOBALS['wp_query']->found_posts ) ? (int) $GLOBALS['wp_query']->found_posts : 0;
$max_pages  = isset( $GLOBALS['wp_query']->max_num_pages ) ? (int) $GLOBALS['wp_query']->max_num_pages : 1;
$current_page = max( 1, (int) get_query_var( 'paged', 1 ) );
$all_filter_params = function_exists( 'mnsk7_get_all_attribute_filter_param_names' ) ? mnsk7_get_all_attribute_filter_param_names() : array();
$has_filter        = false;
foreach ( $all_filter_params as $param_name ) {
	if ( ! empty( $_GET[ $param_name ] ) ) {
		$has_filter = true;
		break;
	}
}
$is_empty_filtered_state = $has_filter && $loop_total < 1;

do_action( 'woocommerce_shop_loop_header' );

echo '<div class="mnsk7-plp-archive-wrap col-full">';
echo '<div class="mnsk7-plp-content col-full">';

/* PLP-12: na stronie wyników wyszukiwania — link "Wyczyść wyszukiwanie" i liczba wyników */
if ( is_search() && get_query_var( 'post_type' ) === 'product' ) {
	global $wp_query;
	$found = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : get_permalink( wc_get_page_id( 'shop' ) );
	if ( $shop_url ) {
		echo '<p class="mnsk7-plp-search-clear col-full">';
		echo '<a href="' . esc_url( $shop_url ) . '" class="mnsk7-plp-search-clear__link">' . esc_html__( 'Wyczyść wyszukiwanie', 'mnsk7-storefront' ) . '</a>';
		if ( $found >= 0 ) {
			echo ' <span class="mnsk7-plp-search-count">';
			echo esc_html( sprintf(
				/* translators: %d: number of products found */
				_n( 'Znaleziono %d produkt', 'Znaleziono %d produktów', $found, 'mnsk7-storefront' ),
				$found
			) );
			echo '</span>';
		}
		echo '</p>';
	}
}

/* Scroll target: górna granica strefy wyników (chips + search + USP + lista). Po zastosowaniu filtra użytkownik ląduje tutaj, nie przy pierwszej karcie. */
echo '<div id="mnsk7-plp-results" class="mnsk7-plp-results-anchor" aria-hidden="true"></div>';

/* Render jednego rzędu chipów nawigacyjnych (kategorie/tagi): etykieta + poziomy scroll + opcjonalnie "Więcej". */
$plp_nav_chips_limit = $plp_is_mobile_request ? 5 : 6;
$render_plp_nav_row = function ( $label, $terms, $active_term_id = 0 ) use ( $plp_nav_chips_limit, $plp_is_mobile_request ) {
	if ( empty( $terms ) || ! is_array( $terms ) ) {
		return;
	}
	$terms   = array_values( $terms );
	$visible = array_slice( $terms, 0, $plp_nav_chips_limit );
	$hidden  = array_slice( $terms, $plp_nav_chips_limit, null );
	$anchor  = function_exists( 'mnsk7_plp_anchor_results' ) ? 'mnsk7_plp_anchor_results' : null;
	if ( $plp_is_mobile_request ) {
		$active_name = '';
		foreach ( $terms as $term ) {
			if ( $active_term_id && (int) $term->term_id === (int) $active_term_id ) {
				$active_name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
				$active_name = function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $active_name ) : $active_name;
				break;
			}
		}
		$summary_meta = $active_name
			? sprintf( __( 'Wybrano: %s', 'mnsk7-storefront' ), $active_name )
			: sprintf(
				_n( '%d opcja', '%d opcji', count( $terms ), 'mnsk7-storefront' ),
				count( $terms )
			);
		$dropdown_class = 'mnsk7-plp-dropdown mnsk7-plp-dropdown--nav col-full' . ( $active_name ? ' mnsk7-plp-dropdown--active' : '' );
		echo '<details class="' . esc_attr( $dropdown_class ) . '">';
		echo '<summary class="mnsk7-plp-dropdown__summary"><span class="mnsk7-plp-dropdown__summary-main"><span class="mnsk7-plp-dropdown__summary-title">' . esc_html( $label ) . '</span><span class="mnsk7-plp-dropdown__summary-meta">' . esc_html( $summary_meta ) . '</span></span></summary>';
		echo '<div class="mnsk7-plp-dropdown__panel">';
		echo '<div class="mnsk7-plp-dropdown__panel-head"><span class="mnsk7-plp-dropdown__panel-title">' . esc_html( $label ) . '</span><button type="button" class="mnsk7-plp-dropdown__close" aria-label="' . esc_attr__( 'Zamknij filtr', 'mnsk7-storefront' ) . '">' . esc_html__( 'Zamknij', 'mnsk7-storefront' ) . '</button></div>';
		echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--nav" role="navigation" aria-label="' . esc_attr( $label ) . '">';
		echo '<div class="mnsk7-plp-chips__scroll">';
		foreach ( $terms as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$link = $anchor ? $anchor( $link ) : $link;
			$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
			$name = function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $name ) : $name;
			$active = $active_term_id && (int) $term->term_id === (int) $active_term_id;
			printf( '<a href="%s" class="mnsk7-plp-chip %s"%s>%s</a>', esc_url( $link ), $active ? 'mnsk7-plp-chip--active' : '', $active ? ' aria-current="page"' : '', esc_html( $name ) );
		}
		echo '</div>';
		echo '</div>';
		echo '</div>';
		echo '</details>';
		return;
	}
	echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--nav col-full" role="navigation" aria-label="' . esc_attr( $label ) . '">';
	echo '<span class="mnsk7-plp-chips__label">' . esc_html( $label ) . '</span>';
	echo '<div class="mnsk7-plp-chips__scroll">';
	foreach ( $visible as $term ) {
		$link = get_term_link( $term );
		if ( is_wp_error( $link ) ) {
			continue;
		}
		$link = $anchor ? $anchor( $link ) : $link;
		$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
		$name = function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $name ) : $name;
		$active = $active_term_id && (int) $term->term_id === (int) $active_term_id;
		printf( '<a href="%s" class="mnsk7-plp-chip %s"%s>%s</a>', esc_url( $link ), $active ? 'mnsk7-plp-chip--active' : '', $active ? ' aria-current="page"' : '', esc_html( $name ) );
	}
	if ( ! empty( $hidden ) ) {
		$more_id = 'mnsk7-plp-more-nav-' . sanitize_title( $label );
		echo '<span class="mnsk7-plp-chips-more" id="' . esc_attr( $more_id ) . '" hidden aria-hidden="true">';
		foreach ( $hidden as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$link = $anchor ? $anchor( $link ) : $link;
			$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
			$name = function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $name ) : $name;
			$active = $active_term_id && (int) $term->term_id === (int) $active_term_id;
			printf( '<a href="%s" class="mnsk7-plp-chip %s"%s>%s</a>', esc_url( $link ), $active ? 'mnsk7-plp-chip--active' : '', $active ? ' aria-current="page"' : '', esc_html( $name ) );
		}
		echo '</span>';
		echo '<button type="button" class="mnsk7-plp-chips-toggle" data-controls="' . esc_attr( $more_id ) . '" aria-controls="' . esc_attr( $more_id ) . '" aria-expanded="false">' . esc_html__( 'Więcej', 'mnsk7-storefront' ) . '</button>';
	}
	echo '</div>';
	echo '</div>';
};

/* Filtr atrybutów (Średnica trzpienia) — ten sam blok na kategorii/tagach i na głównej stronie Sklep. */
$plp_attr_chips_limit = $plp_is_mobile_request ? 4 : 5;
$render_plp_attribute_section = function ( $clear_all_url ) use ( $plp_is_mobile_request, $plp_attr_chips_limit ) {
	$attr_data       = function_exists( 'mnsk7_get_archive_attribute_filter_chips' ) ? mnsk7_get_archive_attribute_filter_chips() : array( 'filters' => array(), 'filter_params' => array() );
	$all_filters     = isset( $attr_data['filters'] ) ? $attr_data['filters'] : array();
	$plp_attr_visible = $plp_is_mobile_request ? count( $all_filters ) : 1;
	$visible_filters  = array_slice( $all_filters, 0, $plp_attr_visible, true );
	$hidden_filters   = array_slice( $all_filters, $plp_attr_visible, null, true );
	$has_hidden_rows  = ! $plp_is_mobile_request && ! empty( $hidden_filters );
	$active_in_hidden = false;
	if ( $has_hidden_rows ) {
		foreach ( $hidden_filters as $attribute_filter ) {
			$param = isset( $attribute_filter['param'] ) ? $attribute_filter['param'] : '';
			if ( $param && ! empty( $_GET[ $param ] ) ) {
				$active_in_hidden = true;
				break;
			}
		}
	}

	$render_filter_row = function ( $attribute_filter ) use ( $plp_attr_chips_limit, $plp_is_mobile_request ) {
		if ( empty( $attribute_filter['chips'] ) ) {
			return;
		}
		$aria_label = sprintf( /* translators: %s: filter group name */ __( 'Filtruj: %s', 'mnsk7-storefront' ), $attribute_filter['label'] );
		$chips_list = $attribute_filter['chips'];
		$param      = $attribute_filter['param'];
		$visible    = array_slice( $chips_list, 0, $plp_attr_chips_limit, true );
		$hidden     = array_slice( $chips_list, $plp_attr_chips_limit, null, true );
		$anchor_fn  = function_exists( 'mnsk7_plp_anchor_results' ) ? 'mnsk7_plp_anchor_results' : null;
		if ( $plp_is_mobile_request ) {
			$active_slug  = isset( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
			$active_label = ( $active_slug && isset( $chips_list[ $active_slug ] ) ) ? $chips_list[ $active_slug ] : '';
			$summary_meta = $active_label
				? sprintf( __( 'Wybrano: %s', 'mnsk7-storefront' ), $active_label )
				: sprintf(
					_n( '%d opcja', '%d opcji', count( $chips_list ), 'mnsk7-storefront' ),
					count( $chips_list )
				);
			$dropdown_class = 'mnsk7-plp-dropdown mnsk7-plp-dropdown--attrs col-full' . ( $active_label ? ' mnsk7-plp-dropdown--active' : '' );
			echo '<details class="' . esc_attr( $dropdown_class ) . '">';
			echo '<summary class="mnsk7-plp-dropdown__summary"><span class="mnsk7-plp-dropdown__summary-main"><span class="mnsk7-plp-dropdown__summary-title">' . esc_html( $attribute_filter['label'] ) . '</span><span class="mnsk7-plp-dropdown__summary-meta">' . esc_html( $summary_meta ) . '</span></span></summary>';
			echo '<div class="mnsk7-plp-dropdown__panel">';
			echo '<div class="mnsk7-plp-dropdown__panel-head"><span class="mnsk7-plp-dropdown__panel-title">' . esc_html( $attribute_filter['label'] ) . '</span><button type="button" class="mnsk7-plp-dropdown__close" aria-label="' . esc_attr__( 'Zamknij filtr', 'mnsk7-storefront' ) . '">' . esc_html__( 'Zamknij', 'mnsk7-storefront' ) . '</button></div>';
			echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--attrs" role="navigation" aria-label="' . esc_attr( $aria_label ) . '">';
			echo '<div class="mnsk7-plp-chips__scroll">';
			foreach ( $chips_list as $slug => $label ) {
				$url = add_query_arg( $param, $slug );
				$url = $anchor_fn ? $anchor_fn( $url ) : $url;
				$active = isset( $_GET[ $param ] ) && sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) === $slug;
				printf( '<a href="%s" class="mnsk7-plp-chip %s"%s>%s</a>', esc_url( $url ), $active ? 'mnsk7-plp-chip--active' : '', $active ? ' aria-current="page"' : '', esc_html( $label ) );
			}
			echo '</div>';
			echo '</div>';
			echo '</div>';
			echo '</details>';
			return;
		}
		echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--attrs col-full" role="navigation" aria-label="' . esc_attr( $aria_label ) . '">';
		echo '<span class="mnsk7-plp-chips__label">' . esc_html( $attribute_filter['label'] ) . '</span>';
		echo '<div class="mnsk7-plp-chips__scroll">';
		foreach ( $visible as $slug => $label ) {
			$url = add_query_arg( $param, $slug );
			$url = $anchor_fn ? $anchor_fn( $url ) : $url;
			$active = isset( $_GET[ $param ] ) && sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) === $slug;
			printf( '<a href="%s" class="mnsk7-plp-chip %s"%s>%s</a>', esc_url( $url ), $active ? 'mnsk7-plp-chip--active' : '', $active ? ' aria-current="page"' : '', esc_html( $label ) );
		}
		if ( ! empty( $hidden ) ) {
			echo '<span class="mnsk7-plp-chips-more" id="mnsk7-plp-more-' . esc_attr( sanitize_title( $param ) ) . '" hidden aria-hidden="true">';
			foreach ( $hidden as $slug => $label ) {
				$url = add_query_arg( $param, $slug );
				$url = $anchor_fn ? $anchor_fn( $url ) : $url;
				$active = isset( $_GET[ $param ] ) && sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) === $slug;
				printf( '<a href="%s" class="mnsk7-plp-chip %s"%s>%s</a>', esc_url( $url ), $active ? 'mnsk7-plp-chip--active' : '', $active ? ' aria-current="page"' : '', esc_html( $label ) );
			}
			echo '</span>';
			echo '<button type="button" class="mnsk7-plp-chips-toggle" data-controls="mnsk7-plp-more-' . esc_attr( sanitize_title( $param ) ) . '" aria-controls="mnsk7-plp-more-' . esc_attr( sanitize_title( $param ) ) . '" aria-expanded="false">' . esc_html__( 'Więcej', 'mnsk7-storefront' ) . '</button>';
		}
		echo '</div>';
		echo '</div>';
	};

	if ( ! empty( $visible_filters ) ) {
		foreach ( $visible_filters as $attribute_filter ) {
			$render_filter_row( $attribute_filter );
		}
	}
	if ( $has_hidden_rows ) {
		$filters_expanded = $active_in_hidden;
		echo '<div class="mnsk7-plp-filters-toggle-wrap col-full">';
		echo '<button type="button" class="mnsk7-plp-chips-toggle mnsk7-plp-filters-toggle" data-controls="mnsk7-plp-more-filters" aria-controls="mnsk7-plp-more-filters" data-more-text="' . esc_attr__( 'Więcej filtrów', 'mnsk7-storefront' ) . '" data-less-text="' . esc_attr__( 'Mniej filtrów', 'mnsk7-storefront' ) . '" aria-expanded="' . ( $filters_expanded ? 'true' : 'false' ) . '">' . esc_html( $filters_expanded ? __( 'Mniej filtrów', 'mnsk7-storefront' ) : __( 'Więcej filtrów', 'mnsk7-storefront' ) ) . '</button>';
		echo '</div>';
		echo '<div class="mnsk7-plp-filters-more col-full" id="mnsk7-plp-more-filters" aria-hidden="' . ( $filters_expanded ? 'false' : 'true' ) . '"' . ( $filters_expanded ? '' : ' hidden' ) . '>';
		foreach ( $hidden_filters as $attribute_filter ) {
			$render_filter_row( $attribute_filter );
		}
		echo '</div>';
	}
	$filter_params = isset( $attr_data['filter_params'] ) && is_array( $attr_data['filter_params'] ) ? $attr_data['filter_params'] : array();
	$active_filters = array();
	foreach ( $filter_params as $param ) {
		if ( ! empty( $_GET[ $param ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
			$active_filters[ $param ] = $val;
		}
	}
	if ( ! empty( $active_filters ) ) {
		$clear_url = is_string( $clear_all_url ) ? $clear_all_url : '';
		if ( $clear_url === '' && function_exists( 'wc_get_page_permalink' ) ) {
			$clear_url = (string) wc_get_page_permalink( 'shop' );
		}
		if ( $clear_url === '' ) {
			$clear_url = home_url( '/' );
		}
		$clear_url = function_exists( 'mnsk7_plp_anchor_results' ) ? mnsk7_plp_anchor_results( $clear_url ) : $clear_url;
		echo '<div class="mnsk7-plp-selected col-full">';
		echo '<span class="mnsk7-plp-selected__label">' . esc_html__( 'Wybrane:', 'mnsk7-storefront' ) . '</span>';
		foreach ( $active_filters as $param => $val ) {
			$without = remove_query_arg( $param );
			$without = function_exists( 'mnsk7_plp_anchor_results' ) ? mnsk7_plp_anchor_results( $without ) : $without;
			$display_val = function_exists( 'mnsk7_normalize_archive_chip_label' ) ? mnsk7_normalize_archive_chip_label( $val ) : $val;
			echo '<a href="' . esc_url( $without ) . '" class="mnsk7-plp-chip mnsk7-plp-chip--active mnsk7-plp-chip--remove" aria-label="' . esc_attr__( 'Usuń filtr', 'mnsk7-storefront' ) . '">' . esc_html( $display_val ) . ' ×</a>';
		}
		echo ' <a href="' . esc_url( $clear_url ) . '" class="button mnsk7-plp-reset">' . esc_html__( 'Wyczyść wszystkie', 'mnsk7-storefront' ) . '</a>';
		echo '</div>';
	}
};

/* Чипсы na stronie taksonomii (kategoria/tag): rząd kategorii + rząd tagów (jak w megamenu), potem filtry atrybutów. */
if ( ! $is_empty_filtered_state && $is_taxonomy && $current_term && isset( $current_term->taxonomy ) ) {
	$cat_row_terms = array();
	if ( $current_term->taxonomy === 'product_cat' ) {
		$parent_id     = $current_term->parent;
		$cat_row_terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent_id,
			'hide_empty' => true,
		) );
	} else {
		$cat_row_terms = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 12,
		) );
	}
	$cat_label = apply_filters( 'mnsk7_megamenu_heading_categories', __( 'Rodzaje frezów', 'mnsk7-storefront' ) );
	if ( ! is_wp_error( $cat_row_terms ) && ! empty( $cat_row_terms ) ) {
		$render_plp_nav_row( $cat_label, $cat_row_terms, $current_term->taxonomy === 'product_cat' ? $current_term->term_id : 0 );
	}
	$megamenu_terms = function_exists( 'mnsk7_get_megamenu_terms' ) ? mnsk7_get_megamenu_terms() : array( 'tags' => array(), 'accessories' => array() );
	$tags_row      = isset( $megamenu_terms['tags'] ) ? $megamenu_terms['tags'] : array();
	$tags_label    = apply_filters( 'mnsk7_megamenu_heading_tags', __( 'Zastosowanie i materiały', 'mnsk7-storefront' ) );
	if ( ! empty( $tags_row ) && 'product_tag' !== $current_term->taxonomy ) {
		$render_plp_nav_row( $tags_label, $tags_row, $current_term->taxonomy === 'product_tag' ? $current_term->term_id : 0 );
	}

	$term_link_clear = get_term_link( $current_term );
	$clear_attr_base = is_wp_error( $term_link_clear ) ? ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '' ) : $term_link_clear;
	$clear_attr_base = $clear_attr_base ? ( function_exists( 'mnsk7_plp_anchor_results' ) ? mnsk7_plp_anchor_results( $clear_attr_base ) : $clear_attr_base ) : home_url( '/' );
	$render_plp_attribute_section( $clear_attr_base );
}

/* Strona Sklep (bez taksonomii): dwie grupy chipów — kategorie i tagi (jak w megamenu). */
if ( ! $is_empty_filtered_state && is_shop() && ! $is_taxonomy ) {
	$megamenu = function_exists( 'mnsk7_get_megamenu_terms' ) ? mnsk7_get_megamenu_terms() : array( 'cats' => array(), 'accessories' => array(), 'tags' => array() );
	$shop_cats = isset( $megamenu['cats'] ) ? $megamenu['cats'] : array();
	$shop_accessories = isset( $megamenu['accessories'] ) ? $megamenu['accessories'] : array();
	$shop_tags = isset( $megamenu['tags'] ) ? $megamenu['tags'] : array();
	$cat_label = apply_filters( 'mnsk7_megamenu_heading_categories', __( 'Rodzaje frezów', 'mnsk7-storefront' ) );
	$tags_label = apply_filters( 'mnsk7_megamenu_heading_tags', __( 'Zastosowanie i materiały', 'mnsk7-storefront' ) );
	$accessories_label = apply_filters( 'mnsk7_megamenu_heading_accessories', __( 'Akcesoria i zestawy', 'mnsk7-storefront' ) );
	if ( ! is_wp_error( $shop_cats ) && ! empty( $shop_cats ) ) {
		$render_plp_nav_row( $cat_label, $shop_cats, 0 );
	}
	if ( ! is_wp_error( $shop_tags ) && ! empty( $shop_tags ) ) {
		$render_plp_nav_row( $tags_label, $shop_tags, 0 );
	}
	if ( ! is_wp_error( $shop_accessories ) && ! empty( $shop_accessories ) ) {
		$render_plp_nav_row( $accessories_label, $shop_accessories, 0 );
	}
	$shop_clear = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '';
	$shop_clear = $shop_clear ? ( function_exists( 'mnsk7_plp_anchor_results' ) ? mnsk7_plp_anchor_results( $shop_clear ) : $shop_clear ) : home_url( '/' );
	$render_plp_attribute_section( $shop_clear );
}

$use_table = is_shop() || $is_taxonomy;
$GLOBALS['mnsk7_plp_use_table'] = $use_table;

/* Jeden layout na request: mobile (user-agent) = karty, desktop = tabela. W DOM tylko jeden blok. */
$plp_is_mobile = $plp_is_mobile_request;

/* Na kategoria/tag (desktop): toolbar u góry w jednej linii z wyszukiwarką; na dole nie powielamy. */
$plp_show_toolbar_at_top = false;

if ( woocommerce_product_loop() ) {
	/* PLP-05/PLP-10: bez toolbara u góry — sortowanie i paginacja tylko na dole; przy tabeli tylko "Pokaż więcej" */
	if ( $use_table ) {
		if ( $plp_is_mobile ) {
			/* Mobile: tylko siatka kart (jedna pętla, bez tabeli w DOM). */
			echo '<div class="mnsk7-plp-grid-mobile col-full">';
			woocommerce_product_loop_start();
			if ( wc_get_loop_prop( 'total' ) ) {
				while ( have_posts() ) {
					the_post();
					do_action( 'woocommerce_shop_loop' );
					wc_get_template_part( 'content', 'product' );
				}
			}
			woocommerce_product_loop_end();
			echo '</div>';
			if ( function_exists( 'mnsk7_render_trust_badges' ) ) {
				echo '<div class="mnsk7-plp-trust-wrap mnsk7-plp-trust-wrap--after-results col-full">';
				mnsk7_render_trust_badges( 'mnsk7-plp-trust' );
				echo '</div>';
			}
		} else {
			/* Desktop/tablet: tylko tabela (bez siatki kart w DOM). Jeden toolbar (na dole) dla spójnego rytmu. */
			$plp_show_toolbar_at_top = true;
			echo '<div class="mnsk7-plp-toolbar-strip col-full">';
			echo '<div class="mnsk7-plp-toolbar mnsk7-plp-toolbar--top">';
			if ( function_exists( 'woocommerce_result_count' ) ) {
				echo '<div class="mnsk7-plp-toolbar__count">';
				woocommerce_result_count();
				echo '</div>';
			}
			if ( function_exists( 'woocommerce_catalog_ordering' ) ) {
				woocommerce_catalog_ordering();
			}
			echo '</div>';
			echo '</div>';
			?>
			<div class="mnsk7-product-table-wrap col-full" role="region" aria-label="<?php esc_attr_e( 'Lista produktów', 'mnsk7-storefront' ); ?>">
				<table id="mnsk7-plp-product-table" class="mnsk7-product-table shop_table">
					<caption class="screen-reader-text"><?php esc_html_e( 'Tabela produktów w katalogu.', 'mnsk7-storefront' ); ?></caption>
					<thead>
						<tr>
							<th scope="col" class="mnsk7-table-cell--thumb"><?php esc_html_e( 'Zdjęcie', 'mnsk7-storefront' ); ?></th>
							<th scope="col" class="mnsk7-table-cell--title"><?php esc_html_e( 'Produkt', 'mnsk7-storefront' ); ?></th>
							<th scope="col" class="mnsk7-table-cell--price"><?php esc_html_e( 'Cena', 'mnsk7-storefront' ); ?></th>
							<th scope="col" class="mnsk7-table-cell--stock"><?php esc_html_e( 'Na stanie', 'mnsk7-storefront' ); ?></th>
							<th scope="col" class="mnsk7-table-cell--qty"><?php esc_html_e( 'Ilość', 'mnsk7-storefront' ); ?></th>
							<th scope="col" class="mnsk7-table-cell--action"><?php esc_html_e( 'Akcja', 'mnsk7-storefront' ); ?></th>
						</tr>
					</thead>
					<tbody>
					<?php
					while ( have_posts() ) {
						the_post();
						global $product;
						if ( ! is_a( $product, WC_Product::class ) ) {
							$product = wc_get_product( get_the_ID() );
						}
						wc_get_template_part( 'content', 'product-table-row' );
					}
					?>
					</tbody>
				</table>
			</div>
			<dialog id="mnsk7-plp-thumb-dialog" class="mnsk7-plp-thumb-dialog" aria-modal="true" aria-label="<?php esc_attr_e( 'Powiększone zdjęcie produktu', 'mnsk7-storefront' ); ?>">
				<div class="mnsk7-plp-thumb-dialog__inner">
					<button type="button" class="mnsk7-plp-thumb-dialog__close" aria-label="<?php esc_attr_e( 'Zamknij', 'mnsk7-storefront' ); ?>"><span aria-hidden="true">&times;</span></button>
					<img class="mnsk7-plp-thumb-dialog__img" alt="" decoding="async" loading="lazy" />
				</div>
			</dialog>
			<?php
			if ( function_exists( 'mnsk7_render_trust_badges' ) ) {
				echo '<div class="mnsk7-plp-trust-wrap mnsk7-plp-trust-wrap--after-results col-full">';
				mnsk7_render_trust_badges( 'mnsk7-plp-trust' );
				echo '</div>';
			}
			if ( $max_pages > $current_page ) {
				$taxonomy = $is_taxonomy && $current_term instanceof WP_Term ? $current_term->taxonomy : '';
				$term_slug = $is_taxonomy && $current_term instanceof WP_Term ? $current_term->slug : '';
				echo '<div class="mnsk7-plp-load-more-wrap col-full" data-current-page="' . esc_attr( $current_page ) . '" data-taxonomy="' . esc_attr( $taxonomy ) . '" data-term="' . esc_attr( $term_slug ) . '">';
				echo '<button type="button" class="mnsk7-plp-load-more button">' . esc_html__( 'Pokaż więcej', 'mnsk7-storefront' ) . '</button>';
				echo '</div>';
			}
			?>
			<?php
		}
		/* Desktop table: jeden wzorzec nawigacji listy (toolbar + paginacja), bez dodatkowego "Pokaż więcej". */
	} else {
		woocommerce_product_loop_start();
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
	}
	echo '</div><!-- .mnsk7-plp-content -->';

	if ( ! $plp_show_toolbar_at_top ) {
		echo '<div class="mnsk7-plp-toolbar mnsk7-plp-toolbar--bottom col-full">';
		do_action( 'woocommerce_after_shop_loop' );
		echo '</div>';
	}
	echo '</div><!-- .mnsk7-plp-archive-wrap -->';
} else {
	if ( $has_filter ) {
		// Jeden spójny blok empty state — bez duplikatu komunikatu WooCommerce.
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
		$clear_url = ! empty( $all_filter_params ) ? remove_query_arg( $all_filter_params ) : ( $current_term && ! is_wp_error( get_term_link( $current_term ) ) ? get_term_link( $current_term ) : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '' ) );
		echo '<div class="mnsk7-plp-empty col-full" role="status">';
		echo '<p class="mnsk7-plp-empty__text">' . esc_html__( 'Brak produktów dla wybranych filtrów.', 'mnsk7-storefront' ) . '</p>';
		echo '<p class="mnsk7-plp-empty__hint">' . esc_html__( 'Zmień kryteria, wyszukaj produkt lub wróć do pełnej listy.', 'mnsk7-storefront' ) . '</p>';
		echo '<a href="' . esc_url( $clear_url ) . '" class="button mnsk7-plp-empty__cta">' . esc_html__( 'Wyczyść filtry', 'mnsk7-storefront' ) . '</a>';
		echo '</div>';
	}
	do_action( 'woocommerce_no_products_found' );
	echo '</div><!-- .mnsk7-plp-content -->';
	echo '</div><!-- .mnsk7-plp-archive-wrap -->';
}

do_action( 'woocommerce_after_main_content' );
if ( ! $use_table ) {
	do_action( 'woocommerce_sidebar' );
}

get_footer( 'shop' );
