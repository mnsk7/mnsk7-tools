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

	<div class="summary entry-summary mnsk7-pdp-buybox">
		<?php if ( function_exists( 'woocommerce_breadcrumb' ) ) : ?>
			<div class="mnsk7-pdp-breadcrumb-slot">
				<div class="mnsk7-pdp-breadcrumb-slot__back">
					<?php if ( function_exists( 'mnsk7_render_pdp_back_to_results' ) ) { mnsk7_render_pdp_back_to_results(); } ?>
				</div>
				<div class="mnsk7-pdp-breadcrumb-slot__trail">
					<?php woocommerce_breadcrumb(); ?>
				</div>
			</div>
		<?php endif; ?>

		<?php
		/**
		 * Buy box: tytuł → cena → dostępność → parametry → CTA → trust (product_card_visual, WOO_CONVERSION_REWORK_PLAN).
		 * woocommerce_single_product_summary hooks (priority):
		 *  5  – (wolne; breadcrumbs renderujemy bezpośrednio w template, nad hookami)
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

<?php
// Sticky CTA na mobile (PDP): cena + stock info + przycisk przewijający do formularza.
if ( isset( $product ) && is_a( $product, 'WC_Product' ) && $product->is_purchasable() && $product->is_in_stock() ) {
	$availability = $product->get_availability();
	$stock_text   = ! empty( $availability['availability'] ) ? $availability['availability'] : __( 'W magazynie', 'mnsk7-storefront' );
	?>
	<div id="mnsk7-pdp-sticky-cta" class="mnsk7-pdp-sticky-cta" aria-hidden="true" hidden>
		<div class="mnsk7-pdp-sticky-cta__left">
			<span class="mnsk7-pdp-sticky-cta__price"><?php echo $product->get_price_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></span>
			<span class="mnsk7-pdp-sticky-cta__stock"><?php echo esc_html( $stock_text ); ?></span>
		</div>
		<?php
		$is_variable   = $product->is_type( 'variable' );
		$cta_label     = $is_variable ? __( 'Wybierz wariant', 'mnsk7-storefront' ) : __( 'Dodaj do koszyka', 'mnsk7-storefront' );
		$cta_aria      = $is_variable ? __( 'Wybierz wariant — przewiń do opcji', 'mnsk7-storefront' ) : __( 'Dodaj do koszyka', 'mnsk7-storefront' );
		$cta_action    = $is_variable ? 'choose' : 'add';
		?>
		<button type="button" class="mnsk7-pdp-sticky-cta__btn" data-action="<?php echo esc_attr( $cta_action ); ?>" aria-label="<?php echo esc_attr( $cta_aria ); ?>"><?php echo esc_html( $cta_label ); ?></button>
	</div>
	<?php
}
?>

<?php do_action( 'woocommerce_after_single_product' ); ?>
