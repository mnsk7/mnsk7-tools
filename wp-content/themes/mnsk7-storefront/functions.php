<?php
/**
 * MNSK7 Storefront child theme functions.
 * Parent: Storefront (official WooCommerce theme).
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

/**
 * Breakpoint header "mobile" (px) — od tego width w dół: burger menu, ikony zamiast pełnego menu.
 * 1024px = przejście w burger wcześniej, bez przepełnienia/nałożenia elementów.
 */
if ( ! defined( 'MNSK7_BREAKPOINT_MOBILE' ) ) {
	define( 'MNSK7_BREAKPOINT_MOBILE', 1024 );
}

/** Wersja motywu (komentarz w header.php — weryfikacja deploy / cache). */
if ( ! defined( 'MNSK7_THEME_VERSION' ) ) {
	define( 'MNSK7_THEME_VERSION', '1.0.17' );
}

/**
 * Czy request jest z urządzenia mobilnego (user-agent). Używane do renderowania jednego layoutu PLP.
 *
 * @return bool
 */
function mnsk7_is_mobile_request() {
	if ( isset( $_GET['mnsk7_mobile'] ) ) {
		$forced = strtolower( sanitize_text_field( wp_unslash( $_GET['mnsk7_mobile'] ) ) );
		if ( in_array( $forced, array( '1', 'true', 'yes', 'on' ), true ) ) {
			return true;
		}
		if ( in_array( $forced, array( '0', 'false', 'no', 'off' ), true ) ) {
			return false;
		}
	}
	return function_exists( 'wp_is_mobile' ) && wp_is_mobile();
}

/**
 * Niezawodne wykrywanie strony archiwum produktów (sklep / kategoria / tag).
 * Główna logika: is_shop(), is_product_category(), is_product_tag(), get_queried_object().
 * Fallback REQUEST_URI (mnsk7_is_plp_url_path) tylko gdy plugin zmienił main query (np. ?filter_*).
 * HANDOFF: normalny conditional first, path fallback — jeden source of truth via mnsk7_is_plp().
 *
 * @return bool True gdy jesteśmy na archiwum sklepu, kategorii lub tagu produktu.
 */
function mnsk7_is_plp_archive() {
	if ( ! function_exists( 'is_shop' ) ) {
		return false;
	}
	// Поиск (в т.ч. ?s=...&post_type=product) — не PLP; nie podsuwamy archive-product.php.
	if ( function_exists( 'is_search' ) && is_search() ) {
		return false;
	}
	if ( is_shop() || ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() ) ) {
		return true;
	}
	$obj = get_queried_object();
	if ( $obj instanceof WP_Term && isset( $obj->taxonomy ) && in_array( $obj->taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
		return true;
	}
	// Fallback: REQUEST_URI tylko gdy main query zmieniony (filter_*).
	// Żeby header i body_class były takie same — traktuj request jako PLP, gdy ścieżka to sklep lub taksonomia.
	return mnsk7_is_plp_url_path();
}

/**
 * Fallback: czy ścieżka URL to sklep/kategoria/tag (gdy main query już zmieniony, np. ?filter_*).
 * Używane tylko wewnątrz mnsk7_is_plp_archive(). Nie wywoływać bezpośrednio dla layoutu — użyć mnsk7_is_plp().
 *
 * @return bool
 */
function mnsk7_is_plp_url_path() {
	$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( $req_uri === '' ) {
		return false;
	}
	$path = trim( (string) wp_parse_url( 'http://dummy' . $req_uri, PHP_URL_PATH ), '/' );
	if ( $path === '' ) {
		// Strona główna — może być ustawiona na sklep
		if ( get_option( 'show_on_front' ) === 'page' && (int) get_option( 'page_on_front' ) === (int) ( function_exists( 'wc_get_page_id' ) ? wc_get_page_id( 'shop' ) : 0 ) ) {
			return true;
		}
		return false;
	}
	$path_segments = array_filter( explode( '/', $path ), 'strlen' );
	$first = isset( $path_segments[0] ) ? $path_segments[0] : '';

	// Ścieżka strony sklepu (np. sklep lub sklep/kategoria)
	if ( function_exists( 'wc_get_page_id' ) ) {
		$shop_id = wc_get_page_id( 'shop' );
		if ( $shop_id > 0 ) {
			$shop_page = get_post( $shop_id );
			if ( $shop_page && ! empty( $shop_page->post_name ) ) {
				$shop_slug = $shop_page->post_name;
				if ( $first === $shop_slug ) {
					return true;
				}
			}
		}
	}

	// Ścieżka archiwum kategorii/tagu (np. kategoria-produktu/frezy lub tag-produktu/xxx)
	if ( taxonomy_exists( 'product_cat' ) ) {
		$tax = get_taxonomy( 'product_cat' );
		$cat_base = ( $tax && ! empty( $tax->rewrite['slug'] ) ) ? $tax->rewrite['slug'] : 'product-category';
		if ( $first === $cat_base ) {
			return true;
		}
	}
	if ( taxonomy_exists( 'product_tag' ) ) {
		$tax = get_taxonomy( 'product_tag' );
		$tag_base = ( $tax && ! empty( $tax->rewrite['slug'] ) ) ? $tax->rewrite['slug'] : 'product-tag';
		if ( $first === $tag_base ) {
			return true;
		}
	}

	return false;
}

/**
 * Jedno miejsce określania "to jest PLP" (sklep / kategoria / tag). Lazy-eval przy pierwszym wywołaniu — cache w $GLOBALS.
 * Nie ustawiamy w wp (priority 1), żeby pluginy zmieniające query (filter_*, koszyk, taxonomy) nie były wyprzedzane.
 * HANDOFF: body_class, template_include, breadcrumbs, header używają tylko tego.
 *
 * @return bool
 */
function mnsk7_is_plp() {
	if ( isset( $GLOBALS['mnsk7_is_plp'] ) ) {
		return (bool) $GLOBALS['mnsk7_is_plp'];
	}
	$GLOBALS['mnsk7_is_plp'] = mnsk7_is_plp_archive();
	return (bool) $GLOBALS['mnsk7_is_plp'];
}

/**
 * Ustawienie globalnego stanu "to jest PLP" na początku requestu (jeden raz).
 */
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

/** PLP audit task 10: jeden selekt sortowania, jedna paginacja — bez duplikatów.
 * Storefront dodaje sortowanie/result_count i przed, i po pętli. Usuwamy z before_shop_loop,
 * zostawiamy tylko w after_shop_loop (toolbar na dole). */
add_action( 'wp', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
		return;
	}
	/* Z góry (before_shop_loop) — usuń, żeby nie było drugiego toolbara */
	remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper', 9 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_catalog_ordering', 10 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_pagination', 30 );
	remove_action( 'woocommerce_before_shop_loop', 'storefront_sorting_wrapper_close', 31 );
	/* Na dół (after_shop_loop) — jeden result_count (5), jeden ordering, jedna paginacja; bez wrappera Storefront */
	add_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 5 );
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 20 );
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper_close', 31 );
}, 25 );

/* PLP-10: przy tabeli (shop/category/tag) nie pokazuj numerów paginacji — tylko przycisk "Pokaż więcej" */
add_action( 'woocommerce_after_shop_loop', function () {
	if ( ! empty( $GLOBALS['mnsk7_plp_use_table'] ) ) {
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
	}
}, 1 );

/**
 * PLP "Pokaż więcej": AJAX — zwraca HTML wierszy tabeli dla następnej strony (bez przejścia na page/2).
 */
function mnsk7_plp_load_more_handler() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce not active' ) );
	}
	check_ajax_referer( 'mnsk7_plp_load_more', 'nonce' );
	$page   = max( 1, isset( $_POST['page'] ) ? (int) $_POST['page'] : 0 );
	$tax    = isset( $_POST['taxonomy'] ) ? sanitize_text_field( wp_unslash( $_POST['taxonomy'] ) ) : '';
	$term   = isset( $_POST['term'] ) ? sanitize_text_field( wp_unslash( $_POST['term'] ) ) : '';
	$orderby = isset( $_POST['orderby'] ) ? sanitize_text_field( wp_unslash( $_POST['orderby'] ) ) : 'menu_order';
	$order   = isset( $_POST['order'] ) ? sanitize_text_field( wp_unslash( $_POST['order'] ) ) : 'asc';

	$per_page = absint( apply_filters( 'loop_shop_per_page', wc_get_default_products_per_row() * wc_get_default_product_rows_per_page() ) );
	if ( $per_page < 1 ) {
		$per_page = 12;
	}

	$args = array(
		'post_type'      => 'product',
		'post_status'    => 'publish',
		'posts_per_page' => $per_page,
		'paged'          => $page,
		'fields'         => 'ids',
	);

	if ( in_array( $tax, array( 'product_cat', 'product_tag' ), true ) && $term !== '' ) {
		$args['tax_query'] = array(
			array(
				'taxonomy' => $tax,
				'field'    => 'slug',
				'terms'    => $term,
			),
		);
	}

	if ( function_exists( 'wc_get_query' ) ) {
		$ordering = WC()->query->get_catalog_ordering_args( $orderby, $order );
		if ( ! empty( $ordering['orderby'] ) ) {
			$args['orderby'] = $ordering['orderby'];
			$args['order']   = isset( $ordering['order'] ) ? $ordering['order'] : 'ASC';
		}
		if ( ! empty( $ordering['meta_key'] ) ) {
			$args['meta_key'] = $ordering['meta_key'];
		}
	}

	$query = new WP_Query( $args );
	$ids   = $query->posts;
	$total = (int) $query->found_posts;
	$max_pages = (int) $query->max_num_pages;

	$rows_html = '';
	foreach ( $ids as $id ) {
		$product = wc_get_product( $id );
		if ( ! $product || ! $product->is_visible() ) {
			continue;
		}
		global $post;
		$post = get_post( $id );
		setup_postdata( $post );
		ob_start();
		wc_get_template_part( 'content', 'product-table-row' );
		$rows_html .= ob_get_clean();
	}
	wp_reset_postdata();

	// Po załadowaniu kolejnej strony: zakres 1–N z total (np. "Wyświetlanie 1–24 z 54 wyników").
	$first = 1;
	$last  = min( $total, $page * $per_page );
	$result_count = sprintf(
		/* translators: 1: first result, 2: last result, 3: total results */
		_n( 'Wyświetlanie %1$d–%2$d z %3$d wyniku', 'Wyświetlanie %1$d–%2$d z %3$d wyników', $total, 'mnsk7-storefront' ),
		$first,
		$last,
		$total
	);

	wp_send_json_success( array(
		'html'        => $rows_html,
		'has_next'    => $page < $max_pages,
		'result_count' => $result_count,
		'next_page'   => $page + 1,
	) );
}
add_action( 'wp_ajax_mnsk7_plp_load_more', 'mnsk7_plp_load_more_handler' );
add_action( 'wp_ajax_nopriv_mnsk7_plp_load_more', 'mnsk7_plp_load_more_handler' );

/** Ładne okruszki: separator › + wrapper */
add_filter( 'woocommerce_breadcrumb_defaults', function ( $args ) {
	$args['delimiter']   = ' <span class="separator" aria-hidden="true">›</span> ';
	$args['wrap_before'] = '<div class="mnsk7-breadcrumb-wrap"><nav class="woocommerce-breadcrumb" aria-label="' . esc_attr__( 'Nawigacja okruszków', 'mnsk7-storefront' ) . '">';
	$args['wrap_after']  = '</nav></div>';
	$args['home']        = __( 'Strona główna', 'mnsk7-storefront' );
	return $args;
} );

/** PDP: okruszki przy tytule produktu, nie pod headerem; bez gwiazdek ratingu (zgodnie z zadaniem) */
add_action( 'wp', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
} );

/** PDP: cena + "X osób kupiło" w jednym rzędzie (otwarcie wrappera przed ceną) */
add_action( 'woocommerce_single_product_summary', function () {
	echo '<div class="mnsk7-pdp-price-row">';
}, 14 );
/** PDP: zamknięcie wrappera ceny + wyświetlenie "X osób kupiło" obok ceny */
add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( $product && is_a( $product, 'WC_Product' ) ) {
		$sales = (int) $product->get_total_sales();
		if ( $sales > 0 ) {
			echo '<span class="mnsk7-product-sold-count">' . esc_html( sprintf( _n( '%d osoba kupiła', '%d osób kupiło', $sales, 'mnsk7-storefront' ), $sales ) ) . '</span>';
		}
	}
	echo '</div>';
}, 16 );

function mnsk7_is_catalog_back_url( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}

	$url       = esc_url_raw( $url );
	$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	$url_host  = wp_parse_url( $url, PHP_URL_HOST );
	if ( ! $home_host || ! $url_host || $home_host !== $url_host ) {
		return false;
	}

	$path = (string) wp_parse_url( $url, PHP_URL_PATH );
	if ( $path === '' ) {
		return false;
	}

	$shop_path = function_exists( 'wc_get_page_permalink' ) ? wp_parse_url( wc_get_page_permalink( 'shop' ), PHP_URL_PATH ) : '/sklep/';
	$shop_path = is_string( $shop_path ) && $shop_path !== '' ? trailingslashit( $shop_path ) : '/sklep/';
	$path_norm = trailingslashit( $path );

	if ( function_exists( 'wc_get_cart_url' ) ) {
		$cart_path = (string) wp_parse_url( wc_get_cart_url(), PHP_URL_PATH );
		if ( $cart_path !== '' && strpos( $path_norm, trailingslashit( $cart_path ) ) === 0 ) {
			return false;
		}
	}
	if ( function_exists( 'wc_get_checkout_url' ) ) {
		$checkout_path = (string) wp_parse_url( wc_get_checkout_url(), PHP_URL_PATH );
		if ( $checkout_path !== '' && strpos( $path_norm, trailingslashit( $checkout_path ) ) === 0 ) {
			return false;
		}
	}
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		$account_path = (string) wp_parse_url( wc_get_page_permalink( 'myaccount' ), PHP_URL_PATH );
		if ( $account_path !== '' && strpos( $path_norm, trailingslashit( $account_path ) ) === 0 ) {
			return false;
		}
	}

	if ( strpos( $path_norm, $shop_path ) === 0 ) {
		return true;
	}

	foreach ( array( 'product_cat', 'product_tag' ) as $taxonomy ) {
		$obj = get_taxonomy( $taxonomy );
		if ( $obj && ! empty( $obj->rewrite['slug'] ) ) {
			$base = '/' . trim( (string) $obj->rewrite['slug'], '/' ) . '/';
			if ( strpos( $path_norm, $base ) === 0 ) {
				return true;
			}
		}
	}

	$query = (string) wp_parse_url( $url, PHP_URL_QUERY );
	if ( $query !== '' ) {
		parse_str( $query, $args );
		if ( ! empty( $args['s'] ) && ( empty( $args['post_type'] ) || $args['post_type'] === 'product' ) ) {
			return true;
		}
	}

	return false;
}

function mnsk7_get_catalog_back_url() {
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	if ( $referer && mnsk7_is_catalog_back_url( $referer ) ) {
		if ( function_exists( 'mnsk7_plp_anchor_results' ) ) {
			$referer = mnsk7_plp_anchor_results( $referer );
		}
		return $referer;
	}

	$cookie = isset( $_COOKIE['mnsk7_catalog_back'] ) ? esc_url_raw( wp_unslash( $_COOKIE['mnsk7_catalog_back'] ) ) : '';
	if ( $cookie && mnsk7_is_catalog_back_url( $cookie ) ) {
		if ( function_exists( 'mnsk7_plp_anchor_results' ) ) {
			$cookie = mnsk7_plp_anchor_results( $cookie );
		}
		return $cookie;
	}

	return '';
}

function mnsk7_normalize_breadcrumb_label( $label ) {
	$label = is_string( $label ) ? wp_strip_all_tags( $label ) : '';
	$label = remove_accents( $label );
	$label = function_exists( 'mb_strtolower' ) ? mb_strtolower( $label, 'UTF-8' ) : strtolower( $label );
	return trim( preg_replace( '/\s+/u', ' ', $label ) );
}

function mnsk7_render_pdp_back_to_results() {
	if ( ! is_singular( 'product' ) ) {
		return;
	}

	$back_url = mnsk7_get_catalog_back_url();
	if ( ! $back_url ) {
		return;
	}

	echo '<p class="mnsk7-pdp-back-search"><a href="' . esc_url( $back_url ) . '" class="mnsk7-pdp-back-search__link">' . esc_html__( 'Wróć do wyników', 'mnsk7-storefront' ) . '</a></p>';
}

/** PDP a11y: etykieta pola Ilość bez nazwy produktu (tylko "Ilość") */
add_filter( 'woocommerce_quantity_input_args', function ( $args, $product ) {
	if ( is_singular( 'product' ) ) {
		$args['product_name'] = '';
	}
	return $args;
}, 10, 2 );

