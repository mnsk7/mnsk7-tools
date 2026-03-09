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

/** PLP: u góry — sortowanie + paginacja; u dołu — tylko wyników + paginacja (bez duplikatu).
 * Storefront parent dodaje do after_shop_loop: storefront_sorting_wrapper (9), woocommerce_catalog_ordering (10),
 * woocommerce_result_count (20), woocommerce_pagination (30), storefront_sorting_wrapper_close (31).
 * Usuwamy sortowanie i wrapper z dołu, result_count zostawiamy raz (nasz na 5). */
add_action( 'wp', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
		return;
	}
	remove_action( 'woocommerce_before_shop_loop', 'woocommerce_result_count', 20 );
	add_action( 'woocommerce_after_shop_loop', 'woocommerce_result_count', 5 );
	// Storefront: zdublowane sortowanie i result_count na dole — usuwamy.
	remove_action( 'woocommerce_after_shop_loop', 'storefront_sorting_wrapper', 9 );
	remove_action( 'woocommerce_after_shop_loop', 'woocommerce_catalog_ordering', 10 );
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

/** PDP: okruszki przy tytule produktu, nie pod headerem */
add_action( 'wp', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	remove_action( 'woocommerce_before_main_content', 'woocommerce_breadcrumb', 20 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_breadcrumb', 5 );
} );

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

/** 4.6 Tekst pustego koszyka (do wyświetlenia w cart-empty.php) */
add_filter( 'wc_empty_cart_message', function () {
	return __( 'Twój koszyk jest pusty — wróć do sklepu', 'mnsk7-storefront' );
}, 10 );

/** Audit: jeden bank cookie — wyłącz bank temy, jeśli używany jest plugin (add_filter( 'mnsk7_show_cookie_bar', __return_false' ); w mu-pluginie) */
add_filter( 'mnsk7_show_cookie_bar', function ( $show ) {
	return $show;
}, 5 );

/** 4.0 UX: domyślny tekst promocyjny w headerze (darmowa dostawa) + CTA do Dostawa (audit Zad.11). Na stronie głównej bez paska — nie konkurować z hero. */
add_filter( 'mnsk7_header_promo_text', function ( $text ) {
	if ( $text !== '' ) {
		return $text;
	}
	if ( is_front_page() ) {
		return '';
	}
	$dostawa_url = home_url( '/dostawa-i-platnosci/' );
	$link = '<a href="' . esc_url( $dostawa_url ) . '">' . esc_html__( 'Warunki dostawy', 'mnsk7-storefront' ) . ' &rarr;</a>';
	return sprintf(
		/* translators: 1: promo text, 2: link HTML to delivery page */
		__( 'Darmowa dostawa od 300 zł. Tylko Polska. %1$s', 'mnsk7-storefront' ),
		$link
	);
}, 5 );

