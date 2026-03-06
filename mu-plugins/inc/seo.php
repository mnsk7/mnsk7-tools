<?php
/**
 * MNK7 Tools — SEO: Organization schema, auto-alt, Yoast meta, opis kategorii.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/* Organization + OnlineStore JSON-LD */
add_action( 'wp_head', function () {
	$schema = array(
		'@context'     => 'https://schema.org',
		'@type'        => array( 'Organization', 'OnlineStore' ),
		'name'         => 'MNK7 Tools',
		'legalName'    => 'MNSK7 SPÓŁKA Z OGRANICZONĄ ODPOWIEDZIALNOŚCIĄ',
		'url'          => home_url( '/' ),
		'logo'         => array( '@type' => 'ImageObject', 'url' => get_site_icon_url( 512 ) ?: home_url( '/wp-content/themes/tech-storefront/assets/images/logo.png' ) ),
		'contactPoint' => array(
			'@type' => 'ContactPoint', 'telephone' => MNK7_CONTACT_PHONE,
			'email' => MNK7_CONTACT_EMAIL, 'contactType' => 'customer service',
			'availableLanguage' => array( 'Polish' ),
			'hoursAvailable' => array(
				array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => array( 'Monday', 'Tuesday', 'Wednesday', 'Thursday', 'Friday' ), 'opens' => '09:00', 'closes' => '17:00' ),
				array( '@type' => 'OpeningHoursSpecification', 'dayOfWeek' => 'Saturday', 'opens' => '10:00', 'closes' => '12:00' ),
			),
		),
		'address'    => array( '@type' => 'PostalAddress', 'streetAddress' => 'ul. Williama Heerleina Lindleya 16/512', 'addressLocality' => 'Warszawa', 'postalCode' => '02-013', 'addressCountry' => 'PL' ),
		'vatID'      => 'PL5242991741',
		'taxID'      => '5242991741',
		'sameAs'     => array( MNK7_INSTAGRAM_URL, MNK7_ALLEGRO_SELLER_URL ),
		'areaServed' => array( '@type' => 'Country', 'name' => 'Poland' ),
		'description' => __( 'Sklep z frezami CNC i narzędziami do obróbki drewna, MDF, aluminium, stali i tworzyw sztucznych. Dostawa następnego dnia, faktura VAT.', 'mnsk7-tools' ),
	);
	echo '<script type="application/ld+json">' . wp_json_encode( $schema, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES ) . '</script>' . "\n"; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
}, 5 );

/* Auto-alt dla zdjęć produktów (~1634 bez alt w bazie stagingg) */
add_filter( 'wp_get_attachment_image_attributes', function ( $attr, $attachment ) {
	if ( ! empty( $attr['alt'] ) ) {
		return $attr;
	}
	$alt = trim( (string) get_post_meta( $attachment->ID, '_wp_attachment_image_alt', true ) );
	if ( $alt !== '' ) {
		$attr['alt'] = $alt;
		return $attr;
	}
	$parent_id = (int) $attachment->post_parent;
	if ( $parent_id > 0 ) {
		$parent = get_post( $parent_id );
		if ( $parent ) {
			$sku = $parent->post_type === 'product' ? get_post_meta( $parent_id, '_sku', true ) : '';
			$attr['alt'] = trim( $parent->post_title . ( $sku ? ' | ' . $sku : '' ) );
			return $attr;
		}
	}
	if ( ! empty( $attachment->post_title ) ) {
		$attr['alt'] = $attachment->post_title;
	}
	return $attr;
}, 20, 2 );

/* Yoast: auto meta description dla produktów */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ! empty( $desc ) || ! is_singular( 'product' ) ) {
		return $desc;
	}
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		$product = wc_get_product( get_the_ID() );
	}
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return $desc;
	}
	$parts = array( $product->get_name() );
	$sred  = $product->get_attribute( 'srednica' ) ?: $product->get_attribute( 'pa_srednica' );
	$zast  = $product->get_attribute( 'zastosowanie' ) ?: $product->get_attribute( 'pa_zastosowanie' );
	if ( $sred ) $parts[] = '| Ø' . $sred;
	if ( $zast ) $parts[] = '| ' . $zast;
	return implode( ' ', $parts ) . ' — ' . __( 'Dostawa następnego dnia. Faktura VAT. Zamów na mnsk7-tools.pl.', 'mnsk7-tools' );
}, 20 );

/* Yoast: auto meta description dla kategorii */
add_filter( 'wpseo_metadesc', function ( $desc ) {
	if ( ! empty( $desc ) || ! is_product_category() ) {
		return $desc;
	}
	$cat = get_queried_object();
	if ( ! $cat ) return $desc;
	return sprintf(
		__( '%1$s — %2$d produktów. Dostawa następnego dnia. Faktura VAT. Sklep mnsk7-tools.pl — frezy CNC i narzędzia skrawające.', 'mnsk7-tools' ),
		$cat->name, (int) $cat->count
	);
}, 21 );

/* Opis kategorii — zastępuje domyślny WooCommerce hook */
remove_action( 'woocommerce_archive_description', 'woocommerce_taxonomy_archive_description', 10 );
add_action( 'woocommerce_archive_description', function () {
	if ( ! is_product_category() ) return;
	$cat = get_queried_object();
	if ( ! $cat instanceof WP_Term ) return;
	$img_id = get_term_meta( $cat->term_id, 'thumbnail_id', true );
	$desc   = term_description();
	if ( empty( $img_id ) && empty( $desc ) ) return;
	echo '<div class="mnsk7-cat-header">';
	if ( $img_id ) {
		echo '<div class="mnsk7-cat-header__img">'
			. wp_get_attachment_image( (int) $img_id, 'medium', false, array( 'alt' => esc_attr( $cat->name ), 'loading' => 'eager' ) )
			. '</div>';
	}
	if ( ! empty( $desc ) ) {
		$desc = preg_replace( '/\[wpf[-_]filters[^\]]*\]/i', '', (string) $desc );
		$desc = preg_replace( '/\s*Filtruj:\s*[^<]*?(?=\n\s*\n|\z)/s', '', $desc );
		$desc = trim( $desc );
		if ( $desc !== '' ) {
			echo '<div class="mnsk7-cat-header__desc">' . wp_kses_post( $desc ) . '</div>';
		}
	}
	echo '</div>';
}, 10 );
