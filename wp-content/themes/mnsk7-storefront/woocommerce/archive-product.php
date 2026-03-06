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
$current_term = $is_taxonomy ? get_queried_object() : null;

do_action( 'woocommerce_shop_loop_header' );

/* Чипсы pod nazwą kategorii (breadcrumb → tytuł → chips) */
if ( $is_taxonomy && $current_term && isset( $current_term->taxonomy ) ) {
	$chips = array();
	if ( $current_term->taxonomy === 'product_cat' ) {
		$parent_id = $current_term->parent;
		$chips = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => $parent_id,
			'hide_empty' => true,
		) );
	} else {
		$chips = get_terms( array(
			'taxonomy'   => 'product_cat',
			'parent'     => 0,
			'hide_empty' => true,
			'number'     => 12,
		) );
	}
	if ( ! is_wp_error( $chips ) && ! empty( $chips ) ) {
		echo '<div class="mnsk7-plp-chips col-full" role="navigation" aria-label="' . esc_attr__( 'Kategorie', 'mnsk7-storefront' ) . '">';
		foreach ( $chips as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) { continue; }
			$active = (int) $current_term->term_id === (int) $term->term_id;
			printf( '<a href="%s" class="mnsk7-plp-chip %s">%s</a>', esc_url( $link ), $active ? 'mnsk7-plp-chip--active' : '', esc_html( $term->name ) );
		}
		echo '</div>';
	}
	$attr_data = function_exists( 'mnsk7_get_archive_attribute_filter_chips' ) ? mnsk7_get_archive_attribute_filter_chips() : array( 'filters' => array() );
	if ( ! empty( $attr_data['filters'] ) ) {
		foreach ( $attr_data['filters'] as $attribute_filter ) {
			if ( empty( $attribute_filter['chips'] ) ) { continue; }
			echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--attrs col-full" role="navigation" aria-label="' . esc_attr__( 'Filtruj', 'mnsk7-storefront' ) . '">';
			echo '<span class="mnsk7-plp-chips__label">' . esc_html( $attribute_filter['label'] ) . '</span>';
			foreach ( $attribute_filter['chips'] as $slug => $label ) {
				$url = add_query_arg( $attribute_filter['param'], $slug );
				$active = isset( $_GET[ $attribute_filter['param'] ] ) && sanitize_text_field( wp_unslash( $_GET[ $attribute_filter['param'] ] ) ) === $slug;
				printf( '<a href="%s" class="mnsk7-plp-chip %s">%s</a>', esc_url( $url ), $active ? 'mnsk7-plp-chip--active' : '', esc_html( $label ) );
			}
			echo '</div>';
		}
	}
}

if ( is_shop() && ! $is_taxonomy && taxonomy_exists( 'product_cat' ) ) {
	$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 20 ) );
	if ( ! is_wp_error( $top_cats ) && ! empty( $top_cats ) ) {
		echo '<div class="mnsk7-plp-chips col-full" role="navigation" aria-label="' . esc_attr__( 'Kategorie', 'mnsk7-storefront' ) . '">';
		foreach ( $top_cats as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) { continue; }
			echo '<a href="' . esc_url( $link ) . '" class="mnsk7-plp-chip">' . esc_html( $term->name ) . '</a>';
		}
		echo '</div>';
	}
}

if ( woocommerce_product_loop() ) {
	echo '<div class="mnsk7-plp-toolbar col-full">';
	do_action( 'woocommerce_before_shop_loop' );
	echo '</div>';

	if ( $is_taxonomy ) {
		// FB-05: search over table
		?>
		<div class="mnsk7-plp-search col-full">
			<form role="search" method="get" class="mnsk7-plp-search__form" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
				<label for="mnsk7-plp-search-input" class="screen-reader-text"><?php esc_html_e( 'Szukaj produktów', 'mnsk7-storefront' ); ?></label>
				<input type="search" id="mnsk7-plp-search-input" class="mnsk7-plp-search__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Szukaj w kategorii…', 'mnsk7-storefront' ); ?>" />
				<?php if ( isset( $current_term->slug ) ) : ?>
				<input type="hidden" name="product_cat" value="<?php echo esc_attr( $current_term->slug ); ?>" />
				<?php endif; ?>
				<button type="submit" class="mnsk7-plp-search__submit"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></button>
			</form>
		</div>
		<?php
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
	$filter_params = array( 'filter_srednica', 'filter_srednica-trzpienia', 'filter_dlugosc-calkowita-l', 'filter_dlugosc-robocza-h' );
	$has_filter = false;
	foreach ( $filter_params as $p ) {
		if ( ! empty( $_GET[ $p ] ) ) {
			$has_filter = true;
			break;
		}
	}
	if ( $is_taxonomy && $has_filter ) {
		$clear_url = remove_query_arg( $filter_params );
		echo '<p class="mnsk7-plp-no-results col-full">';
		echo esc_html__( 'Brak produktów dla wybranego filtra.', 'mnsk7-storefront' );
		echo ' <a href="' . esc_url( $clear_url ) . '">' . esc_html__( 'Wyczyść filtr i pokaż wszystkie', 'mnsk7-storefront' ) . '</a>';
		echo '</p>';
	}
	do_action( 'woocommerce_no_products_found' );
}

do_action( 'woocommerce_after_main_content' );
if ( ! $is_taxonomy ) {
	do_action( 'woocommerce_sidebar' );
}

get_footer( 'shop' );
