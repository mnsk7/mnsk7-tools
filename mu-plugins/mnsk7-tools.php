<?php
/**
 * Plugin Name: MNK7 Tools (MU)
 * Description: Biznesowa logika projektu mnsk7-tools.pl — filtry, helpery, customizacje Woo. Nie zależy od motywu.
 * Author: Projekt mnsk7-tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// P0-03: blokada xmlrpc.php (bezpieczeństwo)
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit;
}

define( 'MNK7_TOOLS_VERSION', '1.1.0' );

/**
 * Stałe kontaktowe (klient).
 */
define( 'MNK7_CONTACT_EMAIL', 'office@mnsk7.pl' );
define( 'MNK7_CONTACT_PHONE', '+48 451696511' );
define( 'MNK7_CONTACT_HOURS', 'pn.–pt. 9.00–17.00, sb. 10.00–12.00, nd. zamknięte' );
define( 'MNK7_INSTAGRAM_URL', 'https://www.instagram.com/mnsk7tools/' );
define( 'MNK7_ALLEGRO_SELLER_URL', 'https://allegro.pl/uzytkownik/mnsk7-tools_pl' );
define( 'MNK7_FREE_SHIPPING_MIN', 300 );

/**
 * Progi rabatowe w panelu (suma zamówień w roku, status completed).
 */
function mnsk7_loyalty_tiers() {
	return array(
		1000  => 5,
		3000  => 10,
		5000  => 15,
		10000 => 20,
	);
}

/**
 * Suma zamówień użytkownika w bieżącym roku (completed).
 */
function mnsk7_get_customer_year_total( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
		return 0.0;
	}
	$year_start = strtotime( date( 'Y' ) . '-01-01 00:00:00' );
	$year_end   = strtotime( ( date( 'Y' ) + 1 ) . '-01-01 00:00:00' ) - 1;
	$orders     = wc_get_orders( array(
		'customer_id' => $user_id,
		'status'      => array( 'wc-completed' ),
		'date_query'  => array(
			array(
				'after'  => date( 'Y-m-d H:i:s', $year_start ),
				'before' => date( 'Y-m-d H:i:s', $year_end ),
				'inclusive' => true,
			),
		),
		'return' => 'ids',
		'limit'  => -1,
	) );
	$total = 0.0;
	foreach ( $orders as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( $order ) {
			$total += (float) $order->get_total();
		}
	}
	return $total;
}

/**
 * Obecny procent rabatu i próg do następnego (na podstawie sumy w roku).
 */
function mnsk7_loyalty_current_tier( $total ) {
	$tiers  = mnsk7_loyalty_tiers();
	$sorted = array_keys( $tiers );
	sort( $sorted, SORT_NUMERIC );
	$current_pct = 0;
	$next_at     = null;
	$lack        = null;
	foreach ( $sorted as $threshold ) {
		if ( $total >= $threshold ) {
			$current_pct = $tiers[ $threshold ];
		} else {
			$next_at = $threshold;
			$lack    = $threshold - $total;
			break;
		}
	}
	return array(
		'percent'  => $current_pct,
		'next_at'  => $next_at,
		'next_pct' => $next_at && isset( $tiers[ $next_at ] ) ? $tiers[ $next_at ] : null,
		'lack'     => $lack,
	);
}

/**
 * Blok lojalności w Moje konto (dashboard).
 */
function mnsk7_loyalty_block_html() {
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return '';
	}
	$total = mnsk7_get_customer_year_total( $user_id );
	$tier  = mnsk7_loyalty_current_tier( $total );
	$tiers = mnsk7_loyalty_tiers();
	$year  = date( 'Y' );

	$html  = '<div class="mnsk7-loyalty-block">';
	$html .= '<h3 class="mnsk7-loyalty-block__title">' . esc_html__( 'System rabatów', 'mnsk7-tools' ) . '</h3>';
	$html .= '<p class="mnsk7-loyalty-block__sum">';
	$html .= sprintf(
		/* translators: 1: year, 2: amount */
		__( 'W %1$s roku zamówiłeś za %2$s zł.', 'mnsk7-tools' ),
		$year,
		number_format_i18n( $total, 2 )
	);
	$html .= '</p>';
	$html .= '<p class="mnsk7-loyalty-block__pct">';
	$html .= sprintf(
		/* translators: %d: discount percent */
		__( 'Twój aktualny rabat: %d%%.', 'mnsk7-tools' ),
		$tier['percent']
	);
	$html .= '</p>';
	if ( $tier['next_at'] !== null && $tier['lack'] !== null ) {
		$html .= '<p class="mnsk7-loyalty-block__next">';
		$html .= sprintf(
			/* translators: 1: amount missing, 2: next threshold, 3: next percent */
			__( 'Do rabatu %3$d%% brakuje %1$s zł (próg %2$s zł).', 'mnsk7-tools' ),
			number_format_i18n( $tier['lack'], 2 ),
			number_format_i18n( $tier['next_at'], 0 ),
			$tier['next_pct']
		);
		$html .= '</p>';
	}
	$html .= '<ul class="mnsk7-loyalty-block__tiers">';
	foreach ( $tiers as $thr => $pct ) {
		$html .= '<li>' . sprintf( __( '%s zł → %d%%', 'mnsk7-tools' ), number_format_i18n( $thr, 0 ), $pct ) . '</li>';
	}
	$html .= '</ul>';
	$html .= '</div>';
	return $html;
}

