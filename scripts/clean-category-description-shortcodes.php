<?php
/**
 * Run in WP context (e.g. wp eval-file this file from site root).
 * Removes [wpf-filters id=7] and similar shortcodes from product_cat descriptions.
 */
if ( ! defined( 'ABSPATH' ) && ! function_exists( 'get_terms' ) ) {
	fwrite( STDERR, "Run via: wp eval-file scripts/clean-category-description-shortcodes.php\n" );
	exit( 1 );
}
$dry_run = ( isset( $argv[1] ) && $argv[1] === '--dry-run' );
$terms   = get_terms( array( 'taxonomy' => 'product_cat', 'hide_empty' => false ) );
foreach ( $terms as $t ) {
	$desc = get_term_field( 'description', $t->term_id, 'product_cat' );
	if ( empty( $desc ) ) {
		continue;
	}
	$orig = $desc;
	$desc = preg_replace( '/\[wpf-filters[^\]]*\]/', '', $desc );
	$desc = preg_replace( '/\[wpf_filters[^\]]*\]/', '', $desc );
	$desc = trim( preg_replace( '/\n\s*\n/', "\n", $desc ) );
	if ( $desc !== $orig ) {
		echo $t->term_id . ' ' . $t->slug . "\n";
		if ( ! $dry_run ) {
			wp_update_term( $t->term_id, 'product_cat', array( 'description' => $desc ) );
		}
	}
}
