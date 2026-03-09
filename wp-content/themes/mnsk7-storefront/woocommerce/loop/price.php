<?php
/**
 * Loop Price — override: zawsze pokazuj blok ceny (Bestsellery, Related); fallback gdy brak ceny.
 *
 * @see     woocommerce/templates/loop/price.php
 * @package WooCommerce\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
$price_html = $product->get_price_html();
?>

<span class="price">
	<?php if ( $price_html ) : ?>
		<?php echo $price_html; ?>
	<?php else : ?>
		<span class="woocommerce-price-fallback"><?php esc_html_e( 'Cena na zapytanie', 'mnsk7-storefront' ); ?></span>
	<?php endif; ?>
</span>
