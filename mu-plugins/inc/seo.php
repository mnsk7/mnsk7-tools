<?php
/**
 * MNK7 Tools — SEO: Organization schema, auto-alt, Yoast meta, opis kategorii.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_is_catalog_filter_request' ) ) {
	/** Whether the current catalog URL contains a non-canonical filter/sort state. */
	function mnsk7_is_catalog_filter_request() {
		foreach ( array_keys( $_GET ) as $raw_key ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			$key = sanitize_key( (string) $raw_key );
			if ( strpos( $key, 'filter_' ) === 0 || strpos( $key, 'query_type_' ) === 0 ) {
				return true;
			}
			if ( in_array( $key, array( 'min_price', 'max_price', 'rating_filter', 'stock_status', 'orderby' ), true ) ) {
				return true;
			}
		}
		return false;
	}
}

if ( ! function_exists( 'mnsk7_is_product_attribute_archive' ) ) {
	/** Whether this request is a public pa_* WooCommerce attribute archive. */
	function mnsk7_is_product_attribute_archive() {
		$term = get_queried_object();
		return $term instanceof WP_Term && strpos( (string) $term->taxonomy, 'pa_' ) === 0;
	}
}

if ( ! function_exists( 'mnsk7_get_system_page_paths' ) ) {
	/** Public utility-page paths that must work but must not appear in search. */
	function mnsk7_get_system_page_paths() {
		return array( 'login', 'logowanie', 'register', 'edit-profile', 'wishlist', 'lista-zyczen', 'reset', 'reset-password', 'password-reset', 'lost-password', 'odzyskaj-haslo' );
	}
}

if ( ! function_exists( 'mnsk7_get_indexable_product_tag_slugs' ) ) {
	/** Controlled SEO facets that have catalog demand and dedicated term content. */
	function mnsk7_get_indexable_product_tag_slugs() {
		return array( 'aluminium', 'mdf', 'stal' );
	}
}

if ( ! function_exists( 'mnsk7_is_indexable_product_tag' ) ) {
	/** Whether the current request is one of the controlled product-tag facets. */
	function mnsk7_is_indexable_product_tag() {
		$term = get_queried_object();
		return $term instanceof WP_Term
			&& $term->taxonomy === 'product_tag'
			&& in_array( $term->slug, mnsk7_get_indexable_product_tag_slugs(), true );
	}
}

if ( ! function_exists( 'mnsk7_get_legacy_seo_page_targets' ) ) {
	/**
	 * Exact legacy landing replacements. No page is sent to a broad parent or home.
	 *
	 * @return array<string, array{taxonomy: string, term: string}>
	 */
	function mnsk7_get_legacy_seo_page_targets() {
		return array(
			'frez-spiralny'                 => array( 'taxonomy' => 'product_cat', 'term' => 'frezy-spiralne' ),
			'frez-prosty'                   => array( 'taxonomy' => 'product_cat', 'term' => 'frezy-proste' ),
			'frez-spiralny-stozkowo-kulowy' => array( 'taxonomy' => 'product_cat', 'term' => 'frezy-stozkowo-kulowe' ),
			'frez-grawerski'                => array( 'taxonomy' => 'product_cat', 'term' => 'frezy-grawerskie' ),
			'frez-kulowy'                   => array( 'taxonomy' => 'product_cat', 'term' => 'frezy-kulowe' ),
			'plytki-wieloostrzowe'          => array( 'taxonomy' => 'product_cat', 'term' => 'plytki-wieloostrzowe' ),
			'tuleje-zaciskowe'              => array( 'taxonomy' => 'product_cat', 'term' => 'tuleje-zaciskowe-osprzet-i-akcesoria' ),
			'frez-diamentowy'               => array( 'taxonomy' => 'product_cat', 'term' => 'frezy-diamentowe' ),
			'frezy-do-aluminium'            => array( 'taxonomy' => 'product_tag', 'term' => 'aluminium' ),
			'frezy-mdf'                     => array( 'taxonomy' => 'product_tag', 'term' => 'mdf' ),
			'frezy-do-stali'                => array( 'taxonomy' => 'product_tag', 'term' => 'stal' ),
		);
	}
}

if ( ! function_exists( 'mnsk7_get_legacy_seo_page_target_url' ) ) {
	/** Resolve an exact taxonomy target, returning an empty string if catalog data changed. */
	function mnsk7_get_legacy_seo_page_target_url( $post ) {
		if ( ! $post instanceof WP_Post || $post->post_type !== 'page' ) {
			return '';
		}
		$targets = mnsk7_get_legacy_seo_page_targets();
		if ( ! isset( $targets[ $post->post_name ] ) ) {
			return '';
		}
		$target = $targets[ $post->post_name ];
		$term   = get_term_by( 'slug', $target['term'], $target['taxonomy'] );
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		$url = get_term_link( $term );
		return is_wp_error( $url ) ? '' : (string) $url;
	}
}

if ( ! function_exists( 'mnsk7_get_temporary_noindex_page_slugs' ) ) {
	/** Legacy landings without one defensible exact catalog equivalent. */
	function mnsk7_get_temporary_noindex_page_slugs() {
		return array( 'frezy-do-szlifierki', 'frez-z-wymiennymi-plytkami' );
	}
}

