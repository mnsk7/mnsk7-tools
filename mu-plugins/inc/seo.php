<?php
/**
 * MNK7 Tools — SEO: Organization schema, auto-alt, Yoast meta, opis kategorii.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_get_catalog_archive_seo_context' ) ) {
	/**
	 * Collects the current catalog archive context for SEO titles/canonicals.
	 *
	 * @return array{term: ?WP_Term, base_url: string, paged: int}
	 */
	function mnsk7_get_catalog_archive_seo_context() {
		$context = array(
			'term'     => null,
			'base_url' => '',
			'paged'    => 1,
		);

		if ( ! function_exists( 'is_shop' ) || ! function_exists( 'wc_get_page_permalink' ) ) {
			return $context;
		}

		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged < 1 ) {
			$paged = 1;
		}
		$context['paged'] = $paged;

		$term = null;
		if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
			$queried = get_queried_object();
			if ( $queried instanceof WP_Term && in_array( $queried->taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
				$term = $queried;
			}
		} elseif ( is_shop() ) {
			foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
				if ( empty( $_GET[ $taxonomy ] ) ) {
					continue;
				}
				$slug = sanitize_text_field( wp_unslash( $_GET[ $taxonomy ] ) );
				if ( $slug === '' ) {
					continue;
				}
				$maybe_term = get_term_by( 'slug', $slug, $taxonomy );
				if ( $maybe_term && ! is_wp_error( $maybe_term ) ) {
					$term = $maybe_term;
					break;
				}
			}
		}

		if ( $term instanceof WP_Term ) {
			$link = get_term_link( $term );
			if ( ! is_wp_error( $link ) ) {
				$context['term']     = $term;
				$context['base_url'] = $link;
				return $context;
			}
		}

		if ( is_shop() ) {
			$context['base_url'] = wc_get_page_permalink( 'shop' );
		}

		return $context;
	}
}

if ( ! function_exists( 'mnsk7_get_catalog_archive_seo_title' ) ) {
	/**
	 * Builds a commercial title for the catalog archive context.
	 *
	 * @return string
	 */
	function mnsk7_get_catalog_archive_seo_title() {
		$context = mnsk7_get_catalog_archive_seo_context();
		if ( empty( $context['term'] ) || ! ( $context['term'] instanceof WP_Term ) ) {
			return '';
		}

		$name = $context['term']->name;
		if ( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ) {
			$name = mnsk7_strip_wpf_filters_from_text( $name );
		}
		$name = trim( (string) $name );
		if ( $name === '' ) {
			return '';
		}

		return sprintf( '%1$s - %2$s', $name, __( 'sklep CNC', 'mnsk7-tools' ) );
	}
}

if ( ! function_exists( 'mnsk7_get_catalog_archive_canonical_url' ) ) {
	/**
	 * Returns a clean canonical URL for shop/category/tag archive states.
	 *
	 * @return string
	 */
	function mnsk7_get_catalog_archive_canonical_url() {
		$context = mnsk7_get_catalog_archive_seo_context();
		if ( empty( $context['base_url'] ) || is_wp_error( $context['base_url'] ) ) {
			return '';
		}

		$url = untrailingslashit( $context['base_url'] );
		if ( ! empty( $context['paged'] ) && (int) $context['paged'] > 1 ) {
			$url = trailingslashit( $url ) . user_trailingslashit( sprintf( 'page/%d', (int) $context['paged'] ), 'paged' );
		} else {
			$url = trailingslashit( $url );
		}

		return $url;
	}
}

/* Organization + OnlineStore JSON-LD */
add_action( 'wp_head', function () {
	$schema = array(
		'@context'     => 'https://schema.org',
		'@type'        => array( 'Organization', 'OnlineStore' ),
		'name'         => 'MNK7 Tools',
		'legalName'    => 'MNSK7 SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ',
		'url'          => home_url( '/' ),
		'logo'         => array( '@type' => 'ImageObject', 'url' => get_site_icon_url( 512 ) ?: home_url( '/wp-content/themes/tech-storefront/assets/images/logo.png' ) ),
		'contactPoint' => array(
			'@type' => 'ContactPoint', 'telephone' => MNK7_CONTACT_PHONE,
			'email' => MNK7_CONTACT_EMAIL, 'contactType' => 'customer service',
			'availableLanguage' => array( 'Polish' ),
			'hoursAvailable' => array(
				array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ), 'opens' => '09:00', 'closes' => '17:00' ),
				array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '10:00', 'closes' => '12:00' ),
			),
		),
		'address'    => array( '@type' => 'PostalAddress', 'streetAddress' => 'ul. Williama Heerleina Lindleya 16/512', 'addressLocality' => 'Warszawa', 'postalCode' => '02-013', 'addressCountry' => 'PL' ),
		'vatID'      => 'PL5242991741',
		'taxID'      => '5242991741',
		'sameAs'     => array( MNK7_INSTAGRAM_URL, MNK7_ALLEGRO_SELLER_URL ),
		'areaServed' => array( '@type' => 'Country', 'name' => 'Poland' ),
		'description' => __( 'Sklep z frezami CNC i narzędziami do obróbki drewna, MDF, aluminium, stali i tworzyw sztucznych. Dostawa następnego dnia, faktura VAT.', 'mnsk7-tools' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5 );

