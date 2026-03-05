<?php
/**
 * Plugin Name: MNK7 Tools (MU)
 * Description: Biznesowa logika projektu mnsk7-tools.pl — filtry, helpery, customizacje Woo. Nie zależy od motywu.
 * Author: Projekt mnsk7-tools
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

// P0-03: blokada xmlrpc.php (bezpieczeństwo)
if ( defined( 'XMLRPC_REQUEST' ) && XMLRPC_REQUEST ) {
	status_header( 403 );
	exit;
}

define( 'MNK7_TOOLS_VERSION', '1.0.0' );

/**
 * Stałe kontaktowe (klient).
 */
define( 'MNK7_CONTACT_EMAIL', 'office@mnsk7.pl' );
define( 'MNK7_CONTACT_PHONE', '+48 451696511' );
define( 'MNK7_CONTACT_HOURS', 'pn.–pt. 9.00–17.00, sb. 10.00–12.00, nd. zamknięte' );
define( 'MNK7_INSTAGRAM_URL', 'https://www.instagram.com/mnsk7tools/' );
define( 'MNK7_ALLEGRO_SELLER_URL', 'https://allegro.pl/uzytkownik/mnsk7-tools_pl' );

/**
 * Lista atrybutów wyświetlanych w bloku "Kluczowe parametry" w karcie produktu.
 * Klucz = slug atrybutu (Woo: pa_* dla globalnych), wartość = etykieta.
 */
function mnsk7_get_key_param_attributes() {
	return array(
		'srednica'       => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'pa_srednica'    => __( 'Średnica części roboczej', 'mnsk7-tools' ),
		'fi'             => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'pa_fi'          => __( 'Średnica trzpienia', 'mnsk7-tools' ),
		'dlugosc-robocza-h' => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-calkowita-l' => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-calkowita'   => __( 'Długość całkowita', 'mnsk7-tools' ),
		'dlugosc-robocza'    => __( 'Długość robocza', 'mnsk7-tools' ),
		'dlugosc-czesci-roboczej' => __( 'Długość części roboczej', 'mnsk7-tools' ),
		'r'              => __( 'Promień R', 'mnsk7-tools' ),
		'pa_r'           => __( 'Promień R', 'mnsk7-tools' ),
		'typ'            => __( 'Typ', 'mnsk7-tools' ),
		'pa_typ'         => __( 'Typ', 'mnsk7-tools' ),
		'ksztalt'        => __( 'Kształt', 'mnsk7-tools' ),
		'zastosowanie'   => __( 'Zastosowanie', 'mnsk7-tools' ),
		'pa_zastosowanie' => __( 'Zastosowanie', 'mnsk7-tools' ),
	);
}

/**
 * Wyświetla blok kluczowych parametrów w karcie produktu (S2-04).
 */
function mnsk7_single_product_key_params() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$labels = mnsk7_get_key_param_attributes();
	$found  = array();

	foreach ( array_keys( $labels ) as $slug ) {
		$val = $product->get_attribute( $slug );
		if ( $val !== '' && $val !== null ) {
			$label = $labels[ $slug ];
			if ( ! isset( $found[ $label ] ) ) {
				$found[ $label ] = $val;
			}
		}
	}

	if ( empty( $found ) ) {
		return;
	}

	echo '<div class="mnsk7-product-key-params">';
	echo '<h4 class="mnsk7-product-key-params__title">' . esc_html__( 'Kluczowe parametry', 'mnsk7-tools' ) . '</h4>';
	echo '<dl class="mnsk7-product-key-params__list">';
	foreach ( $found as $label => $value ) {
		echo '<dt>' . esc_html( $label ) . '</dt>';
		echo '<dd>' . esc_html( $value ) . '</dd>';
	}
	echo '</dl></div>';
}

/**
 * Wyświetla blok "Podstaw dla" w karcie produktu (S2-05).
 */
