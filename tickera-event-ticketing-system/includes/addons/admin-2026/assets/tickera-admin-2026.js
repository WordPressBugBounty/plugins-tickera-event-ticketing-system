/**
 * Tickera Admin 2026 — Add-Ons page enhancements.
 *
 * Reshapes the Freemius add-ons grid into a premium marketing-catalog layout
 * (gradient icon, real tagline, "Explore" link) and injects the bundle banner —
 * mirroring the Venuera add-ons screen, in Tickera brand colors. Also removes
 * deprecated add-ons from the grid.
 */
(function () {
	'use strict';

	var REMOVE = ['barcode reader', 'event timetable', 'paypal chained payments'];

	function removeDeprecated() {
		document.querySelectorAll('.fs-card.fs-addon').forEach(function (card) {
			var titleEl = card.querySelector('.fs-title');
			var t = (titleEl ? titleEl.textContent : card.textContent || '').trim().toLowerCase();
			for (var i = 0; i < REMOVE.length; i++) {
				if (t.indexOf(REMOVE[i]) === 0 || t === REMOVE[i]) { card.style.setProperty('display', 'none', 'important'); return; }
			}
		});
	}

	function injectBanner() {
		if (document.querySelector('.tc-bundle-cta')) { return; }
		var list = document.querySelector('#fs_addons .fs-cards-list') || document.querySelector('#fs_addons');
		if (!list) { return; }
		var b = document.createElement('div');
		b.className = 'tc-bundle-cta';
		b.innerHTML =
			'<div class="vb-txt"><b>Get every add-on with the Bundle Package</b>' +
			'<span>All official Tickera add-ons &mdash; one bundle, one price.</span></div>' +
			'<a class="vb-btn" href="https://tickera.com/pricing/" target="_blank" rel="noopener">View pricing &rarr;</a>';
		list.parentNode.insertBefore(b, list);
	}

	function reshape() {
		var cards = document.querySelectorAll('.fs-card.fs-addon');
		if (!cards.length) { return false; }
		cards.forEach(function (card) {
			var inner  = card.querySelector('.fs-inner');
			var ul     = card.querySelector('.fs-inner > ul');
			var title  = card.querySelector('.fs-title');
			var desc   = card.querySelector('.fs-description');
			var btn    = card.querySelector('.fs-cta .button, .fs-cta .button-primary');

			function fix(el) {
				if (!el) { return; }
				el.style.setProperty('height', 'auto', 'important');
				el.style.setProperty('min-height', '0', 'important');
				el.style.setProperty('max-height', 'none', 'important');
				el.style.setProperty('flex', 'none', 'important');
			}
			// NB: do NOT fix() the card or its .fs-inner — let the CSS grid stretch the
			// card to fill its row, and let .fs-inner flex-grow so the CTA sits at the
			// bottom. Otherwise the page background shows beneath shorter cards.
			fix(ul); fix(desc); fix(title);
			if (title) { title.style.setProperty('overflow', 'visible', 'important'); title.style.setProperty('white-space', 'normal', 'important'); title.style.setProperty('padding', '0', 'important'); }
			if (desc) { desc.style.setProperty('padding', '0', 'important'); }
			if (ul) { ul.style.position = 'static'; }
			if (btn) {
				btn.style.position = 'static';
				if (/view details/i.test(btn.textContent || '')) { btn.textContent = 'Explore'; }
			}
			// NOTE: the add-on banner icons are the real Freemius assets — left untouched.
		});
		return true;
	}

	function run() {
		if (!document.getElementById('fs_addons')) { return; }
		removeDeprecated();
		injectBanner();
		if (reshape()) { return; }
		var tries = 0, iv = setInterval(function () {
			removeDeprecated();
			if (reshape() || ++tries > 20) { clearInterval(iv); injectBanner(); }
		}, 150);
	}

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', run);
	} else {
		run();
	}
	window.addEventListener('load', run);
})();
