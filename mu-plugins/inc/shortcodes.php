<?php
/**
 * MNK7 Tools — shortcodes: dostawa, kontakt, Instagram, Allegro, bestsellery, rating.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/**
 * Try to fetch latest Instagram post URLs from public profile HTML.
 * Falls back gracefully when Instagram blocks scraping.
 */
function mnsk7_instagram_recent_post_urls( $limit = 3 ) {
	$limit     = max( 1, min( 6, (int) $limit ) );
	$cache_key = 'mnsk7_ig_recent_urls';
	$cached    = get_transient( $cache_key );

	if ( is_array( $cached ) && ! empty( $cached ) ) {
		return array_slice( $cached, 0, $limit );
	}

	$profile_url = defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/';
	$response    = wp_remote_get(
		$profile_url,
		array(
			'timeout'     => 10,
			'redirection' => 3,
			'user-agent'  => 'Mozilla/5.0 (compatible; MNK7Bot/1.0; +https://staging.mnsk7-tools.pl)',
		)
	);

	if ( is_wp_error( $response ) ) {
		return array();
	}

	$body = (string) wp_remote_retrieve_body( $response );
	if ( '' === $body ) {
		return array();
	}

	$matches = array();
	preg_match_all( '#https://www\.instagram\.com/(?:p|reel)/[A-Za-z0-9_-]+/?#', $body, $matches );

	if ( empty( $matches[0] ) ) {
		return array();
	}

	$urls = array_values( array_unique( array_map( 'esc_url_raw', $matches[0] ) ) );
	if ( empty( $urls ) ) {
		return array();
	}

	set_transient( $cache_key, $urls, 30 * MINUTE_IN_SECONDS );
	return array_slice( $urls, 0, $limit );
}

function mnsk7_instagram_feed_html( $atts = array() ) {
	$atts  = shortcode_atts( array( 'posts' => '', 'limit' => 3, 'title' => __( 'Instagram @mnsk7tools', 'mnsk7-tools' ) ), $atts, 'mnsk7_instagram_feed' );
	$limit = max( 1, min( 6, (int) $atts['limit'] ) );
	$posts = array_filter( array_map( 'trim', explode( ',', (string) $atts['posts'] ) ) );
	if ( ! empty( $posts ) ) {
		$posts = array_slice( $posts, 0, $limit );
	} else {
		$posts = mnsk7_instagram_recent_post_urls( $limit );
	}
	$html  = '<section class="mnsk7-instagram-feed">';
	$html .= '<h4 class="mnsk7-instagram-feed__title">' . esc_html( $atts['title'] ) . '</h4>';
	if ( ! empty( $posts ) ) {
		$html .= '<div class="mnsk7-instagram-feed__grid">';
		foreach ( $posts as $post_url ) {
			$post_url = esc_url( $post_url );
			if ( ! $post_url ) continue;
			$embed = wp_oembed_get( $post_url );
			$html .= '<div class="mnsk7-instagram-feed__item">';
			$html .= $embed
				? $embed // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				: '<a href="' . $post_url . '" target="_blank" rel="noopener">' . esc_html__( 'Zobacz post na Instagramie', 'mnsk7-tools' ) . '</a>';
			$html .= '</div>';
		}
		$html .= '</div>';
	} else {
		$html .= '<p class="mnsk7-instagram-feed__fallback">' . esc_html__( 'Najświeższe posty znajdziesz na naszym profilu Instagram.', 'mnsk7-tools' ) . '</p>';
	}
	$html .= '<p class="mnsk7-instagram-feed__cta"><a href="' . esc_url( MNK7_INSTAGRAM_URL ) . '" target="_blank" rel="noopener">@mnsk7tools</a></p>';
	$html .= '</section>';
	return $html;
}

