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
			ob_start();
			the_content();
			$page_content = (string) ob_get_clean();
			$has_h1       = preg_match( '/<h1(?:\s|>)/i', $page_content ) === 1;
			$is_wc_flow   = ( function_exists( 'is_cart' ) && is_cart() )
				|| ( function_exists( 'is_checkout' ) && is_checkout() );
			?>
			<article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
				<?php
				if ( ! $has_h1 && ! $is_wc_flow ) {
					echo '<h1 class="screen-reader-text">' . esc_html( get_the_title() ) . '</h1>';
				}
				echo $page_content; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				?>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