/** Quantity steppers: + / - buttons for numeric quantity inputs in theme overrides. */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	?>
	<script id="mnsk7-quantity-stepper">
	document.addEventListener('click', function(event) {
		var button = event.target.closest('.mnsk7-qty-btn');
		if (!button) return;
		var wrap = button.closest('.quantity');
		var input = wrap ? wrap.querySelector('input.qty:not([type="hidden"])') : null;
		if (!input || input.disabled || input.readOnly) return;
		var step = parseFloat(input.step || '1');
		if (!isFinite(step) || step <= 0) step = 1;
		var min = input.min !== '' ? parseFloat(input.min) : 0;
		if (!isFinite(min)) min = 0;
		var max = input.max !== '' ? parseFloat(input.max) : Infinity;
		if (!isFinite(max)) max = Infinity;
		var current = parseFloat(input.value || '');
		if (!isFinite(current)) current = min || 0;
		var next = current + (button.classList.contains('mnsk7-qty-btn--plus') ? step : -step);
		next = Math.max(min, Math.min(max, next));
		if (next === current) return;
		var normalized = String(Math.round(next * 1000) / 1000).replace(/\.0+$/, '').replace(/(\.\d*?)0+$/, '$1');
		input.value = normalized;
		input.dispatchEvent(new Event('input', { bubbles: true }));
		input.dispatchEvent(new Event('change', { bubbles: true }));
	});
	</script>
	<?php
}, 100 );

/** PDP: related products pod opisem (po zakładkach), podtytuł w szablonie related.php */
add_action( 'wp', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 20 );
	add_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_related_products', 25 );
}, 25 );

/** PDP: upsells — polski nagłówek (szablon w woocommerce/single-product/up-sells.php z podtytułem) */
add_filter( 'woocommerce_product_upsells_products_heading', function ( $heading ) {
	return __( 'Może spodoba się również…', 'mnsk7-storefront' );
}, 10 );

/** PDP: podobne produkty — do 8 pozycji; klasa columns-* + siatka w 12-related-products.css (:has dopasowuje kolumny do liczby kart). */
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 8;
	$args['columns']        = 3;
	return $args;
}, 10 );

/** 1.1 Cena w pętli (bestsellery, related, PLP): fallback gdy pusta; suffix "zł" na głównej */
add_filter( 'woocommerce_get_price_html', function ( $html, $product ) {
	if ( in_the_loop() && (string) $html === '' ) {
		return '<span class="woocommerce-price-fallback">' . esc_html__( 'Cena na zapytanie', 'mnsk7-storefront' ) . '</span>';
	}
	if ( ! $html || ! is_front_page() || ! in_the_loop() ) {
		return $html;
	}
	$h = (string) $html;
	if ( strpos( $h, 'zł' ) !== false || strpos( $h, 'PLN' ) !== false ) {
		return $html;
	}
	return $html . ' <span class="woocommerce-price-suffix">zł</span>';
}, 5, 2 );

/** Użycie theme loop/price.php (zawsze blok .price, fallback) zamiast domyślnego WooCommerce */
add_action( 'init', function () {
	if ( ! function_exists( 'woocommerce_template_loop_price' ) ) {
		return;
	}
	remove_action( 'woocommerce_after_shop_loop_item_title', 'woocommerce_template_loop_price', 10 );
	add_action( 'woocommerce_after_shop_loop_item_title', function () {
		wc_get_template( 'loop/price.php' );
	}, 10 );
}, 20 );

/** Link produktu tylko wokół miniatury — nazwa i cena na zewnątrz (inaczej aspect-ratio 1 + overflow:hidden na .woocommerce-loop-product__link obcina tekst). Tytuł w osobnej linku — klikalny. */
add_action( 'init', function () {
	if ( ! function_exists( 'woocommerce_template_loop_product_link_close' ) || ! function_exists( 'woocommerce_template_loop_product_link_open' ) ) {
		return;
	}
	remove_action( 'woocommerce_after_shop_loop_item', 'woocommerce_template_loop_product_link_close', 5 );
	add_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_link_close', 15 );
	add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_link_open', 0 );
	add_action( 'woocommerce_shop_loop_item_title', 'woocommerce_template_loop_product_link_close', 15 );
}, 25 );

/** 4.6 Tekst pustego koszyka (do wyświetlenia w cart-empty.php) */
add_filter( 'wc_empty_cart_message', function () {
	return __( 'Twój koszyk jest pusty — wróć do sklepu', 'mnsk7-storefront' );
}, 10 );

/** Audit task 4: jeden bank cookie — wyłącz bank temy, gdy aktywny Cookie Law Info (duplikat) */
add_filter( 'mnsk7_show_cookie_bar', function ( $show ) {
	if ( defined( 'CLI_VERSION' ) ) {
		return false;
	}
	return $show;
}, 5 );

/**
 * Zwraca stan zgody na opcjonalne pliki cookie (RODO).
 * Wartość ustawiana w JS (localStorage + cookie). Na serwerze dostępna tylko cookie.
 *
 * @return string|null 'accept' | 'reject' | null (brak wyboru lub brak cookie)
 */
function mnsk7_get_cookie_consent() {
	$key = 'mnsk7_cookie_consent';
	if ( isset( $_COOKIE[ $key ] ) && is_string( $_COOKIE[ $key ] ) ) {
		$v = sanitize_text_field( wp_unslash( $_COOKIE[ $key ] ) );
		if ( $v === 'accept' || $v === 'reject' ) {
			return $v;
		}
		if ( $v === '1' ) {
			return 'accept'; // legacy
		}
	}
	return null;
}

/**
 * Mega menu "Sklep": nagłówki sekcji dla użytkownika (nie nazwy taksonomii Woo).
 * Domyślnie: product_cat → "Rodzaje frezów", product_tag → "Zastosowanie i materiały".
 * Aby zmienić: add_filter( 'mnsk7_megamenu_heading_categories', fn( $s ) => 'Twoja etykieta' );
 *              add_filter( 'mnsk7_megamenu_heading_tags', fn( $s ) => 'Twoja etykieta' );
 */

/** 4.0 UX: promo bar — wyłącznie informacja o darmowej dostawie + zamknij (bez lojalności / drugiego CTA). Front-page i koszyk/checkout jak wcześniej ukryte. */
add_filter( 'mnsk7_header_promo_text', function ( $text ) {
	if ( $text !== '' ) {
		return $text;
	}
	if ( is_front_page() ) {
		return '';
	}
	if ( function_exists( 'is_cart' ) && is_cart() ) {
		return '';
	}
	if ( function_exists( 'is_checkout' ) && is_checkout() ) {
		return '';
	}
	if ( is_page( 'dostawa-i-platnosci' ) ) {
		return '';
	}
	if ( is_page( 'kontakt' ) ) {
		return '';
	}
	if ( is_singular( 'page' ) ) {
		$template = get_page_template_slug( get_queried_object_id() );
		if ( $template === 'page-kontakt.php' || $template === 'page-dostawa.php' ) {
			return '';
		}
	}
	$dostawa_url = home_url( '/dostawa-i-platnosci/' );

	return '<p class="mnsk7-promo-bar__msg"><a class="mnsk7-promo-bar__msg-link" href="' . esc_url( $dostawa_url ) . '">'
		. esc_html__( 'Darmowa dostawa od 300 zł', 'mnsk7-storefront' )
		. '</a></p>';
}, 5 );

/** Audit task 14: H1 na stronie Moje konto — jeden nagłówek (zalogowani: przed nawigacją; goście: przed formularzem). Bez duplikatu. */
add_action( 'woocommerce_before_account_navigation', function () {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	if ( is_user_logged_in() ) {
		echo '<h1 class="mnsk7-account-title entry-title">' . esc_html__( 'Moje konto', 'mnsk7-storefront' ) . '</h1>';
	}
}, 5 );
add_action( 'woocommerce_before_customer_login_form', function () {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		echo '<h1 class="mnsk7-account-title entry-title">' . esc_html__( 'Moje konto', 'mnsk7-storefront' ) . '</h1>';
		echo '<aside class="mnsk7-login-benefits" aria-label="' . esc_attr__( 'Korzyści dla zalogowanych klientów', 'mnsk7-storefront' ) . '">';
		echo '<h2 class="mnsk7-login-benefits__title">' . esc_html__( 'Dlaczego warto mieć konto MNSK7', 'mnsk7-storefront' ) . '</h2>';
		echo '<ul class="mnsk7-login-benefits__list">';
		echo '<li>' . esc_html__( 'Historia zamówień i szybkie ponowienie zakupu.', 'mnsk7-storefront' ) . '</li>';
		echo '<li>' . esc_html__( 'Faktury VAT i dane firmy zawsze pod ręką.', 'mnsk7-storefront' ) . '</li>';
		echo '<li>' . esc_html__( 'Program rabatowy dla stałych klientów.', 'mnsk7-storefront' ) . '</li>';
		echo '<li>' . esc_html__( 'Szybsze przejście przez checkout.', 'mnsk7-storefront' ) . '</li>';
		echo '</ul>';
		echo '</aside>';
	}
}, 1 );

/** Audit: po logowaniu z checkout — przekierowanie z powrotem na zamówienie */
add_filter( 'woocommerce_login_redirect', function ( $redirect, $user ) {
	$to = null;
	if ( ! empty( $_GET['redirect_to'] ) && strpos( $_GET['redirect_to'], 'zamowienie' ) !== false ) {
		$to = wp_validate_redirect( esc_url_raw( wp_unslash( $_GET['redirect_to'] ) ), $redirect );
	}
	if ( ! $to && ! empty( $_SERVER['HTTP_REFERER'] ) && strpos( $_SERVER['HTTP_REFERER'], 'zamowienie' ) !== false && function_exists( 'wc_get_checkout_url' ) ) {
		$to = wc_get_checkout_url();
	}
	return $to ?: $redirect;
}, 10, 2 );

/** Footer: dane firmy (jur. adres, KRS, NIP, REGON) — nadpisuj przez filtr mnsk7_footer_legal_address */
add_filter( 'mnsk7_footer_legal_address', function ( $address ) {
	if ( $address !== '' ) {
		return $address;
	}
	return '<strong class="mnsk7-footer__legal-name">MNSK7 Spółka z o.o.</strong><br>'
		. 'ul. Williama Heerleina Lindleya 16/512, 02-013 Warszawa (Ochota)<br>'
		. '<span class="mnsk7-footer__legal-registry">KRS: 0001072755 &middot; NIP: 5242991741 &middot; REGON: 527101693</span>';
}, 5 );

/** 4.1 Korzyń: fallback — jeśli strona koszyka pusta lub bez shortcode, wyświetl [woocommerce_cart] */
add_filter( 'the_content', function ( $content ) {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return $content;
	}
	$trimmed = trim( (string) $content );
	if ( $trimmed === '' || strpos( $content, 'woocommerce_cart' ) === false ) {
		return do_shortcode( '[woocommerce_cart]' );
	}
	return $content;
}, 3 );

/** 4.2 UX: przycisk "Kontynuuj zakupy" na stronie koszyka */
add_action( 'woocommerce_before_cart', function () {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	echo '<p class="mnsk7-cart-continue">';
	echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button mnsk7-btn-back">' . esc_html__( 'Kontynuuj zakupy', 'mnsk7-storefront' ) . '</a>';
	echo '</p>';
}, 5 );

add_action( 'woocommerce_before_checkout_form', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	echo '<nav class="mnsk7-checkout-steps" aria-label="' . esc_attr__( 'Etapy zamówienia', 'mnsk7-storefront' ) . '">';
	echo '<span class="mnsk7-checkout-steps__item mnsk7-checkout-steps__item--done">' . esc_html__( 'Koszyk', 'mnsk7-storefront' ) . '</span>';
	echo '<span class="mnsk7-checkout-steps__divider" aria-hidden="true">→</span>';
	echo '<span class="mnsk7-checkout-steps__item mnsk7-checkout-steps__item--active">' . esc_html__( 'Dane', 'mnsk7-storefront' ) . '</span>';
	echo '<span class="mnsk7-checkout-steps__divider" aria-hidden="true">→</span>';
	echo '<span class="mnsk7-checkout-steps__item">' . esc_html__( 'Płatność', 'mnsk7-storefront' ) . '</span>';
	echo '</nav>';
	echo '<div class="mnsk7-checkout-trust" aria-label="' . esc_attr__( 'Korzyści zakupowe', 'mnsk7-storefront' ) . '">';
	echo '<span>' . esc_html__( 'Wysyłka 24h dla produktów z magazynu', 'mnsk7-storefront' ) . '</span>';
	echo '<span>' . esc_html__( 'Faktura VAT dla firmy i zakupów B2B', 'mnsk7-storefront' ) . '</span>';
	echo '<span>' . esc_html__( 'Wsparcie w doborze przed zakupem', 'mnsk7-storefront' ) . '</span>';
	echo '</div>';
}, 3 );

/* Newsletter: zapis e-mail do opcji (można później podłączyć Mailchimp / integrację) */
add_action( 'template_redirect', function () {
	if ( ! isset( $_POST['mnsk7_newsletter'] ) || empty( $_POST['mnsk7_newsletter_email'] ) ) {
		return;
	}
	if ( ! isset( $_POST['mnsk7_newsletter_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mnsk7_newsletter_nonce'] ) ), 'mnsk7_newsletter' ) ) {
		wp_safe_redirect( add_query_arg( 'mnsk7_newsletter', 'invalid', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}
	$email = sanitize_email( wp_unslash( $_POST['mnsk7_newsletter_email'] ) );
	if ( ! is_email( $email ) ) {
		wp_safe_redirect( add_query_arg( 'mnsk7_newsletter', 'invalid', wp_get_referer() ?: home_url( '/' ) ) );
		exit;
	}
	$saved = get_option( 'mnsk7_newsletter_emails', array() );
	if ( ! is_array( $saved ) ) {
		$saved = array();
	}
	$saved[] = array( 'email' => $email, 'date' => current_time( 'mysql' ) );
	update_option( 'mnsk7_newsletter_emails', array_slice( $saved, -500 ) );
	wp_safe_redirect( add_query_arg( 'mnsk7_newsletter', 'ok', wp_get_referer() ?: home_url( '/' ) ) );
	exit;
}, 5 );

add_action( 'wp_footer', function () {
	if ( ! isset( $_GET['mnsk7_newsletter'] ) ) {
		return;
	}
	$status = sanitize_key( wp_unslash( $_GET['mnsk7_newsletter'] ) );
	$msg    = ( $status === 'ok' )
		? __( 'Dziękujemy za zapis do newslettera.', 'mnsk7-storefront' )
		: __( 'Podaj poprawny adres e-mail.', 'mnsk7-storefront' );
	echo '<script>document.addEventListener("DOMContentLoaded",function(){if(typeof wc_add_to_cart_params!=="undefined"){alert("' . esc_js( $msg ) . '");}else{alert("' . esc_js( $msg ) . '");}});</script>';
}, 1 );

add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
		return;
	}
	?>
	<script>
	(function() {
		if (window.innerWidth > 768) return;
		var selectors = [
			'.woocommerce-form-login-toggle',
			'.woocommerce-form-coupon-toggle',
			'.woocommerce-notices-wrapper .woocommerce-message',
			'.woocommerce-notices-wrapper .woocommerce-info'
		];
		var blocks = [];
		selectors.forEach(function(selector) {
			document.querySelectorAll(selector).forEach(function(el) {
				if (blocks.indexOf(el) === -1) blocks.push(el);
			});
		});
		if (!blocks.length) return;
		blocks.forEach(function(el, index) {
			el.classList.add('mnsk7-checkout-notice-card');
			if (index > 0) {
				el.classList.add('mnsk7-checkout-notice-card--compact');
			}
		});
	})();
	</script>
	<?php
}, 20 );

/* Contact form: send email and redirect */
add_action( 'template_redirect', function () {
	if ( ! isset( $_POST['mnsk7_contact_form'] ) || empty( $_POST['mnsk7_contact_form'] ) ) {
		return;
	}
	$kontakt_url = ( get_page_by_path( 'kontakt' ) ) ? get_permalink( get_page_by_path( 'kontakt' ) ) : home_url( '/kontakt/' );
	if ( ! isset( $_POST['mnsk7_contact_nonce'] ) || ! wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_nonce'] ) ), 'mnsk7_contact_form' ) ) {
		wp_safe_redirect( add_query_arg( 'mnsk7_contact', 'error', $kontakt_url ) );
		exit;
	}
	$name    = isset( $_POST['mnsk7_contact_name'] ) ? sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_name'] ) ) : '';
	$email   = isset( $_POST['mnsk7_contact_email'] ) ? sanitize_email( wp_unslash( $_POST['mnsk7_contact_email'] ) ) : '';
	$phone   = isset( $_POST['mnsk7_contact_phone'] ) ? sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_phone'] ) ) : '';
	$subject = isset( $_POST['mnsk7_contact_subject'] ) ? sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_subject'] ) ) : '';
	$message = isset( $_POST['mnsk7_contact_message'] ) ? sanitize_textarea_field( wp_unslash( $_POST['mnsk7_contact_message'] ) ) : '';
	if ( empty( $name ) || ! is_email( $email ) || empty( $message ) ) {
		wp_safe_redirect( add_query_arg( 'mnsk7_contact', 'error', $kontakt_url ) );
		exit;
	}
	$to = defined( 'MNK7_CONTACT_EMAIL' ) ? MNK7_CONTACT_EMAIL : get_option( 'admin_email' );
	$mail_subject = sprintf(
		/* translators: %s: site name */
		__( '[Kontakt %s] ', 'mnsk7-storefront' ) . ( $subject ? $subject : __( 'Wiadomość z formularza', 'mnsk7-storefront' ) ),
		get_bloginfo( 'name' )
	);
	$body = sprintf( __( 'Imię i nazwisko: %s', 'mnsk7-storefront' ), $name ) . "\n";
	$body .= sprintf( __( 'E-mail: %s', 'mnsk7-storefront' ), $email ) . "\n";
	if ( $phone ) {
		$body .= sprintf( __( 'Telefon: %s', 'mnsk7-storefront' ), $phone ) . "\n";
	}
	if ( $subject ) {
		$body .= sprintf( __( 'Temat: %s', 'mnsk7-storefront' ), $subject ) . "\n";
	}
	$body .= "\n" . __( 'Wiadomość:', 'mnsk7-storefront' ) . "\n" . $message . "\n";
	$headers = array( 'Content-Type: text/plain; charset=UTF-8', 'Reply-To: ' . $name . ' <' . $email . '>' );
	$sent = wp_mail( $to, $mail_subject, $body, $headers );
	wp_safe_redirect( add_query_arg( 'mnsk7_contact', $sent ? 'ok' : 'error', $kontakt_url ) );
	exit;
}, 5 );

