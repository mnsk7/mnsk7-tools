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

/**
 * Search normalization: "fi 4mm" / "4mm" → "fi 4 mm" / "4 mm" for product search (deployable in theme, no mu-plugin dependency).
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {
	if ( is_admin() || ! $query->get( 's' ) ) {
		return;
	}
	$post_type = $query->get( 'post_type' );
	if ( $post_type !== 'product' && ( ! is_array( $post_type ) || ! in_array( 'product', $post_type, true ) ) ) {
		return;
	}
	$s = $query->get( 's' );
	if ( ! is_string( $s ) || trim( $s ) === '' ) {
		return;
	}
	$normalized = preg_replace( '/(\d+(?:[.,]\d+)?)\s*(mm|cm|m\b|g\b|kg|ml)/iu', '$1 $2', $s );
	$normalized = preg_replace( '/\s+/', ' ', $normalized );
	$normalized = trim( $normalized );
	if ( $normalized !== $s ) {
		$query->set( 's', $normalized );
	}
}, 5 );

/**
 * Czy request jest z urządzenia mobilnego (user-agent). Używane do renderowania jednego layoutu PLP.
 *
 * @return bool
 */
function mnsk7_is_mobile_request() {
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
 * Jedno miejsce określania „to jest PLP” (sklep / kategoria / tag). Lazy-eval przy pierwszym wywołaniu — cache w $GLOBALS.
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
 * Ustawienie globalnego stanu „to jest PLP” na początku requestu (jeden raz).
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

/* PLP-10: przy tabeli (shop/category/tag) nie pokazuj numerów paginacji — tylko przycisk „Pokaż więcej” */
add_action( 'woocommerce_after_shop_loop', function () {
	if ( ! empty( $GLOBALS['mnsk7_plp_use_table'] ) ) {
		remove_action( 'woocommerce_after_shop_loop', 'woocommerce_pagination', 10 );
	}
}, 1 );

/**
 * PLP „Pokaż więcej”: AJAX — zwraca HTML wierszy tabeli dla następnej strony (bez przejścia na page/2).
 */
function mnsk7_plp_load_more_handler() {
	if ( ! function_exists( 'wc_get_product' ) ) {
		wp_send_json_error( array( 'message' => 'WooCommerce not active' ) );
	}
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

	// Po załadowaniu kolejnej strony: zakres 1–N z total (np. „Wyświetlanie 1–24 z 54 wyników”).
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
	return $args;
} );

/** PDP: okruszki przy tytule produktu, nie pod headerem; bez gwiazdek ratingu (zgodnie z zadaniem) */
add_action( 'wp', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_breadcrumb', 5 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_rating', 10 );
} );

/** PDP: cena + „X osób kupiło” w jednym rzędzie (otwarcie wrappera przed ceną) */
add_action( 'woocommerce_single_product_summary', function () {
	echo '<div class="mnsk7-pdp-price-row">';
}, 14 );
/** PDP: zamknięcie wrappera ceny + wyświetlenie „X osób kupiło” obok ceny */
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

/** PDP: link „Wróć do wyników wyszukiwania” gdy użytkownik przyszedł z wyszukiwania (lepsza nawigacja niż tylko kategoria) */
add_action( 'woocommerce_single_product_summary', function () {
	$referer = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
	if ( $referer === '' ) {
		return;
	}
	$ref_host = wp_parse_url( $referer, PHP_URL_HOST );
	$home_host = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
	if ( $ref_host !== $home_host ) {
		return;
	}
	if ( strpos( $referer, '?' ) === false ) {
		return;
	}
	$parsed = wp_parse_url( $referer );
	if ( empty( $parsed['query'] ) ) {
		return;
	}
	parse_str( $parsed['query'], $q );
	$is_search = ! empty( $q['s'] ) && ( empty( $q['post_type'] ) || $q['post_type'] === 'product' );
	if ( ! $is_search ) {
		return;
	}
	$search_url = home_url( '/' ) . '?s=' . rawurlencode( $q['s'] ) . '&post_type=product';
	echo '<p class="mnsk7-pdp-back-search"><a href="' . esc_url( $search_url ) . '" class="mnsk7-pdp-back-search__link">' . esc_html__( '← Wróć do wyników wyszukiwania', 'mnsk7-storefront' ) . '</a></p>';
}, 4 );

/** PDP a11y: etykieta pola Ilość bez nazwy produktu (tylko „Ilość”) */
add_filter( 'woocommerce_quantity_input_args', function ( $args, $product ) {
	if ( is_singular( 'product' ) ) {
		$args['product_name'] = '';
	}
	return $args;
}, 10, 2 );

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

/** PDP: Podobne produkty — 8 pozycji, 4 kolumny (pełny rząd bez pustego miejsca z boku) */
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 8;
	$args['columns']        = 4;
	return $args;
}, 10 );

