<?php
/**
 * Template for single guide posts.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_guide_single_estimated_reading_time' ) ) {
	/**
	 * Estimates reading time for a guide article.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	function mnsk7_guide_single_estimated_reading_time( $content ) {
		$words   = str_word_count( wp_strip_all_tags( strip_shortcodes( $content ) ) );
		$minutes = max( 1, (int) ceil( $words / 220 ) );

		return sprintf(
			/* translators: %d: estimated reading time in minutes. */
			_n( '%d min czytania', '%d min czytania', $minutes, 'mnsk7-storefront' ),
			$minutes
		);
	}
}

get_header();

$przewodnik_url   = home_url( '/przewodnik/' );
$przewodnik_label = apply_filters( 'mnsk7_przewodnik_menu_label', __( 'Przewodnik', 'mnsk7-storefront' ) );
?>

<main class="mnsk7-guide-single">
	<?php
	while ( have_posts() ) :
		the_post();
		$reading_time = mnsk7_guide_single_estimated_reading_time( get_the_content() );
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( 'mnsk7-guide-article' ); ?>>
			<header class="mnsk7-guide-article__header">
				<div class="col-full">
					<div class="mnsk7-breadcrumb-wrap mnsk7-guide-article__breadcrumbs">
						<nav class="woocommerce-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb navigation', 'mnsk7-storefront' ); ?>">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Strona glowna', 'mnsk7-storefront' ); ?></a>
							<span class="separator" aria-hidden="true">/</span>
							<a href="<?php echo esc_url( $przewodnik_url ); ?>"><?php echo esc_html( $przewodnik_label ); ?></a>
							<span class="separator" aria-hidden="true">/</span>
							<span class="last-item" aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
						</nav>
					</div>

					<div class="mnsk7-guide-article__hero-grid">
						<div class="mnsk7-guide-article__hero-copy">
							<p class="mnsk7-guide-article__eyebrow"><?php esc_html_e( 'Poradnik techniczny', 'mnsk7-storefront' ); ?></p>
							<h1 class="mnsk7-guide-article__title"><?php the_title(); ?></h1>
							<?php if ( has_excerpt() ) : ?>
								<p class="mnsk7-guide-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<?php endif; ?>
						</div>

						<aside class="mnsk7-guide-article__summary" aria-label="<?php esc_attr_e( 'Article information', 'mnsk7-storefront' ); ?>">
							<span><?php esc_html_e( 'W artykule', 'mnsk7-storefront' ); ?></span>
							<strong><?php echo esc_html( $reading_time ); ?></strong>
							<a href="#mnsk7-guide-products"><?php esc_html_e( 'Produkty i kategorie', 'mnsk7-storefront' ); ?></a>
						</aside>
					</div>
				</div>
			</header>

			<div class="mnsk7-guide-article__content col-full">
				<div class="mnsk7-guide-article__body">
					<?php the_content(); ?>
				</div>
			</div>

			<?php
			$faq_set   = get_post_meta( get_the_ID(), 'mnsk7_faq_set', true );
			$faq_title = get_post_meta( get_the_ID(), 'mnsk7_faq_title', true );
			if ( $faq_set === '' ) {
				$faq_set = 'produkt';
			}
			if ( $faq_title === '' ) {
				$faq_title = __( 'Najczesciej zadawane pytania', 'mnsk7-storefront' );
			}
			?>
			<section class="mnsk7-seo-faq mnsk7-guide-faq">
				<div class="col-full">
					<?php echo do_shortcode( '[mnsk7_faq set="' . esc_attr( $faq_set ) . '" title="' . esc_attr( $faq_title ) . '"]' ); ?>
				</div>
			</section>
		</article>
	<?php endwhile; ?>
</main>

<?php
get_footer();
