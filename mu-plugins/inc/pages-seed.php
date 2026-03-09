<?php
/**
 * Seed landing pages (Kontakt, Dostawa i płatności, Przewodnik) if they don't exist.
 * Ensures staging has the same structure as production.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

add_action( 'init', function () {
	if ( get_option( 'mnsk7_landing_pages_seeded', 0 ) ) {
		return;
	}

	$pages = array(
		array(
			'post_name'   => 'kontakt',
			'post_title'  => _x( 'Kontakt', 'page title', 'mnsk7-tools' ),
			'post_content' => '<p>' . esc_html__( 'Skontaktuj się z nami — odpowiadamy w dni robocze.', 'mnsk7-tools' ) . '</p>',
			'template'   => 'page-kontakt.php',
		),
		array(
			'post_name'   => 'dostawa-i-platnosci',
			'post_title'  => _x( 'Dostawa i płatności', 'page title', 'mnsk7-tools' ),
			'post_content' => '<p>' . esc_html__( 'Informacje o dostawie i formach płatności.', 'mnsk7-tools' ) . '</p>',
			'template'   => 'page-dostawa.php',
		),
		array(
			'post_name'   => 'przewodnik',
			'post_title'  => _x( 'Przewodnik', 'page title', 'mnsk7-tools' ),
			'post_content' => '',
			'template'   => '',
		),
	);

	foreach ( $pages as $p ) {
		$existing = get_page_by_path( $p['post_name'] );
		if ( $existing && $existing->post_status === 'publish' ) {
			continue;
		}

		$post_data = array(
			'post_title'   => $p['post_title'],
			'post_name'    => $p['post_name'],
			'post_content' => $p['post_content'],
			'post_status'  => 'publish',
			'post_type'    => 'page',
			'post_author'  => 1,
			'ping_status'  => 'closed',
			'comment_status' => 'closed',
		);

		$page_id = wp_insert_post( $post_data, true );
		if ( is_wp_error( $page_id ) ) {
			continue;
		}
		if ( ! empty( $p['template'] ) ) {
			update_post_meta( $page_id, '_wp_page_template', $p['template'] );
		}
	}

	update_option( 'mnsk7_landing_pages_seeded', 1 );
}, 15 );
