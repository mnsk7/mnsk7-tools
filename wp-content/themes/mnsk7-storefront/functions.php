<?php
/**
 * MNSK7 Storefront child theme functions.
 * Parent: Storefront (official WooCommerce theme).
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether parent theme Storefront is present (not removed/overwritten by WP or host).
 * When false, child uses its own header fallback and does not enqueue parent styles.
 */
function mnsk7_parent_storefront_available() {
	if ( get_template() !== 'storefront' ) {
		return false;
	}
	$parent_style = get_template_directory() . '/style.css';
	return is_readable( $parent_style );
}

/**
 * FB-01: limit primary menu items so header is not flooded with 20+ categories.
 * Max 7 top-level items; in WP Admin keep menu short (Sklep, Dostawa, Kontakt, etc.).
 */
add_filter( 'wp_nav_menu_objects', function ( $items, $args ) {
	if ( empty( $items ) || ! is_array( $items ) ) {
		return $items;
	}
	$loc = isset( $args->theme_location ) ? $args->theme_location : '';
	if ( $loc !== 'primary' ) {
		return $items;
	}
	$top_level = array();
	foreach ( $items as $item ) {
		if ( empty( $item->menu_item_parent ) || (int) $item->menu_item_parent === 0 ) {
			$top_level[] = $item;
		}
	}
	if ( count( $top_level ) <= 7 ) {
		return $items;
	}
	$keep_ids = array_slice( array_map( function ( $i ) { return $i->ID; }, $top_level ), 0, 7 );
	$keep_ids = array_flip( $keep_ids );
	$filtered = array();
	foreach ( $items as $item ) {
		$id = (int) $item->ID;
		$parent = (int) $item->menu_item_parent;
		if ( isset( $keep_ids[ $id ] ) || ( $parent > 0 && isset( $keep_ids[ $parent ] ) ) ) {
			$filtered[] = $item;
		}
	}
	return $filtered;
}, 20, 2 );

/** Ładne okruszki: separator › + wrapper */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['delimiter']   = ' <span class="separator" aria-hidden="true">›</span> ';
	$args['wrap_before'] = '<div class="mnsk7-breadcrumb-wrap"><nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Nawigacja okruszków', 'mnsk7-storefront' ) . '">';
	$args['wrap_after']  = '</nav></div>';
	return $args;
} );

/** PDP: okruszki przy tytule produktu, nie pod headerem */
add_action( 'wp', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_breadcrumb', 5 );
} );

/** Fallback menu for header when no primary menu set (callable by name for cache-safe wp_nav_menu). */
function mnsk7_header_fallback_menu() {
	echo '<ul id="mnsk7-primary-menu" class="mnsk7-header__menu">';
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Sklep', 'mnsk7-storefront' ) . '</a></li>';
	}
	echo '<li><a href="' . esc_url( home_url( '/kontakt/' ) ) . '">' . esc_html__( 'Kontakt', 'mnsk7-storefront' ) . '</a></li>';
	echo '</ul>';
}

/* 1. Enqueue styles — many small CSS parts (easier to maintain than one 2000+ line file) */
add_action( 'wp_enqueue_scripts', function () {
	$v = '3.0.7';
	$base = get_stylesheet_directory_uri() . '/assets/css/parts/';
	$dir = get_stylesheet_directory() . '/assets/css/parts/';
	if ( mnsk7_parent_storefront_available() ) {
		wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array( 'storefront-style' ), $v );
	} else {
		wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array(), $v );
	}
	$prev = 'mnsk7-storefront-style';
	$parts = array( '01-tokens', '02-reset-typography', '03-storefront-overrides', '04-header', '05-plp-cards', '06-single-product', '07-mnsk7-blocks', '08-home-sections', '09-footer', '10-cookie-bar', '11-hidden', '12-related-products', '13-seo-landing', '14-faq', '15-delivery-contact', '16-woo-notices', '17-buttons', '18-cart-checkout', '19-breadcrumbs', '20-responsive-tablet', '21-responsive-mobile', '22-touch-targets', '23-print', '24-plp-table' );
	$parts_loaded = false;
	foreach ( $parts as $part ) {
		$path = $dir . $part . '.css';
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$handle = 'mnsk7-parts-' . $part;
		wp_enqueue_style( $handle, $base . $part . '.css', array( $prev ), $v );
		$prev = $handle;
		$parts_loaded = true;
	}
	if ( ! $parts_loaded ) {
		wp_enqueue_style( 'mnsk7-main', get_stylesheet_directory_uri() . '/assets/css/main.css', array( $prev ), $v );
	}
}, 10 );

