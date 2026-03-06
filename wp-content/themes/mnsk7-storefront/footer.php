<?php
/**
 * Footer — mnsk7-storefront. Prosty ciemny stop, cały tekst jasny.
 *
 * @package mnsk7-storefront
 */
defined( 'ABSPATH' ) || exit;
?>
	</div><!-- #content -->

<footer id="colophon" class="mnsk7-footer" role="contentinfo">
	<div class="mnsk7-footer__top">
		<div class="mnsk7-footer__inner">
			<div class="mnsk7-footer__col">
				<h3 class="mnsk7-footer__title">Kontakt</h3>
				<p>Email: <a href="mailto:office@mnsk7.pl">office@mnsk7.pl</a></p>
				<p>Tel: <a href="tel:+48451696511">+48 451696511</a></p>
				<p>Pn.–pt. 9.00–17.00, sb. 10.00–12.00</p>
				<p>Instagram: <a href="<?php echo esc_url( defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/' ); ?>" target="_blank" rel="noopener">@mnsk7tools</a></p>
			</div>
			<div class="mnsk7-footer__col">
				<h3 class="mnsk7-footer__title">Dostawa</h3>
				<p>Dostawa następnego dnia. Faktura VAT dostępna na życzenie.</p>
				<p>Darmowa dostawa od 300 zł. Tylko Polska.</p>
			</div>
			<div class="mnsk7-footer__col">
				<h3 class="mnsk7-footer__title">Informacje</h3>
				<ul class="mnsk7-footer__links">
					<li><a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>">Sklep</a></li>
					<li><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>">Dostawa i płatności</a></li>
					<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a></li>
					<li><a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>">Regulamin</a></li>
					<li><a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>">Polityka prywatności</a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="mnsk7-footer__bottom">
		<div class="mnsk7-footer__inner">
			<span class="mnsk7-footer__copy">&copy; <?php echo esc_html( date( 'Y' ) ); ?> mnsk7-tools.pl</span>
		</div>
	</div>
</footer>

</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