add_action( 'wp_footer', function () {
	if ( ! isset( $_GET['mnsk7_contact'] ) ) {
		return;
	}
	$status = sanitize_key( wp_unslash( $_GET['mnsk7_contact'] ) );
	$msg    = ( $status === 'ok' )
		? __( 'Dziękujemy. Twoja wiadomość została wysłana. Odpowiemy w dni robocze.', 'mnsk7-storefront' )
		: __( 'Wystąpił błąd podczas wysyłania. Spróbuj ponownie lub napisz na podany e-mail.', 'mnsk7-storefront' );
	echo '<script>document.addEventListener("DOMContentLoaded",function(){alert("' . esc_js( $msg ) . '");});</script>';
}, 1 );

/** Fallback menu for header when no primary menu set (callable by name for cache-safe wp_nav_menu). */
function mnsk7_header_fallback_menu() {
	echo '<ul id="mnsk7-primary-menu" class="mnsk7-header__menu">';
	if ( function_exists( 'wc_get_page_permalink' ) ) {
		echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Sklep', 'mnsk7-storefront' ) . '</a></li>';
	}
	echo '<li><a href="' . esc_url( home_url( '/kontakt/' ) ) . '">' . esc_html__( 'Kontakt', 'mnsk7-storefront' ) . '</a></li>';
	echo '</ul>';
}

function mnsk7_should_render_home_instagram() {
	return (bool) apply_filters( 'mnsk7_home_instagram_enabled', false );
}

/* 1. Enqueue styles — jeden plik runtime: main.css (zbudowany z parts przez scripts/build-main-css.sh).
 * Architektura: parts/ to tylko źródło do budowy; na staging/prod ładuje się wyłącznie main.css.
 * Eliminuje to podwójną strategię (parts vs main), która powodowała bug: staging mógł serwować
 * stary main.css lub rozjechane parts, a footer/header/PDP dostawały nieaktualne style.
 * Zawsze te same zasoby niezależnie od URL (cache). B1: theme ładuje się po WC (priority 20).
 */
add_action( 'wp_enqueue_scripts', function () {
	$v = defined( 'MNSK7_THEME_VERSION' ) ? MNSK7_THEME_VERSION : '3.0.11';
	$child_deps = array();
	if ( mnsk7_parent_storefront_available() ) {
		wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
		$child_deps[] = 'storefront-style';
	}
	if ( wp_style_is( 'woocommerce-layout', 'registered' ) ) {
		$child_deps[] = 'woocommerce-layout';
	}
	wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), $child_deps, $v );
	wp_enqueue_style( 'mnsk7-main', get_stylesheet_directory_uri() . '/assets/css/main.css', array( 'mnsk7-storefront-style' ), $v );
	// Footer accordion: single source of truth = external script (mobile-only behavior inside JS).
	wp_enqueue_script( 'mnsk7-footer-accordion', get_stylesheet_directory_uri() . '/assets/js/footer-accordion.js', array(), $v, true );
	if ( is_front_page() ) {
		wp_enqueue_script( 'mnsk7-bestsellers-strip', get_stylesheet_directory_uri() . '/assets/js/bestsellers-strip.js', array(), $v, true );
	}
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
		wp_enqueue_script( 'mnsk7-plp-thumb-dialog', get_stylesheet_directory_uri() . '/assets/js/plp-thumb-dialog.js', array(), $v, true );
	}
}, 20 );

/* Override WooCommerce clearfix: woocommerce-layout.css ładuje się PO naszej temie i ustawia .woocommerce ul.products::before{display:table}, co daje pustą pierwszą "komórkę" w gridzie. Dodajemy inline do handle WooCommerce, żeby nasze display:none było po ich regule. Również Moje konto: przyciski + padding (wygrywamy z WC). */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! wp_style_is( 'woocommerce-layout', 'registered' ) ) {
		return;
	}
	$css = 'ul.products::before,.woocommerce ul.products::before,.woocommerce-page ul.products::before,ul.products.columns-3::before,ul.products.columns-4::before{content:none!important;display:none!important}';
	$css .= 'body.woocommerce-account .mnsk7-header__search-input,body.woocommerce-account .mnsk7-header__search-dropdown .mnsk7-header__search-input{border-radius:var(--r-sm)!important;background:var(--color-white)!important}';
	$css .= 'body.woocommerce-account .mnsk7-header__search-submit,body.woocommerce-account .mnsk7-header__search-dropdown .mnsk7-header__search-submit{border-radius:var(--r-md)!important}';
	$css .= 'body.woocommerce-account .mnsk7-header__search-dropdown .mnsk7-header__search-input{border-radius:var(--r-sm) 0 0 var(--r-sm)!important}';
	$css .= 'body.woocommerce-account .mnsk7-header__search-dropdown .mnsk7-header__search-submit{border-radius:0 var(--r-sm) var(--r-sm) 0!important}';
	$css .= 'body.woocommerce-account .woocommerce .button,body.woocommerce-account .woocommerce input[type=submit],body.woocommerce-account .woocommerce button[type=submit],body.woocommerce-account input[type=submit],body.woocommerce-account button[type=submit]{border-radius:var(--r-md)!important;min-height:44px!important}';
	$css .= 'body.woocommerce-account .mnsk7-footer__newsletter-btn{border-radius:var(--r-md)!important}';
	$css .= 'body.woocommerce-account #content,body.woocommerce-account .mnsk7-content,body.woocommerce-account .site-main,body.woocommerce-account .mnsk7-main{max-width:var(--content-max);margin-left:auto;margin-right:auto;padding-left:1.5rem;padding-right:1.5rem;box-sizing:border-box}';
	$css .= 'body.woocommerce-account .col-full{max-width:100%;padding-left:0;padding-right:0}';
	$css .= '@media (max-width:768px){body.post-type-archive-product #content,body.post-type-archive-product .site-content,body.post-type-archive-product .mnsk7-content,body.tax-product_cat #content,body.tax-product_cat .site-content,body.tax-product_cat .mnsk7-content,body.tax-product_tag #content,body.tax-product_tag .site-content,body.tax-product_tag .mnsk7-content{margin-top:0!important}}';
	wp_add_inline_style( 'woocommerce-layout', $css );
}, 20 );

/**
 * LCP (Lighthouse): pierwszy obrazek w pętli produktów (karty/siatka) — eager + fetchpriority=high,
 * żeby przeglądarka nie odkładała ładowania i nie stosowała lazy. Dla tabeli PLP jest content-product-table-row.php.
 */
add_action( 'init', function () {
	remove_action( 'woocommerce_before_shop_loop_item_title', 'woocommerce_template_loop_product_thumbnail', 10 );
	add_action( 'woocommerce_before_shop_loop_item_title', function () {
		$attr = array();
		if ( function_exists( 'wc_get_loop_prop' ) && (int) wc_get_loop_prop( 'current' ) === 1 ) {
			$attr = array( 'loading' => 'eager', 'fetchpriority' => 'high' );
		}
		echo woocommerce_get_product_thumbnail( 'woocommerce_thumbnail', $attr, true ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}, 10 );
}, 20 );

/* Layout overrides: source of truth w 25-global-layout.css (ostatni part). Bez wp_footer — HANDOFF LAYOUT-STATE-REFACTOR. */

/**
 * P2.7: Zwraca kategorie i tagi do megamenu (z transient cache). Inwalidacja przy zapisie termu.
 */
function mnsk7_get_megamenu_terms() {
	$cached = get_transient( 'mnsk7_megamenu_terms' );
	if ( is_array( $cached ) && isset( $cached['cats'] ) && isset( $cached['tags'] ) && isset( $cached['accessories'] ) ) {
		return $cached;
	}
	$top_cats = array();
	$accessories = array();
	$top_tags = array();
	if ( taxonomy_exists( 'product_cat' ) ) {
		$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 16, 'orderby' => 'name' ) );
		$top_cats = is_wp_error( $top_cats ) ? array() : $top_cats;
		$grouped = function_exists( 'mnsk7_split_catalog_category_terms' ) ? mnsk7_split_catalog_category_terms( $top_cats ) : array( 'core' => $top_cats, 'accessories' => array() );
		$top_cats = isset( $grouped['core'] ) ? $grouped['core'] : array();
		$accessories = isset( $grouped['accessories'] ) ? $grouped['accessories'] : array();
	}
	if ( taxonomy_exists( 'product_tag' ) ) {
		$top_tags = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true, 'number' => 10, 'orderby' => 'count', 'order' => 'DESC' ) );
		$top_tags = is_wp_error( $top_tags ) ? array() : $top_tags;
		$top_tags = array_filter( $top_tags, function ( $t ) {
			$slug_ok = isset( $t->slug ) && strtolower( $t->slug ) !== 'sklep';
			$name_ok = empty( $t->name ) || trim( strtolower( $t->name ) ) !== 'sklep';
			return $slug_ok && $name_ok;
		} );
	}
	set_transient( 'mnsk7_megamenu_terms', array( 'cats' => $top_cats, 'accessories' => $accessories, 'tags' => $top_tags ), 12 * HOUR_IN_SECONDS );
	return array( 'cats' => $top_cats, 'accessories' => $accessories, 'tags' => $top_tags );
}

/**
 * Whether a top-level product category should be treated as accessory/navigation-secondary.
 *
 * @param WP_Term $term Product category term.
 * @return bool
 */
function mnsk7_is_accessory_product_category( $term ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		return false;
	}
	$slug = isset( $term->slug ) ? sanitize_title( $term->slug ) : '';
	return in_array(
		$slug,
		array(
			'plytki-wieloostrzowe',
			'zestaw-frezow-do-drewna',
			'zestaw-gwintownikow',
		),
		true
	);
}

function mnsk7_is_hidden_catalog_product_category( $term ) {
	if ( ! ( $term instanceof WP_Term ) ) {
		return false;
	}
	$slug = isset( $term->slug ) ? sanitize_title( $term->slug ) : '';
	return in_array(
		$slug,
		array(
			'zestaw-frezow-do-drewna',
			'zestaw-gwintownikow',
			'zestaw-gwintownikow-i-narzynki',
		),
		true
	);
}

/**
 * Split top-level category terms into core cutter families and accessory-like categories.
 *
 * @param array $terms Category terms.
 * @return array{core: array, accessories: array}
 */
function mnsk7_split_catalog_category_terms( $terms ) {
	$grouped = array(
		'core'        => array(),
		'accessories' => array(),
	);
	if ( empty( $terms ) || ! is_array( $terms ) ) {
		return $grouped;
	}
	foreach ( $terms as $term ) {
		if ( function_exists( 'mnsk7_is_hidden_catalog_product_category' ) && mnsk7_is_hidden_catalog_product_category( $term ) ) {
			continue;
		}
		if ( function_exists( 'mnsk7_is_accessory_product_category' ) && mnsk7_is_accessory_product_category( $term ) ) {
			$grouped['accessories'][] = $term;
			continue;
		}
		$grouped['core'][] = $term;
	}
	return $grouped;
}

function mnsk7_clear_megamenu_transient() {
	delete_transient( 'mnsk7_megamenu_terms' );
}
add_action( 'edited_product_cat', 'mnsk7_clear_megamenu_transient' );
add_action( 'created_product_cat', 'mnsk7_clear_megamenu_transient' );
add_action( 'delete_product_cat', 'mnsk7_clear_megamenu_transient' );
add_action( 'edited_product_tag', 'mnsk7_clear_megamenu_transient' );
add_action( 'created_product_tag', 'mnsk7_clear_megamenu_transient' );
add_action( 'delete_product_tag', 'mnsk7_clear_megamenu_transient' );

/**
 * Zwraca kwotę rabatu lojalnościowego w koszyku (ujemna liczba lub 0).
 * Używane w headerze i fragmentach do wyświetlenia "Rabat: -X zł".
 */
function mnsk7_header_cart_loyalty_discount() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return 0.0;
	}
	$fees = WC()->cart->get_fees();
	foreach ( $fees as $fee ) {
		if ( isset( $fee->name ) && strpos( (string) $fee->name, 'Rabat lojalnościowy' ) !== false ) {
			$amount = isset( $fee->total ) ? (float) $fee->total : (float) $fee->amount;
			return $amount <= 0 ? $amount : 0.0;
		}
	}
	return 0.0;
}

/**
 * Zwraca HTML bloku podsumowania mini-koszyka (liczba produktów, suma, rabat).
 */
function mnsk7_header_cart_summary_html( $cart_count, $cart_total, $loyalty_discount ) {
	$out = '<div class="mnsk7-header__cart-summary">';
	if ( $cart_count > 0 && $cart_total ) {
		$count_text = sprintf( _n( '%d produkt', '%d produktów', $cart_count, 'mnsk7-storefront' ), $cart_count );
		$out .= '<div class="mnsk7-header__cart-summary__main">';
		$out .= '<span class="mnsk7-header__cart-summary__count">' . esc_html( $count_text ) . '</span>';
		$out .= '<span class="mnsk7-header__cart-summary__total">' . wp_kses_post( $cart_total ) . '</span>';
		$out .= '</div>';
		if ( $loyalty_discount < 0 && function_exists( 'wc_price' ) ) {
			$out .= '<div class="mnsk7-header__cart-summary__discount">';
			$out .= esc_html__( 'Rabat:', 'mnsk7-storefront' ) . ' ' . wc_price( abs( $loyalty_discount ) );
			$out .= '</div>';
		}
	} else {
		$out .= esc_html__( 'Koszyk jest pusty', 'mnsk7-storefront' );
	}
	$out .= '</div>';
	return $out;
}

/* 1b. Enqueue cart fragments so header cart count updates via AJAX. PERFORMANCE: nie ładuj na cart/checkout (Pass 2) ani na home (P1.1 — redukcja TBT; licznik odświeży się po przeładowaniu). Defer zostaje. */
add_action( 'wp_enqueue_scripts', function () {
	if ( is_admin() || ! function_exists( 'WC' ) ) {
		return;
	}
	if ( function_exists( 'is_cart' ) && function_exists( 'is_checkout' ) && ( is_cart() || is_checkout() ) ) {
		return;
	}
	wp_enqueue_script( 'wc-cart-fragments' );
}, 5 );

add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( $handle === 'wc-cart-fragments' ) {
		return str_replace( ' src=', ' defer src=', $tag );
	}
	return $tag;
}, 10, 3 );

/* 1c. Cart fragments: trigger (icon + count) + summary in dropdown */
add_filter( 'woocommerce_add_to_cart_fragments', function ( $fragments ) {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return $fragments;
	}
	$cart_count = WC()->cart->get_cart_contents_count();
	$cart_total = WC()->cart->get_cart_total();
	$loyalty_discount = function_exists( 'mnsk7_header_cart_loyalty_discount' ) ? mnsk7_header_cart_loyalty_discount() : 0.0;
	$cart_aria_label = $cart_count === 0
		? __( 'Koszyk', 'mnsk7-storefront' )
		: sprintf( _n( 'Koszyk, %d pozycja', 'Koszyk, %d pozycji', $cart_count, 'mnsk7-storefront' ), $cart_count );
	ob_start();
	?>
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-contents mnsk7-header__cart-trigger" aria-label="<?php echo esc_attr( $cart_aria_label ); ?>" aria-expanded="false" aria-controls="mnsk7-header-cart-dropdown">
		<span class="mnsk7-header__cart-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
		<span class="mnsk7-header__cart-count" aria-hidden="true"><?php echo absint( $cart_count ); ?></span>
	</a>
	<?php
	$fragments['a.mnsk7-header__cart-trigger'] = ob_get_clean();
	$fragments['a.cart-contents'] = $fragments['a.mnsk7-header__cart-trigger'];
	$fragments['.mnsk7-header__cart-summary'] = function_exists( 'mnsk7_header_cart_summary_html' )
		? mnsk7_header_cart_summary_html( $cart_count, $cart_total, $loyalty_discount )
		: '<div class="mnsk7-header__cart-summary">' . ( $cart_count > 0 && $cart_total ? sprintf( _n( '%1$d produkt · %2$s', '%1$d produktów · %2$s', $cart_count, 'mnsk7-storefront' ), $cart_count, $cart_total ) : esc_html__( 'Koszyk jest pusty', 'mnsk7-storefront' ) ) . '</div>';
	return $fragments;
}, 20 );

