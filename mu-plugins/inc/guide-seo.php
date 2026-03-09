<?php
/**
 * Przewodnik (SEO articles): shortcode for product/category links, FAQ support.
 *
 * Shortcode: [mnsk7_guide_products] — linki do kategorii i produktów w artykułach.
 *   category="slug"       — jedna kategoria (link + opcjonalnie produkty)
 *   categories="s1,s2"    — kilka kategorii (lista linków)
 *   ids="1,2,3"           — ID produktów (linki do PDP)
 *   title="Polecane produkty"
 *   format="links"|"grid" — lista linków lub siatka [products] (domyślnie links)
 *   limit="6"             — przy format=grid lub category (ile produktów)
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	add_shortcode( 'mnsk7_guide_products', 'mnsk7_guide_products_shortcode' );
}, 8 );

/**
 * Shortcode: linki do kategorii / produktów w artykułach Przewodnik.
 *
 * @param array $atts category, categories, ids, title, format, limit.
 * @return string HTML.
 */
function mnsk7_guide_products_shortcode( $atts ) {
	$atts = shortcode_atts( array(
		'category'   => '',
		'categories' => '',
		'ids'        => '',
		'title'      => '',
		'format'     => 'links',
		'limit'      => '6',
	), $atts, 'mnsk7_guide_products' );

	$limit = max( 1, min( 12, (int) $atts['limit'] ) );
	$out   = '';

	// Jedna kategoria: link + opcjonalnie siatka produktów
	if ( $atts['category'] !== '' && taxonomy_exists( 'product_cat' ) ) {
		$term = get_term_by( 'slug', sanitize_title( $atts['category'] ), 'product_cat' );
		if ( $term && ! is_wp_error( $term ) ) {
			$url = get_term_link( $term );
			if ( ! is_wp_error( $url ) ) {
				$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
				if ( $atts['title'] ) {
					$out .= '<h3 class="mnsk7-guide-products__title">' . esc_html( $atts['title'] ) . '</h3>';
				}
				$out .= '<p class="mnsk7-guide-products__intro">';
				$out .= '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
				$out .= ' — ' . esc_html( sprintf( _n( '%d produkt', '%d produktów', $term->count, 'mnsk7-tools' ), $term->count ) ) . '.</p>';
				if ( $atts['format'] === 'grid' && function_exists( 'wc_get_loop' ) ) {
					$out .= do_shortcode( sprintf(
						'[products category="%s" limit="%d" columns="3" orderby="popularity"]',
						esc_attr( $term->slug ),
						$limit
					) );
				}
			}
		}
		return $out ? '<div class="mnsk7-guide-products">' . $out . '</div>' : '';
	}

	// Wiele kategorii: lista linków
	if ( $atts['categories'] !== '' && taxonomy_exists( 'product_cat' ) ) {
		$slugs = array_filter( array_map( 'trim', explode( ',', $atts['categories'] ) ) );
		$links = array();
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', sanitize_title( $slug ), 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$url = get_term_link( $term );
				if ( ! is_wp_error( $url ) ) {
					$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
					$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
				}
			}
		}
		if ( empty( $links ) ) {
			return '';
		}
		if ( $atts['title'] ) {
			$out .= '<h3 class="mnsk7-guide-products__title">' . esc_html( $atts['title'] ) . '</h3>';
		}
		$out .= '<ul class="mnsk7-guide-products__list">';
		foreach ( $links as $link ) {
			$out .= '<li>' . $link . '</li>';
		}
		$out .= '</ul>';
		return '<div class="mnsk7-guide-products">' . $out . '</div>';
	}

	// Konkretne ID produktów
	if ( $atts['ids'] !== '' && function_exists( 'wc_get_product' ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
		$ids = array_slice( $ids, 0, 12 );
		$links = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product && $product->is_visible() ) {
				$links[] = '<a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
			}
		}
		if ( empty( $links ) ) {
			return '';
		}
		if ( $atts['title'] ) {
			$out .= '<h3 class="mnsk7-guide-products__title">' . esc_html( $atts['title'] ) . '</h3>';
		}
		$out .= '<ul class="mnsk7-guide-products__list">';
		foreach ( $links as $link ) {
			$out .= '<li>' . $link . '</li>';
		}
		$out .= '</ul>';
		return '<div class="mnsk7-guide-products">' . $out . '</div>';
	}

	return '';
}
