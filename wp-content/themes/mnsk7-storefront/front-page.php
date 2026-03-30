<?php
/**
 * Strona główna sklepu MNK7 Tools.
 *
 * @package mnsk7-storefront
 */

get_header();
?>

<main id="main" class="site-main mnsk7-front-page">

	<!-- HERO -->
	<section class="mnsk7-hero">
		<div class="mnsk7-hero__inner col-full">
			<div class="mnsk7-hero__layout">
				<div class="mnsk7-hero__content">
					<p class="mnsk7-hero__eyebrow"><?php esc_html_e( 'MNSK7 Tools • Sklep CNC', 'mnsk7-storefront' ); ?></p>
					<h1 class="mnsk7-hero__title"><?php esc_html_e( 'Frezy CNC do drewna, aluminium i tworzyw', 'mnsk7-storefront' ); ?></h1>
					<p class="mnsk7-hero__sub"><?php esc_html_e( 'Szybka wysyłka 24h, faktura VAT i realne stany magazynowe.', 'mnsk7-storefront' ); ?></p>

					<ul class="mnsk7-hero__usps" aria-label="<?php esc_attr_e( 'Najważniejsze informacje', 'mnsk7-storefront' ); ?>">
						<li class="mnsk7-hero__usp"><?php esc_html_e( 'Wysyłka 24h', 'mnsk7-storefront' ); ?></li>
						<li class="mnsk7-hero__usp"><?php esc_html_e( 'Faktura VAT', 'mnsk7-storefront' ); ?></li>
						<li class="mnsk7-hero__usp"><?php esc_html_e( 'Realne stany magazynowe', 'mnsk7-storefront' ); ?></li>
					</ul>

					<div class="mnsk7-hero__materials-wrap">
						<p class="mnsk7-hero__group-label"><?php esc_html_e( 'Materiały', 'mnsk7-storefront' ); ?></p>
						<div class="mnsk7-hero__materials" aria-label="<?php esc_attr_e( 'Szybki wybór materiału', 'mnsk7-storefront' ); ?>">
							<?php
							$shop_url = function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/sklep/' );
							$materials = array(
								array( 'label' => 'Drewno', 'slug' => 'drewno' ),
								array( 'label' => 'MDF', 'slug' => 'mdf' ),
								array( 'label' => 'Aluminium', 'slug' => 'aluminium' ),
								array( 'label' => 'Stal', 'slug' => 'stal' ),
								array( 'label' => __( 'Tworzywa', 'mnsk7-storefront' ), 'slug' => 'tworzywa-sztuczne' ),
							);
							foreach ( $materials as $material ) {
								$term = taxonomy_exists( 'product_tag' ) ? get_term_by( 'slug', $material['slug'], 'product_tag' ) : false;
								$link = ( $term && ! is_wp_error( $term ) ) ? get_term_link( $term ) : add_query_arg( 's', rawurlencode( $material['label'] ), $shop_url );
								?>
								<a href="<?php echo esc_url( $link ); ?>" class="mnsk7-hero__material-chip"><?php echo esc_html( $material['label'] ); ?></a>
								<?php
							}
							?>
						</div>
					</div>

					<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<div class="mnsk7-hero__ctas">
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="mnsk7-hero__btn mnsk7-hero__btn--primary">
							<?php esc_html_e( 'Przejdź do sklepu', 'mnsk7-storefront' ); ?>
						</a>
						<a href="#mnsk7-home-catalog" class="mnsk7-hero__btn mnsk7-hero__btn--ghost">
							<?php esc_html_e( 'Zobacz kategorie', 'mnsk7-storefront' ); ?>
						</a>
					</div>
					<?php endif; ?>
				</div>

				<figure class="mnsk7-hero__media" aria-hidden="true">
					<picture>
						<source
							srcset="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/hero-frez-cnc.svg' ); ?>"
							media="(max-width: 767px)"
						/>
						<img
							src="<?php echo esc_url( get_stylesheet_directory_uri() . '/assets/img/hero-frez-cnc.svg' ); ?>"
							alt=""
							width="560"
							height="420"
							loading="eager"
							decoding="async"
						/>
					</picture>
				</figure>
			</div>
		</div>
	</section>

	<!-- BESTSELLERS (CRO: drugi blok po hero — od razu produkt) -->
	<?php if ( function_exists( 'do_shortcode' ) ) : ?>
	<section class="mnsk7-section mnsk7-section--bestsellers">
		<div class="col-full">
			<?php echo do_shortcode( '[mnsk7_bestsellers limit="8" title="Bestsellery i polecane"]' ); ?>
			<p class="mnsk7-section__more mnsk7-bestsellers-more">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ) : home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Zobacz wszystkie bestsellery →', 'mnsk7-storefront' ); ?></a>
			</p>
		</div>
	</section>
	<?php endif; ?>

	<!-- TRUST + OPINIE (CRO: trzeci blok — zaufanie przed głębszym katalogiem) -->
	<section class="mnsk7-section mnsk7-section--trust mnsk7-section--light">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Dlaczego kupujący nam ufają', 'mnsk7-storefront' ); ?></h2>
			<div class="mnsk7-trust-stats">
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number">100%</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'pozytywnych opinii', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number">383</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'ocen na Allegro', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number">3 500+</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'zamówień w 2025 r.', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number">425</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'produktów w ofercie', 'mnsk7-storefront' ); ?></span>
				</div>
			</div>
			<p class="mnsk7-trust-stats__sub"><?php esc_html_e( 'Super Sprzedawca Allegro — najwyższa jakość obsługi i realizacji zamówień.', 'mnsk7-storefront' ); ?></p>
			<?php echo do_shortcode( '[mnsk7_allegro_reviews title="" allegro_link="0"]' ); ?>
			<?php $allegro_url = defined( 'MNK7_ALLEGRO_SELLER_URL' ) ? MNK7_ALLEGRO_SELLER_URL : '#'; ?>
			<?php if ( $allegro_url && $allegro_url !== '#' ) : ?>
				<div class="mnsk7-trust-cta">
					<a href="<?php echo esc_url( $allegro_url ); ?>" class="mnsk7-trust-cta__btn" target="_blank" rel="noopener nofollow">
						<?php esc_html_e( 'Zobacz profil i opinie na Allegro →', 'mnsk7-storefront' ); ?>
					</a>
				</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- KATALOG: grupy chipów (tagi + kategorie) jako poziome swipe rows, potem siatka kart kategorii. -->
	<?php
	$cats     = array();
	$tags     = array();
	$has_cats = taxonomy_exists( 'product_cat' );
	$has_tags = taxonomy_exists( 'product_tag' );
	if ( $has_cats ) {
		$cats = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 20,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );
	}
	if ( $has_tags ) {
		$tags = get_terms( array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
			'number'     => 20,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );
	}
	$tags_label = apply_filters( 'mnsk7_megamenu_heading_tags', __( 'Zastosowanie i materiały', 'mnsk7-storefront' ) );
	$cats_label = apply_filters( 'mnsk7_megamenu_heading_categories', __( 'Rodzaje frezów', 'mnsk7-storefront' ) );
	$show_catalog = ( $has_cats && ! is_wp_error( $cats ) && ! empty( $cats ) ) || ( $has_tags && ! is_wp_error( $tags ) && ! empty( $tags ) );
	if ( $show_catalog ) :
	?>
	<section id="mnsk7-home-catalog" class="mnsk7-section mnsk7-section--catalog mnsk7-section--light">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Przeglądaj asortyment', 'mnsk7-storefront' ); ?></h2>

			<?php if ( $has_tags && ! is_wp_error( $tags ) && ! empty( $tags ) ) : ?>
			<div class="mnsk7-catalog-aside mnsk7-catalog-aside--tags" role="navigation" aria-label="<?php echo esc_attr( $tags_label ); ?>">
				<h3 class="mnsk7-catalog-aside__title"><?php echo esc_html( $tags_label ); ?></h3>
				<div class="mnsk7-catalog-chips__scroll mnsk7-catalog-chips__scroll--cloud">
					<?php foreach ( $tags as $tag ) :
						$t_link = get_term_link( $tag );
						if ( is_wp_error( $t_link ) ) continue;
						$tag_name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $tag->name ) : $tag->name;
						$tag_name = trim( preg_replace( '/\s*mnsk7-tools\.pl\s*/i', '', (string) $tag_name ) );
					?>
					<a href="<?php echo esc_url( $t_link ); ?>" class="mnsk7-tags-chip"><?php echo esc_html( $tag_name ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( $has_cats && ! is_wp_error( $cats ) && ! empty( $cats ) ) : ?>
			<h3 class="mnsk7-catalog-aside__title mnsk7-catalog-aside__title--cats"><?php echo esc_html( $cats_label ); ?></h3>
				<div class="mnsk7-cats mnsk7-cats--catalog">
					<?php foreach ( $cats as $cat ) :
						$link = get_term_link( $cat );
						if ( is_wp_error( $link ) ) continue;
						$cat_name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $cat->name ) : $cat->name;
						$cat_name = trim( preg_replace( '/\s*mnsk7-tools\.pl\s*/i', '', (string) $cat_name ) );
						$img_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
						$img    = $img_id ? wp_get_attachment_image( $img_id, 'medium', false, array( 'alt' => $cat_name ) ) : '';
					?>
					<a href="<?php echo esc_url( $link ); ?>" class="mnsk7-cats__item">
						<span class="mnsk7-cats__img-wrap">
							<?php if ( $img ) : ?>
								<?php echo $img; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
							<?php else : ?>
								<span class="mnsk7-cats__icon mnsk7-cats__icon--default" aria-hidden="true"></span>
							<?php endif; ?>
						</span>
						<span class="mnsk7-cats__body">
							<span class="mnsk7-cats__name"><?php echo esc_html( $cat_name ); ?></span>
							<span class="mnsk7-cats__count"><?php echo esc_html( $cat->count ); ?> <?php esc_html_e( 'prod.', 'mnsk7-storefront' ); ?></span>
						</span>
						<span class="mnsk7-cats__arrow" aria-hidden="true">→</span>
					</a>
					<?php endforeach; ?>
				</div>
			<?php endif; ?>

			<p class="mnsk7-section__more">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Wszystkie produkty →', 'mnsk7-storefront' ); ?></a>
			</p>
		</div>
	</section>
	<?php endif; ?>

	<!-- SYSTEM RABATÓW -->
	<section class="mnsk7-section mnsk7-section--loyalty mnsk7-section--light">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Program rabatowy dla stałych klientów', 'mnsk7-storefront' ); ?></h2>
			<p class="mnsk7-loyalty-intro"><?php esc_html_e( 'Im więcej zamawiasz w ciągu roku, tym większy stały rabat na każde kolejne zamówienie:', 'mnsk7-storefront' ); ?></p>
			<div class="mnsk7-loyalty-tiers">
				<?php
				$tiers = array(
					array( 'from' => '1 000', 'pct' => '5%',  'label' => '' ),
					array( 'from' => '3 000', 'pct' => '10%', 'label' => '' ),
					array( 'from' => '5 000', 'pct' => '15%', 'label' => '' ),
					array( 'from' => '10 000', 'pct' => '20%', 'label' => '' ),
				);
				foreach ( $tiers as $tier ) :
				?>
				<div class="mnsk7-loyalty-tier">
					<span class="mnsk7-loyalty-tier__pct"><?php echo esc_html( $tier['pct'] ); ?></span>
					<span class="mnsk7-loyalty-tier__from"><?php printf( esc_html__( 'od %s zł/rok', 'mnsk7-storefront' ), esc_html( $tier['from'] ) ); ?></span>
				</div>
				<?php endforeach; ?>
			</div>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<div class="mnsk7-loyalty-cta">
				<?php if ( is_user_logged_in() ) : ?>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mnsk7-btn mnsk7-btn--primary"><?php esc_html_e( 'Sprawdź swój poziom rabatu w Moje konto →', 'mnsk7-storefront' ); ?></a>
				<?php else : ?>
					<p class="mnsk7-loyalty-cta__guest"><?php esc_html_e( 'Zaloguj się lub załóż konto, aby zobaczyć swój rabat i zamawiać taniej.', 'mnsk7-storefront' ); ?></p>
					<a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>" class="mnsk7-btn mnsk7-btn--primary"><?php esc_html_e( 'Moje konto / Zaloguj się', 'mnsk7-storefront' ); ?></a>
				<?php endif; ?>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- INSTAGRAM — embed.js ładujemy w footer (shortcode rejestruje skrypt), process() po load + retry -->
	<section class="mnsk7-section mnsk7-section--insta">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Obserwuj nas na Instagramie', 'mnsk7-storefront' ); ?></h2>
			<?php echo do_shortcode( '[mnsk7_instagram_feed limit="6" type="posts" title="Instagram @mnsk7tools"]' ); ?>
		</div>
	</section>

</main>

<?php
get_footer();
