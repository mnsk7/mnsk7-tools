<?php
/**
 * One-time: add shortcode [mnsk7_guide_products] and FAQ meta to post "Frez diamentowy".
 * Run: wp eval-file scripts/update-przewodnik-article.php  (z katalogu WP)
 * Or:  php scripts/update-przewodnik-article.php          (z katalogu projektu, ładuje WP)
 */

if ( php_sapi_name() === 'cli' && ! defined( 'ABSPATH' ) ) {
	$wp_root = dirname( __DIR__ );
	if ( is_file( $wp_root . '/wp-load.php' ) ) {
		require_once $wp_root . '/wp-load.php';
	}
	if ( ! defined( 'ABSPATH' ) ) {
		fwrite( STDERR, "Run from project root (where wp-load.php is) or use: wp eval-file scripts/update-przewodnik-article.php\n" );
		exit( 1 );
	}
}

$all = get_posts( array( 'post_type' => 'post', 'post_status' => 'publish', 'numberposts' => 100 ) );
$posts = array();
foreach ( $all as $p ) {
	if ( stripos( $p->post_title, 'Frez diamentowy' ) !== false || stripos( $p->post_name, 'frez-diamentowy' ) !== false ) {
		$posts = array( $p->ID );
		break;
	}
}

if ( empty( $posts ) ) {
	if ( function_exists( 'WP_CLI' ) ) {
		WP_CLI::error( 'Nie znaleziono posta "Frez diamentowy".' );
	}
	exit( 1 );
}

$id = (int) $posts[0];
$post = get_post( $id );
$content = $post->post_content;

$shortcode = '[mnsk7_guide_products category="frez-diamentowy" title="Frezy diamentowe w ofercie"]';
$block = "\n\n<h3>Frezy diamentowe w ofercie</h3>\n<p>W naszej ofercie znajdziesz frezy diamentowe w różnych typach i średnicach:</p>\n<p>{$shortcode}</p>";

if ( strpos( $content, 'mnsk7_guide_products' ) === false ) {
	$content .= $block;
	wp_update_post( array( 'ID' => $id, 'post_content' => $content ) );
	if ( function_exists( 'WP_CLI' ) ) {
		WP_CLI::success( 'Dodano shortcode do treści.' );
	}
} else {
	if ( function_exists( 'WP_CLI' ) ) {
		WP_CLI::log( 'Shortcode już obecny w treści.' );
	}
}

update_post_meta( $id, 'mnsk7_faq_set', 'produkt' );
update_post_meta( $id, 'mnsk7_faq_title', 'FAQ — frezy diamentowe' );

if ( function_exists( 'WP_CLI' ) ) {
	WP_CLI::success( "Zaktualizowano post ID={$id}: " . get_permalink( $id ) );
}
