<?php
/**
 * Shop breadcrumb
 *
 * Override: ostatni element jest linkiem, gdy ma URL (np. archiwum z filtrami — link "wstecz" do tej samej listy).
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
		$is_last = sizeof( $breadcrumb ) === $key + 1;

		echo $before;

		$classes = array();
		if ( 0 === $key ) {
			$classes[] = 'home';
		}
		if ( $is_last ) {
			$classes[] = 'last-item';
		}
		$link_class = ! empty( $classes ) ? ' class="' . esc_attr( implode( ' ', $classes ) ) . '"' : '';

		// Link gdy jest URL (także dla ostatniego elementu — na archiwum z filtrami ustawiamy URL w woocommerce_get_breadcrumb).
		if ( ! empty( $crumb[1] ) ) {
			echo '<a href="' . esc_url( $crumb[1] ) . '"' . $link_class . '>' . esc_html( $crumb[0] ) . '</a>';
		} else {
			echo '<span' . $link_class . ' aria-current="page">' . esc_html( $crumb[0] ) . '</span>';
		}

		echo $after;

		if ( sizeof( $breadcrumb ) !== $key + 1 ) {
			echo $delimiter;
		}
	}

	echo $wrap_after;

}
