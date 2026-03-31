<?php
/**
 * Template for single post (Przewodnik / baza wiedzy).
 * SEO: breadcrumbs, H1, content with product links shortcode, FAQ accordion.
 *
 * @package mnsk7-storefront
 */

get_header();

$przewodnik_url  = home_url( '/przewodnik/' );
$przewodnik_label = apply_filters( 'mnsk7_przewodnik_menu_label', __( 'Przewodnik', 'mnsk7-storefront' ) );
?>

<main class="mnsk7-guide-single">
	<div class="col-full">
		<div class="mnsk7-breadcrumb-wrap">
			<nav class="woocommerce-breadcrumb" aria-label="<?php esc_attr_e( 'Nawigacja okruszków', 'mnsk7-storefront' ); ?>">
				<a href="<?php echo esc_url( home_url( '/' ) ); ?>"><?php esc_html_e( 'Strona główna', 'mnsk7-storefront' ); ?></a>
				<span class="separator" aria-hidden="true">›</span>
				<a href="<?php echo esc_url( $przewodnik_url ); ?>"><?php echo esc_html( $przewodnik_label ); ?></a>
				<span class="separator" aria-hidden="true">›</span>
				<span class="last-item" aria-current="page"><?php echo esc_html( get_the_title() ); ?></span>
			</nav>
		</div>
	</div>

	<?php
	while ( have_posts() ) :
		the_post();
		?>
	<article id="post-<?php the_ID(); ?>" <?php post_class( 'mnsk7-guide-article' ); ?>>
		<header class="mnsk7-guide-article__header">
			<div class="col-full">
				<h1 class="mnsk7-guide-article__title"><?php the_title(); ?></h1>
				<p class="mnsk7-guide-article__meta">
					<time datetime="<?php echo esc_attr( get_the_date( 'c' ) ); ?>"><?php echo esc_html( get_the_date() ); ?></time>
					<?php if ( get_the_author() ) : ?>
						<span class="mnsk7-guide-article__author"> <?php echo esc_html( sprintf( __( 'przez %s', 'mnsk7-storefront' ), get_the_author() ) ); ?></span>
					<?php endif; ?>
				</p>
			</div>
		</header>

		<div class="mnsk7-guide-article__content col-full">
			<div class="mnsk7-guide-article__body">
				<?php the_content(); ?>
			</div>
		</div>

		<?php
		// FAQ: domyślnie zestaw "produkt" (dobór frezu itd.); w treści można dodać [mnsk7_faq set="dostawa"] lub własny tytuł
		$faq_set   = get_post_meta( get_the_ID(), 'mnsk7_faq_set', true );
		$faq_title = get_post_meta( get_the_ID(), 'mnsk7_faq_title', true );
		if ( $faq_set === '' ) {
			$faq_set = 'produkt';
		}
		if ( $faq_title === '' ) {
			$faq_title = __( 'Najczęściej zadawane pytania', 'mnsk7-storefront' );
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

<?php get_footer(); ?>
