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
	return get_template() === 'storefront' && file_exists( get_template_directory() . '/style.css' );
}

/* 1. Enqueue styles — many small CSS parts (easier to maintain than one 2000+ line file) */
add_action( 'wp_enqueue_scripts', function () {
	$v = '3.0.0';
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
	remove_action( 'storefront_header', 'storefront_secondary_navigation', 30 );
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
	echo '<div class="mnsk7-pdp-trust">';
	echo '<span class="mnsk7-pdp-trust__item">' . esc_html__( 'Dostawa jutro', 'mnsk7-storefront' ) . '</span>';
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
	if ( is_admin() || ! $q instanceof WP_Query ) {
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

function mnsk7_get_archive_attribute_filter_chips() {
	if ( ! is_product_taxonomy() ) {
		return array( 'label' => '', 'param' => '', 'chips' => array() );
	}
	$attrs_to_try = array(
		'pa_srednica'             => __( 'Średnica', 'mnsk7-storefront' ),
		'pa_srednica-trzpienia'   => __( 'Trzpień', 'mnsk7-storefront' ),
		'pa_dlugosc-calkowita-l' => __( 'Długość L', 'mnsk7-storefront' ),
		'pa_dlugosc-robocza-h'   => __( 'Długość H', 'mnsk7-storefront' ),
	);
	$term = get_queried_object();
	if ( ! $term || ! isset( $term->term_id ) ) {
		return array( 'label' => '', 'param' => '', 'chips' => array() );
	}
	foreach ( $attrs_to_try as $tax => $label ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$terms = get_terms( array(
			'taxonomy'   => $tax,
			'hide_empty' => true,
			'number'     => 20,
			'orderby'    => 'name',
		) );
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}
		$chips = array();
		foreach ( $terms as $t ) {
			$chips[ $t->slug ] = $t->name;
		}
		$param = 'filter_' . str_replace( 'pa_', '', $tax );
		return array(
			'label' => $label . ': ',
			'param' => $param,
			'chips' => $chips,
		);
	}
	return array( 'label' => '', 'param' => '', 'chips' => array() );
}