/* 1b. Mobile menu toggle (mnsk7-header) */
add_action( 'wp_footer', function () {
	?>
	<script>
	(function() {
		var t = document.querySelector('.mnsk7-header__menu-toggle');
		var n = document.querySelector('.mnsk7-header__nav');
		if (t && n) {
			t.addEventListener('click', function() {
				n.classList.toggle('is-open');
				t.setAttribute('aria-expanded', n.classList.contains('is-open'));
			});
		}
	})();
	</script>
	<?php
}, 20 );

/* 2. Google Fonts: Inter (replace Storefront default) */
add_action( 'wp_enqueue_scripts', function () {
	if ( mnsk7_parent_storefront_available() ) {
		wp_dequeue_style( 'storefront-fonts' );
	}
	wp_enqueue_style( 'mnsk7-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', array(), null );
}, 20 );

/* 3. Theme support */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

/* 4. Storefront header customization */
add_filter( 'storefront_custom_header_args', function ( $args ) {
	$args['default-text-color'] = '0f172a';
	return $args;
} );

add_action( 'init', function () {
	if ( ! mnsk7_parent_storefront_available() ) {
		return;
	}
	// Child ma własny header.php — wyłączamy cały output Storefront w headerze, żeby nie było podwójnego.
	remove_action( 'storefront_header', 'storefront_skip_links', 0 );
	remove_action( 'storefront_header', 'storefront_site_branding', 20 );
	remove_action( 'storefront_header', 'storefront_secondary_navigation', 30 );
	remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper', 42 );
	remove_action( 'storefront_header', 'storefront_primary_navigation', 50 );
	remove_action( 'storefront_header', 'storefront_header_cart', 60 );
	remove_action( 'storefront_header', 'storefront_primary_navigation_wrapper_close', 68 );
	remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
} );

/* Header: jawnie widoczne linki Koszyk + Moje konto (gdy parent Storefront) */
add_action( 'storefront_header', function () {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	echo '<div class="mnsk7-header-actions">';
	echo '<a href="' . esc_url( wc_get_page_permalink( 'myaccount' ) ) . '" class="mnsk7-header-link mnsk7-header-link--account">' . esc_html__( 'Moje konto', 'mnsk7-storefront' ) . '</a>';
	echo '</div>';
}, 49 );

/* 5. Admin notice when parent Storefront is missing (e.g. overwritten by WP/host) */
add_action( 'admin_notices', function () {
	if ( mnsk7_parent_storefront_available() || get_stylesheet() !== 'mnsk7-storefront' ) {
		return;
	}
	$msg = __( 'Rodzic motywu Storefront nie jest zainstalowany lub został nadpisany. Strona używa zapasowego nagłówka. Zainstaluj motyw Storefront (WooCommerce) lub wdróż go z repozytorium.', 'mnsk7-storefront' );
	echo '<div class="notice notice-warning is-dismissible"><p><strong>MNK7 Storefront:</strong> ' . esc_html( $msg ) . '</p></div>';
} );

/* 6. Prevent page listing fallback in menu */
add_filter( 'wp_page_menu_args', function ( $args ) {
	$args['include'] = '0';
	return $args;
} );

/* 7. Override Storefront typography */
add_filter( 'storefront_google_font_families', '__return_empty_array' );

