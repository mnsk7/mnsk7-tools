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
			<?php if ( has_custom_logo() ) : ?>
				<?php the_custom_logo(); ?>
			<?php else : ?>
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>" class="mnsk7-header__logo-link" rel="home"><?php bloginfo( 'name' ); ?></a>
			<?php endif; ?>
		</div>
		<nav class="mnsk7-header__nav" role="navigation" aria-label="<?php esc_attr_e( 'Menu główne', 'mnsk7-storefront' ); ?>">
			<button type="button" class="mnsk7-header__menu-toggle" aria-expanded="false" aria-controls="mnsk7-primary-menu"><?php esc_html_e( 'Menu', 'mnsk7-storefront' ); ?></button>
			<?php
			wp_nav_menu( array(
				'theme_location' => 'primary',
				'menu_id'        => 'mnsk7-primary-menu',
				'menu_class'     => 'mnsk7-header__menu',
				'container'      => false,
				'fallback_cb'    => function () {
					echo '<ul id="mnsk7-primary-menu" class="mnsk7-header__menu">';
					if ( function_exists( 'wc_get_page_permalink' ) ) {
						echo '<li><a href="' . esc_url( wc_get_page_permalink( 'shop' ) ) . '">' . esc_html__( 'Sklep', 'mnsk7-storefront' ) . '</a></li>';
					}
					echo '<li><a href="' . esc_url( home_url( '/kontakt/' ) ) . '">' . esc_html__( 'Kontakt', 'mnsk7-storefront' ) . '</a></li>';
					echo '</ul>';
				},
			) );
			?>
		</nav>
		<div class="mnsk7-header__actions">
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mnsk7-header__link"><?php esc_html_e( 'Moje konto', 'mnsk7-storefront' ); ?></a>
			<?php endif; ?>
			<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
				<div class="mnsk7-header__cart"><?php woocommerce_mini_cart(); ?></div>
			<?php endif; ?>
		</div>
	</div>
</header>

<div id="content" class="site-content">