/** 1.1 Cena w pętli (bestsellery, related, PLP): fallback gdy pusta; suffix „zł” na głównej */
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
 * Mega menu „Sklep”: nagłówki sekcji dla użytkownika (nie nazwy taksonomii Woo).
 * Domyślnie: product_cat → „Rodzaje frezów”, product_tag → „Zastosowanie i materiały”.
 * Aby zmienić: add_filter( 'mnsk7_megamenu_heading_categories', fn( $s ) => 'Twoja etykieta' );
 *              add_filter( 'mnsk7_megamenu_heading_tags', fn( $s ) => 'Twoja etykieta' );
 */

/** 4.0 UX: domyślny tekst promocyjny w headerze (darmowa dostawa) + CTA do Dostawa (audit Zad.11). Na stronie głównej bez paska — nie konkurować z hero. */
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
	// Strony z szablonem Kontakt/Dostawa — bez paska (slug może być inny na stagingu).
	if ( is_singular( 'page' ) ) {
		$template = get_page_template_slug( get_queried_object_id() );
		if ( $template === 'page-kontakt.php' || $template === 'page-dostawa.php' ) {
			return '';
		}
	}
	$dostawa_url = home_url( '/dostawa-i-platnosci/' );
	$link = '<a href="' . esc_url( $dostawa_url ) . '">' . esc_html__( 'Warunki dostawy', 'mnsk7-storefront' ) . ' &rarr;</a>';
	return sprintf(
		/* translators: 1: promo text, 2: link HTML to delivery page */
		__( 'Darmowa dostawa od 300 zł. Tylko Polska. %1$s', 'mnsk7-storefront' ),
		$link
	);
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

/** 4.2 UX: przycisk „Kontynuuj zakupy” na stronie koszyka */
add_action( 'woocommerce_before_cart', function () {
	if ( ! function_exists( 'wc_get_page_permalink' ) ) {
		return;
	}
	echo '<p class="mnsk7-cart-continue">';
	echo '<a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '" class="button mnsk7-btn-back">' . esc_html__( 'Kontynuuj zakupy', 'mnsk7-storefront' ) . '</a>';
	echo '</p>';
}, 5 );

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

/* 1. Enqueue styles — jeden plik runtime: main.css (zbudowany z parts przez scripts/build-main-css.sh).
 * Architektura: parts/ to tylko źródło do budowy; na staging/prod ładuje się wyłącznie main.css.
 * Eliminuje to podwójną strategię (parts vs main), która powodowała bug: staging mógł serwować
 * stary main.css lub rozjechane parts, a footer/header/PDP dostawały nieaktualne style.
 * Zawsze te same zasoby niezależnie od URL (cache). B1: theme ładuje się po WC (priority 20).
 */
add_action( 'wp_enqueue_scripts', function () {
	$v = defined( 'MNSK7_THEME_VERSION' ) ? MNSK7_THEME_VERSION : '3.0.9';
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
	/* Inline: tylko Instagram karta — tylko gdy na stronie jest shortcode (front page lub treść z [mnsk7_instagram_feed]). */
	$need_insta_inline = is_front_page();
	if ( ! $need_insta_inline && is_singular() ) {
		$post = get_queried_object();
		if ( $post && isset( $post->post_content ) && has_shortcode( (string) $post->post_content, 'mnsk7_instagram_feed' ) ) {
			$need_insta_inline = true;
		}
	}
	if ( $need_insta_inline ) {
		$insta_inline = '.mnsk7-instagram-feed--card{width:100%;max-width:560px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08)}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__carousel{aspect-ratio:1;overflow:hidden;background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__track{display:flex;height:100%;transition:transform .3s ease}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__slide{flex:0 0 100%;width:100%;height:100%;position:relative}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__slide .mnsk7-instagram-feed__img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__dots{display:flex;justify-content:center;gap:6px;padding:10px 0}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__dot{width:8px;height:8px;border-radius:50%;border:none;background:#c4c4c4;cursor:pointer}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__dot.is-active{background:#0d6efd;transform:scale(1.15)}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__profile{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-top:1px solid #eee}';
		wp_add_inline_style( 'mnsk7-main', $insta_inline );
	}
}, 20 );

