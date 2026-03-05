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

function mnsk7_delivery_rules_table_html() {
	$rows = array(
		array( 'courier' => 'InPost', 'order' => __( 'pn.–pt. do 15:00', 'mnsk7-tools' ), 'result' => __( 'dostawa następnego dnia', 'mnsk7-tools' ) ),
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
