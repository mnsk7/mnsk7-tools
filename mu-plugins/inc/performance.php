<?php
/**
 * MNK7 Tools — Performance: resource hints, preconnect, LCP, lazy loading.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/* Preconnect: Google Fonts, CDN */
add_action( 'wp_head', function () {
	?>
	<link rel="preconnect" href="https://fonts.googleapis.com">
	<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
	<?php
}, 1 );

/* DNS prefetch dla często używanych domen zewnętrznych */
add_action( 'wp_head', function () {
	$domains = array(
		'https://www.googletagmanager.com',
		'https://www.google-analytics.com',
		'https://www.instagram.com',
		'https://allegro.pl',
	);
	foreach ( $domains as $domain ) {
		echo '<link rel="dns-prefetch" href="' . esc_url( $domain ) . '">' . "\n";
	}
}, 2 );

/**
 * Pierwszemu obrazkowi produktu w archiwum ustawiamy fetchpriority="high" i loading="eager"
 * (LCP candidate). Pozostałe — loading="lazy".
 */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment, $size ) {
	/* Ustawiamy lazy dla wszystkich obrazków poza tymi, gdzie eager jest wymagany */
	if ( ! isset( $attr['loading'] ) ) {
		$attr['loading'] = 'lazy';
	}
	return $attr;
}, 15, 3 );

/**
 * Hero image na front-page: eager + fetchpriority high.
 * Dotyczy pierwszego obrazka w sekcji hero (gdy używamy get_the_post_thumbnail).
 */
add_filter( 'woocommerce_product_get_image', function ( $image, $product, $size, $attr, $placeholder, $image_obj ) {
	static $first_in_archive = false;
	if ( is_archive() && ! $first_in_archive ) {
		$first_in_archive = true;
		/* Zamień loading="lazy" na eager + dodaj fetchpriority="high" dla pierwszego */
		$image = str_replace( 'loading="lazy"', 'loading="eager" fetchpriority="high"', $image );
	}
	return $image;
}, 10, 6 );

/**
 * Usuwamy zbędne query strings z wersji statycznych assetów (cache busting przez nazwę pliku).
 * Pomaga przy niektórych konfiguracjach cache.
 */
add_filter( 'style_loader_src', 'mnsk7_remove_query_string_from_static', 10 );
add_filter( 'script_loader_src', 'mnsk7_remove_query_string_from_static', 10 );
function mnsk7_remove_query_string_from_static( $src ) {
	/* Tylko dla plików z wp-content, pomijamy jQuery i inne kluczowe skrypty */
	if ( strpos( $src, 'wp-content' ) !== false && strpos( $src, '?ver=' ) !== false ) {
		return remove_query_arg( 'ver', $src );
	}
	return $src;
}

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
add_action( 'wp_enqueue_scripts', function () {
	if ( ! is_admin() ) {
		wp_dequeue_style( 'wp-block-library' );
		wp_dequeue_style( 'wp-block-library-theme' );
		wp_dequeue_style( 'global-styles' );
	}
}, 100 );
