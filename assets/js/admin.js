(function () {
	'use strict';
	var root = document.querySelector('.wa');
	if (!root) { return; }

	var KEY = 'wdta_admin_theme';
	try { var t = localStorage.getItem(KEY); if (t) { root.setAttribute('data-wa-theme', t); } } catch (e) { /* noop */ }
	var tt = root.querySelector('[data-wa-theme-toggle]');
	if (tt) {
		tt.addEventListener('click', function () {
			var next = root.getAttribute('data-wa-theme') === 'dark' ? 'light' : 'dark';
			root.setAttribute('data-wa-theme', next);
			try { localStorage.setItem(KEY, next); } catch (e) { /* noop */ }
		});
	}

	var tabs = root.querySelectorAll('.wa-nav > a');
	var panels = root.querySelectorAll('.wa-panel');
	var save = root.querySelector('[data-wa-save]');
	var formTabs = ['genel', 'alanlar', 'gorunum'];

	var show = function (id) {
		if (!root.querySelector('#' + id + '.wa-panel')) { id = 'genel'; }
		tabs.forEach(function (a) { a.classList.toggle('is-active', a.getAttribute('href') === '#' + id); });
		panels.forEach(function (p) { p.classList.toggle('is-active', p.id === id); });
		if (save) { save.hidden = formTabs.indexOf(id) < 0; }
	};
	tabs.forEach(function (a) {
		a.addEventListener('click', function (e) {
			e.preventDefault();
			var id = a.getAttribute('href').slice(1);
			history.replaceState(null, '', '#' + id);
			show(id);
		});
	});
	show((location.hash || '#genel').slice(1));

	var pv = root.querySelector('[data-wa-preview]');
	var accent = root.querySelector('[data-wa-accent]');
	var code = root.querySelector('[data-wa-accent-code]');
	var swatches = root.querySelectorAll('.wa-swatch');
	var setAccent = function (hex) {
		if (accent) { accent.value = hex; }
		if (code) { code.textContent = hex; }
		if (pv) { pv.style.setProperty('--pv-accent', hex); }
		swatches.forEach(function (s) { s.classList.toggle('is-on', s.getAttribute('data-color').toLowerCase() === hex.toLowerCase()); });
	};
	swatches.forEach(function (s) { s.addEventListener('click', function () { setAccent(s.getAttribute('data-color')); }); });
	if (accent) {
		accent.addEventListener('input', function () { setAccent(accent.value); });
		setAccent(accent.value);
	}

	var radius = root.querySelector('[data-wa-radius]');
	var rout = root.querySelector('[data-wa-radius-out]');
	if (radius) {
		radius.addEventListener('input', function () {
			if (rout) { rout.textContent = radius.value + 'px'; }
			if (pv) { pv.style.setProperty('--pv-radius', radius.value + 'px'); }
		});
	}
})();