function mnsk7_single_product_zastosowanie() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$val = $product->get_attribute( 'zastosowanie' );
	if ( $val === '' || $val === null ) {
		$val = $product->get_attribute( 'pa_zastosowanie' );
	}
	if ( $val === '' || $val === null ) {
		return;
	}

	echo '<div class="mnsk7-product-zastosowanie">';
	echo '<h4 class="mnsk7-product-zastosowanie__title">' . esc_html__( 'Do czego / Zastosowanie', 'mnsk7-tools' ) . '</h4>';
	echo '<p class="mnsk7-product-zastosowanie__text">' . esc_html( $val ) . '</p>';
	echo '</div>';
}

/**
 * Wyświetla dostępność w magazynie (S2-10).
 */
function mnsk7_single_product_availability() {
	global $product;
	if ( ! is_a( $product, 'WC_Product' ) ) {
		return;
	}

	$availability = $product->get_availability();
	$class        = ! empty( $availability['class'] ) ? $availability['class'] : '';
	$text         = ! empty( $availability['availability'] ) ? $availability['availability'] : ( $product->is_in_stock() ? __( 'W magazynie', 'mnsk7-tools' ) : __( 'Na zamówienie', 'mnsk7-tools' ) );

	echo '<p class="mnsk7-product-availability ' . esc_attr( $class ) . '">' . esc_html( $text ) . '</p>';
}

/**
 * Wyświetla informację o dostawie i fakturze VAT (S2-11) — w karcie produktu i shortcode.
 */
function mnsk7_dostawa_vat_html() {
	return '<p class="mnsk7-dostawa-vat">'
		. esc_html__( 'Dostawa następnego dnia. Faktura VAT dostępna na życzenie.', 'mnsk7-tools' )
		. '</p>';
}

/**
 * Kontakt do wyświetlenia w stopce / shortcode.
 */
function mnsk7_contact_info_html() {
	$email = antispambot( MNK7_CONTACT_EMAIL );
	$phone = preg_replace( '/\s+/', '', MNK7_CONTACT_PHONE );

	return '<div class="mnsk7-contact-info">'
		. '<h4 class="mnsk7-contact-info__title">' . esc_html__( 'Kontakt', 'mnsk7-tools' ) . '</h4>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Email:', 'mnsk7-tools' ) . '</strong> <a href="mailto:' . esc_attr( $email ) . '">' . esc_html( $email ) . '</a></p>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Telefon:', 'mnsk7-tools' ) . '</strong> <a href="tel:' . esc_attr( $phone ) . '">' . esc_html( MNK7_CONTACT_PHONE ) . '</a></p>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Godziny:', 'mnsk7-tools' ) . '</strong> ' . esc_html( MNK7_CONTACT_HOURS ) . '</p>'
		. '<p class="mnsk7-contact-info__line"><strong>' . esc_html__( 'Instagram:', 'mnsk7-tools' ) . '</strong> <a href="' . esc_url( MNK7_INSTAGRAM_URL ) . '" target="_blank" rel="noopener">mnsk7tools</a></p>'
		. '</div>';
}

/**
 * Reguły dostawy (InPost / DPD) + darmowa dostawa.
 */
