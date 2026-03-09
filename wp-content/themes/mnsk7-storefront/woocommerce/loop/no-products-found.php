<?php
/**
 * Empty state when no products match (override: mnsk7-storefront).
 * Spójny wygląd z .mnsk7-plp-empty — jeden blok, col-full.
 *
 * @package WooCommerce\Templates
 * @version 7.8.0
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="woocommerce-no-products-found mnsk7-plp-empty mnsk7-plp-empty--woo col-full" role="status">
	<p class="mnsk7-plp-empty__text"><?php echo esc_html( __( 'No products were found matching your selection.', 'woocommerce' ) ); ?></p>
	<p class="mnsk7-plp-empty__hint"><?php esc_html_e( 'Zmień kryteria wyszukiwania lub przejrzyj całą ofertę.', 'mnsk7-storefront' ); ?></p>
</div>
