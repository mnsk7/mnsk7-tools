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

/**
 * SKU-like search fallback: when query looks like an SKU, return products by _sku.
 * Example: ?s=H0600901&post_type=product
 */
add_action( 'pre_get_posts', function ( WP_Query $query ) {
	if ( is_admin() || ! $query->is_search() ) {
		return;
	}

	$post_type = $query->get( 'post_type' );
	if ( $post_type !== 'product' && ( ! is_array( $post_type ) || ! in_array( 'product', $post_type, true ) ) ) {
		return;
	}

	$term = trim( (string) $query->get( 's' ) );
	if ( $term === '' ) {
		return;
	}
	// Run only for compact SKU-like tokens (letters/digits/-/._), containing at least one digit.
	if ( ! preg_match( '/^[A-Za-z0-9._-]{3,}$/', $term ) || ! preg_match( '/\d/', $term ) ) {
		return;
	}

	global $wpdb;
	$like = '%' . $wpdb->esc_like( $term ) . '%';
	$ids  = $wpdb->get_col(
		$wpdb->prepare(
			"SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_sku' AND meta_value LIKE %s
			LIMIT 100",
			$like
		)
	);
	$ids = array_values( array_unique( array_map( 'absint', (array) $ids ) ) );
	if ( empty( $ids ) ) {
		return;
	}

	$query->set( 'post__in', $ids );
	$query->set( 'orderby', 'post__in' );
	$query->set( 's', '' );
}, 999 );

/**
 * Product search fallback by SKU for Woo archives, e.g. ?s=H0600901&post_type=product.
 * Keeps standard search behavior and additionally matches _sku meta value.
 */
add_filter( 'posts_search', function ( $search, WP_Query $query ) {
	if ( is_admin() || ! $query->is_search() ) {
		return $search;
	}

	$post_type = $query->get( 'post_type' );
	if ( $post_type !== 'product' && ( ! is_array( $post_type ) || ! in_array( 'product', $post_type, true ) ) ) {
		return $search;
	}

	$term = trim( (string) $query->get( 's' ) );
	if ( $term === '' ) {
		return $search;
	}

	global $wpdb;
	$like    = '%' . $wpdb->esc_like( $term ) . '%';
	$sku_sql = $wpdb->prepare(
		"{$wpdb->posts}.ID IN (
			SELECT post_id
			FROM {$wpdb->postmeta}
			WHERE meta_key = '_sku' AND meta_value LIKE %s
		)",
		$like
	);

	if ( is_string( $search ) && trim( $search ) !== '' ) {
		$updated = preg_replace( '/\)\s*$/', ' OR ' . $sku_sql . ')', $search, 1 );
		return is_string( $updated ) ? $updated : $search;
	}

	return " AND ({$sku_sql}) ";
}, 20, 2 );

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

/**
 * Remove third-party WPCLV variant block from PDP summary at hook level.
 * We render our own variant UI in mnsk7 product-card, so WPCLV output is duplicate.
 */
add_action( 'wp', function () {
	if ( is_admin() || ! function_exists( 'is_product' ) || ! is_product() ) {
		return;
	}

	$targets = array(
		'woocommerce_before_single_product_summary',
		'woocommerce_single_product_summary',
		'woocommerce_after_single_product_summary',
	);

	foreach ( $targets as $hook_name ) {
		global $wp_filter;
		if ( empty( $wp_filter[ $hook_name ] ) || ! $wp_filter[ $hook_name ] instanceof WP_Hook ) {
			continue;
		}
		foreach ( $wp_filter[ $hook_name ]->callbacks as $priority => $callbacks ) {
			foreach ( $callbacks as $callback ) {
				if ( empty( $callback['function'] ) ) {
					continue;
				}
				$fn = $callback['function'];
				$signature = '';
				if ( is_string( $fn ) ) {
					$signature = $fn;
				} elseif ( is_array( $fn ) ) {
					$owner = is_object( $fn[0] ) ? get_class( $fn[0] ) : (string) $fn[0];
					$signature = $owner . '::' . (string) $fn[1];
				} elseif ( $fn instanceof Closure ) {
					$ref = new ReflectionFunction( $fn );
					$signature = (string) $ref->getFileName();
				}

				if ( stripos( $signature, 'wpclv' ) !== false ) {
					remove_action( $hook_name, $fn, (int) $priority );
				}
			}
		}
	}
}, 1000 );

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