function mnsk7_delivery_rules_table_html() {
	$rows = array(
		array(
			'courier' => 'InPost',
			'order'   => __( 'pn.–pt. do 15:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa następnego dnia', 'mnsk7-tools' ),
		),
		array(
			'courier' => 'InPost',
			'order'   => __( 'sb. do 11:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa w poniedziałek', 'mnsk7-tools' ),
		),
		array(
			'courier' => 'DPD',
			'order'   => __( 'pn.–czw. do 17:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa następnego dnia', 'mnsk7-tools' ),
		),
		array(
			'courier' => 'DPD',
			'order'   => __( 'pt. do 17:00', 'mnsk7-tools' ),
			'result'  => __( 'dostawa w poniedziałek', 'mnsk7-tools' ),
		),
	);

	$html  = '<div class="mnsk7-delivery-rules">';
	$html .= '<h4 class="mnsk7-delivery-rules__title">' . esc_html__( 'Orientacyjny czas dostawy (Polska)', 'mnsk7-tools' ) . '</h4>';
	$html .= '<table class="mnsk7-delivery-rules__table"><thead><tr>';
	$html .= '<th>' . esc_html__( 'Kurier', 'mnsk7-tools' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Kiedy zamówisz', 'mnsk7-tools' ) . '</th>';
	$html .= '<th>' . esc_html__( 'Dostawa', 'mnsk7-tools' ) . '</th>';
	$html .= '</tr></thead><tbody>';

	foreach ( $rows as $row ) {
		$html .= '<tr>';
		$html .= '<td>' . esc_html( $row['courier'] ) . '</td>';
		$html .= '<td>' . esc_html( $row['order'] ) . '</td>';
		$html .= '<td>' . esc_html( $row['result'] ) . '</td>';
		$html .= '</tr>';
	}

	$html .= '</tbody></table>';
	$html .= '<p class="mnsk7-delivery-rules__free">' . esc_html__( 'Darmowa dostawa od 300 zł.', 'mnsk7-tools' ) . '</p>';
	$html .= '</div>';

	return $html;
}

/**
 * Szacowany komunikat ETA pod wybraną metodę dostawy.
 */
function mnsk7_estimated_delivery_text( $courier ) {
	$hour = (int) current_time( 'G' );
	$wday = (int) current_time( 'w' ); // 0 = Sunday, 6 = Saturday.

	if ( $courier === 'inpost' ) {
		if ( $wday >= 1 && $wday <= 5 ) {
			if ( $hour < 15 ) {
				return __( 'InPost: zamów do 15:00 — dostawa następnego dnia.', 'mnsk7-tools' );
			}
			return __( 'InPost: zamówienie po 15:00 — dostawa zwykle w najbliższy dzień roboczy.', 'mnsk7-tools' );
		}
		if ( $wday === 6 ) {
			return __( 'InPost: sobota do 11:00 — dostawa w poniedziałek.', 'mnsk7-tools' );
		}
		return __( 'InPost: zamówienie w niedzielę — wysyłka od poniedziałku.', 'mnsk7-tools' );
	}

	if ( $courier === 'dpd' ) {
		if ( $wday >= 1 && $wday <= 4 ) {
			if ( $hour < 17 ) {
				return __( 'DPD: zamów do 17:00 — dostawa następnego dnia.', 'mnsk7-tools' );
			}
			return __( 'DPD: zamówienie po 17:00 — dostawa zwykle w najbliższy dzień roboczy.', 'mnsk7-tools' );
		}
		if ( $wday === 5 ) {
			return __( 'DPD: piątek do 17:00 — dostawa w poniedziałek.', 'mnsk7-tools' );
		}
		return __( 'DPD: zamówienie w weekend — wysyłka od poniedziałku.', 'mnsk7-tools' );
	}

	return __( 'Dostawa: wybierz InPost lub DPD, aby zobaczyć orientacyjny termin dostawy.', 'mnsk7-tools' );
}

/**
 * Próba rozpoznania kuriera na podstawie wybranej metody Woo (checkout/cart).
 */
function mnsk7_detect_selected_courier() {
	if ( ! function_exists( 'WC' ) || ! WC()->session ) {
		return '';
	}

	$methods = WC()->session->get( 'chosen_shipping_methods', array() );
	if ( empty( $methods ) || ! is_array( $methods ) ) {
		return '';
	}

	$method = strtolower( (string) reset( $methods ) );
	if ( strpos( $method, 'inpost' ) !== false ) {
		return 'inpost';
	}
	if ( strpos( $method, 'dpd' ) !== false ) {
		return 'dpd';
	}
	return '';
}

/**
 * HTML komunikatu ETA.
 */
function mnsk7_delivery_eta_html( $courier = '' ) {
	if ( $courier === '' ) {
		$courier = mnsk7_detect_selected_courier();
	}
	return '<p class="mnsk7-delivery-eta">' . esc_html( mnsk7_estimated_delivery_text( $courier ) ) . '</p>';
}

/**
 * Instagram block (shortcode).
 * Użycie:
 * [mnsk7_instagram_feed]
 * [mnsk7_instagram_feed posts="https://www.instagram.com/p/abc/,https://www.instagram.com/p/def/"]
 */
function mnsk7_instagram_feed_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'posts' => '',
			'limit' => 3,
			'title' => __( 'Instagram @mnsk7tools', 'mnsk7-tools' ),
		),
		$atts,
		'mnsk7_instagram_feed'
	);

	$limit = max( 1, min( 6, (int) $atts['limit'] ) );
	$posts = array_filter( array_map( 'trim', explode( ',', (string) $atts['posts'] ) ) );
	if ( ! empty( $posts ) ) {
		$posts = array_slice( $posts, 0, $limit );
	}

	$html  = '<section class="mnsk7-instagram-feed">';
	$html .= '<h4 class="mnsk7-instagram-feed__title">' . esc_html( $atts['title'] ) . '</h4>';

	if ( ! empty( $posts ) ) {
		$html .= '<div class="mnsk7-instagram-feed__grid">';
		foreach ( $posts as $post_url ) {
			$post_url = esc_url( $post_url );
			if ( $post_url === '' ) {
				continue;
			}
			$embed = wp_oembed_get( $post_url );
			$html .= '<div class="mnsk7-instagram-feed__item">';
			if ( $embed ) {
				$html .= $embed; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
			} else {
				$html .= '<a href="' . $post_url . '" target="_blank" rel="noopener">' . esc_html__( 'Zobacz post na Instagramie', 'mnsk7-tools' ) . '</a>';
			}
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

/**
 * Allegro trust block (shortcode).
 * Użycie:
 * [mnsk7_allegro_trust]
 */
function mnsk7_allegro_trust_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'seller'              => 'mnsk7-tools_pl',
			'recommendation'      => '100%',
			'positive'            => '383',
			'negative'            => '0',
			'on_allegro'          => 'od 1 roku i 8 miesięcy',
			'title'               => __( 'Super sprzedawca na Allegro', 'mnsk7-tools' ),
			'cta'                 => __( 'Zobacz wszystkie oceny i komentarze', 'mnsk7-tools' ),
			'url'                 => MNK7_ALLEGRO_SELLER_URL,
		),
		$atts,
		'mnsk7_allegro_trust'
	);

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

