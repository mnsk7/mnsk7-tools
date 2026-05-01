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

/**
 * Domyślne linki do postów Instagram (kolejność = wyświetlanie: 1 lewo, 2 środek, 3 prawo).
 */
function mnsk7_instagram_default_post_urls() {
	return array(
		'https://www.instagram.com/mnsk7tools/p/DC4agmPtKoy/',  // 1 — lewo
		'https://www.instagram.com/mnsk7tools/p/DC9J3JjNobj/',  // 2 — środek
		'https://www.instagram.com/mnsk7tools/p/DCTybzqtxEi/',  // 3 — prawo
	);
}

/** Jednorazowo zapisz w opcji mnsk7_instagram_post_urls trzy wskazane posty (żeby nie polegać na scrapingu). */
add_action( 'init', function () {
	if ( get_option( 'mnsk7_instagram_post_urls_seeded', 0 ) ) {
		return;
	}
	$current = get_option( 'mnsk7_instagram_post_urls', array() );
	if ( is_array( $current ) && ! empty( $current ) ) {
		update_option( 'mnsk7_instagram_post_urls_seeded', 1 );
		return;
	}
	$default = mnsk7_instagram_default_post_urls();
	update_option( 'mnsk7_instagram_post_urls', $default );
	update_option( 'mnsk7_instagram_post_urls_seeded', 1 );
}, 8 );

/**
 * Pobierz URL obrazka og:image dla strony (np. posta Instagram). Cache w transient 7 dni.
 *
 * @param string $url URL strony.
 * @return string URL obrazka lub pusty string.
 */
function mnsk7_instagram_og_image_for_url( $url ) {
	// v2: UA jak crawler FB — inaczej IG często zwraca placeholder zamiast scontent; v2 czyści stary transient.
	$cache_key = 'mnsk7_ig_thumb_v2_' . md5( $url );
	$cached    = get_transient( $cache_key );
	if ( is_string( $cached ) && $cached !== '' ) {
		return $cached;
	}
	$response = wp_remote_get(
		$url,
		array(
			'timeout'     => 8,
			'user-agent'  => 'facebookexternalhit/1.1 (+http://www.facebook.com/externalhit_uatext.php)',
			'redirection' => 3,
		)
	);
	if ( is_wp_error( $response ) || wp_remote_retrieve_response_code( $response ) !== 200 ) {
		set_transient( $cache_key, '', 1 * HOUR_IN_SECONDS );
		return '';
	}
	$body = wp_remote_retrieve_body( $response );
	if ( preg_match( '/<meta[^>]+property=["\']og:image["\'][^>]+content=["\']([^"\']+)["\']/', $body, $m ) || preg_match( '/<meta[^>]+content=["\']([^"\']+)["\'][^>]+property=["\']og:image["\']/', $body, $m ) ) {
		$img = esc_url_raw( $m[1] );
		if ( $img ) {
			set_transient( $cache_key, $img, 7 * DAY_IN_SECONDS );
			return $img;
		}
	}
	set_transient( $cache_key, '', 1 * HOUR_IN_SECONDS );
	return '';
}

/**
 * Normalizuje listę postów z opcji/shortcode: zwraca array of [ 'url' => ..., 'image' => ... ].
 * Opcja może być: array of string (URL) lub array of array( 'url' => ..., 'image' => ... ).
 */
function mnsk7_instagram_normalize_posts( $raw, $limit ) {
	$out = array();
	foreach ( $raw as $item ) {
		if ( is_array( $item ) ) {
			$url   = isset( $item['url'] ) ? esc_url_raw( $item['url'] ) : '';
			$image = isset( $item['image'] ) ? esc_url_raw( $item['image'] ) : '';
			if ( $url ) {
				$out[] = array( 'url' => $url, 'image' => $image );
			}
		} else {
			$url = esc_url_raw( $item );
			if ( $url ) {
				$out[] = array( 'url' => $url, 'image' => '' );
			}
		}
		if ( count( $out ) >= $limit ) {
			break;
		}
	}
	return $out;
}

