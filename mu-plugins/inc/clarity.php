<?php
/**
 * Microsoft Clarity, loaded only after the visitor opts in to analytics cookies.
 *
 * The cookie banner persists its choice in `mnsk7_cookie_consent` and emits the
 * `mnsk7-cookie-consent` event for a choice made on the current page.
 */

defined( 'ABSPATH' ) || exit;

add_action( 'wp_footer', function () {
	if ( is_admin() || wp_doing_ajax() ) {
		return;
	}
	?>
	<script>
	(function () {
		var projectId = 'xbmo9cjlkv';
		var loaded = false;

		function hasAnalyticsConsent() {
			try {
				if (window.localStorage && window.localStorage.getItem('mnsk7_cookie_consent') === 'accept') {
					return true;
				}
			} catch (e) {}
			return /(?:^|;\s*)mnsk7_cookie_consent=accept(?:;|$)/.test(document.cookie || '');
		}

		function loadClarity() {
			if (loaded || window.clarity) return;
			loaded = true;
			(function (c, l, a, r, i, t, y) {
				c[a] = c[a] || function () { (c[a].q = c[a].q || []).push(arguments); };
				t = l.createElement(r); t.async = 1; t.src = 'https://www.clarity.ms/tag/' + i;
				y = l.getElementsByTagName(r)[0]; y.parentNode.insertBefore(t, y);
			})(window, document, 'clarity', 'script', projectId);
		}

		if (hasAnalyticsConsent()) loadClarity();
		document.addEventListener('mnsk7-cookie-consent', function (event) {
			if (event && event.detail === 'accept') loadClarity();
		});
	})();
	</script>
	<?php
}, 20 );
