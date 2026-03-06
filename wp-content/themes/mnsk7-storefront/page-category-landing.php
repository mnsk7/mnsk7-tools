<?php
/**
 * Template Name: Kategoria — Landing SEO
 *
 * Universal category landing page. Requires page slug matching a Woo category slug
 * or a custom field mnsk7_cat_slug with the target category slug.
 *
 * @package mnsk7-storefront
 */

get_header();

$cat_slug = get_post_meta( get_the_ID(), 'mnsk7_cat_slug', true );
if ( ! $cat_slug ) {
	$cat_slug = get_post_field( 'post_name', get_the_ID() );
}
$term = get_term_by( 'slug', $cat_slug, 'product_cat' );
?>

<main class="mnsk7-seo-page">

	<section class="mnsk7-seo-hero">
		<div class="col-full">
			<h1 class="mnsk7-seo-hero__title"><?php the_title(); ?></h1>
			<?php if ( $term && ! is_wp_error( $term ) ) : ?>
			<p class="mnsk7-seo-hero__sub"><?php echo esc_html( $term->count ); ?> <?php esc_html_e( 'produktów w ofercie', 'mnsk7-storefront' ); ?></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( have_posts() ) : while ( have_posts() ) : the_post(); ?>
	<?php $content = get_the_content(); if ( trim( $content ) ) : ?>
	<section class="mnsk7-seo-intro">
		<div class="col-full">
			<div class="mnsk7-seo-intro__text">
				<?php the_content(); ?>
			</div>
		</div>
	</section>
	<?php endif; endwhile; endif; ?>

	<?php if ( $term && ! is_wp_error( $term ) ) : ?>
	<section class="mnsk7-seo-products">
		<div class="col-full">
			<h2 class="mnsk7-seo-products__title">
				<?php printf( esc_html__( 'Produkty: %s', 'mnsk7-storefront' ), esc_html( $term->name ) ); ?>
			</h2>
			<?php echo do_shortcode( sprintf(
				'[products category="%s" limit="12" columns="4" orderby="popularity"]',
				esc_attr( $term->slug )
			) ); ?>
			<p class="mnsk7-section__more">
				<a href="<?php echo esc_url( get_term_link( $term ) ); ?>">
					<?php esc_html_e( 'Wszystkie produkty w kategorii →', 'mnsk7-storefront' ); ?>
				</a>
			</p>
		</div>
	</section>
	<?php endif; ?>

	<section class="mnsk7-seo-faq">
		<div class="col-full">
			<?php echo do_shortcode( '[mnsk7_faq]' ); ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
