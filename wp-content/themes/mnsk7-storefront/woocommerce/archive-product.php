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

/* PLP-12: na stronie wyników wyszukiwania — link „Wyczyść wyszukiwanie" */
if ( is_search() && get_query_var( 'post_type' ) === 'product' ) {
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : get_permalink( wc_get_page_id( 'shop' ) );
	if ( $shop_url ) {
		echo '<p class="mnsk7-plp-search-clear col-full">';
		echo '<a href="' . esc_url( $shop_url ) . '" class="mnsk7-plp-search-clear__link">' . esc_html__( 'Wyczyść wyszukiwanie', 'mnsk7-storefront' ) . '</a>';
		echo '</p>';
	}
}

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
			printf( '<a href="%s" class="mnsk7-plp-chip %s">%s</a>', esc_url( $link ), $active ? 'mnsk7-plp-chip--active' : '', esc_html( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name ) );
		}
		echo '</div>';
	}
	$attr_data = function_exists( 'mnsk7_get_archive_attribute_filter_chips' ) ? mnsk7_get_archive_attribute_filter_chips() : array( 'filters' => array() );
	$plp_chips_limit = 8; /* PLP-08: max chipów widocznych bez „Więcej" */
	if ( ! empty( $attr_data['filters'] ) ) {
		foreach ( $attr_data['filters'] as $attribute_filter ) {
			if ( empty( $attribute_filter['chips'] ) ) { continue; }
			$aria_label = sprintf( /* translators: %s: filter group name e.g. Średnica */ __( 'Filtruj: %s', 'mnsk7-storefront' ), $attribute_filter['label'] );
			$chips_list = $attribute_filter['chips'];
			$param      = $attribute_filter['param'];
			$visible    = array_slice( $chips_list, 0, $plp_chips_limit, true );
			$hidden     = array_slice( $chips_list, $plp_chips_limit, null, true );
			echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--attrs col-full" role="navigation" aria-label="' . esc_attr( $aria_label ) . '">';
			echo '<span class="mnsk7-plp-chips__label">' . esc_html( $attribute_filter['label'] ) . '</span>';
			foreach ( $visible as $slug => $label ) {
				$url    = add_query_arg( $param, $slug );
				$active = isset( $_GET[ $param ] ) && sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) === $slug;
				printf( '<a href="%s" class="mnsk7-plp-chip %s">%s</a>', esc_url( $url ), $active ? 'mnsk7-plp-chip--active' : '', esc_html( $label ) );
			}
			if ( ! empty( $hidden ) ) {
				echo '<span class="mnsk7-plp-chips-more" id="mnsk7-plp-more-' . esc_attr( sanitize_title( $param ) ) . '" hidden>';
				foreach ( $hidden as $slug => $label ) {
					$url    = add_query_arg( $param, $slug );
					$active = isset( $_GET[ $param ] ) && sanitize_text_field( wp_unslash( $_GET[ $param ] ) ) === $slug;
					printf( '<a href="%s" class="mnsk7-plp-chip %s">%s</a>', esc_url( $url ), $active ? 'mnsk7-plp-chip--active' : '', esc_html( $label ) );
				}
				echo '</span>';
				echo '<button type="button" class="mnsk7-plp-chips-toggle" data-controls="mnsk7-plp-more-' . esc_attr( sanitize_title( $param ) ) . '" aria-expanded="false">' . esc_html__( 'Więcej', 'mnsk7-storefront' ) . '</button>';
			}
			echo '</div>';
		}
	}
	/* Wybrane filtry: pokaż aktywne i link „Reset” (UX audit) */
	$filter_params = array( 'filter_srednica', 'filter_srednica-trzpienia', 'filter_dlugosc-calkowita-l', 'filter_dlugosc-robocza-h' );
	$active_filters = array();
	foreach ( $filter_params as $param ) {
		if ( ! empty( $_GET[ $param ] ) ) {
			$val = sanitize_text_field( wp_unslash( $_GET[ $param ] ) );
			$active_filters[ $param ] = $val;
		}
	}
	if ( ! empty( $active_filters ) ) {
		$term_link = get_term_link( $current_term );
		$clear_url = is_wp_error( $term_link ) ? wc_get_page_permalink( 'shop' ) : $term_link;
		echo '<div class="mnsk7-plp-selected col-full">';
		echo '<span class="mnsk7-plp-selected__label">' . esc_html__( 'Wybrane:', 'mnsk7-storefront' ) . '</span>';
		foreach ( $active_filters as $param => $val ) {
			$without = remove_query_arg( $param );
			echo '<a href="' . esc_url( $without ) . '" class="mnsk7-plp-chip mnsk7-plp-chip--active mnsk7-plp-chip--remove" aria-label="' . esc_attr__( 'Usuń filtr', 'mnsk7-storefront' ) . '">' . esc_html( $val ) . ' ×</a>';
		}
		echo ' <a href="' . esc_url( $clear_url ) . '" class="mnsk7-plp-reset">' . esc_html__( 'Wyczyść wszystkie', 'mnsk7-storefront' ) . '</a>';
		echo '</div>';
	}
}

if ( is_shop() && ! $is_taxonomy && taxonomy_exists( 'product_cat' ) ) {
	$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 20 ) );
	if ( ! is_wp_error( $top_cats ) && ! empty( $top_cats ) ) {
		echo '<div class="mnsk7-plp-chips col-full" role="navigation" aria-label="' . esc_attr__( 'Kategorie', 'mnsk7-storefront' ) . '">';
		foreach ( $top_cats as $term ) {
			$link = get_term_link( $term );
			if ( is_wp_error( $link ) ) { continue; }
			echo '<a href="' . esc_url( $link ) . '" class="mnsk7-plp-chip">' . esc_html( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name ) . '</a>';
		}
		echo '</div>';
	}
}

