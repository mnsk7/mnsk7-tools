<?php
/**
 * The Template for displaying all single products
 *
 * Override: copy from WooCommerce to yourtheme/woocommerce/single-product.php.
 * Child theme: tech-storefront. Customizations in content-single-product.php.
 *
 * @see         https://woocommerce.com/document/template-structure/
 * @package     WooCommerce\Templates
 * @version     1.6.4
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

get_header( 'shop' ); ?>

	<?php
		do_action( 'woocommerce_before_main_content' );
	?>

		<?php while ( have_posts() ) : ?>
			<?php the_post(); ?>

			<?php wc_get_template_part( 'content', 'single-product' ); ?>

		<?php endwhile; ?>

	<?php
		do_action( 'woocommerce_after_main_content' );
	?>

	<?php do_action( 'woocommerce_sidebar' ); ?>

<?php
get_footer( 'shop' );
