<?php
/**
 * Footer — mnsk7-tools.pl (tech-storefront child theme)
 *
 * @package tech-storefront
 */
?>
<footer id="colophon" class="site-footer" itemscope itemtype="https://schema.org/WPFooter">

	<?php if ( function_exists( 'mnsk7_contact_info_html' ) ) : ?>
	<div class="mnsk7-site-footer-block">
		<div class="container">
			<div class="mnsk7-site-footer-block__grid">

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title"><?php esc_html_e( 'Kontakt', 'tech-storefront' ); ?></h4>
					<?php echo mnsk7_contact_info_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?>
				</div>

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title"><?php esc_html_e( 'Dostawa', 'tech-storefront' ); ?></h4>
					<?php
					if ( function_exists( 'mnsk7_dostawa_vat_html' ) ) {
						echo mnsk7_dostawa_vat_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
					}
					?>
					<p class="mnsk7-site-footer-block__delivery-note"><?php esc_html_e( 'Darmowa dostawa od 300 zł. Tylko Polska.', 'tech-storefront' ); ?></p>
				</div>

				<div class="mnsk7-site-footer-block__col">
					<h4 class="mnsk7-site-footer-block__col-title"><?php esc_html_e( 'Informacje', 'tech-storefront' ); ?></h4>
					<ul class="mnsk7-site-footer-block__links">
						<li><a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Sklep', 'tech-storefront' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>"><?php esc_html_e( 'Dostawa i platnosci', 'tech-storefront' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>"><?php esc_html_e( 'Kontakt', 'tech-storefront' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>"><?php esc_html_e( 'Regulamin', 'tech-storefront' ); ?></a></li>
						<li><a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>"><?php esc_html_e( 'Polityka prywatnosci', 'tech-storefront' ); ?></a></li>
					</ul>
					<p class="mnsk7-site-footer-block__social">
						<a href="<?php echo esc_url( defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/' ); ?>" target="_blank" rel="noopener" aria-label="Instagram">
							<?php esc_html_e( 'Instagram @mnsk7tools', 'tech-storefront' ); ?>
						</a>
					</p>
				</div>

			</div>
		</div>
	</div>
	<?php endif; ?>

	<div class="mnsk7-footer-bottom">
		<div class="container">
			<?php best_shop_get_footer_copyright(); ?>
		</div>
	</div>

</footer>
</div><!-- #page -->

<?php wp_footer(); ?>

</body>
</html>