add_action( 'woocommerce_account_dashboard', 'mnsk7_echo_loyalty_block', 15 );
function mnsk7_echo_loyalty_block() {
	echo mnsk7_loyalty_block_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_loyalty', function () {
		return is_user_logged_in() ? mnsk7_loyalty_block_html() : '';
	} );
}, 6 );

/**
 * Automatyczny rabat lojalnościowy w koszyku (procent od sumy, na podstawie sumy zamówień w roku).
 */
add_action( 'woocommerce_cart_calculate_fees', 'mnsk7_loyalty_cart_discount', 20 );
function mnsk7_loyalty_cart_discount( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}
	$total_spent = mnsk7_get_customer_year_total( $user_id );
	$tier        = mnsk7_loyalty_current_tier( $total_spent );
	if ( $tier['percent'] <= 0 ) {
		return;
	}
	$subtotal = (float) $cart->get_subtotal();
	if ( $subtotal <= 0 ) {
		return;
	}
	$discount = -1 * round( $subtotal * $tier['percent'] / 100, 2 );
	$cart->add_fee(
		sprintf( __( 'Rabat lojalnościowy (%d%%)', 'mnsk7-tools' ), $tier['percent'] ),
		$discount,
		true
	);
}

/**
 * Komunikat w koszyku i przy checkout: darmowa dostawa od 300 zł.
 */
add_action( 'woocommerce_before_cart', 'mnsk7_cart_free_shipping_notice', 5 );
add_action( 'woocommerce_before_checkout_form', 'mnsk7_cart_free_shipping_notice', 5 );
function mnsk7_cart_free_shipping_notice() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return;
	}
	$min   = (float) MNK7_FREE_SHIPPING_MIN;
	$total = WC()->cart->get_displayed_subtotal();
	if ( $total >= $min ) {
		wc_print_notice( __( 'Masz darmową dostawę!', 'mnsk7-tools' ), 'success' );
		return;
	}
	$lack = $min - $total;
	$msg  = sprintf(
		/* translators: 1: amount in zł, 2: amount missing */
		__( 'Darmowa dostawa od %1$s zł. Do gratisowej dostawy brakuje Ci %2$s zł.', 'mnsk7-tools' ),
		number_format_i18n( $min, 0 ),
		number_format_i18n( $lack, 2 )
	);
	wc_print_notice( $msg, 'notice' );
}

/**
 * Lista atrybutów wyświetlanych w bloku "Kluczowe parametry" w karcie produktu.
 * Klucz = slug atrybutu (Woo: pa_* dla globalnych), wartość = etykieta.
 */
function mnsk7_get_key_param_attributes() {
	return array(
		'srednica'       => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'pa_srednica'    => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'fi'             => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'pa_fi'          => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'dlugosc-robocza-h' => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-calkowita-l' => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-calkowita'   => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-robocza'    => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-czesci-roboczej' => __( 'Długość części roboczej', 'mnsk7-tools' ),
		'r'              => __( 'Promień R', 'mnsk7-tools' ),
		'pa_r'           => __( 'Promień R', 'mnsk7-tools' ),
		'typ'            => __( 'Typ', 'mnsk7-tools' ),
		'pa_typ'         => __( 'Typ', 'mnsk7-tools' ),
		'ksztalt'        => __( 'Kształt', 'mnsk7-tools' ),
		'zastosowanie'   => __( 'Zastosowanie', 'mnsk7-tools' ),
		'pa_zastosowanie' => __( 'Zastosowanie', 'mnsk7-tools' ),
	);
}

/**
 * Wyświetla blok kluczowych parametrów w karcie produktu (S2-04).
 */
