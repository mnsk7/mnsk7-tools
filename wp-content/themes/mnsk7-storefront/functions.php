<?php
/**
 * MNK7 Storefront — child theme functions.
 * Parent: Storefront (official WooCommerce theme).
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

/* -------------------------------------------------------
   1. Enqueue parent + child styles
   ------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
	wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array( 'storefront-style' ), '1.0.0' );
	wp_enqueue_style( 'mnsk7-main', get_stylesheet_directory_uri() . '/assets/css/main.css', array( 'mnsk7-storefront-style' ), '1.0.0' );
}, 10 );

/* -------------------------------------------------------
   2. Google Fonts (Inter)
   ------------------------------------------------------- */

add_action( 'wp_enqueue_scripts', function () {
	wp_enqueue_style(
		'mnsk7-inter-font',
		'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap',
		array(),
		null
	);
}, 5 );

/* -------------------------------------------------------
   3. Storefront customizations via hooks
   ------------------------------------------------------- */

add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

add_filter( 'storefront_custom_header_args', function ( $args ) {
	$args['default-text-color'] = '0f172a';
	return $args;
} );

/* -------------------------------------------------------
   4. Clean header: programmatic menu when none assigned
   ------------------------------------------------------- */

add_filter( 'wp_page_menu_args', function ( $args ) {
	$args['include'] = '0';
	return $args;
} );

/* -------------------------------------------------------
   5. Footer copyright override
   ------------------------------------------------------- */

add_action( 'init', function () {
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
	add_action( 'storefront_footer', function () {
		echo '<div class="mnsk7-footer-copyright">&copy; ' . esc_html( date( 'Y' ) ) . ' mnsk7-tools.pl</div>';
	}, 20 );
} );