/* 1d. Header: mobile menu, search toggle, cart dropdown, promo bar dismiss, sticky shrink on scroll. PERFORMANCE: critical UI (menu, search, cart) — od razu; promo/shrink/Instagram — w requestIdleCallback, żeby nie blokować main thread i nie opóźniać pierwszego kliku w menu/search/cart. Na archive: cały init w jednym rIC (timeout 150) — redukcja TBT. */
add_action( 'wp_footer', function () {
	$mnsk7_is_archive = function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() );
	?>
	<script>
	window.mnsk7IsArchive = <?php echo $mnsk7_is_archive ? 'true' : 'false'; ?>;
	(function() {
		function runCritical() {
		var menuToggle = document.querySelector('.mnsk7-header__menu-toggle');
		var nav = document.querySelector('.mnsk7-header__nav');
		var cartWrap = document.querySelector('.mnsk7-header__cart');
		var searchToggle = document.querySelector('.mnsk7-header__search-toggle');
		var searchDropdown = document.getElementById('mnsk7-header-search');
		var searchPanel = document.getElementById('mnsk7-header-search-panel');
		var header = document.getElementById('masthead');
		var promoBar = document.getElementById('mnsk7-promo-bar');
		var DESKTOP_MIN = <?php echo (int) MNSK7_BREAKPOINT_MOBILE; ?>;
		var MOBILE_MAX = DESKTOP_MIN - 1;
		var TABLET_MIN = 769;
		var menu = document.getElementById('mnsk7-primary-menu');

		function closeMobileSubmenus() {
			if (!menu) return;
			menu.querySelectorAll('li.menu-item-has-children.is-open').forEach(function(li) {
				var submenu = li.querySelector(':scope > .sub-menu');
				if (submenu) {
					try { submenu.scrollTop = 0; } catch (e) {}
				}
				li.classList.remove('is-open');
				var parentLink = li.firstElementChild && li.firstElementChild.tagName === 'A' ? li.firstElementChild : li.querySelector('a');
				if (parentLink) parentLink.setAttribute('aria-expanded', 'false');
			});
		}

		function resetMobileMenuPosition() {
			if (!menu) return;
			try { menu.scrollTop = 0; } catch (e) {}
			try { menu.scrollTo({ top: 0, left: 0, behavior: 'auto' }); } catch (e) {}
			menu.querySelectorAll('.sub-menu').forEach(function(subMenu) {
				try { subMenu.scrollTop = 0; } catch (e) {}
				try { subMenu.scrollTo({ top: 0, left: 0, behavior: 'auto' }); } catch (e) {}
			});
		}

		// First-open correctness: sync promo offset and sticky shrink before deferred tasks.
		if (promoBar) {
			try {
				document.body.style.setProperty('--mnsk7-promo-h', promoBar.offsetHeight + 'px');
			} catch (e) {}
		}
		if (header && header.classList.contains('mnsk7-header') && !header.dataset.mnsk7ShrinkInit) {
			var SCROLL_ON = 70;
			var SCROLL_OFF = 30;
			var onScrollCritical = function() {
				var y = window.scrollY;
				if (y > SCROLL_ON) {
					header.classList.add('mnsk7-header--scrolled');
				} else if (y < SCROLL_OFF) {
					header.classList.remove('mnsk7-header--scrolled');
				}
			};
			onScrollCritical();
			window.addEventListener('scroll', onScrollCritical, { passive: true });
			header.dataset.mnsk7ShrinkInit = '1';
		}

		function closeMenu() {
			if (!nav) return;
			nav.classList.remove('is-open');
			closeMobileSubmenus();
			resetMobileMenuPosition();
			if (menuToggle) {
				menuToggle.setAttribute('aria-expanded', 'false');
				var menuOpenLabel = menuToggle.getAttribute('data-open-label');
				if (menuOpenLabel) menuToggle.setAttribute('aria-label', menuOpenLabel);
			}
		}

		function isDesktopViewport() {
			return window.innerWidth >= DESKTOP_MIN;
		}

		function isTabletViewport() {
			return window.innerWidth >= TABLET_MIN && window.innerWidth < DESKTOP_MIN;
		}

		function isMobileViewport() {
			return window.innerWidth <= 768;
		}

		function syncSearchPresentation() {
			var mobileOpen = document.body.classList.contains('mnsk7-search-open');
			var searchCloseLabel = searchToggle ? searchToggle.getAttribute('data-close-label') : '';
			var searchOpenLabel = searchToggle ? searchToggle.getAttribute('data-open-label') : '';

			if (searchDropdown) {
				searchDropdown.hidden = !isDesktopViewport();
			}

			if (searchPanel) {
				if (isTabletViewport()) {
					searchPanel.hidden = false;
					searchPanel.setAttribute('aria-hidden', 'false');
				} else if (isMobileViewport()) {
					searchPanel.hidden = !mobileOpen;
					searchPanel.setAttribute('aria-hidden', mobileOpen ? 'false' : 'true');
				} else {
					searchPanel.hidden = true;
					searchPanel.setAttribute('aria-hidden', 'true');
				}
			}

			if (searchToggle) {
				var expanded = isMobileViewport() && mobileOpen;
				searchToggle.setAttribute('aria-expanded', expanded ? 'true' : 'false');
				if (searchCloseLabel && searchOpenLabel) {
					searchToggle.setAttribute('aria-label', expanded ? searchCloseLabel : searchOpenLabel);
				}
			}
		}

		function setSearchOpen(open) {
			if (!searchToggle && !searchDropdown && !searchPanel) return;
			if (isDesktopViewport()) {
				document.body.classList.remove('mnsk7-search-open');
				syncSearchPresentation();
				return;
			}
			if (isTabletViewport()) {
				document.body.classList.remove('mnsk7-search-open');
				syncSearchPresentation();
				return;
			}
			document.body.classList.toggle('mnsk7-search-open', !!open);
			syncSearchPresentation();
			if (open) {
				var searchInput = document.getElementById('mnsk7-header-search-panel-input');
				if (searchInput) { setTimeout(function() { searchInput.focus(); }, 50); }
			}
		}

		function closeSearch() {
			if (!isMobileViewport()) return;
			if (document.body.classList.contains('mnsk7-search-open')) {
				setSearchOpen(false);
			}
		}

		function closeCart() {
			if (!cartWrap) return;
			cartWrap.classList.remove('is-open');
			var trigger = cartWrap.querySelector('.mnsk7-header__cart-trigger, .cart-contents');
			if (trigger) trigger.setAttribute('aria-expanded', 'false');
		}

		function closeAllMobileOverlays(except) {
			if (window.innerWidth >= DESKTOP_MIN) return;
			if (except !== 'menu') closeMenu();
			if (except !== 'search') closeSearch();
			if (except !== 'cart') closeCart();
		}

		if (menuToggle && nav) {
			var menuOpenLabel = menuToggle.getAttribute('data-open-label') || 'Otwórz menu';
			var menuCloseLabel = menuToggle.getAttribute('data-close-label') || 'Zamknij menu';
			function setMenuAria() {
				var open = nav.classList.contains('is-open');
				menuToggle.setAttribute('aria-expanded', open);
				menuToggle.setAttribute('aria-label', open ? menuCloseLabel : menuOpenLabel);
			}
			menuToggle.addEventListener('click', function() {
				var willOpen = !nav.classList.contains('is-open');
				if (window.innerWidth < DESKTOP_MIN && willOpen) {
					closeAllMobileOverlays('menu');
					closeMobileSubmenus();
					resetMobileMenuPosition();
				}
				nav.classList.toggle('is-open');
				if (!willOpen) closeMobileSubmenus();
				setMenuAria();
			});
		}
		window.addEventListener('pageshow', function(e) {
			if (window.innerWidth < DESKTOP_MIN) {
				closeMenu();
				closeSearch();
				closeCart();
			}
		});
		// Mobile (<=1023): tap na parent z submenu (np. "Sklep") rozwija submenu; bez przejścia po URL. Capture phase + pewne wykrycie linku (tap może dać target = tekst/child).
		if (menu) {
			function getLinkFromEvent(ev, root) {
				var el = ev.target;
				while (el && el !== root) {
					if (el.nodeType === 1 && el.tagName === 'A' && el.getAttribute('href')) return el;
					el = el.parentElement;
				}
				return null;
			}
			function isParentItemLink(link, parentLi) {
				if (!parentLi || !link) return false;
				return link.getAttribute('data-mnsk7') === 'sklep-parent' || parentLi.firstElementChild === link;
			}
			function alignOpenedMobileSubmenu(parentLi) {
				if (!menu || !parentLi) return;
				var submenu = parentLi.querySelector(':scope > .sub-menu');
				if (!submenu) return;
				// Root-cause fix: mobile menu must open from the top-level context, not auto-jump to remembered offsets.
				// Keep parent entry visible at the top and reset submenu scroll only.
				try { menu.scrollTop = 0; } catch (e) {}
				try { menu.scrollTo({ top: 0, left: 0, behavior: 'auto' }); } catch (e) {}
				try { submenu.scrollTop = 0; } catch (e) {}
				try { submenu.scrollTo({ top: 0, left: 0, behavior: 'auto' }); } catch (e) {}
			}
			menu.addEventListener('click', function(e) {
				var a = getLinkFromEvent(e, menu);
				if (!a || !a.href) return;
				var li = a.closest('li.menu-item-has-children');
				if (!li || !isParentItemLink(a, li)) return;
				if (window.innerWidth <= MOBILE_MAX) {
					// Mobile: first tap opens submenu, second tap navigates (only for parent link).
					if (!li.classList.contains('is-open')) {
						e.preventDefault();
						e.stopPropagation();
						closeMobileSubmenus();
						li.classList.add('is-open');
						var submenu = li.querySelector(':scope > .sub-menu');
						if (submenu) {
							try { submenu.scrollTop = 0; } catch (e) {}
						}
						alignOpenedMobileSubmenu(li);
						a.setAttribute('aria-expanded', 'true');
					}
				}
			}, true);
			// Mega menu (Sklep): hover delay 400ms na desktop — Baymard/NNG, bez flicker przy przejściu na panel
			var megamenuLi = menu.querySelector('li.menu-item-has-children .sub-menu.mnsk7-megamenu');
			if (megamenuLi) {
				megamenuLi = megamenuLi.closest('li.menu-item-has-children');
			}
			if (megamenuLi) {
				var openTimer, closeTimer;
				var HOVER_OPEN_MS = 400;
				var HOVER_CLOSE_MS = 150;
				var megamenuLink = megamenuLi.firstElementChild && megamenuLi.firstElementChild.tagName === 'A' ? megamenuLi.firstElementChild : null;
				function setMegamenuExpanded(open) {
					if (megamenuLink) megamenuLink.setAttribute('aria-expanded', open ? 'true' : 'false');
					if (open) megamenuLi.classList.add('mnsk7-megamenu-open'); else megamenuLi.classList.remove('mnsk7-megamenu-open');
				}
				function openMegamenu() {
					clearTimeout(closeTimer);
					if (window.innerWidth < DESKTOP_MIN) return;
					openTimer = setTimeout(function() { setMegamenuExpanded(true); }, HOVER_OPEN_MS);
				}
				function closeMegamenu() {
					clearTimeout(openTimer);
					closeTimer = setTimeout(function() { setMegamenuExpanded(false); }, HOVER_CLOSE_MS);
				}
				megamenuLi.addEventListener('mouseenter', openMegamenu);
				megamenuLi.addEventListener('mouseleave', closeMegamenu);
				megamenuLi.addEventListener('focusin', function() { if (window.innerWidth >= DESKTOP_MIN) { clearTimeout(closeTimer); setMegamenuExpanded(true); } });
				megamenuLi.addEventListener('focusout', function(e) {
					if (window.innerWidth < DESKTOP_MIN) return;
					setTimeout(function() { if (!megamenuLi.contains(document.activeElement)) setMegamenuExpanded(false); }, 0);
				});
				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && megamenuLi.classList.contains('mnsk7-megamenu-open')) {
						setMegamenuExpanded(false);
						if (megamenuLink) megamenuLink.focus();
					}
				});
			}
			// Mobile: po kliknięciu w link (nie w parent "Sklep") zamknij overlay — Przewodnik, Dostawa, Kontakt, podpunkty Sklep.
			menu.addEventListener('click', function(e) {
				var a = getLinkFromEvent(e, menu);
				if (!a || !a.getAttribute('href') || window.innerWidth >= DESKTOP_MIN || !nav) return;
				var parentLi = a.closest('li.menu-item-has-children');
				if (parentLi && isParentItemLink(a, parentLi)) return;
				closeMobileSubmenus();
				nav.classList.remove('is-open');
				if (menuToggle) { menuToggle.setAttribute('aria-expanded', 'false'); if (menuToggle.getAttribute('data-open-label')) menuToggle.setAttribute('aria-label', menuToggle.getAttribute('data-open-label')); }
			});
		}
		if (searchToggle || searchDropdown || searchPanel) {
			function updateSearchStateOnResize() {
				if (!isMobileViewport()) {
					document.body.classList.remove('mnsk7-search-open');
				}
				syncSearchPresentation();
			}
			window.addEventListener('resize', updateSearchStateOnResize);
			updateSearchStateOnResize();
			if (searchToggle) {
				searchToggle.addEventListener('click', function() {
					if (!isMobileViewport()) {
						return;
					}
					if (!document.body.classList.contains('mnsk7-search-open')) {
						closeAllMobileOverlays('search');
					}
					var open = document.body.classList.contains('mnsk7-search-open');
					setSearchOpen(!open);
				});
			}
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape') {
					if (document.body.classList.contains('mnsk7-search-open')) {
						setSearchOpen(false);
						if (searchToggle && searchToggle.offsetParent !== null) searchToggle.focus();
					}
				}
			});
			document.addEventListener('click', function(e) {
				var wrap = searchToggle && searchToggle.closest('.mnsk7-header__search-wrap');
				var panelClicked = searchPanel && searchPanel.contains(e.target);
				if (document.body.classList.contains('mnsk7-search-open') && wrap && !wrap.contains(e.target) && !panelClicked) {
					setSearchOpen(false);
				}
			});
			[searchDropdown, searchPanel].forEach(function(container) {
				if (!container) return;
				var searchForm = container.querySelector('form');
				if (searchForm) {
					searchForm.addEventListener('submit', function() {
						if (isMobileViewport()) {
							setSearchOpen(false);
						}
					});
				}
			});
		}
		function normalizeCartTrigger() {
			var wrap = cartWrap;
			if (!wrap) return null;
			var cartLink = wrap.querySelector('a.cart-contents, a.mnsk7-header__cart-trigger');
			if (!cartLink) return null;
			if (!cartLink.classList.contains('mnsk7-header__cart-trigger')) {
				cartLink.classList.add('mnsk7-header__cart-trigger');
			}
			if (!cartLink.querySelector('.mnsk7-header__cart-icon')) {
				var countNode = cartLink.querySelector('.mnsk7-header__cart-count, .count');
				var countText = countNode ? (countNode.textContent || '0') : '0';
				var count = parseInt(String(countText).replace(/[^\d]/g, ''), 10);
				if (isNaN(count)) count = 0;
				cartLink.innerHTML =
					'<span class=\"mnsk7-header__cart-icon\" aria-hidden=\"true\"><svg xmlns=\"http://www.w3.org/2000/svg\" width=\"22\" height=\"22\" viewBox=\"0 0 24 24\" fill=\"none\" stroke=\"currentColor\" stroke-width=\"2\" stroke-linecap=\"round\" stroke-linejoin=\"round\"><path d=\"M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z\"></path><line x1=\"3\" y1=\"6\" x2=\"21\" y2=\"6\"></line><path d=\"M16 10a4 4 0 0 1-8 0\"></path></svg></span>' +
					'<span class=\"mnsk7-header__cart-count\" aria-hidden=\"true\">' + count + '</span>';
			}
			var countNode = cartLink.querySelector('.mnsk7-header__cart-count, .count');
			var countText = countNode ? (countNode.textContent || '0') : '0';
			var count = parseInt(String(countText).replace(/[^\d]/g, ''), 10);
			if (isNaN(count)) count = 0;
			wrap.classList.toggle('mnsk7-header__cart--empty', count === 0);
			cartLink.setAttribute('aria-controls', 'mnsk7-header-cart-dropdown');
			if (!cartLink.hasAttribute('aria-expanded')) {
				cartLink.setAttribute('aria-expanded', 'false');
			}
			cartLink.setAttribute('aria-label', count > 0 ? ('Koszyk, ' + count + (count === 1 ? ' pozycja' : ' pozycji')) : 'Koszyk');
			return cartLink;
		}

		normalizeCartTrigger();
		if (window.jQuery && window.jQuery(document.body).on) {
			window.jQuery(document.body).on('wc_fragments_refreshed wc_fragments_loaded added_to_cart', function() {
				normalizeCartTrigger();
			});
		}

		if (cartWrap) {
			var trigger = normalizeCartTrigger() || cartWrap.querySelector('.mnsk7-header__cart-trigger, .cart-contents');
			var dropdown = cartWrap.querySelector('.mnsk7-header__cart-dropdown');
			if (trigger && dropdown) {
				function setCartExpanded(open) {
					trigger.setAttribute('aria-expanded', open ? 'true' : 'false');
				}
				document.addEventListener('click', function(e) {
					if (!cartWrap.contains(e.target)) {
						cartWrap.classList.remove('is-open');
						setCartExpanded(false);
					}
				});
				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && cartWrap.classList.contains('is-open')) {
						cartWrap.classList.remove('is-open');
						setCartExpanded(false);
						trigger.focus();
					}
				});
				// Mobile: Escape closes any active overlay
				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && window.innerWidth < DESKTOP_MIN) {
						if (cartWrap.classList.contains('is-open')) { cartWrap.classList.remove('is-open'); setCartExpanded(false); return; }
						if (document.body.classList.contains('mnsk7-search-open')) { setSearchOpen(false); return; }
						if (nav && nav.classList.contains('is-open')) { closeMenu(); return; }
					}
				});
				// Mobile: klik na trigger otwiera/zamyka dropdown (na desktop tylko hover)
				trigger.addEventListener('click', function(e) {
					if (window.innerWidth < DESKTOP_MIN) {
						e.preventDefault();
						if (!cartWrap.classList.contains('is-open')) {
							closeAllMobileOverlays('cart');
						}
						cartWrap.classList.toggle('is-open');
						setCartExpanded(cartWrap.classList.contains('is-open'));
					}
				});
				// Desktop: dropdown tylko przy hover na triggerze lub dropdownie (nie na całym headerze/bannerze)
				var cartOpenTimer;
				function openCart() {
					clearTimeout(cartOpenTimer);
					if (window.innerWidth >= DESKTOP_MIN) { cartWrap.classList.add('is-open'); setCartExpanded(true); }
				}
				function closeCart() {
					cartOpenTimer = setTimeout(function() {
						cartWrap.classList.remove('is-open');
						setCartExpanded(false);
					}, 120);
				}
				function cancelClose() { clearTimeout(cartOpenTimer); }
				trigger.addEventListener('mouseenter', openCart);
				trigger.addEventListener('mouseleave', closeCart);
				dropdown.addEventListener('mouseenter', cancelClose);
				dropdown.addEventListener('mouseenter', openCart);
				dropdown.addEventListener('mouseleave', closeCart);
			}
		}
		}
		function runDeferred() {
		// Promo bar: dismissible (sessionStorage). Critical CSS w header.php daje LCP; init tutaj bez rAF (corrective pass: rAF wiązał się z regresją TBT).
		var promoBar = document.getElementById('mnsk7-promo-bar');
		if (promoBar) {
			function setPromoHeightVar() {
				try {
					document.body.style.setProperty('--mnsk7-promo-h', promoBar.offsetHeight + 'px');
				} catch (e) {}
			}
			try {
				if (sessionStorage.getItem('mnsk7_promo_dismissed') === '1') {
					promoBar.remove();
					document.body.classList.remove('mnsk7-has-promo');
				} else {
					setPromoHeightVar();
					if (window.ResizeObserver) {
						try { new ResizeObserver(setPromoHeightVar).observe(promoBar); } catch (e) {}
					} else {
						window.addEventListener('resize', setPromoHeightVar, { passive: true });
					}
					var closeBtn = promoBar.querySelector('.mnsk7-promo-bar__close');
					if (closeBtn) {
						closeBtn.addEventListener('click', function() {
							try { sessionStorage.setItem('mnsk7_promo_dismissed', '1'); } catch (e) {}
							document.body.classList.remove('mnsk7-has-promo');
							document.body.style.removeProperty('--mnsk7-promo-h');
							promoBar.remove();
						});
					}
				}
			} catch (e) {}
		}
		// Header shrink when scrolled (Visual Audit). Hysteresis: add at 70px, remove at 30px — zapobiega "trzęsieniu" przy pozycji ~50px.
		var header = document.getElementById('masthead');
		if (header && header.classList.contains('mnsk7-header') && !header.dataset.mnsk7ShrinkInit) {
			var SCROLL_ON = 70;
			var SCROLL_OFF = 30;
			function onScroll() {
				var y = window.scrollY;
				if (y > SCROLL_ON) {
					header.classList.add('mnsk7-header--scrolled');
				} else if (y < SCROLL_OFF) {
					header.classList.remove('mnsk7-header--scrolled');
				}
			}
			onScroll();
			window.addEventListener('scroll', onScroll, { passive: true });
			header.dataset.mnsk7ShrinkInit = '1';
		}
		}
		// Critical header controls должны быть готовы сразу (без requestIdleCallback), иначе возможен "dead click".
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', runCritical);
		} else {
			runCritical();
		}
		// Deferred: promo/shrink/instagram — можно отложить.
		if (typeof requestIdleCallback === 'function') {
			requestIdleCallback(runDeferred, { timeout: 2000 });
		} else {
			setTimeout(runDeferred, 1);
		}
	})();
	</script>
	<?php
}, 20 );

