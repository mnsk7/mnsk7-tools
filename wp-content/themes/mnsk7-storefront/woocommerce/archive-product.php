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

get_header();

/* Okruszki w szablonie — niezależnie od hooków, żeby nic ich nie nadpisywało (sklep, kategoria, tag, wyszukiwanie). Jedno źródło: mnsk7_is_plp(). */
if ( function_exists( 'woocommerce_breadcrumb' ) ) {
	$show_breadcrumb = ( function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp() )
		|| ( is_search() && get_query_var( 'post_type' ) === 'product' );
	if ( $show_breadcrumb ) {
		woocommerce_breadcrumb();
	}
}

do_action( 'woocommerce_before_main_content' );

$is_taxonomy = is_product_taxonomy();
$current_term = $is_taxonomy ? get_queried_object() : null;

do_action( 'woocommerce_shop_loop_header' );

echo '<div class="mnsk7-plp-archive-wrap col-full">';
echo '<div class="mnsk7-plp-content col-full">';

/* PLP-12: na stronie wyników wyszukiwania — link „Wyczyść wyszukiwanie" i liczba wyników */
if ( is_search() && get_query_var( 'post_type' ) === 'product' ) {
	global $wp_query;
	$found = isset( $wp_query->found_posts ) ? (int) $wp_query->found_posts : 0;
	$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : get_permalink( wc_get_page_id( 'shop' ) );
	if ( $shop_url ) {
		echo '<p class="mnsk7-plp-search-clear col-full">';
		echo '<a href="' . esc_url( $shop_url ) . '" class="mnsk7-plp-search-clear__link">' . esc_html__( 'Wyczyść wyszukiwanie', 'mnsk7-storefront' ) . '</a>';
		if ( $found >= 0 ) {
			echo ' <span class="mnsk7-plp-search-count">';
			echo esc_html( sprintf(
				/* translators: %d: number of products found */
				_n( 'Znaleziono %d produkt', 'Znaleziono %d produktów', $found, 'mnsk7-storefront' ),
				$found
			) );
			echo '</span>';
		}
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
	$attr_data = function_exists( 'mnsk7_get_archive_attribute_filter_chips' ) ? mnsk7_get_archive_attribute_filter_chips() : array( 'filters' => array(), 'filter_params' => array() );
	$plp_chips_limit   = 8; /* PLP-08: max chipów w jednym wierszu bez „Więcej" */
	$plp_attr_visible  = 4; /* Liczba wierszy filtrów (Średnica, Dł. robocza…) widocznych od razu; reszta pod „Więcej filtrów” */
	$all_filters       = isset( $attr_data['filters'] ) ? $attr_data['filters'] : array();
	$visible_filters   = array_slice( $all_filters, 0, $plp_attr_visible, true );
	$hidden_filters    = array_slice( $all_filters, $plp_attr_visible, null, true );
	$has_hidden_rows   = ! empty( $hidden_filters );
	$active_in_hidden  = false;
	if ( $has_hidden_rows ) {
		foreach ( $hidden_filters as $attribute_filter ) {
			$param = isset( $attribute_filter['param'] ) ? $attribute_filter['param'] : '';
			if ( $param && ! empty( $_GET[ $param ] ) ) {
				$active_in_hidden = true;
				break;
			}
		}
	}

	$render_filter_row = function ( $attribute_filter ) use ( $plp_chips_limit ) {
		if ( empty( $attribute_filter['chips'] ) ) { return; }
		$aria_label = sprintf( /* translators: %s: filter group name */ __( 'Filtruj: %s', 'mnsk7-storefront' ), $attribute_filter['label'] );
		$chips_list = $attribute_filter['chips'];
		$param      = $attribute_filter['param'];
		$visible    = array_slice( $chips_list, 0, $plp_chips_limit, true );
		$hidden     = array_slice( $chips_list, $plp_chips_limit, null, true );
		echo '<div class="mnsk7-plp-chips mnsk7-plp-chips--attrs col-full" role="navigation" aria-label="' . esc_attr( $aria_label ) . '">';
		echo '<span class="mnsk7-plp-chips__label">' . esc_html( $attribute_filter['label'] ) . '</span>';
		echo '<div class="mnsk7-plp-chips__scroll">';
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
		echo '</div>';
	};

	if ( ! empty( $visible_filters ) ) {
		foreach ( $visible_filters as $attribute_filter ) {
			$render_filter_row( $attribute_filter );
		}
	}
	if ( $has_hidden_rows ) {
		$filters_expanded = $active_in_hidden;
		echo '<div class="mnsk7-plp-filters-toggle-wrap col-full">';
		echo '<button type="button" class="mnsk7-plp-chips-toggle mnsk7-plp-filters-toggle" data-controls="mnsk7-plp-more-filters" data-more-text="' . esc_attr__( 'Więcej filtrów', 'mnsk7-storefront' ) . '" data-less-text="' . esc_attr__( 'Mniej filtrów', 'mnsk7-storefront' ) . '" aria-expanded="' . ( $filters_expanded ? 'true' : 'false' ) . '">' . esc_html( $filters_expanded ? __( 'Mniej filtrów', 'mnsk7-storefront' ) : __( 'Więcej filtrów', 'mnsk7-storefront' ) ) . '</button>';
		echo '</div>';
		echo '<div class="mnsk7-plp-filters-more col-full" id="mnsk7-plp-more-filters"' . ( $filters_expanded ? '' : ' hidden' ) . '>';
		foreach ( $hidden_filters as $attribute_filter ) {
			$render_filter_row( $attribute_filter );
		}
		echo '</div>';
	}
	/* Wybrane filtry: pokaż aktywne i link „Reset” (UX audit) — parametry z faktycznie wyświetlanych chipów */
	$filter_params = isset( $attr_data['filter_params'] ) && is_array( $attr_data['filter_params'] ) ? $attr_data['filter_params'] : array();
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
		echo ' <a href="' . esc_url( $clear_url ) . '" class="button mnsk7-plp-reset">' . esc_html__( 'Wyczyść wszystkie', 'mnsk7-storefront' ) . '</a>';
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

/* Jeden layout na request: mobile (user-agent) = karty, desktop = tabela. W DOM tylko jeden blok. */
$plp_is_mobile = function_exists( 'mnsk7_is_mobile_request' ) && mnsk7_is_mobile_request();

if ( woocommerce_product_loop() ) {
	/* PLP-05/PLP-10: bez toolbara u góry — sortowanie i paginacja tylko na dole; przy tabeli tylko „Pokaż więcej” */
	if ( $use_table ) {
		if ( $plp_is_mobile ) {
			/* Mobile: tylko siatka kart (jedna pętla, bez tabeli w DOM). */
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
			if ( function_exists( 'mnsk7_render_trust_badges' ) ) {
				echo '<div class="mnsk7-plp-trust-wrap col-full">';
				mnsk7_render_trust_badges( 'mnsk7-plp-trust' );
				echo '</div>';
			}
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
		} else {
			/* Desktop/tablet: tylko tabela (bez siatki kart w DOM). */
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
			if ( function_exists( 'mnsk7_render_trust_badges' ) ) {
				echo '<div class="mnsk7-plp-trust-wrap col-full">';
				mnsk7_render_trust_badges( 'mnsk7-plp-trust' );
				echo '</div>';
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
		}
		/* Przy kilku stronach (desktop): przycisk „Pokaż więcej” — AJAX. Na mobile paginacja z after_shop_loop. */
		if ( $use_table && ! $plp_is_mobile ) {
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
	echo '</div><!-- .mnsk7-plp-content -->';

	echo '<div class="mnsk7-plp-toolbar mnsk7-plp-toolbar--bottom col-full">';
	do_action( 'woocommerce_after_shop_loop' );
	echo '</div>';
	echo '</div><!-- .mnsk7-plp-archive-wrap -->';
} else {
	echo '</div><!-- .mnsk7-plp-content -->';
	$all_filter_params = function_exists( 'mnsk7_get_all_attribute_filter_param_names' ) ? mnsk7_get_all_attribute_filter_param_names() : array();
	$has_filter        = false;
	foreach ( $all_filter_params as $p ) {
		if ( ! empty( $_GET[ $p ] ) ) {
			$has_filter = true;
			break;
		}
	}
	if ( $is_taxonomy && $has_filter ) {
		// Jeden spójny blok empty state — bez duplikatu komunikatu WooCommerce.
		remove_action( 'woocommerce_no_products_found', 'wc_no_products_found', 10 );
		$clear_url = ! empty( $all_filter_params ) ? remove_query_arg( $all_filter_params ) : ( $current_term && ! is_wp_error( get_term_link( $current_term ) ) ? get_term_link( $current_term ) : ( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : '' ) );
		echo '<div class="mnsk7-plp-empty col-full" role="status">';
		echo '<p class="mnsk7-plp-empty__text">' . esc_html__( 'Brak produktów dla wybranych filtrów.', 'mnsk7-storefront' ) . '</p>';
		echo '<p class="mnsk7-plp-empty__hint">' . esc_html__( 'Zmień kryteria lub zobacz całą kategorię.', 'mnsk7-storefront' ) . '</p>';
		echo '<a href="' . esc_url( $clear_url ) . '" class="button mnsk7-plp-empty__cta">' . esc_html__( 'Wyczyść filtry', 'mnsk7-storefront' ) . '</a>';
		echo '</div>';
	}
	do_action( 'woocommerce_no_products_found' );
	echo '</div><!-- .mnsk7-plp-archive-wrap -->';
}

do_action( 'woocommerce_after_main_content' );
if ( ! $use_table ) {
	do_action( 'woocommerce_sidebar' );
}

get_footer( 'shop' );
