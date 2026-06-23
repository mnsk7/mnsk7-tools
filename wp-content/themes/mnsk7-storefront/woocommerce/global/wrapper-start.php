<?php
/**
 * Content wrappers
 *
 * Override: mnsk7-storefront (parent: Storefront). Header otwiera #page i #content;
 * tutaj dodajemy content-area Woo + main.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @see         wp-content/themes/mnsk7-storefront/header.php (opens #content)
 * @see         docs/WRAPPERS_LAYOUT.md
 * @package     WooCommerce\Templates
 * @version     3.3.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>
<div id="primary" class="content-area mnsk7-content-area">
	<main id="main" class="site-main mnsk7-main" role="main">