/**
 * Linki do stron z ocenami Allegro (page=1..N).
 */
function mnsk7_allegro_reviews_pages_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'from'  => 1,
			'to'    => 20,
			'base'  => 'https://allegro.pl/uzytkownik/mnsk7-tools_pl/oceny?page=%d',
			'title' => __( 'Wszystkie oceny Allegro', 'mnsk7-tools' ),
		),
		$atts,
		'mnsk7_allegro_reviews_pages'
	);

	$from = max( 1, (int) $atts['from'] );
	$to   = max( $from, min( 50, (int) $atts['to'] ) );
	$base = (string) $atts['base'];

	$html  = '<section class="mnsk7-allegro-pages">';
	$html .= '<h4 class="mnsk7-allegro-pages__title">' . esc_html( $atts['title'] ) . '</h4>';
	$html .= '<div class="mnsk7-allegro-pages__links">';
	for ( $page = $from; $page <= $to; $page++ ) {
		$url   = esc_url( sprintf( $base, $page ) );
		$html .= '<a href="' . $url . '" target="_blank" rel="noopener">page ' . (int) $page . '</a>';
	}
	$html .= '</div>';
	$html .= '</section>';

	return $html;
}

/**
 * Ręczne cytaty z opinii Allegro.
 * Domyślnie puste; można podpiąć filtrem:
 * add_filter( 'mnsk7_allegro_review_quotes', fn() => [ [ 'text' => '...', 'author' => 'Kupujący' ] ] );
 */
function mnsk7_allegro_review_quotes() {
	$quotes = array();
	return apply_filters( 'mnsk7_allegro_review_quotes', $quotes );
}

/**
 * Shortcode z cytatami opinii + CTA do wszystkich stron ocen.
 * Użycie: [mnsk7_allegro_reviews]
 */
