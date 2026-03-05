<?php
/**
 * MNK7 Tools — UX checkout: etykiety PL, nota o dostawie.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

add_filter( 'woocommerce_checkout_fields', function ( $fields ) {
	$billing = array(
		'billing_first_name' => array( 'label' => 'Imię',              'placeholder' => 'Jan' ),
		'billing_last_name'  => array( 'label' => 'Nazwisko',          'placeholder' => 'Kowalski' ),
		'billing_company'    => array( 'label' => 'Firma (opcjonalnie)', 'placeholder' => 'Nazwa firmy' ),
		'billing_address_1'  => array( 'label' => 'Ulica i numer',     'placeholder' => 'ul. Przykładowa 1/2' ),
		'billing_city'       => array( 'label' => 'Miasto',            'placeholder' => 'Warszawa' ),
		'billing_postcode'   => array( 'label' => 'Kod pocztowy',      'placeholder' => '00-000' ),
		'billing_phone'      => array( 'label' => 'Telefon',           'placeholder' => '+48 500 000 000', 'description' => 'Potrzebny do powiadomień o dostawie.' ),
		'billing_email'      => array( 'label' => 'E-mail',            'placeholder' => 'adres@email.pl',  'description' => 'Na ten adres wyślemy potwierdzenie zamówienia.' ),
		'billing_nip'        => array( 'label' => 'NIP (jeśli chcesz fakturę VAT)', 'placeholder' => '000-000-00-00' ),
	);
	foreach ( $billing as $key => $values ) {
		if ( isset( $fields['billing'][ $key ] ) ) {
			foreach ( $values as $prop => $val ) {
				$fields['billing'][ $key ][ $prop ] = $val;
			}
		}
	}
	if ( isset( $fields['order']['order_comments'] ) ) {
		$fields['order']['order_comments']['label']       = 'Uwagi do zamówienia';
		$fields['order']['order_comments']['placeholder'] = 'Np. prośba o konkretny czas dostawy, nr paczkomatu itp.';
	}
	return $fields;
} );

add_action( 'woocommerce_review_order_before_submit', function () {
	echo '<p class="mnsk7-checkout-delivery-note">'
		. esc_html__( '🚚 Zamówienia złożone do 15:00 (InPost) lub 17:00 (DPD) wysyłamy tego samego dnia.', 'mnsk7-tools' )
		. '</p>';
}, 5 );
