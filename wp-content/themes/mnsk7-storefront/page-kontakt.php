<?php
/**
 * Template Name: Kontakt
 * Strona z danymi kontaktowymi (email, telefon, godziny, Instagram).
 *
 * @package mnsk7-storefront
 */

get_header();
?>

<main id="main" class="site-main mnsk7-page-kontakt">
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
					<?php the_content(); ?>
					<?php
					if ( function_exists( 'mnsk7_contact_info_html' ) ) {
						echo mnsk7_contact_info_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
