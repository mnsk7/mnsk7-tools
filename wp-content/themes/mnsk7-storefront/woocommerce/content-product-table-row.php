<?php
/**
 * One row of the product table (category/tag archive). Sandvik-style PLP table.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}
?>
<tr <?php wc_product_class( '', $product ); ?>>
	<td class="mnsk7-table-cell mnsk7-table-cell--thumb">
		<a href="<?php echo esc_url( get_permalink() ); ?>">
			<?php echo $product->get_image( 'woocommerce_thumbnail' ); ?>
		</a>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--title">
		<a href="<?php echo esc_url( get_permalink() ); ?>"><?php echo esc_html( get_the_title() ); ?></a>
		<?php if ( $product->get_sku() ) : ?>
			<span class="mnsk7-table-sku"><?php echo esc_html( $product->get_sku() ); ?></span>
		<?php endif; ?>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--price">
		<?php echo $product->get_price_html(); ?>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--action">
		<?php woocommerce_template_loop_add_to_cart(); ?>
	</td>
</tr>
