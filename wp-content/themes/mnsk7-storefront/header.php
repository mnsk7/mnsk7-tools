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

<header id="masthead" class="site-header mnsk7-header" role="banner">
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
			<button type="button" class="mnsk7-header__menu-toggle" aria-expanded="false" aria-controls="mnsk7-primary-menu"><?php esc_html_e( 'Menu', 'mnsk7-storefront' ); ?></button>
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
			if ( function_exists( 'wc_get_page_permalink' ) ) {
				?><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mnsk7-header__link"><?php esc_html_e( 'Moje konto', 'mnsk7-storefront' ); ?></a><?php
			}
			if ( function_exists( 'woocommerce_mini_cart' ) ) {
				echo '<div class="mnsk7-header__cart">';
				woocommerce_mini_cart();
				echo '</div>';
			}
			?>
		</div>
	</div>
</header>

<div id="content" class="site-content">
