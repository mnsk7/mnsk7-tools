<?php
/**
 * MNK7 Tools — bloki karty produktu (parametry, zastosowanie, dostępność, trust badges).
 * Podpięte przez hooki WooCommerce — nie wymaga edycji szablonów.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Krótkie etykiety dla tabeli key params (design contract: Średnica robocza, Trzpień).
 *
 * @return array [ 'długa etykieta' => 'krótka etykieta' ]
 */
function mnsk7_get_key_param_short_labels() {
	return array(
		__( 'Średnica części roboczej', 'mnsk7-tools' ) => __( 'Średnica robocza', 'mnsk7-tools' ),
		__( 'Średnica trzpienia', 'mnsk7-tools' )       => __( 'Trzpień', 'mnsk7-tools' ),
		__( 'Długość robocza', 'mnsk7-tools' )          => __( 'Dł. robocza', 'mnsk7-tools' ),
		__( 'Długość całkowita', 'mnsk7-tools' )       => __( 'Dł. całkowita', 'mnsk7-tools' ),
		__( 'Kąt skosu', 'mnsk7-tools' )               => __( 'Kąt', 'mnsk7-tools' ),
		__( 'Liczba zębów', 'mnsk7-tools' )             => __( 'Ilość ostrzy', 'mnsk7-tools' ),
		__( 'Materiał obróbki', 'mnsk7-tools' )         => __( 'Materiał', 'mnsk7-tools' ),
		__( 'Typ operacji', 'mnsk7-tools' )             => __( 'Typ', 'mnsk7-tools' ),
		__( 'Chwyt / trzpienie', 'mnsk7-tools' )        => __( 'Trzpień', 'mnsk7-tools' ),
		__( 'Trzpienie / chwyt', 'mnsk7-tools' )        => __( 'Trzpień', 'mnsk7-tools' ),
	);
}

/**
 * Key product attributes for catalog display (content_catalog_rules).
 * Tylko jedna etykieta na atrybut — używamy pa_* jako klucza kanonicznego, żeby uniknąć duplikatów.
 * Kolejność wyświetlania w tabelce.
 */
function mnsk7_get_key_param_attributes() {
	return array(
		'pa_srednica'             => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'pa_fi'                   => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'pa_srednica-trzpienia'   => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'pa_dlugosc-robocza-h'    => __( 'Długość robocza', 'mnsk7-tools' ),
		'pa_dlugosc-calkowita-l'  => __( 'Długość całkowita', 'mnsk7-tools' ),
		'pa_kat-skosu'            => __( 'Kąt skosu', 'mnsk7-tools' ),
		'pa_r'                    => __( 'Promień R', 'mnsk7-tools' ),
		'pa_typ'                  => __( 'Typ', 'mnsk7-tools' ),
		'pa_ksztalt'              => __( 'Kształt', 'mnsk7-tools' ),
		'pa_zastosowanie'         => __( 'Zastosowanie', 'mnsk7-tools' ),
		'pa_material'             => __( 'Materiał obróbki', 'mnsk7-tools' ),
		'pa_typ-operacji'         => __( 'Typ operacji', 'mnsk7-tools' ),
		'pa_pokrycie'             => __( 'Pokrycie', 'mnsk7-tools' ),
		'pa_liczba-zebow'         => __( 'Liczba zębów', 'mnsk7-tools' ),
		'pa_chwyt'                => __( 'Chwyt / trzpienie', 'mnsk7-tools' ),
		'pa_trzpienie'            => __( 'Trzpienie / chwyt', 'mnsk7-tools' ),
	);
}

/**
 * Parsuje krótki opis produktu i wyciąga parametry w formacie "Nazwa (X) = wartość"
 * lub "Nazwa = wartość". Fallback gdy WC-atrybuty nie są ustawione.
 *
 * @param WC_Product $product
 * @return array Tablica ['etykieta' => 'wartość']
 */
