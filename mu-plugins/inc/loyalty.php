<?php
/**
 * MNK7 Tools — system rabatów lojalnościowych.
 * 5% po rejestracji (stały, zalogowany), 10% od 5 000 zł/rok, 15% (max) od 10 000 zł/rok.
 * Koszyk: dla gości bez rabatu; dla zalogowanych fee wg sumy zamówień roku + wartość koszyka.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Próg zakupów w roku → procent rabatu (klucz 0 = baza dla kont klientów).
 *
 * @return array<int,int>
 */
function mnsk7_loyalty_tiers() {
	return array( 0 => 5, 5000 => 10, 10000 => 15 );
}

/**
 * Linia wyjaśniająca próg dla list (<ul>), HTML escapowany.
 *
 * @param int $thr Prog zakupów rocznych (0 dla rabatu bazowego).
 * @param int $pct Procent rabatu.
 */
function mnsk7_loyalty_tier_public_line_html( $thr, $pct ) {
	$pct = absint( $pct );
	$thr = (int) $thr;
	if ( 0 === $thr ) {
		return esc_html(
			sprintf(
				/* translators: %d loyalty base discount */
				__( '%d%% po rejestracji — stały rabat na koncie klienta', 'mnsk7-tools' ),
				$pct
			)
		);
	}
	return esc_html(
		sprintf(
			/* translators: 1 yearly spend threshold (PLN, formatted), 2 discount percent */
			__( 'od %1$s zł łącznie w roku → %2$d%%', 'mnsk7-tools' ),
			number_format_i18n( $thr, 0 ),
			$pct
		)
	);
}

function mnsk7_get_customer_year_total( $user_id = 0 ) {
	if ( ! $user_id ) {
		$user_id = get_current_user_id();
	}
	if ( ! $user_id || ! function_exists( 'wc_get_orders' ) ) {
		return 0.0;
	}
	$year   = date( 'Y' );
	$from   = $year . '-01-01';
	$to     = $year . '-12-31';
	$orders = wc_get_orders( array(
		'customer_id'  => $user_id,
		'status'       => array( 'wc-completed' ),
		'date_created' => $from . '...' . $to,
		'return'       => 'ids',
		'limit'        => -1,
	) );
	if ( ! is_array( $orders ) ) {
		$orders = array();
	}
	$total = 0.0;
	$ts_from = strtotime( $from . ' 00:00:00' );
	$ts_to   = strtotime( $to . ' 23:59:59' );
	foreach ( $orders as $order_id ) {
		$order = wc_get_order( $order_id );
		if ( ! $order || ! is_a( $order, 'WC_Order' ) ) {
			continue;
		}
		$date_created = $order->get_date_created();
		if ( $date_created && ( $date_created->getTimestamp() < $ts_from || $date_created->getTimestamp() > $ts_to ) ) {
			continue;
		}
		$total += (float) $order->get_total();
	}
	return round( $total, 2 );
}

function mnsk7_loyalty_current_tier( $total ) {
	$tiers       = mnsk7_loyalty_tiers();
	$sorted      = array_keys( $tiers );
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
	$html .= '<p class="mnsk7-loyalty-block__sum">' . sprintf(
		__( 'W %1$s roku zamówiłeś za %2$s zł.', 'mnsk7-tools' ),
		$year, number_format_i18n( $total, 2 )
	) . '</p>';
	$html .= '<p class="mnsk7-loyalty-block__pct">' . sprintf(
		__( 'Twój aktualny rabat: %d%%.', 'mnsk7-tools' ),
		$tier['percent']
	) . '</p>';
	if ( $tier['next_at'] !== null && $tier['lack'] !== null ) {
		$html .= '<p class="mnsk7-loyalty-block__next">' . sprintf(
			__( 'Do rabatu %3$d%% brakuje %1$s zł (próg %2$s zł).', 'mnsk7-tools' ),
			number_format_i18n( $tier['lack'], 2 ),
			number_format_i18n( $tier['next_at'], 0 ),
			$tier['next_pct']
		) . '</p>';
	}
	$html .= '<ul class="mnsk7-loyalty-block__tiers">';
	foreach ( $tiers as $thr => $pct ) {
		$html .= '<li>' . mnsk7_loyalty_tier_public_line_html( (int) $thr, $pct ) . '</li>';
	}
	$html .= '</ul></div>';
	return $html;
}

add_action( 'woocommerce_account_dashboard', function () {
	echo mnsk7_loyalty_block_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 15 );

add_action( 'init', function () {
	add_shortcode( 'mnsk7_loyalty', function () {
		return is_user_logged_in() ? mnsk7_loyalty_block_html() : '';
	} );
}, 6 );

/**
 * Suma do wyliczenia progu: dla gościa = wartość koszyka, dla zalogowanego = suma roku + koszyk (ta zamówienie też liczy się do progu).
 */
function mnsk7_loyalty_total_for_tier( $cart = null ) {
	if ( ! $cart && function_exists( 'WC' ) && WC()->cart ) {
		$cart = WC()->cart;
	}
	$subtotal = $cart ? (float) $cart->get_subtotal() : 0;
	$user_id  = get_current_user_id();
	if ( $user_id && function_exists( 'mnsk7_get_customer_year_total' ) ) {
		return mnsk7_get_customer_year_total( $user_id ) + $subtotal;
	}
	return $subtotal;
}

add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	if ( ! apply_filters( 'mnsk7_loyalty_apply_discount', true ) ) {
		return;
	}
	if ( ! is_user_logged_in() ) {
		return;
	}
	$total_for_tier = mnsk7_loyalty_total_for_tier( $cart );
	$tier           = mnsk7_loyalty_current_tier( $total_for_tier );
	if ( $tier['percent'] <= 0 ) {
		return;
	}
	$subtotal = (float) $cart->get_subtotal();
	if ( $subtotal <= 0 ) {
		return;
	}
	$amount = -1 * round( $subtotal * $tier['percent'] / 100, 2 );
	$cart->add_fee(
		sprintf( __( 'Rabat lojalnościowy (%d%%)', 'mnsk7-tools' ), $tier['percent'] ),
		$amount,
		true
	);
}, 20 );

