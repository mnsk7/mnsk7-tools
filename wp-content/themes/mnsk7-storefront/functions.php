<?php
/**
 * MNK7 Storefront child theme functions.
 * Parent: Storefront (official WooCommerce theme).
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether parent theme Storefront is present (not removed/overwritten by WP or host).
 * When false, child uses its own header fallback and does not enqueue parent styles.
 */
function mnsk7_parent_storefront_available() {
	return get_template() === 'storefront' && file_exists( get_template_directory() . '/style.css' );
}

/* 1. Enqueue styles — many small CSS parts (easier to maintain than one 2000+ line file) */
add_action( 'wp_enqueue_scripts', function () {
	$v = '2.1.0';
	$base = get_stylesheet_directory_uri() . '/assets/css/parts/';
	$dir = get_stylesheet_directory() . '/assets/css/parts/';
	if ( mnsk7_parent_storefront_available() ) {
		wp_enqueue_style( 'storefront-style', get_template_directory_uri() . '/style.css' );
		wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array( 'storefront-style' ), $v );
	} else {
		wp_enqueue_style( 'mnsk7-storefront-style', get_stylesheet_uri(), array(), $v );
	}
	$prev = 'mnsk7-storefront-style';
	$parts = array( '01-tokens', '02-reset-typography', '03-storefront-overrides', '04-header', '05-plp-cards', '06-single-product', '07-mnsk7-blocks', '08-home-sections', '09-footer', '10-cookie-bar', '11-hidden', '12-related-products', '13-seo-landing', '14-faq', '15-delivery-contact', '16-woo-notices', '17-buttons', '18-cart-checkout', '19-breadcrumbs', '20-responsive-tablet', '21-responsive-mobile', '22-touch-targets', '23-print' );
	$parts_loaded = false;
	foreach ( $parts as $part ) {
		$path = $dir . $part . '.css';
		if ( ! file_exists( $path ) ) {
			continue;
		}
		$handle = 'mnsk7-parts-' . $part;
		wp_enqueue_style( $handle, $base . $part . '.css', array( $prev ), $v );
		$prev = $handle;
		$parts_loaded = true;
	}
	if ( ! $parts_loaded ) {
		wp_enqueue_style( 'mnsk7-main', get_stylesheet_directory_uri() . '/assets/css/main.css', array( $prev ), $v );
	}
}, 10 );

/* 2. Google Fonts: Inter (replace Storefront default) */
add_action( 'wp_enqueue_scripts', function () {
	if ( mnsk7_parent_storefront_available() ) {
		wp_dequeue_style( 'storefront-fonts' );
	}
	wp_enqueue_style( 'mnsk7-inter', 'https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&display=swap', array(), null );
}, 20 );

/* 3. Theme support */
add_action( 'after_setup_theme', function () {
	add_theme_support( 'woocommerce' );
	add_theme_support( 'wc-product-gallery-zoom' );
	add_theme_support( 'wc-product-gallery-lightbox' );
	add_theme_support( 'wc-product-gallery-slider' );
} );

/* 4. Storefront header customization */
add_filter( 'storefront_custom_header_args', function ( $args ) {
	$args['default-text-color'] = '0f172a';
	return $args;
} );

add_action( 'init', function () {
	if ( ! mnsk7_parent_storefront_available() ) {
		return;
	}
	remove_action( 'storefront_header', 'storefront_secondary_navigation', 30 );
	remove_action( 'storefront_footer', 'storefront_footer_widgets', 10 );
	remove_action( 'storefront_footer', 'storefront_credit', 20 );
} );

/* 5. Admin notice when parent Storefront is missing (e.g. overwritten by WP/host) */
add_action( 'admin_notices', function () {
	if ( mnsk7_parent_storefront_available() || get_stylesheet() !== 'mnsk7-storefront' ) {
		return;
	}
	$msg = __( 'Rodzic motywu Storefront nie jest zainstalowany lub został nadpisany. Strona używa zapasowego nagłówka. Zainstaluj motyw Storefront (WooCommerce) lub wdróż go z repozytorium.', 'mnsk7-storefront' );
	echo '<div class="notice notice-warning is-dismissible"><p><strong>MNK7 Storefront:</strong> ' . esc_html( $msg ) . '</p></div>';
} );

/* 6. Prevent page listing fallback in menu */
add_filter( 'wp_page_menu_args', function ( $args ) {
	$args['include'] = '0';
	return $args;
} );

/* 7. Override Storefront typography */
add_filter( 'storefront_google_font_families', '__return_empty_array' );
