<?php
/**
 * Template Name: Strona SEO
 *
 * Prosta strona pod SEO: H1, treść, opcjonalnie shortcode w treści.
 *
 * @package mnsk7-storefront
 */
get_header();
?>
<main class="mnsk7-seo-page">
	<section class="mnsk7-seo-hero">
		<div class="col-full">
			<h1 class="mnsk7-seo-hero__title"><?php the_title(); ?></h1>
		</div>
	</section>
	<?php while ( have_posts() ) : the_post(); ?>
	<section class="mnsk7-seo-intro">
		<div class="col-full">
			<div class="mnsk7-seo-intro__text">
				<?php the_content(); ?>
			</div>
		</div>
	</section>
	<?php endwhile; ?>
</main>
<?php get_footer(); ?>
