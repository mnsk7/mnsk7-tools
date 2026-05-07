/**
 * Homepage #bestsellery: swipe row + chevron stepped scroll (no deps).
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		var rail = document.querySelector('#bestsellery.mnsk7-section--bestsellers .mnsk7-bestsellers-strip-rail');
		if (!rail) {
			return;
		}
		var scroller = rail.querySelector('ul.products');
		var prev = rail.querySelector('.mnsk7-bestsellers-strip-rail__chev--prev');
		var next = rail.querySelector('.mnsk7-bestsellers-strip-rail__chev--next');
		if (!scroller || !prev || !next) {
			return;
		}

		var focusableSelector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]';
		var focusables = Array.prototype.slice.call(scroller.querySelectorAll(focusableSelector));

		function setFocusableState(el, enabled) {
			if (!el.dataset.mnsk7BestsellerTabindexSaved) {
				el.dataset.mnsk7BestsellerTabindexSaved = '1';
				if (el.hasAttribute('tabindex')) {
					el.dataset.mnsk7BestsellerTabindex = el.getAttribute('tabindex');
				}
			}
			if (enabled) {
				if (el.dataset.mnsk7BestsellerTabindex !== undefined) {
					el.setAttribute('tabindex', el.dataset.mnsk7BestsellerTabindex);
				} else {
					el.removeAttribute('tabindex');
				}
			} else {
				el.setAttribute('tabindex', '-1');
			}
		}

		function syncTabStops() {
			var scrollerRect = scroller.getBoundingClientRect();
			var left = Math.max(scrollerRect.left, 0) - 1;
			var right = Math.min(scrollerRect.right, window.innerWidth || document.documentElement.clientWidth) + 1;
			focusables.forEach(function(el) {
				var rect = el.getBoundingClientRect();
				var enabled = rect.width > 0 && rect.height > 0 && rect.left >= left && rect.right <= right;
				setFocusableState(el, enabled);
			});
		}

		function scrollStep(direction) {
			var amount = Math.max(160, Math.round(scroller.clientWidth * 0.82));
			scroller.scrollBy({ left: direction * amount, behavior: 'smooth' });
			window.setTimeout(syncTabStops, 260);
		}

		prev.addEventListener('click', function () {
			scrollStep(-1);
		});
		next.addEventListener('click', function () {
			scrollStep(1);
		});
		scroller.addEventListener('scroll', function() {
			window.requestAnimationFrame(syncTabStops);
		}, { passive: true });
		window.addEventListener('resize', syncTabStops, { passive: true });
		syncTabStops();
	});
})();
