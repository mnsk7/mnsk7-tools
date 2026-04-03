<?php
/**
 * Strona główna sklepu MNSK7 Tools.
 *
 * @package mnsk7-storefront
 */

get_header();
?>

<main id="main" class="site-main mnsk7-front-page">

	<!-- HERO -->
	<section class="mnsk7-hero">
		<div class="mnsk7-hero__inner col-full">
			<?php
			$materials = array( 'Drewno', 'MDF', 'Aluminium', 'Stal', __( 'Tworzywa sztuczne', 'mnsk7-storefront' ) );
			$hero_lines = array(
				array(
					'label' => __( 'Drewno i MDF', 'mnsk7-storefront' ),
					'text'  => __( 'frezy proste, spiralne, kompresyjne i grawerskie do czystej obróbki płyt i litego drewna', 'mnsk7-storefront' ),
				),
				array(
					'label' => __( 'Aluminium i tworzywa', 'mnsk7-storefront' ),
					'text'  => __( 'jednopiórowe i specjalistyczne geometrie pod szybkie odprowadzanie wióra i stabilne cięcie', 'mnsk7-storefront' ),
				),
				array(
					'label' => __( 'Warsztat i B2B', 'mnsk7-storefront' ),
					'text'  => __( 'faktura VAT, powtarzalne zakupy i oferta ułożona pod szybki wybór zamiast przypadkowego scrollowania', 'mnsk7-storefront' ),
				),
			);
			?>

			<div class="mnsk7-hero__split">
				<div class="mnsk7-hero__content">
					<p class="mnsk7-hero__eyebrow"><?php esc_html_e( 'Super Sprzedawca Allegro', 'mnsk7-storefront' ); ?></p>
					<h1 class="mnsk7-hero__title">
						<?php esc_html_e( 'Precyzja obróbki', 'mnsk7-storefront' ); ?>
						<span class="mnsk7-hero__title-accent"><?php esc_html_e( 'zaczyna się od frezu', 'mnsk7-storefront' ); ?></span>
					</h1>
					<p class="mnsk7-hero__lead"><?php esc_html_e( 'Profesjonalne frezy CNC i narzędzia skrawające do drewna, aluminium, tworzyw i stali. Oferta ułożona pod szybki wybór właściwej geometrii, średnicy i materiału.', 'mnsk7-storefront' ); ?></p>

					<div class="mnsk7-hero__stats" aria-label="<?php esc_attr_e( 'Najważniejsze informacje', 'mnsk7-storefront' ); ?>">
						<div class="mnsk7-hero__stat">
							<span class="mnsk7-hero__stat-value" data-mnsk7-counter>100%</span>
							<span class="mnsk7-hero__stat-label"><?php esc_html_e( 'pozytywnych opinii', 'mnsk7-storefront' ); ?></span>
						</div>
						<div class="mnsk7-hero__stat">
							<span class="mnsk7-hero__stat-value" data-mnsk7-counter>383</span>
							<span class="mnsk7-hero__stat-label"><?php esc_html_e( 'ocen na Allegro', 'mnsk7-storefront' ); ?></span>
						</div>
						<div class="mnsk7-hero__stat">
							<span class="mnsk7-hero__stat-value" data-mnsk7-counter>3 500+</span>
							<span class="mnsk7-hero__stat-label"><?php esc_html_e( 'zamówień w 2025 r.', 'mnsk7-storefront' ); ?></span>
						</div>
						<div class="mnsk7-hero__stat">
							<span class="mnsk7-hero__stat-value" data-mnsk7-counter>425</span>
							<span class="mnsk7-hero__stat-label"><?php esc_html_e( 'produktów w ofercie', 'mnsk7-storefront' ); ?></span>
						</div>
					</div>

					<div class="mnsk7-hero__materials" aria-label="<?php esc_attr_e( 'Materiały', 'mnsk7-storefront' ); ?>">
						<?php
						foreach ( $materials as $mat ) {
							echo '<span class="mnsk7-hero__material-chip">' . esc_html( $mat ) . '</span>';
						}
						?>
					</div>

					<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
					<div class="mnsk7-hero__ctas">
						<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="mnsk7-hero__btn mnsk7-hero__btn--primary">
							<?php esc_html_e( 'Przejdź do sklepu', 'mnsk7-storefront' ); ?>
						</a>
						<a href="#kategorie" class="mnsk7-hero__btn mnsk7-hero__btn--secondary">
							<?php esc_html_e( 'Zobacz kategorie', 'mnsk7-storefront' ); ?>
						</a>
					</div>
					<?php endif; ?>
				</div>

				<div class="mnsk7-hero__rail" aria-label="<?php esc_attr_e( 'Dlaczego MNSK7', 'mnsk7-storefront' ); ?>">
					<div class="mnsk7-hero__panel">
						<p class="mnsk7-hero__panel-kicker"><?php esc_html_e( 'Dlaczego warsztaty wybierają MNSK7', 'mnsk7-storefront' ); ?></p>
						<h2 class="mnsk7-hero__panel-title"><?php esc_html_e( 'Szybciej trafiasz do właściwego freza, bez błądzenia po przypadkowym katalogu', 'mnsk7-storefront' ); ?></h2>
						<ul class="mnsk7-hero__proof-list">
							<?php foreach ( $hero_lines as $line ) : ?>
							<li class="mnsk7-hero__proof-row">
								<span class="mnsk7-hero__proof-label"><?php echo esc_html( $line['label'] ); ?></span>
								<span class="mnsk7-hero__proof-text"><?php echo esc_html( $line['text'] ); ?></span>
							</li>
							<?php endforeach; ?>
						</ul>
						<div class="mnsk7-hero__panel-meta">
							<span class="mnsk7-hero__panel-chip"><?php esc_html_e( 'Kontakt i doradztwo', 'mnsk7-storefront' ); ?></span>
							<span class="mnsk7-hero__panel-chip"><?php esc_html_e( 'Szybka wysyłka 24h', 'mnsk7-storefront' ); ?></span>
						</div>
					</div>
				</div>
			</div>
		</div>
	</section>

	<!-- BESTSELLERS (CRO: drugi blok po hero — od razu produkt) -->
	<?php if ( function_exists( 'do_shortcode' ) ) : ?>
	<section id="bestsellery" class="mnsk7-section mnsk7-section--bestsellers">
		<div class="col-full">
			<div class="mnsk7-section__header mnsk7-section__header--bestsellers">
				<div class="mnsk7-section__header-copy">
					<p class="mnsk7-section__eyebrow"><?php esc_html_e( 'Bestsellery i polecane', 'mnsk7-storefront' ); ?></p>
					<h2 class="mnsk7-section__title"><?php esc_html_e( 'Najczęściej wybierane frezy przez naszych klientów', 'mnsk7-storefront' ); ?></h2>
					<p class="mnsk7-section__sub"><?php esc_html_e( 'Sprawdzone pozycje do codziennej pracy: drewno, MDF, aluminium, stal i tworzywa w najczęściej zamawianych wariantach.', 'mnsk7-storefront' ); ?></p>
				</div>
				<p class="mnsk7-section__more mnsk7-bestsellers-more">
					<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? add_query_arg( 'orderby', 'popularity', wc_get_page_permalink( 'shop' ) ) : home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Zobacz wszystkie →', 'mnsk7-storefront' ); ?></a>
				</p>
			</div>
			<?php echo do_shortcode( '[mnsk7_bestsellers limit="6" title=""]' ); ?>
		</div>
	</section>
	<?php endif; ?>

	<!-- TRUST + OPINIE (CRO: trzeci blok — zaufanie przed głębszym katalogiem) -->
	<section class="mnsk7-section mnsk7-section--trust mnsk7-section--light">
		<div class="col-full">
			<p class="mnsk7-section__eyebrow"><?php esc_html_e( 'Zaufanie i obsługa', 'mnsk7-storefront' ); ?></p>
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Dlaczego kupujący nam ufają', 'mnsk7-storefront' ); ?></h2>
			<p class="mnsk7-section__sub"><?php esc_html_e( 'Łączymy specjalistyczny asortyment CNC z przewidywalną realizacją, realnymi opiniami i obsługą pod zakupy warsztatowe oraz B2B.', 'mnsk7-storefront' ); ?></p>
			<div class="mnsk7-trust-stats">
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number" data-mnsk7-counter>100%</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'pozytywnych opinii', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number" data-mnsk7-counter>383</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'ocen na Allegro', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number" data-mnsk7-counter>3 500+</span>
					<span class="mnsk7-trust-stats__label"><?php esc_html_e( 'zamówień w 2025 r.', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-trust-stats__item">
					<span class="mnsk7-trust-stats__number" data-mnsk7-counter>425</span>
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
	$accessory_cats = array();
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
		if ( ! is_wp_error( $cats ) && function_exists( 'mnsk7_split_catalog_category_terms' ) ) {
			$grouped = mnsk7_split_catalog_category_terms( $cats );
			$cats = isset( $grouped['core'] ) ? $grouped['core'] : array();
			$accessory_cats = isset( $grouped['accessories'] ) ? $grouped['accessories'] : array();
		}
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
	$accessories_label = apply_filters( 'mnsk7_megamenu_heading_accessories', __( 'Akcesoria i zestawy', 'mnsk7-storefront' ) );
	$show_catalog = ( $has_cats && ! is_wp_error( $cats ) && ! empty( $cats ) ) || ( $has_tags && ! is_wp_error( $tags ) && ! empty( $tags ) );
	if ( $show_catalog ) :
	?>
	<section id="kategorie" class="mnsk7-section mnsk7-section--catalog mnsk7-section--light">
		<div class="col-full">
			<p class="mnsk7-section__eyebrow"><?php esc_html_e( 'Kategorie i materiały', 'mnsk7-storefront' ); ?></p>
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Przeglądaj asortyment', 'mnsk7-storefront' ); ?></h2>
			<p class="mnsk7-section__sub"><?php esc_html_e( 'Wejdź od materiału albo od rodzaju freza. Struktura katalogu prowadzi do właściwej grupy produktów zamiast zmuszać do przypadkowego przeszukiwania sklepu.', 'mnsk7-storefront' ); ?></p>

			<?php if ( $has_tags && ! is_wp_error( $tags ) && ! empty( $tags ) ) : ?>
			<div class="mnsk7-catalog-aside mnsk7-catalog-aside--tags" role="navigation" aria-label="<?php echo esc_attr( $tags_label ); ?>">
				<h3 class="mnsk7-catalog-aside__title"><?php echo esc_html( $tags_label ); ?></h3>
				<div class="mnsk7-catalog-chips__scroll mnsk7-catalog-chips__scroll--cloud">
					<?php foreach ( $tags as $tag ) :
						$t_link = get_term_link( $tag );
						if ( is_wp_error( $t_link ) ) continue;
						$tag_name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $tag->name ) : $tag->name;
						$tag_name = trim( preg_replace( '/\s*mnsk7-tools\.pl\s*/i', '', (string) $tag_name ) );
						$tag_name = function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $tag_name ) : $tag_name;
					?>
					<a href="<?php echo esc_url( $t_link ); ?>" class="mnsk7-tags-chip"><?php echo esc_html( $tag_name ); ?></a>
					<?php endforeach; ?>
				</div>
			</div>
			<?php endif; ?>

			<?php if ( ! empty( $accessory_cats ) ) : ?>
			<div class="mnsk7-catalog-aside mnsk7-catalog-aside--tags" role="navigation" aria-label="<?php echo esc_attr( $accessories_label ); ?>">
				<h3 class="mnsk7-catalog-aside__title"><?php echo esc_html( $accessories_label ); ?></h3>
				<div class="mnsk7-catalog-chips__scroll mnsk7-catalog-chips__scroll--cloud">
					<?php foreach ( $accessory_cats as $cat ) :
						$link = get_term_link( $cat );
						if ( is_wp_error( $link ) ) continue;
						$cat_name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $cat->name ) : $cat->name;
						$cat_name = function_exists( 'mnsk7_normalize_catalog_term_label' ) ? mnsk7_normalize_catalog_term_label( $cat_name ) : $cat_name;
					?>
					<a href="<?php echo esc_url( $link ); ?>" class="mnsk7-tags-chip"><?php echo esc_html( $cat_name ); ?></a>
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
			<p class="mnsk7-section__eyebrow"><?php esc_html_e( 'Stała współpraca', 'mnsk7-storefront' ); ?></p>
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Program rabatowy dla stałych klientów', 'mnsk7-storefront' ); ?></h2>
			<p class="mnsk7-section__sub mnsk7-loyalty-intro"><?php esc_html_e( 'Im więcej zamawiasz w ciągu roku, tym większy stały rabat na każde kolejne zamówienie i bardziej przewidywalne koszty zakupów.', 'mnsk7-storefront' ); ?></p>
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
					<span class="mnsk7-loyalty-tier__pct" data-mnsk7-counter><?php echo esc_html( $tier['pct'] ); ?></span>
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

	<!-- INSTAGRAM -->
	<section class="mnsk7-section mnsk7-section--insta">
		<div class="col-full">
			<p class="mnsk7-section__eyebrow"><?php esc_html_e( 'Marka w praktyce', 'mnsk7-storefront' ); ?></p>
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Najnowsze posty z Instagrama', 'mnsk7-storefront' ); ?></h2>
			<p class="mnsk7-section__sub"><?php esc_html_e( 'Nowości, realizacje i materiały z kanału @mnsk7tools w normalnej siatce, bez rozwalania układu strony.', 'mnsk7-storefront' ); ?></p>
			<?php
			echo do_shortcode( '[mnsk7_instagram_feed type="posts" limit="4" title="Instagram @mnsk7tools" urls="https://www.instagram.com/mnsk7tools/p/DCTybzqtxEi/,https://www.instagram.com/mnsk7tools/p/DCeUnS8Ismh/,https://www.instagram.com/mnsk7tools/p/DCzOqKqtjUe/,https://www.instagram.com/mnsk7tools/p/DC9J3JjNobj/" images="https://scontent-waw2-2.cdninstagram.com/v/t51.82787-15/618838310_17958807636028695_553601329369762955_n.jpg?stp=c216.0.648.648a_dst-jpg_e35_s640x640_tt6&amp;_nc_cat=105&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiRkVFRC5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=IUXh4HFzmfwQ7kNvwFhBPPA&amp;_nc_oc=AdrUhxsMTJRY5jA5AlC8CHFLilhfRQZM1cWukGQLqU6VQoSrm1-LRgr_cgjn4Al0eHQ&amp;_nc_zt=23&amp;_nc_ht=scontent-waw2-2.cdninstagram.com&amp;_nc_gid=IViln7Zdil6dHgb41JbAYQ&amp;_nc_ss=7a389&amp;oh=00_Af3sK9Kq5R7AzkxhZVPehW3tCVw8no-qDa7YKLFcYp8r8Q&amp;oe=69D4733A,https://scontent-waw2-1.cdninstagram.com/v/t39.30808-6/467187685_122106145898613748_7017910040251271378_n.jpg?stp=c216.0.648.648a_dst-jpg_e35_s640x640_tt6&amp;_nc_cat=109&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=G5EOZG4vr60Q7kNvwG9N57B&amp;_nc_oc=Adq3bXNTEgR91r8phG3y-Ozgdcz8cirWRPNg0D0-u2d2XXPqATUAf2PcIU_5nNNrk0k&amp;_nc_zt=23&amp;_nc_ht=scontent-waw2-1.cdninstagram.com&amp;_nc_gid=v72drcEWJHE27TP5KijVqA&amp;_nc_ss=7a389&amp;oh=00_Af3RK_2T8pFEp2Nl3FQNuxo2asfMLgHkKYOuqyNob5lsKA&amp;oe=69D4825C,https://scontent-waw2-1.cdninstagram.com/v/t51.75761-15/467779881_17847371727357860_1861874929847902807_n.jpg?stp=c216.0.648.648a_dst-jpg_e35_s640x640_tt6&amp;_nc_cat=104&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiQ0FST1VTRUxfSVRFTS5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=al7c1C8wYQgQ7kNvwERLH6S&amp;_nc_oc=AdpS3lqTvVGGHz_k_ceeHNOG5MF0-weEmhBf4Tkt0oFm3bJ3GhB9qTmwF1tplDL3kig&amp;_nc_zt=23&amp;_nc_ht=scontent-waw2-1.cdninstagram.com&amp;_nc_gid=jjBUYev877dvo_json7m3g&amp;_nc_ss=7a389&amp;oh=00_Af3Is290mBlUPEj-VQhnkn5b5wYdnEO0GYX-v1QH-4Q1lA&amp;oe=69D499B8,https://scontent-waw2-2.cdninstagram.com/v/t51.82787-15/624277716_18111854674567315_1589126126157759200_n.jpg?stp=c216.0.648.648a_dst-jpg_e35_s640x640_tt6&amp;_nc_cat=105&amp;ccb=7-5&amp;_nc_sid=18de74&amp;efg=eyJlZmdfdGFnIjoiRkVFRC5iZXN0X2ltYWdlX3VybGdlbi5DMyJ9&amp;_nc_ohc=eIHTAtIw6CUQ7kNvwF2oMQu&amp;_nc_oc=Adq3DsIxJA0m3BHRF3uCkqqqVk37qfXsUT_2APRDibLdZsyW07SnsPH12Wm9t4WWsgg&amp;_nc_zt=23&amp;_nc_ht=scontent-waw2-2.cdninstagram.com&amp;_nc_gid=G-J1-hCvYaXNCpYqRr6NlA&amp;_nc_ss=7a389&amp;oh=00_Af0o0xy4p_8nIBOyQ-frkkuW5wKPIu4Ci8kaxoKfkELj8w&amp;oe=69D481A5"]' );
			?>
		</div>
	</section>

</main>

<?php
get_footer();
