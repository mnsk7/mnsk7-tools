<?php
/**
 * MNK7 Tools — 301 redirects for consolidated / retired product URLs.
 *
 * BaseLinker (inventory) is the source of truth. When a stale duplicate Woo
 * product is removed (trashed), its old permalink must 301 to the surviving
 * canonical product so SEO authority and incoming links are preserved.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_retired_url_redirects' ) ) {
	/**
	 * Map of retired URL paths to their canonical target.
	 *
	 * Key   — old request path (leading/trailing slash are normalized).
	 * Value — array with either 'product_id' (preferred, resolves live
	 *         permalink) or 'url' (absolute or relative fallback).
	 *
	 * @return array<string, array{product_id?: int, url?: string}>
	 */
	function mnsk7_retired_url_redirects() {
		return array(
			// WC 441 (SKU H04230302) — stale duplicate of WC 25846 (SKU 2011091144, BL-mapped).
			'/sklep/frez-do-metalu-huhao-spiralny-4f-881963-h04230302/' => array( 'product_id' => 25846 ),
		);
	}
}

if ( ! function_exists( 'mnsk7_handle_retired_url_redirect' ) ) {
	/**
	 * Issues a 301 redirect when the current request matches a retired URL.
	 *
	 * @return void
	 */
	function mnsk7_handle_retired_url_redirect() {
		$request = isset( $_SERVER['REQUEST_URI'] ) ? wp_unslash( $_SERVER['REQUEST_URI'] ) : '';
		if ( ! is_string( $request ) || $request === '' ) {
			return;
		}

		$path = wp_parse_url( $request, PHP_URL_PATH );
		if ( ! is_string( $path ) || $path === '' ) {
			return;
		}
		$path = '/' . trim( $path, '/' ) . '/';

		foreach ( mnsk7_retired_url_redirects() as $old => $target ) {
			$old_norm = '/' . trim( (string) $old, '/' ) . '/';
			if ( $old_norm !== $path ) {
				continue;
			}

			$destination = '';
			if ( ! empty( $target['product_id'] ) ) {
				$permalink = get_permalink( (int) $target['product_id'] );
				if ( $permalink && ! is_wp_error( $permalink ) ) {
					$destination = $permalink;
				}
			}
			if ( $destination === '' && ! empty( $target['url'] ) ) {
				$destination = (string) $target['url'];
			}
			if ( $destination === '' ) {
				return;
			}

			wp_safe_redirect( $destination, 301 );
			exit;
		}
	}
}

add_action( 'template_redirect', 'mnsk7_handle_retired_url_redirect', 1 );
