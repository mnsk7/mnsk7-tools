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
		. esc_html__( '🚚 Zamówienia złożone do 15:00 (InPost) lub 12:00 (DPD) wysyłamy tego samego dnia.', 'mnsk7-tools' )
		. '</p>';
}, 5 );

/**
 * Faktura VAT — NIP wymagany, gdy klient zaznaczy „Chcę fakturę VAT”.
 *
 * Geneza (zamówienie #48078): klient zaznaczył fakturę, ale pole NIP było puste.
 * BaseLinker pobiera zamówienia z WooCommerce i ustawia `want_invoice` na podstawie
 * wypełnionego pola NIP — przy pustym NIP intencja faktury ginie podczas eksportu.
 *
 * Pola pochodzą z wtyczki Flexible Checkout Fields (nie edytujemy plików wtyczki):
 *   - checkbox: billing_chce_fakture_vat  (meta `_billing_chce_fakture_vat`, wartość „Tak”)
 *   - NIP:      billing_nip               (meta `_billing_nip`)
 */

if ( ! defined( 'MNSK7_INVOICE_FLAG_FIELD' ) ) {
	define( 'MNSK7_INVOICE_FLAG_FIELD', 'billing_chce_fakture_vat' );
}
if ( ! defined( 'MNSK7_INVOICE_NIP_FIELD' ) ) {
	define( 'MNSK7_INVOICE_NIP_FIELD', 'billing_nip' );
}

if ( ! function_exists( 'mnsk7_checkout_wants_invoice' ) ) {
	/**
	 * Czy wartość pola „Chcę fakturę VAT” oznacza, że klient chce fakturę.
	 *
	 * @param mixed $value Surowa wartość pola (checkbox FCF zwraca „Tak”).
	 * @return bool
	 */
	function mnsk7_checkout_wants_invoice( $value ) {
		if ( is_array( $value ) ) {
			$value = implode( '', $value );
		}
		$value = strtolower( trim( (string) wp_unslash( $value ) ) );
		if ( '' === $value ) {
			return false;
		}
		return ! in_array( $value, array( 'nie', 'no', '0', 'false', 'off' ), true );
	}
}

if ( ! function_exists( 'mnsk7_checkout_normalize_nip' ) ) {
	/**
	 * Zostawia same cyfry z NIP (PL NIP = 10 cyfr; usuwa „PL”, myślniki, spacje).
	 *
	 * @param mixed $nip Surowa wartość pola NIP.
	 * @return string
	 */
	function mnsk7_checkout_normalize_nip( $nip ) {
		$nip = (string) wp_unslash( $nip );
		return preg_replace( '/\D+/', '', $nip );
	}
}

/**
 * Walidacja serwerowa: NIP obowiązkowy przy zaznaczonej fakturze VAT.
 */