/** Audit: H1 na stronie Moje konto (task Account H1) */
add_action( 'woocommerce_before_account_navigation', function () {
	if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
		return;
	}
	echo '<h1 class="mnsk7-account-title entry-title">' . esc_html__( 'Moje konto', 'mnsk7-storefront' ) . '</h1>';
}, 5 );

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
	$v = defined( 'MNSK7_THEME_VERSION' ) ? MNSK7_THEME_VERSION : '3.0.9';
	$base = get_stylesheet_directory_uri() . '/assets/css/parts/';
	$dir = get_stylesheet_directory() . '/assets/css/parts/';
	if ( mnsk7_parent_storefront_available() ) {
		wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array( 'storefront-style' ), $v );
	} else {
		wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array(), $v );
	}
	$prev = 'mnsk7-storefront-style';
	$parts = array( '00-fonts-inter', '01-tokens', '02-reset-typography', '03-storefront-overrides', '04-header', '05-plp-cards', '06-single-product', '07-mnsk7-blocks', '08-home-sections', '09-footer', '10-cookie-bar', '11-hidden', '12-related-products', '13-seo-landing', '14-faq', '15-delivery-contact', '16-woo-notices', '17-buttons', '18-cart-checkout', '19-breadcrumbs', '20-responsive-tablet', '21-responsive-mobile', '22-touch-targets', '23-print', '24-plp-table' );
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
		$prev = 'mnsk7-main';
	}
	/* Krytyczne style inline — footer ciemny i Instagram karta; przywiązane do ostatniego handle, żeby działały nawet gdy brak pliku parts/09 lub 08 */
	$footer_inline = 'footer.mnsk7-footer,#colophon.mnsk7-footer,.site-footer.mnsk7-footer{background:#1e293b!important;color:#e2e8f0}.mnsk7-footer__top,.mnsk7-footer__col,.mnsk7-footer__title,.mnsk7-footer__top p,.mnsk7-footer__col p,.mnsk7-footer__col li{color:#e2e8f0!important}.mnsk7-footer__top a{color:#60a5fa}';
	$insta_inline  = '.mnsk7-instagram-feed--card{width:100%;max-width:560px;background:#fff;border-radius:12px;box-shadow:0 4px 20px rgba(0,0,0,.08)}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__carousel{aspect-ratio:1;overflow:hidden;background:linear-gradient(135deg,#833ab4,#fd1d1d,#fcb045)}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__track{display:flex;height:100%;transition:transform .3s ease}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__slide{flex:0 0 100%;width:100%;height:100%;position:relative}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__slide .mnsk7-instagram-feed__img{position:absolute;inset:0;width:100%;height:100%;object-fit:cover}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__dots{display:flex;justify-content:center;gap:6px;padding:10px 0}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__dot{width:8px;height:8px;border-radius:50%;border:none;background:#c4c4c4;cursor:pointer}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__dot.is-active{background:#0d6efd;transform:scale(1.15)}.mnsk7-instagram-feed--card .mnsk7-instagram-feed__profile{display:flex;align-items:center;gap:.5rem;padding:.75rem 1rem;border-top:1px solid #eee}';
	wp_add_inline_style( $prev, $footer_inline . "\n" . $insta_inline );
}, 10 );

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

/* 1b. Enqueue cart fragments so header cart count updates via AJAX (na wszystkich stronach z headerem) */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_admin() && function_exists( 'WC' ) ) {
		wp_enqueue_script( 'wc-cart-fragments' );
	}
}, 5 );

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
	$fragments['.mnsk7-header__cart-summary'] = function_exists( 'mnsk7_header_cart_summary_html' )
		? mnsk7_header_cart_summary_html( $cart_count, $cart_total, $loyalty_discount )
		: '<div class="mnsk7-header__cart-summary">' . ( $cart_count > 0 && $cart_total ? sprintf( _n( '%1$d produkt · %2$s', '%1$d produktów · %2$s', $cart_count, 'mnsk7-storefront' ), $cart_count, $cart_total ) : esc_html__( 'Koszyk jest pusty', 'mnsk7-storefront' ) ) . '</div>';
	return $fragments;
}, 20 );

