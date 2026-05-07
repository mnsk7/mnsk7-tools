<?php
/**
 * Content wrapper start. Override for mnsk7-storefront (parent: Storefront).
 * Header already opened #page and #content; here we add Woo content-area + main.
 *
 * @see wp-content/themes/mnsk7-storefront/header.php (opens #content)
 * @see docs/WRAPPERS_LAYOUT.md
 * @package WooCommerce\Templates
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="primary" class="content-area mnsk7-content-area">
	<main id="main" class="site-main mnsk7-main" role="main">
		<?php
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			echo '<h1 class="screen-reader-text">' . esc_html__( 'Koszyk', 'mnsk7-storefront' ) . '</h1>';
		} elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
			echo '<h1 class="screen-reader-text">' . esc_html__( 'Zamówienie', 'mnsk7-storefront' ) . '</h1>';
		}
		?>