function mnsk7_parse_excerpt_params( $product ) {
	$excerpt = $product->get_short_description();
	if ( empty( $excerpt ) ) {
		return array();
	}

	$text   = wp_strip_all_tags( $excerpt );
	$lines  = preg_split( '/[\n\r<br>]+/', $text );
	$params = array();

	foreach ( $lines as $line ) {
		$line = trim( $line );
		if ( $line === '' ) {
			continue;
		}
		/* Format: "Etykieta (X) = wartość mm" lub "Etykieta = wartość" */
		if ( preg_match( '/^([^\=]+?)\s*=\s*(.+)$/', $line, $m ) ) {
			$label = trim( preg_replace( '/\s*\([A-Z]+\)\s*$/', '', trim( $m[1] ) ) );
			$value = trim( $m[2] );
			if ( $label !== '' && $value !== '' && strlen( $label ) < 60 ) {
				/* Unikamy duplikatów */
				$params[ $label ] = $value;
			}
		}
	}

	return $params;
}

/**
 * Map key-param slug to WooCommerce attribute taxonomy (pa_*) used in category filters.
 *
 * @param string $slug Attribute key from get_key_param_attributes (e.g. dlugosc-robocza-h, pa_srednica).
 * @return string|null Taxonomy name or null if not a filterable attribute.
 */
function mnsk7_key_param_slug_to_taxonomy( $slug ) {
	if ( ! is_string( $slug ) || $slug === '' || strpos( $slug, 'excerpt_' ) === 0 ) {
		return null;
	}
	$taxonomy = ( strpos( $slug, 'pa_' ) === 0 ) ? $slug : 'pa_' . $slug;
	return taxonomy_exists( $taxonomy ) ? $taxonomy : null;
}

function mnsk7_get_product_primary_category_id( $product ) {
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return 0;
	}
	$cat_ids = $product->get_category_ids();
	if ( empty( $cat_ids ) ) {
		return 0;
	}
	return (int) $cat_ids[0];
}

/**
 * Product IDs from WPC Linked Variation group for current product.
 *
 * Production uses WPC linked groups as the source of "same model" products.
 * When a group exists, PDP key-param switches must stay inside it instead of
 * widening to the whole product category.
 *
 * @param int    $product_id Current product ID.
 * @param string $taxonomy   Attribute taxonomy currently rendered.
 * @return int[]
 */
function mnsk7_get_wpclv_model_product_ids( $product_id, $taxonomy = '' ) {
	$product_id = absint( $product_id );
	if ( $product_id <= 0 || ! post_type_exists( 'wpclv' ) ) {
		return array();
	}

	$cache_key = 'mnsk7_wpclv_model_products_' . $product_id . '_' . sanitize_key( $taxonomy );
	$cached    = wp_cache_get( $cache_key, 'mnsk7_product_card' );
	if ( false !== $cached ) {
		return is_array( $cached ) ? $cached : array();
	}

	$groups = get_posts( array(
		'post_type'              => 'wpclv',
		'post_status'            => 'publish',
		'posts_per_page'         => 100,
		'fields'                 => 'ids',
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
	) );

	if ( empty( $groups ) ) {
		wp_cache_set( $cache_key, array(), 'mnsk7_product_card', HOUR_IN_SECONDS );
		return array();
	}

	$taxonomy_id = 0;
	if ( $taxonomy && function_exists( 'wc_attribute_taxonomy_id_by_name' ) ) {
		$taxonomy_id = absint( wc_attribute_taxonomy_id_by_name( str_replace( 'pa_', '', $taxonomy ) ) );
	}

	$fallback = array();
	foreach ( $groups as $group_id ) {
		$raw  = get_post_meta( $group_id, 'wpclv_link', true );
		$data = maybe_unserialize( $raw );
		if ( is_string( $data ) ) {
			$data = maybe_unserialize( $data );
		}
		if ( ! is_array( $data ) || empty( $data['products'] ) ) {
			continue;
		}

		$ids = array_values( array_filter( array_map( 'absint', explode( ',', (string) $data['products'] ) ) ) );
		if ( ! in_array( $product_id, $ids, true ) ) {
			continue;
		}

		$attr_ids = array();
		if ( ! empty( $data['attributes'] ) && is_array( $data['attributes'] ) ) {
			foreach ( $data['attributes'] as $attr_ref ) {
				if ( preg_match( '/^id:(\d+)$/', (string) $attr_ref, $m ) ) {
					$attr_ids[] = absint( $m[1] );
				}
			}
		}

		if ( $taxonomy_id > 0 && in_array( $taxonomy_id, $attr_ids, true ) ) {
			wp_cache_set( $cache_key, $ids, 'mnsk7_product_card', HOUR_IN_SECONDS );
			return $ids;
		}

		if ( empty( $fallback ) ) {
			$fallback = $ids;
		}
	}

	wp_cache_set( $cache_key, $fallback, 'mnsk7_product_card', HOUR_IN_SECONDS );
	return $fallback;
}