/* 7b. PLP: nie pokazuj shortcodów ani artefaktów filtrów ([wpf-filters id=7] + blok „Filtruj: Średnica: …”) */
function mnsk7_strip_wpf_filters_from_text( $text ) {
	if ( ! is_string( $text ) || $text === '' ) {
		return $text;
	}
	$text = preg_replace( '/\[wpf-filters[^\]]*\]/i', '', $text );
	$text = preg_replace( '/\[wpf_filters[^\]]*\]/i', '', $text );
	$text = preg_replace( '/\s*Filtruj:\s*[^<]*?(?=\n\s*\n|\z)/s', '', $text );
	return trim( preg_replace( '/\n\s*\n\s*\n/', "\n\n", $text ) );
}
add_filter( 'term_description', function ( $desc ) {
	if ( ! function_exists( 'is_product_taxonomy' ) || ! is_product_taxonomy() ) {
		return $desc;
	}
	return mnsk7_strip_wpf_filters_from_text( $desc );
}, 5 );
add_filter( 'get_the_archive_description', 'mnsk7_strip_wpf_filters_from_text', 5 );
add_filter( 'get_the_archive_title', function ( $title ) {
	if ( is_product_taxonomy() && is_string( $title ) ) {
		$title = mnsk7_strip_wpf_filters_from_text( $title );
	}
	return $title;
}, 5 );
add_filter( 'woocommerce_page_title', 'mnsk7_strip_wpf_filters_from_text', 5 );
add_filter( 'woocommerce_taxonomy_archive_description_raw', 'mnsk7_strip_wpf_filters_from_text', 5 );
add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
	if ( ! is_array( $crumbs ) ) {
		return $crumbs;
	}
	foreach ( $crumbs as $i => $crumb ) {
		if ( isset( $crumb[1] ) && is_string( $crumb[1] ) ) {
			$crumbs[ $i ][1] = mnsk7_strip_wpf_filters_from_text( $crumb[1] );
		}
	}
	return $crumbs;
}, 5 );
add_filter( 'the_content', 'mnsk7_strip_wpf_filters_from_text', 1 );
add_filter( 'document_title_parts', function ( $parts ) {
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() && ! empty( $parts['title'] ) && is_string( $parts['title'] ) ) {
		$parts['title'] = mnsk7_strip_wpf_filters_from_text( $parts['title'] );
	}
	return $parts;
}, 5 );

/* 8. Front page document title (SEO + zakładka) — fallback gdy brak ustawionej strony głównej */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( ! is_front_page() ) {
		return $parts;
	}
	if ( empty( $parts['title'] ) || trim( (string) $parts['title'] ) === '' ) {
		$parts['title'] = __( 'Frezy CNC i narzędzia skrawające', 'mnsk7-storefront' );
	}
	return $parts;
}, 15 );

/* 9. PDP — blok kluczowych parametrów (fallback gdy brak mu-plugina) */
add_action( 'woocommerce_single_product_summary', function () {
	if ( function_exists( 'mnsk7_single_product_key_params' ) ) {
		return;
	}
	global $product;
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$attrs = $product->get_attributes();
	if ( empty( $attrs ) ) {
		return;
	}
	echo '<div class="mnsk7-pdp-key-params">';
	echo '<h3 class="mnsk7-pdp-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-storefront' ) . '</h3>';
	echo '<table><tbody>';
	foreach ( $attrs as $attr ) {
		if ( ! $attr->get_visible() ) {
			continue;
		}
		$label = wc_attribute_label( $attr->get_name() );
		$value = $product->get_attribute( $attr->get_name() );
		if ( $value === '' ) {
			continue;
		}
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . wp_kses_post( $value ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}, 21 );

/* 10. PDP — trust strip pod CTA (fallback gdy brak mu-plugina) */
add_action( 'woocommerce_single_product_summary', function () {
	if ( function_exists( 'mnsk7_single_product_trust_badges' ) ) {
		return;
	}
	$eta_label = function_exists( 'mnsk7_delivery_eta_badge_label' ) ? mnsk7_delivery_eta_badge_label() : __( 'Dostawa jutro', 'mnsk7-storefront' );
	echo '<div class="mnsk7-pdp-trust">';
	echo '<span class="mnsk7-pdp-trust__item">' . esc_html( $eta_label ) . '</span>';
	echo '<span class="mnsk7-pdp-trust__item">' . esc_html__( 'Faktura VAT', 'mnsk7-storefront' ) . '</span>';
	echo '<span class="mnsk7-pdp-trust__item">' . esc_html__( 'Darmowa dostawa od 300 zł', 'mnsk7-storefront' ) . '</span>';
	echo '<span class="mnsk7-pdp-trust__item">' . esc_html__( 'Zwroty 30 dni', 'mnsk7-storefront' ) . '</span>';
	echo '</div>';
}, 35 );

/* 11. Instagram shortcode + domyślne linki do postów (gdy brak mu-plugina) */
add_action( 'init', function () {
	if ( shortcode_exists( 'mnsk7_instagram_feed' ) ) {
		return;
	}
	add_shortcode( 'mnsk7_instagram_feed', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit' => 6,
			'title' => 'Instagram @mnsk7tools',
		), $atts, 'mnsk7_instagram_feed' );
		$limit = max( 1, min( 12, (int) $atts['limit'] ) );
		$profile = defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/';
		$urls = get_option( 'mnsk7_instagram_post_urls', array() );
		if ( ! is_array( $urls ) || empty( $urls ) ) {
			$urls = array(
				'https://www.instagram.com/mnsk7tools/p/DC4agmPtKoy/',
				'https://www.instagram.com/mnsk7tools/p/DC9J3JjNobj/',
				'https://www.instagram.com/mnsk7tools/p/DCTybzqtxEi/',
			);
		}
		$urls = array_slice( array_filter( array_map( 'esc_url_raw', $urls ) ), 0, $limit );
		$out = '<div class="mnsk7-instagram-feed">';
		if ( ! empty( $urls ) ) {
			$out .= '<div class="mnsk7-instagram-feed__grid">';
			foreach ( $urls as $i => $url ) {
				$out .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__item" aria-label="' . esc_attr( sprintf( __( 'Post %d na Instagramie', 'mnsk7-storefront' ), $i + 1 ) ) . '">';
				$out .= '<span class="mnsk7-instagram-feed__icon" aria-hidden="true"></span>';
				$out .= '</a>';
			}
			$out .= '</div>';
		}
		$out .= '<p class="mnsk7-instagram-feed__cta"><a href="' . esc_url( $profile ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-insta-cta__link">' . esc_html( $atts['title'] ) . ' →</a></p>';
		$out .= '</div>';
		return $out;
	} );
}, 5 );