if ( ! function_exists( 'mnsk7_is_system_page' ) ) {
	/** System/customer page that must work but must not appear in search. */
	function mnsk7_is_system_page() {
		if ( ( function_exists( 'is_cart' ) && is_cart() )
			|| ( function_exists( 'is_checkout' ) && is_checkout() )
			|| ( function_exists( 'is_account_page' ) && is_account_page() ) ) {
			return true;
		}

		if ( is_page( mnsk7_get_system_page_paths() ) ) {
			return true;
		}
		$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
		$request_slug = sanitize_title( basename( untrailingslashit( $request_path ) ) );
		if ( in_array( $request_slug, mnsk7_get_system_page_paths(), true ) ) {
			return true;
		}

		$post = get_queried_object();
		return $post instanceof WP_Post
			&& $post->post_type === 'page'
			&& in_array( $post->post_name, mnsk7_get_system_page_paths(), true );
	}
}

if ( ! function_exists( 'mnsk7_is_offer_variant_page' ) ) {
	/** Whether a page belongs to the reusable /oferta-*-warianty/ family. */
	function mnsk7_is_offer_variant_page( $post ) {
		if ( ! $post instanceof WP_Post || $post->post_type !== 'page' ) {
			return false;
		}

		$uri      = trim( (string) get_page_uri( $post ), '/' );
		$segments = $uri === '' ? array() : explode( '/', $uri );
		$slug     = (string) end( $segments );
		return (bool) preg_match( '/^oferta-[a-z0-9-]*-warianty(?:-[2-9][0-9]*)?$/', $slug );
	}
}

if ( ! function_exists( 'mnsk7_get_offer_duplicate_page_id' ) ) {
	/**
	 * Resolve WordPress' numeric duplicate suffix only when the exact base page
	 * exists and has the same commercial title.
	 */
	function mnsk7_get_offer_duplicate_page_id( $post ) {
		if ( ! mnsk7_is_offer_variant_page( $post ) || ! preg_match( '/^(.+-warianty)-([2-9][0-9]*)$/', $post->post_name, $matches ) ) {
			return 0;
		}

		$uri         = trim( (string) get_page_uri( $post ), '/' );
		$base_uri    = preg_replace( '/-([2-9][0-9]*)$/', '', $uri );
		$target_page = get_page_by_path( $base_uri, OBJECT, 'page' );
		if ( ! $target_page instanceof WP_Post || $target_page->post_status !== 'publish' || $target_page->ID === $post->ID ) {
			return 0;
		}

		$current_title = trim( wp_strip_all_tags( $post->post_title ) );
		$target_title  = trim( wp_strip_all_tags( $target_page->post_title ) );
		return $current_title !== '' && $current_title === $target_title ? (int) $target_page->ID : 0;
	}
}

if ( ! function_exists( 'mnsk7_get_offer_seo_state' ) ) {
	/**
	 * Classify an /oferta-*-warianty/ page without unsafe blanket redirects.
	 * Explicit post meta wins; otherwise only a visible preparation notice is noindexed.
	 */
	function mnsk7_get_offer_seo_state( $post_id ) {
		$post = get_post( $post_id );
		if ( ! mnsk7_is_offer_variant_page( $post ) ) {
			return '';
		}

		$explicit = sanitize_key( (string) get_post_meta( $post->ID, '_mnsk7_offer_seo_state', true ) );
		if ( in_array( $explicit, array( 'ready', 'duplicate', 'draft' ), true ) ) {
			return $explicit;
		}
		if ( mnsk7_get_offer_duplicate_page_id( $post ) > 0 ) {
			return 'duplicate';
		}

		$content = remove_accents( wp_strip_all_tags( $post->post_title . ' ' . $post->post_content ) );
		return stripos( $content, 'w przygotowaniu' ) !== false ? 'draft' : 'ready';
	}
}

if ( ! function_exists( 'mnsk7_is_shopengine_template_request' ) ) {
	/** Whether a singular ShopEngine template is being exposed as a public URL. */
	function mnsk7_is_shopengine_template_request() {
		$post_type = (string) get_post_type( get_queried_object_id() );
		return is_singular() && ( $post_type === 'shopengine-template' || strpos( $post_type, 'shopengine' ) === 0 );
	}
}

if ( ! function_exists( 'mnsk7_should_noindex_request' ) ) {
	/** Central robots decision used by Yoast and WordPress core. */
	function mnsk7_should_noindex_request() {
		if ( mnsk7_is_product_attribute_archive() || mnsk7_is_system_page() || mnsk7_is_shopengine_template_request() ) {
			return true;
		}
		if ( is_page( mnsk7_get_temporary_noindex_page_slugs() ) ) {
			return true;
		}
		if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && mnsk7_is_catalog_filter_request() ) {
			return true;
		}
		return is_singular( 'page' ) && in_array( mnsk7_get_offer_seo_state( get_queried_object_id() ), array( 'draft', 'duplicate' ), true );
	}
}

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

if ( ! function_exists( 'mnsk7_is_guide_archive' ) ) {
	/**
	 * Whether the current request is the public Przewodnik archive.
	 *
	 * @return bool
	 */
	function mnsk7_is_guide_archive() {
		return is_category( 'przewodnik' );
	}
}

if ( ! function_exists( 'mnsk7_is_guide_post' ) ) {
	/**
	 * Whether the current request is a Przewodnik article.
	 *
	 * @return bool
	 */
	function mnsk7_is_guide_post() {
		return is_singular( 'post' ) && has_category( 'przewodnik', get_queried_object_id() );
	}
}

