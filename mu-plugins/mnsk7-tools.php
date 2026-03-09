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
require_once $mnsk7_inc . 'checkout.php';
require_once $mnsk7_inc . 'woo-ux.php';
require_once $mnsk7_inc . 'pages-seed.php';
require_once $mnsk7_inc . 'performance.php';
