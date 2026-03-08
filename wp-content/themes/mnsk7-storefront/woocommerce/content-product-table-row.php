<?php
/**
 * One row of the product table (shop/category archive). Sandvik-style PLP table.
 * Columns: thumb, title, price, stock, qty, action.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

global $product;

if ( ! is_a( $product, WC_Product::class ) || ! $product->is_visible() ) {
	return;
}

$stock_qty   = $product->get_stock_quantity();
$stock_html  = $product->is_in_stock()
	? ( $stock_qty !== null ? sprintf( _n( '%d szt.', '%d szt.', $stock_qty, 'mnsk7-storefront' ), $stock_qty ) : esc_html__( 'W magazynie', 'mnsk7-storefront' ) )
	: '<span class="mnsk7-table-outofstock">' . esc_html__( 'Brak', 'mnsk7-storefront' ) . '</span>';
$total_sales = (int) $product->get_total_sales();
$max_qty     = $product->get_max_purchase_quantity();
$min_qty     = $product->get_min_purchase_quantity();
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
		<?php if ( $total_sales > 0 ) : ?>
			<span class="mnsk7-table-sold"><?php echo esc_html( sprintf( _n( '%d osoba kupiła', '%d osób kupiło', $total_sales, 'mnsk7-storefront' ), $total_sales ) ); ?></span>
		<?php endif; ?>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--price">
		<?php echo $product->get_price_html(); ?>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--stock">
		<?php echo wp_kses_post( $stock_html ); ?>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--qty">
		<?php
		$form_id = 'mnsk7-addcart-' . $product->get_id();
		if ( $product->is_purchasable() && $product->is_in_stock() ) :
			if ( $product->is_sold_individually() ) :
				?>
				<span class="mnsk7-table-qty-one">1</span>
			<?php else : ?>
				<input type="number" form="<?php echo esc_attr( $form_id ); ?>" class="mnsk7-table-qty-input input-text qty text" name="quantity" value="<?php echo esc_attr( max( $min_qty, 1 ) ); ?>" min="<?php echo esc_attr( $min_qty ); ?>" max="<?php echo esc_attr( $max_qty > 0 ? $max_qty : 9999 ); ?>" step="1" aria-label="<?php esc_attr_e( 'Ilość', 'mnsk7-storefront' ); ?>" />
			<?php endif; ?>
		<?php else : ?>
			—
		<?php endif; ?>
	</td>
	<td class="mnsk7-table-cell mnsk7-table-cell--action">
		<?php
		if ( $product->is_purchasable() && $product->is_in_stock() ) {
			?>
			<form id="<?php echo esc_attr( $form_id ); ?>" method="post" action="" class="mnsk7-table-addcart-form">
				<input type="hidden" name="add-to-cart" value="<?php echo esc_attr( $product->get_id() ); ?>" />
				<?php if ( $product->is_sold_individually() ) : ?>
					<input type="hidden" name="quantity" value="1" />
				<?php endif; ?>
				<button type="submit" class="button mnsk7-table-addcart-btn"><?php esc_html_e( 'Dodaj do koszyka', 'mnsk7-storefront' ); ?></button>
			</form>
			<?php
		} else {
			echo '<span class="mnsk7-table-no-action">—</span>';
		}
		?>
	</td>
</tr>