if ( ! function_exists( 'mnsk7_get_guide_archive_canonical_url' ) ) {
	/**
	 * Returns a self-referencing canonical for the paginated guide archive.
	 *
	 * @return string
	 */
	function mnsk7_get_guide_archive_canonical_url() {
		if ( ! mnsk7_is_guide_archive() ) {
			return '';
		}
		$term = get_queried_object();
		if ( ! $term instanceof WP_Term ) {
			return '';
		}
		$url = get_term_link( $term );
		if ( is_wp_error( $url ) ) {
			return '';
		}
		$paged = max( 1, (int) get_query_var( 'paged' ) );
		if ( $paged > 1 ) {
			return trailingslashit( $url ) . user_trailingslashit( sprintf( 'page/%d', $paged ), 'paged' );
		}
		return trailingslashit( $url );
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
		'name'         => 'MNSK7 Tool',
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
		'description' => __( 'Sklep z frezami CNC i narzędziami do obróbki drewna, MDF, aluminium, stali i tworzyw sztucznych. Wysyłka w dni robocze, faktura VAT.', 'mnsk7-tools' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5 );

/**
 * Builds useful, contextual alt text for a product image.
 *
 * @param WC_Product $product       Product represented by the image.
 * @param int        $attachment_id Attachment ID.
 * @param string     $fallback      Existing useful alt text.
 * @return string
 */
function mnsk7_product_image_alt_text( $product, $attachment_id, $fallback = '' ) {
	$fallback = trim( wp_strip_all_tags( (string) $fallback ) );
	if ( ! $product instanceof WC_Product ) {
		return $fallback;
	}

	$name      = trim( wp_strip_all_tags( $product->get_name() ) );
	$image_ids = array_values( array_unique( array_filter( array_merge( array( $product->get_image_id() ), $product->get_gallery_image_ids() ) ) ) );
	$position  = array_search( (int) $attachment_id, array_map( 'intval', $image_ids ), true );
	if ( $name !== '' && $position !== false ) {
		return $position > 0
			? sprintf( __( '%1$s — zdjęcie %2$d', 'mnsk7-tools' ), $name, $position + 1 )
			: $name;
	}

	return $fallback;
}

/**
 * Detects empty, hash-like and camera-default alt values.
 *
 * @param string $alt Alt text.
 * @return bool
 */
function mnsk7_image_alt_needs_fallback( $alt ) {
	$alt = trim( (string) $alt );
	return $alt === ''
		|| (bool) preg_match( '/^[a-f0-9]{16,}(?:-\d+)?$/i', $alt )
		|| (bool) preg_match( '/^(?:img|image|photo|dsc)[-_ ]?\d+$/i', $alt );
}

/* Auto-alt dla zdjęć produktów i artykułów. */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	$current_alt = isset( $attr['alt'] ) ? trim( (string) $attr['alt'] ) : '';
	if ( ! mnsk7_image_alt_needs_fallback( $current_alt ) ) {
		return $attr;
	}
	$alt = trim( (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) );
	if ( ! mnsk7_image_alt_needs_fallback( $alt ) ) {
		$attr['alt'] = $alt;
		return $attr;
	}

	global $product;
	if ( $product instanceof WC_Product ) {
		$product_alt = mnsk7_product_image_alt_text( $product, $attachment->ID, $current_alt );
		if ( $product_alt !== '' ) {
			$attr['alt'] = $product_alt;
			return $attr;
		}
	}

	$parent_id = (int) $attachment->post_parent;
	if ( $parent_id > 0 ) {
		$parent = get_post( $parent_id );
		if ( $parent ) {
			if ( $parent->post_type === 'product' && function_exists( 'wc_get_product' ) ) {
				$parent_product = wc_get_product( $parent_id );
				$attr['alt']    = mnsk7_product_image_alt_text( $parent_product, $attachment->ID, $parent->post_title );
			} else {
				$attr['alt'] = trim( wp_strip_all_tags( $parent->post_title ) );
			}
			return $attr;
		}
	}
	if ( ! empty( $attachment->post_title ) && ! mnsk7_image_alt_needs_fallback( $attachment->post_title ) ) {
		$attr['alt'] = $attachment->post_title;
	}
	return $attr;
}, 20, 2 );

/* Yoast: auto meta description dla produktów */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( mnsk7_is_guide_archive() ) {
		$paged  = max( 1, (int) get_query_var( 'paged' ) );
		$suffix = $paged > 1 ? sprintf( __( ' Strona %d.', 'mnsk7-tools' ), $paged ) : '';
		return __( 'Praktyczne poradniki o frezach CNC, doborze narzędzi, obróbce drewna, aluminium, stali i materiałów płytowych.', 'mnsk7-tools' ) . $suffix;
	}
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
	return implode( ' ', $parts ) . ' — ' . __( 'Wysyłka w dni robocze. Faktura VAT. Zamów na mnsk7-tools.pl.', 'mnsk7-tools' );
}, 20 );

/* Yoast: auto meta description dla kategorii */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ! empty( $desc ) || ! is_product_category() ) {
		return $desc;
	}
	$cat = get_queried_object();
	if ( ! $cat ) return $desc;
	return sprintf(
		__( '%1$s — %2$d produktów. Wysyłka w dni robocze. Faktura VAT. Sklep mnsk7-tools.pl — frezy CNC i narzędzia skrawające.', 'mnsk7-tools' ),
		$cat->name, (int) $cat->count
	);
}, 21 );

