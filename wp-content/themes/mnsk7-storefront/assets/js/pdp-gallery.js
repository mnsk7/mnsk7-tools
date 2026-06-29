/**
 * PDP gallery enhancements.
 *
 * - Mobile: the gallery wrapper is a native horizontal scroll-snap container
 *   (see 06-single-product.css). We add tappable dots that reflect / control
 *   the current image. Lightbox + zoom stay handled by WooCommerce PhotoSwipe.
 * - Desktop: WooCommerce flexslider renders the thumbnail strip (re-enabled in
 *   CSS), so no JS is needed there.
 */
(function () {
	'use strict';

	function init() {
		var gallery = document.querySelector('.single-product .woocommerce-product-gallery');
		if (!gallery) {
			return;
		}

		var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
		if (!wrapper) {
			return;
		}

		var images = wrapper.querySelectorAll('.woocommerce-product-gallery__image');
		if (images.length < 2) {
			return;
		}

		var dotsList = document.createElement('ul');
		dotsList.className = 'mnsk7-gallery-dots';
		dotsList.setAttribute('role', 'tablist');
		dotsList.setAttribute('aria-label', 'Miniatury zdjęć produktu');

		var dots = [];
		for (var i = 0; i < images.length; i++) {
			(function (index) {
				var li = document.createElement('li');
				var btn = document.createElement('button');
				btn.type = 'button';
				btn.className = 'mnsk7-gallery-dots__dot';
				btn.setAttribute('aria-label', 'Pokaż zdjęcie ' + (index + 1));
				btn.addEventListener('click', function () {
					var target = images[index];
					if (target) {
						wrapper.scrollTo({ left: target.offsetLeft - wrapper.offsetLeft, behavior: 'smooth' });
					}
				});
				li.appendChild(btn);
				dotsList.appendChild(li);
				dots.push(btn);
			})(i);
		}

		gallery.appendChild(dotsList);

		function setActive(index) {
			for (var i = 0; i < dots.length; i++) {
				if (i === index) {
					dots[i].classList.add('is-active');
					dots[i].setAttribute('aria-current', 'true');
				} else {
					dots[i].classList.remove('is-active');
					dots[i].removeAttribute('aria-current');
				}
			}
		}

		function syncActive() {
			var width = wrapper.clientWidth || 1;
			var index = Math.round(wrapper.scrollLeft / width);
			if (index < 0) {
				index = 0;
			}
			if (index > dots.length - 1) {
				index = dots.length - 1;
			}
			setActive(index);
		}

		var ticking = false;
		wrapper.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(function () {
					syncActive();
					ticking = false;
				});
			}
		}, { passive: true });

		window.addEventListener('resize', syncActive, { passive: true });
		setActive(0);
		window.setTimeout(syncActive, 300);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
