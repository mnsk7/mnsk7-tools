<?php
/**
 * Empty cart page. Override: mnsk7-storefront.
 * Ilustracja + tekst + link do sklepu (backlog 4.6).
 *
 * @package WooCommerce\Templates
 * @version 7.0.1
 */

defined( 'ABSPATH' ) || exit;

?>
<div class="mnsk7-cart-empty">
	<div class="mnsk7-cart-empty__icon" aria-hidden="true">
		<svg xmlns="http://www.w3.org/2000/svg" width="80" height="80" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" stroke-linejoin="round">
			<circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/>
			<path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/>
		</svg>
	</div>
	<p class="mnsk7-cart-empty__eyebrow"><?php esc_html_e( 'Koszyk', 'mnsk7-storefront' ); ?></p>
	<h2 class="mnsk7-cart-empty__title"><?php esc_html_e( 'Dodaj produkty i wróć do zamówienia w minutę', 'mnsk7-storefront' ); ?></h2>
	<p class="mnsk7-cart-empty__lead"><?php esc_html_e( 'Wybierz frezy, tuleje lub zestawy. W kolejnym kroku zobaczysz dostawę i formę płatności przed finalizacją zamówienia.', 'mnsk7-storefront' ); ?></p>
	<div class="mnsk7-cart-empty__benefits" aria-label="<?php esc_attr_e( 'Korzyści zakupowe', 'mnsk7-storefront' ); ?>">
		<span><?php esc_html_e( 'Darmowa dostawa od 300 zł', 'mnsk7-storefront' ); ?></span>
		<span><?php esc_html_e( 'Bezpieczne płatności', 'mnsk7-storefront' ); ?></span>
		<span><?php esc_html_e( 'Szybka wysyłka', 'mnsk7-storefront' ); ?></span>
	</div>
	<?php do_action( 'woocommerce_cart_is_empty' ); ?>
	<?php if ( wc_get_page_id( 'shop' ) > 0 ) : ?>
		<p class="mnsk7-cart-empty__actions">
			<a class="button mnsk7-cart-empty__button" href="<?php echo esc_url( apply_filters( 'woocommerce_return_to_shop_redirect', wc_get_page_permalink( 'shop' ) ) ); ?>">
				<?php echo esc_html( apply_filters( 'woocommerce_return_to_shop_text', __( 'Przejdź do sklepu', 'mnsk7-storefront' ) ) ); ?>
			</a>
		</p>
	<?php endif; ?>
	<p class="mnsk7-cart-empty__secondary">
		<a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>"><?php esc_html_e( 'Sprawdź dostawę i płatności', 'mnsk7-storefront' ); ?></a>
	</p>
</div>