/* Indexable brands and pages receive a conservative fallback only when Yoast is empty. */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( trim( (string) $desc ) !== '' || mnsk7_should_noindex_request() ) {
		return $desc;
	}

	$term = get_queried_object();
	if ( $term instanceof WP_Term && $term->taxonomy === 'product_brand' ) {
		return sprintf(
			'%1$s w sklepie MNSK7: sprawdź aktualne produkty, warianty, ceny i dostępność narzędzi do obróbki CNC.',
			$term->name
		);
	}
	if ( ! is_singular( 'page' ) ) {
		return $desc;
	}

	$post = get_post( get_queried_object_id() );
	if ( ! $post instanceof WP_Post || mnsk7_get_legacy_seo_page_target_url( $post ) !== '' ) {
		return $desc;
	}
	$text = trim( preg_replace( '/\s+/u', ' ', wp_strip_all_tags( strip_shortcodes( $post->post_content ) ) ) );
	if ( strlen( $text ) >= 80 ) {
		return wp_html_excerpt( $text, 155, '…' );
	}
	if ( mnsk7_is_offer_variant_page( $post ) ) {
		return wp_html_excerpt( $post->post_title . ' — sprawdź opis wariantu, zastosowanie oraz aktualne dane produktu w sklepie MNSK7.', 155, '…' );
	}
	if ( get_page_template_slug( $post->ID ) === 'page-kontakt.php' ) {
		return 'Skontaktuj się ze sklepem MNSK7 w sprawie doboru frezów CNC, zamówienia, dostawy lub obsługi posprzedażowej.';
	}
	if ( get_page_template_slug( $post->ID ) === 'page-dostawa.php' ) {
		return 'Sprawdź metody, zasady i aktualne informacje dotyczące dostawy zamówień ze sklepu MNSK7.';
	}
	return wp_html_excerpt( $post->post_title . ' — praktyczne informacje, dobór narzędzi i aktualna oferta sklepu MNSK7.', 155, '…' );
}, 30 );

/* Curated taxonomy metadata lives in term meta and overrides generic fallbacks. */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return $desc;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return $desc;
	}
	$custom = trim( (string) get_term_meta( $term->term_id, '_mnsk7_term_seo_metadesc', true ) );
	return $custom !== '' ? $custom : $desc;
}, 40 );

add_filter( 'wpseo_title', function ( $title ) {
	if ( mnsk7_is_guide_archive() ) {
		$paged  = max( 1, (int) get_query_var( 'paged' ) );
		$suffix = $paged > 1 ? sprintf( __( ' — strona %d', 'mnsk7-tools' ), $paged ) : '';
		return __( 'Przewodnik CNC — frezy, dobór narzędzi i obróbka', 'mnsk7-tools' ) . $suffix . ' | MNSK7 Tool';
	}
	if ( mnsk7_is_guide_post() ) {
		if ( get_post_field( 'post_name', get_queried_object_id() ) === 'frez-do-wyrownania-sleba-i-planowania-powierzchni' ) {
			return 'Frez do planowania drewna i slabów — jak wybrać? | MNSK7 Tool';
		}
		return get_the_title( get_queried_object_id() ) . ' | MNSK7 Tool';
	}
	$catalog_title = function_exists( 'mnsk7_get_catalog_archive_seo_title' ) ? mnsk7_get_catalog_archive_seo_title() : '';
	if ( $catalog_title !== '' ) {
		return $catalog_title;
	}
	return $title;
}, 20 );

add_filter( 'wpseo_title', function ( $title ) {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return $title;
	}
	$term = get_queried_object();
	if ( ! $term instanceof WP_Term ) {
		return $title;
	}
	$custom = trim( (string) get_term_meta( $term->term_id, '_mnsk7_term_seo_title', true ) );
	if ( $custom === '' ) {
		return $title;
	}
	$paged = max( 1, (int) get_query_var( 'paged' ) );
	return $paged > 1 ? $custom . sprintf( ' — strona %d', $paged ) : $custom;
}, 40 );

add_filter( 'wpseo_canonical', function ( $canonical ) {
	if ( mnsk7_is_guide_archive() ) {
		// The archive canonical is printed once by our fallback below because Yoast
		// suppresses its canonical presenter for categories configured as noindex.
		return '';
	}
	if ( mnsk7_is_indexable_product_tag() ) {
		// Printed exactly once by the fallback below because Yoast's taxonomy
		// setting can suppress its canonical presenter for product tags.
		return '';
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() ) && mnsk7_is_catalog_filter_request() ) {
		// Printed by the fallback below so filtered states always have exactly one
		// clean canonical even when Yoast suppresses canonicals for noindex pages.
		return '';
	}
	if ( is_singular( 'page' ) && mnsk7_get_offer_seo_state( get_queried_object_id() ) === 'ready' ) {
		return get_permalink( get_queried_object_id() );
	}

	$catalog_canonical = function_exists( 'mnsk7_get_catalog_archive_canonical_url' ) ? mnsk7_get_catalog_archive_canonical_url() : '';
	if ( $catalog_canonical !== '' ) {
		return $catalog_canonical;
	}
	return $canonical;
}, 20 );

