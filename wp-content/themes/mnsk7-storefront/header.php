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
				$is_shop_archive = $is_shop || ( function_exists( 'is_product_category' ) && is_product_category() ) || ( function_exists( 'is_product_tag' ) && is_product_tag() );
				$sklep_class = $is_shop_archive ? ' class="current-menu-item menu-item-has-children"' : ' class="menu-item-has-children"';
				?>
				<li<?php echo $sklep_class; ?>>
					<a href="<?php echo esc_url( $shop_url ); ?>"><?php esc_html_e( 'Sklep', 'mnsk7-storefront' ); ?></a>
					<?php
					$has_submenu = false;
					if ( taxonomy_exists( 'product_cat' ) || taxonomy_exists( 'product_tag' ) ) {
						$top_cats = array();
						if ( taxonomy_exists( 'product_cat' ) ) {
							$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 12 ) );
							$top_cats = is_wp_error( $top_cats ) ? array() : $top_cats;
						}
						$top_tags = array();
						if ( taxonomy_exists( 'product_tag' ) ) {
							$top_tags = get_terms( array( 'taxonomy' => 'product_tag', 'hide_empty' => true, 'number' => 12, 'orderby' => 'count', 'order' => 'DESC' ) );
							$top_tags = is_wp_error( $top_tags ) ? array() : $top_tags;
						}
						if ( ! empty( $top_cats ) || ! empty( $top_tags ) ) {
							$has_submenu = true;
							echo '<ul class="sub-menu">';
							foreach ( $top_cats as $term ) {
								$link = get_term_link( $term );
								if ( is_wp_error( $link ) ) { continue; }
								echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name ) . '</a></li>';
							}
							if ( ! empty( $top_tags ) ) {
								foreach ( $top_tags as $term ) {
									$link = get_term_link( $term );
									if ( is_wp_error( $link ) ) { continue; }
									echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $term->name ) . '</a></li>';
								}
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
			// Search: один поиск — только иконка, по клику dropdown (inline form скрыт)
			?>
			<div class="mnsk7-header__search-wrap">
				<button type="button" class="mnsk7-header__search-toggle" aria-expanded="false" aria-controls="mnsk7-header-search" aria-label="<?php esc_attr_e( 'Szukaj', 'mnsk7-storefront' ); ?>">
					<span class="mnsk7-header__search-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg></span>
					<span class="mnsk7-header__search-label"><?php esc_html_e( 'Szukaj', 'mnsk7-storefront' ); ?></span>
				</button>
				<div id="mnsk7-header-search" class="mnsk7-header__search-dropdown" hidden>
					<form role="search" method="get" class="mnsk7-header__search-form" action="<?php echo esc_url( home_url( '/' ) ); ?>">
						<label for="mnsk7-header-search-input" class="screen-reader-text"><?php esc_html_e( 'Szukaj produktów', 'mnsk7-storefront' ); ?></label>
						<input type="search" id="mnsk7-header-search-input" class="mnsk7-header__search-input" placeholder="<?php esc_attr_e( 'Szukaj produktów…', 'mnsk7-storefront' ); ?>" value="<?php echo esc_attr( get_search_query() ); ?>" name="s" />
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
				<span class="mnsk7-header__account-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg></span>
				<span class="mnsk7-header__link-text"><?php echo esc_html( $account_label ); ?></span>
			</a><?php
			}
			if ( function_exists( 'wc_get_cart_url' ) && function_exists( 'woocommerce_mini_cart' ) ) {
				$cart_count = WC()->cart ? WC()->cart->get_cart_contents_count() : 0;
				$cart_total = WC()->cart ? WC()->cart->get_cart_total() : '';
				?>
				<div class="mnsk7-header__cart">
					<a href="<?php echo esc_url( wc_get_cart_url() ); ?>" class="cart-contents mnsk7-header__cart-trigger" aria-label="<?php esc_attr_e( 'Koszyk', 'mnsk7-storefront' ); ?>">
						<span class="mnsk7-header__cart-icon" aria-hidden="true"><svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 2L3 6v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2V6l-3-4z"/><line x1="3" y1="6" x2="21" y2="6"/><path d="M16 10a4 4 0 0 1-8 0"/></svg></span>
						<span class="mnsk7-header__cart-count" aria-hidden="true"><?php echo absint( $cart_count ); ?></span>
					</a>
					<div class="mnsk7-header__cart-dropdown">
						<?php
						$loyalty_discount = function_exists( 'mnsk7_header_cart_loyalty_discount' ) ? mnsk7_header_cart_loyalty_discount() : 0.0;
						echo function_exists( 'mnsk7_header_cart_summary_html' )
							? mnsk7_header_cart_summary_html( $cart_count, $cart_total, $loyalty_discount )
							: '<div class="mnsk7-header__cart-summary">' . ( $cart_count > 0 && $cart_total ? sprintf( _n( '%1$d produkt · %2$s', '%1$d produktów · %2$s', $cart_count, 'mnsk7-storefront' ), $cart_count, wp_kses_post( $cart_total ) ) : esc_html__( 'Koszyk jest pusty', 'mnsk7-storefront' ) ) . '</div>';
						?>
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
