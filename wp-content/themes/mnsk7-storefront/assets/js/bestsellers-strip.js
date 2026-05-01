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

		function scrollStep(direction) {
			var amount = Math.max(160, Math.round(scroller.clientWidth * 0.82));
			scroller.scrollBy({ left: direction * amount, behavior: 'smooth' });
		}

		prev.addEventListener('click', function () {
			scrollStep(-1);
		});
		next.addEventListener('click', function () {
			scrollStep(1);
		});
	});
})();
