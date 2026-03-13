<?php
/**
 * MNK7 Tools — WooCommerce UX: related products, upsells, cookie, security.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Search normalization: "fi 4mm" / "4mm" → "fi 4 mm" / "4 mm" for product search (deployable in repo).
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {
	if ( is_admin() || ! $query->get( 's' ) ) {
		return;
	}
	$post_type = $query->get( 'post_type' );
	if ( $post_type !== 'product' && ( ! is_array( $post_type ) || ! in_array( 'product', $post_type, true ) ) ) {
		return;
	}
	$s = $query->get( 's' );
	if ( ! is_string( $s ) || trim( $s ) === '' ) {
		return;
	}
	$normalized = preg_replace( '/(\d+(?:[.,]\d+)?)\s*(mm|cm|m\b|g\b|kg|ml)/iu', '$1 $2', $s );
	$normalized = preg_replace( '/\s+/', ' ', $normalized );
	$normalized = trim( $normalized );
	if ( $normalized !== $s ) {
		$query->set( 's', $normalized );
	}
}, 5 );

/* PLP-03: jednorazowy flush rewrite rules — po ustawieniu opcji mnsk7_plp_rewrite_flush (np. update_option('mnsk7_plp_rewrite_flush',1)) przy następnym odświeżeniu strony product_tag/category URL zaczną działać. */
add_action( 'init', function () {
	if ( get_option( 'mnsk7_plp_rewrite_flush', 0 ) ) {
		flush_rewrite_rules( false );
		delete_option( 'mnsk7_plp_rewrite_flush' );
	}
}, 999 );

/* Related products: limit 4, 4 kolumny */
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );

add_filter( 'woocommerce_upsells_total',   fn() => 4 );
add_filter( 'woocommerce_upsells_columns', fn() => 4 );

/* Cross-sells na koszyku: czytelny tytuł zamiast „Zobacz inne równie interesujące…” */
add_filter( 'woocommerce_product_cross_sells_products_heading', function () {
	return __( 'Dopasowane do Twojego koszyka', 'mnsk7-tools' );
} );

/* Cookie consent bar — jeden bank w motywie (footer.php: Akceptuję wszystkie / Tylko niezbędne / Ustawienia). Tu nie dodajemy drugiego. */

/**
 * Seed primary menu: Przewodnik, Sklep, Dostawa i płatności, Kontakt (home_url() — staging i prod).
 */
add_action( 'init', function () {
	if ( get_option( 'mnsk7_menu_landings_seeded', 0 ) ) {
		return;
	}
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( $menu_id < 1 ) {
		update_option( 'mnsk7_menu_landings_seeded', 1 );
		return;
	}
	$items = wp_get_nav_menu_items( $menu_id );
	$urls  = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( ! empty( $item->url ) ) {
				$urls[] = trailingslashit( $item->url );
			}
		}
	}
	$home   = trailingslashit( home_url() );
	$to_add = array(
		array( 'Przewodnik', $home . 'przewodnik/' ),
		array( 'Sklep', $home . 'sklep/' ),
		array( 'Dostawa i płatności', $home . 'dostawa-i-platnosci/' ),
		array( 'Kontakt', $home . 'kontakt/' ),
	);
	foreach ( $to_add as $pair ) {
		$target_url = trailingslashit( $pair[1] );
		$exists     = false;
		foreach ( $urls as $u ) {
			if ( $u === $target_url || strpos( $u, $target_url ) === 0 || strpos( $target_url, $u ) === 0 ) {
				$exists = true;
				break;
			}
		}
		if ( ! $exists ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => $pair[0],
				'menu-item-url'    => $pair[1],
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			) );
		}
	}
	update_option( 'mnsk7_menu_landings_seeded', 1 );
}, 20 );
