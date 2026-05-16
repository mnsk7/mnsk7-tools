<?php
/**
 * Plugin Name: MNSK7 — open issues fixes
 * Description: Small, isolated fixes for current GitHub issues #28, #29 and #30.
 *
 * @package mnsk7
 */

defined( 'ABSPATH' ) || exit;

/**
 * Issue #29: conservative delivery label.
 *
 * The old fallback could show "Dostawa jutro" late in the evening. After the cutoff and on
 * weekends we deliberately switch to a safer business-day message.
 *
 * @return string
 */
if ( ! function_exists( 'mnsk7_delivery_eta_badge_label' ) ) {
	function mnsk7_delivery_eta_badge_label() {
		$timestamp   = current_time( 'timestamp' );
		$cutoff_hour = (int) apply_filters( 'mnsk7_delivery_eta_cutoff_hour', 14 );
		$weekday     = (int) wp_date( 'N', $timestamp ); // 1 = Monday, 7 = Sunday.
		$hour        = (int) wp_date( 'G', $timestamp );
		$before_cutoff = $hour < $cutoff_hour;

		if ( $weekday >= 1 && $weekday <= 4 && $before_cutoff ) {
			return __( 'Dostawa jutro', 'mnsk7-storefront' );
		}

		if ( 5 === $weekday && $before_cutoff ) {
			return __( 'Dostawa w poniedziałek', 'mnsk7-storefront' );
		}

		if ( $weekday >= 6 ) {
			return __( 'Wysyłka w poniedziałek', 'mnsk7-storefront' );
		}

		return __( 'Wysyłka w najbliższy dzień roboczy', 'mnsk7-storefront' );
	}
}

/**
 * Issue #30: keep the "Zastosowanie i materiały" megamenu section material-focused.
 *
 * The theme currently takes the top product_tag terms by count. That can pull in generic,
 * navigation or product-family tags. For the exact get_terms() shape used by the megamenu,
 * keep only material-like tags.
 */
add_filter(
	'get_terms',
	function ( $terms, $taxonomies, $args ) {
		if ( is_admin() || empty( $terms ) || is_wp_error( $terms ) || ! is_array( $terms ) ) {
			return $terms;
		}

		$taxonomies = is_array( $taxonomies ) ? $taxonomies : array( $taxonomies );
		if ( ! in_array( 'product_tag', $taxonomies, true ) ) {
			return $terms;
		}

		$is_megamenu_query = isset( $args['number'], $args['orderby'], $args['order'] )
			&& (int) $args['number'] === 10
			&& strtolower( (string) $args['orderby'] ) === 'count'
			&& strtolower( (string) $args['order'] ) === 'desc';

		if ( ! $is_megamenu_query ) {
			return $terms;
		}

		$material_keywords = apply_filters(
			'mnsk7_megamenu_material_tag_keywords',
			array(
				'aluminium',
				'aluminum',
				'akryl',
				'ceram',
				'drewno',
				'hdf',
				'inox',
				'kamien',
				'kompozyt',
				'laminat',
				'mdf',
				'metal',
				'miedz',
				'miedź',
				'mosiadz',
				'mosiądz',
				'nierdzew',
				'pcv',
				'plexi',
				'plastik',
				'pvc',
				'sklejka',
				'szklo',
				'szkło',
				'stal',
				'tworzyw',
				'zeliwo',
				'żeliwo',
			)
		);

		$blocked_keywords = apply_filters(
			'mnsk7_megamenu_blocked_tag_keywords',
			array(
				'cnc',
				'frez',
				'frezy',
				'gwint',
				'narzyn',
				'pilnik',
				'promoc',
				'zestaw',
			)
		);

		$filtered = array();
		foreach ( $terms as $term ) {
			if ( ! ( $term instanceof WP_Term ) ) {
				continue;
			}

			$haystack = strtolower( remove_accents( $term->slug . ' ' . $term->name ) );
			$is_blocked = false;
			foreach ( $blocked_keywords as $keyword ) {
				$keyword = strtolower( remove_accents( (string) $keyword ) );
				if ( '' !== $keyword && false !== strpos( $haystack, $keyword ) ) {
					$is_blocked = true;
					break;
				}
			}
			if ( $is_blocked ) {
				continue;
			}

			foreach ( $material_keywords as $keyword ) {
				$keyword = strtolower( remove_accents( (string) $keyword ) );
				if ( '' !== $keyword && false !== strpos( $haystack, $keyword ) ) {
					$filtered[] = $term;
					break;
				}
			}
		}

		return ! empty( $filtered ) ? array_values( $filtered ) : $terms;
	},
	20,
	3
);