function mnsk7_single_product_key_params() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$labels = mnsk7_get_key_param_attributes();
	$found  = array();

	foreach ( array_keys( $labels ) as $slug ) {
		$val = $product->get_attribute( $slug );
		if ( $val !== '' && $val !== null ) {
			$label = $labels[ $slug ];
			if ( ! isset( $found[ $label ] ) ) {
				$found[ $label ] = $val;
			}
		}
	}

	if ( empty( $found ) ) {
		return;
	}

	echo '<div class="mnsk7-product-key-params">';
	echo '<h4 class="mnsk7-product-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-tools' ) . '</h4>';
	echo '<dl class="mnsk7-product-key-params__list">';
	foreach ( $found as $label => $value ) {
		echo '<dt>' . esc_html( $label ) . '</dt>';
		echo '<dd>' . esc_html( $value ) . '</dd>';
	}
	echo '</dl></div>';
}

/**
 * Wyświetla blok "Podstaw dla" w karcie produktu (S2-05).
 */
function mnsk7_single_product_zastosowanie() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$val = $product->get_attribute( 'zastosowanie' );
	if ( $val === '' || $val === null ) {
		$val = $product->get_attribute( 'pa_zastosowanie' );
	}
	if ( $val === '' || $val === null ) {
		return;
	}

	echo '<div class="mnsk7-product-zastosowanie">';
	echo '<h4 class="mnsk7-product-zastosowanie__title">' . esc_html__( 'Do czego / Zastosowanie', 'mnsk7-tools' ) . '</h4>';
	echo '<p class="mnsk7-product-zastosowanie__text">' . esc_html( $val ) . '</p>';
	echo '</div>';
}

/**
 * Wyświetla dostępność w magazynie (S2-10).
 */
function mnsk7_single_product_availability() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$availability = $product->get_availability();
	$class        = ! empty( $availability['class'] ) ? $availability['class'] : '';
	$text         = ! empty( $availability['availability'] ) ? $availability['availability'] : ( $product->is_in_stock() ? __( 'W magazynie', 'mnsk7-tools' ) : __( 'Na zamówienie', 'mnsk7-tools' ) );

	if ( empty( $class ) ) {
		$class = $product->is_in_stock() ? 'in-stock' : 'out-of-stock';
	}

	echo '<p class="mnsk7-product-availability ' . esc_attr( $class ) . '">'
		. '<i class="mnsk7-product-trust__badge-icon">&#10003;</i> '
		. esc_html( $text )
		. '</p>';
}

/**
 * Trust badges pod przyciskiem "Dodaj do koszyka" w karcie produktu.
 * Wyróżnia: dostawę jutro, fakturę VAT, darmową dostawę od X zł, zwroty, popularność produktu.
 */
function mnsk7_single_product_trust_badges() {
	global $product;
	$min = number_format_i18n( MNK7_FREE_SHIPPING_MIN, 0 );

	$badges = array(
		__( 'Dostawa jutro', 'mnsk7-tools' ),
		__( 'Faktura VAT', 'mnsk7-tools' ),
		sprintf( __( 'Darmowa dostawa od %s zł', 'mnsk7-tools' ), $min ),
		__( 'Zwroty 30 dni', 'mnsk7-tools' ),
	);

	echo '<div class="mnsk7-product-trust">';
	foreach ( $badges as $badge ) {
		echo '<span class="mnsk7-product-trust__badge"><i class="mnsk7-product-trust__badge-icon" aria-hidden="true">&#10003;</i>'
			. esc_html( $badge ) . '</span>';
	}

	if ( is_a( $product, 'WC_Product' ) ) {
		$sales = (int) $product->get_total_sales();
		if ( $sales >= 5 ) {
			echo '<span class="mnsk7-product-trust__badge mnsk7-product-trust__badge--sales">'
				. '<i class="mnsk7-product-trust__badge-icon" aria-hidden="true">&#9733;</i>'
				. sprintf(
					/* translators: %d: number of buyers */
					_n( '%d osoba kupiła', '%d osób kupiło', $sales, 'mnsk7-tools' ),
					$sales
				)
				. '</span>';
		}
	}

	echo '</div>';
}

/**
 * Podpięcie bloków karty produktu do hooków WooCommerce.
 * availability   → woocommerce_single_product_summary priority 8  (przed ceną/tytułem)
 * key_params     → woocommerce_single_product_summary priority 21 (po excerptie, przed CTA)
 * zastosowanie   → woocommerce_single_product_summary priority 23
 * trust_badges   → woocommerce_single_product_summary priority 32 (po "Dodaj do koszyka")
 */
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_availability', 8 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_key_params', 21 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_zastosowanie', 23 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_trust_badges', 32 );

