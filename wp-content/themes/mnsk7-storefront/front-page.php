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
			<h1 class="mnsk7-hero__title"><?php esc_html_e( 'Frezy CNC i narzędzia skrawające', 'mnsk7-storefront' ); ?></h1>
			<p class="mnsk7-hero__sub"><?php esc_html_e( 'Drewno · MDF · Aluminium · Stal · Tworzywa sztuczne', 'mnsk7-storefront' ); ?></p>
			<div class="mnsk7-hero__usps">
				<div class="mnsk7-hero__usp">
					<span class="mnsk7-hero__usp-icon" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Darmowa dostawa od 300 zł', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-hero__usp">
					<span class="mnsk7-hero__usp-icon" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Dostawa następnego dnia', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-hero__usp">
					<span class="mnsk7-hero__usp-icon" aria-hidden="true"></span>
					<span><?php esc_html_e( '100% pozytywnych opinii', 'mnsk7-storefront' ); ?></span>
				</div>
				<div class="mnsk7-hero__usp">
					<span class="mnsk7-hero__usp-icon" aria-hidden="true"></span>
					<span><?php esc_html_e( 'Faktura VAT', 'mnsk7-storefront' ); ?></span>
				</div>
			</div>
			<?php if ( is_user_logged_in() ) : $u = wp_get_current_user(); ?>
			<p class="mnsk7-hero__welcome"><?php printf( esc_html__( 'Witaj, %s!', 'mnsk7-storefront' ), esc_html( $u->display_name ?: $u->user_login ) ); ?></p>
			<?php endif; ?>
			<?php if ( function_exists( 'wc_get_page_permalink' ) ) : ?>
			<div class="mnsk7-hero__ctas">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>" class="mnsk7-hero__btn mnsk7-hero__btn--primary">
					<?php esc_html_e( 'Przejdź do sklepu', 'mnsk7-storefront' ); ?>
				</a>
			</div>
			<?php endif; ?>
		</div>
	</section>

	<!-- BESTSELLERS (CRO: drugi blok po hero — od razu produkt) -->
	<?php if ( function_exists( 'do_shortcode' ) ) : ?>
	<section class="mnsk7-section mnsk7-section--bestsellers">
		<div class="col-full">
			<?php echo do_shortcode( '[mnsk7_bestsellers limit="6" title="Bestsellery i polecane"]' ); ?>
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
			<div class="mnsk7-trust-cta">
				<a href="<?php echo esc_url( $allegro_url ); ?>" class="mnsk7-trust-cta__btn" target="_blank" rel="noopener nofollow">
					<?php esc_html_e( 'Zobacz profil i opinie na Allegro →', 'mnsk7-storefront' ); ?>
				</a>
			</div>
		</div>
	</section>

	<!-- KATEGORIE (CRO: po trust — wybór kategorii) -->
	<?php if ( taxonomy_exists( 'product_cat' ) ) :
		$cats = get_terms( array(
			'taxonomy'   => 'product_cat',
			'hide_empty' => true,
			'parent'     => 0,
			'number'     => 12,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );
		$quick_slugs = array( 'frez-spiralny', 'frezy-do-drewna-mdf', 'frezy-do-aluminium', 'frezy-do-stali', 'frezy-do-plastiku' );
		$quick_links = array();
		foreach ( $quick_slugs as $slug ) {
			$t = get_term_by( 'slug', $slug, 'product_cat' );
			if ( $t && ! is_wp_error( get_term_link( $t ) ) ) {
				$quick_links[] = $t;
			}
		}
		if ( empty( $quick_links ) && ! is_wp_error( $cats ) && ! empty( $cats ) ) {
			$quick_links = array_slice( $cats, 0, 5 );
		}
		if ( ! is_wp_error( $cats ) && ! empty( $cats ) ) :
	?>
	<section class="mnsk7-section mnsk7-section--cats mnsk7-section--light">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Kategorie', 'mnsk7-storefront' ); ?></h2>
			<?php if ( ! empty( $quick_links ) ) : ?>
			<div class="mnsk7-cats-quick">
				<span class="mnsk7-cats-quick__label"><?php esc_html_e( 'Przeglądaj:', 'mnsk7-storefront' ); ?></span>
				<?php foreach ( $quick_links as $q ) :
					$q_link = get_term_link( $q );
					if ( is_wp_error( $q_link ) ) continue;
				?>
				<a href="<?php echo esc_url( $q_link ); ?>" class="mnsk7-cats-quick__chip"><?php echo esc_html( $q->name ); ?></a>
				<?php endforeach; ?>
			</div>
			<?php endif; ?>
			<div class="mnsk7-cats mnsk7-cats--catalog">
				<?php foreach ( $cats as $cat ) :
					$link = get_term_link( $cat );
					if ( is_wp_error( $link ) ) continue;
					$img_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
					$img    = $img_id ? wp_get_attachment_image( $img_id, 'medium' ) : '';
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
							<span class="mnsk7-cats__name"><?php echo esc_html( $cat->name ); ?></span>
							<span class="mnsk7-cats__count"><?php echo esc_html( $cat->count ); ?> <?php esc_html_e( 'prod.', 'mnsk7-storefront' ); ?></span>
						</span>
						<span class="mnsk7-cats__arrow" aria-hidden="true">→</span>
					</a>
				<?php endforeach; ?>
			</div>
			<p class="mnsk7-section__more">
				<a href="<?php echo esc_url( wc_get_page_permalink( 'shop' ) ); ?>"><?php esc_html_e( 'Wszystkie produkty →', 'mnsk7-storefront' ); ?></a>
			</p>
		</div>
	</section>
	<?php endif; endif; ?>

	<!-- TAGI PRODUKTÓW (nawigacja do stron /tag-produktu/...) -->
	<?php
	if ( taxonomy_exists( 'product_tag' ) ) {
		$tags = get_terms( array(
			'taxonomy'   => 'product_tag',
			'hide_empty' => true,
			'number'     => 16,
			'orderby'    => 'count',
			'order'      => 'DESC',
		) );
		if ( ! is_wp_error( $tags ) && ! empty( $tags ) ) :
	?>
	<section class="mnsk7-section mnsk7-section--tags mnsk7-section--light">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Tagi produktów', 'mnsk7-storefront' ); ?></h2>
			<p class="mnsk7-section__sub mnsk7-tags-intro"><?php esc_html_e( 'Przeglądaj asortyment według tagów — np. typ frezu, liczba ostrzy.', 'mnsk7-storefront' ); ?></p>
			<div class="mnsk7-tags-chips">
				<?php foreach ( $tags as $tag ) :
					$t_link = get_term_link( $tag );
					if ( is_wp_error( $t_link ) ) continue;
				?>
				<a href="<?php echo esc_url( $t_link ); ?>" class="mnsk7-tags-chip"><?php echo esc_html( $tag->name ); ?></a>
				<?php endforeach; ?>
			</div>
			<p class="mnsk7-section__more">
				<a href="<?php echo esc_url( function_exists( 'wc_get_page_permalink' ) ? wc_get_page_permalink( 'shop' ) : home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Wszystkie produkty →', 'mnsk7-storefront' ); ?></a>
			</p>
		</div>
	</section>
	<?php
		endif;
	}
	?>

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

	<!-- INSTAGRAM — skrypt od razu za blockquote, żeby pluginy nie odkładały go do footer i embedy się narysowały -->
	<section class="mnsk7-section mnsk7-section--insta">
		<div class="col-full">
			<h2 class="mnsk7-section__title"><?php esc_html_e( 'Obserwuj nas na Instagramie', 'mnsk7-storefront' ); ?></h2>
			<?php echo do_shortcode( '[mnsk7_instagram_feed limit="6" title="Instagram @mnsk7tools"]' ); ?>
			<script src="https://www.instagram.com/embed.js"></script>
			<script>
			(function(){
				function run(){ if(window.instgrm&&window.instgrm.Embeds) window.instgrm.Embeds.process(); }
				run();
				if(document.readyState!=='complete') window.addEventListener('load',run);
			})();
			</script>
		</div>
	</section>

</main>

<?php
get_footer();
