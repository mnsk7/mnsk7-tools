<?php
/**
 * Related Products
 *
 * Override: mnsk7-storefront. Podtytuł „Z tej samej kategorii” pod H2.
 *
 * @see     woocommerce/templates/single-product/related.php
 * @package WooCommerce\Templates
 * @version 10.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( $related_products ) :
	if ( function_exists( 'wp_increase_content_media_count' ) ) {
		$content_media_count = wp_increase_content_media_count( 0 );
		if ( $content_media_count < wp_omit_loading_attr_threshold() ) {
			wp_increase_content_media_count( wp_omit_loading_attr_threshold() - $content_media_count );
		}
	}
	?>

	<section class="related products">

		<?php
		$heading = apply_filters( 'woocommerce_product_related_products_heading', __( 'Related products', 'woocommerce' ) );
		$subtitle = apply_filters( 'mnsk7_related_products_subtitle', __( 'Z tej samej kategorii', 'mnsk7-storefront' ) );

		if ( $heading ) :
			?>
			<h2><?php echo esc_html( $heading ); ?></h2>
			<?php if ( $subtitle ) : ?>
				<p class="related-products__subtitle"><?php echo esc_html( $subtitle ); ?></p>
			<?php endif; ?>
		<?php endif; ?>
		<?php woocommerce_product_loop_start(); ?>

			<?php foreach ( $related_products as $related_product ) : ?>

					<?php
					$post_object = get_post( $related_product->get_id() );

					setup_postdata( $GLOBALS['post'] = $post_object ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited, Squiz.PHP.DisallowMultipleAssignments.Found

					wc_get_template_part( 'content', 'product' );
					?>

			<?php endforeach; ?>

		<?php woocommerce_product_loop_end(); ?>

	</section>
	<?php
endif;

wp_reset_postdata();
