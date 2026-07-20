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
		$reading_time  = mnsk7_guide_single_estimated_reading_time( get_the_content() );
		$is_slab_guide = get_post_field( 'post_name', get_the_ID() ) === 'frez-do-wyrownania-sleba-i-planowania-powierzchni';
		$article_class = $is_slab_guide ? 'mnsk7-guide-article mnsk7-guide-article--slab' : 'mnsk7-guide-article';
		$hero_product  = $is_slab_guide && function_exists( 'wc_get_product' ) ? wc_get_product( 6820 ) : null;
		$hero_image_id = 0;
		if ( $hero_product instanceof WC_Product ) {
			$hero_gallery  = $hero_product->get_gallery_image_ids();
			$hero_image_id = ! empty( $hero_gallery ) ? reset( $hero_gallery ) : $hero_product->get_image_id();
		}
		?>
		<article id="post-<?php the_ID(); ?>" <?php post_class( $article_class ); ?>>
			<header class="mnsk7-guide-article__header">
				<div class="col-full">
					<div class="mnsk7-breadcrumb-wrap mnsk7-guide-article__breadcrumbs">
						<nav class="woocommerce-breadcrumb" aria-label="<?php esc_attr_e( 'Breadcrumb navigation', 'mnsk7-storefront' ); ?>">
							<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Strona główna', 'mnsk7-storefront' ); ?></a>
							<span class="separator" aria-hidden="true">/</span>
							<a href="<?php echo esc_url( $przewodnik_url ); ?>"><?php echo esc_html( $przewodnik_label ); ?></a>
							<span class="separator" aria-hidden="true">/</span>
							<span class="last-item" aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
						</nav>
					</div>

					<div class="mnsk7-guide-article__hero-grid">
						<div class="mnsk7-guide-article__hero-copy">
							<p class="mnsk7-guide-article__eyebrow"><?php echo esc_html( $is_slab_guide ? __( 'Frezy do planowania drewna', 'mnsk7-storefront' ) : __( 'Poradnik techniczny', 'mnsk7-storefront' ) ); ?></p>
							<h1 class="mnsk7-guide-article__title"><?php the_title(); ?></h1>
							<?php if ( has_excerpt() ) : ?>
								<p class="mnsk7-guide-article__lead"><?php echo esc_html( get_the_excerpt() ); ?></p>
							<?php endif; ?>
							<?php if ( $is_slab_guide ) : ?>
								<div class="mnsk7-slab-hero__actions">
									<a class="button mnsk7-slab-hero__primary" href="#mnsk7-guide-products"><?php esc_html_e( 'Dobierz frez do tulei', 'mnsk7-storefront' ); ?></a>
									<a class="mnsk7-slab-hero__secondary" href="#jak-wybrac-frez"><?php esc_html_e( 'Jak wybrać średnicę?', 'mnsk7-storefront' ); ?></a>
								</div>
								<ul class="mnsk7-slab-hero__trust" aria-label="<?php esc_attr_e( 'Najważniejsze informacje o ofercie', 'mnsk7-storefront' ); ?>">
									<li><?php esc_html_e( 'Trzpienie 8 i 12 mm', 'mnsk7-storefront' ); ?></li>
									<li><?php esc_html_e( 'Wymienne płytki', 'mnsk7-storefront' ); ?></li>
									<li><?php esc_html_e( 'DPD: wysyłka tego samego dnia przy zamówieniu do 12:00', 'mnsk7-storefront' ); ?></li>
								</ul>
								<p class="mnsk7-guide-article__byline">
									<span><?php esc_html_e( 'Opracowanie: Zespół MNSK7 Tool', 'mnsk7-storefront' ); ?></span>
									<span aria-hidden="true">·</span>
									<time datetime="<?php echo esc_attr( get_the_modified_date( DATE_W3C ) ); ?>"><?php echo esc_html( sprintf( __( 'Aktualizacja: %s', 'mnsk7-storefront' ), get_the_modified_date( 'd.m.Y' ) ) ); ?></time>
								</p>
							<?php endif; ?>
						</div>

						<?php if ( $is_slab_guide && $hero_product instanceof WC_Product && $hero_image_id ) : ?>
							<aside class="mnsk7-guide-article__summary mnsk7-guide-article__summary--product" aria-label="<?php esc_attr_e( 'Polecany frez do planowania', 'mnsk7-storefront' ); ?>">
								<span class="mnsk7-slab-product-card__badge"><?php esc_html_e( 'Najczęstszy wybór do tulei 8 mm', 'mnsk7-storefront' ); ?></span>
								<a class="mnsk7-guide-article__hero-product" href="<?php echo esc_url( $hero_product->get_permalink() ); ?>">
									<?php
									echo wp_get_attachment_image(
										$hero_image_id,
										'large',
										false,
										array(
											'alt'           => esc_attr__( 'Frez do planowania drewna MNSK7 Tool z wymiennymi płytkami, średnica 39 mm i trzpień 8 mm', 'mnsk7-storefront' ),
											'loading'       => 'eager',
											'fetchpriority' => 'high',
											'sizes'         => '(max-width: 767px) 88vw, 360px',
										)
									); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									?>
								</a>
								<div class="mnsk7-slab-product-card__details">
									<p class="mnsk7-slab-product-card__spec"><?php esc_html_e( 'Ø 39 mm · trzpień 8 mm · 3P', 'mnsk7-storefront' ); ?></p>
									<h2><?php esc_html_e( 'Frez z wymiennymi płytkami do planowania', 'mnsk7-storefront' ); ?></h2>
									<div class="mnsk7-slab-product-card__price"><?php echo wp_kses_post( $hero_product->get_price_html() ); ?></div>
									<p><?php esc_html_e( 'Uniwersalny wariant do drewna, MDF i wyrównywania większych powierzchni.', 'mnsk7-storefront' ); ?></p>
									<div class="mnsk7-slab-product-card__actions">
										<a href="<?php echo esc_url( $hero_product->add_to_cart_url() ); ?>" data-quantity="1" class="button product_type_simple add_to_cart_button ajax_add_to_cart" data-product_id="<?php echo esc_attr( $hero_product->get_id() ); ?>" data-product_sku="<?php echo esc_attr( $hero_product->get_sku() ); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Dodaj do koszyka: %s', 'mnsk7-storefront' ), $hero_product->get_name() ) ); ?>"><?php esc_html_e( 'Dodaj do koszyka', 'mnsk7-storefront' ); ?></a>
										<a class="mnsk7-slab-product-card__more" href="<?php echo esc_url( $hero_product->get_permalink() ); ?>"><?php esc_html_e( 'Szczegóły produktu', 'mnsk7-storefront' ); ?></a>
									</div>
								</div>
							</aside>
						<?php else : ?>
							<aside class="mnsk7-guide-article__summary" aria-label="<?php esc_attr_e( 'Article information', 'mnsk7-storefront' ); ?>">
								<span><?php esc_html_e( 'W artykule', 'mnsk7-storefront' ); ?></span>
								<strong><?php echo esc_html( $reading_time ); ?></strong>
								<a href="#mnsk7-guide-products"><?php esc_html_e( 'Produkty i kategorie', 'mnsk7-storefront' ); ?></a>
							</aside>
						<?php endif; ?>
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