/* Audit: pewne przejście z koszyka na checkout — fallback przy przechwyconym kliku */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_cart' ) || ! is_cart() ) {
		return;
	}
	$url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';
	if ( ! $url ) {
		return;
	}
	?>
	<script>
	(function() {
		var btn = document.getElementById('mnsk7-cart-checkout-button') || document.querySelector('.woocommerce-cart a.checkout-button');
		if (!btn || !btn.href) return;
		var checkoutUrl = <?php echo json_encode( $url ); ?>;
		function normalizePath(u) {
			try {
				var url = new URL(u, window.location.href);
				var path = (url.pathname || '/');
				path = path.replace(/\/+$/, '') || '/';
				return url.origin + path;
			} catch (e) {
				return '';
			}
		}
		var checkoutKey = normalizePath(checkoutUrl);
		btn.addEventListener('click', function(e) {
			var href = this.getAttribute('href') || this.href;
			if (!href) return;
			if (checkoutKey && normalizePath(href) === checkoutKey) {
				e.preventDefault();
				window.location.href = checkoutUrl;
			}
		}, true);
	})();
	</script>
	<?php
}, 5 );

/* Front page trust/loyalty counters: lekka animacja wzrostu liczb po wejściu sekcji w viewport. */
add_action( 'wp_footer', function () {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<script>
	(function() {
		if (window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;
		var counters = document.querySelectorAll('[data-mnsk7-counter]');
		if (!counters.length) return;

		function parseCounter(raw) {
			var text = (raw || '').trim();
			var normalized = text.replace(/\s+/g, '');
			var hasPercent = normalized.indexOf('%') !== -1;
			var hasPlus = normalized.indexOf('+') !== -1;
			var digits = normalized.replace(/[^\d]/g, '');
			var target = parseInt(digits, 10);
			if (!target || isNaN(target)) return null;
			return { target: target, hasPercent: hasPercent, hasPlus: hasPlus };
		}

		function formatValue(value) {
			return value.toString().replace(/\B(?=(\d{3})+(?!\d))/g, ' ');
		}

		function runCounter(el) {
			var meta = parseCounter(el.textContent);
			if (!meta) return;
			var duration = 950;
			var start = null;
			var paintedTarget = false;

			function paint(value) {
				var out = formatValue(value);
				if (meta.hasPlus) out += '+';
				if (meta.hasPercent) out += '%';
				el.textContent = out;
			}

			function step(ts) {
				if (!start) start = ts;
				var p = Math.min((ts - start) / duration, 1);
				var eased = 1 - Math.pow(1 - p, 3);
				var current = Math.round(meta.target * eased);
				paint(current);
				if (p < 1) {
					window.requestAnimationFrame(step);
				} else if (!paintedTarget) {
					paint(meta.target);
					paintedTarget = true;
				}
			}

			paint(0);
			window.requestAnimationFrame(step);
		}

		var observer = new IntersectionObserver(function(entries, obs) {
			entries.forEach(function(entry) {
				if (!entry.isIntersecting) return;
				runCounter(entry.target);
				obs.unobserve(entry.target);
			});
		}, { threshold: 0.55 });

		counters.forEach(function(counter) {
			observer.observe(counter);
		});
	})();
	</script>
	<?php
}, 6 );

/* PDP: sticky CTA na mobile — pokaż gdy formularz poza viewport, klik przewija i uruchamia główny przycisk */
add_action( 'wp_footer', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	?>
	<script>
	(function() {
		var sticky = document.getElementById('mnsk7-pdp-sticky-cta');
		var form = document.querySelector('.single-product .summary form.cart');
		var mainBtn = form ? form.querySelector('.single_add_to_cart_button') : null;
		if (!sticky || !form || !mainBtn) return;
		var stickyPrice = sticky.querySelector('.mnsk7-pdp-sticky-cta__price');
		var stickyStock = sticky.querySelector('.mnsk7-pdp-sticky-cta__stock');
		var stickyBtn = sticky.querySelector('.mnsk7-pdp-sticky-cta__btn');
		var summaryPrice = document.querySelector('.single-product .summary .price');
		var inlineAvailability = document.querySelector('.single-product .mnsk7-product-availability--inline');
		var defaultStickyPrice = stickyPrice ? stickyPrice.innerHTML : '';
		var defaultStickyStock = stickyStock ? stickyStock.textContent : '';
		var isSubmitting = false;
		function isMobile() { return window.matchMedia('(max-width: 768px)').matches; }
		function isInViewport(el) {
			if (!el) return false;
			var r = el.getBoundingClientRect();
			return r.top < (window.innerHeight || document.documentElement.clientHeight) && r.bottom > 0;
		}
		function setStickyHeightVar() {
			try { document.body.style.setProperty('--mnsk7-sticky-cta-h', sticky.offsetHeight + 'px'); } catch (e) {}
		}
		function clearStickyHeightVar() {
			try { document.body.style.removeProperty('--mnsk7-sticky-cta-h'); } catch (e) {}
		}
		function setStickyVisible(visible) {
			if (!isMobile()) {
				sticky.setAttribute('hidden', '');
				sticky.classList.remove('is-visible');
				document.body.classList.remove('mnsk7-pdp-sticky-cta-visible');
				clearStickyHeightVar();
				return;
			}
			if (visible) {
				sticky.removeAttribute('hidden');
				sticky.classList.add('is-visible');
				sticky.setAttribute('aria-hidden', 'false');
				document.body.classList.add('mnsk7-pdp-sticky-cta-visible');
				setStickyHeightVar();
			} else {
				sticky.setAttribute('hidden', '');
				sticky.classList.remove('is-visible');
				sticky.setAttribute('aria-hidden', 'true');
				document.body.classList.remove('mnsk7-pdp-sticky-cta-visible');
				clearStickyHeightVar();
			}
		}
		var observer = new IntersectionObserver(function(entries) {
			if (!isMobile()) return;
			var e = entries[0];
			setStickyVisible(!e.isIntersecting);
		}, { root: null, rootMargin: '0px', threshold: 0.1 });
		observer.observe(form);
		function syncStickyMeta() {
			if (stickyPrice && summaryPrice) {
				stickyPrice.innerHTML = summaryPrice.innerHTML || defaultStickyPrice;
			}
			if (stickyStock && inlineAvailability) {
				var stockText = (inlineAvailability.textContent || '').trim();
				if (stockText) stickyStock.textContent = stockText.replace(/^\s*[✓✔]\s*/u, '');
			}
		}
		function resetStickyMeta() {
			if (stickyPrice) stickyPrice.innerHTML = summaryPrice ? (summaryPrice.innerHTML || defaultStickyPrice) : defaultStickyPrice;
			if (stickyStock) stickyStock.textContent = defaultStickyStock;
		}
		var variationsForm = document.querySelector('.single-product form.variations_form');
		var defaultBtnLabel = stickyBtn ? stickyBtn.textContent : '';
		var chooseLbl = <?php echo json_encode( __( 'Wybierz wariant', 'mnsk7-storefront' ) ); ?>;
		function syncStickyActionability(action) {
			if (!stickyBtn) return;
			var disabled = action === 'add' ? !!mainBtn.disabled : false;
			stickyBtn.disabled = disabled;
			stickyBtn.setAttribute('aria-disabled', disabled ? 'true' : 'false');
		}
		function isVariationChosen() {
			if (!variationsForm) return true;
			var selects = variationsForm.querySelectorAll('.variations select');
			for (var i = 0; i < selects.length; i++) {
				if (!selects[i].value) return false;
			}
			return true;
		}
		function updateStickyBtnState() {
			if (!stickyBtn) return;
			if (variationsForm && !isVariationChosen()) {
				stickyBtn.textContent = chooseLbl;
				stickyBtn.dataset.action = 'choose';
				syncStickyActionability('choose');
			} else {
				stickyBtn.textContent = defaultBtnLabel;
				stickyBtn.dataset.action = 'add';
				syncStickyActionability('add');
			}
		}
		updateStickyBtnState();
		stickyBtn.addEventListener('click', function() {
			if (!isMobile() || isSubmitting) return;
			if (stickyBtn.dataset.action === 'choose') {
				form.scrollIntoView({ behavior: 'smooth', block: 'center' });
				var firstEmpty = null;
				if (variationsForm) {
					var selects = variationsForm.querySelectorAll('.variations select');
					for (var i = 0; i < selects.length; i++) {
						if (!selects[i].value) {
							firstEmpty = selects[i];
							break;
						}
					}
					if (!firstEmpty) firstEmpty = variationsForm.querySelector('.variations select');
				}
				if (firstEmpty) setTimeout(function() { firstEmpty.focus(); }, 350);
				return;
			}
			isSubmitting = true;
			mainBtn.click();
			setTimeout(function() { isSubmitting = false; }, 600);
		});
		window.addEventListener('resize', function() {
			if (!isMobile()) { setStickyVisible(false); return; }
			if (sticky.classList.contains('is-visible')) setStickyHeightVar();
		}, { passive: true });
		syncStickyMeta();
		if (variationsForm && window.jQuery) {
			variationsForm.addEventListener('change', function() {
				updateStickyBtnState();
				syncStickyMeta();
			});
			window.jQuery(form).on('show_variation', function(_event, variation) {
				if (stickyPrice) {
					stickyPrice.innerHTML = (variation && variation.price_html) ? variation.price_html : (summaryPrice ? summaryPrice.innerHTML : defaultStickyPrice);
				}
				syncStickyMeta();
				updateStickyBtnState();
				if (sticky.classList.contains('is-visible')) setStickyHeightVar();
			});
			window.jQuery(form).on('hide_variation reset_data', function() {
				resetStickyMeta();
				syncStickyMeta();
				updateStickyBtnState();
				if (sticky.classList.contains('is-visible')) setStickyHeightVar();
			});
		}
	})();
	</script>
	<?php
}, 6 );

/* 1e. PLP "Pokaż więcej": podгрузка następnych wierszy tabeli bez przejścia na page/2 */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
		return;
	}
	$ajax_url = admin_url( 'admin-ajax.php' );
	?>
	<script>
	(function() {
		var wrap = document.querySelector('.mnsk7-plp-load-more-wrap');
		if (!wrap) return;
		var btn = wrap.querySelector('.mnsk7-plp-load-more');
		var tbody = document.querySelector('.mnsk7-product-table tbody');
		if (!btn || !tbody) return;
		var ajaxUrl = <?php echo json_encode( $ajax_url ); ?>;
		btn.addEventListener('click', function(e) {
			e.preventDefault();
			if (btn.disabled) return;
			var currentPage = parseInt(wrap.dataset.currentPage || '1', 10);
			var nextPage = currentPage + 1;
			var taxonomy = wrap.dataset.taxonomy || '';
			var term = wrap.dataset.term || '';
			var orderbyEl = document.querySelector('.mnsk7-plp-toolbar select[name="orderby"], .woocommerce-ordering select[name="orderby"]');
			var orderby = orderbyEl ? orderbyEl.value : 'menu_order';
			var order = (orderby === 'price-desc' || orderby === 'rating') ? 'desc' : 'asc';
			btn.disabled = true;
			btn.classList.add('loading');
			var form = new FormData();
			form.append('action', 'mnsk7_plp_load_more');
			form.append('page', nextPage);
			form.append('taxonomy', taxonomy);
			form.append('term', term);
			form.append('orderby', orderby);
			form.append('order', order);
			form.append('nonce', <?php echo wp_json_encode( wp_create_nonce( 'mnsk7_plp_load_more' ) ); ?>);
			fetch(ajaxUrl, { method: 'POST', body: form, credentials: 'same-origin' })
				.then(function(r) { return r.json(); })
				.then(function(data) {
					if (data.success && data.data && data.data.html) {
						tbody.insertAdjacentHTML('beforeend', data.data.html);
						var rc = document.querySelectorAll('.woocommerce-result-count');
						if (data.data.result_count && rc.length) {
							rc.forEach(function(el) { el.textContent = data.data.result_count; });
						}
						wrap.dataset.currentPage = String(nextPage);
						if (!data.data.has_next) {
							wrap.style.display = 'none';
						}
					}
				})
				.catch(function() {})
				.finally(function() {
					btn.disabled = false;
					btn.classList.remove('loading');
				});
		});
	})();
	</script>
	<?php
}, 20 );