/* Auto-alt dla zdjęć produktów (~1634 bez alt w bazie stagingg) */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( ! empty( $attr['alt'] ) ) {
		return $attr;
	}
	$alt = trim( (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) );
	if ( $alt !== '' ) {
		$attr['alt'] = $alt;
		return $attr;
	}
	$parent_id = (int) $attachment->post_parent;
	if ( $parent_id > 0 ) {
		$parent = get_post( $parent_id );
		if ( $parent ) {
			$sku = $parent->post_type === 'product' ? get_post_meta( $parent_id, '_sku', true ) : '';
			$attr['alt'] = trim( $parent->post_title . ( $sku ? ' | ' . $sku : '' ) );
			return $attr;
		}
	}
	if ( ! empty( $attachment->post_title ) ) {
		$attr['alt'] = $attachment->post_title;
	}
	return $attr;
}, 20, 2 );

/* Yoast: auto meta description dla produktów */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ! empty( $desc ) || ! is_singular( 'product' ) ) {
		return $desc;
	}
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return $desc;
	}
	$parts = array( $product->get_name() );
	$sred  = $product->get_attribute( 'srednica' ) ?: $product->get_attribute( 'pa_srednica' );
	$zast  = $product->get_attribute( 'zastosowanie' ) ?: $product->get_attribute( 'pa_zastosowanie' );
	if ( $sred ) $parts[] = '| Ø' . $sred;
	if ( $zast ) $parts[] = '| ' . $zast;
	return implode( ' ', $parts ) . ' — ' . __( 'Dostawa następnego dnia. Faktura VAT. Zamów na mnsk7-tools.pl.', 'mnsk7-tools' );
}, 20 );

/* Yoast: auto meta description dla kategorii */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ! empty( $desc ) || ! is_product_category() ) {
		return $desc;
	}
	$cat = get_queried_object();
	if ( ! $cat ) return $desc;
	return sprintf(
		__( '%1$s — %2$d produktów. Dostawa następnego dnia. Faktura VAT. Sklep mnsk7-tools.pl — frezy CNC i narzędzia skrawające.', 'mnsk7-tools' ),
		$cat->name, (int) $cat->count
	);
}, 21 );

add_filter( 'wpseo_title', function ( $title ) {
	$catalog_title = function_exists( 'mnsk7_get_catalog_archive_seo_title' ) ? mnsk7_get_catalog_archive_seo_title() : '';
	if ( $catalog_title !== '' ) {
		return $catalog_title;
	}
	return $title;
}, 20 );

add_filter( 'wpseo_canonical', function ( $canonical ) {
	$catalog_canonical = function_exists( 'mnsk7_get_catalog_archive_canonical_url' ) ? mnsk7_get_catalog_archive_canonical_url() : '';
	if ( $catalog_canonical !== '' ) {
		return $catalog_canonical;
	}
	return $canonical;
}, 20 );

add_action( 'wp_head', function () {
	$catalog_canonical = function_exists( 'mnsk7_get_catalog_archive_canonical_url' ) ? mnsk7_get_catalog_archive_canonical_url() : '';
	if ( $catalog_canonical === '' ) {
		return;
	}
	echo '<link rel="canonical" href="' . esc_url( $catalog_canonical ) . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 2 );

/* Opis archiwum taksonomii (kategoria, tag) — zastępuje domyślny WooCommerce hook */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
add_action( 'woocommerce_archive_description', function () {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return;
	}
	$img_id = get_term_meta( $term->term_id, 'thumbnail_id', true );
	$desc   = term_description();
	if ( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ) {
		$desc = mnsk7_strip_wpf_filters_from_text( (string) $desc );
	} else {
		$desc = preg_replace( '/\[wpf[-_]filters[^\]]*\]/i', '', (string) $desc );
		$desc = trim( $desc );
	}
	if ( empty( $img_id ) && empty( $desc ) ) {
		return;
	}
	echo '<div class="mnsk7-cat-header">';
	if ( $img_id ) {
		$alt = ( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name );
		echo '<div class="mnsk7-cat-header__img">'
			. wp_get_attachment_image( (int) $img_id, 'medium', false, array( 'alt' => esc_attr( $alt ), 'loading' => 'eager' ) )
			. '</div>';
	}
	if ( $desc !== '' ) {
		echo '<div class="mnsk7-cat-header__desc">' . wp_kses_post( $desc ) . '</div>';
	}
	echo '</div>';
}, 10 );
