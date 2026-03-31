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
	<?php esc_html_e( 'Przejdź do płatności', 'mnsk7-storefront' ); ?>
</a>
<p class="mnsk7-cart-checkout-note">
	<?php esc_html_e( 'W kolejnym kroku wybierzesz dostawę i formę płatności.', 'mnsk7-storefront' ); ?>
</p>
<div class="mnsk7-cart-checkout-trust" aria-label="<?php esc_attr_e( 'Korzyści przy zamówieniu', 'mnsk7-storefront' ); ?>">
	<span><?php esc_html_e( 'Bezpieczne płatności', 'mnsk7-storefront' ); ?></span>
	<span><?php esc_html_e( 'Darmowa dostawa od 300 zł', 'mnsk7-storefront' ); ?></span>
	<span><?php esc_html_e( 'Szybka wysyłka', 'mnsk7-storefront' ); ?></span>
</div>
