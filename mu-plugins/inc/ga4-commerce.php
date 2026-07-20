<?php
/**
 * GA4: consent-aware User-ID and server-side purchase events.
 *
 * The Measurement Protocol secret is deliberately read from the production
 * environment or wp-config.php, never from this repository.
 */

defined( 'ABSPATH' ) || exit;

const MNSK7_GA4_MEASUREMENT_ID = 'G-WL18DC0S1Z';

function mnsk7_ga4_measurement_protocol_secret() {
	if ( defined( 'MNSK7_GA4_MEASUREMENT_PROTOCOL_API_SECRET' ) ) {
		return (string) MNSK7_GA4_MEASUREMENT_PROTOCOL_API_SECRET;
	}

	return (string) getenv( 'GA4_MEASUREMENT_PROTOCOL_API_SECRET' );
}

function mnsk7_ga4_client_id_from_cookie() {
	if ( empty( $_COOKIE['_ga'] ) || ! is_string( $_COOKIE['_ga'] ) ) {
		return '';
	}

	$cookie = sanitize_text_field( wp_unslash( $_COOKIE['_ga'] ) );
	if ( preg_match( '/^GA\d+\.\d+\.(\d+\.\d+)$/', $cookie, $matches ) ) {
		return $matches[1];
	}

	return '';
}

/** Store attribution while the checkout request still has the visitor's cookies. */
function mnsk7_ga4_store_order_attribution( $order ) {
	if ( ! $order instanceof WC_Order || $order->get_meta( '_mnsk7_ga4_client_id', true ) ) {
		return;
	}

	$client_id = mnsk7_ga4_client_id_from_cookie();
	if ( '' === $client_id ) {
		return;
	}

	$order->update_meta_data( '_mnsk7_ga4_client_id', $client_id );
}

add_action( 'woocommerce_checkout_create_order', 'mnsk7_ga4_store_order_attribution', 10, 1 );
add_action( 'woocommerce_store_api_checkout_update_order_meta', 'mnsk7_ga4_store_order_attribution', 10, 1 );

function mnsk7_ga4_purchase_items( WC_Order $order ) {
	$items = array();

	foreach ( $order->get_items( 'line_item' ) as $item ) {
		$product = $item->get_product();
		if ( ! $product instanceof WC_Product ) {
			continue;
		}

		$product_id = $product->get_id();
		$item_data  = array(
			'item_id'   => $product->get_sku() ? $product->get_sku() : (string) $product_id,
			'item_name' => wp_strip_all_tags( $product->get_name() ),
			'price'     => (float) $order->get_item_total( $item, false, false ),
			'quantity'  => (int) $item->get_quantity(),
		);

		$categories = wc_get_product_category_list( $product_id, '|' );
		if ( $categories ) {
			$item_data['item_category'] = wp_strip_all_tags( explode( '|', $categories )[0] );
		}

		$items[] = $item_data;
	}

	return $items;
}

/**
 * Send exactly one purchase after WooCommerce confirms payment.
 * GA4 also deduplicates ecommerce purchases by transaction_id.
 */
function mnsk7_ga4_send_purchase( $order_id ) {
	$order = wc_get_order( $order_id );
	if ( ! $order instanceof WC_Order || ! $order->is_paid() ) {
		return;
	}
	if ( $order->get_meta( '_mnsk7_ga4_purchase_sent', true ) ) {
		return;
	}

	$secret    = mnsk7_ga4_measurement_protocol_secret();
	$client_id = (string) $order->get_meta( '_mnsk7_ga4_client_id', true );
	$items     = mnsk7_ga4_purchase_items( $order );
	if ( '' === $secret || '' === $client_id || empty( $items ) ) {
		return;
	}

	$purchase = array(
		'transaction_id' => (string) $order->get_order_number(),
		'currency'       => $order->get_currency(),
		'value'          => (float) $order->get_total(),
		'tax'            => (float) $order->get_total_tax(),
		'shipping'       => (float) $order->get_shipping_total(),
		'items'          => $items,
	);
	$payload = array(
		'client_id' => $client_id,
		'events'    => array(
			array(
				'name'   => 'purchase',
				'params' => $purchase,
			),
		),
	);

	if ( $order->get_customer_id() ) {
		$payload['user_id'] = 'wc_' . (int) $order->get_customer_id();
	}

	$response = wp_remote_post(
		'https://www.google-analytics.com/mp/collect?measurement_id=' . rawurlencode( MNSK7_GA4_MEASUREMENT_ID ) . '&api_secret=' . rawurlencode( $secret ),
		array(
			'timeout' => 5,
			'headers' => array( 'Content-Type' => 'application/json' ),
			'body'    => wp_json_encode( $payload ),
		)
	);

	if ( ! is_wp_error( $response ) && wp_remote_retrieve_response_code( $response ) >= 200 && wp_remote_retrieve_response_code( $response ) < 300 ) {
		$order->update_meta_data( '_mnsk7_ga4_purchase_sent', gmdate( 'c' ) );
		$order->save();
	}
}

add_action( 'woocommerce_payment_complete', 'mnsk7_ga4_send_purchase', 20 );
add_action( 'woocommerce_order_status_processing', 'mnsk7_ga4_send_purchase', 20 );
add_action( 'woocommerce_order_status_completed', 'mnsk7_ga4_send_purchase', 20 );

/** Set User-ID for logged-in customers and clear it after logout. */
add_action( 'wp_head', function () {
	if ( is_admin() ) {
		return;
	}
	$user_id = get_current_user_id();
	?>
	<script>
	(function () {
		var userId = <?php echo wp_json_encode( $user_id ? 'wc_' . $user_id : null ); ?>;
		function applyUserId() {
			try {
				window.dataLayer = window.dataLayer || [];
				window.gtag = window.gtag || function(){ window.dataLayer.push(arguments); };
				if (userId) {
					window.gtag('set', { user_id: userId });
					window.localStorage.setItem('mnsk7_ga4_user_id_set', '1');
				} else if (window.localStorage.getItem('mnsk7_ga4_user_id_set') === '1') {
					window.gtag('set', { user_id: null });
					window.localStorage.removeItem('mnsk7_ga4_user_id_set');
				}
			} catch (e) {}
		}
		function accepted() {
			try { return window.localStorage.getItem('mnsk7_cookie_consent') === 'accept'; } catch (e) { return false; }
		}
		if (accepted()) applyUserId();
		document.addEventListener('mnsk7-cookie-consent', function (event) {
			if (event && event.detail === 'accept') applyUserId();
		});
	})();
	</script>
	<?php
}, 1 );
