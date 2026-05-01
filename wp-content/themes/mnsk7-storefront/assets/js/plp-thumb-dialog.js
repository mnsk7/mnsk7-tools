/**
 * PLP tabela: kliknięcie miniatury otwiera pełne zdjęcie w <dialog>.
 */
(function () {
	'use strict';

	var dialog = document.getElementById('mnsk7-plp-thumb-dialog');
	if (!dialog || typeof dialog.showModal !== 'function') {
		return;
	}
	var img = dialog.querySelector('.mnsk7-plp-thumb-dialog__img');
	var closeBtn = dialog.querySelector('.mnsk7-plp-thumb-dialog__close');
	if (!img) {
		return;
	}

	function openFromButton(btn) {
		var src = btn.getAttribute('data-full-src');
		if (!src) {
			return;
		}
		var innerImg = btn.querySelector('img');
		img.alt = innerImg ? innerImg.getAttribute('alt') || '' : '';
		img.src = src;
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

	dialog.addEventListener('click', function (e) {
		if (e.target === dialog) {
			dialog.close();
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
	});
})();