/**
 * Wyświetla informację o dostawie i fakturze VAT (S2-11) — w karcie produktu i shortcode.
 */
function mnsk7_dostawa_vat_html() {
	return '<p class="mnsk7-dostawa-vat">'
		. esc_html__( 'Dostawa następnego dnia. Faktura VAT dostępna na życzenie.', 'mnsk7-tools' )
		. '</p>';
}

/**
 * Kontakt do wyświetlenia w stopce / shortcode.
 */
function mnsk7_contact_info_html() {
	$email = antispambot( MNK7_CONTACT_EMAIL );
	$phone = preg_replace( '/\s+/', '', MNK7_CONTACT_PHONE );

	return '<div class="mnsk7-contact-info">'
		. '<h4 class="mnsk7-contact-info__title">' . esc_html__( 'Kontakt', 'mnsk7-tools' ) . '</h4>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Email:', 'mnsk7-tools' ) . '</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Telefon:', 'mnsk7-tools' ) . '</strong> <a href="tel:' . esc_attr( $phone ) . '">' . esc_html( MNK7_CONTACT_PHONE ) . '</a></p>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Godziny:', 'mnsk7-tools' ) . '</strong> ' . esc_html( MNK7_CONTACT_HOURS ) . '</p>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Instagram:', 'mnsk7-tools' ) . '</strong> <a href="' . esc_url( MNK7_INSTAGRAM_URL ) . '" target="_blank" rel="noopener">mnsk7tools</a></p>'
		. '</div>';
}

/**
 * Reguły dostawy (InPost / DPD) + darmowa dostawa.
 */
function mnsk7_delivery_rules_table_html() {
	$rows = array(
		array(
			'courier' => 'InPost',
			'order'   => __( 'pn.–pt. do 15:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa następnego dnia', 'mnsk7-tools' ),
		),
		array(
			'courier' => 'InPost',
			'order'   => __( 'sb. do 11:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa w poniedziałek', 'mnsk7-tools' ),
		),
		array(
			'courier' => 'DPD',
			'order'   => __( 'pn.–czw. do 17:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa następnego dnia', 'mnsk7-tools' ),
		),
		array(
			'courier' => 'DPD',
			'order'   => __( 'pt. do 17:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa w poniedziałek', 'mnsk7-tools' ),
		),
	);

	$html  = '<div class="mnsk7-delivery-rules">';
	$html .= '<h4 class="mnsk7-delivery-rules__title">' . esc_html__( 'Orientacyjny czas dostawy (Polska)', 'mnsk7-tools' ) . '</h4>';
	$html .= '<table class="mnsk7-delivery-rules__table"><thead><tr>';
	$html .= '<th>' . esc_html__( 'Kurier', 'mnsk7-tools' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Kiedy zamówisz', 'mnsk7-tools' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Dostawa', 'mnsk7-tools' ) . '</th>';
	$html .= '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$html .= '<tr>';
		$html .= '<td>' . esc_html( $row['courier'] ) . '</td>';
		$html .= '<td>' . esc_html( $row['order'] ) . '</td>';
		$html .= '<td>' . esc_html( $row['result'] ) . '</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody></table>';
	$html .= '<p class="mnsk7-delivery-rules__free">' . esc_html__( 'Darmowa dostawa od 300 zł.', 'mnsk7-tools' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Szacowany komunikat ETA pod wybraną metodę dostawy.
 */
function mnsk7_estimated_delivery_text( $courier ) {
	$hour = (int) current_time( 'G' );
	$wday = (int) current_time( 'w' ); // 0 = Sunday, 6 = Saturday.

	if ( $courier === 'inpost' ) {
		if ( $wday >= 1 && $wday <= 5 ) {
			if ( $hour < 15 ) {
				return __( 'InPost: zamów do 15:00 — dostawa następnego dnia.', 'mnsk7-tools' );
			}
			return __( 'InPost: zamówienie po 15:00 — dostawa zwykle w najbliższy dzień roboczy.', 'mnsk7-tools' );
		}
		if ( $wday === 6 ) {
			return __( 'InPost: sobota do 11:00 — dostawa w poniedziałek.', 'mnsk7-tools' );
		}
		return __( 'InPost: zamówienie w niedzielę — wysyłka od poniedziałku.', 'mnsk7-tools' );
	}

	if ( $courier === 'dpd' ) {
		if ( $wday >= 1 && $wday <= 4 ) {
			if ( $hour < 17 ) {
				return __( 'DPD: zamów do 17:00 — dostawa następnego dnia.', 'mnsk7-tools' );
			}
			return __( 'DPD: zamówienie po 17:00 — dostawa zwykle w najbliższy dzień roboczy.', 'mnsk7-tools' );
		}
		if ( $wday === 5 ) {
			return __( 'DPD: piątek do 17:00 — dostawa w poniedziałek.', 'mnsk7-tools' );
		}
		return __( 'DPD: zamówienie w weekend — wysyłka od poniedziałku.', 'mnsk7-tools' );
	}

	return __( 'Dostawa: wybierz InPost lub DPD, aby zobaczyć orientacyjny termin dostawy.', 'mnsk7-tools' );
}

/**
 * Próba rozpoznania kuriera na podstawie wybranej metody Woo (checkout/cart).
 */
function mnsk7_detect_selected_courier() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return '';
	}

	$methods = WC()->session->get( 'chosen_shipping_methods', array() );
	if ( empty( $methods ) || ! is_array( $methods ) ) {
		return '';
	}

	$method = strtolower( (string) reset( $methods ) );
	if ( strpos( $method, 'inpost' ) !== false ) {
		return 'inpost';
	}
	if ( strpos( $method, 'dpd' ) !== false ) {
		return 'dpd';
	}
	return '';
}

