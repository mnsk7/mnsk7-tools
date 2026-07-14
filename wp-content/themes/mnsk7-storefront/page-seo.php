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
	<?php if ( is_page( 'przewodnik' ) ) : ?>
		<?php
		$guide_posts = new WP_Query(
			array(
				'post_type'           => 'post',
				'post_status'         => 'publish',
				'category_name'       => 'przewodnik',
				'posts_per_page'      => 12,
				'orderby'             => 'date',
				'order'               => 'DESC',
				'ignore_sticky_posts' => true,
			)
		);
		?>
		<?php if ( $guide_posts->have_posts() ) : ?>
			<section class="mnsk7-guide-index">
				<div class="col-full">
					<h2 class="mnsk7-guide-index__title"><?php esc_html_e( 'Najnowsze poradniki', 'mnsk7-storefront' ); ?></h2>
					<div class="mnsk7-guide-index__grid">
						<?php while ( $guide_posts->have_posts() ) : ?>
							<?php $guide_posts->the_post(); ?>
							<article class="mnsk7-guide-index__card">
								<h3 class="mnsk7-guide-index__card-title">
									<a href="<?php the_permalink(); ?>"><?php the_title(); ?></a>
								</h3>
								<?php if ( has_excerpt() ) : ?>
									<p class="mnsk7-guide-index__excerpt"><?php echo esc_html( get_the_excerpt() ); ?></p>
								<?php endif; ?>
								<a class="mnsk7-guide-index__more" href="<?php the_permalink(); ?>"><?php esc_html_e( 'Czytaj poradnik', 'mnsk7-storefront' ); ?></a>
							</article>
						<?php endwhile; ?>
					</div>
				</div>
			</section>
			<?php wp_reset_postdata(); ?>
		<?php endif; ?>
	<?php endif; ?>
</main>
<?php get_footer(); ?>