function mnsk7_allegro_reviews_html( $atts = array() ) {
	$atts = shortcode_atts(
		array(
			'title'      => __( 'Opinie kupujących z Allegro', 'mnsk7-tools' ),
			'empty_text' => __( 'Opinie produktowe są aktualnie synchronizowane. Zobacz pełne oceny sprzedawcy na Allegro.', 'mnsk7-tools' ),
			'pages'      => 20,
		),
		$atts,
		'mnsk7_allegro_reviews'
	);

	$quotes = mnsk7_allegro_review_quotes();
	$html   = '<section class="mnsk7-allegro-reviews">';
	$html  .= '<h4 class="mnsk7-allegro-reviews__title">' . esc_html( $atts['title'] ) . '</h4>';

	if ( empty( $quotes ) || ! is_array( $quotes ) ) {
		$html .= '<p class="mnsk7-allegro-reviews__empty">' . esc_html( $atts['empty_text'] ) . '</p>';
		$html .= mnsk7_allegro_reviews_pages_html(
			array(
				'from' => 1,
				'to'   => (int) $atts['pages'],
			)
		);
		$html .= '</section>';
		return $html;
	}

	$html .= '<div class="mnsk7-allegro-reviews__list">';
	foreach ( $quotes as $quote ) {
		$text   = isset( $quote['text'] ) ? sanitize_text_field( (string) $quote['text'] ) : '';
		$author = isset( $quote['author'] ) ? sanitize_text_field( (string) $quote['author'] ) : __( 'Kupujący Allegro', 'mnsk7-tools' );
		if ( $text === '' ) {
			continue;
		}
		$html .= '<blockquote class="mnsk7-allegro-reviews__item">';
		$html .= '<p>' . esc_html( $text ) . '</p>';
		$html .= '<cite>' . esc_html( $author ) . '</cite>';
		$html .= '</blockquote>';
	}
	$html .= '</div>';
	$html .= mnsk7_allegro_reviews_pages_html(
		array(
			'from' => 1,
			'to'   => (int) $atts['pages'],
		)
	);
	$html .= '</section>';

	return $html;
}

add_action( 'init', function () {
	add_shortcode( 'mnsk7_dostawa_vat', function () {
		return mnsk7_dostawa_vat_html();
	} );
	add_shortcode( 'mnsk7_contact_info', function () {
		return mnsk7_contact_info_html();
	} );
	add_shortcode( 'mnsk7_delivery_rules', function () {
		return mnsk7_delivery_rules_table_html();
	} );
	add_shortcode( 'mnsk7_delivery_eta', function ( $atts ) {
		$atts = shortcode_atts(
			array(
				'courier' => '',
			),
			$atts,
			'mnsk7_delivery_eta'
		);
		$courier = strtolower( sanitize_text_field( $atts['courier'] ) );
		if ( ! in_array( $courier, array( '', 'inpost', 'dpd' ), true ) ) {
			$courier = '';
		}
		return mnsk7_delivery_eta_html( $courier );
	} );
	add_shortcode( 'mnsk7_instagram_feed', function ( $atts ) {
		return mnsk7_instagram_feed_html( $atts );
	} );
	add_shortcode( 'mnsk7_allegro_trust', function ( $atts ) {
		return mnsk7_allegro_trust_html( $atts );
	} );
	add_shortcode( 'mnsk7_allegro_reviews_pages', function ( $atts ) {
		return mnsk7_allegro_reviews_pages_html( $atts );
	} );
	add_shortcode( 'mnsk7_allegro_reviews', function ( $atts ) {
		return mnsk7_allegro_reviews_html( $atts );
	} );
}, 5 );

// W karcie produktu: po bloku "Do czego" pokazujemy dostawę i VAT
add_action( 'woocommerce_single_product_summary', function () {
	echo mnsk7_dostawa_vat_html();
}, 35 );

// Stopka: treść wyświetlana w szablonie footer (tech-storefront), nie w wp_footer.

/**
 * Cookie consent — minimalny pasek (zastępuje zewnętrzny plugin).
 * Po kliknięciu "Przyjmuję" ustawia ciasteczko na 1 rok i chowa pasek.
 */