/* 12. Menu główne — uzupełnienie linkami (Sklep, Kontakt, Dostawa, Przewodnik) przy pierwszym ładowaniu */
add_action( 'after_setup_theme', function () {
	$done = get_option( 'mnsk7_primary_menu_seeded', 0 );
	if ( $done ) {
		return;
	}
	$locations = get_nav_menu_locations();
	$menu_id = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( ! $menu_id ) {
		$menus = wp_get_nav_menus();
		foreach ( $menus as $menu ) {
			if ( $menu->slug === 'primary' || stripos( $menu->name, 'primary' ) !== false || stripos( $menu->name, 'główne' ) !== false ) {
				$menu_id = (int) $menu->term_id;
				break;
			}
		}
		if ( ! $menu_id && ! empty( $menus ) ) {
			$menu_id = (int) $menus[0]->term_id;
		}
	}
	if ( ! $menu_id ) {
		return;
	}
	$pages = array(
		'sklep'             => __( 'Sklep', 'mnsk7-storefront' ),
		'dostawa-i-platnosci' => __( 'Dostawa i płatności', 'mnsk7-storefront' ),
		'kontakt'           => __( 'Kontakt', 'mnsk7-storefront' ),
		'przewodnik'        => __( 'Baza wiedzy', 'mnsk7-storefront' ),
	);
	$added = 0;
	foreach ( $pages as $slug => $title ) {
		$page = get_page_by_path( $slug );
		if ( ! $page || $page->post_status !== 'publish' ) {
			continue;
		}
		$item = wp_get_nav_menu_items( $menu_id );
		$exists = false;
		if ( is_array( $item ) ) {
			foreach ( $item as $i ) {
				if ( (int) $i->object_id === (int) $page->ID && $i->object === 'page' ) {
					$exists = true;
					break;
				}
			}
		}
		if ( ! $exists ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'     => $title,
				'menu-item-url'       => get_permalink( $page ),
				'menu-item-status'    => 'publish',
				'menu-item-type'      => 'post_type',
				'menu-item-object'    => 'page',
				'menu-item-object-id' => $page->ID,
			) );
			$added++;
		}
	}
	if ( $added > 0 ) {
		update_option( 'mnsk7_primary_menu_seeded', 1 );
	}
}, 20 );
add_action( 'woocommerce_product_query', function ( $q ) {
	if ( is_admin() || ! is_object( $q ) || ! method_exists( $q, 'set' ) ) {
		return;
	}
	$attr_taxonomies = array( 'pa_srednica', 'pa_srednica-trzpienia', 'pa_dlugosc-calkowita-l', 'pa_dlugosc-robocza-h' );
	foreach ( $attr_taxonomies as $attr ) {
		$param = 'filter_' . str_replace( 'pa_', '', $attr );
		if ( empty( $_GET[ $param ] ) ) {
			continue;
		}
		$slug = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
		if ( $slug === '' ) {
			continue;
		}
		$tax = $q->get( 'tax_query' );
		if ( ! is_array( $tax ) ) {
			$tax = array();
		}
		$tax[] = array(
			'taxonomy' => $attr,
			'field'    => 'slug',
			'terms'    => $slug,
		);
		$q->set( 'tax_query', $tax );
		break;
	}
}, 20 );

