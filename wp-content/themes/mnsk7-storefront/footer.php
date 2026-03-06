<?php
/**
 * Footer — mnsk7-tools.pl (mnsk7-storefront child theme).
 * Closes exactly one wrapper: #content (opened in header.php). #page is closed after footer.
 *
 * @package mnsk7-storefront
 */
?>
	</div><!-- #content -->

<footer id="colophon" class="site-footer site-footer--mnsk7" role="contentinfo">

	<div class="mnsk7-site-footer-block">
		<div class="col-full">
			<div class="mnsk7-site-footer-block__grid">

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title">Kontakt</h4>
					<?php if ( function_exists( 'mnsk7_contact_info_html' ) ) { echo mnsk7_contact_info_html(); } else { ?>
					<p>Email: <a href="mailto:office@mnsk7.pl">office@mnsk7.pl</a></p>
					<p>Tel: <a href="tel:+48451696511">+48 451696511</a></p>
					<p>Pn.–pt. 9.00–17.00, sb. 10.00–12.00</p>
					<?php } ?>
				</div>

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title">Dostawa</h4>
					<?php if ( function_exists( 'mnsk7_dostawa_vat_html' ) ) echo mnsk7_dostawa_vat_html(); ?>
					<p class="mnsk7-site-footer-block__delivery-note">Darmowa dostawa od 300 zł. Tylko Polska.</p>
				</div>

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title">Informacje</h4>
					<ul class="mnsk7-site-footer-block__links">
						<li><a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>">Sklep</a></li>
						<li><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>">Dostawa i płatności</a></li>
						<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a></li>
						<li><a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>">Regulamin</a></li>
						<li><a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>">Polityka prywatności</a></li>
					</ul>
					<p class="mnsk7-site-footer-block__social">
						<a href="<?php echo esc_url( defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/' ); ?>" target="_blank" rel="noopener">Instagram @mnsk7tools</a>
					</p>
				</div>

			</div>
		</div>
	</div>

	<div class="mnsk7-footer-bottom">
		<div class="col-full">
			<span class="mnsk7-footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> mnsk7-tools.pl</span>
		</div>
	</div>

</footer>

</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
