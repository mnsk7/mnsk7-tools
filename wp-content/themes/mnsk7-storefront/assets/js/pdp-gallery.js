/**
 * PDP gallery enhancements.
 *
 * - Mobile: native horizontal scroll-snap on the main wrapper (CSS) plus a
 *   scrollable thumbnail strip when Woo renders flex-control-thumbs. Dots are
 *   only added when thumbs are absent.
 * - Desktop: WooCommerce flexslider thumbnail strip (CSS) + zoom/lightbox hint.
 */
(function () {
	'use strict';

	function hasThumbStrip(gallery) {
		var thumbs = gallery.querySelector('.flex-control-thumbs');
		return !!(thumbs && thumbs.children.length);
	}

	function initDots(gallery, wrapper, images) {
		if (hasThumbStrip(gallery)) {
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

	function initThumbStripSync(gallery, wrapper) {
		var thumbs = gallery.querySelector('.flex-control-thumbs');
		if (!thumbs) {
			return;
		}

		var thumbItems = thumbs.querySelectorAll('li');
		if (!thumbItems.length) {
			return;
		}

		function scrollThumbIntoView(item) {
			if (!item || !thumbs) {
				return;
			}
			var left = item.offsetLeft - thumbs.offsetLeft - (thumbs.clientWidth / 2) + (item.clientWidth / 2);
			thumbs.scrollTo({ left: Math.max(0, left), behavior: 'smooth' });
		}

		thumbs.addEventListener('click', function (event) {
			var item = event.target.closest('li');
			if (!item) {
				return;
			}
			scrollThumbIntoView(item);
		});

		if (!wrapper) {
			return;
		}

		function syncFromMain() {
			var width = wrapper.clientWidth || 1;
			var index = Math.round(wrapper.scrollLeft / width);
			if (index < 0) {
				index = 0;
			}
			if (index > thumbItems.length - 1) {
				index = thumbItems.length - 1;
			}
			for (var i = 0; i < thumbItems.length; i++) {
				thumbItems[i].classList.toggle('flex-active', i === index);
			}
			scrollThumbIntoView(thumbItems[index]);
		}

		var ticking = false;
		wrapper.addEventListener('scroll', function () {
			if (!ticking) {
				ticking = true;
				window.requestAnimationFrame(function () {
					syncFromMain();
					ticking = false;
				});
			}
		}, { passive: true });
	}

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

		initThumbStripSync(gallery, wrapper);
		initDots(gallery, wrapper, images);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
