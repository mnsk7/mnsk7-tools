<?php
/**
 * Footer — mnsk7-storefront (audit 2026-03).
 * Kolejność kolumn: Klient, Kategorie, Kontakt, Newsletter. Filtry: mnsk7_footer_legal_address, mnsk7_footer_contact.
 *
 * @package mnsk7-storefront
 */
defined( 'ABSPATH' ) || exit;

$footer_contact = apply_filters( 'mnsk7_footer_contact', array(
	'email'          => 'office@mnsk7.pl',
	'phone'          => '+48 451696511',
	'phone_href'     => 'tel:+48451696511',
	'hours_html'     => '<div class="mnsk7-footer__hours-row"><dt>pn.&nbsp;&ndash;&nbsp;pt.</dt><dd>9:00&nbsp;&ndash;&nbsp;17:00</dd></div><div class="mnsk7-footer__hours-row"><dt>sb.</dt><dd>10:00&nbsp;&ndash;&nbsp;12:00</dd></div><div class="mnsk7-footer__hours-row"><dt>nd.</dt><dd>' . esc_html__( 'zamknięte', 'mnsk7-storefront' ) . '</dd></div>',
	'instagram_url'  => defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/',
	'instagram_label'=> '@mnsk7tools',
) );
$top_cats = array();
if ( taxonomy_exists( 'product_cat' ) ) {
	$top_cats = get_terms( array( 'taxonomy' => 'product_cat', 'parent' => 0, 'hide_empty' => true ) );
	if ( is_wp_error( $top_cats ) ) {
		$top_cats = array();
	}
}
$dostawa_url = home_url( '/dostawa-i-platnosci/' );
$kontakt_url = home_url( '/kontakt/' );
$regulamin_zwroty_url = home_url( '/regulamin/#zwroty' );
?>
	</div><!-- #content -->

