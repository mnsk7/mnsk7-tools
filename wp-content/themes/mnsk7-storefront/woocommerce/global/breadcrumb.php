<?php
/**
 * Shop breadcrumb
 *
 * Override: ostatni element jest linkiem, gdy ma URL (np. archiwum z filtrami — link „wstecz” do tej samej listy).
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     2.3.0
 * @see         woocommerce_breadcrumb()
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! empty( $breadcrumb ) ) {

	echo $wrap_before;

	foreach ( $breadcrumb as $key => $crumb ) {

		echo $before;

		// Link gdy jest URL (także dla ostatniego elementu — na archiwum z filtrami ustawiamy URL w woocommerce_get_breadcrumb).
		if ( ! empty( $crumb[1] ) ) {
			echo '<a href="' . esc_url( $crumb[1] ) . '">' . esc_html( $crumb[0] ) . '</a>';
		} else {
			echo esc_html( $crumb[0] );
		}

		echo $after;

		if ( sizeof( $breadcrumb ) !== $key + 1 ) {
			echo $delimiter;
		}
	}

	echo $wrap_after;

}
