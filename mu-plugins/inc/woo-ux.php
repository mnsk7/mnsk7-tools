<?php
/**
 * MNK7 Tools — WooCommerce UX: related products, upsells, cookie, security.
 *
 * @package mnsk7-tools
 */

defined( 'ABSPATH' ) || exit;

/* Related products: limit 4, 4 kolumny */
add_filter( 'woocommerce_output_related_products_args', function ( $args ) {
	$args['posts_per_page'] = 4;
	$args['columns']        = 4;
	return $args;
} );

add_filter( 'woocommerce_upsells_total',   fn() => 4 );
add_filter( 'woocommerce_upsells_columns', fn() => 4 );

/* Cookie consent bar — widoczny, z przyciskiem Ustawienia (GDPR) */
add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	$privacy_url = home_url( '/polityka-prywatnosci/' );
	$settings_url = add_query_arg( 'cookies', '1', $privacy_url ) . '#cookies';
	?>
	<div id="mnsk7-cookie-bar" class="mnsk7-cookie-bar" role="dialog" aria-label="<?php esc_attr_e( 'Informacja o cookies', 'mnsk7-tools' ); ?>" aria-hidden="true" hidden>
		<div class="mnsk7-cookie-bar__inner">
			<p class="mnsk7-cookie-bar__text"><?php esc_html_e( 'Ta strona używa plików cookie. Kliknij „Przyjmuję", aby kontynuować.', 'mnsk7-tools' ); ?> <a href="<?php echo esc_url( $privacy_url ); ?>#cookies" class="mnsk7-cookie-bar__link"><?php esc_html_e( 'Więcej informacji', 'mnsk7-tools' ); ?></a></p>
			<div class="mnsk7-cookie-bar__buttons">
				<a href="<?php echo esc_url( $settings_url ); ?>" class="mnsk7-cookie-bar__btn mnsk7-cookie-bar__btn--secondary" id="mnsk7-cookie-bar-settings"><?php esc_html_e( 'Ustawienia', 'mnsk7-tools' ); ?></a>
				<button type="button" class="mnsk7-cookie-bar__btn" id="mnsk7-cookie-bar-accept"><?php esc_html_e( 'Przyjmuję', 'mnsk7-tools' ); ?></button>
			</div>
		</div>
	</div>
	<script>
	(function(){
		var key='mnsk7_cookie_ok';
		function get(n){var m=document.cookie.match(new RegExp('(?:^|; )'+n.replace(/([\.$?*|{}\(\)\[\]\\\/+^])/g,'\\$1')+'=([^;]*)'));return m?decodeURIComponent(m[1]):null;}
		function set(n,v,d){var e=new Date();e.setTime(e.getTime()+d*86400000);document.cookie=n+'='+encodeURIComponent(v)+';path=/;expires='+e.toUTCString()+';SameSite=Lax';}
		var bar=document.getElementById('mnsk7-cookie-bar');
		if(!bar)return;
		if(get(key)){bar.remove();return;}
		bar.removeAttribute('hidden');bar.setAttribute('aria-hidden','false');
		document.body.classList.add('mnsk7-cookie-bar-visible');
		document.getElementById('mnsk7-cookie-bar-accept').addEventListener('click',function(){set(key,'1',365);bar.remove();document.body.classList.remove('mnsk7-cookie-bar-visible');});
	})();
	</script>
	<?php
}, 99 );

/**
 * Seed primary menu: Przewodnik, Sklep, Dostawa i płatności, Kontakt (home_url() — staging i prod).
 */
add_action( 'init', function () {
	if ( get_option( 'mnsk7_menu_landings_seeded', 0 ) ) {
		return;
	}
	$locations = get_nav_menu_locations();
	$menu_id   = isset( $locations['primary'] ) ? (int) $locations['primary'] : 0;
	if ( $menu_id < 1 ) {
		update_option( 'mnsk7_menu_landings_seeded', 1 );
		return;
	}
	$items = wp_get_nav_menu_items( $menu_id );
	$urls  = array();
	if ( is_array( $items ) ) {
		foreach ( $items as $item ) {
			if ( ! empty( $item->url ) ) {
				$urls[] = trailingslashit( $item->url );
			}
		}
	}
	$home   = trailingslashit( home_url() );
	$to_add = array(
		array( 'Przewodnik', $home . 'przewodnik/' ),
		array( 'Sklep', $home . 'sklep/' ),
		array( 'Dostawa i płatności', $home . 'dostawa-i-platnosci/' ),
		array( 'Kontakt', $home . 'kontakt/' ),
	);
	foreach ( $to_add as $pair ) {
		$target_url = trailingslashit( $pair[1] );
		$exists     = false;
		foreach ( $urls as $u ) {
			if ( $u === $target_url || strpos( $u, $target_url ) === 0 || strpos( $target_url, $u ) === 0 ) {
				$exists = true;
				break;
			}
		}
		if ( ! $exists ) {
			wp_update_nav_menu_item( $menu_id, 0, array(
				'menu-item-title'  => $pair[0],
				'menu-item-url'    => $pair[1],
				'menu-item-status' => 'publish',
				'menu-item-type'   => 'custom',
			) );
		}
	}
	update_option( 'mnsk7_menu_landings_seeded', 1 );
}, 20 );
