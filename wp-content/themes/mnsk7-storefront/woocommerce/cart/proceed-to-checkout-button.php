<?php
/**
 * Proceed to checkout button — override dla pewnego przejścia na checkout (audit).
 *
 * @see plugins/woocommerce/templates/cart/proceed-to-checkout-button.php
 * @package mnsk7-storefront
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$checkout_url = function_exists( 'wc_get_checkout_url' ) ? wc_get_checkout_url() : '';
if ( ! $checkout_url ) {
	return;
}
?>

<a id="mnsk7-cart-checkout-button" href="<?php echo esc_url( $checkout_url ); ?>" class="checkout-button button alt wc-forward<?php echo esc_attr( wc_wp_theme_get_element_class_name( 'button' ) ? ' ' . wc_wp_theme_get_element_class_name( 'button' ) : '' ); ?>">
	<?php esc_html_e( 'Proceed to checkout', 'woocommerce' ); ?>
</a>