/**
 * Get product IDs in current archive term (category/tag), in stock only, respecting current attribute filters.
 * Used by FB-03 to show only attribute terms that have products in the current category.
 *
 * @param array $attrs_to_try Map of taxonomy => label (to build tax_query from filter_* params).
 * @return int[] Product IDs, or empty array.
 */
function mnsk7_get_archive_product_ids_for_chips( $attrs_to_try ) {
	$term = get_queried_object();
	if ( ! $term || ! isset( $term->term_id ) ) {
		return array();
	}
	$tax_query = array(
		array(
			'taxonomy' => $term->taxonomy,
			'field'    => 'term_id',
			'terms'    => $term->term_id,
		),
	);
	// Add current attribute filters from URL so chips reflect only terms that exist for filtered set.
	foreach ( $attrs_to_try as $tax => $label ) {
		$param = 'filter_' . str_replace( 'pa_', '', $tax );
		if ( empty( $_GET[ $param ] ) || ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$slug = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
		if ( $slug === '' ) {
			continue;
		}
		$tax_query[] = array(
			'taxonomy' => $tax,
			'field'    => 'slug',
			'terms'    => $slug,
		);
	}
	$query_args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'fields'         => 'ids',
		'posts_per_page' => 800,
		'no_found_rows'  => true,
		'tax_query'      => array_merge( array( 'relation' => 'AND' ), $tax_query ),
		'meta_query'     => array(
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		),
	);
	$q = new WP_Query( $query_args );
	return $q->posts ? array_map( 'intval', $q->posts ) : array();
}

/**
 * Attribute filter chips for PLP. Skips Średnica for category "Zestawy" (Sets).
 * FB-02: when category is Zestawy, diameter filter row is hidden.
 * FB-03: only terms that have in-stock products in the current category are shown.
 *
 * @return array{filters: array<array{label: string, param: string, chips: array}>} Wszystkie atrybuty z termami (Średnica, Trzpień, Długość L/H).
 */
function mnsk7_get_archive_attribute_filter_chips() {
	$empty = array( 'filters' => array() );
	if ( ! is_product_taxonomy() ) {
		return $empty;
	}
	$attrs_to_try = array(
		'pa_srednica'             => __( 'Średnica', 'mnsk7-storefront' ),
		'pa_srednica-trzpienia'   => __( 'Trzpień', 'mnsk7-storefront' ),
		'pa_dlugosc-calkowita-l'  => __( 'Długość L', 'mnsk7-storefront' ),
		'pa_dlugosc-robocza-h'    => __( 'Długość H', 'mnsk7-storefront' ),
	);
	$term = get_queried_object();
	if ( ! $term || ! isset( $term->term_id ) ) {
		return $empty;
	}
	$term_slug = isset( $term->slug ) ? strtolower( (string) $term->slug ) : '';
	$term_name = isset( $term->name ) ? strtolower( (string) $term->name ) : '';
	$is_zestawy = ( strpos( $term_slug, 'zestaw' ) !== false || strpos( $term_name, 'zestaw' ) !== false );

	$product_ids = mnsk7_get_archive_product_ids_for_chips( $attrs_to_try );
	$filters = array();

	foreach ( $attrs_to_try as $tax => $label ) {
		if ( $is_zestawy && $tax === 'pa_srednica' ) {
			continue;
		}
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$get_terms_args = array(
			'taxonomy'   => $tax,
			'hide_empty' => true,
			'number'     => 24,
			'orderby'    => 'name',
		);
		if ( ! empty( $product_ids ) ) {
			$get_terms_args['object_ids'] = $product_ids;
		}
		$terms = get_terms( $get_terms_args );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}
		$chips = array();
		foreach ( $terms as $t ) {
			$chips[ $t->slug ] = $t->name;
		}
		$param = 'filter_' . str_replace( 'pa_', '', $tax );
		$filters[] = array(
			'label' => $label . ': ',
			'param' => $param,
			'chips' => $chips,
		);
	}
	return array( 'filters' => $filters );
}
