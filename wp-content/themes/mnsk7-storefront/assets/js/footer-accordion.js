/**
 * Mobile footer accordion — mnsk7-storefront.
 * Odseparowany plik, żeby minify/combine nie mieszał z innymi skryptami (unikamy "Unexpected token '<'").
 * Try-catch + retry gdy #colophon jeszcze nie w DOM.
 */
(function() {
	'use strict';
	var FOOTER_ACCORDION_BREAKPOINT = 768;
	var mq = '(max-width: ' + FOOTER_ACCORDION_BREAKPOINT + 'px)';

	function initFooterAccordion() {
		try {
			var footer = document.getElementById('colophon') || document.querySelector('.mnsk7-footer');
			if (!footer) return false;

			function toggleSection(trigger) {
				if (!trigger || trigger.getAttribute('aria-controls') === null) return;
				var col = trigger.closest('.mnsk7-footer__col');
				if (!col) return;
				var isOpen = col.classList.toggle('is-open');
				trigger.setAttribute('aria-expanded', isOpen ? 'true' : 'false');
			}

			function handleTrigger(e) {
				if (!window.matchMedia(mq).matches) return;
				var trigger = e.target.closest('.mnsk7-footer__accordion-trigger');
				if (!trigger) return;
				e.preventDefault();
				e.stopPropagation();
				toggleSection(trigger);
			}

			footer.addEventListener('click', handleTrigger);
			footer.addEventListener('keydown', function(e) {
				if (!window.matchMedia(mq).matches) return;
				var trigger = e.target.closest('.mnsk7-footer__accordion-trigger');
				if (!trigger) return;
				if (e.key === 'Enter' || e.key === ' ') {
					e.preventDefault();
					toggleSection(trigger);
				}
			});

			function syncAria() {
				footer.querySelectorAll('.mnsk7-footer__accordion-trigger').forEach(function(btn) {
					var col = btn.closest('.mnsk7-footer__col');
					if (!col) return;
					btn.setAttribute('aria-expanded', col.classList.contains('is-open') ? 'true' : 'false');
				});
			}
			syncAria();
			window.matchMedia(mq).addEventListener('change', syncAria);
			return true;
		} catch (err) {
			if (typeof console !== 'undefined' && console.error) {
				console.error('mnsk7 footer accordion init error:', err);
			}
			return false;
		}
	}

	function run() {
		if (initFooterAccordion()) return;
		setTimeout(function() {
			initFooterAccordion();
		}, 100);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
})();