/* PLP layout sync: when viewport crosses mobile breakpoint, reload once to render proper server layout (table/list). */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
		return;
	}
	?>
	<script>
	(function() {
		var mq = window.matchMedia('(max-width: 768px)');
		var syncKey = 'mnsk7_plp_layout_sync_once';
		var reloaded = false;

		function viewportMode() {
			return window.innerWidth <= 768 ? 'mobile' : 'desktop';
		}

		function renderedMode() {
			if (document.querySelector('.mnsk7-plp-grid-mobile')) return 'mobile';
			if (document.querySelector('.mnsk7-product-table')) return 'desktop';
			return 'unknown';
		}

		function scheduleReload(reason, fromMode, toMode) {
			if (reloaded) return;
			var marker = [window.location.pathname, reason, fromMode, toMode].join('|');
			try {
				if (sessionStorage.getItem(syncKey) === marker) return;
				sessionStorage.setItem(syncKey, marker);
			} catch (e) {}
			reloaded = true;
			window.location.reload();
		}

		var initialViewport = viewportMode();
		var initialRendered = renderedMode();
		if (initialRendered !== 'unknown' && initialRendered !== initialViewport) {
			scheduleReload('boot-mismatch', initialRendered, initialViewport);
		}

		function handleViewportSwitch() {
			var currentViewport = viewportMode();
			if (currentViewport !== initialViewport) {
				scheduleReload('viewport-switch', initialViewport, currentViewport);
			}
		}

		if (typeof mq.addEventListener === 'function') {
			mq.addEventListener('change', handleViewportSwitch);
		} else if (typeof mq.addListener === 'function') {
			mq.addListener(handleViewportSwitch);
		}

		window.addEventListener('resize', handleViewportSwitch, { passive: true });
	})();
	</script>
	<?php
}, 21 );

/* 1e bis. PLP chips "Więcej" / "Więcej filtrów": pokaż/ukryj dodatkowe chipy i blok filtrów. Scroll do wyników po filter/category/tag. */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
		return;
	}
	$more_text = __( 'Więcej', 'mnsk7-storefront' );
	$less_text = __( 'Mniej', 'mnsk7-storefront' );
	$has_filter_params = ! empty( $_GET ) && preg_match( '/filter_/', implode( ' ', array_keys( $_GET ) ) );
	?>
	<script>
	(function() {
		function setExpandableTargetState(btn, target, expanded, labels) {
			if (btn) btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			if (target) {
				target.hidden = !expanded;
				target.setAttribute('aria-hidden', expanded ? 'false' : 'true');
			}
			if (btn && labels) {
				btn.textContent = expanded ? labels.less : labels.more;
			}
		}

		function initPlpToggles() {
			var toggles = document.querySelectorAll('.mnsk7-plp-chips-toggle');
			var moreLabel = <?php echo json_encode( $more_text ); ?>;
			var lessLabel = <?php echo json_encode( $less_text ); ?>;
			toggles.forEach(function(btn) {
				var id = btn.getAttribute('data-controls');
				if (!id) return;
				var target = document.getElementById(id);
				if (!target) return;
				var btnMore = btn.getAttribute('data-more-text') || moreLabel;
				var btnLess = btn.getAttribute('data-less-text') || lessLabel;
				var expandedInitial = btn.getAttribute('aria-expanded') === 'true' || !target.hidden;
				setExpandableTargetState(btn, target, expandedInitial, { more: btnMore, less: btnLess });
				btn.addEventListener('click', function() {
					var expanded = btn.getAttribute('aria-expanded') === 'true';
					setExpandableTargetState(btn, target, !expanded, { more: btnMore, less: btnLess });
				});
			});
		}
		function scrollToResults() {
			var el = document.getElementById('mnsk7-plp-results');
			if (el && (window.location.hash === '#mnsk7-plp-results' || <?php echo $has_filter_params ? 'true' : 'false'; ?>)) {
				el.scrollIntoView({ behavior: 'smooth', block: 'start' });
			}
		}
		function init() {
			initPlpToggles();
			scrollToResults();
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', init);
		} else {
			init();
		}
	})();
	</script>
	<?php
}, 19 );

/* 1e ter. Catalog chips "Więcej" na stronie głównej — rozwijanie ukrytych chipów w grupie. */
add_action( 'wp_footer', function () {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<script>
	(function() {
		function setExpandableTargetState(btn, target, expanded, labels) {
			if (btn) btn.setAttribute('aria-expanded', expanded ? 'true' : 'false');
			if (target) {
				target.hidden = !expanded;
				target.setAttribute('aria-hidden', expanded ? 'false' : 'true');
			}
			if (btn && labels) {
				btn.textContent = expanded ? labels.less : labels.more;
			}
		}

		function initCatalogChipsToggles() {
			var toggles = document.querySelectorAll('.mnsk7-catalog-chips-toggle');
			var moreLabel = <?php echo json_encode( __( 'Więcej', 'mnsk7-storefront' ) ); ?>;
			var lessLabel = <?php echo json_encode( __( 'Mniej', 'mnsk7-storefront' ) ); ?>;
			toggles.forEach(function(btn) {
				var id = btn.getAttribute('data-controls');
				if (!id) return;
				var target = document.getElementById(id);
				if (!target) return;
				var expandedInitial = btn.getAttribute('aria-expanded') === 'true' || !target.hidden;
				setExpandableTargetState(btn, target, expandedInitial, { more: moreLabel, less: lessLabel });
				btn.addEventListener('click', function() {
					var expanded = btn.getAttribute('aria-expanded') === 'true';
					setExpandableTargetState(btn, target, !expanded, { more: moreLabel, less: lessLabel });
				});
			});
		}
		if (document.readyState === 'loading') {
			document.addEventListener('DOMContentLoaded', initCatalogChipsToggles);
		} else {
			initCatalogChipsToggles();
		}
	})();
	</script>
	<?php
}, 20 );

/* 1f. Strefa wysyłki: nie pokazuj powiadomienia "Customer matched zone" / "Strefa wysyłki dopasowana" — zbędne na froncie, powodowało skok strony w dół */
function mnsk7_suppress_shipping_zone_matched_notice( $message ) {
	if ( ! is_string( $message ) || $message === '' ) {
		return $message;
	}
	$lower = function_exists( 'mb_strtolower' ) ? mb_strtolower( $message, 'UTF-8' ) : strtolower( $message );
	if ( strpos( $lower, 'strefa wysyłki' ) !== false || strpos( $lower, 'dopasowana' ) !== false
		|| strpos( $message, 'Customer matched zone' ) !== false ) {
		return '';
	}
	return $message;
}
add_filter( 'woocommerce_add_success', 'mnsk7_suppress_shipping_zone_matched_notice', 10, 1 );
add_filter( 'woocommerce_add_notice', 'mnsk7_suppress_shipping_zone_matched_notice', 10, 1 );

/* 1f bis. Na wypadek gdyby powiadomienie jednak się pojawiło: przenieś nad footer bez przewijania strony */
add_action( 'wp_footer', function () {
	?>
	<script>
	(function() {
		var placeholder = document.getElementById('mnsk7-shipping-zone-notice-placeholder');
		if (!placeholder) return;
		var notices = document.querySelectorAll('.woocommerce-info, .woocommerce-message');
		var scrollY = window.scrollY;
		for (var i = 0; i < notices.length; i++) {
			var text = (notices[i].textContent || '').trim();
			if (text.indexOf('Strefa wysyłki') !== -1 || text.indexOf('dopasowana') !== -1) {
				placeholder.appendChild(notices[i]);
				placeholder.classList.add('has-notice');
				requestAnimationFrame(function() { window.scrollTo(0, scrollY); });
				break;
			}
		}
	})();
	</script>
	<?php
}, 25 );

/* 2. Inter: local woff2 via @font-face (00-fonts-inter.css), no Google Fonts */
add_action( 'wp_enqueue_scripts', function () {
	if ( mnsk7_parent_storefront_available() ) {
		wp_dequeue_style( 'storefront-fonts' );
	}
	/* Inter loaded from assets/css/parts/00-fonts-inter.css (first in parts list) */
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

/* Header: jeden source of truth — header.php (mnsk7-header). Legacy storefront_header output usunięty; nie dodawać tu żadnych elementów headera. */

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

/* 7b. PLP: nie pokazuj shortcodów ani artefaktów filtrów ([wpf-filters id=7] + blok "Filtruj: Średnica: …") */
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
/** PLP: na stronie Sklep (bez kategorii/tagów) H1 = komunikat katalogu zamiast „Sklep”. */
add_filter(
	'woocommerce_page_title',
	function ( $title ) {
		if ( function_exists( 'is_shop' ) && is_shop() && function_exists( 'is_product_taxonomy' ) && ! is_product_taxonomy() ) {
			return __( 'Katalog narzędzi CNC', 'mnsk7-storefront' );
		}
		return $title;
	},
	16
);
add_filter( 'woocommerce_taxonomy_archive_description_raw', 'mnsk7_strip_wpf_filters_from_text', 5 );

/* PLP-01: opis strony Sklep — usuń shortcode [wpf-filters] z treści (WooCommerce wyświetla post_content bez filtra) */
add_action( 'init', function () {
	remove_action( 'woocommerce_archive_description', 'woocommerce_product_archive_description', 10 );
	add_action( 'woocommerce_archive_description', 'mnsk7_shop_archive_description_stripped', 10 );
}, 20 );
function mnsk7_shop_archive_description_stripped() {
	if ( is_search() ) {
		return;
	}
	if ( ! is_post_type_archive( 'product' ) || ! in_array( absint( get_query_var( 'paged' ) ), array( 0, 1 ), true ) ) {
		return;
	}
	$shop_page = get_post( wc_get_page_id( 'shop' ) );
	if ( ! $shop_page || empty( $shop_page->post_content ) ) {
		return;
	}
	$desc = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $shop_page->post_content ) : $shop_page->post_content;
	$desc = preg_replace( '/\[wpf[-_]filters[^\]]*\]/i', '', (string) $desc );
	// Usuń powtarzalny nagłówek marketingowy (zostaje reszta intro w treści strony Sklep).
	$desc = preg_replace( '/<[^>]+>\s*KATALOG\s+(?:FREZ[ÓO]W(?:\s+I\s+NARZĘDZI)?|NARZĘDZI)\s+CNC\s*<\/[^>]+>/iu', '', (string) $desc );
	$desc = preg_replace( '/^\s*KATALOG\s+(?:FREZ[ÓO]W(?:\s+I\s+NARZĘDZI)?|NARZĘDZI)\s+CNC\s*$/imu', '', (string) $desc );
	$desc = trim( (string) $desc );
	if ( $desc === '' ) {
		return;
	}
	$allowed_html = wp_kses_allowed_html( 'post' );
	$description  = wc_format_content( wp_kses( $desc, $allowed_html ) );
	if ( $description ) {
		echo '<div class="page-description">' . $description . '</div>'; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	}
}

/* Nie pokazuj zdjęcia kategorii u góry archiwum (kwadrat z lewej) — Storefront/snippety */
add_action( 'init', function () {
	remove_action( 'storefront_before_content', 'woocommerce_category_image', 2 );
	remove_action( 'woocommerce_archive_description', 'woocommerce_category_image', 15 );
}, 20 );

/* PLP-02: breadcrumbs na archive — wyświetlane w woocommerce/archive-product.php (żeby nic nie nadpisywało). Tu tylko usuwamy domyślne WC z before_main_content, żeby nie duplikować. */
add_action( 'woocommerce_before_main_content', function () {
	$is_plp = function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp();
	$is_product_search = is_search() && get_query_var( 'post_type' ) === 'product';
	if ( $is_plp || $is_product_search ) {
		remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	}
}, 5 );

/**
 * Krytyczny stan layoutu: mnsk7-has-promo i mnsk7-cookie-bar-visible w PHP (nie tylko JS).
 * First paint: odступ #page i layout nie zależą od wykonania JS. JS nadal usuwa klasy po zamknięciu/dismiss.
 */
add_filter( 'body_class', function ( $classes ) {
	$promo_text = apply_filters( 'mnsk7_header_promo_text', '' );
	if ( $promo_text !== '' && ! in_array( 'mnsk7-has-promo', $classes, true ) ) {
		$classes[] = 'mnsk7-has-promo';
	}
	// Cookie bar visible: źródło prawdy po stronie serwera — cookie. JS przy accept/reject ustawia cookie, żeby kolejny request widział stan.
	if ( apply_filters( 'mnsk7_show_cookie_bar', true ) ) {
		$consent = isset( $_COOKIE['mnsk7_cookie_consent'] ) ? sanitize_text_field( wp_unslash( $_COOKIE['mnsk7_cookie_consent'] ) ) : '';
		if ( $consent !== 'accept' && $consent !== 'reject' && ! in_array( 'mnsk7-cookie-bar-visible', $classes, true ) ) {
			$classes[] = 'mnsk7-cookie-bar-visible';
		}
	}
	return $classes;
}, 5 );

/**
 * Archive (sklep/kategoria): klasa mnsk7-archive dla critical CSS — kompaktowy promo bar, żeby LCP mógł przejąć pierwszy blok produktów.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( function_exists( 'is_shop' ) && ( is_shop() || is_product_category() || is_product_tag() ) ) {
		$classes[] = 'mnsk7-archive';
	}
	return $classes;
}, 6 );

/**
 * PLP + filter_*: jedna struktura body_class dla archiwum — niezależnie od ?filter_*.
 * Zapobiega przełączeniu layoutu/headera gdy pluginy zmieniają klasy przy "filter request".
 * Krytyczne klasy dla 24-plp-table.css: post-type-archive-product, tax-product_cat, tax-product_tag.
 * REQUEST_URI fallback: gdy plugin zmienia main query (np. ?filter_*), get_queried_object() bywa pusty —
 * wtedy tax-* dopisywane po ścieżce. Pełna determinacja wymagałaby poprawnego main query po stronie Woo/pluginu.
 */
add_filter( 'body_class', function ( $classes ) {
	if ( ! function_exists( 'mnsk7_is_plp' ) || ! mnsk7_is_plp() ) {
		return $classes;
	}
	$ensure = array( 'woocommerce', 'woocommerce-page', 'post-type-archive', 'post-type-archive-product' );
	$obj = get_queried_object();
	if ( $obj instanceof WP_Term && isset( $obj->taxonomy ) ) {
		if ( in_array( $obj->taxonomy, array( 'product_cat', 'product_tag' ), true ) ) {
			$ensure[] = 'tax-' . $obj->taxonomy;
		}
	}
	// Fallback tylko wewnątrz PLP: gdy query zmieniony (filter_*), get_queried_object() pusty — dopisz tax-* po ścieżce URL.
	if ( ! $obj ) {
		$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		$path = trim( (string) wp_parse_url( 'http://dummy' . $req_uri, PHP_URL_PATH ), '/' );
		$segments = array_filter( explode( '/', $path ), 'strlen' );
		if ( taxonomy_exists( 'product_cat' ) ) {
			$tax = get_taxonomy( 'product_cat' );
			$cat_base = ( $tax && ! empty( $tax->rewrite['slug'] ) ) ? $tax->rewrite['slug'] : 'product-category';
			if ( isset( $segments[0] ) && $segments[0] === $cat_base ) {
				$ensure[] = 'tax-product_cat';
			}
		}
		if ( taxonomy_exists( 'product_tag' ) ) {
			$tax = get_taxonomy( 'product_tag' );
			$tag_base = ( $tax && ! empty( $tax->rewrite['slug'] ) ) ? $tax->rewrite['slug'] : 'product-tag';
			if ( isset( $segments[0] ) && $segments[0] === $tag_base ) {
				$ensure[] = 'tax-product_tag';
			}
		}
	}
	foreach ( $ensure as $class ) {
		if ( ! in_array( $class, $classes, true ) ) {
			$classes[] = $class;
		}
	}
	return $classes;
}, 999 );

/**
 * PLP + filter_*: wymuś szablon archiwum produktów gdy ścieżka URL to sklep/kategoria/tag.
 * Zapobiega załadowaniu index.php (parent) gdy plugin zmienił main query — ten sam header i layout.
 */
add_filter( 'template_include', function ( $template ) {
	if ( ! function_exists( 'mnsk7_is_plp' ) || ! mnsk7_is_plp() ) {
		return $template;
	}
	if ( $template && ( strpos( $template, 'archive-product' ) !== false ) ) {
		return $template;
	}
	$child_archive = get_stylesheet_directory() . '/woocommerce/archive-product.php';
	if ( is_readable( $child_archive ) ) {
		return $child_archive;
	}
	$parent_archive = get_template_directory() . '/woocommerce/archive-product.php';
	if ( is_readable( $parent_archive ) ) {
		return $parent_archive;
	}
	if ( function_exists( 'wc_locate_template' ) ) {
		$found = wc_locate_template( 'archive-product.php' );
		if ( $found !== '' ) {
			return $found;
		}
	}
	return $template;
}, 5 );

/**
 * PLP cache: Vary: User-Agent na archiwum sklepu/kategorii/tagu (CDN/cache nie serwuje desktop użytkownikom mobile).
 *
 * NIE ustawiać Cache-Control: no-cache dla URL z ?filter_* — to powodowało divergent final DOM:
 * z no-cache odpowiedź nie trafia do cache, plugin (WP Rocket itp.) nie przetwarza HTML (minify, lazy load),
 * więc strona z filtrem miała "raw" output, a bez filtra — przetworzony; header/footer wyglądały inaczej.
 * Po deployu należy czyścić pełny cache strony, żeby wszystkie URL (w tym z filter_*) dostały świeżą wersję.
 */
add_action( 'send_headers', function () {
	if ( ! headers_sent() ) {
		if ( function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp() ) {
			header( 'Vary: User-Agent', false );
		}
	}
}, 5 );

add_action( 'template_redirect', function () {
	if ( headers_sent() ) {
		return;
	}

	$should_store = ( function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp() )
		|| ( is_search() && get_query_var( 'post_type' ) === 'product' );
	if ( ! $should_store ) {
		return;
	}

	$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( $req_uri === '' ) {
		return;
	}

	$url = esc_url_raw( home_url( $req_uri ) );
	if ( ! $url || ! function_exists( 'mnsk7_is_catalog_back_url' ) || ! mnsk7_is_catalog_back_url( $url ) ) {
		return;
	}
	if ( function_exists( 'mnsk7_plp_anchor_results' ) ) {
		$url = mnsk7_plp_anchor_results( $url );
	}

	setcookie( 'mnsk7_catalog_back', $url, time() + HOUR_IN_SECONDS, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
}, 5 );

add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
	if ( ! is_array( $crumbs ) ) {
		return $crumbs;
	}

	if ( is_singular( 'product' ) ) {
		$product_id = get_queried_object_id();
		$product    = $product_id ? wc_get_product( $product_id ) : null;
		if ( $product && is_a( $product, 'WC_Product' ) ) {
			$shop_url         = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/sklep/' );
			$shop_name        = function_exists( 'wc_get_page_id' ) ? get_the_title( wc_get_page_id( 'shop' ) ) : __( 'Sklep', 'mnsk7-storefront' );
			$catalog_back_url = function_exists( 'mnsk7_get_catalog_back_url' ) ? mnsk7_get_catalog_back_url() : '';
			$entry_url        = $catalog_back_url ? $catalog_back_url : $shop_url;
			$entry_name       = $shop_name ? $shop_name : __( 'Sklep', 'mnsk7-storefront' );
			if ( $catalog_back_url ) {
				$has_filters = ( strpos( $catalog_back_url, 'filter_' ) !== false ) || ( strpos( $catalog_back_url, '?s=' ) !== false );
				$entry_name  = $has_filters ? __( 'Wyniki filtrowania', 'mnsk7-storefront' ) : __( 'Wyniki', 'mnsk7-storefront' );
			}
			$crumbs    = array(
				array( $entry_name, $entry_url ),
			);

			$terms = wc_get_product_terms( $product_id, 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) );
			if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
				$main_term = apply_filters( 'woocommerce_breadcrumb_main_term', $terms[0], $terms );
				if ( $main_term && ! is_wp_error( $main_term ) ) {
					$ancestors = array_reverse( array_map( 'intval', get_ancestors( $main_term->term_id, 'product_cat' ) ) );
					foreach ( $ancestors as $ancestor_id ) {
						$ancestor = get_term( $ancestor_id, 'product_cat' );
						if ( ! $ancestor || is_wp_error( $ancestor ) ) {
							continue;
						}
						$link = get_term_link( $ancestor );
						if ( ! is_wp_error( $link ) ) {
							$crumbs[] = array( $ancestor->name, $link );
						}
					}
					$term_link = get_term_link( $main_term );
					if ( ! is_wp_error( $term_link ) ) {
						$crumbs[] = array( $main_term->name, $term_link );
					}
				}
			}

			if ( count( $crumbs ) > 1 ) {
				$product_label = mnsk7_normalize_breadcrumb_label( $product->get_name() );
				$last_index    = count( $crumbs ) - 1;
				$last_label    = mnsk7_normalize_breadcrumb_label( $crumbs[ $last_index ][0] ?? '' );
				if ( $last_label !== '' && $product_label !== '' && 0 === strpos( $product_label, $last_label ) ) {
					array_pop( $crumbs );
				}
			}

		}
	}

	if ( function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp() && ! empty( $_GET ) && count( $crumbs ) > 0 ) {
		$last_idx = count( $crumbs ) - 1;
		if ( isset( $crumbs[ $last_idx ][1] ) ) {
			$crumbs[ $last_idx ][1] = '';
		}
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
	if ( function_exists( 'mnsk7_get_catalog_archive_seo_title' ) ) {
		$catalog_title = mnsk7_get_catalog_archive_seo_title();
		if ( $catalog_title !== '' ) {
			$parts['title'] = $catalog_title;
			return $parts;
		}
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() && ! empty( $parts['title'] ) && is_string( $parts['title'] ) ) {
		$parts['title'] = mnsk7_strip_wpf_filters_from_text( $parts['title'] );
	}
	return $parts;
}, 5 );

