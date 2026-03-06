<?php
/**
 * Template for product archives (shop and category). Override: mnsk7-storefront.
 * On category/tag: Sandvik-style table + chips (чипсы). Else: default grid.
 *
 * @see https://woocommerce.com/document/template-structure/
 * @package WooCommerce\Templates
 * @version 8.6.0
 */

defined( 'ABSPATH' ) || exit;

get_header( 'shop' );

do_action( 'woocommerce_before_main_content' );

$is_taxonomy = is_product_taxonomy();

if ( $is_taxonomy ) {
	// Чипсы: подкатегории или соседние термины (стиль Sandvik)
	$current_term = get_queried_object();
	$chips = array();
	if ( $current_term && isset( $current_term->taxonomy ) ) {
		if ( $current_term->taxonomy === 'product_cat' ) {
			$parent_id = $current_term->parent;
			$chips = get_terms( array(
				'taxonomy'   => 'product_cat',
				'parent'     => $parent_id,
				'hide_empty' => true,
			) );
		} else {
			// product_tag lub inna taksonomia: pokaż top-level product_cat jako чипсы do nawigacji
			$chips = get_terms( array(
				'taxonomy'   => 'product_cat',
				'parent'     => 0,
				'hide_empty' => true,
				'number'     => 12,
			) );
		}
	}
	if ( ! is_wp_error( $chips ) && ! empty( $chips ) ) {
		echo '<div class="mnsk7-plp-chips col-full">';
		foreach ( $chips as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) {
				continue;
			}
			$active = ( $current_term && (int) $current_term->term_id === (int) $term->term_id );
			printf(
				'<a href="%s" class="mnsk7-plp-chip %s">%s</a>',
				esc_url( $link ),
				$active ? 'mnsk7-plp-chip--active' : '',
				esc_html( $term->name )
			);
		}
		echo '</div>';
	}
}

do_action( 'woocommerce_shop_loop_header' );

if ( woocommerce_product_loop() ) {
	do_action( 'woocommerce_before_shop_loop' );

	if ( $is_taxonomy ) {
		// Table layout (Sandvik-style)
		?>
		<div class="mnsk7-product-table-wrap col-full">
			<table class="mnsk7-product-table shop_table">
				<thead>
					<tr>
						<th class="mnsk7-table-cell--thumb"><?php esc_html_e( 'Zdjęcie', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--title"><?php esc_html_e( 'Produkt', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--price"><?php esc_html_e( 'Cena', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--action"><?php esc_html_e( 'Akcja', 'mnsk7-storefront' ); ?></th>
					</tr>
				</thead>
				<tbody>
				<?php
				while ( have_posts() ) {
					the_post();
					global $product;
					if ( ! is_a( $product, WC_Product::class ) ) {
						$product = wc_get_product( get_the_ID() );
					}
					wc_get_template_part( 'content', 'product-table-row' );
				}
				?>
				</tbody>
			</table>
		</div>
		<?php
	} else {
		// Default grid
		woocommerce_product_loop_start();
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
	}

	do_action( 'woocommerce_after_shop_loop' );
} else {
	do_action( 'woocommerce_no_products_found' );
}

do_action( 'woocommerce_after_main_content' );
do_action( 'woocommerce_sidebar' );

get_footer( 'shop' );
