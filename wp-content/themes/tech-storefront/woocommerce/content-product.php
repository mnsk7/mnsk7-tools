<?php
/**
 * Product card in loop (PLP, related, etc.). Override: tech-storefront.
 * Contract: Image → Title → Key spec line → Price → CTA.
 *
 * @package WooCommerce\Templates
 * @version 9.4.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
?>
<li <?php wc_product_class( 'mnsk7-product-card', $product ); ?>>
	<?php do_action( 'woocommerce_before_shop_loop_item' ); ?>
	<?php do_action( 'woocommerce_before_shop_loop_item_title' ); ?>
	<?php do_action( 'woocommerce_shop_loop_item_title' ); ?>

	<?php
	// Key spec line (one line): D=38 mm • S=8 mm • 4P
	if ( function_exists( 'mnsk7_get_product_key_spec_line' ) ) {
		$spec_line = mnsk7_get_product_key_spec_line( $product );
		if ( $spec_line !== '' ) {
			echo '<p class="mnsk7-card-spec-line">' . esc_html( $spec_line ) . '</p>';
		}
	}
	?>

	<?php do_action( 'woocommerce_after_shop_loop_item_title' ); ?>

	<div class="mnsk7-card-cta">
		<?php
		if ( function_exists( 'woocommerce_template_loop_add_to_cart' ) ) {
			woocommerce_template_loop_add_to_cart();
		} else {
			do_action( 'best_shop_loop_add_to_cart' );
		}
		?>
	</div>
</li>
