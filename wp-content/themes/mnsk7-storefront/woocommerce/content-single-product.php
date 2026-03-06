<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * Override: mnsk7-storefront. W Sprint 02: dodać blok kluczowych parametrów i "podstaw dla".
 *
 * @see     https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 3.6.0
 */

defined( 'ABSPATH' ) || exit;

global $product;

do_action( 'woocommerce_before_single_product' );

if ( post_password_required() ) {
	echo get_the_password_form();
	return;
}
?>
<div id="product-<?php the_ID(); ?>" <?php wc_product_class( '', $product ); ?>>

	<?php do_action( 'woocommerce_before_single_product_summary' ); ?>

	<div class="summary entry-summary">
		<?php
		/**
		 * woocommerce_single_product_summary hooks (priority):
		 *  5  – rating
		 *  8  – mnsk7_single_product_availability
		 * 10  – title
		 * 15  – price (moved up for above-fold)
		 * 21  – mnsk7_single_product_key_params
		 * 23  – mnsk7_single_product_zastosowanie
		 * 30  – add_to_cart
		 * 32  – mnsk7_single_product_trust_badges
		 * 40  – mnsk7_single_product_meta_chips
		 *
		 * Removed: excerpt (20), old meta (40) — replaced by structured blocks.
		 */
		do_action( 'woocommerce_single_product_summary' );
		?>
	</div>

	<?php
	// S2-06: miejsce na schemat parametrów lub wideo (treść dodawana przez klienta)
	if ( function_exists( 'mnsk7_single_product_schema_video_placeholder' ) ) :
		$extra = mnsk7_single_product_schema_video_placeholder();
		if ( ! empty( $extra ) ) :
	?>
	<div class="mnsk7-product-extra-media">
		<?php echo $extra; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
	</div>
	<?php
		endif;
	endif;
	?>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
