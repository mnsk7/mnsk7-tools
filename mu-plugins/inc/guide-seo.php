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
 * Article-to-catalog relevance map.
 *
 * Keys in "terms" can be product_cat slugs, product_tag slugs, or lightweight
 * keyword hints used on PDP when a product is not tagged enough yet.
 *
 * @return array<int,array{slug:string,title:string,terms:string[]}>
 */
function mnsk7_get_guide_article_links_map() {
	return array(
		array(
			'slug'  => 'frezy-kompresyjne-do-czego-sluza',
			'title' => 'Frez kompresyjny - kiedy wybrac UP&DOWN CUT?',
			'terms' => array( 'frezy-kompresyjne-updown-cut', 'frezy-spiralne', 'mdf', 'drewno', 'sklejka', 'kompresyjny' ),
		),
		array(
			'slug'  => 'frez-do-wyrownania-sleba-i-planowania-powierzchni',
			'title' => 'Jaki frez do planowania drewna i slabow?',
			'terms' => array( 'frezy-do-planowania', 'frezy-proste-z-wymiennymi-plytkami', 'drewno', 'mdf', 'planowania', 'slab' ),
		),
		array(
			'slug'  => 'borfrezy-do-metalu-zastosowanie',
			'title' => 'Pilniki obrotowe i borfrezy - jak wybrac?',
			'terms' => array( 'pilniki-obrotowe', 'pilniki-obrotowe-typ-a', 'pilniki-obrotowe-typ-f', 'stal', 'metal', 'borfrez' ),
		),
		array(
			'slug'  => 'frezy-do-3d-obrobki-cnc',
			'title' => 'Frezy do obrobki 3D CNC',
			'terms' => array( 'frezy-kulowe', 'frezy-stozkowo-kulowe', 'frezy-diamentowe', 'relief', '3d', 'kulowy' ),
		),
		array(
			'slug'  => 'frezy-raszplowe-kukurydza',
			'title' => 'Frez kukurydza - szybka obrobka zgrubna',
			'terms' => array( 'frezy-proste-wieloostrzowe', 'frezy-proste', 'kukurydza', 'wieloostrzowy', 'zgrubna' ),
		),
		array(
			'slug'  => 'rodzaje-frezow-do-recznego-frezera-do-drewna',
			'title' => 'Frezy do recznego frezera do drewna',
			'terms' => array( 'frezy-z-lozyskiem', 'frezy-krawedziowe', 'frezy-zaokraglajace', 'lozyskiem', 'reczny', 'drewno' ),
		),
		array(
			'slug'  => 'rodzaje-frezow-cnc-do-drewna',
			'title' => 'Frezy CNC do drewna - rodzaje i zastosowania',
			'terms' => array( 'frezy-spiralne', 'frezy-kompresyjne-updown-cut', 'frezy-kulowe', 'drewno', 'mdf', 'sklejka' ),
		),
		array(
			'slug'  => 'frezy-koncowe-do-aluminium',
			'title' => 'Jaki frez do aluminium CNC?',
			'terms' => array( 'frezy-spiralne-jednopiorowe-1p', 'frezy-spiralne-dwupiorowe-2p', 'aluminium', '1p', '2p' ),
		),
		array(
			'slug'  => 'frezy-do-metalu-stal-i-metale-kolorowe',
			'title' => 'Frezy CNC do stali i metali kolorowych',
			'terms' => array( 'frezy-proste', 'frezy-proste-wieloostrzowe', 'frezy-spiralne-czteropiorowe-4p', 'stal', 'metal', 'metale' ),
		),
		array(
			'slug'  => 'liczba-ostrz-frezu-jednopiorowe-dwupiorowe-czteropiorowe',
			'title' => 'Ile ostrzy ma miec frez: 1P, 2P, 3P czy 4P?',
			'terms' => array( 'frezy-spiralne-jednopiorowe-1p', 'frezy-spiralne-dwupiorowe-2p', 'frezy-spiralne-trzypiorowe-3p', 'frezy-spiralne-czteropiorowe-4p', '1p', '2p', '3p', '4p' ),
		),
	);
}

/**
 * Returns a relevant product for a guide article.
 *
 * The result is cached because it is also used by social/schema image filters.
 *
 * @param int $post_id Guide post ID.
 * @return WC_Product|null
 */
