<?php
/**
 * MNK7 Tools — Performance: resource hints, preconnect, LCP, lazy loading, asset dequeue.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Whether Ultimate Member assets should load on the current request.
 *
 * @return bool
 */
function mnsk7_perf_um_context() {
	if ( function_exists( 'is_account_page' ) && is_account_page() ) {
		return true;
	}
	if ( function_exists( 'um_is_core_page' ) ) {
		foreach ( array( 'login', 'register', 'account', 'password-reset', 'user', 'logout', 'members' ) as $um_page ) {
			if ( um_is_core_page( $um_page ) ) {
				return true;
			}
		}
	}
	return false;
}

/**
 * Whether wc-product-table-lite assets are needed (shop / category / tag archives).
 *
 * @return bool
 */
function mnsk7_perf_plp_context() {
	if ( function_exists( 'mnsk7_is_plp' ) && mnsk7_is_plp() ) {
		return true;
	}
	if ( function_exists( 'is_shop' ) && is_shop() ) {
		return true;
	}
	if ( function_exists( 'is_product_taxonomy' ) && is_product_taxonomy() ) {
		return true;
	}
	return false;
}

/**
 * Dequeue registered styles or scripts whose src contains a needle.
 *
 * @param string $type   'style' or 'script'.
 * @param string $needle Substring to match in src URL.
 */
function mnsk7_perf_dequeue_by_src( $type, $needle ) {
	$needle = (string) $needle;
	if ( $needle === '' ) {
		return;
	}
	$registry = ( $type === 'script' ) ? wp_scripts() : wp_styles();
	if ( ! ( $registry instanceof WP_Dependencies ) || empty( $registry->registered ) ) {
		return;
	}
	foreach ( $registry->registered as $handle => $obj ) {
		$src = isset( $obj->src ) ? (string) $obj->src : '';
		if ( $src !== '' && strpos( $src, $needle ) !== false ) {
			if ( $type === 'script' ) {
				wp_dequeue_script( $handle );
			} else {
				wp_dequeue_style( $handle );
			}
		}
	}
}

/**
 * URL pierwszego obrazka bestsellerów (LCP candidate) — cache 1h.
 *
 * @return string
 */
function mnsk7_get_home_lcp_image_url() {
	static $resolved = null;
	if ( $resolved !== null ) {
		return $resolved;
	}
	$resolved = '';
	if ( ! function_exists( 'wc_get_products' ) ) {
		return $resolved;
	}
	$cached = get_transient( 'mnsk7_home_lcp_thumb' );
	if ( is_string( $cached ) && $cached !== '' ) {
		$resolved = $cached;
		return $resolved;
	}
	$products = wc_get_products(
		array(
			'limit'   => 1,
			'orderby' => 'popularity',
			'order'   => 'DESC',
			'status'  => 'publish',
		)
	);
	if ( empty( $products[0] ) || ! $products[0] instanceof WC_Product ) {
		return $resolved;
	}
	$img_id = $products[0]->get_image_id();
	if ( ! $img_id ) {
		return $resolved;
	}
	$src = wp_get_attachment_image_url( $img_id, 'woocommerce_thumbnail' );
	if ( is_string( $src ) && $src !== '' ) {
		$resolved = $src;
		set_transient( 'mnsk7_home_lcp_thumb', $resolved, HOUR_IN_SECONDS );
	}
	return $resolved;
}

/**
 * Lazy loading obrazków — ustawiamy loading="lazy" jeśli nie jest jeszcze ustawiony.
 * Pomijamy obrazki oznaczone jako LCP (eager / fetchpriority high).
 */
add_filter(
	'wp_get_attachment_image_attributes',
	function ( $attr, $attachment, $size ) {
		if ( ! is_array( $attr ) ) {
			return $attr;
		}
		if ( isset( $attr['loading'] ) && $attr['loading'] === 'eager' ) {
			return $attr;
		}
		if ( isset( $attr['fetchpriority'] ) && $attr['fetchpriority'] === 'high' ) {
			return $attr;
		}
		if ( ! isset( $attr['loading'] ) ) {
			$attr['loading'] = 'lazy';
		}
		return $attr;
	},
	15,
	3
);

/**
 * LCP: pierwszy thumbnail w pętli produktów na stronie głównej — eager + fetchpriority high + wykluczenie z lazy.
 */
add_filter(
	'wp_get_attachment_image_attributes',
	function ( $attr, $attachment, $size ) {
		if ( is_admin() || ! is_array( $attr ) ) {
			return $attr;
		}
		if ( ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
			return $attr;
		}
		if ( ! function_exists( 'wc_get_loop_prop' ) || (int) wc_get_loop_prop( 'current' ) !== 1 ) {
			return $attr;
		}
		$class = isset( $attr['class'] ) ? (string) $attr['class'] : '';
		if ( strpos( $class, 'attachment-woocommerce_thumbnail' ) === false ) {
			return $attr;
		}
		$attr['loading']       = 'eager';
		$attr['fetchpriority'] = 'high';
		$attr['data-no-lazy']  = '1';
		if ( strpos( $class, 'skip-lazy' ) === false ) {
			$attr['class'] = trim( $class . ' skip-lazy mnsk7-lcp-candidate' );
		}
		return $attr;
	},
	25,
	3
);

/**
 * LCP fallback w HTML produktu (shortcode [products] / bestsellery) — pierwszy obrazek na home i archive.
 */