/**
 * Direct product links for a key parameter inside the current production model group.
 *
 * @param WC_Product $product Current product.
 * @param string     $taxonomy Attribute taxonomy.
 * @return array<int,array{product_id:int,value:string,url:string,current:bool}>
 */
function mnsk7_get_key_param_model_links( $product, $taxonomy ) {
	if ( ! is_a( $product, 'WC_Product' ) || ! $taxonomy || ! taxonomy_exists( $taxonomy ) ) {
		return array();
	}

	$model_ids = mnsk7_get_wpclv_model_product_ids( $product->get_id(), $taxonomy );
	if ( empty( $model_ids ) ) {
		return array();
	}

	$options = array();
	$current_attrs = array();
	foreach ( array_keys( mnsk7_get_key_param_attributes() ) as $attr_slug ) {
		$attr_tax = mnsk7_key_param_slug_to_taxonomy( $attr_slug );
		if ( ! $attr_tax || $attr_tax === $taxonomy ) {
			continue;
		}
		$current_value = trim( wp_strip_all_tags( $product->get_attribute( $attr_tax ) ) );
		if ( $current_value !== '' ) {
			$current_attrs[ $attr_tax ] = $current_value;
		}
	}

	foreach ( $model_ids as $product_id ) {
		$linked = wc_get_product( $product_id );
		if ( ! $linked || get_post_status( $product_id ) !== 'publish' ) {
			continue;
		}

		$value = trim( wp_strip_all_tags( $linked->get_attribute( $taxonomy ) ) );
		if ( '' === $value ) {
			continue;
		}

		$score = ( (int) $product_id === (int) $product->get_id() ) ? 1000 : 0;
		foreach ( $current_attrs as $attr_tax => $current_value ) {
			$linked_value = trim( wp_strip_all_tags( $linked->get_attribute( $attr_tax ) ) );
			if ( $linked_value !== '' && $linked_value === $current_value ) {
				$score++;
			}
		}
		if ( $linked->is_in_stock() ) {
			$score += 0.1;
		}

		$key = sanitize_title( $value );
		if ( isset( $options[ $key ] ) && $score <= $options[ $key ]['score'] ) {
			continue;
		}

		$options[ $key ] = array(
			'product_id' => (int) $product_id,
			'value'      => $value,
			'url'        => get_permalink( $product_id ),
			'current'    => ( (int) $product_id === (int) $product->get_id() ),
			'score'      => $score,
		);
	}

	if ( count( $options ) < 2 ) {
		return array();
	}

	uasort( $options, static function ( $a, $b ) {
		$an = (float) str_replace( ',', '.', preg_replace( '/[^0-9,.\-]/', '', $a['value'] ) );
		$bn = (float) str_replace( ',', '.', preg_replace( '/[^0-9,.\-]/', '', $b['value'] ) );
		if ( $an === $bn ) {
			return strnatcasecmp( $a['value'], $b['value'] );
		}
		return $an <=> $bn;
	} );

	return array_map( static function ( $option ) {
		unset( $option['score'] );
		return $option;
	}, array_values( $options ) );
}