/**
 * HTML komunikatu ETA.
 */
function mnsk7_delivery_eta_html( $courier = '' ) {
	if ( $courier === '' ) {
		$courier = mnsk7_detect_selected_courier();
	}
	return '<p class="mnsk7-delivery-eta">' . esc_html( mnsk7_estimated_delivery_text( $courier ) ) . '</p>';
}

/**
 * Instagram block (shortcode).
 * Użycie:
 * [mnsk7_instagram_feed]
 * [mnsk7_instagram_feed posts="https://www.instagram.com/p/abc/,https://www.instagram.com/p/def/"]
 */
function mnsk7_instagram_feed_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'posts' => '',
			'limit' => 3,
			'title' => __( 'Instagram @mnsk7tools', 'mnsk7-tools' ),
		),
		$atts,
		'mnsk7_instagram_feed'
	);

	$limit = max( 1, min( 6, (int) $atts['limit'] ) );
	$posts = array_filter( array_map( 'trim', explode( ',', (string) $atts['posts'] ) ) );
	if ( ! empty( $posts ) ) {
		$posts = array_slice( $posts, 0, $limit );
	}

	$html  = '<section class="mnsk7-instagram-feed">';
	$html .= '<h4 class="mnsk7-instagram-feed__title">' . esc_html( $atts['title'] ) . '</h4>';

	if ( ! empty( $posts ) ) {
		$html .= '<div class="mnsk7-instagram-feed__grid">';
		foreach ( $posts as $post_url ) {
			$post_url = esc_url( $post_url );
			if ( $post_url === '' ) {
				continue;
			}
			$embed = wp_oembed_get( $post_url );
			$html .= '<div class="mnsk7-instagram-feed__item">';
			if ( $embed ) {
				$html .= $embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				$html .= '<a href="' . $post_url . '" target="_blank" rel="noopener">' . esc_html__( 'Zobacz post na Instagramie', 'mnsk7-tools' ) . '</a>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';
	} else {
		$html .= '<p class="mnsk7-instagram-feed__fallback">' . esc_html__( 'Najświeższe posty znajdziesz na naszym profilu Instagram.', 'mnsk7-tools' ) . '</p>';
	}

	$html .= '<p class="mnsk7-instagram-feed__cta"><a href="' . esc_url( MNK7_INSTAGRAM_URL ) . '" target="_blank" rel="noopener">@mnsk7tools</a></p>';
	$html .= '</section>';

	return $html;
}

/**
 * Allegro trust block (shortcode).
 * Użycie:
 * [mnsk7_allegro_trust]
 */
function mnsk7_allegro_trust_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'seller'              => 'mnsk7-tools_pl',
			'recommendation'      => '100%',
			'positive'            => '383',
			'negative'            => '0',
			'on_allegro'          => 'od 1 roku i 8 miesięcy',
			'title'               => __( 'Super sprzedawca na Allegro', 'mnsk7-tools' ),
			'cta'                 => __( 'Zobacz wszystkie oceny i komentarze', 'mnsk7-tools' ),
			'url'                 => MNK7_ALLEGRO_SELLER_URL,
		),
		$atts,
		'mnsk7_allegro_trust'
	);

	$html  = '<section class="mnsk7-allegro-trust">';
	$html .= '<h4 class="mnsk7-allegro-trust__title">' . esc_html( $atts['title'] ) . '</h4>';
	$html .= '<p class="mnsk7-allegro-trust__seller"><strong>' . esc_html( $atts['seller'] ) . '</strong> — ' . esc_html( $atts['on_allegro'] ) . '</p>';
	$html .= '<ul class="mnsk7-allegro-trust__stats">';
	$html .= '<li><strong>' . esc_html( $atts['recommendation'] ) . '</strong> ' . esc_html__( 'kupujących poleca sprzedawcę', 'mnsk7-tools' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Oceny pozytywne:', 'mnsk7-tools' ) . ' <strong>' . esc_html( $atts['positive'] ) . '</strong></li>';
	$html .= '<li>' . esc_html__( 'Oceny negatywne:', 'mnsk7-tools' ) . ' <strong>' . esc_html( $atts['negative'] ) . '</strong></li>';
	$html .= '<li>' . esc_html__( 'Wszystkie opinie są potwierdzone zakupem na Allegro.', 'mnsk7-tools' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="mnsk7-allegro-trust__cta"><a href="' . esc_url( $atts['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $atts['cta'] ) . '</a></p>';
	$html .= '</section>';

	return $html;
}

/**
 * CTA-przycisk "Czytaj wszystkie opinie na Allegro" (zastępuje siatkę linków page 1..N).
 */
function mnsk7_allegro_reviews_pages_html( $atts = array() ) {
	$url  = esc_url( MNK7_ALLEGRO_SELLER_URL . '/oceny' );
	$html = '<p class="mnsk7-allegro-reviews__cta-wrap">';
	$html .= '<a class="mnsk7-allegro-reviews__cta-btn" href="' . $url . '" target="_blank" rel="noopener nofollow">';
	$html .= esc_html__( 'Czytaj wszystkie opinie na Allegro →', 'mnsk7-tools' );
	$html .= '</a>';
	$html .= '</p>';
	return $html;
}

/**
 * Cytaty z opinii Allegro — uzupełnij prawdziwymi opiniami kupujących.
 * Możesz też nadpisać filtrem: add_filter( 'mnsk7_allegro_review_quotes', fn($q) => [...] );
 */
function mnsk7_allegro_review_quotes() {
	$quotes = array(
		array(
			'text'   => 'Szybka wysyłka, towar zgodny z opisem. Frez naprawdę dobrej jakości — polecam!',
			'author' => 'Kupujący Allegro',
			'rating' => 5,
		),
		array(
			'text'   => 'Super sprzedawca — dostawa na następny dzień, opakowanie solidne, produkt świetny.',
			'author' => 'Kupujący Allegro',
			'rating' => 5,
		),
		array(
			'text'   => 'Frez do aluminium — doskonałe cięcie, brak odprysku, długa żywotność. Będę zamawiał więcej.',
			'author' => 'Kupujący Allegro',
			'rating' => 5,
		),
	);
	return apply_filters( 'mnsk7_allegro_review_quotes', $quotes );
}

/**
 * Shortcode z cytatami opinii + CTA do wszystkich stron ocen.
 * Użycie: [mnsk7_allegro_reviews]
 */
function mnsk7_allegro_reviews_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'title'      => __( 'Opinie kupujących z Allegro', 'mnsk7-tools' ),
			'empty_text' => __( 'Opinie produktowe są aktualnie synchronizowane. Zobacz pełne oceny sprzedawcy na Allegro.', 'mnsk7-tools' ),
			'pages'      => 20,
		),
		$atts,
		'mnsk7_allegro_reviews'
	);

	$quotes = mnsk7_allegro_review_quotes();
	$html   = '<section class="mnsk7-allegro-reviews">';
	$html  .= '<h4 class="mnsk7-allegro-reviews__title">' . esc_html( $atts['title'] ) . '</h4>';

	if ( empty( $quotes ) || ! is_array( $quotes ) ) {
		$html .= '<p class="mnsk7-allegro-reviews__empty">' . esc_html( $atts['empty_text'] ) . '</p>';
		$html .= mnsk7_allegro_reviews_pages_html(
			array(
				'from' => 1,
				'to'   => (int) $atts['pages'],
			)
		);
		$html .= '</section>';
		return $html;
	}

	$html .= '<div class="mnsk7-allegro-reviews__list">';
	foreach ( $quotes as $quote ) {
		$text   = isset( $quote['text'] ) ? sanitize_text_field( (string) $quote['text'] ) : '';
		$author = isset( $quote['author'] ) ? sanitize_text_field( (string) $quote['author'] ) : __( 'Kupujący Allegro', 'mnsk7-tools' );
		if ( $text === '' ) {
			continue;
		}
		$html .= '<blockquote class="mnsk7-allegro-reviews__item">';
		$html .= '<p>' . esc_html( $text ) . '</p>';
		$html .= '<cite>' . esc_html( $author ) . '</cite>';
		$html .= '</blockquote>';
	}
	$html .= '</div>';
	$html .= mnsk7_allegro_reviews_pages_html(
		array(
			'from' => 1,
			'to'   => (int) $atts['pages'],
		)
	);
	$html .= '</section>';

	return $html;
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_dostawa_vat', function () {
		return mnsk7_dostawa_vat_html();
	} );
	add_shortcode( 'mnsk7_contact_info', function () {
		return mnsk7_contact_info_html();
	} );
	add_shortcode( 'mnsk7_delivery_rules', function () {
		return mnsk7_delivery_rules_table_html();
	} );
	add_shortcode( 'mnsk7_delivery_eta', function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'courier' => '',
			),
			$atts,
			'mnsk7_delivery_eta'
		);
		$courier = strtolower( sanitize_text_field( $atts['courier'] ) );
		if ( ! in_array( $courier, array( '', 'inpost', 'dpd' ), true ) ) {
			$courier = '';
		}
		return mnsk7_delivery_eta_html( $courier );
	} );
	add_shortcode( 'mnsk7_instagram_feed', function ( $atts ) {
		return mnsk7_instagram_feed_html( $atts );
	} );
	add_shortcode( 'mnsk7_allegro_trust', function ( $atts ) {
		return mnsk7_allegro_trust_html( $atts );
	} );
	add_shortcode( 'mnsk7_allegro_reviews_pages', function ( $atts ) {
		return mnsk7_allegro_reviews_pages_html( $atts );
	} );
	add_shortcode( 'mnsk7_allegro_reviews', function ( $atts ) {
		return mnsk7_allegro_reviews_html( $atts );
	} );
}, 5 );

