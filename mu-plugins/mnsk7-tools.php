<?php
/**
 * Plugin Name: MNK7 Tools (MU)
 * Description: Biznesowa logika projektu mnsk7-tools.pl — filtry, helpery, customizacje Woo. Nie zależy od motywu.
 * Author: Projekt mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

// P0-03: blokada xmlrpc.php (bezpieczeństwo)
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit;
}

$mnsk7_inc = __DIR__ . '/inc/';

require_once $mnsk7_inc . 'constants.php';
require_once $mnsk7_inc . 'loyalty.php';
require_once $mnsk7_inc . 'product-card.php';
require_once $mnsk7_inc . 'delivery.php';
require_once $mnsk7_inc . 'shortcodes.php';
require_once $mnsk7_inc . 'seo.php';
require_once $mnsk7_inc . 'faq.php';
require_once $mnsk7_inc . 'guide-seo.php';
require_once $mnsk7_inc . 'checkout.php';
require_once $mnsk7_inc . 'woo-ux.php';
require_once $mnsk7_inc . 'pages-seed.php';
require_once $mnsk7_inc . 'performance.php';

/**
 * Legacy URL compatibility (preprod readiness):
 * `/polityka-prywatnosci/` existed historically; on staging/prod the actual page slug may differ.
 * If the request is a 404 for that legacy path, redirect to the configured Privacy Policy URL
 * (or a safe fallback that exists on staging).
 */
add_action( 'template_redirect', function () {
	if ( ! function_exists( 'is_404' ) || ! is_404() ) {
		return;
	}
	$path = isset( $_SERVER['REQUEST_URI'] ) ? (string) wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
	$path = wp_parse_url( $path, PHP_URL_PATH );
	if ( ! is_string( $path ) ) {
		return;
	}
	if ( untrailingslashit( $path ) !== '/polityka-prywatnosci' ) {
		return;
	}

	$target = function_exists( 'get_privacy_policy_url' ) ? (string) get_privacy_policy_url() : '';
	if ( $target === '' ) {
		$target = home_url( '/privacy-policy/' );
	}
	if ( $target !== '' ) {
		wp_safe_redirect( $target, 301 );
		exit;
	}
}, 1 );
