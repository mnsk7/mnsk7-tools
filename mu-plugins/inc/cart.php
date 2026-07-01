<?php
/**
 * MNK7 Tools — Cart UX: auto-remove unavailable line items.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_cart_line_item_unavailable' ) ) {
	/**
	 * Whether a cart line should be removed (deleted, draft, not purchasable, OOS without backorders).
	 *
	 * @param array $cart_item WooCommerce cart line.
	 * @return bool
	 */
	function mnsk7_cart_line_item_unavailable( array $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( ! $product || ! is_a( $product, 'WC_Product' ) ) {
			return true;
		}

		if ( ! $product->exists() ) {
			return true;
		}

		$parent_id = $product->get_parent_id();
		if ( $parent_id ) {
			$parent = wc_get_product( $parent_id );
			if ( ! $parent || 'publish' !== $parent->get_status() ) {
				return true;
			}
		} elseif ( 'publish' !== $product->get_status() ) {
			return true;
		}

		if ( ! $product->is_purchasable() ) {
			return true;
		}

		if ( ! $product->is_in_stock() && ! $product->backorders_allowed() ) {
			return true;
		}

		if ( $product->managing_stock() ) {
			$stock = $product->get_stock_quantity();
			if ( null !== $stock && $stock < 1 && ! $product->backorders_allowed() ) {
				return true;
			}
		}

		return false;
	}
}

if ( ! function_exists( 'mnsk7_get_cart_line_display_name' ) ) {
	/**
	 * Human-readable name for a cart line (for notices).
	 *
	 * @param array $cart_item WooCommerce cart line.
	 * @return string
	 */
	function mnsk7_get_cart_line_display_name( array $cart_item ) {
		$product = isset( $cart_item['data'] ) ? $cart_item['data'] : null;
		if ( $product && is_a( $product, 'WC_Product' ) ) {
			return wp_strip_all_tags( $product->get_name() );
		}
		if ( ! empty( $cart_item['product_id'] ) ) {
			$fallback = get_the_title( (int) $cart_item['product_id'] );
			if ( $fallback ) {
				return wp_strip_all_tags( $fallback );
			}
		}
		return __( 'Produkt', 'mnsk7-tools' );
	}
}

if ( ! function_exists( 'mnsk7_cart_remove_unavailable_items' ) ) {
	/**
	 * Scan cart and remove unavailable lines.
	 *
	 * @return string[] Removed product display names.
	 */
	function mnsk7_cart_remove_unavailable_items() {
		if ( ! function_exists( 'WC' ) || ! WC()->cart ) {
			return array();
		}

		static $running = false;
		if ( $running ) {
			return array();
		}
		$running = true;

		$removed = array();
		$cart    = WC()->cart->get_cart();

		foreach ( $cart as $cart_item_key => $cart_item ) {
			if ( mnsk7_cart_line_item_unavailable( $cart_item ) ) {
				$removed[] = mnsk7_get_cart_line_display_name( $cart_item );
				WC()->cart->remove_cart_item( $cart_item_key );
			}
		}

		if ( ! empty( $removed ) ) {
			WC()->cart->calculate_totals();
		}

		$running = false;
		return $removed;
	}
}

if ( ! function_exists( 'mnsk7_cart_notice_removed_items' ) ) {
	/**
	 * Add a single WooCommerce notice listing removed products.
	 *
	 * @param string[] $removed Product display names.
	 */
	function mnsk7_cart_notice_removed_items( array $removed ) {
		$removed = array_values( array_unique( array_filter( $removed ) ) );
		if ( empty( $removed ) ) {
			return;
		}

		$message = __( 'Usunęliśmy z koszyka produkty, które nie są już dostępne.', 'mnsk7-tools' );
		if ( count( $removed ) <= 5 ) {
			$message .= ' ' . sprintf(
				/* translators: %s: comma-separated product names */
				__( 'Usunięte pozycje: %s.', 'mnsk7-tools' ),
				implode( ', ', $removed )
			);
		} else {
			$message .= ' ' . sprintf(
				/* translators: %d: number of removed items */
				__( 'Usunięto %d pozycji.', 'mnsk7-tools' ),
				count( $removed )
			);
		}

		wc_add_notice( $message, 'notice' );
	}
}

if ( ! function_exists( 'mnsk7_cart_maybe_cleanup_unavailable' ) ) {
	/**
	 * Remove unavailable items, show notice, redirect empty checkout cart to cart page.
	 *
	 * @param string $context `cart` or `checkout`.
	 */
	function mnsk7_cart_maybe_cleanup_unavailable( $context = 'cart' ) {
		static $noticed = false;

		$removed = mnsk7_cart_remove_unavailable_items();
		if ( empty( $removed ) ) {
			return;
		}

		if ( ! $noticed ) {
			mnsk7_cart_notice_removed_items( $removed );
			$noticed = true;
		}

		if ( WC()->cart->is_empty() && 'checkout' === $context && function_exists( 'wc_get_cart_url' ) ) {
			wp_safe_redirect( wc_get_cart_url() );
			exit;
		}
	}
}

/**
 * Early cleanup on cart / checkout page load (before templates render).
 */
add_action(
	'template_redirect',
	function () {
		if ( is_admin() || wp_doing_ajax() || ( defined( 'REST_REQUEST' ) && REST_REQUEST ) ) {
			return;
		}
		if ( ! function_exists( 'WC' ) || ! WC()->cart || WC()->cart->is_empty() ) {
			return;
		}

		$context = null;
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			$context = 'cart';
		} elseif ( function_exists( 'is_checkout' ) && is_checkout() && ! is_order_received_page() ) {
			$context = 'checkout';
		}

		if ( ! $context ) {
			return;
		}

		mnsk7_cart_maybe_cleanup_unavailable( $context );
	},
	15
);

/**
 * Before WooCommerce checkout validation (`WC_Cart::check_cart_items`).
 */
add_action(
	'woocommerce_check_cart_items',
	function () {
		mnsk7_cart_maybe_cleanup_unavailable( 'checkout' );
	},
	5
);

/**
 * Backup: run before cart table renders.
 */
add_action(
	'woocommerce_before_cart',
	function () {
		mnsk7_cart_maybe_cleanup_unavailable( 'cart' );
	},
	1
);
