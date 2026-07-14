/**
 * PLP table: open product images in a large lightbox with per-row gallery nav.
 */
(function () {
	'use strict';

	var dialog = document.getElementById('mnsk7-plp-thumb-dialog');
	if (!dialog || typeof dialog.showModal !== 'function') {
		return;
	}

	var img = dialog.querySelector('.mnsk7-plp-thumb-dialog__img');
	var closeBtn = dialog.querySelector('.mnsk7-plp-thumb-dialog__close');
	var prevBtn = dialog.querySelector('.mnsk7-plp-thumb-dialog__nav--prev');
	var nextBtn = dialog.querySelector('.mnsk7-plp-thumb-dialog__nav--next');
	var counter = dialog.querySelector('.mnsk7-plp-thumb-dialog__counter');
	var gallery = [];
	var currentIndex = 0;

	if (!img) {
		return;
	}

	function readGallery(btn) {
		var raw = btn.getAttribute('data-gallery');
		var src = btn.getAttribute('data-full-src');
		var parsed = [];

		if (raw) {
			try {
				parsed = JSON.parse(raw);
			} catch (err) {
				parsed = [];
			}
		}

		parsed = Array.isArray(parsed) ? parsed.filter(function (item) {
			return item && typeof item.src === 'string' && item.src.length > 0;
		}) : [];

		if (parsed.length < 1 && src) {
			parsed.push({
				src: src,
				alt: ''
			});
		}

		return parsed;
	}

	function renderImage(index) {
		if (!gallery.length) {
			return;
		}

		currentIndex = (index + gallery.length) % gallery.length;
		img.src = gallery[currentIndex].src;
		img.alt = gallery[currentIndex].alt || '';

		var hasMultiple = gallery.length > 1;
		dialog.classList.toggle('has-multiple', hasMultiple);

		if (prevBtn) {
			prevBtn.hidden = !hasMultiple;
			prevBtn.disabled = !hasMultiple;
		}
		if (nextBtn) {
			nextBtn.hidden = !hasMultiple;
			nextBtn.disabled = !hasMultiple;
		}
		if (counter) {
			counter.hidden = !hasMultiple;
			counter.textContent = hasMultiple ? String(currentIndex + 1) + ' / ' + String(gallery.length) : '';
		}
	}

	function openFromButton(btn) {
		gallery = readGallery(btn);
		if (!gallery.length) {
			return;
		}

		var innerImg = btn.querySelector('img');
		if (innerImg && !gallery[0].alt) {
			gallery[0].alt = innerImg.getAttribute('alt') || '';
		}

		renderImage(0);
		dialog.showModal();
	}

	document.addEventListener(
		'click',
		function (e) {
			var btn = e.target.closest('.mnsk7-table-thumb-zoom');
			if (!btn) {
				return;
			}
			e.preventDefault();
			openFromButton(btn);
		},
		true
	);

	if (prevBtn) {
		prevBtn.addEventListener('click', function () {
			renderImage(currentIndex - 1);
		});
	}

	if (nextBtn) {
		nextBtn.addEventListener('click', function () {
			renderImage(currentIndex + 1);
		});
	}

	dialog.addEventListener('click', function (e) {
		if (e.target === dialog) {
			dialog.close();
		}
	});

	dialog.addEventListener('keydown', function (e) {
		if (!gallery.length || gallery.length < 2) {
			return;
		}
		if (e.key === 'ArrowLeft') {
			e.preventDefault();
			renderImage(currentIndex - 1);
		}
		if (e.key === 'ArrowRight') {
			e.preventDefault();
			renderImage(currentIndex + 1);
		}
	});

	if (closeBtn) {
		closeBtn.addEventListener('click', function () {
			dialog.close();
		});
	}

	dialog.addEventListener('close', function () {
		img.removeAttribute('src');
		img.alt = '';
		gallery = [];
		currentIndex = 0;
		if (counter) {
			counter.textContent = '';
		}
	});
})();