function mnsk7_get_guide_primary_product( $post_id ) {
	$post_id = absint( $post_id );
	if ( ! $post_id || ! function_exists( 'wc_get_product' ) || ! function_exists( 'wc_get_products' ) ) {
		return null;
	}

	$cache_key = 'mnsk7_guide_primary_product_' . $post_id;
	$cached_id = get_transient( $cache_key );
	if ( $cached_id !== false ) {
		$product = $cached_id ? wc_get_product( (int) $cached_id ) : null;
		return $product instanceof WC_Product ? $product : null;
	}

	$post_slug = (string) get_post_field( 'post_name', $post_id );
	$featured_product_ids = array(
		'frez-do-wyrownania-sleba-i-planowania-powierzchni' => 6820,
	);
	if ( isset( $featured_product_ids[ $post_slug ] ) ) {
		$featured_product = wc_get_product( $featured_product_ids[ $post_slug ] );
		if ( $featured_product instanceof WC_Product && $featured_product->is_visible() && $featured_product->get_image_id() ) {
			set_transient( $cache_key, $featured_product->get_id(), 12 * HOUR_IN_SECONDS );
			return $featured_product;
		}
	}

	$signals   = array();
	foreach ( mnsk7_get_guide_article_links_map() as $article ) {
		if ( $article['slug'] === $post_slug ) {
			$signals = $article['terms'];
			break;
		}
	}

	foreach ( array_unique( array_map( 'sanitize_title', $signals ) ) as $category_slug ) {
		if ( ! term_exists( $category_slug, 'product_cat' ) ) {
			continue;
		}
		$products = wc_get_products(
			array(
				'status'   => 'publish',
				'limit'    => 6,
				'category' => array( $category_slug ),
				'orderby'  => 'date',
				'order'    => 'DESC',
			)
		);
		foreach ( $products as $product ) {
			if ( $product instanceof WC_Product && $product->is_visible() && $product->get_image_id() ) {
				set_transient( $cache_key, $product->get_id(), 12 * HOUR_IN_SECONDS );
				return $product;
			}
		}
	}

	set_transient( $cache_key, 0, HOUR_IN_SECONDS );
	return null;
}

/**
 * Returns the full-size relevant product image for a guide article.
 *
 * @param int $post_id Guide post ID.
 * @return string
 */
function mnsk7_get_guide_primary_image_url( $post_id ) {
	$product = mnsk7_get_guide_primary_product( $post_id );
	if ( ! $product instanceof WC_Product ) {
		return '';
	}

	return (string) wp_get_attachment_image_url( $product->get_image_id(), 'full' );
}

/**
 * Gets related guide posts for catalog/product terms.
 *
 * @param string[] $signals Term slugs and keyword hints.
 * @param int      $limit   Maximum number of posts.
 * @return array<int,array{title:string,url:string}>
 */
function mnsk7_get_related_guide_articles( $signals, $limit = 3 ) {
	$signals = array_values( array_unique( array_filter( array_map( 'sanitize_title', (array) $signals ) ) ) );
	if ( empty( $signals ) ) {
		return array();
	}

	$scored = array();
	foreach ( mnsk7_get_guide_article_links_map() as $article ) {
		$terms = array_map( 'sanitize_title', $article['terms'] );
		$score = count( array_intersect( $signals, $terms ) );
		if ( $score <= 0 ) {
			continue;
		}
		$scored[] = array(
			'score' => $score,
			'slug'  => $article['slug'],
			'title' => $article['title'],
		);
	}

	usort( $scored, function ( $a, $b ) {
		return $b['score'] <=> $a['score'];
	} );

	$out = array();
	foreach ( array_slice( $scored, 0, max( 1, (int) $limit ) ) as $row ) {
		$post = get_page_by_path( $row['slug'], OBJECT, 'post' );
		$url  = $post instanceof WP_Post ? get_permalink( $post ) : home_url( '/' . $row['slug'] . '/' );
		if ( ! $url ) {
			continue;
		}
		$out[] = array(
			'title' => $post instanceof WP_Post ? get_the_title( $post ) : $row['title'],
			'url'   => $url,
		);
	}

	return $out;
}

/**
 * Renders related guide links.
 *
 * @param array<int,array{title:string,url:string}> $articles Related articles.
 * @param string                                    $title    Block title.
 * @return string
 */
function mnsk7_render_related_guide_articles( $articles, $title = '' ) {
	if ( empty( $articles ) ) {
		return '';
	}

	$classes = array( 'mnsk7-related-guides' );
	if ( function_exists( 'is_product' ) && is_product() ) {
		$classes[] = 'mnsk7-related-guides--pdp';
	} elseif ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		$classes[] = 'mnsk7-related-guides--archive';
	}

	$title = $title ?: __( 'Powiazane poradniki', 'mnsk7-tools' );
	$out   = '<section class="' . esc_attr( implode( ' ', $classes ) ) . '" aria-label="' . esc_attr( $title ) . '">';
	$out  .= '<div class="mnsk7-related-guides__head">';
	$out  .= '<span class="mnsk7-related-guides__eyebrow">' . esc_html__( 'Przewodnik', 'mnsk7-tools' ) . '</span>';
	$out  .= '<h2>' . esc_html( $title ) . '</h2>';
	$out  .= '</div>';
	$out  .= '<div class="mnsk7-related-guides__list">';
	foreach ( $articles as $article ) {
		$out .= '<a class="mnsk7-related-guides__item" href="' . esc_url( $article['url'] ) . '">';
		$out .= '<span>' . esc_html( $article['title'] ) . '</span>';
		$out .= '<strong>' . esc_html__( 'Czytaj', 'mnsk7-tools' ) . '</strong>';
		$out .= '</a>';
	}
	$out .= '</div></section>';

	return $out;
}

/**
 * Renders related guide articles near the bottom of product category pages.
 *
 * @return void
 */