function mnsk7_allegro_trust_html( $atts = array() ) {
	$atts = shortcode_atts( array(
		'seller' => 'mnsk7-tools_pl', 'recommendation' => '100%', 'positive' => '383',
		'negative' => '0', 'on_allegro' => 'od 1 roku i 8 miesięcy',
		'title' => __( 'Super sprzedawca na Allegro', 'mnsk7-tools' ),
		'cta'   => __( 'Zobacz wszystkie oceny i komentarze', 'mnsk7-tools' ),
		'url'   => MNK7_ALLEGRO_SELLER_URL,
	), $atts, 'mnsk7_allegro_trust' );
	$html  = '<section class="mnsk7-allegro-trust">';
	$html .= '<h4 class="mnsk7-allegro-trust__title">' . esc_html( $atts['title'] ) . '</h4>';
	$html .= '<p class="mnsk7-allegro-trust__seller"><strong>' . esc_html( $atts['seller'] ) . '</strong> — ' . esc_html( $atts['on_allegro'] ) . '</p>';
	$html .= '<ul class="mnsk7-allegro-trust__stats">';
	$html .= '<li><strong>' . esc_html( $atts['recommendation'] ) . '</strong> ' . esc_html__( 'kupujących poleca sprzedawcę', 'mnsk7-tools' ) . '</li>';
	$html .= '<li>' . esc_html__( 'Oceny pozytywne:', 'mnsk7-tools' ) . ' <strong>' . esc_html( $atts['positive'] ) . '</strong></li>';
	$html .= '<li>' . esc_html__( 'Oceny negatywne:', 'mnsk7-tools' ) . ' <strong>' . esc_html( $atts['negative'] ) . '</strong></li>';
	$html .= '<li>' . esc_html__( 'Wszystkie opinie są potwierdzone zakupem na Allegro.', 'mnsk7-tools' ) . '</li>';
	$html .= '</ul>';
	$html .= '<p class="mnsk7-allegro-trust__cta"><a href="' . esc_url( $atts['url'] ) . '" target="_blank" rel="noopener">' . esc_html( $atts['cta'] ) . '</a></p>';
	$html .= '</section>';
	return $html;
}

function mnsk7_allegro_reviews_pages_html( $atts = array() ) {
	$url  = esc_url( MNK7_ALLEGRO_SELLER_URL . '/oceny' );
	return '<p class="mnsk7-allegro-reviews__cta-wrap">'
		. '<a class="mnsk7-allegro-reviews__cta-btn" href="' . $url . '" target="_blank" rel="noopener nofollow">'
		. esc_html__( 'Czytaj wszystkie opinie na Allegro →', 'mnsk7-tools' )
		. '</a></p>';
}

function mnsk7_allegro_review_quotes() {
	$quotes = array(
		array( 'text' => 'Szybka wysyłka, towar zgodny z opisem. Frez naprawdę dobrej jakości — polecam!', 'author' => 'Kupujący Allegro', 'rating' => 5 ),
		array( 'text' => 'Super sprzedawca — dostawa na następny dzień, opakowanie solidne, produkt świetny.', 'author' => 'Kupujący Allegro', 'rating' => 5 ),
		array( 'text' => 'Frez do aluminium — doskonałe cięcie, brak odprysku, długa żywotność. Będę zamawiał więcej.', 'author' => 'Kupujący Allegro', 'rating' => 5 ),
	);
	return apply_filters( 'mnsk7_allegro_review_quotes', $quotes );
}