/**
 * Blok systemu lojalności w koszyku: aktualny poziom, ile brakuje do następnego, progi.
 */
function mnsk7_loyalty_cart_block_html() {
	if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
		return '';
	}
	$subtotal   = (float) WC()->cart->get_subtotal();
	$user_id    = get_current_user_id();
	$year_total = $user_id && function_exists( 'mnsk7_get_customer_year_total' ) ? mnsk7_get_customer_year_total( $user_id ) : 0;

	/* Gość: żadnego „sztucznego” progu z koszykiem rabat nie przysługuje dopóki brak konta */
	$total_for_tier = $user_id ? mnsk7_loyalty_total_for_tier() : 0;
	$tier   = $user_id ? mnsk7_loyalty_current_tier( $total_for_tier ) : array(
		'percent'  => 0,
		'next_at'  => null,
		'lack'     => null,
		'next_pct' => null,
	);

	$tiers = mnsk7_loyalty_tiers();
	$year  = date( 'Y' );

	$html = '<div class="mnsk7-cart-loyalty">';
	$html .= '<h3 class="mnsk7-cart-loyalty__title">' . esc_html__( 'Rabaty dla stałych klientów — oszczędzaj przy każdym zamówieniu', 'mnsk7-tools' ) . '</h3>';
	if ( $user_id && $year_total > 0 ) {
		$html .= '<p class="mnsk7-cart-loyalty__sum">' . sprintf(
			__( 'W %1$s zamówiłeś za %2$s zł. Wartość tego koszyka: %3$s zł.', 'mnsk7-tools' ),
			$year,
			number_format_i18n( $year_total, 2 ),
			number_format_i18n( $subtotal, 2 )
		) . '</p>';
	} elseif ( $user_id ) {
		$html .= '<p class="mnsk7-cart-loyalty__sum">' . sprintf(
			__( 'Wartość koszyka: %s zł. Stały rabat 5%% na koncie po rejestracji; wyżej: 10%% od 5 000 zł i 15%% od 10 000 zł łącznie w roku (zamówienia zrealizowane).', 'mnsk7-tools' ),
			number_format_i18n( $subtotal, 2 )
		) . '</p>';
	} else {
		$myaccount_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/moje-konto/' );
		$html .= '<p class="mnsk7-cart-loyalty__sum">' . sprintf(
			__( 'Wartość koszyka: %s zł.', 'mnsk7-tools' ),
			number_format_i18n( $subtotal, 2 )
		) . '</p>';
		$html .= '<p class="mnsk7-cart-loyalty__guest-cta"><a href="' . esc_url( $myaccount_url ) . '" class="mnsk7-cart-loyalty__cta-link">' . esc_html(
			sprintf(
				/* translators: %d maximal loyalty discount */
				__( 'Zaloguj się — dla kont stały rabat 5%%; maksymalnie %d%% przy wyższej sumie zakupów w roku.', 'mnsk7-tools' ),
				15
			)
		) . '</a></p>';
	}
	if ( $tier['percent'] > 0 ) {
		$html .= '<p class="mnsk7-cart-loyalty__pct mnsk7-cart-loyalty__pct--active">' . sprintf(
			__( 'Twój rabat na to zamówienie: %d%%.', 'mnsk7-tools' ),
			$tier['percent']
		) . '</p>';
	}
	if ( $tier['next_at'] !== null && $tier['lack'] !== null ) {
		$html .= '<p class="mnsk7-cart-loyalty__next">' . sprintf(
			__( 'Do rabatu %3$d%% brakuje %1$s zł (próg %2$s zł).', 'mnsk7-tools' ),
			number_format_i18n( $tier['lack'], 2 ),
			number_format_i18n( $tier['next_at'], 0 ),
			$tier['next_pct']
		) . '</p>';
	}
	$html .= '<ul class="mnsk7-cart-loyalty__tiers" aria-label="' . esc_attr__( 'Progi rabatowe', 'mnsk7-tools' ) . '">';
	foreach ( $tiers as $thr => $pct ) {
		$thr     = (int) $thr;
		$reached = $user_id ? ( $total_for_tier >= $thr ) : false;
		if ( $reached ) {
			$html .= '<li class="mnsk7-cart-loyalty__tier--reached">';
		} else {
			$html .= '<li>';
		}
		$html .= mnsk7_loyalty_tier_public_line_html( $thr, $pct ) . '</li>';
	}
	$html .= '</ul></div>';
	return $html;
}

add_action( 'woocommerce_before_cart_collaterals', function () {
	echo mnsk7_loyalty_cart_block_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 8 );