/* Override WooCommerce clearfix: woocommerce-layout.css ładuje się PO naszej temie i ustawia .woocommerce ul.products::before{display:table}, co daje pustą pierwszą „komórkę” w gridzie. Dodajemy inline do handle WooCommerce, żeby nasze display:none było po ich regule. Również Moje konto: przyciski + padding (wygrywamy z WC). */
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
	if ( is_array( $cached ) && isset( $cached['cats'] ) && isset( $cached['tags'] ) ) {
		return $cached;
	}
	$top_cats = array();
	$top_tags = array();
	if ( taxonomy_exists( 'product_cat' ) ) {
		$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 16, 'orderby' => 'name' ) );
		$top_cats = is_wp_error( $top_cats ) ? array() : $top_cats;
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
	set_transient( 'mnsk7_megamenu_terms', array( 'cats' => $top_cats, 'tags' => $top_tags ), 12 * HOUR_IN_SECONDS );
	return array( 'cats' => $top_cats, 'tags' => $top_tags );
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
	ob_start();
	?>
	<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-contents mnsk7-header__cart-trigger" aria-label="<?php esc_attr_e( 'Koszyk', 'mnsk7-storefront' ); ?>">
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

		function closeMenu() {
			if (!nav) return;
			nav.classList.remove('is-open');
			if (menuToggle) {
				menuToggle.setAttribute('aria-expanded', 'false');
				var menuOpenLabel = menuToggle.getAttribute('data-open-label');
				if (menuOpenLabel) menuToggle.setAttribute('aria-label', menuOpenLabel);
			}
		}

		function setSearchOpen(open) {
			if (!searchToggle || !searchDropdown) return;
			searchDropdown.hidden = !open;
			searchToggle.setAttribute('aria-expanded', open);
			var searchCloseLabel = searchToggle.getAttribute('data-close-label');
			var searchOpenLabel = searchToggle.getAttribute('data-open-label');
			if (searchCloseLabel && searchOpenLabel) searchToggle.setAttribute('aria-label', open ? searchCloseLabel : searchOpenLabel);
			if (searchPanel) {
				if (window.innerWidth < 1025) {
					document.body.classList.toggle('mnsk7-search-open', open);
					searchPanel.hidden = !open;
					searchPanel.setAttribute('aria-hidden', open ? 'false' : 'true');
					if (open) {
						var panelInput = document.getElementById('mnsk7-header-search-panel-input');
						if (panelInput) { setTimeout(function() { panelInput.focus(); }, 50); }
					}
				} else {
					document.body.classList.remove('mnsk7-search-open');
					searchPanel.hidden = true;
					searchPanel.setAttribute('aria-hidden', 'true');
				}
			}
		}

		function closeSearch() {
			if (window.innerWidth < 1025 && document.body.classList.contains('mnsk7-search-open')) {
				setSearchOpen(false);
			} else if (window.innerWidth >= 1025 && searchDropdown && !searchDropdown.hidden) {
				searchDropdown.hidden = true;
				if (searchToggle) searchToggle.setAttribute('aria-expanded', 'false');
			}
		}

		function closeCart() {
			if (!cartWrap) return;
			cartWrap.classList.remove('is-open');
			var trigger = cartWrap.querySelector('.mnsk7-header__cart-trigger, .cart-contents');
			if (trigger) trigger.setAttribute('aria-expanded', 'false');
		}

		function closeAllMobileOverlays(except) {
			if (window.innerWidth >= 1025) return;
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
				if (window.innerWidth < 1025 && !nav.classList.contains('is-open')) {
					closeAllMobileOverlays('menu');
				}
				nav.classList.toggle('is-open');
				setMenuAria();
			});
		}
		// Mobile (≤1024): tap na parent z submenu (np. „Sklep”) rozwijá submenu; bez przejścia po URL. Capture phase + pewne wykrycie linku (tap może dać target = tekst/child).
		var menu = document.getElementById('mnsk7-primary-menu');
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
			menu.addEventListener('click', function(e) {
				var a = getLinkFromEvent(e, menu);
				if (!a || !a.href) return;
				var li = a.closest('li.menu-item-has-children');
				if (!li || !isParentItemLink(a, li)) return;
				if (window.innerWidth <= 1024) {
					// Mobile: first tap opens submenu, second tap navigates (only for parent link).
					if (!li.classList.contains('is-open')) {
						e.preventDefault();
						e.stopPropagation();
						li.classList.add('is-open');
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
					if (window.innerWidth < 1025) return;
					openTimer = setTimeout(function() { setMegamenuExpanded(true); }, HOVER_OPEN_MS);
				}
				function closeMegamenu() {
					clearTimeout(openTimer);
					closeTimer = setTimeout(function() { setMegamenuExpanded(false); }, HOVER_CLOSE_MS);
				}
				megamenuLi.addEventListener('mouseenter', openMegamenu);
				megamenuLi.addEventListener('mouseleave', closeMegamenu);
				megamenuLi.addEventListener('focusin', function() { if (window.innerWidth >= 1025) { clearTimeout(closeTimer); setMegamenuExpanded(true); } });
				megamenuLi.addEventListener('focusout', function(e) {
					if (window.innerWidth < 1025) return;
					setTimeout(function() { if (!megamenuLi.contains(document.activeElement)) setMegamenuExpanded(false); }, 0);
				});
				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && megamenuLi.classList.contains('mnsk7-megamenu-open')) {
						setMegamenuExpanded(false);
						if (megamenuLink) megamenuLink.focus();
					}
				});
			}
			// Mobile: po kliknięciu w link (nie w parent „Sklep”) zamknij overlay — Przewodnik, Dostawa, Kontakt, podpunkty Sklep.
			menu.addEventListener('click', function(e) {
				var a = getLinkFromEvent(e, menu);
				if (!a || !a.getAttribute('href') || window.innerWidth > 1024 || !nav) return;
				var parentLi = a.closest('li.menu-item-has-children');
				if (parentLi && isParentItemLink(a, parentLi)) return; // tap na parent (Sklep) — nie zamykaj, toggle obsłużył
				nav.classList.remove('is-open');
				if (menuToggle) { menuToggle.setAttribute('aria-expanded', 'false'); if (menuToggle.getAttribute('data-open-label')) menuToggle.setAttribute('aria-label', menuToggle.getAttribute('data-open-label')); }
			});
		}
		if (searchToggle && searchDropdown) {
			function updateSearchDesktop() {
				if (window.innerWidth >= 1025) {
					searchDropdown.removeAttribute('hidden');
					searchToggle.setAttribute('aria-expanded', 'true');
					var searchOpenLabel = searchToggle.getAttribute('data-open-label');
					if (searchOpenLabel) searchToggle.setAttribute('aria-label', searchOpenLabel);
					document.body.classList.remove('mnsk7-search-open');
					if (searchPanel) { searchPanel.hidden = true; searchPanel.setAttribute('aria-hidden', 'true'); }
				} else {
					searchDropdown.hidden = true;
					if (!document.body.classList.contains('mnsk7-search-open') && searchPanel) {
						searchPanel.hidden = true;
						searchPanel.setAttribute('aria-hidden', 'true');
					}
				}
			}
			window.addEventListener('resize', updateSearchDesktop);
			updateSearchDesktop();
			searchToggle.addEventListener('click', function() {
				if (window.innerWidth >= 1025) return;
				if (!document.body.classList.contains('mnsk7-search-open')) {
					closeAllMobileOverlays('search');
				}
				var open = document.body.classList.contains('mnsk7-search-open');
				setSearchOpen(!open);
			});
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape') {
					if (window.innerWidth < 1025 && document.body.classList.contains('mnsk7-search-open')) {
						setSearchOpen(false);
						if (searchToggle.offsetParent !== null) searchToggle.focus();
					} else if (window.innerWidth >= 1025 && !searchDropdown.hidden) {
						searchDropdown.hidden = true;
						searchToggle.setAttribute('aria-expanded', 'false');
						if (searchToggle.offsetParent !== null) searchToggle.focus();
					}
				}
			});
			document.addEventListener('click', function(e) {
				if (window.innerWidth >= 1025) return;
				var wrap = searchToggle && searchToggle.closest('.mnsk7-header__search-wrap');
				var panel = searchPanel && searchPanel.contains(e.target);
				if (document.body.classList.contains('mnsk7-search-open') && !wrap.contains(e.target) && !panel) {
					setSearchOpen(false);
				}
			});
			var searchForm = searchDropdown.querySelector('form');
			if (searchForm) {
				searchForm.addEventListener('submit', function() {
					if (window.innerWidth < 1025) setSearchOpen(false);
				});
			}
			if (searchPanel) {
				var panelForm = searchPanel.querySelector('form');
				if (panelForm) {
					panelForm.addEventListener('submit', function() {
						if (window.innerWidth < 1025) setSearchOpen(false);
					});
				}
			}
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
					if (e.key === 'Escape' && window.innerWidth < 1025) {
						if (cartWrap.classList.contains('is-open')) { cartWrap.classList.remove('is-open'); setCartExpanded(false); return; }
						if (document.body.classList.contains('mnsk7-search-open')) { setSearchOpen(false); return; }
						if (nav && nav.classList.contains('is-open')) { closeMenu(); return; }
					}
				});
				// Mobile: klik na trigger otwiera/zamyka dropdown (na desktop tylko hover)
				trigger.addEventListener('click', function(e) {
					if (window.innerWidth < 1025) {
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
					if (window.innerWidth >= 1025) { cartWrap.classList.add('is-open'); setCartExpanded(true); }
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
			try {
				if (sessionStorage.getItem('mnsk7_promo_dismissed') === '1') {
					promoBar.remove();
					document.body.classList.remove('mnsk7-has-promo');
				} else {
					document.body.style.setProperty('--mnsk7-promo-h', promoBar.offsetHeight + 'px');
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
		// Header shrink when scrolled (Visual Audit). Hysteresis: add at 70px, remove at 30px — zapobiega „trzęsieniu” przy pozycji ~50px.
		var header = document.getElementById('masthead');
		if (header && header.classList.contains('mnsk7-header')) {
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
		}
		// Instagram card carousel (alesyatakun.by style)
		var carousel = document.querySelector('.mnsk7-instagram-feed--card .mnsk7-instagram-feed__carousel');
		if (carousel) {
			var track = carousel.querySelector('.mnsk7-instagram-feed__track');
			var dots = carousel.querySelectorAll('.mnsk7-instagram-feed__dot');
			var slides = carousel.querySelectorAll('.mnsk7-instagram-feed__slide');
			var n = slides.length;
			if (n > 1 && track && dots.length === n) {
				function goTo(idx) {
					idx = Math.max(0, Math.min(idx, n - 1));
					track.style.transform = 'translateX(-' + idx * 100 + '%)';
					slides.forEach(function(s, i) { s.classList.toggle('is-active', i === idx); });
					dots.forEach(function(d, i) {
						d.classList.toggle('is-active', i === idx);
						d.setAttribute('aria-selected', i === idx ? 'true' : 'false');
					});
				}
				dots.forEach(function(dot, i) {
					dot.addEventListener('click', function() { goTo(i); });
				});
			}
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
		var stickyBtn = sticky.querySelector('.mnsk7-pdp-sticky-cta__btn');
		function isMobile() { return window.matchMedia('(max-width: 768px)').matches; }
		function setStickyVisible(visible) {
			if (!isMobile()) { sticky.setAttribute('hidden', ''); sticky.classList.remove('is-visible'); return; }
			if (visible) { sticky.removeAttribute('hidden'); sticky.classList.add('is-visible'); sticky.setAttribute('aria-hidden', 'false'); }
			else { sticky.setAttribute('hidden', ''); sticky.classList.remove('is-visible'); sticky.setAttribute('aria-hidden', 'true'); }
		}
		var observer = new IntersectionObserver(function(entries) {
			if (!isMobile()) return;
			var e = entries[0];
			setStickyVisible(!e.isIntersecting);
		}, { root: null, rootMargin: '0px', threshold: 0.1 });
		observer.observe(form);
		stickyBtn.addEventListener('click', function() {
			form.scrollIntoView({ behavior: 'smooth', block: 'center' });
			setTimeout(function() { mainBtn.focus(); }, 400);
		});
		// Sync ceny przy wariacjach (variable product)
		var summaryPrice = document.querySelector('.single-product .summary .price');
		if (summaryPrice && stickyPrice && document.querySelector('.single-product form.variations_form')) {
			document.body.addEventListener('show_variation', function(ev) {
				if (ev.target && ev.target.closest && ev.target.closest('.single-product') && ev.data && ev.data.display_price) {
					stickyPrice.innerHTML = ev.data.price_html || summaryPrice.innerHTML;
				}
			});
		}
	})();
	</script>
	<?php
}, 6 );

/* 1e. PLP „Pokaż więcej”: podгрузка następnych wierszy tabeli bez przejścia na page/2 */
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

/* 1e bis. PLP chips „Więcej” / „Więcej filtrów”: pokaż/ukryj dodatkowe chipy i blok filtrów. Scroll do wyników po filter/category/tag. */
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
				btn.addEventListener('click', function() {
					var expanded = btn.getAttribute('aria-expanded') === 'true';
					btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
					target.hidden = expanded;
					btn.textContent = expanded ? btnMore : btnLess;
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

/* 1e ter. Catalog chips „Więcej” na stronie głównej — rozwijanie ukrytych chipów w grupie. */
add_action( 'wp_footer', function () {
	if ( ! is_front_page() ) {
		return;
	}
	?>
	<script>
	(function() {
		function initCatalogChipsToggles() {
			var toggles = document.querySelectorAll('.mnsk7-catalog-chips-toggle');
			var moreLabel = <?php echo json_encode( __( 'Więcej', 'mnsk7-storefront' ) ); ?>;
			var lessLabel = <?php echo json_encode( __( 'Mniej', 'mnsk7-storefront' ) ); ?>;
			toggles.forEach(function(btn) {
				var id = btn.getAttribute('data-controls');
				if (!id) return;
				var target = document.getElementById(id);
				if (!target) return;
				btn.addEventListener('click', function() {
					var expanded = btn.getAttribute('aria-expanded') === 'true';
					btn.setAttribute('aria-expanded', expanded ? 'false' : 'true');
					target.hidden = expanded;
					btn.textContent = expanded ? moreLabel : lessLabel;
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
 * Zapobiega przełączeniu layoutu/headera gdy pluginy zmieniają klasy przy „filter request”.
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

/**
 * Na archiwum tagu produktu: zapisz pełny URL (z filtrami) w cookie, żeby na PDP móc pokazać
 * „wstecz” do tej samej listy nawet gdy Referer nie jest wysyłany (nowa karta, privacy).
 */
add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_product_tag' ) || ! is_product_tag() || headers_sent() ) {
		return;
	}
	$req_uri = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	if ( $req_uri === '' ) {
		return;
	}
	$url = home_url( $req_uri );
	$safe = esc_url_raw( $url );
	if ( $safe === '' ) {
		return;
	}
	setcookie( 'mnsk7_tag_back', $safe, time() + 1800, COOKIEPATH ?: '/', COOKIE_DOMAIN, is_ssl(), true );
}, 5 );

add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
	if ( ! is_array( $crumbs ) ) {
		return $crumbs;
	}
	// Na PDP: jeśli użytkownik przyszedł z archiwum tagu (referer lub cookie), pokaż: Strona główna › Tag. Link = pełny URL (z filtrami).
	if ( is_singular( 'product' ) && count( $crumbs ) > 1 ) {
		$product_id = get_queried_object_id();
		$home_host  = wp_parse_url( home_url( '/' ), PHP_URL_HOST );
		$tag_base   = 'tag-produktu';
		if ( taxonomy_exists( 'product_tag' ) ) {
			$tax_obj = get_taxonomy( 'product_tag' );
			if ( $tax_obj && ! empty( $tax_obj->rewrite['slug'] ) ) {
				$tag_base = $tax_obj->rewrite['slug'];
			}
		}
		$pattern = '#/' . preg_quote( $tag_base, '#' ) . '/([^/]+)/?#';

		$source_url = '';
		$referer    = isset( $_SERVER['HTTP_REFERER'] ) ? esc_url_raw( wp_unslash( $_SERVER['HTTP_REFERER'] ) ) : '';
		if ( $referer !== '' && $home_host && $home_host === wp_parse_url( $referer, PHP_URL_HOST ) ) {
			$ref_path = wp_parse_url( $referer, PHP_URL_PATH );
			if ( $ref_path && preg_match( $pattern, $ref_path ) ) {
				$source_url = $referer;
			}
		}
		if ( $source_url === '' && isset( $_COOKIE['mnsk7_tag_back'] ) ) {
			$cookie_val = esc_url_raw( wp_unslash( $_COOKIE['mnsk7_tag_back'] ) );
			if ( $cookie_val !== '' && $home_host && $home_host === wp_parse_url( $cookie_val, PHP_URL_HOST ) ) {
				$cookie_path = wp_parse_url( $cookie_val, PHP_URL_PATH );
				if ( $cookie_path && preg_match( $pattern, $cookie_path ) ) {
					$source_url = $cookie_val;
				}
			}
		}

		if ( $source_url !== '' ) {
			$ref_path = wp_parse_url( $source_url, PHP_URL_PATH );
			if ( $ref_path && preg_match( $pattern, $ref_path, $m ) && ! empty( $m[1] ) ) {
				$tag_slug = sanitize_text_field( $m[1] );
				$tag_term = get_term_by( 'slug', $tag_slug, 'product_tag' );
				if ( $tag_term && ! is_wp_error( $tag_term ) && has_term( $tag_term->term_id, 'product_tag', $product_id ) ) {
					$tag_link = get_term_link( $tag_term );
					if ( ! is_wp_error( $tag_link ) ) {
						$tag_name   = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $tag_term->name ) : $tag_term->name;
						$home_crumb = isset( $crumbs[0] ) ? $crumbs[0] : array( _x( 'Home', 'breadcrumb', 'woocommerce' ), wc_get_page_permalink( 'shop' ) );
						if ( is_array( $home_crumb ) && isset( $home_crumb[0] ) ) {
							$home_url = home_url( '/' );
							$back_url = $source_url;
							$crumbs   = array(
								array( $home_crumb[0], $home_url ),
								array( $tag_name, $back_url ),
							);
							foreach ( $crumbs as $i => $crumb ) {
								if ( isset( $crumb[1] ) && is_string( $crumb[1] ) ) {
									$crumbs[ $i ][1] = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $crumb[1] ) : $crumb[1];
								}
							}
							return $crumbs;
						}
					}
				}
			}
		}

		// Domyślnie: Strona główna › Sklep › Kategoria (bez nazwy produktu).
		array_pop( $crumbs );
		// Upewnij się, że kategoria produktu jest w okruszkach (WC czasem jej nie dodaje).
		$terms = $product_id ? wc_get_product_terms( $product_id, 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) ) : array();
		if ( ! empty( $terms ) && ! is_wp_error( $terms ) ) {
			$main_term = apply_filters( 'woocommerce_breadcrumb_main_term', $terms[0], $terms );
			$term_link = get_term_link( $main_term );
			if ( ! is_wp_error( $term_link ) ) {
				$last_url = ! empty( $crumbs[ count( $crumbs ) - 1 ][1] ) ? $crumbs[ count( $crumbs ) - 1 ][1] : '';
				if ( $last_url !== $term_link ) {
					$crumbs[] = array( $main_term->name, $term_link );
				}
			}
		}
	}

	// Na archiwum (kategoria, tag, sklep): nie linkuj ostatniego crumb do URL z parametrami filtrów (SEO: duplikaty).
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
	$exclusions[] = 'embed.js';
	$exclusions[] = 'instagram.com';
	return $exclusions;
} );
add_filter( 'script_loader_tag', function ( $tag, $handle, $src ) {
	if ( $handle === 'mnsk7-instagram-embed' ) {
		$tag = str_replace( ' src=', ' nowprocket src=', $tag );
		$tag = preg_replace( '#\s(defer|async)=["\']?[^"\']*["\']?#i', '', $tag );
		// process() dopiero po załadowaniu embed.js — inaczej instgrm jeszcze nie istnieje i iframe nie powstanie
		$tag = str_replace( '</script>', '', $tag );
		$tag .= " onload=\"if(typeof mnsk7InstgrmRun==='function')mnsk7InstgrmRun();\"></script>\n";
	}
	return $tag;
}, 10, 3 );
add_action( 'init', function () {
	add_shortcode( 'mnsk7_instagram_feed', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit' => 6,
			'title' => 'Instagram @mnsk7tools',
			'type'  => 'profile', // profile = jeden iframe z embed profilu (ładuje się stabilnie); posts = blockquote + embed.js
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
		$raw = get_option( 'mnsk7_instagram_post_urls', array() );
		if ( ! is_array( $raw ) ) {
			$raw = array();
		}
		if ( empty( $raw ) ) {
			$raw = array(
				'https://www.instagram.com/mnsk7tools/p/DC4agmPtKoy/',
				'https://www.instagram.com/mnsk7tools/p/DC9J3JjNobj/',
				'https://www.instagram.com/mnsk7tools/p/DCTybzqtxEi/',
			);
		}
		$urls = array();
		foreach ( array_slice( $raw, 0, $limit ) as $entry ) {
			$url = is_array( $entry ) ? ( isset( $entry['url'] ) ? $entry['url'] : '' ) : $entry;
			$url = esc_url_raw( $url );
			if ( $url !== '' ) {
				// Format bez /username/ (instagram.com/p/CODE) — lepsza kompatybilność z embed w 2024+
				if ( preg_match( '#instagram\.com/p/([A-Za-z0-9_-]+)/?#', $url, $m ) ) {
					$url = 'https://www.instagram.com/p/' . $m[1] . '/';
				}
				$urls[] = $url;
			}
		}

		// Skrypt przez wp_enqueue_script — WP Rocket/optymalizatory go nie opóźnią (exclusion), kolejność gwarantowana.
		static $enqueue_done = false;
		if ( ! $enqueue_done ) {
			$enqueue_done = true;
			$embed_url = 'https://www.instagram.com/embed.js';
			wp_enqueue_script( 'mnsk7-instagram-embed', $embed_url, array(), null, true );
			// process() wywołane w onload skryptu embed.js; retry 2s i 5s gdy skrypt ładuje się wolno lub DOM się opóźnia
			$inline = "function mnsk7InstgrmRun(){try{if(window.instgrm&&window.instgrm.Embeds)window.instgrm.Embeds.process();}catch(e){}}setTimeout(mnsk7InstgrmRun,2000);setTimeout(mnsk7InstgrmRun,5000);if(document.readyState!=='complete')window.addEventListener('load',mnsk7InstgrmRun);";
			wp_add_inline_script( 'mnsk7-instagram-embed', $inline, 'after' );
		}
		$out = '<div class="mnsk7-instagram-feed mnsk7-instagram-feed--embed">';
		$out .= '<p class="mnsk7-instagram-feed__more"><a href="' . esc_url( $profile ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__more-link">' . esc_html( $atts['title'] ) . '</a></p>';
		if ( ! empty( $urls ) ) {
			$out .= '<div class="mnsk7-instagram-feed__posts" role="region" aria-label="' . esc_attr__( 'Posty z Instagrama', 'mnsk7-storefront' ) . '">';
			foreach ( $urls as $url ) {
				$out .= '<div class="mnsk7-instagram-feed__post" data-instagram-url="' . esc_attr( $url ) . '">';
				$out .= '<blockquote class="instagram-media" data-instgrm-permalink="' . esc_url( $url ) . '" data-instgrm-version="14"></blockquote>';
				$out .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__post-fallback">' . esc_html__( 'Zobacz post', 'mnsk7-storefront' ) . '</a>';
				$out .= '</div>';
			}
			$out .= '</div>';
			// Gdy iframe nie załaduje się — po 3 s pokaż link „Zobacz post” (blokada embed, CSP, brak sieci).
			$out .= '<script>(function(){var posts=document.querySelectorAll(".mnsk7-instagram-feed__post");if(!posts.length)return;setTimeout(function(){posts.forEach(function(el){var ifr=el.querySelector("iframe");var fallback=el.querySelector(".mnsk7-instagram-feed__post-fallback");if(!fallback)return;if(!ifr||ifr.offsetHeight<100){var wrap=el.querySelector(".instagram-media")||el.querySelector("iframe");if(wrap)wrap.style.display="none";fallback.style.display="inline-flex";}});},3000);})();</script>';
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
 * Human-readable Polish labels for product attribute slugs (for chips and filters).
 * Keys = attribute slug (as in pa_*), values = label for customer.
 *
 * @param string $attr_name Attribute slug without 'pa_' (e.g. srednica, dlugosc-robocza-h).
 * @return string Label to show, or empty to use WooCommerce default.
 */
function mnsk7_attribute_label_pl( $attr_name ) {
	$labels = array(
		'srednica'                => __( 'Średnica', 'mnsk7-storefront' ),
		'srednica-trzpienia'      => __( 'Trzpień', 'mnsk7-storefront' ),
		'wymiary-trzpienia'       => __( 'Wymiary trzpienia', 'mnsk7-storefront' ),
		'dlugosc-calkowita'       => __( 'Długość całkowita', 'mnsk7-storefront' ),
		'dlugosc-calkowita-l'     => __( 'Dł. całkowita (L)', 'mnsk7-storefront' ),
		'dlugosc-czesci-roboczej' => __( 'Dł. części roboczej', 'mnsk7-storefront' ),
		'dlugosc-robocza'         => __( 'Długość robocza', 'mnsk7-storefront' ),
		'dlugosc-robocza-h'       => __( 'Dł. robocza (H)', 'mnsk7-storefront' ),
		'fi'                      => __( 'Średnica (fi)', 'mnsk7-storefront' ),
		'kat-skosu'               => __( 'Kąt skosu', 'mnsk7-storefront' ),
		'kat_skosu'               => __( 'Kąt skosu', 'mnsk7-storefront' ),
		'r'                       => __( 'Promień R', 'mnsk7-storefront' ),
		'typ-pilnika'             => __( 'Typ pilnika', 'mnsk7-storefront' ),
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
 * All filter_* query param names for product attributes (for clearing filters / detecting active filter).
 *
 * @return string[]
 */
function mnsk7_get_all_attribute_filter_param_names() {
	$taxonomies = function_exists( 'mnsk7_get_product_attribute_taxonomy_names' ) ? mnsk7_get_product_attribute_taxonomy_names() : array();
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
	$attr_taxonomies = function_exists( 'mnsk7_get_product_attribute_taxonomy_names' ) ? mnsk7_get_product_attribute_taxonomy_names() : array();
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
 * Attribute filter chips for PLP. Only attributes that have terms in the current archive's products are shown.
 * FB-02: when category is Zestawy, diameter filter row is hidden.
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
	$attr_taxonomies = function_exists( 'mnsk7_get_product_attribute_taxonomy_names' ) ? mnsk7_get_product_attribute_taxonomy_names() : array();
	$attrs_to_try   = array_fill_keys( $attr_taxonomies, '' );
	$term           = get_queried_object();
	if ( ! $term || ! isset( $term->term_id ) ) {
		return $empty;
	}
	$term_slug  = isset( $term->slug ) ? strtolower( (string) $term->slug ) : '';
	$term_name  = isset( $term->name ) ? strtolower( (string) $term->name ) : '';
	$is_zestawy = ( strpos( $term_slug, 'zestaw' ) !== false || strpos( $term_name, 'zestaw' ) !== false );

	$product_ids = mnsk7_get_archive_product_ids_for_chips( $attrs_to_try );
	$filters     = array();
	// When archive has no (in-stock) products, don't show attribute chips — they would show global terms and lead to "Brak produktów".
	if ( empty( $product_ids ) ) {
		return array( 'filters' => array(), 'filter_params' => array() );
	}

	foreach ( $attrs_to_try as $tax => $_ ) {
		if ( $is_zestawy && $tax === 'pa_srednica' ) {
			continue;
		}
		if ( ! taxonomy_exists( $tax ) ) {
			continue;
		}
		$attr_name = str_replace( 'pa_', '', $tax );
		$label     = function_exists( 'mnsk7_attribute_label_pl' ) ? mnsk7_attribute_label_pl( $attr_name ) : '';
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
		$chips = array();
		foreach ( $terms as $t ) {
			$chips[ $t->slug ] = $t->name;
		}
		$param    = 'filter_' . str_replace( 'pa_', '', $tax );
		$filters[] = array(
			'label' => $label . ': ',
			'param' => $param,
			'chips' => $chips,
		);
	}
	$filter_params = array_column( $filters, 'param' );
	return array( 'filters' => $filters, 'filter_params' => $filter_params );
}