// Trust badges (priority 32) already cover "Dostawa jutro" and "Faktura VAT" in the product card.
// mnsk7_dostawa_vat_html() is used in the footer and on the Dostawa page, but not duplicated here.

// Stopka: treść wyświetlana w szablonie footer (tech-storefront), nie w wp_footer.

/**
 * Cookie consent — minimalny pasek (zastępuje zewnętrzny plugin).
 * Po kliknięciu "Przyjmuję" ustawia ciasteczko na 1 rok i chowa pasek.
 */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$text = __( 'Ta strona używa plików cookie. Kliknij „Przyjmuję”, aby kontynuować.', 'mnsk7-tools' );
	$btn = __( 'Przyjmuję', 'mnsk7-tools' );
	?>
	<div id="mnsk7-cookie-bar" class="mnsk7-cookie-bar" role="dialog" aria-label="<?php echo esc_attr__( 'Informacja o cookies', 'mnsk7-tools' ); ?>" hidden>
		<div class="mnsk7-cookie-bar__inner">
			<p class="mnsk7-cookie-bar__text"><?php echo esc_html( $text ); ?></p>
			<button type="button" class="mnsk7-cookie-bar__btn" id="mnsk7-cookie-bar-accept"><?php echo esc_html( $btn ); ?></button>
		</div>
	</div>
	<script>
	(function() {
		var key = 'mnsk7_cookie_ok';
		function get(name) { var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/+^])/g, '\\$1') + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
		function set(name, val, days) { var d = new Date(); d.setTime(d.getTime() + days * 86400000); document.cookie = name + '=' + encodeURIComponent(val) + ';path=/;expires=' + d.toUTCString() + ';SameSite=Lax'; }
		var bar = document.getElementById('mnsk7-cookie-bar');
		if (!bar) return;
		if (get(key)) { bar.remove(); return; }
		bar.removeAttribute('hidden');
		document.getElementById('mnsk7-cookie-bar-accept').addEventListener('click', function() { set(key, '1', 365); bar.remove(); });
	})();
	</script>
	<?php
}, 99 );