function mnsk7_render_related_guides_on_product_archive() {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}

	$signals = array( $term->slug );
	foreach ( get_ancestors( (int) $term->term_id, $term->taxonomy ) as $ancestor_id ) {
		$ancestor = get_term( (int) $ancestor_id, $term->taxonomy );
		if ( $ancestor instanceof WP_Term ) {
			$signals[] = $ancestor->slug;
		}
	}

	echo mnsk7_render_related_guide_articles( mnsk7_get_related_guide_articles( $signals, 3 ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_main_content', 'mnsk7_render_related_guides_on_product_archive', 8 );

/**
 * Renders related guide articles after PDP description.
 *
 * @return void
 */
function mnsk7_render_related_guides_on_product_page() {
	if ( ! function_exists( 'is_product' ) || ! is_product() || ! function_exists( 'wc_get_product' ) ) {
		return;
	}

	$product = wc_get_product( get_the_ID() );
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$signals = array();
	foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
		$terms = wc_get_product_terms( $product->get_id(), $taxonomy );
		foreach ( $terms as $term ) {
			if ( $term instanceof WP_Term ) {
				$signals[] = $term->slug;
			}
		}
	}

	$title_text = strtolower( remove_accents( $product->get_name() ) );
	foreach ( array( 'aluminium', 'stal', 'metal', 'drewno', 'mdf', 'kompresyjny', 'kulowy', 'kukurydza', '1p', '2p', '3p', '4p' ) as $hint ) {
		if ( strpos( $title_text, $hint ) !== false ) {
			$signals[] = $hint;
		}
	}

	echo mnsk7_render_related_guide_articles( mnsk7_get_related_guide_articles( $signals, 3 ), __( 'Poradniki do tego produktu', 'mnsk7-tools' ) ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}
add_action( 'woocommerce_after_single_product_summary', 'mnsk7_render_related_guides_on_product_page', 12 );

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
				if ( $atts['format'] === 'grid' && shortcode_exists( 'products' ) ) {
					$out .= do_shortcode( sprintf(
						'[products category="%s" limit="%d" columns="3" orderby="popularity"]',
						esc_attr( $term->slug ),
						$limit
					) );
				}
			}
		}
		return $out ? '<div id="mnsk7-guide-products" class="mnsk7-guide-products">' . $out . '</div>' : '';
	}

	// Wiele kategorii: lista linków
	if ( $atts['categories'] !== '' && taxonomy_exists( 'product_cat' ) ) {
		$slugs = array_filter( array_map( 'trim', explode( ',', $atts['categories'] ) ) );
		$links = array();
		$valid_slugs = array();
		foreach ( $slugs as $slug ) {
			$term = get_term_by( 'slug', sanitize_title( $slug ), 'product_cat' );
			if ( $term && ! is_wp_error( $term ) ) {
				$url = get_term_link( $term );
				if ( ! is_wp_error( $url ) ) {
					$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
					$links[] = '<a href="' . esc_url( $url ) . '">' . esc_html( $name ) . '</a>';
					$valid_slugs[] = $term->slug;
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
		if ( $atts['format'] === 'grid' && ! empty( $valid_slugs ) && shortcode_exists( 'products' ) ) {
			$out .= do_shortcode( sprintf(
				'[products category="%s" limit="%d" columns="3" orderby="popularity"]',
				esc_attr( implode( ',', array_unique( $valid_slugs ) ) ),
				$limit
			) );
		}
		return '<div id="mnsk7-guide-products" class="mnsk7-guide-products">' . $out . '</div>';
	}

	// Konkretne ID produktów
	if ( $atts['ids'] !== '' && function_exists( 'wc_get_product' ) ) {
		$ids = array_filter( array_map( 'absint', explode( ',', $atts['ids'] ) ) );
		$ids = array_slice( $ids, 0, 12 );
		$links = array();
		$visible_ids = array();
		foreach ( $ids as $id ) {
			$product = wc_get_product( $id );
			if ( $product && $product->is_visible() ) {
				$visible_ids[] = $id;
				$links[] = '<a href="' . esc_url( $product->get_permalink() ) . '">' . esc_html( $product->get_name() ) . '</a>';
			}
		}
		if ( empty( $links ) ) {
			return '';
		}
		if ( $atts['title'] ) {
			$out .= '<h3 class="mnsk7-guide-products__title">' . esc_html( $atts['title'] ) . '</h3>';
		}
		if ( $atts['format'] === 'grid' && shortcode_exists( 'products' ) ) {
			$out .= do_shortcode( sprintf(
				'[products ids="%s" limit="%d" columns="3" orderby="post__in"]',
				esc_attr( implode( ',', $visible_ids ) ),
				min( $limit, count( $visible_ids ) )
			) );
			return '<div id="mnsk7-guide-products" class="mnsk7-guide-products mnsk7-guide-products--curated">' . $out . '</div>';
		}
		$out .= '<ul class="mnsk7-guide-products__list">';
		foreach ( $links as $link ) {
			$out .= '<li>' . $link . '</li>';
		}
		$out .= '</ul>';
		return '<div id="mnsk7-guide-products" class="mnsk7-guide-products">' . $out . '</div>';
	}

	return '';
}
