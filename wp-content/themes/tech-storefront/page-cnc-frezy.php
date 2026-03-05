<?php
/**
 * Template Name: SEO — Frezy CNC (overview)
 * Lądowanie SEO pod zapytanie "frezy CNC", "narzędzia CNC sklep".
 *
 * @package tech-storefront
 */

get_header();
?>

<main class="mnsk7-seo-page">

	<section class="mnsk7-seo-hero">
		<div class="container">
			<h1 class="mnsk7-seo-hero__title">Frezy CNC — sklep internetowy</h1>
			<p class="mnsk7-seo-hero__sub">425+ produktów · VHM · dostawa następnego dnia · faktura VAT · 100% pozytywnych opinii</p>
		</div>
	</section>

	<section class="mnsk7-seo-intro">
		<div class="container">
			<div class="mnsk7-seo-intro__text">
				<p>MNK7 Tools to polski sklep specjalizujący się w <strong>frezach CNC i narzędziach skrawających</strong> do obróbki drewna, MDF, aluminium, stali i tworzyw sztucznych. W ofercie ponad 425 produktów — frezy palcowe, jednopiórowe, kulowe, kopiarkowe, pilniki obrotowe i zestawy frezów.</p>

				<h2>Co wyróżnia nasze frezy CNC?</h2>
				<ul>
					<li><strong>Pełny twardy stop VHM</strong> — wysoka trwałość, odporność na ścieranie i stabilność wymiarowa.</li>
					<li><strong>Twardość HRC 55–65</strong> — w zależności od zastosowania: aluminium, stal, drewno, tworzywa.</li>
					<li><strong>Powłoki DLC / AlTiN / ZrN</strong> — minimalizują tarcie, zwiększają żywotność i jakość powierzchni.</li>
					<li><strong>Szeroki zakres średnic</strong> — od śr. 3 mm do śr. 12 mm i więcej, trzpień 6 mm lub 8 mm.</li>
				</ul>

				<h2>Dla kogo są nasze produkty?</h2>
				<p>Frezy CNC z MNK7 Tools wybierają: stolarnie i producenci mebli (drewno, MDF), zakłady obróbki metali i aluminium, serwisy i warsztaty CNC, hobbystyczne frezarki CNC (CNC router, Shapeoko, X-Carve, MakerBot).</p>

				<h2>Frezy CNC według materiału obróbki</h2>
				<ul>
					<li><a href="<?php echo esc_url( home_url( '/frezy-do-aluminium/' ) ); ?>">Frezy do aluminium</a> — jednopiórowe, DLC, szybkie odprowadzenie wióra.</li>
					<li><a href="<?php echo esc_url( home_url( '/frezy-do-drewna-mdf/' ) ); ?>">Frezy do drewna i MDF</a> — spiralne, z podłożem, down-cut i up-cut.</li>
					<li><a href="<?php echo esc_url( home_url( '/frezy-do-stali/' ) ); ?>">Frezy do stali i metalu</a> — 4P VHM HRC 65, pilniki obrotowe.</li>
				</ul>
			</div>
		</div>
	</section>

	<section class="mnsk7-seo-products">
		<div class="container">
			<h2 class="mnsk7-seo-products__title">Bestsellery — najpopularniejsze frezy CNC</h2>
			<?php echo do_shortcode( '[mnsk7_bestsellers limit="8" title=""]' ); ?>
		</div>
	</section>

	<section class="mnsk7-seo-faq">
		<div class="container">
			<?php echo do_shortcode( '[mnsk7_faq title="FAQ — frezy CNC"]' ); ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
