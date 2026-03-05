<?php
/**
 * Template Name: MNK7 Strona główna
 * Główna strona sklepu: baner, kategorie, bestsellery, trust Allegro, lojalność, opinie, Instagram.
 *
 * @package tech-storefront
 */

get_header();
?>

<main id="main" class="site-main mnsk7-front-page">

	<section class="mnsk7-front-hero">
		<div class="container">
			<h1 class="mnsk7-front-hero__title"><?php echo esc_html( get_bloginfo( 'name' ) ); ?></h1>
			<p class="mnsk7-front-hero__tagline"><?php echo esc_html( get_bloginfo( 'description' ) ); ?></p>
			<p class="mnsk7-front-hero__text"><?php esc_html_e( 'Dostawa następnego dnia. Faktura VAT na życzenie. Tylko Polska.', 'tech-storefront' ); ?></p>
		</div>
	</section>

	<?php if ( taxonomy_exists( 'product_cat' ) ) : ?>
	<section class="mnsk7-front-cats">
		<div class="container">
			<h2 class="mnsk7-front-section-title"><?php esc_html_e( 'Kategorie', 'tech-storefront' ); ?></h2>
			<div class="mnsk7-front-cats__grid">
				<?php
				$cats = get_terms( array(
					'taxonomy'   => 'product_cat',
					'hide_empty' => true,
					'parent'     => 0,
					'number'     => 12,
					'orderby'    => 'count',
					'order'      => 'DESC',
				) );
				if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
					foreach ( $cats as $cat ) :
						$link = get_term_link( $cat );
						if ( is_wp_error( $link ) ) {
							continue;
						}
						?>
						<a href="<?php echo esc_url( $link ); ?>" class="mnsk7-front-cats__item"><?php echo esc_html( $cat->name ); ?></a>
					<?php endforeach; ?>
				<?php endif; ?>
			</div>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<p class="mnsk7-front-cats__shop-link"><a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Wszystkie produkty →', 'tech-storefront' ); ?></a></p>
			<?php endif; ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( function_exists( 'do_shortcode' ) ) : ?>
	<section class="mnsk7-front-section mnsk7-front-bestsellers">
		<div class="container">
			<?php echo do_shortcode( '[mnsk7_bestsellers limit="8" title="Polecane / Bestsellery"]' ); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( function_exists( 'mnsk7_allegro_trust_html' ) ) : ?>
	<section class="mnsk7-front-section mnsk7-front-trust">
		<div class="container">
			<?php echo mnsk7_allegro_trust_html( array() ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
		</div>
	</section>
	<?php endif; ?>

	<section class="mnsk7-front-section mnsk7-front-loyalty">
		<div class="container">
			<h2 class="mnsk7-front-section-title"><?php esc_html_e( 'System rabatów', 'tech-storefront' ); ?></h2>
			<p class="mnsk7-front-loyalty__text">
				<?php esc_html_e( 'W panelu „Moje konto” w ciągu roku:', 'tech-storefront' ); ?>
				<strong> 1000 zł → 5%, 3000 zł → 10%, 5000 zł → 15%, 10 000 zł → 20%.</strong>
			</p>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<p><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Moje konto →', 'tech-storefront' ); ?></a></p>
			<?php endif; ?>
		</div>
	</section>

	<?php if ( function_exists( 'do_shortcode' ) ) : ?>
	<section class="mnsk7-front-section mnsk7-front-reviews">
		<div class="container">
			<?php echo do_shortcode( '[mnsk7_allegro_reviews]' ); ?>
		</div>
	</section>
	<?php endif; ?>

	<?php if ( function_exists( 'do_shortcode' ) ) : ?>
	<section class="mnsk7-front-section mnsk7-front-insta">
		<div class="container">
			<?php echo do_shortcode( '[mnsk7_instagram_feed title="Instagram @mnsk7tools"]' ); ?>
		</div>
	</section>
	<?php endif; ?>

</main>

<?php
get_footer();