<div id="mnsk7-shipping-zone-notice-placeholder" class="mnsk7-shipping-zone-placeholder"></div>
<footer id="colophon" class="mnsk7-footer" role="contentinfo">
	<div class="mnsk7-footer__top">
		<div class="mnsk7-footer__inner">
			<div id="footer-col-klient" class="mnsk7-footer__col mnsk7-footer__col--client is-open" aria-label="<?php esc_attr_e( 'Linki dla klienta', 'mnsk7-storefront' ); ?>">
				<button type="button" class="mnsk7-footer__accordion-trigger" id="footer-trigger-klient" aria-expanded="true" aria-controls="footer-panel-klient">
					<span class="mnsk7-footer__accordion-title"><?php esc_html_e( 'Klient', 'mnsk7-storefront' ); ?></span>
					<span class="mnsk7-footer__accordion-icon" aria-hidden="true"></span>
				</button>
				<div id="footer-panel-klient" class="mnsk7-footer__accordion-panel" role="region" aria-labelledby="footer-trigger-klient">
				<ul class="mnsk7-footer__links">
					<li><a href="<?php echo esc_url( home_url( '/sklep/' ) ); ?>"><?php esc_html_e( 'Sklep', 'mnsk7-storefront' ); ?></a></li>
					<?php if ( function_exists( 'wc_get_page_permalink' ) ) { ?>
					<li><a href="<?php echo esc_url( wc_get_page_permalink( 'myaccount' ) ); ?>"><?php esc_html_e( 'Moje konto', 'mnsk7-storefront' ); ?></a></li>
					<?php } ?>
					<li><a href="<?php echo esc_url( $dostawa_url ); ?>"><?php esc_html_e( 'Dostawa i płatności', 'mnsk7-storefront' ); ?></a></li>
					<li><a href="<?php echo esc_url( $kontakt_url ); ?>"><?php esc_html_e( 'Kontakt', 'mnsk7-storefront' ); ?></a></li>
					<li><a href="<?php echo esc_url( $regulamin_zwroty_url ); ?>"><?php esc_html_e( 'Zwroty i reklamacje', 'mnsk7-storefront' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/regulamin/' ) ); ?>"><?php esc_html_e( 'Regulamin', 'mnsk7-storefront' ); ?></a></li>
					<li><a href="<?php echo esc_url( home_url( '/polityka-prywatnosci/' ) ); ?>"><?php esc_html_e( 'Polityka prywatności', 'mnsk7-storefront' ); ?></a></li>
				</ul>
				</div>
			</div>
			<div id="footer-col-kategorie" class="mnsk7-footer__col mnsk7-footer__col--kategorie" aria-label="<?php esc_attr_e( 'Kategorie produktów', 'mnsk7-storefront' ); ?>">
				<button type="button" class="mnsk7-footer__accordion-trigger" id="footer-trigger-kategorie" aria-expanded="false" aria-controls="footer-panel-kategorie">
					<span class="mnsk7-footer__accordion-title"><?php esc_html_e( 'Kategorie', 'mnsk7-storefront' ); ?></span>
					<span class="mnsk7-footer__accordion-icon" aria-hidden="true"></span>
				</button>
				<div id="footer-panel-kategorie" class="mnsk7-footer__accordion-panel" role="region" aria-labelledby="footer-trigger-kategorie">
				<ul class="mnsk7-footer__links">
					<?php
					foreach ( $top_cats as $term ) {
						$link = get_term_link( $term );
						if ( is_wp_error( $link ) ) { continue; }
						$name = function_exists( 'mnsk7_strip_wpf_filters_from_text' ) ? mnsk7_strip_wpf_filters_from_text( $term->name ) : $term->name;
						echo '<li><a href="' . esc_url( $link ) . '">' . esc_html( $name ) . '</a></li>';
					}
					?>
				</ul>
				</div>
			</div>
			<div id="footer-col-kontakt" class="mnsk7-footer__col mnsk7-footer__col--contact" aria-label="<?php esc_attr_e( 'Dane kontaktowe', 'mnsk7-storefront' ); ?>">
				<button type="button" class="mnsk7-footer__accordion-trigger" id="footer-trigger-kontakt" aria-expanded="false" aria-controls="footer-panel-kontakt">
					<span class="mnsk7-footer__accordion-title"><?php esc_html_e( 'Kontakt', 'mnsk7-storefront' ); ?></span>
					<span class="mnsk7-footer__accordion-icon" aria-hidden="true"></span>
				</button>
				<div id="footer-panel-kontakt" class="mnsk7-footer__accordion-panel" role="region" aria-labelledby="footer-trigger-kontakt">
				<?php
				$footer_address = apply_filters( 'mnsk7_footer_legal_address', '' );
				if ( $footer_address !== '' ) {
					echo '<p class="mnsk7-footer__address">' . wp_kses_post( $footer_address ) . '</p>';
				}
				?>
				<p class="mnsk7-footer__contact-page-link"><a href="<?php echo esc_url( $kontakt_url ); ?>"><?php esc_html_e( 'Formularz kontaktowy', 'mnsk7-storefront' ); ?></a></p>
				<ul class="mnsk7-footer__contact-list">
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--email" aria-hidden="true"></span>
						<a href="mailto:<?php echo esc_attr( $footer_contact['email'] ); ?>"><?php echo esc_html( $footer_contact['email'] ); ?></a>
					</li>
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--phone" aria-hidden="true"></span>
						<a href="<?php echo esc_attr( $footer_contact['phone_href'] ?? 'tel:' . preg_replace( '/\s+/', '', $footer_contact['phone'] ) ); ?>"><?php echo esc_html( $footer_contact['phone'] ); ?></a>
					</li>
					<li class="mnsk7-footer__contact-item--hours">
						<span class="mnsk7-footer__icon mnsk7-footer__icon--clock" aria-hidden="true"></span>
						<dl class="mnsk7-footer__hours" aria-label="<?php esc_attr_e( 'Godziny otwarcia', 'mnsk7-storefront' ); ?>">
							<?php echo wp_kses_post( $footer_contact['hours_html'] ?? '' ); ?>
						</dl>
					</li>
					<li>
						<span class="mnsk7-footer__icon mnsk7-footer__icon--instagram" aria-hidden="true"></span>
						<a href="<?php echo esc_url( $footer_contact['instagram_url'] ?? '#' ); ?>" target="_blank" rel="noopener"><?php echo esc_html( $footer_contact['instagram_label'] ?? '@mnsk7tools' ); ?></a>
					</li>
				</ul>
				</div>
			</div>
			<div id="footer-col-newsletter" class="mnsk7-footer__col mnsk7-footer__col--newsletter" aria-label="<?php esc_attr_e( 'Zapisz się do newslettera', 'mnsk7-storefront' ); ?>">
				<button type="button" class="mnsk7-footer__accordion-trigger" id="footer-trigger-newsletter" aria-expanded="false" aria-controls="footer-panel-newsletter">
					<span class="mnsk7-footer__accordion-title"><?php esc_html_e( 'Newsletter', 'mnsk7-storefront' ); ?></span>
					<span class="mnsk7-footer__accordion-icon" aria-hidden="true"></span>
				</button>
				<div id="footer-panel-newsletter" class="mnsk7-footer__accordion-panel" role="region" aria-labelledby="footer-trigger-newsletter">
				<?php /* W View Source: jeśli w środku jest ten komentarz i formularz, deploy footer.php jest OK. */ ?>
				<!-- mnsk7-footer newsletter -->
				<p class="mnsk7-footer__newsletter-desc"><?php esc_html_e( 'Otrzymuj informacje o promocjach, nowościach i poradach.', 'mnsk7-storefront' ); ?></p>
				<form class="mnsk7-footer__newsletter-form" action="<?php echo esc_url( home_url( '/' ) ); ?>" method="post" aria-label="<?php esc_attr_e( 'Zapisz się do newslettera', 'mnsk7-storefront' ); ?>">
					<?php wp_nonce_field( 'mnsk7_newsletter', 'mnsk7_newsletter_nonce' ); ?>
					<input type="hidden" name="mnsk7_newsletter" value="1" />
					<label for="mnsk7-newsletter-email" class="screen-reader-text"><?php esc_html_e( 'Adres e-mail', 'mnsk7-storefront' ); ?></label>
					<input type="email" id="mnsk7-newsletter-email" name="mnsk7_newsletter_email" placeholder="<?php esc_attr_e( 'Twój e-mail', 'mnsk7-storefront' ); ?>" required class="mnsk7-footer__newsletter-input" />
					<button type="submit" class="mnsk7-footer__newsletter-btn"><?php esc_html_e( 'Zapisz się', 'mnsk7-storefront' ); ?></button>
				</form>
				<p class="mnsk7-footer__newsletter-privacy"><?php esc_html_e( 'Możesz w każdej chwili wypisać się. Zobacz politykę prywatności.', 'mnsk7-storefront' ); ?></p>
				</div>
			</div>
		</div>
	</div>
	<div class="mnsk7-footer__bottom">
		<div class="mnsk7-footer__bottom-inner">
			<span class="mnsk7-footer__copy">&copy; <?php echo esc_html( date( 'Y' ) ); ?> mnsk7-tools.pl</span>
			<?php
			$cookie_url = get_privacy_policy_url() ? get_privacy_policy_url() . '#cookies' : home_url( '/polityka-prywatnosci/#cookies' );
			?>
			<a href="<?php echo esc_url( $cookie_url ); ?>" class="mnsk7-footer__cookie-link"><?php esc_html_e( 'Polityka cookie', 'mnsk7-storefront' ); ?></a>
		</div>
	</div>
</footer>

<?php /* Accordion: assets/js/footer-accordion.js (enqueue w functions.php). */ ?>

</div><!-- #page -->
<?php
$privacy_url = get_privacy_policy_url();
$cookie_settings_url = $privacy_url ? $privacy_url . '#cookies' : home_url( '/polityka-prywatnosci/#cookies' );
$show_theme_cookie_bar = apply_filters( 'mnsk7_show_cookie_bar', true );
$cookie_consent = function_exists( 'mnsk7_get_cookie_consent' ) ? mnsk7_get_cookie_consent() : null;
$show_cookie_bar_markup = $show_theme_cookie_bar && ( $cookie_consent !== 'accept' && $cookie_consent !== 'reject' );
if ( $show_cookie_bar_markup ) :
?>
<div id="mnsk7-cookie-bar" class="mnsk7-cookie-bar" hidden role="dialog" aria-label="<?php esc_attr_e( 'Informacja o plikach cookie', 'mnsk7-storefront' ); ?>" aria-hidden="true">
	<div class="mnsk7-cookie-bar__inner">
		<p class="mnsk7-cookie-bar__text">
			<?php esc_html_e( 'Ta strona używa plików cookie — niezbędnych do działania sklepu oraz opcjonalnych (analityka). Możesz zaakceptować wszystkie, odrzucić opcjonalne lub zobaczyć szczegóły w', 'mnsk7-storefront' ); ?>
			<?php if ( $privacy_url ) : ?>
				<a href="<?php echo esc_url( $privacy_url ); ?>"><?php esc_html_e( 'Polityce prywatności', 'mnsk7-storefront' ); ?></a>.
			<?php endif; ?>
		</p>
		<div class="mnsk7-cookie-bar__buttons">
			<button type="button" class="mnsk7-cookie-bar__btn mnsk7-cookie-bar-accept"><?php esc_html_e( 'Akceptuję wszystkie', 'mnsk7-storefront' ); ?></button>
			<button type="button" class="mnsk7-cookie-bar__btn mnsk7-cookie-bar-reject"><?php esc_html_e( 'Tylko niezbędne', 'mnsk7-storefront' ); ?></button>
			<a href="<?php echo esc_url( $cookie_settings_url ); ?>" class="mnsk7-cookie-bar__btn mnsk7-cookie-bar__btn--secondary"><?php esc_html_e( 'Ustawienia', 'mnsk7-storefront' ); ?></a>
		</div>
	</div>
</div>
<script>
(function() {
	function init() {
		var bar = document.getElementById('mnsk7-cookie-bar');
		if (!bar) return;
		var key = 'mnsk7_cookie_consent';
		function show() { bar.removeAttribute('hidden'); bar.setAttribute('aria-hidden', 'false'); document.body.classList.add('mnsk7-cookie-bar-visible'); var acceptBtn = bar.querySelector('.mnsk7-cookie-bar-accept'); if (acceptBtn) setTimeout(function() { acceptBtn.focus(); }, 100); }
		function hide() { bar.setAttribute('hidden', ''); bar.setAttribute('aria-hidden', 'true'); document.body.classList.remove('mnsk7-cookie-bar-visible'); }
		var valAccept = 'accept';
		var valReject = 'reject';
		function setConsent(value) {
			try { localStorage.setItem(key, value); } catch(e) {}
			try { document.cookie = key + '=' + encodeURIComponent(value) + '; path=/; max-age=31536000; SameSite=Lax'; } catch(e) {}
			window.mnsk7CookieConsent = value;
		}
		function getStored() {
			try { var s = localStorage.getItem(key); if (s) return s; } catch(e) {}
			var m = document.cookie.match(new RegExp('(?:^|; )' + key.replace(/([.*+?^${}()|[\]\\])/g, '\\$1') + '=([^;]*)'));
			return m ? decodeURIComponent(m[1].replace(/\+/g, ' ')) : null;
		}
		var stored = getStored();
		if (stored === valAccept || stored === valReject) {
			window.mnsk7CookieConsent = stored;
			hide();
			return;
		}
		var legacy = document.cookie.indexOf(key + '=1') !== -1 || (typeof localStorage !== 'undefined' && localStorage.getItem(key) === '1');
		if (legacy) { setConsent(valAccept); hide(); return; }
		show();
		window.mnsk7CookieConsent = null;
		function onChoice(e, value) {
			if (e) { e.preventDefault(); e.stopPropagation(); }
			setConsent(value);
			hide();
			document.dispatchEvent(new CustomEvent('mnsk7-cookie-consent', { detail: value }));
		}
		bar.addEventListener('click', function(e) {
			var target = e.target && e.target.closest ? e.target.closest('button') : null;
			if (!target) return;
			if (target.classList.contains('mnsk7-cookie-bar-accept')) { onChoice(e, valAccept); return; }
			if (target.classList.contains('mnsk7-cookie-bar-reject')) { onChoice(e, valReject); return; }
		}, false);
	}
	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
</script>
<?php endif; ?>
<?php wp_footer(); ?>
</body>
</html>