add_action( 'wp_footer', function () {
	if ( is_admin() ) {
		return;
	}
	$text = __( 'Ta strona używa plików cookie. Kliknij „Przyjmuję”, aby kontynuować.', 'mnsk7-tools' );
	$btn = __( 'Przyjmuję', 'mnsk7-tools' );
	?>
	<div id="mnsk7-cookie-bar" class="mnsk7-cookie-bar" role="dialog" aria-label="<?php echo esc_attr__( 'Informacja o cookies', 'mnsk7-tools' ); ?>" hidden>
		<div class="mnsk7-cookie-bar__inner">
			<p class="mnsk7-cookie-bar__text"><?php echo esc_html( $text ); ?></p>
			<button type="button" class="mnsk7-cookie-bar__btn" id="mnsk7-cookie-bar-accept"><?php echo esc_html( $btn ); ?></button>
		</div>
	</div>
	<script>
	(function() {
		var key = 'mnsk7_cookie_ok';
		function get(name) { var m = document.cookie.match(new RegExp('(?:^|; )' + name.replace(/([\.$?*|{}\(\)\[\]\\\/+^])/g, '\\$1') + '=([^;]*)')); return m ? decodeURIComponent(m[1]) : null; }
		function set(name, val, days) { var d = new Date(); d.setTime(d.getTime() + days * 86400000); document.cookie = name + '=' + encodeURIComponent(val) + ';path=/;expires=' + d.toUTCString() + ';SameSite=Lax'; }
		var bar = document.getElementById('mnsk7-cookie-bar');
		if (!bar) return;
		if (get(key)) { bar.remove(); return; }
		bar.removeAttribute('hidden');
		document.getElementById('mnsk7-cookie-bar-accept').addEventListener('click', function() { set(key, '1', 365); bar.remove(); });
	})();
	</script>
	<?php
}, 99 );

/**
 * S2-06: placeholder na schemat parametrów lub wideo w karcie produktu.
 * Gdy dodasz zdjęcie schematu / wideo — wyświetl je tutaj lub usuń wywołanie.
 */
function mnsk7_single_product_schema_video_placeholder() {
	// Opcjonalnie: echo '<p class="mnsk7-schema-video-placeholder" style="color:#999;font-size:0.9em;">Miejsce na schemat parametrów lub wideo.</p>';
	// Na razie puste — blok jest w szablonie, można dodać treść per produkt (np. custom field).
}

/**
 * Kolejność atrybutów do filtrów katalogu (S2-02). Użyj w pluginie filtrów lub w sidebarze.
 * Typ → średnica → trzpień → długość → zastosowanie.
 */
function mnsk7_get_filter_attribute_order() {
	return array( 'typ', 'srednica', 'fi', 'dlugosc-robocza-h', 'dlugosc-calkowita-l', 'zastosowanie' );
}

/**
 * Shortcode: rating sklepu (S2-09). Placeholder pod Allegro lub przyszłe opinie.
 * Użycie: [mnsk7_rating] lub [mnsk7_rating url="https://allegro.pl/..." title="Nasz sklep"]
 */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_rating', function ( $atts ) {
		$atts = shortcode_atts( array(
			'url'   => '',
			'title' => __( 'Sprawdź opinie o naszym sklepie na Allegro', 'mnsk7-tools' ),
		), $atts, 'mnsk7_rating' );
		$url = esc_url( $atts['url'] );
		if ( $url === '' ) {
			return '<p class="mnsk7-store-rating">' . esc_html( $atts['title'] ) . '</p>';
		}
		return '<p class="mnsk7-store-rating"><a href="' . $url . '" target="_blank" rel="noopener">' . esc_html( $atts['title'] ) . '</a></p>';
	} );
}, 6 );

/**
 * Shortcode: blok popularnych / hitów (S2-07). Na głównej: [mnsk7_bestsellers].
 * Domyślnie 4 produkty po popularności (orderby=popularity); można nadpisać atrybutami.
 */
add_action( 'init', function () {
	add_shortcode( 'mnsk7_bestsellers', function ( $atts ) {
		$atts = shortcode_atts( array(
			'limit'   => 4,
			'orderby' => 'popularity',
			'title'   => __( 'Polecane / Bestsellery', 'mnsk7-tools' ),
		), $atts, 'mnsk7_bestsellers' );
		$shortcode = sprintf(
			'[products limit="%d" orderby="%s" columns="4"]',
			(int) $atts['limit'],
			sanitize_key( $atts['orderby'] )
		);
		return '<section class="mnsk7-bestsellers">'
			. '<h2 class="mnsk7-bestsellers-title">' . esc_html( $atts['title'] ) . '</h2>'
			. do_shortcode( $shortcode )
			. '</section>';
	} );
}, 6 );
