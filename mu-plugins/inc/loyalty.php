<?php
/**
 * MNK7 Tools — system rabatów lojalnościowych.
 * Progi roczne, blok w "Moje konto", auto-rabat w koszyku.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

function mnsk7_loyalty_tiers() {
	return array( 1000 => 5, 3000 => 10, 5000 => 15, 10000 => 20 );
}

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
		'date_query'  => array( array(
			'after'     => date( 'Y-m-d H:i:s', $year_start ),
			'before'    => date( 'Y-m-d H:i:s', $year_end ),
			'inclusive' => true,
		) ),
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
		$html .= '<li>' . sprintf( __( '%s zł → %d%%', 'mnsk7-tools' ), number_format_i18n( $thr, 0 ), $pct ) . '</li>';
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

add_action( 'woocommerce_cart_calculate_fees', function ( $cart ) {
	if ( is_admin() && ! defined( 'DOING_AJAX' ) ) {
		return;
	}
	$user_id = get_current_user_id();
	if ( ! $user_id ) {
		return;
	}
	$tier = mnsk7_loyalty_current_tier( mnsk7_get_customer_year_total( $user_id ) );
	if ( $tier['percent'] <= 0 ) {
		return;
	}
	$subtotal = (float) $cart->get_subtotal();
	if ( $subtotal <= 0 ) {
		return;
	}
	$cart->add_fee(
		sprintf( __( 'Rabat lojalnościowy (%d%%)', 'mnsk7-tools' ), $tier['percent'] ),
		-1 * round( $subtotal * $tier['percent'] / 100, 2 ),
		true
	);
}, 20 );
