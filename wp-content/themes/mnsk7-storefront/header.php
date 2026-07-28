<?php
/**
 * Site header.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

$mnsk7_shop_url    = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/sklep/' );
$mnsk7_account_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'myaccount' ) : home_url( '/moje-konto/' );
$mnsk7_cart_url    = function_exists( 'wc_get_cart_url' ) ? wc_get_cart_url() : home_url( '/koszyk/' );
$mnsk7_promo_text  = apply_filters( 'mnsk7_header_promo_text', '' );
$mnsk7_menu_tree   = function_exists( 'mnsk7_get_megamenu_category_tree' ) ? mnsk7_get_megamenu_category_tree() : array();
$mnsk7_menu_terms  = function_exists( 'mnsk7_get_megamenu_terms' ) ? mnsk7_get_megamenu_terms() : array();
$mnsk7_menu_tags   = isset( $mnsk7_menu_terms['tags'] ) && is_array( $mnsk7_menu_terms['tags'] ) ? $mnsk7_menu_terms['tags'] : array();
$mnsk7_cart_count  = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
$mnsk7_cart_total  = function_exists( 'WC' ) && WC()->cart ? WC()->cart->get_cart_total() : '';
$mnsk7_account_label = is_user_logged_in() ? wp_get_current_user()->display_name : __( 'Moje konto', 'mnsk7-storefront' );

$mnsk7_term_label = static function ( $raw_label ) {
	$label = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $raw_label ) : $raw_label;
	return function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $label ) : $label;
};
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php
	$mnsk7_font_path = get_stylesheet_directory() . '/assets/fonts/inter-latin-wght-normal.woff2';
	if ( file_exists( $mnsk7_font_path ) ) :
		?>
		<link rel="preload" href="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/fonts/inter-latin-wght-normal.woff2' ); ?>" as="font" type="font/woff2" crossorigin>
	<?php endif; ?>
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php
$mnsk7_header_version = defined( 'MNSK7_THEME_VERSION' ) ? MNSK7_THEME_VERSION : '1.0.95';
echo '<!-- mnsk7-header v' . esc_attr( $mnsk7_header_version ) . ' -->' . "\n";
wp_body_open();
?>
<a class="mnsk7-skip-link skip-link" href="#main"><?php esc_html_e( 'Przejdź do treści', 'mnsk7-storefront' ); ?></a>
<div id="page" class="hfeed site">
	<?php if ( '' !== $mnsk7_promo_text ) : ?>
		<div id="mnsk7-promo-bar" class="mnsk7h-promo" role="complementary" aria-label="<?php esc_attr_e( 'Promocja', 'mnsk7-storefront' ); ?>">
			<div class="mnsk7h-promo__inner">
				<span class="mnsk7h-promo__text"><?php echo wp_kses_post( $mnsk7_promo_text ); ?></span>
				<button type="button" class="mnsk7h-promo__close" aria-label="<?php esc_attr_e( 'Zamknij promocję', 'mnsk7-storefront' ); ?>">&times;</button>
			</div>
		</div>
	<?php endif; ?>

	<header id="masthead" class="site-header mnsk7h" role="banner">
		<div class="mnsk7h__inner">
			<div class="mnsk7h__brand">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mnsk7h__logo-link" rel="home"><?php bloginfo( 'name' ); ?></a>
				<?php endif; ?>
			</div>

			<button type="button" class="mnsk7h__action mnsk7h__menu-toggle" aria-expanded="false" aria-controls="mnsk7h-navigation" aria-label="<?php esc_attr_e( 'Otwórz menu', 'mnsk7-storefront' ); ?>" data-open-label="<?php esc_attr_e( 'Otwórz menu', 'mnsk7-storefront' ); ?>" data-close-label="<?php esc_attr_e( 'Zamknij menu', 'mnsk7-storefront' ); ?>">
				<span class="mnsk7h__hamburger" aria-hidden="true"></span>
			</button>

			<nav id="mnsk7h-navigation" class="mnsk7h__navigation" aria-label="<?php esc_attr_e( 'Menu główne', 'mnsk7-storefront' ); ?>">
				<div class="mnsk7h__drawer-head">
					<strong><?php esc_html_e( 'Menu', 'mnsk7-storefront' ); ?></strong>
					<button type="button" class="mnsk7h__drawer-close" aria-label="<?php esc_attr_e( 'Zamknij menu', 'mnsk7-storefront' ); ?>">&times;</button>
				</div>
				<ul class="mnsk7h__root">
					<li class="mnsk7h__shop-item">
						<details class="mnsk7h__shop">
							<summary><?php esc_html_e( 'Sklep', 'mnsk7-storefront' ); ?></summary>
							<div class="mnsk7h__mega">
								<a class="mnsk7h__shop-all" href="<?php echo esc_url( $mnsk7_shop_url ); ?>"><?php esc_html_e( 'Zobacz cały sklep', 'mnsk7-storefront' ); ?></a>
								<p class="mnsk7h__section-label"><?php esc_html_e( 'Kategorie', 'mnsk7-storefront' ); ?></p>
								<ul class="mnsk7h__category-list">
									<?php foreach ( $mnsk7_menu_tree as $mnsk7_node ) : ?>
										<?php
										$mnsk7_parent = isset( $mnsk7_node['term'] ) ? $mnsk7_node['term'] : null;
										if ( ! ( $mnsk7_parent instanceof WP_Term ) ) {
											continue;
										}
										$mnsk7_parent_url = get_term_link( $mnsk7_parent );
										if ( is_wp_error( $mnsk7_parent_url ) ) {
											continue;
										}
										$mnsk7_parent_name = $mnsk7_term_label( $mnsk7_parent->name );
										$mnsk7_children    = isset( $mnsk7_node['children'] ) && is_array( $mnsk7_node['children'] ) ? $mnsk7_node['children'] : array();
										?>
										<li class="mnsk7h__category">
											<?php if ( $mnsk7_children ) : ?>
												<details>
													<summary>
														<span><?php echo esc_html( $mnsk7_parent_name ); ?></span>
														<span class="mnsk7h__count" aria-hidden="true"><?php echo esc_html( number_format_i18n( count( $mnsk7_children ) ) ); ?></span>
													</summary>
													<div class="mnsk7h__subcategory-panel">
														<a class="mnsk7h__category-all" href="<?php echo esc_url( $mnsk7_parent_url ); ?>">
															<?php
															printf(
																/* translators: %s: product category name. */
																esc_html__( 'Wszystkie: %s', 'mnsk7-storefront' ),
																esc_html( $mnsk7_parent_name )
															);
															?>
														</a>
														<ul>
															<?php foreach ( $mnsk7_children as $mnsk7_child ) : ?>
																<?php
																if ( ! ( $mnsk7_child instanceof WP_Term ) ) {
																	continue;
																}
																$mnsk7_child_url = get_term_link( $mnsk7_child );
																if ( is_wp_error( $mnsk7_child_url ) ) {
																	continue;
																}
																?>
																<li>
																	<a href="<?php echo esc_url( $mnsk7_child_url ); ?>">
																		<span><?php echo esc_html( $mnsk7_term_label( $mnsk7_child->name ) ); ?></span>
																		<?php if ( (int) $mnsk7_child->count > 0 ) : ?>
																			<span class="mnsk7h__count" aria-hidden="true"><?php echo esc_html( number_format_i18n( (int) $mnsk7_child->count ) ); ?></span>
																		<?php endif; ?>
																	</a>
																</li>
															<?php endforeach; ?>
														</ul>
													</div>
												</details>
											<?php else : ?>
												<a href="<?php echo esc_url( $mnsk7_parent_url ); ?>">
													<span><?php echo esc_html( $mnsk7_parent_name ); ?></span>
													<?php if ( (int) $mnsk7_parent->count > 0 ) : ?>
														<span class="mnsk7h__count" aria-hidden="true"><?php echo esc_html( number_format_i18n( (int) $mnsk7_parent->count ) ); ?></span>
													<?php endif; ?>
												</a>
											<?php endif; ?>
										</li>
									<?php endforeach; ?>
								</ul>

								<?php if ( $mnsk7_menu_tags ) : ?>
									<details class="mnsk7h__materials">
										<summary><?php esc_html_e( 'Zastosowanie i materiały', 'mnsk7-storefront' ); ?></summary>
										<ul>
											<?php foreach ( $mnsk7_menu_tags as $mnsk7_tag ) : ?>
												<?php
												$mnsk7_tag_url = get_term_link( $mnsk7_tag );
												if ( is_wp_error( $mnsk7_tag_url ) ) {
													continue;
												}
												?>
												<li><a href="<?php echo esc_url( $mnsk7_tag_url ); ?>"><?php echo esc_html( $mnsk7_term_label( $mnsk7_tag->name ) ); ?></a></li>
											<?php endforeach; ?>
										</ul>
									</details>
								<?php endif; ?>
							</div>
						</details>
					</li>
					<li><a href="<?php echo esc_url( home_url( '/przewodnik/' ) ); ?>"><?php esc_html_e( 'Przewodnik', 'mnsk7-storefront' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>"><?php esc_html_e( 'Dostawa i płatności', 'mnsk7-storefront' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'mnsk7-storefront' ); ?></a></li>
					<li class="mnsk7h__mobile-account"><a href="<?php echo esc_url( $mnsk7_account_url ); ?>"><?php echo esc_html( is_user_logged_in() ? $mnsk7_account_label : __( 'Moje konto / Zaloguj się', 'mnsk7-storefront' ) ); ?></a></li>
				</ul>
			</nav>

			<div class="mnsk7h__actions">
				<div class="mnsk7h__search">
					<button type="button" class="mnsk7h__action mnsk7h__search-toggle" aria-expanded="false" aria-controls="mnsk7h-search-form" aria-label="<?php esc_attr_e( 'Szukaj', 'mnsk7-storefront' ); ?>" data-open-label="<?php esc_attr_e( 'Szukaj', 'mnsk7-storefront' ); ?>" data-close-label="<?php esc_attr_e( 'Zamknij wyszukiwanie', 'mnsk7-storefront' ); ?>">
						<svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><path d="m21 21-4.35-4.35"/></svg>
					</button>
					<form id="mnsk7h-search-form" role="search" method="get" class="mnsk7h__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label for="mnsk7h-search-input" class="screen-reader-text"><?php esc_html_e( 'Szukaj produktów', 'mnsk7-storefront' ); ?></label>
						<input id="mnsk7h-search-input" type="search" name="s" value="<?php echo esc_attr( get_search_query() ); ?>" placeholder="<?php esc_attr_e( 'Szukaj produktów…', 'mnsk7-storefront' ); ?>">
						<input type="hidden" name="post_type" value="product">
						<button type="submit"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></button>
					</form>
				</div>

				<a class="mnsk7h__action mnsk7h__account" href="<?php echo esc_url( $mnsk7_account_url ); ?>" aria-label="<?php echo esc_attr( is_user_logged_in() ? $mnsk7_account_label : __( 'Moje konto / Zaloguj się', 'mnsk7-storefront' ) ); ?>">
					<svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
					<span><?php echo esc_html( $mnsk7_account_label ); ?></span>
				</a>

				<div class="mnsk7h__cart">
					<a href="<?php echo esc_url( $mnsk7_cart_url ); ?>" class="cart-contents mnsk7-header__cart-trigger mnsk7h__action mnsk7h__cart-trigger" aria-label="<?php esc_attr_e( 'Koszyk', 'mnsk7-storefront' ); ?>" aria-expanded="false" aria-controls="mnsk7h-cart-dropdown">
						<svg aria-hidden="true" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 2 3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4Z"/><path d="M3 6h18M16 10a4 4 0 0 1-8 0"/></svg>
						<span class="mnsk7-header__cart-count mnsk7h__cart-count" aria-hidden="true"><?php echo absint( $mnsk7_cart_count ); ?></span>
					</a>
					<div id="mnsk7h-cart-dropdown" class="mnsk7h__cart-dropdown" role="dialog" aria-label="<?php esc_attr_e( 'Koszyk', 'mnsk7-storefront' ); ?>" hidden>
						<?php
						$mnsk7_loyalty_discount = function_exists( 'mnsk7_header_cart_loyalty_discount' ) ? mnsk7_header_cart_loyalty_discount() : 0.0;
						echo function_exists( 'mnsk7_header_cart_summary_html' )
							? mnsk7_header_cart_summary_html( $mnsk7_cart_count, $mnsk7_cart_total, $mnsk7_loyalty_discount )
							: '';
						?>
						<div class="widget_shopping_cart_content">
							<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
								<?php woocommerce_mini_cart(); ?>
							<?php endif; ?>
						</div>
					</div>
				</div>
			</div>
		</div>
	</header>

	<div id="content" class="site-content mnsk7-content">