add_action( 'wp_head', function () {
	$is_filtered_catalog = function_exists( 'is_shop' )
		&& ( is_shop() || is_product_taxonomy() )
		&& mnsk7_is_catalog_filter_request();
	if ( ! $is_filtered_catalog && ! mnsk7_is_indexable_product_tag() ) {
		return;
	}
	$url = mnsk7_is_indexable_product_tag() ? get_term_link( get_queried_object() ) : mnsk7_get_catalog_archive_canonical_url();
	if ( is_wp_error( $url ) ) {
		$url = '';
	}
	if ( $url !== '' ) {
		echo '<link rel="canonical" href="' . esc_url( $url ) . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}, 2 );

add_filter( 'wpseo_robots', function ( $robots ) {
	if ( mnsk7_should_noindex_request() ) {
		return 'noindex, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
	}
	if ( ( mnsk7_is_guide_archive() || mnsk7_is_indexable_product_tag() ) && wp_get_environment_type() === 'production' ) {
		return 'index, follow, max-image-preview:large, max-snippet:-1, max-video-preview:-1';
	}
	return $robots;
}, 30 );

/* Keep the same robots policy when Yoast is disabled or replaced. */
add_filter( 'wp_robots', function ( $robots ) {
	if ( mnsk7_is_indexable_product_tag() && wp_get_environment_type() === 'production' ) {
		$robots['index']  = true;
		$robots['follow'] = true;
		unset( $robots['noindex'], $robots['nofollow'] );
		return $robots;
	}
	if ( ! mnsk7_should_noindex_request() ) {
		return $robots;
	}
	$robots['noindex'] = true;
	$robots['follow']  = true;
	unset( $robots['index'], $robots['nofollow'] );
	return $robots;
}, 30 );

/* Attribute taxonomies and ShopEngine templates never belong in Yoast XML sitemaps. */
add_filter( 'wpseo_sitemap_exclude_taxonomy', function ( $excluded, $taxonomy ) {
	if ( strpos( (string) $taxonomy, 'pa_' ) === 0 || $taxonomy === 'product_tag' ) {
		return true;
	}
	return $excluded;
}, 20, 2 );

/*
 * Yoast's global product-tag noindex setting suppresses the native taxonomy
 * sitemap before term filters run. Publish only the controlled facets through
 * one small sitemap instead of enabling every product tag.
 */
add_filter( 'wpseo_sitemap_index', function ( $sitemap_index ) {
	$sitemap_index .= '<sitemap><loc>' . esc_url( home_url( '/material-facets-sitemap.xml' ) ) . '</loc></sitemap>';
	return $sitemap_index;
}, 20 );

add_action( 'template_redirect', function () {
	$request_path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_parse_url( wp_unslash( $_SERVER['REQUEST_URI'] ), PHP_URL_PATH ) : ''; // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
	if ( untrailingslashit( $request_path ) !== '/material-facets-sitemap.xml' ) {
		return;
	}

	$urls = array();
	foreach ( mnsk7_get_indexable_product_tag_slugs() as $slug ) {
		$term = get_term_by( 'slug', $slug, 'product_tag' );
		if ( ! $term instanceof WP_Term || (int) $term->count < 1 ) {
			continue;
		}
		$url = get_term_link( $term );
		if ( ! is_wp_error( $url ) ) {
			$urls[] = $url;
		}
	}

	status_header( 200 );
	header( 'Content-Type: application/xml; charset=UTF-8' );
	header( 'X-Robots-Tag: noindex, follow', true );
	echo '<?xml version="1.0" encoding="UTF-8"?>' . "\n";
	echo '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">';
	foreach ( $urls as $url ) {
		echo '<url><loc>' . esc_xml( $url ) . '</loc></url>';
	}
	echo '</urlset>';
	exit;
}, 0 );

add_filter( 'wpseo_sitemap_exclude_post_type', function ( $excluded, $post_type ) {
	return ( $post_type === 'shopengine-template' || strpos( (string) $post_type, 'shopengine' ) === 0 ) ? true : $excluded;
}, 20, 2 );

add_filter( 'wpseo_exclude_from_sitemap_by_post_ids', function ( $excluded_ids ) {
	$excluded_ids = array_map( 'absint', (array) $excluded_ids );
	if ( function_exists( 'wc_get_page_id' ) ) {
		foreach ( array( 'cart', 'checkout', 'myaccount' ) as $page_key ) {
			$page_id = absint( wc_get_page_id( $page_key ) );
			if ( $page_id > 0 ) {
				$excluded_ids[] = $page_id;
			}
		}
	}
	foreach ( mnsk7_get_system_page_paths() as $path ) {
		$page = get_page_by_path( $path );
		if ( $page instanceof WP_Post ) {
			$excluded_ids[] = $page->ID;
		}
	}
	foreach ( array_keys( mnsk7_get_legacy_seo_page_targets() ) as $path ) {
		$page = get_page_by_path( $path );
		if ( $page instanceof WP_Post && mnsk7_get_legacy_seo_page_target_url( $page ) !== '' ) {
			$excluded_ids[] = $page->ID;
		}
	}
	foreach ( mnsk7_get_temporary_noindex_page_slugs() as $path ) {
		$page = get_page_by_path( $path );
		if ( $page instanceof WP_Post ) {
			$excluded_ids[] = $page->ID;
		}
	}
	foreach ( get_posts( array( 'post_type' => 'page', 'post_status' => 'publish', 'numberposts' => -1 ) ) as $page ) {
		if ( in_array( mnsk7_get_offer_seo_state( $page->ID ), array( 'draft', 'duplicate' ), true ) ) {
			$excluded_ids[] = $page->ID;
		}
	}
	return array_values( array_unique( array_filter( $excluded_ids ) ) );
}, 20 );

/* Core sitemap parity for installations where Yoast is temporarily unavailable. */
add_filter( 'wp_sitemaps_taxonomies', function ( $taxonomies ) {
	foreach ( array_keys( (array) $taxonomies ) as $taxonomy ) {
		if ( strpos( (string) $taxonomy, 'pa_' ) === 0 ) {
			unset( $taxonomies[ $taxonomy ] );
		}
	}
	return $taxonomies;
} );

add_filter( 'wp_sitemaps_taxonomies_query_args', function ( $args, $taxonomy ) {
	if ( $taxonomy !== 'product_tag' ) {
		return $args;
	}
	$allowed_ids = get_terms( array(
		'taxonomy'   => 'product_tag',
		'hide_empty' => false,
		'slug'       => mnsk7_get_indexable_product_tag_slugs(),
		'fields'     => 'ids',
	) );
	$args['include'] = is_wp_error( $allowed_ids ) ? array( 0 ) : array_map( 'absint', $allowed_ids );
	return $args;
}, 20, 2 );

add_filter( 'wp_sitemaps_post_types', function ( $post_types ) {
	foreach ( array_keys( (array) $post_types ) as $post_type ) {
		if ( $post_type === 'shopengine-template' || strpos( (string) $post_type, 'shopengine' ) === 0 ) {
			unset( $post_types[ $post_type ] );
		}
	}
	return $post_types;
} );

/* Duplicate offer pages redirect only to an exact resolved or explicitly assigned target. */
add_action( 'template_redirect', function () {
	if ( ! is_singular( 'page' ) || mnsk7_get_offer_seo_state( get_queried_object_id() ) !== 'duplicate' ) {
		return;
	}
	$post       = get_post( get_queried_object_id() );
	$target_id  = mnsk7_get_offer_duplicate_page_id( $post );
	if ( $target_id < 1 ) {
		$target_id = absint( get_post_meta( get_queried_object_id(), '_mnsk7_offer_redirect_product_id', true ) );
	}
	$target_url = $target_id > 0 ? get_permalink( $target_id ) : '';
	if ( ! $target_url ) {
		$target_url = esc_url_raw( (string) get_post_meta( get_queried_object_id(), '_mnsk7_offer_redirect_url', true ) );
	}
	if ( $target_url ) {
		wp_safe_redirect( $target_url, 301, 'MNSK7 SEO' );
		exit;
	}
}, 1 );

/* Retire legacy SEO landings only when their exact live catalog equivalent resolves. */
add_action( 'template_redirect', function () {
	if ( ! is_singular( 'page' ) ) {
		return;
	}
	$url = mnsk7_get_legacy_seo_page_target_url( get_post( get_queried_object_id() ) );
	if ( $url !== '' ) {
		wp_safe_redirect( $url, 301, 'MNSK7 SEO' );
		exit;
	}
}, 2 );

add_filter( 'wpseo_opengraph_image', function ( $image ) {
	if ( ! mnsk7_is_guide_post() || ! function_exists( 'mnsk7_get_guide_primary_image_url' ) ) {
		return $image;
	}
	$guide_image = mnsk7_get_guide_primary_image_url( get_queried_object_id() );
	return $guide_image !== '' ? $guide_image : $image;
}, 30 );

add_filter( 'wpseo_schema_article', function ( $data ) {
	if ( ! mnsk7_is_guide_post() || ! function_exists( 'mnsk7_get_guide_primary_image_url' ) ) {
		return $data;
	}
	$image_url = mnsk7_get_guide_primary_image_url( get_queried_object_id() );
	if ( $image_url === '' ) {
		return $data;
	}
	$data['image'] = array(
		'@type'      => 'ImageObject',
		'@id'        => get_permalink( get_queried_object_id() ) . '#primaryimage',
		'url'        => $image_url,
		'contentUrl' => $image_url,
	);
	if ( get_post_field( 'post_name', get_queried_object_id() ) === 'frez-do-wyrownania-sleba-i-planowania-powierzchni' ) {
		$data['author'] = array(
			'@type' => 'Organization',
			'name'  => 'Zespół MNSK7 Tool',
			'url'   => home_url( '/' ),
		);
	}
	return $data;
}, 30 );

add_action( 'wp_head', function () {
	$guide_canonical = mnsk7_get_guide_archive_canonical_url();
	if ( $guide_canonical === '' ) {
		return;
	}
	echo '<link rel="canonical" href="' . esc_url( $guide_canonical ) . '">' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 2 );

add_action( 'wp_head', function () {
	if ( ! mnsk7_is_guide_archive() ) {
		return;
	}

	global $wp_query;
	$url   = mnsk7_get_guide_archive_canonical_url();
	$items = array();
	if ( $wp_query instanceof WP_Query ) {
		$position = 0;
		foreach ( $wp_query->posts as $post ) {
			if ( ! $post instanceof WP_Post ) {
				continue;
			}
			$position++;
			$items[] = array(
				'@type'    => 'ListItem',
				'position' => $position,
				'name'     => get_the_title( $post ),
				'url'      => get_permalink( $post ),
			);
		}
	}

	$schema = array(
		'@context'    => 'https://schema.org',
		'@type'       => 'CollectionPage',
		'@id'         => $url . '#collection',
		'url'         => $url,
		'name'        => __( 'Przewodnik CNC — frezy, dobór narzędzi i obróbka', 'mnsk7-tools' ),
		'description' => __( 'Praktyczne poradniki o frezach CNC, doborze narzędzi, obróbce drewna, aluminium, stali i materiałów płytowych.', 'mnsk7-tools' ),
		'isPartOf'    => array(
			'@type' => 'WebSite',
			'@id'   => home_url( '/#website' ),
		),
		'mainEntity'  => array(
			'@type'           => 'ItemList',
			'numberOfItems'   => count( $items ),
			'itemListElement' => $items,
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 6 );

add_action( 'wp_head', function () {
	if ( ! is_singular( 'post' ) ) {
		return;
	}
	// Yoast owns Article/Breadcrumb schema when active; the block below is a fallback.
	if ( defined( 'WPSEO_VERSION' ) || class_exists( 'WPSEO_Options' ) ) {
		return;
	}

	$post_id   = get_queried_object_id();
	$permalink = get_permalink( $post_id );
	if ( ! $post_id || ! $permalink ) {
		return;
	}

	$description = get_the_excerpt( $post_id );
	if ( $description === '' ) {
		$description = wp_trim_words( wp_strip_all_tags( get_post_field( 'post_content', $post_id ) ), 28, '' );
	}

	$image = get_the_post_thumbnail_url( $post_id, 'full' );
	if ( ! $image && function_exists( 'mnsk7_get_guide_primary_image_url' ) ) {
		$image = mnsk7_get_guide_primary_image_url( $post_id );
	}
	if ( ! $image ) {
		$image = get_site_icon_url( 512 ) ?: home_url( '/wp-content/themes/tech-storefront/assets/images/logo.png' );
	}

	$schema = array(
		array(
			'@context'         => 'https://schema.org',
			'@type'            => 'BlogPosting',
			'mainEntityOfPage' => array(
				'@type' => 'WebPage',
				'@id'   => $permalink,
			),
			'headline'         => get_the_title( $post_id ),
			'description'      => $description,
			'image'            => array( $image ),
			'datePublished'    => get_the_date( DATE_W3C, $post_id ),
			'dateModified'     => get_the_modified_date( DATE_W3C, $post_id ),
			'author'           => array(
				'@type' => 'Organization',
				'name'  => 'MNSK7 Tool',
				'url'   => home_url( '/' ),
			),
			'publisher'        => array(
				'@type' => 'Organization',
				'name'  => 'MNSK7 Tool',
				'url'   => home_url( '/' ),
				'logo'  => array(
					'@type' => 'ImageObject',
					'url'   => get_site_icon_url( 512 ) ?: $image,
				),
			),
		),
		array(
			'@context'        => 'https://schema.org',
			'@type'           => 'BreadcrumbList',
			'itemListElement' => array(
				array(
					'@type'    => 'ListItem',
					'position' => 1,
					'name'     => __( 'Strona główna', 'mnsk7-tools' ),
					'item'     => home_url( '/' ),
				),
				array(
					'@type'    => 'ListItem',
					'position' => 2,
					'name'     => apply_filters( 'mnsk7_przewodnik_menu_label', __( 'Przewodnik', 'mnsk7-tools' ) ),
					'item'     => home_url( '/przewodnik/' ),
				),
				array(
					'@type'    => 'ListItem',
					'position' => 3,
					'name'     => get_the_title( $post_id ),
					'item'     => $permalink,
				),
			),
		),
	);

	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 6 );

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
	$is_curated_term = in_array( $term->taxonomy, array( 'product_cat', 'product_tag' ), true )
		&& get_term_meta( $term->term_id, '_mnsk7_term_seo_version', true ) !== '';
	if ( $is_curated_term && ( max( 1, (int) get_query_var( 'paged' ) ) > 1 || mnsk7_is_catalog_filter_request() ) ) {
		return;
	}
	if ( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ) {
		$desc = mnsk7_strip_wpf_filters_from_text( (string) $desc );
	} else {
		$desc = preg_replace( '/\[wpf[-_]filters[^\]]*\]/i', '', (string) $desc );
		$desc = trim( $desc );
	}
	if ( empty( $img_id ) && empty( $desc ) ) {
		return;
	}
	$classes = $is_curated_term ? 'mnsk7-cat-header mnsk7-term-seo mnsk7-term-seo__intro' : 'mnsk7-cat-header';
	echo '<div class="' . esc_attr( $classes ) . '">';
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

if ( ! function_exists( 'mnsk7_render_term_seo_after_products' ) ) {
	/** Render long category copy and FAQ after the live WooCommerce product loop. */
	function mnsk7_render_term_seo_after_products( $term ) {
		if ( ! $term instanceof WP_Term || ! in_array( $term->taxonomy, array( 'product_cat', 'product_tag' ), true ) || max( 1, (int) get_query_var( 'paged' ) ) > 1 || mnsk7_is_catalog_filter_request() ) {
			return;
		}

		$after = trim( (string) get_term_meta( $term->term_id, '_mnsk7_term_seo_after', true ) );
		$faq   = json_decode( (string) get_term_meta( $term->term_id, '_mnsk7_term_seo_faq', true ), true );
		if ( $after === '' && empty( $faq ) ) {
			return;
		}

		echo '<section class="mnsk7-term-seo mnsk7-term-seo__after col-full" aria-label="' . esc_attr__( 'Poradnik wyboru', 'mnsk7-tools' ) . '">';
		if ( $after !== '' ) {
			echo '<div class="mnsk7-term-seo__copy">' . wp_kses_post( $after ) . '</div>';
		}

		$schema_items = array();
		if ( is_array( $faq ) && ! empty( $faq ) ) {
			echo '<div class="mnsk7-term-seo__faq">';
			echo '<h2>' . esc_html__( 'Najczęstsze pytania', 'mnsk7-tools' ) . '</h2>';
			foreach ( $faq as $item ) {
				$question = isset( $item['q'] ) ? sanitize_text_field( $item['q'] ) : '';
				$answer   = isset( $item['a'] ) ? wp_kses_post( $item['a'] ) : '';
				if ( $question === '' || trim( wp_strip_all_tags( $answer ) ) === '' ) {
					continue;
				}
				echo '<details class="mnsk7-term-seo__faq-item"><summary>' . esc_html( $question ) . '</summary><div>' . $answer . '</div></details>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				$schema_items[] = array(
					'@type'          => 'Question',
					'name'           => $question,
					'acceptedAnswer' => array( '@type' => 'Answer', 'text' => wp_strip_all_tags( $answer ) ),
				);
			}
			echo '</div>';
		}

		$links = array();
		if ( $term->taxonomy === 'product_cat' ) {
			$children = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => $term->term_id, 'hide_empty' => true, 'number' => 6 ) );
			if ( ! is_wp_error( $children ) ) {
				foreach ( $children as $child ) {
					$url = get_term_link( $child );
					if ( ! is_wp_error( $url ) ) {
						$links[] = array( 'url' => $url, 'label' => $child->name );
					}
				}
			}
		}
		$guide_slugs = array(
			'frezy-kompresyjne-updown-cut' => 'frezy-kompresyjne-do-czego-sluza',
			'frezy-diamentowe'              => 'frez-diamentowy-do-obrobki-3d-cnc',
			'pilniki-obrotowe'              => 'pilniki-obrotowe-jak-wybrac-typ',
			'frezy-do-planowania'           => 'frez-do-wyrownania-sleba-i-planowania-powierzchni',
		);
		if ( isset( $guide_slugs[ $term->slug ] ) ) {
			$guide = get_page_by_path( $guide_slugs[ $term->slug ], OBJECT, 'post' );
			if ( $guide instanceof WP_Post && $guide->post_status === 'publish' ) {
				$links[] = array( 'url' => get_permalink( $guide ), 'label' => get_the_title( $guide ) );
			}
		}
		if ( ! empty( $links ) ) {
			echo '<nav class="mnsk7-term-seo__links" aria-label="' . esc_attr__( 'Powiązane kategorie i poradniki', 'mnsk7-tools' ) . '"><h2>' . esc_html__( 'Zobacz także', 'mnsk7-tools' ) . '</h2><ul>';
			foreach ( $links as $link ) {
				echo '<li><a href="' . esc_url( $link['url'] ) . '">' . esc_html( $link['label'] ) . '</a></li>';
			}
			echo '</ul></nav>';
		}
		echo '</section>';

		/* FAQ schema is emitted only for the questions visibly rendered above. */
		if ( ! empty( $schema_items ) ) {
			$schema = array( '@context' => 'https://schema.org', '@type' => 'FAQPage', 'mainEntity' => $schema_items );
			echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		}
	}
}

add_filter( 'woocommerce_structured_data_product', function ( $markup, $product ) {
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return $markup;
	}

	/*
	 * Merchant listings: the storefront visibly offers a 30-day return period.
	 * Keep this on each Offer, where Google expects offer-level return data.
	 */
	$return_policy = array(
		'@type'                => 'MerchantReturnPolicy',
		'applicableCountry'    => 'PL',
		'returnPolicyCategory' => 'https://schema.org/MerchantReturnFiniteReturnWindow',
		'merchantReturnDays'   => 30,
		'merchantReturnLink'   => home_url( '/regulamin/#zwroty' ),
	);
	$last_changed = $product->get_date_modified();
	$valid_from   = $last_changed instanceof WC_DateTime ? $last_changed->date( DATE_W3C ) : '';
	if ( $valid_from === '' ) {
		$valid_from = get_post_modified_time( DATE_W3C, true, $product->get_id() );
	}
	$apply_offer_metadata = static function ( &$offer ) use ( $return_policy, $valid_from ) {
		$offer['hasMerchantReturnPolicy'] = $return_policy;
		if ( $valid_from === '' ) {
			return;
		}
		$offer['validFrom'] = $valid_from;
		if ( isset( $offer['priceSpecification']['@type'] ) ) {
			$offer['priceSpecification']['validFrom'] = $valid_from;
		} elseif ( isset( $offer['priceSpecification'] ) && is_array( $offer['priceSpecification'] ) ) {
			foreach ( $offer['priceSpecification'] as $spec_key => $specification ) {
				if ( is_array( $specification ) && isset( $specification['@type'] ) ) {
					$offer['priceSpecification'][ $spec_key ]['validFrom'] = $valid_from;
				}
			}
		}
	};
	if ( isset( $markup['offers']['@type'] ) ) {
		$apply_offer_metadata( $markup['offers'] );
	} elseif ( isset( $markup['offers'] ) && is_array( $markup['offers'] ) ) {
		foreach ( $markup['offers'] as $offer_key => $offer ) {
			if ( is_array( $offer ) && isset( $offer['@type'] ) ) {
				$apply_offer_metadata( $markup['offers'][ $offer_key ] );
			}
		}
	}

	$rating_count = (int) $product->get_rating_count();
	$review_count = (int) $product->get_review_count();
	$average      = (float) $product->get_average_rating();

	if ( $rating_count <= 0 || $average <= 0 ) {
		return $markup;
	}

	$markup['aggregateRating'] = array(
		'@type'       => 'AggregateRating',
		'ratingValue' => number_format( $average, 2, '.', '' ),
		'ratingCount' => $rating_count,
		'reviewCount' => max( $review_count, $rating_count ),
		'bestRating'  => '5',
		'worstRating' => '1',
	);

	$reviews = get_comments(
		array(
			'post_id' => $product->get_id(),
			'status'  => 'approve',
			'type'    => 'review',
			'number'  => 5,
			'orderby' => 'comment_date_gmt',
			'order'   => 'DESC',
		)
	);

	if ( ! empty( $reviews ) ) {
		$markup['review'] = array();
		foreach ( $reviews as $review ) {
			$rating = (int) get_comment_meta( $review->comment_ID, 'rating', true );
			$item   = array(
				'@type'         => 'Review',
				'author'        => array(
					'@type' => 'Person',
					'name'  => $review->comment_author ?: __( 'Klient MNSK7 Tool', 'mnsk7-tools' ),
				),
				'datePublished' => mysql2date( DATE_W3C, $review->comment_date_gmt, false ),
				'reviewBody'    => wp_trim_words( wp_strip_all_tags( $review->comment_content ), 60, '' ),
			);
			if ( $rating > 0 ) {
				$item['reviewRating'] = array(
					'@type'       => 'Rating',
					'ratingValue' => (string) $rating,
					'bestRating'  => '5',
					'worstRating' => '1',
				);
			}
			$markup['review'][] = $item;
		}
	}

	return $markup;
}, 999, 2 );