$use_table = is_shop() || $is_taxonomy;
$GLOBALS['mnsk7_plp_use_table'] = $use_table;

if ( woocommerce_product_loop() ) {
	/* PLP-05/PLP-10: bez toolbara u góry — sortowanie i paginacja tylko na dole; przy tabeli tylko „Pokaż więcej” */
	if ( $use_table ) {
		if ( $is_taxonomy && $current_term && isset( $current_term->slug ) ) {
			$is_tag = isset( $current_term->taxonomy ) && $current_term->taxonomy === 'product_tag';
			$search_placeholder = $is_tag ? __( 'Szukaj w tagu…', 'mnsk7-storefront' ) : __( 'Szukaj w kategorii…', 'mnsk7-storefront' );
			?>
			<div class="mnsk7-plp-search col-full">
				<form role="search" method="get" class="mnsk7-plp-search__form" action="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>">
					<label for="mnsk7-plp-search-input" class="screen-reader-text"><?php esc_html_e( 'Szukaj produktów', 'mnsk7-storefront' ); ?></label>
					<input type="search" id="mnsk7-plp-search-input" class="mnsk7-plp-search__input" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php echo esc_attr( $search_placeholder ); ?>" />
					<?php if ( $is_tag ) : ?>
						<input type="hidden" name="product_tag" value="<?php echo esc_attr( $current_term->slug ); ?>" />
					<?php else : ?>
						<input type="hidden" name="product_cat" value="<?php echo esc_attr( $current_term->slug ); ?>" />
					<?php endif; ?>
					<button type="submit" class="mnsk7-plp-search__submit"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></button>
				</form>
			</div>
			<?php
		}
		?>
		<div class="mnsk7-product-table-wrap col-full">
			<table class="mnsk7-product-table shop_table">
				<thead>
					<tr>
						<th class="mnsk7-table-cell--thumb"><?php esc_html_e( 'Zdjęcie', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--title"><?php esc_html_e( 'Produkt', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--price"><?php esc_html_e( 'Cena', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--stock"><?php esc_html_e( 'Na stanie', 'mnsk7-storefront' ); ?></th>
						<th class="mnsk7-table-cell--qty"><?php esc_html_e( 'Ilość', 'mnsk7-storefront' ); ?></th>
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
		/* PLP-09: na mobile (≤768px) grid zamiast tabeli — drugi loop w ukrytym kontenerze */
		rewind_posts();
		echo '<div class="mnsk7-plp-grid-mobile col-full">';
		woocommerce_product_loop_start();
		if ( wc_get_loop_prop( 'total' ) ) {
			while ( have_posts() ) {
				the_post();
				do_action( 'woocommerce_shop_loop' );
				wc_get_template_part( 'content', 'product' );
			}
		}
		woocommerce_product_loop_end();
		echo '</div>';
		rewind_posts();
		?>
		<?php
		/* Przy kilku stronach: przycisk „Pokaż więcej” — JS ładuje następne wiersze w tabelę (AJAX), bez przejścia na page/2 */
		if ( $use_table ) {
			$paged = max( 1, get_query_var( 'paged' ) );
			$total_pages = $GLOBALS['wp_query']->max_num_pages;
			$total = (int) $GLOBALS['wp_query']->found_posts;
			$per_page = (int) $GLOBALS['wp_query']->get( 'posts_per_page' );
			if ( $per_page < 1 ) {
				$per_page = 12;
			}
			if ( $total_pages > 1 && $paged < $total_pages ) {
				$next_url = add_query_arg( 'paged', $paged + 1 );
				$taxonomy = '';
				$term_slug = '';
				if ( $is_taxonomy && $current_term && isset( $current_term->taxonomy, $current_term->slug ) ) {
					$taxonomy  = $current_term->taxonomy;
					$term_slug = $current_term->slug;
				}
				echo '<div class="mnsk7-plp-load-more-wrap col-full" data-current-page="' . esc_attr( (string) $paged ) . '" data-total-pages="' . esc_attr( (string) $total_pages ) . '" data-taxonomy="' . esc_attr( $taxonomy ) . '" data-term="' . esc_attr( $term_slug ) . '" data-per-page="' . esc_attr( (string) $per_page ) . '" data-total="' . esc_attr( (string) $total ) . '">';
				echo '<a href="' . esc_url( $next_url ) . '" class="mnsk7-plp-load-more button">' . esc_html__( 'Pokaż więcej', 'mnsk7-storefront' ) . '</a>';
				echo '</div>';
			}
		}
	} else {
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

	echo '<div class="mnsk7-plp-toolbar mnsk7-plp-toolbar--bottom col-full">';
	do_action( 'woocommerce_after_shop_loop' );
	echo '</div>';
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
		echo '<div class="mnsk7-plp-empty col-full" role="status">';
		echo '<p class="mnsk7-plp-empty__text">' . esc_html__( 'Brak produktów dla wybranych filtrów.', 'mnsk7-storefront' ) . '</p>';
		echo '<p class="mnsk7-plp-empty__hint">' . esc_html__( 'Zmień kryteria lub zobacz całą kategorię.', 'mnsk7-storefront' ) . '</p>';
		echo '<a href="' . esc_url( $clear_url ) . '" class="button mnsk7-plp-empty__cta">' . esc_html__( 'Wyczyść filtry', 'mnsk7-storefront' ) . '</a>';
		echo '</div>';
	}
	do_action( 'woocommerce_no_products_found' );
}

do_action( 'woocommerce_after_main_content' );
if ( ! $use_table ) {
	do_action( 'woocommerce_sidebar' );
}

get_footer( 'shop' );