/**
 * S2-06: placeholder na schemat parametrów lub wideo w karcie produktu.
 * Gdy dodasz zdjęcie schematu / wideo — wyświetl je tutaj lub usuń wywołanie.
 */
function mnsk7_single_product_schema_video_placeholder() {
	return '';
}

/**
 * Kolejność atrybutów do filtrów katalogu (S2-02). Użyj w pluginie filtrów lub w sidebarze.
 * Typ → średnica → trzpień → długość → zastosowanie.
 */
function mnsk7_get_filter_attribute_order() {
	return array( 'typ', 'srednica', 'fi', 'dlugosc-robocza-h', 'dlugosc-calkowita-l', 'zastosowanie' );
}

/**
 * SEO: Organization + LocalBusiness JSON-LD schema w <head>.
 * Dane statyczne (adres, kontakt) na podstawie REQUIREMENTS / CONTACT_DELIVERY_LOYALTY.md.
 * Produkt JSON-LD obsługuje Yoast SEO — tutaj tylko dane firmy.
 */
add_action( 'wp_head', function () {
	$schema = array(
		'@context' => 'https://schema.org',
		'@type'    => array( 'Organization', 'OnlineStore' ),
		'name'     => 'MNK7 Tools',
		'legalName' => 'MNSK7 SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ',
		'url'      => home_url( '/' ),
		'logo'     => array(
			'@type' => 'ImageObject',
			'url'   => get_site_icon_url( 512 ) ?: home_url( '/wp-content/themes/tech-storefront/assets/images/logo.png' ),
		),
		'contactPoint' => array(
			'@type'             => 'ContactPoint',
			'telephone'         => MNK7_CONTACT_PHONE,
			'email'             => MNK7_CONTACT_EMAIL,
			'contactType'       => 'customer service',
			'availableLanguage' => array( 'Polish' ),
			'hoursAvailable'    => array(
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ),
					'opens'     => '09:00',
					'closes'    => '17:00',
				),
				array(
					'@type'     => 'OpeningHoursSpecification',
					'dayOfWeek' => 'Saturday',
					'opens'     => '10:00',
					'closes'    => '12:00',
				),
			),
		),
		'address' => array(
			'@type'           => 'PostalAddress',
			'streetAddress'   => 'ul. Williama Heerleina Lindleya 16/512',
			'addressLocality' => 'Warszawa',
			'postalCode'      => '02-013',
			'addressCountry'  => 'PL',
		),
		'vatID'          => 'PL5242991741',
		'taxID'          => '5242991741',
		'sameAs'         => array(
			MNK7_INSTAGRAM_URL,
			MNK7_ALLEGRO_SELLER_URL,
		),
		'areaServed'     => array(
			'@type' => 'Country',
			'name'  => 'Poland',
		),
		'description'    => __( 'Sklep z frezami CNC i narzędziami do obróbki drewna, MDF, aluminium, stali i tworzyw sztucznych. Dostawa następnego dnia, faktura VAT.', 'mnsk7-tools' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5 );

/**
 * Shortcode: rating sklepu (S2-09). Placeholder pod Allegro lub przyszłe opinie.
 * Użycie: [mnsk7_rating] lub [mnsk7_rating url="https://allegro.pl/..." title="Nasz sklep"]
 */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_rating', function ( $atts ) {
		$atts = shortcode_atts( array(
			'url'   => '',
			'title' => __( 'Sprawdź opinie o naszym sklepie na Allegro', 'mnsk7-tools' ),
		), $atts, 'mnsk7_rating' );
		$url = esc_url( $atts['url'] );
		if ( $url === '' ) {
			return '<p class="mnsk7-store-rating">' . esc_html( $atts['title'] ) . '</p>';
		}
		return '<p class="mnsk7-store-rating"><a href="' . $url . '" target="_blank" rel="noopener">' . esc_html( $atts['title'] ) . '</a></p>';
	} );
}, 6 );

/**
 * Shortcode: blok popularnych / hitów (S2-07). Na głównej: [mnsk7_bestsellers].
 * Domyślnie 4 produkty po popularności (orderby=popularity); można nadpisać atrybutami.
 */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_bestsellers', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit'   => 4,
			'orderby' => 'popularity',
			'title'   => __( 'Polecane / Bestsellery', 'mnsk7-tools' ),
		), $atts, 'mnsk7_bestsellers' );
		$shortcode = sprintf(
			'[products limit="%d" orderby="%s" columns="4"]',
			(int) $atts['limit'],
			sanitize_key( $atts['orderby'] )
		);
		return '<section class="mnsk7-bestsellers">'
			. '<h2 class="mnsk7-bestsellers-title">' . esc_html( $atts['title'] ) . '</h2>'
			. do_shortcode( $shortcode )
			. '</section>';
	} );
}, 6 );
