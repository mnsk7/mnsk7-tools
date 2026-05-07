<?php
/**
 * Default page template — mnsk7-storefront.
 * Jeden entry point dla wszystkich stron (w tym Koszyk), ten sam header/footer i DOM.
 * Zapobiega divergent layout gdy parent (Storefront) ładuje inny wrapper/hooks.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

get_header();
?>

<main id="main" class="site-main">
	<div class="col-full">
		<?php
		if ( function_exists( 'is_cart' ) && is_cart() ) {
			echo '<h1 class="screen-reader-text">' . esc_html__( 'Koszyk', 'mnsk7-storefront' ) . '</h1>';
		} elseif ( function_exists( 'is_checkout' ) && is_checkout() ) {
			echo '<h1 class="screen-reader-text">' . esc_html__( 'Zamówienie', 'mnsk7-storefront' ) . '</h1>';
		}
		?>
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php the_content(); ?>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
