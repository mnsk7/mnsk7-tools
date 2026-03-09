<?php
/**
 * Single Product Up-Sells
 *
 * Override: mnsk7-storefront. Podtytuł i spójna struktura z related (H2 + subtitle).
 *
 * @see     woocommerce/templates/single-product/up-sells.php
 * @package WooCommerce\Templates
 * @version 3.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $upsells ) :
	if ( function_exists( 'wp_increase_content_media_count' ) ) {
		$content_media_count = wp_increase_content_media_count( 0 );
		if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
			wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
		}
	}
	?>

	<section class="up-sells upsells products">
		<?php
		$heading = apply_filters( 'woocommerce_product_upsells_products_heading', __( 'You may also like&hellip;', 'woocommerce' ) );
		$subtitle = apply_filters( 'mnsk7_upsells_subtitle', __( 'Dopasowane do tego produktu', 'mnsk7-storefront' ) );

		if ( $heading ) :
			?>
			<h2><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $subtitle ) : ?>
				<p class="related-products__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php woocommerce_product_loop_start(); ?>

			<?php foreach ( $upsells as $upsell ) : ?>
				<?php
				$product_id = is_object( $upsell ) ? $upsell->get_id() : (int) $upsell;
				$post_object = get_post( $product_id );
				if ( ! $post_object ) {
					continue;
				}
				setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found
				wc_get_template_part( 'content', 'product' );
				?>
			<?php endforeach; ?>

		<?php woocommerce_product_loop_end(); ?>
	</section>
	<?php
endif;

wp_reset_postdata();