function mnsk7_get_model_variant_label( $linked ) {
	if ( ! is_a( $linked, 'WC_Product' ) ) {
		return '';
	}

	$title = wp_strip_all_tags( $linked->get_name() );
	if ( preg_match( '/\b([0-9]+L)\b/i', $title, $m ) ) {
		return strtoupper( $m[1] );
	}
	if ( preg_match( '/\b([0-9]+P)\b/i', $title, $m ) ) {
		return strtoupper( $m[1] );
	}

	$sku = trim( (string) $linked->get_sku() );
	if ( $sku !== '' ) {
		return $sku;
	}

	return (string) $linked->get_id();
}

function mnsk7_get_model_variant_links( $product ) {
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return array();
	}

	$model_ids = mnsk7_get_wpclv_model_product_ids( $product->get_id(), '' );
	if ( count( $model_ids ) < 2 ) {
		return array();
	}

	$options = array();
	foreach ( $model_ids as $product_id ) {
		$linked = wc_get_product( $product_id );
		if ( ! $linked || get_post_status( $product_id ) !== 'publish' ) {
			continue;
		}

		$label = mnsk7_get_model_variant_label( $linked );
		if ( $label === '' ) {
			continue;
		}

		$options[] = array(
			'product_id' => (int) $product_id,
			'value'      => $label,
			'url'        => get_permalink( $product_id ),
			'current'    => ( (int) $product_id === (int) $product->get_id() ),
		);
	}

	if ( count( $options ) < 2 ) {
		return array();
	}

	usort( $options, static function ( $a, $b ) {
		return strnatcasecmp( $a['value'], $b['value'] );
	} );

	return $options;
}

function mnsk7_render_key_param_model_links( $links ) {
	echo '<div class="mnsk7-key-param-options" role="list">';
	foreach ( $links as $link ) {
		$class = 'mnsk7-key-param-option' . ( ! empty( $link['current'] ) ? ' is-current' : '' );
		echo '<a class="' . esc_attr( $class ) . '" role="listitem" href="' . esc_url( $link['url'] ) . '"' . ( ! empty( $link['current'] ) ? ' aria-current="page"' : '' ) . '>';
		echo esc_html( $link['value'] );
		echo '</a>';
	}
	echo '</div>';
}

/**
 * Get variant options for a key param: other values available in the same category.
 * Used to show select/links on PDP so user can switch to another product (category + filter).
 *
 * @param WC_Product $product    Current product.
 * @param string     $attr_slug  Attribute slug (e.g. pa_dlugosc-robocza-h).
 * @param string     $value      Current display value (e.g. "13 mm").
 * @return array|null { param, category_url, options: [ slug => name ], current_slug } or null.
 */
