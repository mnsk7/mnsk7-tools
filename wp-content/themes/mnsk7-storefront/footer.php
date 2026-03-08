<?php
/**
 * Footer — mnsk7-storefront. Kontakt z ikonami, Dostawa, Informacje + kategorie, dół z copyright.
 *
 * @package mnsk7-storefront
 */
defined( 'ABSPATH' ) || exit;

$instagram_url = defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/';
$top_cats = array();
if ( taxonomy_exists( 'product_cat' ) ) {
	$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true, 'number' => 8 ) );
	if ( is_wp_error( $top_cats ) ) {
		$top_cats = array();
	}
}
?>
	</div><!-- #content -->

<footer id="colophon" class="mnsk7-footer" role="contentinfo">
	<div class="mnsk7-footer__top">
		<div class="mnsk7-footer__inner">
			<div class="mnsk7-footer__col mnsk7-footer__col--newsletter">
				<h3 class="mnsk7-footer__title"><?php esc_html_e( 'Newsletter', 'mnsk7-storefront' ); ?></h3>
				<p class="mnsk7-footer__newsletter-desc"><?php esc_html_e( 'Otrzymuj informacje o promocjach, nowościach i poradach.', 'mnsk7-storefront' ); ?></p>
				<form class="mnsk7-footer__newsletter-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="post" aria-label="<?php esc_attr_e( 'Zapisz się do newslettera', 'mnsk7-storefront' ); ?>">
					<input type="hidden" name="mnsk7_newsletter" value="1" />
					<label for="mnsk7-newsletter-email" class="screen-reader-text"><?php esc_html_e( 'Adres e-mail', 'mnsk7-storefront' ); ?></label>
					<input type="email" id="mnsk7-newsletter-email" name="mnsk7_newsletter_email" placeholder="<?php esc_attr_e( 'Twój e-mail', 'mnsk7-storefront' ); ?>" required class="mnsk7-footer__newsletter-input" />
					<button type="submit" class="mnsk7-footer__newsletter-btn"><?php esc_html_e( 'Zapisz się', 'mnsk7-storefront' ); ?></button>
				</form>
			</div>
			<div class="mnsk7-footer__col mnsk7-footer__col--contact">
				<h3 class="mnsk7-footer__title">Kontakt</h3>
				<ul class="mnsk7-footer__contact-list">
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--email" aria-hidden="true"></span>
						<a href="mailto:office@mnsk7.pl">office@mnsk7.pl</a>
					</li>
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--phone" aria-hidden="true"></span>
						<a href="tel:+48451696511">+48 451696511</a>
					</li>
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--clock" aria-hidden="true"></span>
						<span>Pn.–pt. 9.00–17.00, sb. 10.00–12.00, nd. zamknięte</span>
					</li>
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--instagram" aria-hidden="true"></span>
						<a href="<?php echo esc_url( $instagram_url ); ?>" target="_blank" rel="noopener">@mnsk7tools</a>
					</li>
				</ul>
			</div>
			<div class="mnsk7-footer__col">
				<h3 class="mnsk7-footer__title">Dostawa</h3>
				<p>Dostawa następnego dnia. Faktura VAT dostępna na życzenie.</p>
				<p>Darmowa dostawa od 300 zł. Tylko Polska.</p>
			</div>
			<div class="mnsk7-footer__col mnsk7-footer__col--info">
				<h3 class="mnsk7-footer__title">Informacje</h3>
				<ul class="mnsk7-footer__links">
					<li><a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>">Sklep</a></li>
					<?php
					foreach ( $top_cats as $term ) {
						$link = get_term_link( $term );
						if ( is_wp_error( $link ) ) { continue; }
						$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
						echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></li>';
					}
					?>
					<li><a href="<?php echo esc_url( home_url( '/dostawa-i-platnosci/' ) ); ?>">Dostawa i płatności</a></li>
					<li><a href="<?php echo esc_url( home_url( '/kontakt/' ) ); ?>">Kontakt</a></li>
					<li><a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>">Regulamin</a></li>
					<li><a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>">Polityka prywatności</a></li>
				</ul>
			</div>
		</div>
	</div>
	<div class="mnsk7-footer__bottom">
		<div class="mnsk7-footer__bottom-inner">
			<span class="mnsk7-footer__copy">&copy; <?php echo esc_html( date( 'Y' ) ); ?> mnsk7-tools.pl</span>
		</div>
	</div>
</footer>

</div><!-- #page -->
<?php wp_footer(); ?>
</body>
</html>
