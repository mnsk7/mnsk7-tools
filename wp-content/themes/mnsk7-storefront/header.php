<?php
/**
 * Header — mnsk7-storefront. Prosty, zawsze ten sam: logo + menu + konto + koszyk.
 *
 * @package mnsk7-storefront
 */
defined( 'ABSPATH' ) || exit;
?>
<!DOCTYPE html>
<html <?php language_attributes(); ?>>
<head>
	<meta charset="<?php bloginfo( 'charset' ); ?>">
	<meta name="viewport" content="width=device-width, initial-scale=1">
	<link rel="profile" href="https://gmpg.org/xfn/11">
	<?php wp_head(); ?>
</head>
<body <?php body_class(); ?>>
<?php wp_body_open(); ?>
<div id="page" class="hfeed site">

<?php
$promo_text = apply_filters( 'mnsk7_header_promo_text', '' );
if ( $promo_text !== '' ) :
	?>
	<div id="mnsk7-promo-bar" class="mnsk7-promo-bar" role="complementary" aria-label="<?php esc_attr_e( 'Promocja', 'mnsk7-storefront' ); ?>">
		<div class="mnsk7-promo-bar__inner">
			<span class="mnsk7-promo-bar__text"><?php echo wp_kses_post( $promo_text ); ?></span>
			<button type="button" class="mnsk7-promo-bar__close" aria-label="<?php esc_attr_e( 'Zamknij', 'mnsk7-storefront' ); ?>">&times;</button>
		</div>
	</div>
	<?php
endif;
?>

