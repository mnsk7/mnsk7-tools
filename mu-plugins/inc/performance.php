<?php
/**
 * MNK7 Tools — Performance: resource hints, preconnect, LCP, lazy loading.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Lazy loading obrazków — ustawiamy loading="lazy" jeśli nie jest jeszcze ustawiony.
 * WooCommerce 10.6 (marzec 2026) robi to domyślnie dla obrazków produktów;
 * nasz filtr jest bezpieczny dzięki sprawdzeniu isset().
 *
 * @see https://developer.woocommerce.com/ (advisory: "Product images are now lazy-loaded by default in WooCommerce 10.6")
 */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment, $size ) {
	if ( ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}
	return $attr;
}, 15, 3 );

/**
 * Hero image na front-page: eager + fetchpriority high.
 * Dotyczy pierwszego obrazka w sekcji hero (gdy używamy get_the_post_thumbnail).
 */
add_filter( 'woocommerce_product_get_image', function ( $image, $product, $size, $attr, $placeholder, $image_obj ) {
	static $first_in_archive = false;
	if ( is_archive() && ! $first_in_archive ) {
		$first_in_archive = true;
		/* Zamień loading="lazy" na eager + dodaj fetchpriority="high" dla pierwszego */
		$image = str_replace( 'loading="lazy"', 'loading="eager" fetchpriority="high"', $image );
	}
	return $image;
}, 10, 6 );

/* Uwaga: nie usuwamy ?ver= z assetów — LiteSpeed Cache i WP Rocket obsługują to samodzielnie.
 * Ręczne usuwanie może powodować problemy z inwalidacją cache w niektórych konfiguracjach. */

/**
 * Wyłącz emoji scripts — zmniejszenie liczby requestów.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

/**
 * Wyłącz wbudowane bloki Gutenberga CSS (jeśli strona nie używa blok-editora w frontendzie).
 */
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_admin() ) {
		if ( ! is_singular() || ! has_blocks( get_post() ) ) {
			wp_dequeue_style( 'wp-block-library' );
			wp_dequeue_style( 'wp-block-library-theme' );
			wp_dequeue_style( 'global-styles' );
		}
	}
}, 100 );