add_filter(
	'woocommerce_product_get_image',
	function ( $image, $product, $size, $attr, $placeholder, $image_obj ) {
		static $first_lcp = false;
		$is_lcp_page = ( function_exists( 'is_front_page' ) && is_front_page() )
			|| ( function_exists( 'is_archive' ) && is_archive() );
		if ( ! $is_lcp_page || $first_lcp ) {
			return $image;
		}
		$first_lcp = true;
		$image     = (string) $image;
		if ( $image === '' ) {
			return $image;
		}
		$image = preg_replace( '/\sloading=(["\'])lazy\1/i', ' loading="eager"', $image );
		if ( stripos( $image, 'fetchpriority=' ) === false ) {
			$image = preg_replace( '/<img\b/i', '<img fetchpriority="high"', $image, 1 );
		}
		if ( stripos( $image, 'data-no-lazy' ) === false ) {
			$image = preg_replace( '/<img\b/i', '<img data-no-lazy="1"', $image, 1 );
		}
		if ( stripos( $image, 'skip-lazy' ) === false ) {
			$image = preg_replace( '/\bclass=(["\'])/i', 'class=$1skip-lazy mnsk7-lcp-candidate ', $image, 1 );
		}
		return $image;
	},
	20,
	6
);

/**
 * Preload LCP image on homepage — discoverable before CSS/JS block render.
 */
add_action(
	'wp_head',
	function () {
		if ( ! function_exists( 'is_front_page' ) || ! is_front_page() ) {
			return;
		}
		$url = mnsk7_get_home_lcp_image_url();
		if ( $url === '' ) {
			return;
		}
		echo '<link rel="preload" as="image" href="' . esc_url( $url ) . '" fetchpriority="high">' . "\n";
	},
	2
);

/**
 * WP Rocket: nie lazy-load obrazków LCP (data-no-lazy, skip-lazy, fetchpriority).
 */
add_filter(
	'rocket_lazyload_excluded_attributes',
	function ( $attrs ) {
		if ( ! is_array( $attrs ) ) {
			$attrs = array();
		}
		$attrs[] = 'data-no-lazy';
		$attrs[] = 'data-skip-lazy';
		$attrs[] = 'fetchpriority';
		return $attrs;
	}
);

add_filter(
	'rocket_lazyload_exclude_classes',
	function ( $classes ) {
		if ( ! is_array( $classes ) ) {
			$classes = array();
		}
		$classes[] = 'skip-lazy';
		$classes[] = 'mnsk7-lcp-candidate';
		return $classes;
	}
);

/* Uwaga: nie usuwamy ?ver= z assetów — LiteSpeed Cache i WP Rocket obsługują to samodzielnie.
 * Ręczne usuwanie może powodować problemy z inwalidacją cache w niektórych konfiguracjach. */

/**
 * Wyłącz emoji scripts — zmniejszenie liczby requestów.
 */
remove_action( 'wp_head', 'print_emoji_detection_script', 7 );
remove_action( 'wp_print_styles', 'print_emoji_styles' );
remove_action( 'admin_print_scripts', 'print_emoji_detection_script' );
remove_action( 'admin_print_styles', 'print_emoji_styles' );
remove_filter( 'the_content_feed', 'wp_staticize_emoji' );
remove_filter( 'comment_text_rss', 'wp_staticize_emoji' );
remove_filter( 'wp_mail', 'wp_staticize_emoji_for_email' );

/**
 * Wyłącz wbudowane bloki Gutenberga CSS (jeśli strona nie używa blok-editora w frontendzie).
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( ! is_admin() ) {
			if ( ! is_singular() || ! has_blocks( get_post() ) ) {
				wp_dequeue_style( 'wp-block-library' );
				wp_dequeue_style( 'wp-block-library-theme' );
				wp_dequeue_style( 'global-styles' );
			}
		}
	},
	100
);

/**
 * Dequeue plugin CSS/JS off critical paths (home LCP, storefront).
 * Cart/checkout/add-to-cart JS untouched — only styles and non-Woo plugin assets.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		if ( is_admin() ) {
			return;
		}

		// Ultimate Member — tylko Moje konto / strony UM.
		if ( ! mnsk7_perf_um_context() ) {
			mnsk7_perf_dequeue_by_src( 'style', 'ultimate-member' );
			mnsk7_perf_dequeue_by_src( 'script', 'ultimate-member' );
		}

		// Tabela PLP — tylko archiwum sklepu/kategorii/tagów.
		if ( ! mnsk7_perf_plp_context() ) {
			mnsk7_perf_dequeue_by_src( 'style', 'wc-product-table-lite' );
			mnsk7_perf_dequeue_by_src( 'script', 'wc-product-table-lite' );
		}

		// Strona główna: modal/wishlist/comparison ShopEngine + per-post Elementor CSS (front-page.php = PHP theme).
		if ( function_exists( 'is_front_page' ) && is_front_page() ) {
			mnsk7_perf_dequeue_by_src( 'style', 'shopengine-modal' );
			mnsk7_perf_dequeue_by_src( 'script', 'shopengine-modal' );
			mnsk7_perf_dequeue_by_src( 'style', 'shopengine/modules/wishlist' );
			mnsk7_perf_dequeue_by_src( 'style', 'shopengine/modules/comparison' );
			mnsk7_perf_dequeue_by_src( 'script', 'shopengine/modules/wishlist' );
			mnsk7_perf_dequeue_by_src( 'script', 'shopengine/modules/comparison' );

			global $wp_styles;
			if ( $wp_styles instanceof WP_Styles && ! empty( $wp_styles->queue ) ) {
				foreach ( array_keys( $wp_styles->queue ) as $handle ) {
					if ( strpos( (string) $handle, 'elementor-post-' ) === 0 ) {
						wp_dequeue_style( $handle );
					}
				}
			}
		}
	},
	999
);