add_action(
	'woocommerce_after_checkout_validation',
	function ( $data, $errors ) {
		$flag_field = MNSK7_INVOICE_FLAG_FIELD;
		$nip_field  = MNSK7_INVOICE_NIP_FIELD;

		$flag_value = '';
		if ( is_array( $data ) && isset( $data[ $flag_field ] ) ) {
			$flag_value = $data[ $flag_field ];
		} elseif ( isset( $_POST[ $flag_field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$flag_value = wp_unslash( $_POST[ $flag_field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( ! mnsk7_checkout_wants_invoice( $flag_value ) ) {
			return;
		}

		$nip_raw = '';
		if ( is_array( $data ) && isset( $data[ $nip_field ] ) ) {
			$nip_raw = $data[ $nip_field ];
		} elseif ( isset( $_POST[ $nip_field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$nip_raw = wp_unslash( $_POST[ $nip_field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}

		if ( '' === trim( (string) $nip_raw ) ) {
			$errors->add(
				'billing_nip_required',
				__( 'Zaznaczono „Chcę fakturę VAT”, więc numer NIP jest wymagany. Podaj NIP albo odznacz fakturę.', 'mnsk7-tools' )
			);
			return;
		}

		if ( 10 !== strlen( mnsk7_checkout_normalize_nip( $nip_raw ) ) ) {
			$errors->add(
				'billing_nip_invalid',
				__( 'Numer NIP powinien zawierać 10 cyfr. Sprawdź wpisany NIP.', 'mnsk7-tools' )
			);
		}
	},
	10,
	2
);

/**
 * Utrwalenie intencji faktury + notatka dla obsługi/BaseLinker.
 *
 * Pola FCF zapisują się same, ale dokładamy znormalizowaną flagę i notatkę,
 * dzięki czemu intencja faktury jest jednoznaczna nawet gdyby układ pól się zmienił.
 * Uruchamiamy po zapisaniu zamówienia, aby `add_order_note()` miało ID zamówienia.
 */
add_action(
	'woocommerce_checkout_order_processed',
	function ( $order_id, $posted_data, $order = null ) {
		if ( ! $order instanceof WC_Order ) {
			$order = wc_get_order( $order_id );
		}
		if ( ! $order instanceof WC_Order ) {
			return;
		}

		$flag_field = MNSK7_INVOICE_FLAG_FIELD;
		$nip_field  = MNSK7_INVOICE_NIP_FIELD;

		$flag_value = '';
		if ( is_array( $posted_data ) && isset( $posted_data[ $flag_field ] ) ) {
			$flag_value = $posted_data[ $flag_field ];
		} elseif ( isset( $_POST[ $flag_field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$flag_value = wp_unslash( $_POST[ $flag_field ] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		if ( ! mnsk7_checkout_wants_invoice( $flag_value ) ) {
			return;
		}

		$nip_raw = '';
		if ( is_array( $posted_data ) && isset( $posted_data[ $nip_field ] ) ) {
			$nip_raw = sanitize_text_field( (string) $posted_data[ $nip_field ] );
		} elseif ( isset( $_POST[ $nip_field ] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Missing
			$nip_raw = sanitize_text_field( wp_unslash( $_POST[ $nip_field ] ) ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
		}
		$nip_digits = mnsk7_checkout_normalize_nip( $nip_raw );

		$order->update_meta_data( '_mnsk7_invoice_requested', 'yes' );
		if ( '' !== $nip_digits ) {
			$order->update_meta_data( '_mnsk7_invoice_nip', $nip_digits );
		}
		$order->save();

		$order->add_order_note(
			sprintf(
				/* translators: %s: NIP number. */
				__( 'Klient poprosił o fakturę VAT. NIP: %s', 'mnsk7-tools' ),
				'' !== $nip_raw ? $nip_raw : __( '(brak)', 'mnsk7-tools' )
			)
		);
	},
	20,
	3
);

/**
 * UX: oznacz pole NIP jako wymagane, gdy zaznaczono fakturę VAT (klient widzi gwiazdkę).
 * Twardą bramką pozostaje walidacja serwerowa powyżej.
 */
add_action(
	'wp_footer',
	function () {
		if ( ! function_exists( 'is_checkout' ) || ! is_checkout() || is_order_received_page() ) {
			return;
		}
		$flag_field = esc_js( MNSK7_INVOICE_FLAG_FIELD );
		$nip_field  = esc_js( MNSK7_INVOICE_NIP_FIELD );
		?>
<script id="mnsk7-invoice-nip-required">
(function(){
	var FLAG = '<?php echo $flag_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
	var NIP  = '<?php echo $nip_field; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>';
	function wantsInvoice(el){
		if(!el){return false;}
		if(el.type === 'checkbox' || el.type === 'radio'){return el.checked;}
		var v = (el.value || '').toLowerCase();
		return v !== '' && v !== 'nie' && v !== 'no' && v !== '0';
	}
	function sync(){
		var flag = document.getElementById(FLAG);
		var nip = document.getElementById(NIP);
		var wrap = document.getElementById(NIP + '_field');
		if(!flag || !nip){return;}
		var on = wantsInvoice(flag);
		if(on){
			nip.setAttribute('required','required');
			nip.setAttribute('aria-required','true');
		}else{
			nip.removeAttribute('required');
			nip.setAttribute('aria-required','false');
		}
		if(wrap){
			wrap.classList.toggle('validate-required', on);
			var label = wrap.querySelector('label');
			if(label){
				var ab = label.querySelector('.required');
				if(on && !ab){
					var s = document.createElement('abbr');
					s.className = 'required';
					s.title = 'wymagane';
					s.textContent = ' *';
					label.appendChild(s);
				}else if(!on && ab){
					ab.parentNode.removeChild(ab);
				}
			}
		}
	}
	function bind(){
		var flag = document.getElementById(FLAG);
		if(flag && !flag.dataset.mnsk7Bound){
			flag.dataset.mnsk7Bound = '1';
			flag.addEventListener('change', sync);
			flag.addEventListener('input', sync);
		}
		sync();
	}
	if(document.readyState !== 'loading'){bind();}else{document.addEventListener('DOMContentLoaded', bind);}
	if(window.jQuery){ jQuery(document.body).on('updated_checkout', bind); }
})();
</script>
		<?php
	},
	99
);