/** Clear the megamenu transient once after this patch is deployed. */
add_action(
	'init',
	function () {
		$version = '2026-05-16-issue-30-material-tags-v1';
		if ( get_option( 'mnsk7_open_issues_fixes_version' ) === $version ) {
			return;
		}
		delete_transient( 'mnsk7_megamenu_terms' );
		update_option( 'mnsk7_open_issues_fixes_version', $version, false );
	},
	5
);

/**
 * Issue #28: desktop account/login layout hardening.
 *
 * Existing account CSS uses a two-column dashboard layout for logged-in users. The guest login
 * view needs its own desktop rules, otherwise the login/register forms can inherit dashboard
 * grid behavior or cramped header-account sizing.
 */
add_action(
	'wp_enqueue_scripts',
	function () {
		$css = '
body.woocommerce-account:not(.logged-in) .woocommerce{display:block!important;max-width:980px;margin-left:auto;margin-right:auto;}
body.woocommerce-account:not(.logged-in) .woocommerce #customer_login,
body.woocommerce-account:not(.logged-in) .woocommerce .u-columns{display:grid!important;grid-template-columns:repeat(2,minmax(0,1fr));gap:2rem;align-items:start;}
body.woocommerce-account:not(.logged-in) .woocommerce .u-column1,
body.woocommerce-account:not(.logged-in) .woocommerce .u-column2{float:none!important;width:auto!important;margin:0!important;max-width:none!important;}
body.woocommerce-account:not(.logged-in) .woocommerce form.login,
body.woocommerce-account:not(.logged-in) .woocommerce form.register{margin:0!important;padding:1.25rem 1.35rem!important;border:1px solid var(--color-border,#e5e7eb)!important;border-radius:var(--r-md,12px)!important;background:var(--color-white,#fff)!important;box-shadow:var(--shadow-sm,0 1px 3px rgba(0,0,0,.06));}
body.woocommerce-account:not(.logged-in) .woocommerce form .form-row{margin-bottom:1rem;}
body.woocommerce-account:not(.logged-in) .woocommerce form .woocommerce-Input,
body.woocommerce-account:not(.logged-in) .woocommerce form input.input-text{width:100%;box-sizing:border-box;min-height:44px;}
@media (min-width:1024px){.mnsk7-header__link--account{min-width:0;max-width:12rem;}.mnsk7-header__link--account .mnsk7-header__link-text{display:block;overflow:hidden;text-overflow:ellipsis;white-space:nowrap;}}
@media (max-width:768px){body.woocommerce-account:not(.logged-in) .woocommerce #customer_login,body.woocommerce-account:not(.logged-in) .woocommerce .u-columns{display:block!important;}body.woocommerce-account:not(.logged-in) .woocommerce .u-column1,body.woocommerce-account:not(.logged-in) .woocommerce .u-column2{margin-bottom:1rem!important;}}
';

		if ( wp_style_is( 'mnsk7-main', 'registered' ) || wp_style_is( 'mnsk7-main', 'enqueued' ) ) {
			wp_add_inline_style( 'mnsk7-main', $css );
			return;
		}

		add_action(
			'wp_head',
			function () use ( $css ) {
				echo '<style id="mnsk7-open-issues-fixes-css">' . wp_strip_all_tags( $css ) . '</style>';
			},
			30
		);
	},
	30
);