function mnsk7_allegro_reviews_html( $atts = array() ) {
	$atts   = shortcode_atts( array(
		'title'      => __( 'Opinie kupujących z Allegro', 'mnsk7-tools' ),
		'empty_text' => __( 'Opinie produktowe są aktualnie synchronizowane. Zobacz pełne oceny sprzedawcy na Allegro.', 'mnsk7-tools' ),
		'pages'      => 20,
	), $atts, 'mnsk7_allegro_reviews' );
	$quotes = mnsk7_allegro_review_quotes();
	$html   = '<section class="mnsk7-allegro-reviews">';
	$html  .= '<h4 class="mnsk7-allegro-reviews__title">' . esc_html( $atts['title'] ) . '</h4>';
	if ( empty( $quotes ) ) {
		$html .= '<p class="mnsk7-allegro-reviews__empty">' . esc_html( $atts['empty_text'] ) . '</p>';
		$html .= mnsk7_allegro_reviews_pages_html();
		$html .= '</section>';
		return $html;
	}
	$html .= '<div class="mnsk7-allegro-reviews__list">';
	foreach ( $quotes as $quote ) {
		$text   = sanitize_text_field( (string) ( $quote['text'] ?? '' ) );
		$author = sanitize_text_field( (string) ( $quote['author'] ?? __( 'Kupujący Allegro', 'mnsk7-tools' ) ) );
		if ( ! $text ) continue;
		$html .= '<blockquote class="mnsk7-allegro-reviews__item"><p>' . esc_html( $text ) . '</p><cite>' . esc_html( $author ) . '</cite></blockquote>';
	}
	$html .= '</div>';
	$html .= mnsk7_allegro_reviews_pages_html();
	$html .= '</section>';
	return $html;
}

function mnsk7_get_filter_attribute_order() {
	return array( 'typ', 'srednica', 'fi', 'dlugosc-robocza-h', 'dlugosc-calkowita-l', 'zastosowanie' );
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_dostawa_vat',         fn() => mnsk7_dostawa_vat_html() );
	add_shortcode( 'mnsk7_contact_info',        fn() => mnsk7_contact_info_html() );
	add_shortcode( 'mnsk7_delivery_rules',      fn() => mnsk7_delivery_rules_table_html() );
	add_shortcode( 'mnsk7_delivery_eta', function ( $atts ) {
		$atts    = shortcode_atts( array( 'courier' => '' ), $atts, 'mnsk7_delivery_eta' );
		$courier = strtolower( sanitize_text_field( $atts['courier'] ) );
		if ( ! in_array( $courier, array( '', 'inpost', 'dpd' ), true ) ) {
			$courier = '';
		}
		return mnsk7_delivery_eta_html( $courier );
	} );
	add_shortcode( 'mnsk7_instagram_feed',      fn( $a ) => mnsk7_instagram_feed_html( $a ) );
	add_shortcode( 'mnsk7_allegro_trust',       fn( $a ) => mnsk7_allegro_trust_html( $a ) );
	add_shortcode( 'mnsk7_allegro_reviews_pages', fn( $a ) => mnsk7_allegro_reviews_pages_html( $a ) );
	add_shortcode( 'mnsk7_allegro_reviews',     fn( $a ) => mnsk7_allegro_reviews_html( $a ) );
	add_shortcode( 'mnsk7_rating', function ( $atts ) {
		$atts = shortcode_atts( array( 'url' => '', 'title' => __( 'Sprawdź opinie o naszym sklepie na Allegro', 'mnsk7-tools' ) ), $atts, 'mnsk7_rating' );
		$url  = esc_url( $atts['url'] );
		return $url
			? '<p class="mnsk7-store-rating"><a href="' . $url . '" target="_blank" rel="noopener">' . esc_html( $atts['title'] ) . '</a></p>'
			: '<p class="mnsk7-store-rating">' . esc_html( $atts['title'] ) . '</p>';
	} );
	add_shortcode( 'mnsk7_bestsellers', function ( $atts ) {
		$atts = shortcode_atts( array( 'limit' => 4, 'orderby' => 'popularity', 'title' => __( 'Polecane / Bestsellery', 'mnsk7-tools' ) ), $atts, 'mnsk7_bestsellers' );
		return '<section class="mnsk7-bestsellers"><h2 class="mnsk7-bestsellers-title">' . esc_html( $atts['title'] ) . '</h2>'
			. do_shortcode( sprintf( '[products limit="%d" orderby="%s" columns="4"]', (int) $atts['limit'], sanitize_key( $atts['orderby'] ) ) )
			. '</section>';
	} );
}, 5 );