function mnsk7_get_key_param_variant_options( $product, $attr_slug, $value ) {
	$taxonomy = mnsk7_key_param_slug_to_taxonomy( $attr_slug );
	if ( ! $taxonomy || ! is_a( $product, 'WC_Product' ) ) {
		return null;
	}

	$cat_id = mnsk7_get_product_primary_category_id( $product );
	if ( $cat_id <= 0 ) {
		return null;
	}
	$term_link = get_term_link( $cat_id, 'product_cat' );
	if ( is_wp_error( $term_link ) ) {
		return null;
	}

	$cache_key = 'mnsk7_key_param_variant_options_' . md5( $product->get_id() . '|' . $taxonomy . '|' . $cat_id );
	$cached    = wp_cache_get( $cache_key, 'mnsk7_product_card' );
	if ( false !== $cached ) {
		return $cached;
	}

	$model_product_ids = mnsk7_get_wpclv_model_product_ids( $product->get_id(), $taxonomy );
	$query_args        = array(
		'post_type'              => 'product',
		'post_status'            => 'publish',
		'fields'                 => 'ids',
		'posts_per_page'         => $model_product_ids ? max( 150, count( $model_product_ids ) ) : 150,
		'no_found_rows'          => true,
		'update_post_meta_cache' => false,
		'update_post_term_cache' => false,
		'meta_query'             => array(
			array(
				'key'   => '_stock_status',
				'value' => 'instock',
			),
		),
	);

	if ( $model_product_ids ) {
		$query_args['post__in'] = $model_product_ids;
	} else {
		$query_args['tax_query'] = array(
			array(
				'taxonomy' => 'product_cat',
				'field'    => 'term_id',
				'terms'    => $cat_id,
			),
		);
	}

	$product_ids = get_posts( $query_args );

	if ( empty( $product_ids ) ) {
		wp_cache_set( $cache_key, null, 'mnsk7_product_card', HOUR_IN_SECONDS );
		return null;
	}

	$terms = get_terms( array(
		'taxonomy'   => $taxonomy,
		'hide_empty' => true,
		'object_ids' => $product_ids,
		'orderby'    => 'name',
		'number'     => 50,
	) );
	if ( is_wp_error( $terms ) || empty( $terms ) ) {
		wp_cache_set( $cache_key, null, 'mnsk7_product_card', HOUR_IN_SECONDS );
		return null;
	}

	$options = array();
	foreach ( $terms as $t ) {
		$options[ $t->slug ] = $t->name;
	}
	if ( count( $options ) < 2 ) {
		wp_cache_set( $cache_key, null, 'mnsk7_product_card', HOUR_IN_SECONDS );
		return null;
	}

	$product_terms = wp_get_object_terms( $product->get_id(), $taxonomy );
	$current_slug = null;
	if ( ! is_wp_error( $product_terms ) && ! empty( $product_terms ) ) {
		$current_slug = $product_terms[0]->slug;
	} else {
		$value_lower = is_string( $value ) ? trim( $value ) : '';
		foreach ( $terms as $t ) {
			if ( $t->name === $value_lower || sanitize_title( $t->name ) === sanitize_title( $value_lower ) ) {
				$current_slug = $t->slug;
				break;
			}
		}
	}
	if ( $current_slug === null && ! empty( $options ) ) {
		$current_slug = array_key_first( $options );
	}

	$param = 'filter_' . str_replace( 'pa_', '', $taxonomy );
	$result = array(
		'param'         => $param,
		'category_url'  => $term_link,
		'options'       => $options,
		'current_slug'  => $current_slug,
	);
	wp_cache_set( $cache_key, $result, 'mnsk7_product_card', HOUR_IN_SECONDS );
	return $result;
}

