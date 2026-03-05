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

/* Cookie consent bar */
add_action( 'wp_footer', function () {
	if ( is_admin() ) return;
	?>
	<div id="mnsk7-cookie-bar" class="mnsk7-cookie-bar" role="dialog" aria-label="<?php esc_attr_e( 'Informacja o cookies', 'mnsk7-tools' ); ?>" hidden>
		<div class="mnsk7-cookie-bar__inner">
			<p class="mnsk7-cookie-bar__text"><?php esc_html_e( 'Ta strona używa plików cookie. Kliknij „Przyjmuję", aby kontynuować.', 'mnsk7-tools' ); ?></p>
			<button type="button" class="mnsk7-cookie-bar__btn" id="mnsk7-cookie-bar-accept"><?php esc_html_e( 'Przyjmuję', 'mnsk7-tools' ); ?></button>
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
		bar.removeAttribute('hidden');
		document.getElementById('mnsk7-cookie-bar-accept').addEventListener('click',function(){set(key,'1',365);bar.remove();});
	})();
	</script>
	<?php
}, 99 );
