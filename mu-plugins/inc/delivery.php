<?php
/**
 * MNK7 Tools — dostawa, kontakt, VAT, free shipping notice.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

function mnsk7_dostawa_vat_html() {
	return '<p class="mnsk7-dostawa-vat">'
		. esc_html__( 'Dostawa następnego dnia. Faktura VAT dostępna na życzenie.', 'mnsk7-tools' )
		. '</p>';
}

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
 * Oblicza najwcześniejszą datę dostawy (InPost + DPD, Polska).
 * Reguły: InPost pn.–pt. do 15:00 → nast. dzień; sb. do 11:00 → pon.; DPD pn.–czw. do 17:00 → nast. dzień; pt. do 17:00 → pon.
 *
 * @return string Etykieta: "Dostawa dziś", "Dostawa jutro", "Dostawa w poniedziałek" itd.
 */
function mnsk7_delivery_eta_badge_label() {
	$tz = new DateTimeZone( 'Europe/Warsaw' );
	$now = new DateTime( 'now', $tz );
	$wday = (int) $now->format( 'w' ); // 0 = niedziela, 6 = sobota
	$hour = (int) $now->format( 'G' );
	$today = clone $now;
	$today->setTime( 0, 0, 0 );

	// InPost: pn.–pt. do 15:00 → dostawa dziś; po 15:00 → nast. dzień; pt. po 15:00 → pon.; sb. do 11:00 → pon.; ndz. → pon.
	$inpost = clone $now;
	if ( $wday >= 1 && $wday <= 5 ) {
		if ( $hour < 15 ) {
			$inpost->setTime( 0, 0, 0 ); // dziś
		} else {
			$inpost->modify( $wday === 5 ? '+3 days' : '+1 day' );
		}
	} elseif ( $wday === 6 ) {
		$inpost->modify( '+2 days' ); // sobota → poniedziałek
	} else {
		$inpost->modify( '+1 day' ); // niedziela → poniedziałek
	}

	// DPD: pn.–czw. do 17:00 → nast. dzień; po 17:00 czw. → pt.; pt. (do 17:00 lub po) → pon.; sb. → pon.; ndz. → pon.
	$dpd = clone $now;
	if ( $wday >= 1 && $wday <= 4 ) {
		$dpd->modify( $hour < 17 ? '+1 day' : '+1 day' ); // czw. po 17:00 → piątek (+1)
	} elseif ( $wday === 5 ) {
		$dpd->modify( '+3 days' ); // piątek → poniedziałek
	} elseif ( $wday === 6 ) {
		$dpd->modify( '+2 days' );
	} else {
		$dpd->modify( '+1 day' );
	}

	$inpost->setTime( 0, 0, 0 );
	$dpd->setTime( 0, 0, 0 );
	$earliest = $inpost <= $dpd ? $inpost : $dpd;

	$days = (int) $today->diff( $earliest )->format( '%a' );
	$earliest_wday = (int) $earliest->format( 'w' );

	if ( $days === 0 ) {
		return __( 'Dostawa dziś', 'mnsk7-tools' );
	}
	if ( $days === 1 ) {
		return __( 'Dostawa jutro', 'mnsk7-tools' );
	}

	$weekdays = array(
		0 => __( 'niedzielę', 'mnsk7-tools' ),
		1 => __( 'poniedziałek', 'mnsk7-tools' ),
		2 => __( 'wtorek', 'mnsk7-tools' ),
		3 => __( 'środę', 'mnsk7-tools' ),
		4 => __( 'czwartek', 'mnsk7-tools' ),
		5 => __( 'piątek', 'mnsk7-tools' ),
		6 => __( 'sobotę', 'mnsk7-tools' ),
	);
	$day_name = isset( $weekdays[ $earliest_wday ] ) ? $weekdays[ $earliest_wday ] : $earliest->format( 'd.m' );
	return sprintf( __( 'Dostawa w %s', 'mnsk7-tools' ), $day_name );
}

