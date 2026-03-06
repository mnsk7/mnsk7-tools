<?php
/**
 * Template Name: Dostawa i płatności
 * Strona z tabelą warunków dostawy (InPost, DPD, free od 300 zł).
 *
 * @package tech-storefront
 */

get_header();
?>

<main id="main" class="site-main mnsk7-page-dostawa">
	<div class="col-full">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>
				<div class="entry-content">
					<?php
					the_content();
					if ( function_exists( 'mnsk7_delivery_rules_table_html' ) ) {
						echo mnsk7_delivery_rules_table_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					if ( function_exists( 'do_shortcode' ) ) {
						echo do_shortcode( '[mnsk7_delivery_eta]' );
					}
					?>
					<p class="mnsk7-page-dostawa__note"><?php esc_html_e( 'Realizujemy zamówienia tylko na terenie Polski. Faktura VAT na życzenie.', 'tech-storefront' ); ?></p>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
