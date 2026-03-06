<?php
/**
 * Template Name: SEO — Frezy do aluminium
 * Lądowanie SEO pod zapytanie "frezy do aluminium CNC".
 *
 * @package mnsk7-storefront
 */

get_header();
?>

<main class="mnsk7-seo-page">

	<section class="mnsk7-seo-hero">
		<div class="col-full">
			<h1 class="mnsk7-seo-hero__title">Frezy do aluminium CNC</h1>
			<p class="mnsk7-seo-hero__sub">VHM · DLC · 1P / 2P · dostawa następnego dnia · faktura VAT</p>
		</div>
	</section>

	<section class="mnsk7-seo-intro">
		<div class="col-full">
			<div class="mnsk7-seo-intro__text">
				<p>Aluminium jest jednym z najtrudniejszych materiałów do frezowania — tworzy długie wióry, nagrzewa się i skleja do krawędzi skrawającej. Wybór odpowiedniego frezu do aluminium decyduje o jakości powierzchni, trwałości narzędzia i wydajności obróbki.</p>

				<h2>Jakie frezy do aluminium wybrać?</h2>
				<p>Do frezowania aluminium najlepiej sprawdzają się <strong>frezy jednopiórowe (1P) z powłoką DLC</strong> lub <strong>frezy dwupiórowe (2P) z powłoką AlTiN / ZrN</strong>. Duże rowki na wiór umożliwiają szybkie odprowadzenie wióra i zapobiegają klejeniu się materiału.</p>
				<ul>
					<li><strong>Frez 1P (jednopiórowy)</strong> — maksymalnie duży rowek, do szybkiego usuwania materiału, cięcia na pełną głębokość; ideał dla obrabiarek CNC z dużą prędkością wrzeciona.</li>
					<li><strong>Frez 2P (dwupiórowy)</strong> — kompromis między wydajnością a wykończeniem; nadaje się do konturowania i frezowania kieszeni w aluminium.</li>
					<li><strong>Powłoka DLC</strong> (Diamond-Like Carbon) — bardzo niska przyczepność aluminium, gładka powierzchnia, długa żywotność przy obróbce Al.</li>
				</ul>

				<h2>Parametry obróbki aluminium</h2>
				<p>Zalecana prędkość obrotowa: <strong>18 000–30 000 RPM</strong> przy śr. 6 mm; posuw: <strong>1 500–4 000 mm/min</strong> — w zależności od głębokości skrawania i mocy wrzeciona. Stosuj chłodzenie powietrzem lub MQL (mgła olejowa).</p>
			</div>
		</div>
	</section>

	<section class="mnsk7-seo-products">
		<div class="col-full">
			<h2 class="mnsk7-seo-products__title">Frezy do aluminium — dostępne produkty</h2>
			<?php
			echo do_shortcode( '[products category="frezy-do-aluminium" limit="12" columns="4" orderby="popularity"]' );
			// Jeśli kategoria ma inny slug, zmień powyższy parametr category=""
			echo do_shortcode( '[products tag="aluminium" limit="12" columns="4" orderby="popularity"]' );
			?>
		</div>
	</section>

	<section class="mnsk7-seo-faq">
		<div class="col-full">
			<?php echo do_shortcode( '[mnsk7_faq set="produkt" title="Najczęściej zadawane pytania — frezy do aluminium"]' ); ?>
			<?php echo do_shortcode( '[mnsk7_faq set="dostawa"]' ); ?>
		</div>
	</section>

</main>

<?php get_footer(); ?>