function mnsk7_single_product_key_params() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$labels = mnsk7_get_key_param_attributes();
	$found  = array(); // slug => [ 'label' => long label, 'value' => value ] — jeden wpis na atrybut (tylko pa_*)
	foreach ( array_keys( $labels ) as $slug ) {
		$val = $product->get_attribute( $slug );
		if ( ( $val === '' || $val === null ) && strpos( $slug, 'pa_' ) === 0 ) {
			$val = $product->get_attribute( str_replace( 'pa_', '', $slug ) );
		}
		if ( $val !== '' && $val !== null ) {
			$found[ $slug ] = array( 'label' => $labels[ $slug ], 'value' => $val );
		}
	}

	if ( empty( $found ) ) {
		$parsed = mnsk7_parse_excerpt_params( $product );
		foreach ( $parsed as $label => $value ) {
			$found[ 'excerpt_' . sanitize_title( $label ) ] = array( 'label' => $label, 'value' => $value );
		}
	}

	if ( empty( $found ) ) {
		return;
	}

	$is_variable = $product->is_type( 'variable' );
	$var_attrs   = $is_variable ? $product->get_variation_attributes() : array();
	$short_labels = function_exists( 'mnsk7_get_key_param_short_labels' ) ? mnsk7_get_key_param_short_labels() : array();
	$shown_labels = array();
	$has_model_link_row = false;

	echo '<div class="mnsk7-product-key-params">';
	echo '<h4 class="mnsk7-product-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-tools' ) . '</h4>';
	echo '<dl class="mnsk7-product-key-params__list">';
	foreach ( $found as $slug => $item ) {
		$long_label = $item['label'];
		$value      = $item['value'];
		$display_label = isset( $short_labels[ $long_label ] ) ? $short_labels[ $long_label ] : $long_label;
		if ( isset( $shown_labels[ $display_label ] ) ) {
			continue;
		}
		$shown_labels[ $display_label ] = true;
		echo '<dt>' . esc_html( $display_label ) . '</dt>';
		$is_var_attr = $is_variable && strpos( $slug, 'excerpt_' ) !== 0 && isset( $var_attrs[ $slug ] );
		$taxonomy = ( strpos( $slug, 'excerpt_' ) !== 0 ) ? mnsk7_key_param_slug_to_taxonomy( $slug ) : '';
		$model_links = $taxonomy ? mnsk7_get_key_param_model_links( $product, $taxonomy ) : array();
		if ( $model_links ) {
			echo '<dd class="mnsk7-product-key-params__dd--options">';
			mnsk7_render_key_param_model_links( $model_links );
			echo '</dd>';
			$has_model_link_row = true;
		} elseif ( $is_var_attr && ! empty( $var_attrs[ $slug ] ) ) {
			$name = 'attribute_' . esc_attr( $slug );
			echo '<dd class="mnsk7-product-key-params__dd--select">';
			echo '<select name="' . $name . '" data-attribute_name="' . esc_attr( $slug ) . '" class="mnsk7-key-param-select">';
			echo '<option value="">' . esc_html__( 'Wybierz', 'mnsk7-tools' ) . '</option>';
			foreach ( $var_attrs[ $slug ] as $opt_val ) {
				$term_obj  = get_term_by( 'slug', $opt_val, $slug );
				$opt_label = $term_obj ? $term_obj->name : $opt_val;
				$selected  = ( $opt_val === $value || sanitize_title( $opt_label ) === sanitize_title( $value ) ) ? ' selected' : '';
				echo '<option value="' . esc_attr( $opt_val ) . '"' . $selected . '>' . esc_html( $opt_label ) . '</option>';
			}
			echo '</select></dd>';
		} elseif ( function_exists( 'mnsk7_get_key_param_variant_options' ) ) {
			$variant = mnsk7_get_key_param_variant_options( $product, $slug, $value );
			if ( $variant && count( $variant['options'] ) > 1 ) {
				$param = $variant['param'];
				$current = $variant['current_slug'];
				echo '<dd class="mnsk7-product-key-params__dd--select">';
				echo '<select class="mnsk7-key-param-select mnsk7-key-param-select--archive" data-param="' . esc_attr( $param ) . '" data-base-url="' . esc_attr( $variant['category_url'] ) . '">';
				foreach ( $variant['options'] as $opt_slug => $opt_name ) {
					$url = add_query_arg( $param, $opt_slug, $variant['category_url'] );
					$selected = ( $opt_slug === $current ) ? ' selected' : '';
					echo '<option value="' . esc_attr( $url ) . '"' . $selected . '>' . esc_html( $opt_name ) . '</option>';
				}
				echo '</select></dd>';
			} else {
				echo '<dd>' . esc_html( $value ) . '</dd>';
			}
		} else {
			echo '<dd>' . esc_html( $value ) . '</dd>';
		}
	}
	if ( ! $has_model_link_row ) {
		$model_variant_links = mnsk7_get_model_variant_links( $product );
		if ( $model_variant_links ) {
			echo '<dt>' . esc_html__( 'Wariant', 'mnsk7-tools' ) . '</dt>';
			echo '<dd class="mnsk7-product-key-params__dd--options">';
			mnsk7_render_key_param_model_links( $model_variant_links );
			echo '</dd>';
		}
	}
	echo '</dl></div>';
}