function mnsk7_instagram_feed_html( $atts = array() ) {
	$atts  = shortcode_atts( array( 'posts' => '', 'limit' => 6, 'title' => __( 'Instagram @mnsk7tools', 'mnsk7-tools' ) ), $atts, 'mnsk7_instagram_feed' );
	$limit = max( 1, min( 12, (int) $atts['limit'] ) );
	$posts = array_filter( array_map( 'trim', explode( ',', (string) $atts['posts'] ) ) );
	if ( ! empty( $posts ) ) {
		$posts = mnsk7_instagram_normalize_posts( array_map( 'esc_url_raw', $posts ), $limit );
	} else {
		$from_option = get_option( 'mnsk7_instagram_post_urls', array() );
		if ( is_array( $from_option ) && ! empty( $from_option ) ) {
			$posts = mnsk7_instagram_normalize_posts( $from_option, $limit );
		}
		if ( empty( $posts ) ) {
			$urls = mnsk7_instagram_recent_post_urls( $limit );
			$posts = mnsk7_instagram_normalize_posts( $urls, $limit );
		}
		if ( empty( $posts ) ) {
			$posts = mnsk7_instagram_normalize_posts( mnsk7_instagram_default_post_urls(), $limit );
		}
	}
	$html  = '<section class="mnsk7-instagram-feed">';
	$html .= '<h4 class="mnsk7-instagram-feed__title">' . esc_html( $atts['title'] ) . '</h4>';
	if ( ! empty( $posts ) ) {
		$html .= '<div class="mnsk7-instagram-feed__grid">';
		$manual_images = apply_filters( 'mnsk7_instagram_post_images', array() );
		foreach ( $posts as $i => $item ) {
			$post_url = $item['url'];
			$thumb_url = ! empty( $item['image'] ) ? $item['image'] : ( isset( $manual_images[ $post_url ] ) ? $manual_images[ $post_url ] : '' );
			if ( ! $thumb_url ) {
				$thumb_url = mnsk7_instagram_og_image_for_url( $post_url );
			}
			$embed = wp_oembed_get( $post_url );
			$html .= '<div class="mnsk7-instagram-feed__item">';
			if ( $embed ) {
				$html .= $embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				$aria = sprintf( /* translators: %d: post number */ __( 'Post %d na Instagramie', 'mnsk7-tools' ), $i + 1 );
				$html .= '<a href="' . esc_url( $post_url ) . '" target="_blank" rel="noopener noreferrer" class="mnsk7-instagram-feed__link" aria-label="' . esc_attr( $aria ) . '">';
				if ( $thumb_url ) {
					$html .= '<img src="' . esc_url( $thumb_url ) . '" alt="" loading="lazy" width="320" height="320" class="mnsk7-instagram-feed__img">';
				} else {
					$html .= '<span class="mnsk7-instagram-feed__icon" aria-hidden="true"></span>';
					$html .= '<span class="mnsk7-instagram-feed__link-text">' . esc_html__( 'Zobacz post', 'mnsk7-tools' ) . '</span>';
				}
				$html .= '</a>';
			}
			$html .= '</div>';
		}
		$html .= '</div>';
	} else {
		$html .= '<p class="mnsk7-instagram-feed__fallback">' . esc_html__( 'Najświeższe posty znajdziesz na naszym profilu Instagram.', 'mnsk7-tools' ) . '</p>';
	}
	$profile_url = defined( 'MNK7_INSTAGRAM_URL' ) ? MNK7_INSTAGRAM_URL : 'https://www.instagram.com/mnsk7tools/';
	$html .= '<p class="mnsk7-instagram-feed__cta"><a href="' . esc_url( $profile_url ) . '" target="_blank" rel="noopener">@mnsk7tools</a></p>';
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
	$atts = shortcode_atts( array( 'show' => '1' ), $atts, 'mnsk7_allegro_reviews_pages' );
	if ( isset( $atts['show'] ) && ( $atts['show'] === '0' || $atts['show'] === false ) ) {
		return '';
	}
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
		'title'       => __( 'Opinie kupujących z Allegro', 'mnsk7-tools' ),
		'empty_text'  => __( 'Opinie produktowe są aktualnie synchronizowane. Zobacz pełne oceny sprzedawcy na Allegro.', 'mnsk7-tools' ),
		'pages'       => 20,
		'allegro_link' => '1', // '0' = nie pokazuj linku "Czytaj wszystkie opinie" (gdy sekcja ma własny CTA)
	), $atts, 'mnsk7_allegro_reviews' );
	$show_pages_link = ( isset( $atts['allegro_link'] ) && ( $atts['allegro_link'] === '0' || $atts['allegro_link'] === false ) ) ? false : true;
	$quotes = mnsk7_allegro_review_quotes();
	$html   = '<section class="mnsk7-allegro-reviews">';
	$html  .= '<h4 class="mnsk7-allegro-reviews__title">' . esc_html( $atts['title'] ) . '</h4>';
	if ( empty( $quotes ) ) {
		$html .= '<p class="mnsk7-allegro-reviews__empty">' . esc_html( $atts['empty_text'] ) . '</p>';
		if ( $show_pages_link ) {
			$html .= mnsk7_allegro_reviews_pages_html( array( 'show' => '1' ) );
		}
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
	if ( $show_pages_link ) {
		$html .= mnsk7_allegro_reviews_pages_html( array( 'show' => '1' ) );
	}
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
	/* mnsk7_instagram_feed — tylko motyw mnsk7-storefront (init 99), tu zostają helpery: og:image, opcje URL. */
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
		$atts = shortcode_atts(
			array(
				'limit'   => 4,
				'orderby' => 'popularity',
				'title'   => __( 'Polecane / Bestsellery', 'mnsk7-tools' ),
				'columns' => '3',
			),
			$atts,
			'mnsk7_bestsellers'
		);
		$limit   = max( 1, min( 48, (int) $atts['limit'] ) );
		$columns = max( 1, min( 6, (int) $atts['columns'] ) );
		return '<section class="mnsk7-bestsellers"><h2 class="mnsk7-bestsellers-title">' . esc_html( $atts['title'] ) . '</h2>'
			. do_shortcode( sprintf( '[products limit="%d" orderby="%s" columns="%d"]', $limit, sanitize_key( $atts['orderby'] ), $columns ) )
			. '</section>';
	} );
}, 5 );
