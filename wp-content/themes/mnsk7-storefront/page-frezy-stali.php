<?php
/**
 * Template Name: SEO — Frezy do stali i metalu
 * Lądowanie SEO pod zapytania "frezy do stali CNC", "frezy do metalu VHM".
 *
 * @package mnsk7-storefront
 */

get_header();
?>

<main class="mnsk7-seo-page">

	<section class="mnsk7-seo-hero">
		<div class="col-full">
			<h1 class="mnsk7-seo-hero__title">Frezy do stali i metalu — CNC</h1>
			<p class="mnsk7-seo-hero__sub">VHM HRC 65 · 4P · pilniki obrotowe · dostawa następnego dnia · faktura VAT</p>
		</div>
	</section>

	<section class="mnsk7-seo-intro">
		<div class="col-full">
			<div class="mnsk7-seo-intro__text">
				<p>Frezowanie stali wymaga narzędzi o wysokiej twardości, odporności na ścieranie i stabilności termicznej. Nasze frezy do stali i metalu wykonane są z pełnego twardego stopu (VHM) o twardości HRC 65, co gwarantuje długą żywotność nawet przy intensywnej obróbce.</p>

				<h2>Frezy palcowe 4P do stali</h2>
				<p>Frezy czteropióre (4P) zapewniają gładkie wykończenie powierzchni i stabilne skrawanie stali konstrukcyjnej, stopowej i nierdzewnej. Klucz to <strong>odpowiedni dobór prędkości i chłodzenia</strong> — zalecamy emulsję chłodzącą lub MQL:</p>
				<ul>
					<li><strong>Prędkość obrotowa:</strong> 3 000–8 000 RPM przy śr. 8–12 mm.</li>
					<li><strong>Posuw:</strong> 100–400 mm/min — w zależności od głębokości i materiału.</li>
					<li><strong>Głębokość skrawania:</strong> do 0,5×D osiowo (wykończenie) lub do 1×D przy małych posuwach.</li>
				</ul>

				<h2>Pilniki obrotowe do metalu (VHM)</h2>
				<p>Pilniki obrotowe (frezy trzpieniowe z nakarbowaniem) to narzędzia do ręcznego lub maszynowego szlifowania, gratowania i formowania metalu. Dostępne w typach A (cylindryczny), F (sferyczny), G i L. Idealne do finiszy trudno dostępnych krawędzi i rowków.</p>
			</div>
		</div>
	</section>

	<section class="mnsk7-seo-products">
		<div class="col-full">
			<h2 class="mnsk7-seo-products__title">Frezy do stali i metalu — produkty</h2>
			<?php
			echo do_shortcode( '[products category="frezy-do-stali" limit="12" columns="4" orderby="popularity"]' );
			echo do_shortcode( '[products tag="stal" limit="12" columns="4" orderby="popularity"]' );
			echo do_shortcode( '[products tag="metal" limit="12" columns="4" orderby="popularity"]' );
			?>
		</div>
	</section>

	<section class="mnsk7-seo-faq">
		<div class="col-full">
			<?php echo do_shortcode( '[mnsk7_faq set="produkt" title="FAQ — frezy do stali i metalu"]' ); ?>
			<?php echo do_shortcode( '[mnsk7_faq set="dostawa"]' ); ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
