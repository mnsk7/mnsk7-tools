<?php
/**
 * Footer -- mnsk7-tools.pl (mnsk7-storefront child theme)
 *
 * @package mnsk7-storefront
 */
?>

	</div>
</div>

<footer id="colophon" class="site-footer" role="contentinfo">

	<?php if ( function_exists( 'mnsk7_contact_info_html' ) ) : ?>
	<div class="mnsk7-site-footer-block">
		<div class="col-full">
			<div class="mnsk7-site-footer-block__grid">

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title">Kontakt</h4>
					<?php echo mnsk7_contact_info_html(); ?>
				</div>

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title">Dostawa</h4>
					<?php if ( function_exists( 'mnsk7_dostawa_vat_html' ) ) echo mnsk7_dostawa_vat_html(); ?>
					<p class="mnsk7-site-footer-block__delivery-note">Darmowa dostawa od 300 zl. Tylko Polska.</p>
				</div>

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title">Informacje</h4>
					<ul class="mnsk7-site-footer-block__links">
						<li><a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>">Sklep</a></li>
						<li><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>">Dostawa i platnosci</a></li>
						<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a></li>
						<li><a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>">Regulamin</a></li>
						<li><a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>">Polityka prywatnosci</a></li>
					</ul>
					<p class="mnsk7-site-footer-block__social">
						<a href="<?php echo esc_url( defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/' ); ?>" target="_blank" rel="noopener">Instagram @mnsk7tools</a>
					</p>
				</div>

			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="mnsk7-footer-bottom">
		<div class="col-full">
			<span class="mnsk7-footer-copyright">&copy; <?php echo esc_html( date( 'Y' ) ); ?> mnsk7-tools.pl</span>
		</div>
	</div>

</footer>

</div>

<?php wp_footer(); ?>

</body>
</html>
