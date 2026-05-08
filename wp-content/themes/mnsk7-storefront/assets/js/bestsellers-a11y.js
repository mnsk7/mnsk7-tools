/**
 * Homepage bestsellers: prevent keyboard focus from entering clipped cards.
 */
(function () {
	document.addEventListener('DOMContentLoaded', function () {
		var scroller = document.querySelector('#bestsellery.mnsk7-section--bestsellers ul.products');
		if (!scroller) {
			return;
		}
		var selector = 'a[href], button:not([disabled]), input:not([disabled]), select:not([disabled]), textarea:not([disabled]), [tabindex]';

		function setTabStop(el, enabled) {
			if (!el.dataset.mnsk7BestsellerA11ySaved) {
				el.dataset.mnsk7BestsellerA11ySaved = '1';
				if (el.hasAttribute('tabindex')) {
					el.dataset.mnsk7BestsellerA11yTabindex = el.getAttribute('tabindex');
				}
			}
			if (enabled) {
				if (el.dataset.mnsk7BestsellerA11yTabindex !== undefined) {
					el.setAttribute('tabindex', el.dataset.mnsk7BestsellerA11yTabindex);
				} else {
					el.removeAttribute('tabindex');
				}
			} else {
				el.setAttribute('tabindex', '-1');
			}
		}

		function sync() {
			var rail = scroller.getBoundingClientRect();
			var min = Math.max(rail.left, 0) - 1;
			var max = Math.min(rail.right, window.innerWidth || document.documentElement.clientWidth) + 1;
			scroller.querySelectorAll(selector).forEach(function (el) {
				var rect = el.getBoundingClientRect();
				var enabled = rect.width > 0 && rect.height > 0 && rect.left >= min && rect.right <= max;
				setTabStop(el, enabled);
			});
		}

		scroller.addEventListener('scroll', function () {
			window.requestAnimationFrame(sync);
		}, { passive: true });
		window.addEventListener('resize', sync, { passive: true });
		try {
			new MutationObserver(sync).observe(scroller, { childList: true, subtree: true, attributes: true, attributeFilter: ['class', 'style'] });
		} catch (e) {}
		sync();
		window.setTimeout(sync, 400);
		window.setTimeout(sync, 1200);
	});
})();
