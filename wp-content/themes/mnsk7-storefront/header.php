<?php
/**
 * Header — mnsk7-storefront (child).
 * When parent Storefront is missing or overwritten by WP, outputs fallback header
 * so the site never loses the header.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

$parent_storefront_ok = function_exists( 'mnsk7_parent_storefront_available' ) && mnsk7_parent_storefront_available();

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

<header id="masthead" class="site-header" role="banner">
	<div class="col-full">
		<?php if ( $parent_storefront_ok ) : ?>
			<?php do_action( 'storefront_header' ); ?>
		<?php else : ?>
			<?php
			// Fallback when parent theme is missing/overwritten — keep header visible
			?>
			<div class="site-branding">
				<?php if ( has_custom_logo() ) : ?>
					<?php the_custom_logo(); ?>
				<?php else : ?>
					<p class="site-title"><a href="<?php echo esc_url( home_url( '/' ) ); ?>" rel="home"><?php bloginfo( 'name' ); ?></a></p>
					<?php if ( get_bloginfo( 'description' ) ) : ?>
						<p class="site-description"><?php bloginfo( 'description' ); ?></p>
					<?php endif; ?>
				<?php endif; ?>
			</div>
			<nav id="site-navigation" class="main-navigation storefront-primary-navigation" role="navigation">
				<button class="menu-toggle" aria-controls="primary-menu" aria-expanded="false"><?php esc_html_e( 'Menu', 'mnsk7-storefront' ); ?></button>
				<?php
				wp_nav_menu( array(
					'theme_location' => 'primary',
					'menu_id'        => 'primary-menu',
					'container'      => false,
					'fallback_cb'    => false,
				) );
				?>
			</nav>
			<div class="mnsk7-header-actions">
				<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
				<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mnsk7-header-link mnsk7-header-link--account"><?php esc_html_e( 'Moje konto', 'mnsk7-storefront' ); ?></a>
				<?php endif; ?>
				<?php if ( function_exists( 'woocommerce_mini_cart' ) ) : ?>
				<ul class="site-header-cart">
					<li><?php woocommerce_mini_cart(); ?></li>
				</ul>
				<?php endif; ?>
			</div>
		<?php endif; ?>
	</div>
</header>

<div id="content" class="site-content">