<header id="masthead" class="site-header mnsk7-header mnsk7-header--sticky" role="banner">
	<div class="mnsk7-header__inner">
		<div class="mnsk7-header__brand">
			<?php
			if ( has_custom_logo() ) {
				the_custom_logo();
			} else {
				?><a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mnsk7-header__logo-link" rel="home"><?php bloginfo( 'name' ); ?></a><?php
			}
			?>
		</div>
		<nav class="mnsk7-header__nav" role="navigation" aria-label="<?php esc_attr_e( 'Menu główne', 'mnsk7-storefront' ); ?>">
			<button type="button" class="mnsk7-header__menu-toggle" aria-expanded="false" aria-controls="mnsk7-primary-menu" aria-label="<?php esc_attr_e( 'Otwórz menu', 'mnsk7-storefront' ); ?>">
				<span class="mnsk7-header__hamburger" aria-hidden="true"></span>
			</button>
			<ul id="mnsk7-primary-menu" class="mnsk7-header__menu">
				<?php
				$shop_url = ( function_exists( 'wc_get_page_permalink' ) ) ? wc_get_page_permalink( 'shop' ) : home_url( '/sklep/' );
				$is_shop = function_exists( 'is_shop' ) && is_shop();
				$sklep_class = $is_shop ? ' class="current-menu-item menu-item-has-children"' : ' class="menu-item-has-children"';
				?>
				<li<?php echo $sklep_class; ?>>
					<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Sklep', 'mnsk7-storefront' ); ?></a>
					<?php
					if ( taxonomy_exists( 'product_cat' ) ) {
						$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 12 ) );
						if ( ! is_wp_error( $top_cats ) && ! empty( $top_cats ) ) {
							echo '<ul class="sub-menu">';
							foreach ( $top_cats as $term ) {
								$link = get_term_link( $term );
								if ( is_wp_error( $link ) ) { continue; }
								echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name ) . '</a></li>';
							}
							echo '</ul>';
						}
					}
					?>
				</li>
				<li<?php echo is_page( 'przewodnik' ) ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url( home_url( '/przewodnik/' ) ); ?>"><?php esc_html_e( 'Przewodnik', 'mnsk7-storefront' ); ?></a></li>
				<li<?php echo is_page( 'dostawa-i-platnosci' ) ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>"><?php esc_html_e( 'Dostawa i płatności', 'mnsk7-storefront' ); ?></a></li>
				<li<?php echo is_page( 'kontakt' ) ? ' class="current-menu-item"' : ''; ?>><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'mnsk7-storefront' ); ?></a></li>
			</ul>
		</nav>
		<div class="mnsk7-header__actions">
			<?php
			// Search: on desktop — visible inline bar; on mobile — icon opens dropdown (audit: "Keep search bar visible on desktop")
			?>
			<div class="mnsk7-header__search-wrap">
				<form role="search" method="get" class="mnsk7-header__search-form mnsk7-header__search-form--inline" action="<?php echo esc_url( home_url( '/' ) ); ?>">
					<label for="mnsk7-header-search-input" class="screen-reader-text"><?php esc_html_e( 'Szukaj produktów', 'mnsk7-storefront' ); ?></label>
					<input type="search" id="mnsk7-header-search-input" class="mnsk7-header__search-input" placeholder="<?php esc_attr_e( 'Szukaj produktów…', 'mnsk7-storefront' ); ?>" value="<?php echo get_search_query(); ?>" name="s" autocomplete="off" />
					<input type="hidden" name="post_type" value="product" />
					<button type="submit" class="mnsk7-header__search-submit" aria-label="<?php esc_attr_e( 'Szukaj', 'mnsk7-storefront' ); ?>"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></button>
				</form>
				<button type="button" class="mnsk7-header__search-toggle" aria-expanded="false" aria-controls="mnsk7-header-search" aria-label="<?php esc_attr_e( 'Szukaj', 'mnsk7-storefront' ); ?>">
					<span class="mnsk7-header__search-icon" aria-hidden="true"></span>
					<span class="mnsk7-header__search-label"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></span>
				</button>
				<div id="mnsk7-header-search" class="mnsk7-header__search-dropdown" hidden>
					<form role="search" method="get" class="mnsk7-header__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label for="mnsk7-header-search-input-mobile" class="screen-reader-text"><?php esc_html_e( 'Szukaj produktów', 'mnsk7-storefront' ); ?></label>
						<input type="search" id="mnsk7-header-search-input-mobile" class="mnsk7-header__search-input" placeholder="<?php esc_attr_e( 'Szukaj produktów…', 'mnsk7-storefront' ); ?>" value="<?php echo get_search_query(); ?>" name="s" />
						<input type="hidden" name="post_type" value="product" />
						<button type="submit" class="mnsk7-header__search-submit"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></button>
					</form>
				</div>
			</div>
			<?php
			if ( function_exists( 'wc_get_page_permalink' ) ) {
				$account_url = wc_get_page_permalink( 'myaccount' );
				$account_label = is_user_logged_in() ? wp_get_current_user()->display_name : __( 'Moje konto', 'mnsk7-storefront' );
				if ( $account_label === '' && is_user_logged_in() ) {
					$account_label = wp_get_current_user()->user_login;
				}
				?><a href="<?php echo esc_url( $account_url ); ?>" class="mnsk7-header__link mnsk7-header__link--account">
				<span class="mnsk7-header__account-icon" aria-hidden="true"></span>
				<span class="mnsk7-header__link-text"><?php echo esc_html( $account_label ); ?></span>
			</a><?php
			}
			if ( function_exists( 'wc_get_cart_url' ) && function_exists( 'woocommerce_mini_cart' ) ) {
				$cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
				$cart_total = WC()->cart ? WC()->cart->get_cart_total() : '';
				?>
				<div class="mnsk7-header__cart">
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-contents mnsk7-header__cart-trigger" aria-label="<?php esc_attr_e( 'Koszyk', 'mnsk7-storefront' ); ?>">
						<span class="mnsk7-header__cart-icon" aria-hidden="true"></span>
						<?php if ( $cart_count > 0 ) { ?>
							<span class="mnsk7-header__cart-count"><?php echo absint( $cart_count ); ?></span>
						<?php } ?>
					</a>
					<div class="mnsk7-header__cart-dropdown">
						<div class="mnsk7-header__cart-summary">
							<?php
							if ( $cart_count > 0 && $cart_total ) {
								printf(
									/* translators: 1: number of items, 2: cart total */
									esc_html( _n( '%1$d produkt · %2$s', '%1$d produktów · %2$s', $cart_count, 'mnsk7-storefront' ) ),
									$cart_count,
									wp_kses_post( $cart_total )
								);
							} else {
								esc_html_e( 'Koszyk jest pusty', 'mnsk7-storefront' );
							}
							?>
						</div>
						<div class="widget_shopping_cart_content">
							<?php woocommerce_mini_cart(); ?>
						</div>
					</div>
				</div>
				<?php
			}
			?>
		</div>
	</div>
</header>

<div id="content" class="site-content">
