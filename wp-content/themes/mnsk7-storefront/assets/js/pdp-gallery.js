/**
 * PDP gallery — "magazine" page-flip viewer (desktop + mobile).
 *
 * WooCommerce flexslider and hover-zoom are disabled (functions.php removes
 * `wc-product-gallery-slider` and `wc-product-gallery-zoom`), so the gallery
 * markup stays clean: a wrapper with N `.woocommerce-product-gallery__image`
 * nodes. We turn that wrapper into a stacked "deck" and animate a book-like
 * page turn (rotateY around the left edge) when moving between images.
 *
 * Behaviour:
 *  - prev/next arrows on the main image,
 *  - thumbnail click jumps to that page (with flip),
 *  - swipe left/right on touch flips the page,
 *  - "i / n" page indicator,
 *  - thumbnails scroll cleanly (no raw scrollbar) with desktop chevrons,
 *  - PhotoSwipe lightbox stays intact (WooCommerce binds it to the real
 *    `.woocommerce-product-gallery__image a` links) but only a genuine TAP
 *    opens it — a swipe/drag past a small threshold is suppressed so the
 *    fullscreen viewer never pops open mid-swipe,
 *  - variable products: when WooCommerce (or a variation-gallery plugin) swaps
 *    the variation image set it fires `woocommerce_gallery_reset_slide_position`
 *    / `found_variation` and may re-render the whole wrapper; we re-enhance the
 *    gallery so the thumbnail rail + counter + arrows always come back for the
 *    new image set (idempotently — a single top rail, never a duplicate),
 *  - prefers-reduced-motion: the flip degrades to a simple crossfade.
 */