function mnsk7_single_product_zastosowanie() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$val = $product->get_attribute( 'zastosowanie' ) ?: $product->get_attribute( 'pa_zastosowanie' );
	if ( empty( $val ) ) {
		return;
	}
	echo '<div class="mnsk7-product-zastosowanie">';
	echo '<h4 class="mnsk7-product-zastosowanie__title">' . esc_html__( 'Do czego / Zastosowanie', 'mnsk7-tools' ) . '</h4>';
	echo '<p class="mnsk7-product-zastosowanie__text">' . esc_html( $val ) . '</p>';
	echo '</div>';
}

/**
 * Placeholder: availability jest teraz w CTA row (obok przycisku) — woocommerce_before_add_to_cart_button.
 * Nie wyświetlamy osobnego bloku nad formularzem.
 */
function mnsk7_single_product_availability() {
	// Pusty — jeden blok availability tylko przy CTA (mnsk7_single_product_availability_inline).
}

/**
 * Stock availability tuż obok przycisku „Dodaj do koszyka” (compact inline, po lewej od CTA).
 */
function mnsk7_single_product_availability_inline() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$availability = $product->get_availability();
	$class        = ! empty( $availability['class'] ) ? $availability['class'] : ( $product->is_in_stock() ? 'in-stock' : 'out-of-stock' );
	$text         = ! empty( $availability['availability'] ) ? $availability['availability'] : ( $product->is_in_stock() ? __( 'W magazynie', 'mnsk7-tools' ) : __( 'Na zamówienie', 'mnsk7-tools' ) );
	echo '<span class="mnsk7-product-availability mnsk7-product-availability--inline ' . esc_attr( $class ) . '" aria-live="polite">';
	echo '<i class="mnsk7-product-availability__icon" aria-hidden="true"></i>';
	echo esc_html( $text );
	echo '</span>';
}

function mnsk7_single_product_value_statement() {
	return;
}

function mnsk7_single_product_trust_badges() {
	global $product;
	$min    = number_format_i18n( MNK7_FREE_SHIPPING_MIN, 0 );
	$eta    = function_exists( 'mnsk7_delivery_eta_badge_label' ) ? mnsk7_delivery_eta_badge_label() : __( 'Dostawa jutro', 'mnsk7-tools' );
	$badges = array(
		$eta,
		__( 'Faktura VAT', 'mnsk7-tools' ),
		sprintf( __( 'Darmowa dostawa od %s zł', 'mnsk7-tools' ), $min ),
		__( 'Zwroty 30 dni', 'mnsk7-tools' ),
	);
	echo '<div class="mnsk7-product-trust">';
	foreach ( $badges as $badge ) {
		echo '<span class="mnsk7-product-trust__badge"><i class="mnsk7-product-trust__badge-icon" aria-hidden="true"></i><span class="mnsk7-product-trust__badge-label">' . esc_html( $badge ) . '</span></span>';
	}
	echo '</div>';
}

function mnsk7_single_product_schema_video_placeholder() {
	return '';
}

/*
 * WooCommerce domyślnie wyświetla "X w magazynie" wewnątrz formularza "Dodaj do koszyka"
 * (woocommerce_get_stock_html). Wyłączamy to żeby nie duplikować z naszym badgem.
 */
add_filter( 'woocommerce_get_stock_html', function ( $html ) {
	if ( is_singular( 'product' ) ) {
		return ''; // nasz mnsk7_single_product_availability() obsługuje to na priority 8
	}
	return $html;
} );

