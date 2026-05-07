/**
 * PDP gallery: keep keyboard focus on visible mobile/slider images only.
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		var gallery = document.querySelector('.single-product .woocommerce-product-gallery');
		var wrapper = gallery ? gallery.querySelector('.woocommerce-product-gallery__wrapper') : null;
		if (!gallery || !wrapper) {
			return;
		}

		function setTabStop(link, enabled) {
			if (!link.dataset.mnsk7GalleryTabindexSaved) {
				link.dataset.mnsk7GalleryTabindexSaved = '1';
				if (link.hasAttribute('tabindex')) {
					link.dataset.mnsk7GalleryTabindex = link.getAttribute('tabindex');
				}
			}
			if (enabled) {
				if (link.dataset.mnsk7GalleryTabindex !== undefined) {
					link.setAttribute('tabindex', link.dataset.mnsk7GalleryTabindex);
				} else {
					link.removeAttribute('tabindex');
				}
			} else {
				link.setAttribute('tabindex', '-1');
			}
		}

		function syncGalleryTabStops() {
			var viewport = wrapper.getBoundingClientRect();
			wrapper.querySelectorAll('.woocommerce-product-gallery__image a[href]').forEach(function (link) {
				var rect = link.getBoundingClientRect();
				var visible = rect.width > 0 && rect.height > 0 && rect.left >= viewport.left - 1 && rect.right <= viewport.right + 1;
				setTabStop(link, visible);
			});
		}

		wrapper.addEventListener('scroll', function () {
			window.requestAnimationFrame(syncGalleryTabStops);
		}, { passive: true });
		window.addEventListener('resize', syncGalleryTabStops, { passive: true });
		try {
			new MutationObserver(syncGalleryTabStops).observe(wrapper, {
				childList: true,
				subtree: true,
				attributes: true,
				attributeFilter: ['style', 'class']
			});
		} catch (e) {}
		syncGalleryTabStops();
		window.setTimeout(syncGalleryTabStops, 400);
		window.setTimeout(syncGalleryTabStops, 1200);
	});
})();