function mnsk7_delivery_rules_table_html() {
	$rows = array(
		array( 'courier' => 'InPost', 'order' => __( 'pn.–pt. do 15:00', 'mnsk7-tools' ), 'result' => __( 'dostawa tego samego dnia', 'mnsk7-tools' ) ),
		array( 'courier' => 'InPost', 'order' => __( 'pn.–pt. po 15:00', 'mnsk7-tools' ), 'result' => __( 'dostawa następnego dnia', 'mnsk7-tools' ) ),
		array( 'courier' => 'InPost', 'order' => __( 'sb. do 11:00', 'mnsk7-tools' ),    'result' => __( 'dostawa w poniedziałek', 'mnsk7-tools' ) ),
		array( 'courier' => 'DPD',    'order' => __( 'pn.–czw. do 17:00', 'mnsk7-tools' ), 'result' => __( 'dostawa następnego dnia', 'mnsk7-tools' ) ),
		array( 'courier' => 'DPD',    'order' => __( 'pt. do 17:00', 'mnsk7-tools' ),    'result' => __( 'dostawa w poniedziałek', 'mnsk7-tools' ) ),
	);
	$html  = '<div class="mnsk7-delivery-rules">';
	$html .= '<h4 class="mnsk7-delivery-rules__title">' . esc_html__( 'Orientacyjny czas dostawy (Polska)', 'mnsk7-tools' ) . '</h4>';
	$html .= '<table class="mnsk7-delivery-rules__table"><thead><tr>'
		. '<th>' . esc_html__( 'Kurier', 'mnsk7-tools' ) . '</th>'
		. '<th>' . esc_html__( 'Kiedy zamówisz', 'mnsk7-tools' ) . '</th>'
		. '<th>' . esc_html__( 'Dostawa', 'mnsk7-tools' ) . '</th>'
		. '</tr></thead><tbody>';
	foreach ( $rows as $row ) {
		$html .= '<tr><td>' . esc_html( $row['courier'] ) . '</td><td>' . esc_html( $row['order'] ) . '</td><td>' . esc_html( $row['result'] ) . '</td></tr>';
	}
	$html .= '</tbody></table>';
	$html .= '<p class="mnsk7-delivery-rules__free">' . esc_html__( 'Darmowa dostawa od 300 zł.', 'mnsk7-tools' ) . '</p>';
	$html .= '</div>';
	return $html;
}

function mnsk7_estimated_delivery_text( $courier ) {
	$hour = (int) current_time( 'G' );
	$wday = (int) current_time( 'w' );
	if ( $courier === 'inpost' ) {
		if ( $wday >= 1 && $wday <= 5 ) {
			return $hour < 15
				? __( 'InPost: zamów do 15:00 — dostawa następnego dnia.', 'mnsk7-tools' )
				: __( 'InPost: zamówienie po 15:00 — dostawa zwykle w najbliższy dzień roboczy.', 'mnsk7-tools' );
		}
		return $wday === 6
			? __( 'InPost: sobota do 11:00 — dostawa w poniedziałek.', 'mnsk7-tools' )
			: __( 'InPost: zamówienie w niedzielę — wysyłka od poniedziałku.', 'mnsk7-tools' );
	}
	if ( $courier === 'dpd' ) {
		if ( $wday >= 1 && $wday <= 4 ) {
			return $hour < 17
				? __( 'DPD: zamów do 17:00 — dostawa następnego dnia.', 'mnsk7-tools' )
				: __( 'DPD: zamówienie po 17:00 — dostawa zwykle w najbliższy dzień roboczy.', 'mnsk7-tools' );
		}
		return $wday === 5
			? __( 'DPD: piątek do 17:00 — dostawa w poniedziałek.', 'mnsk7-tools' )
			: __( 'DPD: zamówienie w weekend — wysyłka od poniedziałku.', 'mnsk7-tools' );
	}
	return __( 'Dostawa: wybierz InPost lub DPD, aby zobaczyć orientacyjny termin.', 'mnsk7-tools' );
}

function mnsk7_detect_selected_courier() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return '';
	}
	$methods = WC()->session->get( 'chosen_shipping_methods', array() );
	if ( empty( $methods ) ) {
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

function mnsk7_delivery_eta_html( $courier = '' ) {
	if ( $courier === '' ) {
		$courier = mnsk7_detect_selected_courier();
	}
	return '<p class="mnsk7-delivery-eta">' . esc_html( mnsk7_estimated_delivery_text( $courier ) ) . '</p>';
}

/* Free shipping notice w koszyku i checkoucie */
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
	wc_print_notice( sprintf(
		__( 'Darmowa dostawa od %1$s zł. Do gratisowej dostawy brakuje Ci %2$s zł.', 'mnsk7-tools' ),
		number_format_i18n( $min, 0 ),
		number_format_i18n( $min - $total, 2 )
	), 'notice' );
}