(function () {
	'use strict';

	var DUR = 420;
	var EASE = 'cubic-bezier(0.22, 0.61, 0.20, 1)';
	var FLIP_OUT = 'rotateY(-118deg)';
	// Movement (px) beyond which a pointer gesture counts as a swipe/drag, not a
	// tap — used to suppress the accidental lightbox open while swiping.
	var TAP_SLOP = 10;
	var prefersReduced = window.matchMedia
		? window.matchMedia('(prefers-reduced-motion: reduce)').matches
		: false;

	function svgIcon(d) {
		return '<svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" ' +
			'stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">' +
			'<path d="' + d + '"/></svg>';
	}

	function onTransitionEnd(el, prop, cb) {
		var done = false;
		function handler(e) {
			if (e && e.propertyName && e.propertyName !== prop) {
				return;
			}
			finish();
		}
		function finish() {
			if (done) {
				return;
			}
			done = true;
			el.removeEventListener('transitionend', handler);
			cb();
		}
		el.addEventListener('transitionend', handler);
		window.setTimeout(finish, DUR + 140);
	}

	function thumbSrc(page) {
		var ds = page.getAttribute('data-thumb');
		if (ds) {
			return ds;
		}
		var img = page.querySelector('img');
		if (img) {
			return img.getAttribute('src') || img.currentSrc || '';
		}
		return '';
	}

	function pageAlt(page) {
		var img = page.querySelector('img');
		return img ? (img.getAttribute('alt') || '') : '';
	}

	function purgeLegacyGalleryChrome(gallery) {
		gallery.querySelectorAll('.flex-control-thumbs, .flex-direction-nav').forEach(function (el) {
			if (el.parentNode) {
				el.parentNode.removeChild(el);
			}
		});
	}

	function clearPageStyles(page) {
		page.style.transition = '';
		page.style.transform = '';
		page.style.opacity = '';
		page.style.visibility = '';
		page.style.zIndex = '';
		page.style.pointerEvents = '';
		page.classList.remove('mnsk7-pg', 'is-current', 'is-turning');
	}

	/**
	 * Enhance one set of gallery pages. Returns a `destroy()` that removes every
	 * listener and injected node so the gallery can be rebuilt cleanly when a
	 * variation swaps the image set.
	 */
	function build(gallery, wrapper, pages) {
		var n = pages.length;
		var current = 0;
		var animating = false;

		gallery.classList.add('mnsk7-gallery--enhanced');
		gallery.setAttribute('aria-roledescription', 'Galeria zdjęć produktu');

		// --- Deck: stack the real Woo image nodes and show only the first. ---
		pages.forEach(function (page, i) {
			page.classList.add('mnsk7-pg');
			resetPage(page, i === 0);
			var img = page.querySelector('img');
			if (img) {
				if (i === 0) {
					img.setAttribute('loading', 'eager');
					img.setAttribute('fetchpriority', 'high');
				} else if (!img.getAttribute('loading')) {
					img.setAttribute('loading', 'lazy');
				}
			}
		});

		// --- Navigation arrows + page counter (overlay the main image). ---
		var prevBtn = document.createElement('button');
		prevBtn.type = 'button';
		prevBtn.className = 'mnsk7-gallery-nav mnsk7-gallery-nav--prev';
		prevBtn.setAttribute('aria-label', 'Poprzednie zdjęcie');
		prevBtn.innerHTML = svgIcon('M15 18l-6-6 6-6');

		var nextBtn = document.createElement('button');
		nextBtn.type = 'button';
		nextBtn.className = 'mnsk7-gallery-nav mnsk7-gallery-nav--next';
		nextBtn.setAttribute('aria-label', 'Następne zdjęcie');
		nextBtn.innerHTML = svgIcon('M9 6l6 6-6 6');

		var counter = document.createElement('div');
		counter.className = 'mnsk7-gallery-counter';
		counter.setAttribute('aria-live', 'polite');

		wrapper.appendChild(prevBtn);
		wrapper.appendChild(nextBtn);
		wrapper.appendChild(counter);

		function onPrev(e) {
			e.preventDefault();
			goTo(current - 1);
		}
		function onNext(e) {
			e.preventDefault();
			goTo(current + 1);
		}
		prevBtn.addEventListener('click', onPrev);
		nextBtn.addEventListener('click', onNext);

		// --- Thumbnails (no raw scrollbar; desktop chevrons on overflow). ---
		var thumbsWrap = document.createElement('div');
		thumbsWrap.className = 'mnsk7-gallery-thumbs';

		var track = document.createElement('div');
		track.className = 'mnsk7-gallery-thumbs__track';
		track.setAttribute('role', 'tablist');
		track.setAttribute('aria-label', 'Miniatury zdjęć produktu');

		var thumbs = [];
		pages.forEach(function (page, i) {
			var btn = document.createElement('button');
			btn.type = 'button';
			btn.className = 'mnsk7-gallery-thumb';
			btn.setAttribute('role', 'tab');
			btn.setAttribute('aria-label', 'Pokaż zdjęcie ' + (i + 1) + ' z ' + n);
			var img = document.createElement('img');
			img.src = thumbSrc(page);
			img.alt = pageAlt(page);
			img.loading = 'lazy';
			img.decoding = 'async';
			btn.appendChild(img);
			btn.addEventListener('click', function () {
				goTo(i);
			});
			track.appendChild(btn);
			thumbs.push(btn);
		});

		var chevPrev = document.createElement('button');
		chevPrev.type = 'button';
		chevPrev.className = 'mnsk7-gallery-thumbs__chevron mnsk7-gallery-thumbs__chevron--prev';
		chevPrev.setAttribute('aria-label', 'Przewiń miniatury w lewo');
		chevPrev.innerHTML = svgIcon('M15 18l-6-6 6-6');

		var chevNext = document.createElement('button');
		chevNext.type = 'button';
		chevNext.className = 'mnsk7-gallery-thumbs__chevron mnsk7-gallery-thumbs__chevron--next';
		chevNext.setAttribute('aria-label', 'Przewiń miniatury w prawo');
		chevNext.innerHTML = svgIcon('M9 6l6 6-6 6');

		thumbsWrap.appendChild(chevPrev);
		thumbsWrap.appendChild(track);
		thumbsWrap.appendChild(chevNext);

		// Thumbs above the main stage (wrapper, or flex-viewport if flexslider ran first).
		var stage = wrapper.parentElement && wrapper.parentElement.classList.contains('flex-viewport')
			? wrapper.parentElement
			: wrapper;
		gallery.insertBefore(thumbsWrap, stage);

		purgeLegacyGalleryChrome(gallery);

		chevPrev.addEventListener('click', function () {
			track.scrollBy({ left: -track.clientWidth * 0.8, behavior: 'smooth' });
		});
		chevNext.addEventListener('click', function () {
			track.scrollBy({ left: track.clientWidth * 0.8, behavior: 'smooth' });
		});

		function syncChevrons() {
			var scrollable = track.scrollWidth - track.clientWidth > 2;
			thumbsWrap.classList.toggle('is-scrollable', scrollable);
			if (!scrollable) {
				return;
			}
			chevPrev.disabled = track.scrollLeft <= 1;
			chevNext.disabled = track.scrollLeft >= track.scrollWidth - track.clientWidth - 1;
		}
		var chevTick = false;
		track.addEventListener('scroll', function () {
			if (!chevTick) {
				chevTick = true;
				window.requestAnimationFrame(function () {
					syncChevrons();
					chevTick = false;
				});
			}
		}, { passive: true });
		window.addEventListener('resize', syncChevrons, { passive: true });

		function scrollThumbIntoView(i) {
			var btn = thumbs[i];
			if (!btn) {
				return;
			}
			var left = btn.offsetLeft - track.offsetLeft - (track.clientWidth / 2) + (btn.clientWidth / 2);
			track.scrollTo({ left: Math.max(0, left), behavior: prefersReduced ? 'auto' : 'smooth' });
		}

		// --- State helpers --------------------------------------------------
		function resetPage(page, isCurrent) {
			page.style.transition = 'none';
			page.style.transform = 'none';
			page.style.opacity = isCurrent ? '1' : '0';
			page.style.visibility = isCurrent ? 'visible' : 'hidden';
			page.style.zIndex = isCurrent ? '2' : '0';
			page.style.pointerEvents = isCurrent ? 'auto' : 'none';
			page.classList.toggle('is-current', !!isCurrent);
		}

		function updateChrome(index) {
			counter.textContent = (index + 1) + ' / ' + n;
			prevBtn.disabled = index <= 0;
			nextBtn.disabled = index >= n - 1;
			thumbs.forEach(function (btn, i) {
				var active = i === index;
				btn.classList.toggle('is-active', active);
				btn.setAttribute('aria-selected', active ? 'true' : 'false');
				if (active) {
					btn.setAttribute('aria-current', 'true');
				} else {
					btn.removeAttribute('aria-current');
				}
			});
			scrollThumbIntoView(index);
		}

		function goTo(target) {
			if (target < 0) {
				target = 0;
			}
			if (target > n - 1) {
				target = n - 1;
			}
			if (target === current || animating) {
				return;
			}

			var from = pages[current];
			var to = pages[target];
			var forward = target > current;
			animating = true;
			updateChrome(target);

			if (prefersReduced) {
				// Crossfade fallback.
				to.style.transition = 'none';
				to.style.transform = 'none';
				to.style.visibility = 'visible';
				to.style.zIndex = '3';
				to.style.pointerEvents = 'none';
				to.style.opacity = '0';
				void to.offsetWidth;
				to.style.transition = 'opacity 0.25s ease';
				to.style.opacity = '1';
				onTransitionEnd(to, 'opacity', function () {
					resetPage(from, false);
					resetPage(to, true);
					current = target;
					animating = false;
				});
				return;
			}

			if (forward) {
				// The current page peels away to the left, revealing the next.
				to.style.transition = 'none';
				to.style.transform = 'none';
				to.style.opacity = '1';
				to.style.visibility = 'visible';
				to.style.zIndex = '1';
				to.style.pointerEvents = 'none';

				from.style.transition = 'none';
				from.style.transform = 'rotateY(0deg)';
				from.style.zIndex = '3';
				from.classList.add('is-turning');
				void from.offsetWidth;
				from.style.transition = 'transform ' + DUR + 'ms ' + EASE;
				from.style.transform = FLIP_OUT;

				onTransitionEnd(from, 'transform', function () {
					from.classList.remove('is-turning');
					resetPage(from, false);
					resetPage(to, true);
					current = target;
					animating = false;
				});
			} else {
				// The target page swings back onto the stack from the left.
				from.style.transition = 'none';
				from.style.transform = 'none';
				from.style.zIndex = '1';
				from.style.pointerEvents = 'none';
				from.classList.remove('is-current');

				to.style.transition = 'none';
				to.style.transform = FLIP_OUT;
				to.style.opacity = '1';
				to.style.visibility = 'visible';
				to.style.zIndex = '3';
				to.style.pointerEvents = 'none';
				to.classList.add('is-turning');
				void to.offsetWidth;
				to.style.transition = 'transform ' + DUR + 'ms ' + EASE;
				to.style.transform = 'rotateY(0deg)';

				onTransitionEnd(to, 'transform', function () {
					to.classList.remove('is-turning');
					resetPage(from, false);
					resetPage(to, true);
					current = target;
					animating = false;
				});
			}
		}

		// --- Swipe / tap discrimination -------------------------------------
		// A single pointer drag past TAP_SLOP flips the page AND is treated as a
		// swipe: the trailing `click` (which WooCommerce uses to open the
		// PhotoSwipe lightbox) is cancelled so the fullscreen viewer never opens
		// by accident. A genuine tap (tiny movement) still opens the lightbox.
		var startX = 0;
		var startY = 0;
		var startT = 0;
		var pointerSeen = false;
		var swipeHandled = false;

		function recordStart(x, y) {
			startX = x;
			startY = y;
			startT = Date.now();
			pointerSeen = true;
			swipeHandled = false;
		}

		function onPointerDown(e) {
			// Ignore multi-touch / non-primary buttons.
			if (e.button && e.button !== 0) {
				return;
			}
			recordStart(e.clientX, e.clientY);
		}
		function onTouchStart(e) {
			if (e.touches.length !== 1) {
				pointerSeen = false;
				return;
			}
			recordStart(e.touches[0].clientX, e.touches[0].clientY);
		}
		function onTouchEnd(e) {
			var t = e.changedTouches && e.changedTouches[0];
			if (!t) {
				return;
			}
			var dx = t.clientX - startX;
			var dy = t.clientY - startY;
			var dt = Date.now() - startT;
			// Clear, fast horizontal swipe flips the page; let vertical gestures
			// scroll the page.
			if (dt < 700 && Math.abs(dx) > 42 && Math.abs(dx) > Math.abs(dy) * 1.4) {
				swipeHandled = true;
				goTo(dx < 0 ? current + 1 : current - 1);
			}
		}

		// Capture-phase click guard: runs before WooCommerce's delegated
		// lightbox handler (and before the link's own handler), so cancelling
		// here reliably blocks the lightbox after a swipe/drag.
		function onClickCapture(e) {
			if (!pointerSeen) {
				return;
			}
			pointerSeen = false;
			var dx = e.clientX - startX;
			var dy = e.clientY - startY;
			var movedFar = Math.sqrt(dx * dx + dy * dy) > TAP_SLOP;
			if (movedFar || swipeHandled) {
				e.preventDefault();
				e.stopPropagation();
				if (e.stopImmediatePropagation) {
					e.stopImmediatePropagation();
				}
			}
			swipeHandled = false;
		}

		var hasPointer = 'PointerEvent' in window;
		if (hasPointer) {
			wrapper.addEventListener('pointerdown', onPointerDown, true);
		} else {
			wrapper.addEventListener('mousedown', onPointerDown, true);
		}
		wrapper.addEventListener('touchstart', onTouchStart, { passive: true });
		wrapper.addEventListener('touchend', onTouchEnd, { passive: true });
		wrapper.addEventListener('click', onClickCapture, true);

		// --- Keyboard -------------------------------------------------------
		function onKeydown(e) {
			if (e.key === 'ArrowRight') {
				goTo(current + 1);
			} else if (e.key === 'ArrowLeft') {
				goTo(current - 1);
			}
		}
		gallery.addEventListener('keydown', onKeydown);

		// --- Init -----------------------------------------------------------
		updateChrome(0);
		window.setTimeout(syncChevrons, 60);
		window.setTimeout(syncChevrons, 600);
		window.setTimeout(function () { purgeLegacyGalleryChrome(gallery); }, 0);
		window.setTimeout(function () { purgeLegacyGalleryChrome(gallery); }, 120);

		return function destroy() {
			window.removeEventListener('resize', syncChevrons);
			gallery.removeEventListener('keydown', onKeydown);
			if (hasPointer) {
				wrapper.removeEventListener('pointerdown', onPointerDown, true);
			} else {
				wrapper.removeEventListener('mousedown', onPointerDown, true);
			}
			wrapper.removeEventListener('touchstart', onTouchStart);
			wrapper.removeEventListener('touchend', onTouchEnd);
			wrapper.removeEventListener('click', onClickCapture, true);
			[prevBtn, nextBtn, counter, thumbsWrap].forEach(function (el) {
				if (el && el.parentNode) {
					el.parentNode.removeChild(el);
				}
			});
			pages.forEach(clearPageStyles);
			gallery.classList.remove('mnsk7-gallery--enhanced');
		};
	}

	function listHasGalleryImage(nodeList) {
		if (!nodeList || !nodeList.length) {
			return false;
		}
		for (var i = 0; i < nodeList.length; i++) {
			var node = nodeList[i];
			if (node.nodeType !== 1) {
				continue;
			}
			if (node.classList && node.classList.contains('woocommerce-product-gallery__image')) {
				return true;
			}
			if (node.querySelector && node.querySelector('.woocommerce-product-gallery__image')) {
				return true;
			}
		}
		return false;
	}

	/**
	 * Controller: builds the enhanced gallery and rebuilds it (idempotently)
	 * whenever the variation image set changes or a plugin re-renders the
	 * gallery wrapper, so the thumbnail rail always reappears for the variation.
	 */
	function enhance(gallery) {
		var observer = null;
		var destroyChrome = null;
		var tick = false;

		function removeStaleChrome() {
			gallery.querySelectorAll('.mnsk7-gallery-thumbs, .mnsk7-gallery-nav, .mnsk7-gallery-counter')
				.forEach(function (el) {
					if (el.parentNode) {
						el.parentNode.removeChild(el);
					}
				});
			gallery.classList.remove('mnsk7-gallery--enhanced');
		}

		function rebuild() {
			if (observer) {
				observer.disconnect();
			}
			try {
				if (destroyChrome) {
					destroyChrome();
					destroyChrome = null;
				}
				// Belt-and-braces: drop any rail left behind by a previous init.
				removeStaleChrome();

				var wrapper = gallery.querySelector('.woocommerce-product-gallery__wrapper');
				if (!wrapper) {
					return;
				}
				var pages = Array.prototype.slice.call(
					wrapper.querySelectorAll(':scope > .woocommerce-product-gallery__image')
				);
				if (pages.length < 2) {
					gallery.classList.add('mnsk7-gallery--single');
					purgeLegacyGalleryChrome(gallery);
					return;
				}
				gallery.classList.remove('mnsk7-gallery--single');
				destroyChrome = build(gallery, wrapper, pages);
			} finally {
				if (observer) {
					observer.observe(gallery, { childList: true, subtree: true });
				}
			}
		}

		function schedule() {
			if (tick) {
				return;
			}
			tick = true;
			window.setTimeout(function () {
				tick = false;
				rebuild();
			}, 60);
		}

		rebuild();

		// Re-render detection: a variation-gallery plugin swapping the image set
		// adds/removes `.woocommerce-product-gallery__image` nodes. Our own
		// chrome (rail/nav/counter) is ignored so we never loop.
		if (window.MutationObserver) {
			observer = new MutationObserver(function (mutations) {
				for (var i = 0; i < mutations.length; i++) {
					var m = mutations[i];
					if (listHasGalleryImage(m.addedNodes) || listHasGalleryImage(m.removedNodes)) {
						schedule();
						return;
					}
				}
			});
			observer.observe(gallery, { childList: true, subtree: true });
		}

		if (window.jQuery) {
			try {
				var $gallery = window.jQuery(gallery);
				$gallery.on('woocommerce_gallery_reset_slide_position woocommerce_gallery_init_slider', schedule);
				$gallery.closest('.product').find('.variations_form')
					.on('found_variation reset_data hide_variation', schedule);
			} catch (err) {}
		} else {
			// No jQuery: watch the first image's src for variation swaps.
			var firstImg = gallery.querySelector('.woocommerce-product-gallery__image img');
			if (firstImg && window.MutationObserver) {
				new MutationObserver(schedule).observe(firstImg, {
					attributes: true,
					attributeFilter: ['src', 'srcset']
				});
			}
		}
	}

	function init() {
		var gallery = document.querySelector('.single-product .woocommerce-product-gallery');
		if (!gallery) {
			return;
		}
		enhance(gallery);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', init);
	} else {
		init();
	}
})();
