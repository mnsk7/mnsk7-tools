<?php
/**
 * Template Name: Kontakt
 * Strona z danymi kontaktowymi i formularzem (email, telefon, godziny, formularz wiadomości).
 *
 * @package mnsk7-storefront
 */

get_header();
?>

<main id="main" class="site-main mnsk7-page-kontakt">
	<div class="col-full">
		<?php
		while ( have_posts() ) :
			the_post();
			?>
			<article id="page-<?php the_ID(); ?>" <?php post_class(); ?>>
				<header class="entry-header">
					<?php the_title( '<h1 class="entry-title">', '</h1>' ); ?>
				</header>
				<div class="entry-content">
					<?php the_content(); ?>

					<div class="mnsk7-page-kontakt__grid">
						<div class="mnsk7-page-kontakt__col mnsk7-page-kontakt__col--info">
							<?php
							if ( function_exists( 'mnsk7_contact_info_html' ) ) {
								echo mnsk7_contact_info_html(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							}
							?>
						</div>
						<div class="mnsk7-page-kontakt__col mnsk7-page-kontakt__col--form">
							<div class="mnsk7-contact-form-wrapper">
								<h2 class="mnsk7-contact-form__title"><?php esc_html_e( 'Formularz kontaktowy', 'mnsk7-storefront' ); ?></h2>
								<p class="mnsk7-contact-form__desc"><?php esc_html_e( 'Napisz do nas — odpowiadamy w dni robocze.', 'mnsk7-storefront' ); ?></p>
								<form class="mnsk7-contact-form" action="<?php echo esc_url( get_permalink() ); ?>" method="post" aria-label="<?php esc_attr_e( 'Wyślij wiadomość', 'mnsk7-storefront' ); ?>">
									<?php wp_nonce_field( 'mnsk7_contact_form', 'mnsk7_contact_nonce' ); ?>
									<input type="hidden" name="mnsk7_contact_form" value="1" />
									<p class="mnsk7-contact-form__row">
										<label for="mnsk7-contact-name"><?php esc_html_e( 'Imię i nazwisko', 'mnsk7-storefront' ); ?> <span class="required">*</span></label>
										<input type="text" id="mnsk7-contact-name" name="mnsk7_contact_name" required class="mnsk7-contact-form__input" placeholder="<?php esc_attr_e( 'Jan Kowalski', 'mnsk7-storefront' ); ?>" value="<?php echo isset( $_POST['mnsk7_contact_name'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_name'] ) ) ) : ''; ?>" />
									</p>
									<p class="mnsk7-contact-form__row">
										<label for="mnsk7-contact-email"><?php esc_html_e( 'E-mail', 'mnsk7-storefront' ); ?> <span class="required">*</span></label>
										<input type="email" id="mnsk7-contact-email" name="mnsk7_contact_email" required class="mnsk7-contact-form__input" placeholder="<?php esc_attr_e( 'jan@example.pl', 'mnsk7-storefront' ); ?>" value="<?php echo isset( $_POST['mnsk7_contact_email'] ) ? esc_attr( sanitize_email( wp_unslash( $_POST['mnsk7_contact_email'] ) ) ) : ''; ?>" />
									</p>
									<p class="mnsk7-contact-form__row">
										<label for="mnsk7-contact-phone"><?php esc_html_e( 'Telefon', 'mnsk7-storefront' ); ?></label>
										<input type="tel" id="mnsk7-contact-phone" name="mnsk7_contact_phone" class="mnsk7-contact-form__input" placeholder="<?php esc_attr_e( '+48 123 456 789', 'mnsk7-storefront' ); ?>" value="<?php echo isset( $_POST['mnsk7_contact_phone'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_phone'] ) ) ) : ''; ?>" />
									</p>
									<p class="mnsk7-contact-form__row">
										<label for="mnsk7-contact-subject"><?php esc_html_e( 'Temat', 'mnsk7-storefront' ); ?></label>
										<input type="text" id="mnsk7-contact-subject" name="mnsk7_contact_subject" class="mnsk7-contact-form__input" placeholder="<?php esc_attr_e( 'np. zapytanie o produkt', 'mnsk7-storefront' ); ?>" value="<?php echo isset( $_POST['mnsk7_contact_subject'] ) ? esc_attr( sanitize_text_field( wp_unslash( $_POST['mnsk7_contact_subject'] ) ) ) : ''; ?>" />
									</p>
									<p class="mnsk7-contact-form__row mnsk7-contact-form__row--full">
										<label for="mnsk7-contact-message"><?php esc_html_e( 'Wiadomość', 'mnsk7-storefront' ); ?> <span class="required">*</span></label>
										<textarea id="mnsk7-contact-message" name="mnsk7_contact_message" required class="mnsk7-contact-form__input mnsk7-contact-form__textarea" rows="5" placeholder="<?php esc_attr_e( 'Opisz swoją sprawę…', 'mnsk7-storefront' ); ?>"><?php echo isset( $_POST['mnsk7_contact_message'] ) ? esc_textarea( wp_unslash( $_POST['mnsk7_contact_message'] ) ) : ''; ?></textarea>
									</p>
									<p class="mnsk7-contact-form__row mnsk7-contact-form__row--submit">
										<button type="submit" class="button mnsk7-contact-form__submit"><?php esc_html_e( 'Wyślij wiadomość', 'mnsk7-storefront' ); ?></button>
									</p>
								</form>
							</div>
						</div>
					</div>
				</div>
			</article>
			<?php
		endwhile;
		?>
	</div>
</main>

<?php
get_footer();