add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_availability', 8 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_value_statement', 17 );
add_action( 'woocommerce_before_add_to_cart_button', 'mnsk7_single_product_availability_inline', 5 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_key_params', 21 );

add_action( 'wp_footer', function () {
	if ( ! is_singular( 'product' ) ) {
		return;
	}
	?>
	<script>
	document.addEventListener('DOMContentLoaded', function() {
		document.querySelectorAll('.mnsk7-key-param-select--archive').forEach(function(el) {
			el.addEventListener('change', function() {
				var url = this.options[this.selectedIndex].value;
				if (url) { window.location.href = url; }
			});
		});
	});
	</script>
	<?php
}, 25 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_zastosowanie', 23 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_trust_badges', 32 );
add_action( 'woocommerce_single_product_summary', 'mnsk7_single_product_meta_chips', 40 );

add_action( 'woocommerce_before_single_product', function () {
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_excerpt', 20 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_meta', 40 );
	remove_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 10 );
	add_action( 'woocommerce_single_product_summary', 'woocommerce_template_single_price', 15 );
	// Bez listy zakładek (Opis | Opinie) — tylko harmonijka „Pokaż opis”.
	remove_action( 'woocommerce_after_single_product_summary', 'woocommerce_output_product_data_tabs', 10 );
	add_action( 'woocommerce_after_single_product_summary', 'mnsk7_single_product_description_accordion_block', 10 );
}, 5 );

function mnsk7_single_product_description_accordion_block() {
	mnsk7_product_description_accordion();
}

function mnsk7_single_product_meta_chips() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}
	$cats = wc_get_product_category_list( $product->get_id(), '' );
	$tags = wc_get_product_tag_list( $product->get_id(), '' );
	// Zamień link "Sklep" na "Katalog" (czytelniejsza etykieta)
	if ( $cats ) {
		$cats = str_replace( '>Sklep<', '>' . esc_html__( 'Katalog', 'mnsk7-tools' ) . '<', $cats );
	}
	if ( ! $cats && ! $tags ) {
		return;
	}
	echo '<div class="mnsk7-product-meta-chips">';
	if ( $cats ) {
		echo '<div class="mnsk7-product-meta-chips__row">' . $cats . '</div>';
	}
	if ( $tags ) {
		echo '<div class="mnsk7-product-meta-chips__row">' . $tags . '</div>';
	}
	echo '</div>';
}

/**
 * On single product pages, completely suppress the short description (excerpt)
 * to avoid duplicating info already shown in key_params block and description tab.
 */
add_filter( 'woocommerce_short_description', function ( $excerpt ) {
	if ( is_singular( 'product' ) ) {
		return '';
	}
	return $excerpt;
}, 99 );

/**
 * Ukryj zakładkę "Informacje dodatkowe" (parametry są w bloku Kluczowe parametry przy zdjęciu).
 * Ukryj zakładkę "Opinie" gdy brak recenzji (0).
 */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	unset( $tabs['additional_information'] );
	$post = get_post();
	if ( $post && (int) $post->comment_count === 0 && isset( $tabs['reviews'] ) ) {
		unset( $tabs['reviews'] );
	}
	return $tabs;
}, 20 );

/**
 * Opis produktu jako harmonijka (domyślnie zwinięty).
 */
add_filter( 'woocommerce_product_tabs', function ( $tabs ) {
	if ( isset( $tabs['description'] ) ) {
		$tabs['description']['callback'] = 'mnsk7_product_description_accordion';
	}
	return $tabs;
}, 25 );

function mnsk7_product_description_accordion() {
	$content = get_the_content( null, false, get_the_ID() );
	if ( trim( $content ) === '' ) {
		return;
	}
	echo '<details class="mnsk7-product-description-accordion">';
	echo '<summary class="mnsk7-product-description-accordion__summary">' . esc_html__( 'Pokaż opis', 'mnsk7-tools' ) . '</summary>';
	echo '<div class="mnsk7-product-description-accordion__content">' . apply_filters( 'the_content', $content ) . '</div>';
	echo '</details>';
}
