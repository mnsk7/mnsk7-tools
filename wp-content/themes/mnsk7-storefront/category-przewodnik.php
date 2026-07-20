<?php
/**
 * Category archive for Przewodnik.
 *
 * @package mnsk7-storefront
 */

defined( 'ABSPATH' ) || exit;

if ( ! function_exists( 'mnsk7_guide_estimated_reading_time' ) ) {
	/**
	 * Estimates reading time for a post.
	 *
	 * @param string $content Post content.
	 * @return string
	 */
	function mnsk7_guide_estimated_reading_time( $content ) {
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

$category      = get_queried_object();
$category_name = $category instanceof WP_Term ? $category->name : __( 'Przewodnik', 'mnsk7-storefront' );
$total_posts   = $category instanceof WP_Term ? (int) $category->count : 0;
$topics        = array(
	array( __( 'Frezy spiralne', 'mnsk7-storefront' ), home_url( '/kategoria-produktu/frezy-spiralne/' ) ),
	array( __( 'Frezy kompresyjne', 'mnsk7-storefront' ), home_url( '/kategoria-produktu/frezy-kompresyjne-updown-cut/' ) ),
	array( __( 'Planowanie powierzchni', 'mnsk7-storefront' ), home_url( '/kategoria-produktu/frezy-do-planowania/' ) ),
	array( __( 'Obróbka 3D', 'mnsk7-storefront' ), home_url( '/kategoria-produktu/frezy-kulowe/' ) ),
	array( __( 'Frezy proste', 'mnsk7-storefront' ), home_url( '/kategoria-produktu/frezy-proste/' ) ),
	array( __( 'Pilniki obrotowe', 'mnsk7-storefront' ), home_url( '/kategoria-produktu/pilniki-obrotowe/' ) ),
);
?>

<main class="mnsk7-guide-archive">
	<section class="mnsk7-guide-archive__hero">
		<div class="col-full">
			<div class="mnsk7-guide-archive__hero-grid">
				<div class="mnsk7-guide-archive__hero-copy">
					<p class="mnsk7-guide-archive__eyebrow"><?php esc_html_e( 'Baza wiedzy MNSK7 Tool', 'mnsk7-storefront' ); ?></p>
					<h1 class="mnsk7-guide-archive__title"><?php echo esc_html( $category_name ); ?></h1>
					<p class="mnsk7-guide-archive__lead">
						<?php esc_html_e( 'Praktyczne poradniki o frezach CNC, doborze narzędzi, materiałach i jakości obróbki. Bez lania wody: co wybrać, kiedy uważać i gdzie przejść do właściwych produktów.', 'mnsk7-storefront' ); ?>
					</p>
					<div class="mnsk7-guide-archive__actions" aria-label="<?php esc_attr_e( 'Szybkie przejścia', 'mnsk7-storefront' ); ?>">
						<a class="mnsk7-guide-archive__primary" href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Przejdź do sklepu', 'mnsk7-storefront' ); ?></a>
						<a class="mnsk7-guide-archive__secondary" href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Zapytaj o dobór', 'mnsk7-storefront' ); ?></a>
					</div>
				</div>

				<div class="mnsk7-guide-archive__panel" aria-label="<?php esc_attr_e( 'Informacje o przewodniku', 'mnsk7-storefront' ); ?>">
					<span class="mnsk7-guide-archive__panel-kicker"><?php esc_html_e( 'W archiwum', 'mnsk7-storefront' ); ?></span>
					<strong class="mnsk7-guide-archive__panel-number"><?php echo esc_html( number_format_i18n( $total_posts ) ); ?></strong>
					<span class="mnsk7-guide-archive__panel-label"><?php esc_html_e( 'poradników technicznych', 'mnsk7-storefront' ); ?></span>
					<p><?php esc_html_e( 'Każdy tekst prowadzi dalej do powiązanych kategorii i produktów, żeby szybciej zamienić wiedzę w dobór narzędzia.', 'mnsk7-storefront' ); ?></p>
				</div>
			</div>

			<nav class="mnsk7-guide-archive__topics" aria-label="<?php esc_attr_e( 'Tematy przewodnika', 'mnsk7-storefront' ); ?>">
				<?php foreach ( $topics as $topic ) : ?>
					<a href="<?php echo esc_url( $topic[1] ); ?>"><?php echo esc_html( $topic[0] ); ?></a>
				<?php endforeach; ?>
			</nav>
		</div>
	</section>

	<section class="mnsk7-guide-archive__content">
		<div class="col-full">
			<?php if ( have_posts() ) : ?>
				<div class="mnsk7-guide-archive__section-head">
					<h2><?php esc_html_e( 'Najnowsze artykuły', 'mnsk7-storefront' ); ?></h2>
					<p><?php esc_html_e( 'Czytaj od początku albo wybierz temat pod konkretny materiał i operację.', 'mnsk7-storefront' ); ?></p>
				</div>

				<div class="mnsk7-guide-archive__grid">
					<?php
					$index = 0;
					while ( have_posts() ) :
						the_post();
						$index++;
						$is_featured = $index === 1 && ! is_paged();
						?>
						<article id="post-<?php the_ID(); ?>" <?php post_class( $is_featured ? 'mnsk7-guide-card mnsk7-guide-card--featured' : 'mnsk7-guide-card' ); ?>>
							<a class="mnsk7-guide-card__link" href="<?php the_permalink(); ?>" aria-label="<?php echo esc_attr( sprintf( __( 'Czytaj: %s', 'mnsk7-storefront' ), get_the_title() ) ); ?>">
								<span class="mnsk7-guide-card__meta">
									<span><?php esc_html_e( 'Poradnik techniczny', 'mnsk7-storefront' ); ?></span>
									<span><?php echo esc_html( mnsk7_guide_estimated_reading_time( get_the_content() ) ); ?></span>
								</span>
								<h3 class="mnsk7-guide-card__title"><?php the_title(); ?></h3>
								<p class="mnsk7-guide-card__excerpt"><?php echo esc_html( wp_trim_words( get_the_excerpt(), $is_featured ? 34 : 22, '...' ) ); ?></p>
								<span class="mnsk7-guide-card__cta"><?php esc_html_e( 'Czytaj poradnik', 'mnsk7-storefront' ); ?></span>
							</a>
						</article>
					<?php endwhile; ?>
				</div>

				<div class="mnsk7-guide-archive__pagination">
					<?php
					the_posts_pagination(
						array(
							'mid_size'  => 1,
							'prev_text' => __( 'Nowsze', 'mnsk7-storefront' ),
							'next_text' => __( 'Starsze', 'mnsk7-storefront' ),
						)
					);
					?>
				</div>
			<?php else : ?>
				<div class="mnsk7-guide-archive__empty">
					<h2><?php esc_html_e( 'Poradniki są w przygotowaniu', 'mnsk7-storefront' ); ?></h2>
					<p><?php esc_html_e( 'Wróć za chwilę albo przejdź do sklepu, jeśli chcesz od razu dobrać narzędzie.', 'mnsk7-storefront' ); ?></p>
					<a class="mnsk7-guide-archive__primary" href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Zobacz produkty', 'mnsk7-storefront' ); ?></a>
				</div>
			<?php endif; ?>
		</div>
	</section>
</main>

<?php
get_footer();
