<?php
/**
 * Moje konto: usuń osierocone shortcode’y z treści (np. po wyłączeniu wtyczki dashboardu).
 */
defined( 'ABSPATH' ) || exit;

add_filter(
	'the_content',
	static function ( $content ) {
		if ( ! function_exists( 'is_account_page' ) || ! is_account_page() ) {
			return $content;
		}
		return (string) preg_replace( '/\[(?:user_role|order_progress_bar)[^\]]*\]/i', '', (string) $content );
	},
	99
);