/* PLP-04: title zakładki przy filtracji GET ?product_cat= / ?product_tag= (gdy is_shop() ale wybrana kategoria/tag) */
add_filter( 'document_title_parts', function ( $parts ) {
	if ( ! function_exists( 'is_shop' ) || ! is_shop() ) {
		return $parts;
	}
	if ( function_exists( 'mnsk7_get_catalog_archive_seo_title' ) ) {
		$catalog_title = mnsk7_get_catalog_archive_seo_title();
		if ( $catalog_title !== '' ) {
			$parts['title'] = $catalog_title;
			return $parts;
		}
	}
	$slug = null;
	$tax  = null;
	if ( ! empty( $_GET['product_cat'] ) ) {
		$slug = sanitize_text_field( wp_unslash( $_GET['product_cat'] ) );
		$tax  = 'product_cat';
	} elseif ( ! empty( $_GET['product_tag'] ) ) {
		$slug = sanitize_text_field( wp_unslash( $_GET['product_tag'] ) );
		$tax  = 'product_tag';
	}
	if ( ! $slug || ! $tax || ! taxonomy_exists( $tax ) ) {
		return $parts;
	}
	$term = get_term_by( 'slug', $slug, $tax );
	if ( ! $term || is_wp_error( $term ) ) {
		return $parts;
	}
	$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
	$parts['title'] = $name;
	return $parts;
}, 8 );

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
		$name  = $attr->get_name();
		$slug  = str_replace( 'pa_', '', $name );
		$label = function_exists( 'mnsk7_attribute_label_pl' ) ? mnsk7_attribute_label_pl( $slug ) : '';
		if ( $label === '' && function_exists( 'wc_attribute_label' ) ) {
			$label = wc_attribute_label( $name );
		}
		if ( $label === '' ) {
			$label = $slug;
		}
		$value = $product->get_attribute( $name );
		if ( $value === '' ) {
			continue;
		}
		echo '<tr><th>' . esc_html( $label ) . '</th><td>' . wp_kses_post( $value ) . '</td></tr>';
	}
	echo '</tbody></table></div>';
}, 21 );

add_action( 'woocommerce_single_product_summary', function () {
	global $product;
	if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$short_description = trim( (string) $product->get_short_description() );
	if ( $short_description === '' ) {
		return;
	}
	echo '<div class="mnsk7-pdp-description-intro">';
	echo wp_kses_post( wpautop( $short_description ) );
	echo '</div>';
}, 19 );

/** Trust badges HTML (PDP i PLP — wspólna treść: dostawa, faktura, zwroty) */
function mnsk7_render_trust_badges( $wrapper_class = 'mnsk7-pdp-trust' ) {
	$eta_label = function_exists( 'mnsk7_delivery_eta_badge_label' ) ? mnsk7_delivery_eta_badge_label() : __( 'Dostawa jutro', 'mnsk7-storefront' );
	$eta_label = preg_replace( '/\s*[-–—]+\s*$/u', '', (string) $eta_label );
	$item_class = $wrapper_class . '__item';
	echo '<div class="' . esc_attr( $wrapper_class ) . '" role="list">';
	echo '<span class="' . esc_attr( $item_class ) . '">' . esc_html( $eta_label ) . '</span>';
	echo '<span class="' . esc_attr( $item_class ) . '">' . esc_html__( 'Faktura VAT', 'mnsk7-storefront' ) . '</span>';
	echo '<span class="' . esc_attr( $item_class ) . '">' . esc_html__( 'Darmowa dostawa od 300 zł', 'mnsk7-storefront' ) . '</span>';
	echo '<span class="' . esc_attr( $item_class ) . '">' . esc_html__( 'Zwroty 30 dni', 'mnsk7-storefront' ) . '</span>';
	echo '</div>';
}

/**
 * Product imagery hardening:
 * - fallback alt text for Woo thumbnails when media alt is empty/hash-like,
 * - disable lazy placeholders for Woo thumbnails on PLP/PDP to avoid gray boxes.
 */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( is_admin() || ! is_array( $attr ) ) {
		return $attr;
	}

	if ( ! function_exists( 'is_product' ) || ! function_exists( 'is_shop' ) || ! function_exists( 'is_product_taxonomy' ) ) {
		return $attr;
	}

	$is_woo_catalog_context = is_product() || is_shop() || is_product_taxonomy();
	if ( ! $is_woo_catalog_context ) {
		return $attr;
	}

	$class = isset( $attr['class'] ) ? (string) $attr['class'] : '';
	$is_loop_thumb = strpos( $class, 'attachment-woocommerce_thumbnail' ) !== false;
	if ( ! $is_loop_thumb ) {
		return $attr;
	}

	$alt = isset( $attr['alt'] ) ? trim( (string) $attr['alt'] ) : '';
	$needs_alt_fallback = ( $alt === '' ) || (bool) preg_match( '/^[a-f0-9]{20,}$/i', $alt );
	if ( $needs_alt_fallback ) {
		$product_title = '';
		global $product;
		if ( $product instanceof WC_Product ) {
			$product_title = (string) $product->get_name();
		}
		if ( $product_title === '' ) {
			$post_id = get_the_ID();
			if ( $post_id && get_post_type( $post_id ) === 'product' ) {
				$product_title = (string) get_the_title( $post_id );
			}
		}
		if ( $product_title !== '' ) {
			$attr['alt'] = $product_title;
		}
	}

	// Perf: eager only for above-the-fold thumbnails on PLP (first 4). Everything else: lazy/auto.
	$is_plp = function_exists( 'is_shop' ) && ( is_shop() || is_product_taxonomy() );
	if ( $is_plp && function_exists( 'wc_get_loop_prop' ) ) {
		$current = (int) wc_get_loop_prop( 'current' );
		if ( $current > 0 && $current <= 4 ) {
			$attr['loading']       = 'eager';
			$attr['fetchpriority'] = 'high';
		} else {
			$attr['loading']       = 'lazy';
			$attr['fetchpriority'] = 'auto';
		}
	} else {
		$attr['loading']       = 'lazy';
		$attr['fetchpriority'] = 'auto';
	}
	$attr['data-no-lazy'] = '1';

	return $attr;
}, 20, 2 );

/* 10. PDP — trust strip pod CTA (fallback gdy brak mu-plugina) */
add_action( 'woocommerce_single_product_summary', function () {
	if ( function_exists( 'mnsk7_single_product_trust_badges' ) ) {
		return;
	}
	mnsk7_render_trust_badges( 'mnsk7-pdp-trust' );
}, 35 );

/* 11. Instagram shortcode — oficjalny embed (jak na alesyatakun.by: blockquote + embed.js) */
/* WP Rocket Delay JS: embed musi się załadować od razu, inaczej iframe nie powstanie. */
add_filter( 'rocket_delay_js_exclusions', function ( $exclusions ) {
	if ( ! is_array( $exclusions ) ) {
		$exclusions = array();
	}
	// Theme inline scripts must run immediately (mobile Safari + bfcache stability).
	$exclusions[] = 'mnsk7-';
	$exclusions[] = 'mnsk7IsArchive';
	$exclusions[] = 'runCritical';
	$exclusions[] = 'mnsk7-pdp-sticky-cta';
	// Exact markers that exist in our inline script bodies.
	$exclusions[] = 'checkoutUrl';
	$exclusions[] = 'initCatalogChipsToggles';
	$exclusions[] = 'mnsk7-plp-chips-toggle';
	// WooCommerce: variable product add-to-cart relies on variation JS to enable the submit button.
	// If delayed, mobile PDP click path can fail (button remains disabled / no variation_id set).
	$exclusions[] = 'add-to-cart-variation';
	$exclusions[] = 'wc-add-to-cart-variation';
	$exclusions[] = 'wc-add-to-cart';
	$exclusions[] = 'embed.js';
	$exclusions[] = 'instagram.com';
	return $exclusions;
} );
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( $handle === 'mnsk7-instagram-embed' ) {
		// WP Rocket: nie opóźniaj embed.js (Instagram inicjalizuje blockquote po załadowaniu).
		$tag = str_replace( ' src=', ' nowprocket src=', $tag );
	}
	return $tag;
}, 10, 3 );

/** Rejestracja embed.js — enqueue w shortcode (przed wp_footer), patrz alesyatakun.by. */
add_action( 'wp_enqueue_scripts', function () {
	wp_register_script(
		'mnsk7-instagram-embed',
		'https://www.instagram.com/embed.js',
		array(),
		null,
		true
	);
	if ( function_exists( 'wp_script_add_data' ) ) {
		wp_script_add_data( 'mnsk7-instagram-embed', 'strategy', 'async' );
	}
}, 5 );

/**
 * Instagram og:image z meta często wskazuje na URL z centralnym kadrem kwadratowym (parametr stp=c….a_…),
 * więc napisy na szerokich grafikach są obcinane niezależnie od CSS. W takim przypadku lepszy jest embed.
 *
 * @param string $url URL obrazka (og:image).
 * @return bool True — nie używaj jako miniatury, użyj iframe.
 */
function mnsk7_instagram_og_preview_is_square_cdn_crop( $url ) {
	if ( ! is_string( $url ) || $url === '' ) {
		return false;
	}
	$u = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	if ( stripos( $u, 'static.cdninstagram.com/rsrc.php' ) !== false ) {
		return true;
	}
	return (bool) preg_match( '/[?&]stp=c[0-9]+\.[0-9]+\.[0-9]+\.[0-9]+a/i', $u );
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_instagram_feed', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit'  => 6,
			'title'  => 'Instagram @mnsk7tools',
			'type'   => 'profile',
			'urls'   => '',
			'images' => '',
			// embedjs = blockquote + embed.js (jak alesyatakun.by, pełna kompozycja posta). thumbs = lekkie miniatury og:iframe (legacy).
			'render' => 'embedjs',
		), $atts, 'mnsk7_instagram_feed' );
		$profile = defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/';
		$handle  = preg_replace( '#^https?://(www\.)?instagram\.com/#', '', untrailingslashit( $profile ) );
		$handle  = $handle !== '' ? $handle : 'mnsk7tools';
		$embed_profile_url = 'https://www.instagram.com/' . $handle . '/embed/';

		if ( $atts['type'] === 'profile' ) {
			$out  = '<div class="mnsk7-instagram-feed mnsk7-instagram-feed--profile">';
			$out .= '<div class="mnsk7-instagram-profile-embed">';
			$out .= '<iframe src="' . esc_url( $embed_profile_url ) . '" title="' . esc_attr( $atts['title'] ) . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin"></iframe>';
			$out .= '</div>';
			$out .= '</div>';
			return $out;
		}

		$limit = max( 1, min( 12, (int) $atts['limit'] ) );
		$raw = array();
		if ( is_string( $atts['urls'] ) && trim( $atts['urls'] ) !== '' ) {
			$raw = preg_split( '/[\s,]+/', trim( $atts['urls'] ) );
		}
		if ( empty( $raw ) ) {
			$raw = get_option( 'mnsk7_instagram_post_urls', array() );
		}
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		if ( empty( $raw ) ) {
			$raw = array(
				'https://www.instagram.com/mnsk7tools/p/DC12yM_NZIP/',
				'https://www.instagram.com/mnsk7tools/p/DCTybzqtxEi/',
				'https://www.instagram.com/mnsk7tools/p/DCeUnS8Ismh/',
				'https://www.instagram.com/mnsk7tools/p/DCzOqKqtjUe/',
				'https://www.instagram.com/mnsk7tools/p/DC9J3JjNobj/',
			);
		}
		$urls       = array();
		$image_urls = array();
		if ( is_string( $atts['images'] ) && trim( $atts['images'] ) !== '' ) {
			$decoded = html_entity_decode( trim( $atts['images'] ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
			$image_urls = preg_split( '/\s*,\s*/', $decoded );
		}
		foreach ( array_slice( $raw, 0, $limit ) as $entry ) {
			$url = is_array( $entry ) ? ( isset( $entry['url'] ) ? $entry['url'] : '' ) : $entry;
			$url = esc_url_raw( $url );
			if ( $url !== '' ) {
				if ( preg_match( '#instagram\.com/reel/([A-Za-z0-9_-]+)/?#', $url, $m ) ) {
					$url = 'https://www.instagram.com/reel/' . $m[1] . '/';
				} elseif ( preg_match( '#instagram\.com/p/([A-Za-z0-9_-]+)/?#', $url, $m ) ) {
					$url = 'https://www.instagram.com/p/' . $m[1] . '/';
				}
				$urls[] = $url;
			}
		}

		$render = isset( $atts['render'] ) ? sanitize_key( (string) $atts['render'] ) : 'embedjs';
		if ( ! in_array( $render, array( 'embedjs', 'thumbs' ), true ) ) {
			$render = 'embedjs';
		}

		$out = '<div class="mnsk7-instagram-feed mnsk7-instagram-feed--posts mnsk7-instagram-feed--render-' . esc_attr( $render ) . '">';
		if ( ! empty( $urls ) ) {
			if ( $render === 'embedjs' ) {
				wp_enqueue_script( 'mnsk7-instagram-embed' );
			}
			$n_posts = count( $urls );
			$cols    = ' mnsk7-instagram-feed__posts--cols-' . (string) min( 4, max( 1, $n_posts ) );
			$out    .= '<div class="mnsk7-instagram-feed__posts' . esc_attr( $cols ) . '" role="region" aria-label="' . esc_attr__( 'Posty z Instagrama', 'mnsk7-storefront' ) . '">';
			foreach ( $urls as $index => $url ) {
				if ( $render === 'embedjs' ) {
					$out .= '<div class="mnsk7-instagram-feed__post mnsk7-instagram-feed__post--blockquote">';
					$out .= '<blockquote class="instagram-media" data-instgrm-permalink="' . esc_url( $url ) . '" data-instgrm-version="14"></blockquote>';
					$out .= '</div>';
					continue;
				}
				$image_url = '';
				if ( isset( $image_urls[ $index ] ) && is_string( $image_urls[ $index ] ) ) {
					$image_url = esc_url_raw( trim( html_entity_decode( $image_urls[ $index ], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) ) );
				}
				if ( $image_url === '' && function_exists( 'mnsk7_instagram_og_image_for_url' ) ) {
					$image_url = mnsk7_instagram_og_image_for_url( $url );
					if ( $image_url !== '' && mnsk7_instagram_og_preview_is_square_cdn_crop( $image_url ) ) {
						$image_url = '';
					}
				}
				$has_thumb = $image_url !== '';
				$out      .= '<div class="mnsk7-instagram-feed__post' . ( $has_thumb ? ' mnsk7-instagram-feed__post--thumb' : ' mnsk7-instagram-feed__post--embed' ) . '">';
				if ( $has_thumb ) {
					$out .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__post-link mnsk7-instagram-feed__post-link--stacked">';
					$out .= '<span class="mnsk7-instagram-feed__post-thumbwrap">';
					$out .= '<img src="' . esc_url( $image_url ) . '" alt="' . esc_attr__( 'Post z Instagrama MNSK7', 'mnsk7-storefront' ) . '" loading="lazy" decoding="async" class="mnsk7-instagram-feed__post-image" />';
					$out .= '</span>';
					$out .= '<span class="mnsk7-instagram-feed__post-cta">' . esc_html__( 'Otwórz w Instagramie', 'mnsk7-storefront' ) . '</span>';
					$out .= '</a>';
				} else {
					$embed_url = trailingslashit( $url ) . 'embed/';
					$out      .= '<iframe class="mnsk7-instagram-feed__iframe" src="' . esc_url( $embed_url ) . '" title="' . esc_attr__( 'Post z Instagrama', 'mnsk7-storefront' ) . '" loading="lazy" referrerpolicy="strict-origin-when-cross-origin" allowtransparency="true"></iframe>';
				}
				$out .= '</div>';
			}
			$out .= '</div>';
		}
		$out .= '<div class="mnsk7-instagram-feed__profile">';
		$out .= '<span class="mnsk7-instagram-feed__profile-icon" aria-hidden="true"></span>';
		$out .= '<span class="mnsk7-instagram-feed__profile-handle">@' . esc_html( $handle ) . '</span>';
		$out .= '<a href="' . esc_url( $profile ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__profile-btn">' . esc_html__( 'Zobacz profil', 'mnsk7-storefront' ) . '</a>';
		$out .= '</div>';
		$out .= '</div>';
		return $out;
	} );
}, 99 );

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
/**
 * Product attribute taxonomies (pa_*) registered in WooCommerce. Used for PLP filter chips and query.
 *
 * @return string[] List of taxonomy names (e.g. pa_srednica, pa_dlugosc-robocza-h).
 */
