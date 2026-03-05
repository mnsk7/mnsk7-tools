<?php
/**
 * The template for displaying product content in the single-product.php template
 *
 * Override: tech-storefront. W Sprint 02: dodać blok kluczowych parametrów i "podstaw dla".
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
		<?php do_action( 'woocommerce_single_product_summary' ); ?>

		<?php if ( function_exists( 'mnsk7_single_product_availability' ) ) : ?>
			<?php mnsk7_single_product_availability(); ?>
		<?php endif; ?>
	</div>

	<?php
	// Blok kluczowych parametrów (S2-04) i "Do czego" (S2-05)
	if ( function_exists( 'mnsk7_single_product_key_params' ) ) {
		mnsk7_single_product_key_params();
	}
	if ( function_exists( 'mnsk7_single_product_zastosowanie' ) ) {
		mnsk7_single_product_zastosowanie();
	}
	?>

	<?php
	// S2-06: miejsce na schemat parametrów lub wideo (treść dodawana później)
	?>
	<div class="mnsk7-product-extra-media">
		<?php if ( function_exists( 'mnsk7_single_product_schema_video_placeholder' ) ) : ?>
			<?php mnsk7_single_product_schema_video_placeholder(); ?>
		<?php endif; ?>
	</div>

	<?php do_action( 'woocommerce_after_single_product_summary' ); ?>
</div>

<?php do_action( 'woocommerce_after_single_product' ); ?>