/* 1d. Header: mobile menu, search toggle, cart dropdown, promo bar dismiss, sticky shrink on scroll */
add_action( 'wp_footer', function () {
	?>
	<script>
	(function() {
		var menuToggle = document.querySelector('.mnsk7-header__menu-toggle');
		var nav = document.querySelector('.mnsk7-header__nav');
		if (menuToggle && nav) {
			menuToggle.addEventListener('click', function() {
				nav.classList.toggle('is-open');
				menuToggle.setAttribute('aria-expanded', nav.classList.contains('is-open'));
			});
		}
		// Mobile (≤768px): link „Sklep” prowadzi do sklepu (submenu ukryte w CSS)
		var menu = document.getElementById('mnsk7-primary-menu');
		if (menu) {
			var parentItems = menu.querySelectorAll('li.menu-item-has-children');
			parentItems.forEach(function(li) {
				var a = li.querySelector(':scope > a');
				if (!a) return;
				a.addEventListener('click', function(e) {
					if (window.innerWidth <= 768) return;
					e.preventDefault();
					li.classList.toggle('is-open');
					a.setAttribute('aria-expanded', li.classList.contains('is-open'));
				});
			});
		}
		var searchToggle = document.querySelector('.mnsk7-header__search-toggle');
		var searchDropdown = document.getElementById('mnsk7-header-search');
		if (searchToggle && searchDropdown) {
			function setSearchOpen(open) {
				searchDropdown.hidden = !open;
				searchToggle.setAttribute('aria-expanded', open);
			}
			function updateSearchDesktop() {
				if (window.innerWidth >= 900) {
					searchDropdown.removeAttribute('hidden');
					searchToggle.setAttribute('aria-expanded', 'true');
				} else {
					searchDropdown.hidden = true;
				}
			}
			window.addEventListener('resize', updateSearchDesktop);
			updateSearchDesktop();
			searchToggle.addEventListener('click', function() {
				if (window.innerWidth >= 900) return;
				var open = searchDropdown.hidden;
				setSearchOpen(open);
				if (open) {
					var inp = document.getElementById('mnsk7-header-search-input') || document.querySelector('#mnsk7-header-search input[type="search"]');
					if (inp) { inp.focus(); }
				}
			});
			document.addEventListener('keydown', function(e) {
				if (e.key === 'Escape' && !searchDropdown.hidden) {
					setSearchOpen(false);
					if (searchToggle.offsetParent !== null) searchToggle.focus();
				}
			});
			var searchForm = searchDropdown.querySelector('form');
			if (searchForm) {
				searchForm.addEventListener('submit', function() {
					if (window.innerWidth < 900) setSearchOpen(false);
				});
			}
		}
		var cartWrap = document.querySelector('.mnsk7-header__cart');
		if (cartWrap) {
			var trigger = cartWrap.querySelector('.mnsk7-header__cart-trigger');
			var dropdown = cartWrap.querySelector('.mnsk7-header__cart-dropdown');
			if (trigger && dropdown) {
				document.addEventListener('click', function(e) {
					if (!cartWrap.contains(e.target)) cartWrap.classList.remove('is-open');
				});
				document.addEventListener('keydown', function(e) {
					if (e.key === 'Escape' && cartWrap.classList.contains('is-open')) {
						cartWrap.classList.remove('is-open');
						trigger.focus();
					}
				});
				// Desktop: dropdown tylko przy hover na triggerze lub dropdownie (nie na całym headerze/bannerze)
				var cartOpenTimer;
				function openCart() {
					clearTimeout(cartOpenTimer);
					if (window.innerWidth >= 769) cartWrap.classList.add('is-open');
				}
				function closeCart() {
					cartOpenTimer = setTimeout(function() {
						cartWrap.classList.remove('is-open');
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
		// Promo bar: dismissible (sessionStorage), header sticks below when visible
		var promoBar = document.getElementById('mnsk7-promo-bar');
		if (promoBar) {
			try {
				if (sessionStorage.getItem('mnsk7_promo_dismissed') === '1') {
					promoBar.remove();
				} else {
					document.body.classList.add('mnsk7-has-promo');
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
		// Header shrink when scrolled (Visual Audit)
		var header = document.getElementById('masthead');
		if (header && header.classList.contains('mnsk7-header')) {
			function onScroll() {
				header.classList.toggle('mnsk7-header--scrolled', window.scrollY > 50);
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
		btn.addEventListener('click', function(e) {
			var href = this.getAttribute('href');
			if (href && href.indexOf('zamowienie') !== -1) {
				setTimeout(function() {
					if (window.location.pathname.indexOf('koszyk') !== -1) {
						window.location.href = href;
					}
				}, 100);
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

/* 1e bis. PLP chips „Więcej”: pokaż/ukryj dodatkowe chipy filtrów (Średnica, Długość L/H) */
add_action( 'wp_footer', function () {
	if ( ! function_exists( 'is_shop' ) || ( ! is_shop() && ! is_product_category() && ! is_product_tag() ) ) {
		return;
	}
	$more_text = __( 'Więcej', 'mnsk7-storefront' );
	$less_text = __( 'Mniej', 'mnsk7-storefront' );
	?>
	<script>
	(function() {
		var toggles = document.querySelectorAll('.mnsk7-plp-chips-toggle');
		var moreLabel = <?php echo json_encode( $more_text ); ?>;
		var lessLabel = <?php echo json_encode( $less_text ); ?>;
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
	})();
	</script>
	<?php
}, 19 );

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

/* PLP-02: breadcrumbs na archive (sklep, kategoria, tag, wyniki wyszukiwania produktów) */
add_action( 'woocommerce_before_main_content', function () {
	$is_plp = function_exists( 'is_shop' ) && ( is_shop() || ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() ) );
	$is_product_search = is_search() && get_query_var( 'post_type' ) === 'product';
	if ( $is_plp || $is_product_search ) {
		woocommerce_breadcrumb();
	}
}, 19 );

add_filter( 'woocommerce_get_breadcrumb', function ( $crumbs ) {
	if ( ! is_array( $crumbs ) ) {
		return $crumbs;
	}
	// Na PDP bez nazwy produktu w okruszkach: Strona główna › Sklep › Kategoria.
	if ( is_singular( 'product' ) && count( $crumbs ) > 1 ) {
		array_pop( $crumbs );
		// Upewnij się, że kategoria produktu jest w okruszkach (WC czasem jej nie dodaje).
		$product_id = get_queried_object_id();
		$terms      = $product_id ? wc_get_product_terms( $product_id, 'product_cat', array( 'orderby' => 'parent', 'order' => 'DESC' ) ) : array();
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

/* 11. Instagram shortcode — oficjalny embed (jak na alesyatakun.by: blockquote + embed.js) */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_instagram_feed', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit' => 6,
			'title' => 'Instagram @mnsk7tools',
		), $atts, 'mnsk7_instagram_feed' );
		$limit = max( 1, min( 12, (int) $atts['limit'] ) );
		$profile = defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/';
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
				$urls[] = $url;
			}
		}
		$handle = preg_replace( '#^https?://(www\.)?instagram\.com/#', '', untrailingslashit( $profile ) );
		$handle = $handle !== '' ? $handle : 'mnsk7tools';

		// Na front-page skrypt jest w szablonie (za sekcją). Gdzie indziej — w footer.
		static $footer_done = false;
		if ( ! is_front_page() && ! $footer_done ) {
			$footer_done = true;
			add_action( 'wp_footer', function () {
				echo '<script src="https://www.instagram.com/embed.js"></script>' . "\n";
				echo '<script>(function(){function r(){if(window.instgrm&&window.instgrm.Embeds)window.instgrm.Embeds.process();}r();if(document.readyState!=="complete")window.addEventListener("load",r);})();</script>' . "\n";
			}, 5 );
		}
		$out = '<div class="mnsk7-instagram-feed mnsk7-instagram-feed--embed">';
		$out .= '<p class="mnsk7-instagram-feed__more"><a href="' . esc_url( $profile ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__more-link">' . esc_html( $atts['title'] ) . '</a></p>';
		if ( ! empty( $urls ) ) {
			$out .= '<div class="mnsk7-instagram-feed__posts" role="region" aria-label="' . esc_attr__( 'Posty z Instagrama', 'mnsk7-storefront' ) . '">';
			foreach ( $urls as $url ) {
				$out .= '<div class="mnsk7-instagram-feed__post">';
				$out .= '<blockquote class="instagram-media" data-instgrm-permalink="' . esc_url( $url ) . '" data-instgrm-version="14"></blockquote>';
				$out .= '<a href="' . esc_url( $url ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__post-fallback">' . esc_html__( 'Zobacz post', 'mnsk7-storefront' ) . '</a>';
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
add_action( 'woocommerce_product_query', function ( $q ) {
	if ( is_admin() || ! is_object( $q ) || ! method_exists( $q, 'set' ) ) {
		return;
	}
	$attr_taxonomies = array( 'pa_srednica', 'pa_srednica-trzpienia', 'pa_dlugosc-calkowita-l', 'pa_dlugosc-robocza-h' );
	$tax            = $q->get( 'tax_query' );
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
		// FB-03 bis: jeśli z object_ids wyszło pusto, pokaż wiersz atrybutu z wszystkimi termami (np. Trzpień — żeby był wszędzie, gdzie atrybut istnieje).
		if ( ( is_wp_error( $terms ) || empty( $terms ) ) && ! empty( $product_ids ) ) {
			unset( $get_terms_args['object_ids'] );
			$terms = get_terms( $get_terms_args );
		}
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