function mnsk7_get_product_attribute_taxonomy_names() {
	if ( ! function_exists( 'wc_get_attribute_taxonomies' ) ) {
		return array();
	}
	$attrs = wc_get_attribute_taxonomies();
	if ( ! is_array( $attrs ) ) {
		return array();
	}
	$list = array();
	foreach ( $attrs as $a ) {
		$name = isset( $a->attribute_name ) ? $a->attribute_name : null;
		if ( $name !== null && $name !== '' ) {
			$tax = 'pa_' . $name;
			if ( taxonomy_exists( $tax ) ) {
				$list[] = $tax;
			}
		}
	}
	return $list;
}

/**
 * Canonical PLP attribute filters — tylko średnica trzpienia (razem z Rodzaje frezów + Zastosowanie i materiały w archive-product.php).
 * Pierwszy pasujący slug z listy (fi / srednica-trzpienia / …) — jedna taksonomia na archiwum.
 *
 * @return array<string,string> Map taxonomy => customer-facing label.
 */
function mnsk7_get_plp_attribute_filter_taxonomies() {
	$available = array_flip( function_exists( 'mnsk7_get_product_attribute_taxonomy_names' ) ? mnsk7_get_product_attribute_taxonomy_names() : array() );
	$groups    = array(
		array(
			'label' => __( 'Średnica trzpienia', 'mnsk7-storefront' ),
			'slugs' => array( 'fi', 'srednica-trzpienia', 'srednica_trzpienia', 'wymiary-trzpienia' ),
		),
	);
	$resolved = array();
	foreach ( $groups as $group ) {
		foreach ( $group['slugs'] as $slug ) {
			$taxonomy = 'pa_' . $slug;
			if ( isset( $available[ $taxonomy ] ) ) {
				$resolved[ $taxonomy ] = $group['label'];
				break;
			}
		}
	}
	return $resolved;
}

/**
 * Human-readable Polish labels for product attribute slugs (for chips and filters).
 * Keys = attribute slug (as in pa_*), values = label for customer.
 *
 * @param string $attr_name Attribute slug without 'pa_' (e.g. srednica, dlugosc-robocza-h).
 * @return string Label to show, or empty to use WooCommerce default.
 */
function mnsk7_attribute_label_pl( $attr_name ) {
	$labels = array(
		'srednica'                => __( 'Średnica robocza', 'mnsk7-storefront' ),
		'srednica-trzpienia'      => __( 'Trzpień', 'mnsk7-storefront' ),
		'wymiary-trzpienia'       => __( 'Trzpień', 'mnsk7-storefront' ),
		'dlugosc-calkowita'       => __( 'Długość całkowita', 'mnsk7-storefront' ),
		'dlugosc-calkowita-l'     => __( 'Długość całkowita', 'mnsk7-storefront' ),
		'dlugosc-czesci-roboczej' => __( 'Długość robocza', 'mnsk7-storefront' ),
		'dlugosc-robocza'         => __( 'Długość robocza', 'mnsk7-storefront' ),
		'dlugosc-robocza-h'       => __( 'Długość robocza', 'mnsk7-storefront' ),
		'fi'                      => __( 'Średnica robocza', 'mnsk7-storefront' ),
		'kat-skosu'               => __( 'Kąt skosu', 'mnsk7-storefront' ),
		'kat_skosu'               => __( 'Kąt skosu', 'mnsk7-storefront' ),
		'r'                       => __( 'Promień R', 'mnsk7-storefront' ),
		'typ-pilnika'             => __( 'Typ pilnika', 'mnsk7-storefront' ),
		'liczba-zebow'            => __( 'Liczba ostrzy', 'mnsk7-storefront' ),
		'zastosowanie'            => __( 'Zastosowanie', 'mnsk7-storefront' ),
		'material'                => __( 'Materiał', 'mnsk7-storefront' ),
		'kompatybilnosc'          => __( 'Kompatybilność', 'mnsk7-storefront' ),
	);
	$key = strtolower( trim( (string) $attr_name ) );
	if ( isset( $labels[ $key ] ) ) {
		return $labels[ $key ];
	}
	// WooCommerce może zwracać slug z podkreślnikami (np. kat_skosu) — sprawdź wariant z myślnikami i na odwrót.
	$key_alt = str_replace( '_', '-', $key );
	if ( isset( $labels[ $key_alt ] ) ) {
		return $labels[ $key_alt ];
	}
	$key_alt = str_replace( '-', '_', $key );
	return isset( $labels[ $key_alt ] ) ? $labels[ $key_alt ] : '';
}

/**
 * Canonical frontend labels for category/tag terms with legacy naming noise.
 *
 * @param string $name Source label.
 * @return string
 */
function mnsk7_normalize_catalog_term_label( $name ) {
	$normalized = trim( (string) $name );
	if ( $normalized === '' ) {
		return $normalized;
	}
	$map = array(
		'Pilnik oborotowy' => 'Pilnik obrotowy',
		'Pilniki oborotowe' => 'Pilniki obrotowe',
	);
	$normalized = isset( $map[ $normalized ] ) ? $map[ $normalized ] : $normalized;
	$first_char = function_exists( 'mb_substr' ) ? mb_substr( $normalized, 0, 1, 'UTF-8' ) : substr( $normalized, 0, 1 );
	$rest       = function_exists( 'mb_substr' ) ? mb_substr( $normalized, 1, null, 'UTF-8' ) : substr( $normalized, 1 );
	if ( $first_char !== '' ) {
		$first_char = function_exists( 'mb_strtoupper' ) ? mb_strtoupper( $first_char, 'UTF-8' ) : strtoupper( $first_char );
		$normalized = $first_char . $rest;
	}
	return $normalized;
}

add_filter( 'single_term_title', function ( $title ) {
	return function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $title ) : $title;
}, 20 );

add_filter( 'get_the_archive_title', function ( $title ) {
	if ( is_product_taxonomy() && function_exists( 'mnsk7_normalize_catalog_term_label' ) ) {
		$title = str_replace( 'Pilnik oborotowy', mnsk7_normalize_catalog_term_label( 'Pilnik oborotowy' ), (string) $title );
		$title = str_replace( 'Pilniki oborotowe', mnsk7_normalize_catalog_term_label( 'Pilniki oborotowe' ), (string) $title );
	}
	return $title;
}, 20 );

/**
 * All filter_* query param names for product attributes (for clearing filters / detecting active filter).
 *
 * @return string[]
 */
function mnsk7_get_all_attribute_filter_param_names() {
	$taxonomies = array_keys( function_exists( 'mnsk7_get_plp_attribute_filter_taxonomies' ) ? mnsk7_get_plp_attribute_filter_taxonomies() : array() );
	$params     = array();
	foreach ( $taxonomies as $tax ) {
		$params[] = 'filter_' . str_replace( 'pa_', '', $tax );
	}
	return $params;
}

/**
 * Append #mnsk7-plp-results to URL so after filter/category/tag navigation user lands at products.
 * Used for PLP chip and filter links (reload → scroll to results).
 *
 * @param string $url Full URL (e.g. term link or add_query_arg result).
 * @return string URL with fragment (existing hash replaced).
 */
function mnsk7_plp_anchor_results( $url ) {
	$anchor = 'mnsk7-plp-results';
	$url    = (string) $url;
	$pos    = strpos( $url, '#' );
	if ( $pos !== false ) {
		$url = substr( $url, 0, $pos );
	}
	return $url . '#' . $anchor;
}

add_action( 'woocommerce_product_query', function ( $q ) {
	if ( is_admin() || ! is_object( $q ) || ! method_exists( $q, 'set' ) ) {
		return;
	}
	$attr_taxonomies = array_keys( function_exists( 'mnsk7_get_plp_attribute_filter_taxonomies' ) ? mnsk7_get_plp_attribute_filter_taxonomies() : array() );
	$tax             = $q->get( 'tax_query' );
	if ( ! is_array( $tax ) ) {
		$tax = array();
	}
	foreach ( $attr_taxonomies as $attr ) {
		$param = 'filter_' . str_replace( 'pa_', '', $attr );
		if ( empty( $_GET[ $param ] ) ) {
			continue;
		}
		$slug = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
		if ( $slug === '' ) {
			continue;
		}
		$tax[] = array(
			'taxonomy' => $attr,
			'field'    => 'slug',
			'terms'    => $slug,
		);
	}
	if ( ! empty( $tax ) ) {
		$q->set( 'tax_query', array_merge( array( 'relation' => 'AND' ), $tax ) );
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
 * Normalize displayed chip label for Polish CNC ecommerce.
 *
 * @param string $label Raw term name.
 * @param string $taxonomy Attribute taxonomy.
 * @return string
 */
function mnsk7_normalize_archive_chip_label( $label, $taxonomy = '' ) {
	$label = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( (string) $label ) : (string) $label;
	$label = html_entity_decode( $label, ENT_QUOTES, 'UTF-8' );
	$label = str_replace( "\xc2\xa0", ' ', $label );
	$label = trim( preg_replace( '/\s+/u', ' ', $label ) );
	if ( $label === '' ) {
		return '';
	}

	$label = preg_replace( '/(\d+(?:[.,]\d+)?)\s*(mm|cm|m|fi)\b/iu', '$1 $2', $label );
	$label = preg_replace( '/(\d+)\s*°/u', '$1°', $label );

	$taxonomy = (string) $taxonomy;
	if ( in_array( $taxonomy, array( 'pa_kat-skosu', 'pa_kat_skosu' ), true ) ) {
		$label = preg_replace( '/^(\d+(?:[.,]\d+)?)\s*(st|stopni|deg)?$/iu', '$1°', $label );
	}

	if ( function_exists( 'mnsk7_normalize_catalog_term_label' ) ) {
		$label = mnsk7_normalize_catalog_term_label( $label );
	}

	return trim( preg_replace( '/\s+/u', ' ', $label ) );
}

/**
 * Business priority for archive attribute filter rows (PLP — tylko średnica trzpienia).
 *
 * @param WP_Term $term Current archive term (unused; kept for API stability).
 * @return string[]
 */
function mnsk7_get_archive_filter_priority( $term ) {
	return array(
		'pa_fi',
		'pa_srednica-trzpienia',
		'pa_srednica_trzpienia',
		'pa_wymiary-trzpienia',
	);
}

/**
 * Attribute filter chips for PLP — wyłącznie Średnica trzpienia (Źródło taksonomii: mnsk7_get_plp_attribute_filter_taxonomies).
 * FB-02 (zestawy / pa_srednica): nie dotyczy — filtr średnicy roboczej nie jest już na PLP.
 * FB-03: only terms that have in-stock products in the current category are shown (no fallback to global terms).
 * Labels from WooCommerce (e.g. Średnica robocza, Dł. robocza, Dł. całkowita, Promień R).
 *
 * @return array{filters: array<array{label: string, param: string, chips: array}>, filter_params: string[]}
 */
function mnsk7_get_archive_attribute_filter_chips() {
	$empty = array( 'filters' => array(), 'filter_params' => array() );
	if ( ! is_product_taxonomy() ) {
		return $empty;
	}
	$attrs_to_try    = function_exists( 'mnsk7_get_plp_attribute_filter_taxonomies' ) ? mnsk7_get_plp_attribute_filter_taxonomies() : array();
	$term = get_queried_object();
	if ( ! $term || ! isset( $term->term_id ) ) {
		return $empty;
	}
	$priority_order = function_exists( 'mnsk7_get_archive_filter_priority' ) ? mnsk7_get_archive_filter_priority( $term ) : array();

	$product_ids = mnsk7_get_archive_product_ids_for_chips( $attrs_to_try );
	$filters     = array();
	// When archive has no (in-stock) products, don't show attribute chips — they would show global terms and lead to "Brak produktów".
	if ( empty( $product_ids ) ) {
		return array( 'filters' => array(), 'filter_params' => array() );
	}

	foreach ( $attrs_to_try as $tax => $preferred_label ) {
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$attr_name = str_replace( 'pa_', '', $tax );
		$label     = $preferred_label;
		if ( $label === '' && function_exists( 'mnsk7_attribute_label_pl' ) ) {
			$label = mnsk7_attribute_label_pl( $attr_name );
		}
		if ( $label === '' && function_exists( 'wc_attribute_label' ) ) {
			$label = wc_attribute_label( $attr_name );
		}
		if ( $label === '' ) {
			$label = $attr_name;
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
		// Only show this attribute row if there are terms in the current archive's products (no fallback to all terms).
		if ( is_wp_error( $terms ) || empty( $terms ) ) {
			continue;
		}
		$param      = 'filter_' . str_replace( 'pa_', '', $tax );
		$active_val = ! empty( $_GET[ $param ] ) ? sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) : '';
		if ( count( $terms ) < 2 && $active_val === '' ) {
			continue;
		}
		$chips = array();
		foreach ( $terms as $t ) {
			$normalized_label = function_exists( 'mnsk7_normalize_archive_chip_label' ) ? mnsk7_normalize_archive_chip_label( $t->name, $tax ) : $t->name;
			if ( $normalized_label === '' ) {
				continue;
			}
			$chips[ $t->slug ] = $normalized_label;
		}
		if ( empty( $chips ) ) {
			continue;
		}
		$filters[] = array(
			'taxonomy' => $tax,
			'label' => $label,
			'param' => $param,
			'chips' => $chips,
		);
	}
	if ( ! empty( $filters ) && ! empty( $priority_order ) ) {
		$priority_map = array_flip( $priority_order );
		usort( $filters, function ( $left, $right ) use ( $priority_map ) {
			$left_tax  = isset( $left['taxonomy'] ) ? $left['taxonomy'] : '';
			$right_tax = isset( $right['taxonomy'] ) ? $right['taxonomy'] : '';
			$left_rank = isset( $priority_map[ $left_tax ] ) ? (int) $priority_map[ $left_tax ] : 999;
			$right_rank = isset( $priority_map[ $right_tax ] ) ? (int) $priority_map[ $right_tax ] : 999;
			if ( $left_rank === $right_rank ) {
				return strcmp( (string) $left['label'], (string) $right['label'] );
			}
			return $left_rank <=> $right_rank;
		} );
	}
	$filters = array_map( function ( $filter ) {
		unset( $filter['taxonomy'] );
		return $filter;
	}, $filters );
	$filter_params = array_column( $filters, 'param' );
	return array( 'filters' => $filters, 'filter_params' => $filter_params );
}
