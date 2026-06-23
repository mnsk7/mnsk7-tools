<?php
/**
 * Loop Price
 *
 * Override: mnsk7-storefront. Zawsze pokazuj blok ceny (bestsellery, related); fallback gdy brak ceny.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @see         woocommerce/templates/loop/price.php
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $product;
$price_html = $product->get_price_html();
?>

<span class="price">
	<?php if ( $price_html ) : ?>
		<?php echo wp_kses_post( $price_html ); ?>
	<?php else : ?>
		<span class="woocommerce-price-fallback"><?php esc_html_e( 'Cena na zapytanie', 'mnsk7-storefront' ); ?></span>
	<?php endif; ?>
</span>
