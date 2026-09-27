/* WD Türkiye Adres — zincirleme adres seçimi */
(function ($) {
	'use strict';

	var C = window.WDTA_CFG;
	if (!C) { return; }
	['rural', 'kapiReq', 'daireReq', 'auto', 'validate'].forEach(function (k) { C[k] = Number(C[k]) === 1; });
	C.popular = (C.popular || []).map(Number);

	/* ------------------------------------------------------------------ */
	/* Yardımcılar                                                         */
	/* ------------------------------------------------------------------ */

	var FOLD = { 'ç': 'c', 'ğ': 'g', 'ı': 'i', 'ö': 'o', 'ş': 's', 'ü': 'u', 'â': 'a', 'î': 'i', 'û': 'u' };
	var fold = function (s) {
		return String(s == null ? '' : s).toLocaleLowerCase('tr').replace(/[çğıöşüâîû]/g, function (c) { return FOLD[c]; });
	};
	var trTitle = function (s) {
		return String(s).trim().replace(/\s+/g, ' ').split(' ').map(function (w) {
			var l = w.toLocaleLowerCase('tr');
			return l.charAt(0).toLocaleUpperCase('tr') + l.slice(1);
		}).join(' ');
	};
	var fmt = function (n) { return Number(n).toLocaleString('tr-TR'); };
	var pad2 = function (n) { return (n < 10 ? '0' : '') + n; };
	var isMobile = function () { return window.matchMedia('(max-width: 640px)').matches; };
	var h = function (tag, cls, text) {
		var el = document.createElement(tag);
		if (cls) { el.className = cls; }
		if (text != null) { el.textContent = text; }
		return el;
	};
	var svg = function (paths, cls) {
		var s = '<svg viewBox="0 0 20 20" aria-hidden="true"' + (cls ? ' class="' + cls + '"' : '') + '>' + paths + '</svg>';
		var t = document.createElement('template');
		t.innerHTML = s;
		return t.content.firstChild;
	};
	var ICON = {
		check: '<path d="m5 10.5 3.2 3L15 6.8" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round"/>',
		pen: '<path d="M12.8 4.2 15.8 7.2M4 16l.9-3.6L13.4 4a1.4 1.4 0 0 1 2 0l.6.6a1.4 1.4 0 0 1 0 2L7.6 15.1 4 16Z" fill="none" stroke="currentColor" stroke-width="1.5" stroke-linejoin="round"/>',
		search: '<circle cx="9" cy="9" r="5.5" fill="none" stroke="currentColor" stroke-width="1.6"/><path d="m13.2 13.2 3.3 3.3" stroke="currentColor" stroke-width="1.6" stroke-linecap="round"/>',
		close: '<path d="m5.5 5.5 9 9m0-9-9 9" stroke="currentColor" stroke-width="1.7" stroke-linecap="round"/>',
		empty: '<circle cx="9" cy="9" r="5.5" fill="none" stroke="currentColor" stroke-width="1.4"/><path d="m13.2 13.2 3.3 3.3M7 9h4" stroke="currentColor" stroke-width="1.4" stroke-linecap="round"/>'
	};

	var sokakLabel = function (name, type) {
		var num = /^\d+[A-Za-zÇĞİÖŞÜçğıöşü]?$/.test(name);
		switch (type) {
			case 'c': return num ? name + '. Cadde' : name + ' Caddesi';
			case 'b': return num ? name + '. Bulvar' : name + ' Bulvarı';
			case 'y': return name + ' Meydanı';
			case 'k': return name + ' Küme Evleri';
			case 'o': return name + ' Köy Sokağı';
			default: return num ? name + '. Sokak' : name + ' Sokak';
		}
	};

	/* ------------------------------------------------------------------ */
	/* Veri                                                                */
	/* ------------------------------------------------------------------ */

	var ilMap = {};
	C.iller.forEach(function (r) { ilMap[r[0]] = r[1]; });

	var getJSON = function (url) {
		return fetch(url, { credentials: 'same-origin' }).then(function (r) {
			if (!r.ok) { throw new Error('HTTP ' + r.status); }
			return r.json();
		});
	};
	var memo = function (store, key, fn) {
		if (!store[key]) {
			store[key] = fn().catch(function (e) { delete store[key]; throw e; });
		}
		return store[key];
	};
	var mahStore = {}, sokStore = {};

	var collator = new Intl.Collator('tr', { numeric: true, sensitivity: 'base' });

	var MAH_TAG = { m: 'Mah', k: 'Köy', v: 'Mevki', z: 'Mezra', e: 'Evler' };
	var MAH_GRP = { m: [0, 'Mahalleler'], k: [1, 'Köyler'], v: [2, 'Mevki · mezra · küme evler'], z: [2, 'Mevki · mezra · küme evler'], e: [2, 'Mevki · mezra · küme evler'] };
	var SOK_TAG = { c: 'Cd', b: 'Blv', y: 'Myd', s: 'Sk', k: 'Küme', o: 'Köy' };
	var SOK_GRP = { c: [0, 'Caddeler · bulvarlar'], b: [0, 'Caddeler · bulvarlar'], y: [0, 'Caddeler · bulvarlar'], s: [1, 'Sokaklar'], k: [2, 'Küme evler · köy sokakları'], o: [2, 'Küme evler · köy sokakları'] };

	var sortGrouped = function (items) {
		return items.sort(function (a, b) { return (a.go - b.go) || collator.compare(a.name, b.name); });
	};

	var Data = {
		iller: function () {
			return C.iller.map(function (r) {
				return { id: String(r[0]), name: r[1], side: pad2(r[0]), badge: pad2(r[0]), f: fold(r[1]) + ' ' + pad2(r[0]) };
			});
		},
		ilceler: function (il) {
			return (C.ilceler[il] || []).map(function (r) {
				return { id: String(r[0]), name: r[1], side: r[2], pk: r[2], f: fold(r[1]) };
			});
		},
		mahalleler: function (ilce) {
			return memo(mahStore, ilce, function () {
				return getJSON(C.mahalleUrl + ilce + '.json?v=' + C.ver)
					.catch(function () { return getJSON(C.restUrl + 'mahalle/' + ilce); })
					.then(function (rows) {
						return sortGrouped(rows.map(function (r) {
							var g = MAH_GRP[r[2]] || MAH_GRP.m;
							return { id: String(r[0]), name: r[1], type: r[2], tag: MAH_TAG[r[2]] || 'Mah', pk: r[3], side: r[3], go: g[0], group: g[1], badge: MAH_TAG[r[2]] || 'Mah', f: fold(r[1]) };
						}));
					});
			});
		},
		sokaklar: function (ilce, mah) {
			return memo(sokStore, ilce + ':' + mah, function () {
				return getJSON(C.restUrl + 'sokak/' + ilce + '/' + mah).then(function (rows) {
					return sortGrouped(rows.map(function (r) {
						var g = SOK_GRP[r[2]] || SOK_GRP.s;
						var label = sokakLabel(r[1], r[2]);
						return { id: String(r[0]), name: label, type: r[2], tag: SOK_TAG[r[2]] || 'Sk', go: g[0], group: g[1], badge: SOK_TAG[r[2]] || 'Sk', f: fold(label) };
					}));
				});
			});
		}
	};

	var search = function (items, q) {
		var fq = fold(q).trim();
		if (!fq) { return { list: items, q: '' }; }
		var tokens = fq.split(/\s+/);
		var out = [];
		for (var i = 0; i < items.length; i++) {
			var it = items[i], pos = it.f.indexOf(fq), score;
			if (pos === 0) { score = 0; }
			else if (pos > 0) { score = it.f.charAt(pos - 1) === ' ' ? 1 : 2; }
			else {
				var all = true;
				for (var t = 0; t < tokens.length; t++) { if (it.f.indexOf(tokens[t]) < 0) { all = false; break; } }
				if (!all) { continue; }
				score = 3;
			}
			out.push({ it: it, s: score, i: i, pos: pos });
		}
		out.sort(function (a, b) { return (a.s - b.s) || (a.i - b.i); });
		return { list: out.map(function (o) { o.it._pos = o.pos; return o.it; }), q: fq };
	};

	/* ------------------------------------------------------------------ */
	/* Combobox                                                            */
	/* ------------------------------------------------------------------ */

	var LIMIT = 200;
	var uid = 0;

	function Combo(panel, field) {
		this.panel = panel;
		this.field = field;
		this.level = field.getAttribute('data-level');
		this.box = field.querySelector('.wdta-cb');
		this.input = field.querySelector('.wdta-cb__input');
		this.hidden = field.querySelector('input[data-role="value"]');
		this.idx = field.querySelector('.wdta-cb__idx');
		this.hint = field.querySelector('.wdta-f__hint');
		this.labelText = (field.querySelector('.wdta-f__label').firstChild.textContent || '').trim();
		this.items = [];
		this.value = this.hidden.value || '';
		this.text = '';
		this.freeText = false;
		this.isOpen = false;
		this.active = -1;
		this.view = [];
		this.query = '';
		this.typed = false;
		this.pending = null;
		this.pop = null;
		this.bind();
	}

	Combo.prototype.bind = function () {
		var self = this, inp = this.input;
		inp.id = inp.id || ('wdta_q_' + (++uid));

		inp.addEventListener('focus', function () {
			if (self.state() === 'locked' || self.freeText) { return; }
			if (!isMobile()) {
				self.open();
				setTimeout(function () { try { inp.select(); } catch (e) { /* noop */ } }, 0);
			}
		});
		inp.addEventListener('click', function () {
			if (self.state() === 'locked' || self.freeText) { return; }
			if (isMobile()) { self.open(); } else if (!self.isOpen) { self.open(); }
		});
		inp.addEventListener('input', function () {
			if (self.freeText) {
				self.panel.onFreeText(self, inp.value);
				return;
			}
			self.typed = true;
			self.query = inp.value;
			if (!self.isOpen) { self.open(true); } else { self.render(); }
		});
		inp.addEventListener('keydown', function (e) { self.onKey(e); });
		inp.addEventListener('blur', function () {
			if (isMobile() || self.freeText) { return; }
			setTimeout(function () {
				if (document.activeElement !== inp) { self.close(); }
			}, 120);
		});
		this.syncReadonly();
	};

	Combo.prototype.syncReadonly = function () {
		if (this.freeText) {
			this.input.readOnly = false;
			this.input.removeAttribute('role');
			return;
		}
		this.input.setAttribute('role', 'combobox');
		this.input.readOnly = isMobile() || this.state() === 'locked';
		this.input.setAttribute('inputmode', isMobile() ? 'none' : 'text');
	};

	Combo.prototype.state = function (s) {
		if (s) {
			this.box.setAttribute('data-state', s);
			this.syncReadonly();
		}
		return this.box.getAttribute('data-state');
	};

	Combo.prototype.lock = function (placeholder) {
		this.close();
		this.pending = null;
		this.items = [];
		this.setValue('', '', '');
		this.setFree(false);
		this.input.placeholder = placeholder;
		this.input.tabIndex = -1;
		this.state('locked');
	};

	Combo.prototype.unlock = function (placeholder) {
		this.input.placeholder = placeholder;
		this.input.tabIndex = 0;
		this.state(this.value || this.text ? 'done' : 'idle');
	};

	Combo.prototype.setFree = function (on, placeholder) {
		this.freeText = !!on;
		this.box.classList.toggle('is-free', this.freeText);
		if (on) {
			this.close();
			this.input.placeholder = placeholder || this.input.placeholder;
			this.input.tabIndex = 0;
			this.state('idle');
		}
		this.syncReadonly();
	};

	Combo.prototype.setItems = function (items) {
		this.items = items || [];
		if (this.isOpen) { this.render(); }
	};

	Combo.prototype.setValue = function (id, text, badge) {
		this.value = id ? String(id) : '';
		this.text = text || '';
		this.hidden.value = this.value;
		this.input.value = this.text;
		if (this.idx) { this.idx.textContent = badge || ''; }
		if (this.state() !== 'locked') { this.state(this.value || this.text ? 'done' : 'idle'); }
		this.invalid(false);
	};

	Combo.prototype.invalid = function (msg) {
		this.field.classList.toggle('is-invalid', !!msg);
		if (this.hint) { this.hint.textContent = msg || ''; }
	};

	Combo.prototype.loading = function (promise) {
		var self = this;
		this.state('loading');
		this.pending = promise;
		return promise.then(function (items) {
			if (self.pending !== promise) { return items; }
			self.pending = null;
			self.state(self.value || self.text ? 'done' : 'idle');
			return items;
		}, function (err) {
			if (self.pending === promise) {
				self.pending = null;
				self.state('idle');
				self.invalid('Liste yüklenemedi. Bağlantınızı kontrol edip tekrar deneyin.');
			}
			throw err;
		});
	};

	/* ----- açılır liste ----- */

	Combo.prototype.buildPop = function (sheet) {
		var self = this;
		var pop = h('div', 'wdta-pop' + (sheet ? ' is-sheet' : ''));
		pop.setAttribute('data-level', this.level);

		if (sheet) {
			var head = h('div', 'wdta-pop__sheet-head');
			head.appendChild(h('span', 'wdta-pop__grab'));
			var row = h('div', 'wdta-pop__row');
			row.appendChild(h('span', 'wdta-pop__title', this.labelText));
			var close = h('button', 'wdta-pop__close');
			close.type = 'button';
			close.setAttribute('aria-label', 'Kapat');
			close.appendChild(svg(ICON.close));
			close.addEventListener('click', function () { self.close(); });
			row.appendChild(close);
			head.appendChild(row);
			var sb = h('label', 'wdta-pop__search');
			sb.appendChild(svg(ICON.search));
			var si = h('input');
			si.type = 'search';
			si.placeholder = this.labelText + ' ara';
			si.autocomplete = 'off';
			si.setAttribute('enterkeyhint', 'done');
			si.addEventListener('input', function () { self.typed = true; self.query = si.value; self.render(); });
			si.addEventListener('keydown', function (e) { self.onKey(e); });
			sb.appendChild(si);
			head.appendChild(sb);
			pop.appendChild(head);
			this.sheetInput = si;
		}

		var meta = h('div', 'wdta-pop__meta');
		this.countEl = h('span', 'wdta-pop__count');
		meta.appendChild(this.countEl);
		var keys = h('span', 'wdta-pop__keys');
		['↑', '↓', 'Enter'].forEach(function (k) { keys.appendChild(h('kbd', null, k)); });
		meta.appendChild(keys);
		pop.appendChild(meta);

		this.chipsEl = h('div', 'wdta-pop__chips');
		pop.appendChild(this.chipsEl);

		this.listEl = h('ul', 'wdta-pop__list');
		this.listEl.id = this.input.id + '_list';
		this.listEl.setAttribute('role', 'listbox');
		pop.appendChild(this.listEl);

		this.manualEl = h('button', 'wdta-pop__manual');
		this.manualEl.type = 'button';
		this.manualEl.hidden = true;
		pop.appendChild(this.manualEl);

		pop.addEventListener('mousedown', function (e) {
			if (!sheet || e.target !== self.sheetInput) { if (e.target.tagName !== 'INPUT') { e.preventDefault(); } }
		});
		this.listEl.addEventListener('click', function (e) {
			var li = e.target.closest('.wdta-opt-i');
			if (li) { self.commit(Number(li.getAttribute('data-i'))); }
		});
		this.listEl.addEventListener('mousemove', function (e) {
			var li = e.target.closest('.wdta-opt-i');
			if (li) { self.setActive(Number(li.getAttribute('data-i')), false); }
		});
		this.manualEl.addEventListener('click', function () { self.commitManual(); });
		this.chipsEl.addEventListener('click', function (e) {
			var b = e.target.closest('.wdta-chip');
			if (!b) { return; }
			var id = b.getAttribute('data-id');
			var it = self.items.filter(function (x) { return x.id === id; })[0];
			if (it) { self.choose(it); }
		});
		return pop;
	};

	Combo.prototype.open = function (fromTyping) {
		if (this.isOpen || this.freeText || this.state() === 'locked') { return; }
		var self = this;

		if (this.pending) {
			this.pending.then(function () { if (document.activeElement === self.input || isMobile()) { self.open(fromTyping); } });
			return;
		}

		this.panel.closeOthers(this);
		var sheet = isMobile();
		this.pop = this.buildPop(sheet);
		if (!fromTyping) { this.query = ''; this.typed = false; }

		if (sheet) {
			this.portal = h('div', 'wdta wdta-portal');
			this.portal.setAttribute('data-theme', this.panel.root.getAttribute('data-theme') || 'light');
			this.backdrop = h('div', 'wdta-backdrop');
			this.backdrop.addEventListener('click', function () { self.close(); });
			this.portal.appendChild(this.backdrop);
			this.portal.appendChild(this.pop);
			document.body.appendChild(this.portal);
			document.documentElement.classList.add('wdta-lock');
			setTimeout(function () { if (self.sheetInput) { self.sheetInput.focus(); } }, 60);
		} else {
			this.box.appendChild(this.pop);
			var r = this.box.getBoundingClientRect();
			var below = window.innerHeight - r.bottom, above = r.top;
			if (below < 280 && above > below) { this.pop.classList.add('is-up'); }
		}

		this.isOpen = true;
		this.box.classList.add('is-open');
		this.input.setAttribute('aria-expanded', 'true');
		this.active = -1;
		this.render();

		if (!this.typed && this.value) {
			var at = this.view.findIndex(function (x) { return x && x.id === self.value; });
			if (at >= 0) { this.setActive(at, true); }
		}
	};

	Combo.prototype.close = function () {
		if (!this.isOpen) { return; }
		this.isOpen = false;
		this.box.classList.remove('is-open');
		this.input.setAttribute('aria-expanded', 'false');
		this.input.removeAttribute('aria-activedescendant');
		if (this.portal) {
			this.portal.remove();
			this.portal = null;
			document.documentElement.classList.remove('wdta-lock');
		} else if (this.pop) {
			this.pop.remove();
		}
		this.pop = null;
		this.sheetInput = null;
		this.input.value = this.text;
		this.query = '';
		this.typed = false;
	};

	Combo.prototype.allowManual = function () {
		return this.level === 'sokak' && C.sokakMode === 'list_manual';
	};

	Combo.prototype.render = function () {
		if (!this.pop) { return; }
		var self = this;
		var q = this.typed ? this.query : '';
		var res = search(this.items, q);
		var list = res.list;
		var frag = document.createDocumentFragment();
		var shown = Math.min(list.length, LIMIT);
		var lastGroup = null;
		this.view = [];

		for (var i = 0; i < shown; i++) {
			var it = list[i];
			if (!res.q && it.group && it.group !== lastGroup) {
				var gl = h('li', 'wdta-grp', it.group);
				gl.setAttribute('role', 'presentation');
				frag.appendChild(gl);
				lastGroup = it.group;
			}
			var li = h('li', 'wdta-opt-i');
			var n = this.view.length;
			li.id = this.listEl.id + '_' + n;
			li.setAttribute('role', 'option');
			li.setAttribute('data-i', n);
			li.setAttribute('aria-selected', it.id === this.value ? 'true' : 'false');
			if (it.tag) { li.appendChild(h('span', 'wdta-opt-i__tag', it.tag)); }

			var name = h('span', 'wdta-opt-i__name');
			if (res.q && it._pos >= 0) {
				name.appendChild(document.createTextNode(it.name.slice(0, it._pos)));
				name.appendChild(h('mark', null, it.name.slice(it._pos, it._pos + res.q.length)));
				name.appendChild(document.createTextNode(it.name.slice(it._pos + res.q.length)));
			} else {
				name.textContent = it.name;
			}
			li.appendChild(name);
			if (it.id === this.value) { li.appendChild(svg(ICON.check, 'wdta-opt-i__check')); }
			else if (it.side) { li.appendChild(h('span', 'wdta-opt-i__side', it.side)); }
			frag.appendChild(li);
			this.view.push(it);
		}

		if (!list.length) {
			var em = h('li', 'wdta-pop__empty');
			em.appendChild(svg(ICON.empty));
			em.appendChild(document.createTextNode(this.items.length ? 'Eşleşen sonuç bulunamadı' : 'Bu alan için kayıt yok'));
			frag.appendChild(em);
		} else if (list.length > shown) {
			frag.appendChild(h('li', 'wdta-pop__more', '+' + fmt(list.length - shown) + ' sonuç daha · aramayı daraltın'));
		}

		this.listEl.textContent = '';
		this.listEl.appendChild(frag);

		var noun = { il: 'il', ilce: 'ilçe', mahalle: 'mahalle / köy', sokak: 'cadde / sokak' }[this.level];
		this.countEl.textContent = res.q ? fmt(list.length) + ' sonuç' : fmt(this.items.length) + ' ' + noun;

		this.chipsEl.textContent = '';
		this.chipsEl.hidden = true;
		if (this.level === 'il' && !res.q && C.popular && C.popular.length) {
			C.popular.forEach(function (pid) {
				if (!ilMap[pid]) { return; }
				var b = h('button', 'wdta-chip');
				b.type = 'button';
				b.setAttribute('data-id', String(pid));
				b.appendChild(h('b', null, pad2(pid)));
				b.appendChild(document.createTextNode(ilMap[pid]));
				self.chipsEl.appendChild(b);
			});
			this.chipsEl.hidden = false;
		}

		var raw = (this.typed ? this.query : '').trim();
		var exact = raw && list.some(function (x) { return x.f === fold(raw); });
		this.manualIndex = -1;
		if (this.allowManual() && raw.length >= 2 && !exact) {
			this.manualEl.textContent = '';
			this.manualEl.appendChild(svg(ICON.pen));
			var sp = h('span');
			sp.appendChild(document.createTextNode('Listede yok mu? '));
			sp.appendChild(h('em', null, '“' + trTitle(raw) + '”'));
			sp.appendChild(document.createTextNode(' adını kullan'));
			this.manualEl.appendChild(sp);
			this.manualEl.hidden = false;
			this.manualIndex = this.view.length;
		} else {
			this.manualEl.hidden = true;
		}

		this.active = -1;
		if (res.q && this.view.length) { this.setActive(0, false); }
	};

	Combo.prototype.setActive = function (i, scroll) {
		var max = this.view.length + (this.manualIndex >= 0 ? 1 : 0);
		if (!max) { this.active = -1; return; }
		i = Math.max(0, Math.min(max - 1, i));
		var prev = this.listEl.querySelector('.wdta-opt-i.is-active');
		if (prev) { prev.classList.remove('is-active'); }
		this.manualEl.classList.remove('is-active');
		this.active = i;
		if (i === this.manualIndex) {
			this.manualEl.classList.add('is-active');
			this.input.removeAttribute('aria-activedescendant');
			return;
		}
		var li = this.listEl.querySelector('[data-i="' + i + '"]');
		if (li) {
			li.classList.add('is-active');
			this.input.setAttribute('aria-activedescendant', li.id);
			if (scroll !== false) { li.scrollIntoView({ block: 'nearest' }); }
		}
	};

	Combo.prototype.onKey = function (e) {
		if (this.freeText) { return; }
		switch (e.key) {
			case 'ArrowDown':
				e.preventDefault();
				if (!this.isOpen) { this.open(); } else { this.setActive(this.active + 1); }
				break;
			case 'ArrowUp':
				e.preventDefault();
				if (this.isOpen) { this.setActive(this.active - 1); }
				break;
			case 'PageDown':
				if (this.isOpen) { e.preventDefault(); this.setActive(this.active + 8); }
				break;
			case 'PageUp':
				if (this.isOpen) { e.preventDefault(); this.setActive(this.active - 8); }
				break;
			case 'Enter':
				if (this.isOpen) {
					e.preventDefault();
					if (this.active >= 0) { this.commit(this.active); }
				}
				break;
			case 'Tab':
				if (this.isOpen && this.typed && this.active >= 0) { this.commit(this.active, true); }
				else { this.close(); }
				break;
			case 'Escape':
				if (this.isOpen) { e.preventDefault(); this.close(); if (isMobile()) { this.input.blur(); } }
				break;
		}
	};

	Combo.prototype.commit = function (i, viaTab) {
		if (i === this.manualIndex) { this.commitManual(); return; }
		var it = this.view[i];
		if (it) { this.choose(it, viaTab); }
	};

	Combo.prototype.choose = function (it, viaTab) {
		var changed = it.id !== this.value;
		this.setValue(it.id, it.name, it.badge || '');
		this.close();
		this.panel.onSelect(this, it, changed, viaTab);
	};

	Combo.prototype.commitManual = function () {
		var raw = trTitle((this.query || '').trim());
		if (raw.length < 2) { return; }
		this.setValue('', raw, 'Elle');
		this.close();
		this.panel.onSelect(this, { id: '', name: raw, manual: true }, true, false);
	};

	/* ------------------------------------------------------------------ */
	/* Panel                                                               */
	/* ------------------------------------------------------------------ */

	var LEVELS = ['il', 'ilce', 'mahalle', 'sokak'];
	var PLACE = {
		il: 'İl arayın veya seçin',
		ilce: 'İlçe arayın veya seçin',
		mahalle: 'Mahalle veya köy arayın',
		sokak: 'Cadde veya sokak arayın'
	};
	var LOCKED = { ilce: 'Önce il seçin', mahalle: 'Önce ilçe seçin', sokak: 'Önce mahalle seçin' };

	function Panel(root) {
		this.root = root;
		this.group = root.getAttribute('data-group');
		this.row = root.closest('.wdta-row');
		this.form = root.closest('form');
		this.isCheckout = !!(this.form && this.form.classList.contains('checkout'));
		this.c = {};
		var self = this;

		LEVELS.forEach(function (lv) {
			self.c[lv] = new Combo(self, root.querySelector('.wdta-f[data-level="' + lv + '"]'));
		});

		this.q = function (role) { return root.querySelector('[data-role="' + role + '"]'); };
		this.mahAd = this.q('mahalle_ad');
		this.sokAd = this.q('sokak_ad');
		this.kapi = this.q('kapi');
		this.daire = this.q('daire');
		this.bina = this.q('bina');
		this.tarif = this.q('tarif');
		this.preview = this.q('preview');

		this.applyTheme();
		this.bindInputs();
		this.c.il.setItems(Data.iller());
		this.hydrate();
		this.watchCountry();
		this.bindValidation();
		this.refresh(false);
	}

	Panel.prototype.fieldId = function (name) { return document.getElementById(this.group + '_' + name); };

	Panel.prototype.applyTheme = function () {
		var pref = this.root.getAttribute('data-theme-pref') || 'auto';
		var theme = pref;
		if (pref === 'auto') {
			var el = this.root.parentElement, rgb = null;
			while (el && el !== document.documentElement) {
				var bg = getComputedStyle(el).backgroundColor;
				var m = bg && bg.match(/rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?/);
				if (m && (m[4] === undefined || Number(m[4]) > 0.5)) { rgb = m; break; }
				el = el.parentElement;
			}
			if (!rgb) {
				var hb = getComputedStyle(document.body).backgroundColor.match(/rgba?\(([\d.]+),\s*([\d.]+),\s*([\d.]+)(?:,\s*([\d.]+))?/);
				rgb = hb && (hb[4] === undefined || Number(hb[4]) > 0.5) ? hb : null;
			}
			var lum = rgb ? (0.2126 * rgb[1] + 0.7152 * rgb[2] + 0.0722 * rgb[3]) / 255 : 1;
			theme = lum < 0.45 ? 'dark' : 'light';
		}
		this.root.setAttribute('data-theme', theme);
	};

	Panel.prototype.bindInputs = function () {
		var self = this;
		var clean = function (el) {
			if (!el) { return; }
			el.addEventListener('input', function () {
				var v = el.value.replace(/[^0-9A-Za-zÇĞİÖŞÜçğıöşü\/\-\s]/g, '');
				if (v !== el.value) { el.value = v; }
				el.closest('.wdta-f').classList.remove('is-invalid');
				var hint = el.closest('.wdta-f').querySelector('.wdta-f__hint');
				if (hint) { hint.textContent = ''; }
				self.refresh(false);
			});
		};
		clean(this.kapi);
		clean(this.daire);
		[this.bina, this.tarif].forEach(function (el) {
			if (el) { el.addEventListener('input', function () { self.refresh(false); }); }
		});

		if (this.tarif) {
			var count = this.root.querySelector('.wdta-in__count');
			var upd = function () { if (count) { count.textContent = self.tarif.value.length + '/250'; } };
			this.tarif.addEventListener('input', upd);
			upd();
			var tg = this.q('tarif-toggle');
			tg.addEventListener('click', function () {
				var box = self.tarif.closest('.wdta-in');
				var open = tg.getAttribute('aria-expanded') !== 'true';
				tg.setAttribute('aria-expanded', open ? 'true' : 'false');
				box.hidden = !open;
				if (open) { self.tarif.focus(); } else { self.tarif.value = ''; upd(); self.refresh(false); }
			});
		}

		['first_name', 'last_name'].forEach(function (k) {
			['billing', 'shipping'].forEach(function (g) {
				var el = document.getElementById(g + '_' + k);
				if (el) { el.addEventListener('input', function () { self.renderPreview(); }); }
			});
		});

		document.addEventListener('mousedown', function (e) {
			LEVELS.forEach(function (lv) {
				var cb = self.c[lv];
				if (cb.isOpen && !isMobile() && !cb.box.contains(e.target)) { cb.close(); }
			});
		});

		var mq = window.matchMedia('(max-width: 640px)');
		var onMq = function () { LEVELS.forEach(function (lv) { self.c[lv].close(); self.c[lv].syncReadonly(); }); };
		if (mq.addEventListener) { mq.addEventListener('change', onMq); } else if (mq.addListener) { mq.addListener(onMq); }
	};

	Panel.prototype.closeOthers = function (keep) {
		var self = this;
		LEVELS.forEach(function (lv) { if (self.c[lv] !== keep) { self.c[lv].close(); } });
	};

	/* ----- zincir ----- */

	Panel.prototype.setIl = function (id, silent) {
		var c = this.c;
		c.ilce.lock(LOCKED.ilce);
		c.mahalle.lock(LOCKED.mahalle);
		c.sokak.lock(LOCKED.sokak);
		this.mahAd.value = '';
		this.sokAd.value = '';
		if (!id) { return; }
		c.ilce.setItems(Data.ilceler(id));
		c.ilce.unlock(PLACE.ilce);
	};

	Panel.prototype.setIlce = function (id) {
		var self = this, c = this.c;
		c.mahalle.lock(LOCKED.mahalle);
		c.sokak.lock(LOCKED.sokak);
		this.mahAd.value = '';
		this.sokAd.value = '';
		if (!id) { return Promise.resolve([]); }
		c.mahalle.unlock(PLACE.mahalle);
		var p = c.mahalle.loading(Data.mahalleler(id)).then(function (items) {
			if (c.ilce.value !== String(id)) { return []; }
			var keep = self.c.mahalle.value;
			c.mahalle.setItems(C.rural ? items : items.filter(function (x) { return x.type === 'm' || x.type === 'k' || x.id === keep; }));
			return items;
		});
		p.catch(function () { /* hata ipucu gösterildi */ });
		return p;
	};

	Panel.prototype.setMahalle = function (it) {
		var self = this, c = this.c;
		c.sokak.lock(LOCKED.sokak);
		this.sokAd.value = '';
		if (!it) { return Promise.resolve([]); }
		this.mahAd.value = it.name;

		if (C.sokakMode === 'manual') {
			c.sokak.unlock(PLACE.sokak);
			c.sokak.setFree(true, 'Cadde veya sokak adını yazın');
			return Promise.resolve([]);
		}

		c.sokak.unlock(PLACE.sokak);
		var ilce = c.ilce.value;
		var p = c.sokak.loading(Data.sokaklar(ilce, it.id)).then(function (items) {
			if (c.mahalle.value !== String(it.id)) { return []; }
			if (!items.length) {
				c.sokak.setFree(true, 'Cadde veya sokak adını yazın');
				c.sokak.invalid(false);
				c.sokak.hint.textContent = 'Bu yerleşim için kayıtlı sokak listesi yok; adı elle yazabilirsiniz.';
			} else {
				c.sokak.setFree(false);
				c.sokak.setItems(items);
			}
			return items;
		});
		p.catch(function () {
			if (c.mahalle.value === String(it.id)) { c.sokak.setFree(true, 'Cadde veya sokak adını yazın'); }
		});
		return p;
	};

	Panel.prototype.onSelect = function (combo, it, changed, viaTab) {
		var self = this, next = null;

		if (combo.level === 'il') {
			if (changed) { this.setIl(it.id); }
			next = 'ilce';
		} else if (combo.level === 'ilce') {
			if (changed) { this.setIlce(it.id); }
			next = 'mahalle';
		} else if (combo.level === 'mahalle') {
			if (changed) { this.setMahalle(it); } else { this.mahAd.value = it.name; }
			next = 'sokak';
		} else if (combo.level === 'sokak') {
			this.sokAd.value = it.name;
			next = 'kapi';
		}

		this.refresh(combo.level === 'il' || combo.level === 'ilce' || combo.level === 'mahalle');

		if (!C.auto || viaTab) { return; }
		setTimeout(function () {
			if (next === 'kapi') {
				if (self.kapi && !self.kapi.value) { self.kapi.focus(); }
				return;
			}
			var n = self.c[next];
			if (n.value || n.text) { return; }
			if (n.freeText) { n.input.focus(); return; }
			if (isMobile()) {
				if (n.pending) { n.pending.then(function () { n.open(); }); } else { n.open(); }
			} else {
				n.input.focus();
			}
		}, isMobile() ? 280 : 30);
	};

	Panel.prototype.onFreeText = function (combo, value) {
		if (combo.level !== 'sokak') { return; }
		combo.text = value;
		combo.value = '';
		combo.hidden.value = '';
		this.sokAd.value = value.trim();
		combo.invalid(false);
		this.refresh(false);
	};

	/* ----- mevcut değerleri yükle ----- */

	Panel.prototype.hydrate = function () {
		var self = this, c = this.c;
		var il = c.il.hidden.value, ilce = c.ilce.hidden.value, mah = c.mahalle.hidden.value, sok = c.sokak.hidden.value;
		var mahAd = this.mahAd.value, sokAd = this.sokAd.value;

		if (!il) {
			var st = this.fieldId('state');
			var m = st && st.value && st.value.match(/^TR(\d{2})$/);
			if (m) {
				il = String(Number(m[1]));
				var city = this.fieldId('city');
				if (city && city.value) {
					var fc = fold(city.value).trim();
					(C.ilceler[il] || []).some(function (r) {
						if (fold(r[1]) === fc) { ilce = String(r[0]); return true; }
						return false;
					});
				}
				mah = sok = mahAd = sokAd = '';
			}
		}

		c.ilce.lock(LOCKED.ilce);
		c.mahalle.lock(LOCKED.mahalle);
		c.sokak.lock(LOCKED.sokak);

		if (!il || !ilMap[il]) { c.il.unlock(PLACE.il); return; }

		c.il.setValue(il, ilMap[il], pad2(Number(il)));
		c.il.unlock(PLACE.il);
		c.ilce.setItems(Data.ilceler(il));
		c.ilce.unlock(PLACE.ilce);

		var ilceRow = (C.ilceler[il] || []).filter(function (r) { return String(r[0]) === ilce; })[0];
		if (!ilceRow) { return; }
		c.ilce.setValue(ilce, ilceRow[1], '');

		this.setIlce(ilce).then(function (items) {
			if (c.ilce.value !== ilce) { return; }
			if (!mah) { self.refresh(false); return; }
			var it = items.filter(function (x) { return x.id === mah; })[0];
			if (!it && !mahAd) { self.refresh(false); return; }
			c.mahalle.setValue(mah, it ? it.name : mahAd, it ? it.badge : '');
			self.mahAd.value = it ? it.name : mahAd;

			if (C.sokakMode === 'manual') {
				c.sokak.unlock(PLACE.sokak);
				c.sokak.setFree(true, 'Cadde veya sokak adını yazın');
				c.sokak.text = sokAd; c.sokak.input.value = sokAd; self.sokAd.value = sokAd;
				self.refresh(false);
				return;
			}
			c.sokak.unlock(PLACE.sokak);
			c.sokak.loading(Data.sokaklar(ilce, mah)).then(function (sitems) {
				if (c.mahalle.value !== mah) { return; }
				if (!sitems.length) {
					c.sokak.setFree(true, 'Cadde veya sokak adını yazın');
					c.sokak.text = sokAd; c.sokak.input.value = sokAd; self.sokAd.value = sokAd;
				} else {
					c.sokak.setItems(sitems);
					var s = sok ? sitems.filter(function (x) { return x.id === sok; })[0] : null;
					if (s) { c.sokak.setValue(s.id, s.name, s.badge); self.sokAd.value = s.name; }
					else if (sokAd && C.sokakMode === 'list_manual') { c.sokak.setValue('', sokAd, 'Elle'); self.sokAd.value = sokAd; }
				}
				self.refresh(false);
			}).catch(function () {
				if (sokAd) { c.sokak.setFree(true); c.sokak.text = sokAd; c.sokak.input.value = sokAd; }
				self.refresh(false);
			});
		}).catch(function () {
			if (mahAd) { c.mahalle.setValue(mah, mahAd, ''); }
			self.refresh(false);
		});
	};

	/* ----- çekirdek WooCommerce alanları ----- */

	Panel.prototype.compose = function () {
		var c = this.c;
		var ilce = (C.ilceler[c.il.value] || []).filter(function (r) { return String(r[0]) === c.ilce.value; })[0];
		var mahItem = c.mahalle.items.filter(function (x) { return x.id === c.mahalle.value; })[0];
		var sok = (this.sokAd.value || '').trim();
		var kapi = this.kapi ? this.kapi.value.trim() : '';
		var daire = this.daire ? this.daire.value.trim() : '';
		var bina = this.bina ? this.bina.value.trim() : '';
		var tarif = this.tarif ? this.tarif.value.trim() : '';

		var line1 = '';
		if (this.mahAd.value) {
			line1 = this.mahAd.value;
			if (sok) { line1 += ', ' + sok; }
			if (kapi) { line1 += ' No: ' + kapi; }
			if (daire) { line1 += ' D: ' + daire; }
		}
		var l2 = [];
		if (bina) { l2.push(bina); }
		if (tarif) { l2.push('(' + tarif + ')'); }

		return {
			il: c.il.value ? ilMap[c.il.value] : '',
			state: c.il.value ? 'TR' + pad2(Number(c.il.value)) : '',
			city: ilce ? ilce[1] : '',
			address_1: line1,
			address_2: l2.join(' '),
			postcode: (mahItem && mahItem.pk) || (ilce ? ilce[2] : ''),
			done: {
				il: !!c.il.value,
				ilce: !!c.ilce.value,
				mahalle: !!c.mahalle.value,
				sokak: sok.length >= 2,
				kapi: C.kapiReq ? !!kapi : true,
				daire: C.daireReq ? !!daire : true
			}
		};
	};

	Panel.prototype.setCore = function (name, value) {
		var el = this.fieldId(name);
		if (!el || el.value === value) { return false; }
		if (el.tagName === 'SELECT' && value && !el.querySelector('option[value="' + value + '"]')) {
			var o = document.createElement('option');
			o.value = value;
			o.textContent = value;
			el.appendChild(o);
		}
		el.value = value;
		return true;
	};

	Panel.prototype.isTR = function () {
		var el = this.fieldId('country');
		return !el || !el.value || el.value === 'TR';
	};

	Panel.prototype.refresh = function (important) {
		var d = this.compose();
		this.lastCompose = d;

		if (this.isTR()) {
			var stChanged = this.setCore('state', d.state);
			if (stChanged) { $(this.fieldId('state')).trigger('change.select2'); }
			this.setCore('city', d.city);
			this.setCore('address_1', d.address_1);
			this.setCore('address_2', d.address_2);
			var pkChanged = this.setCore('postcode', d.postcode);

			if (this.isCheckout && (stChanged || pkChanged || important)) {
				clearTimeout(this.upd);
				this.upd = setTimeout(function () { $(document.body).trigger('update_checkout'); }, 250);
			}
		}

		var trail = this.root.querySelectorAll('.wdta__trail li');
		var cur = false;
		Array.prototype.forEach.call(trail, function (li) {
			var step = li.getAttribute('data-step');
			var ok = step === 'kapi' ? (d.done.kapi && d.done.daire && d.done.sokak) : d.done[step];
			li.classList.toggle('is-done', !!ok);
			li.classList.toggle('is-current', !ok && !cur);
			if (!ok) { cur = true; }
		});

		this.renderPreview(d);
	};

	/* ----- önizleme ----- */

	Panel.prototype.renderPreview = function (d) {
		if (!this.preview) { return; }
		d = d || this.lastCompose || this.compose();
		var pv = this.preview;
		var set = function (role, text, ph) {
			var el = pv.querySelector('[data-role="' + role + '"]');
			if (!el) { return; }
			el.textContent = '';
			if (text) { el.textContent = text; }
			else if (ph) { el.appendChild(h('span', 'wdta-label__ph', ph)); }
		};

		var name = function (g) {
			var f = document.getElementById(g + '_first_name'), l = document.getElementById(g + '_last_name');
			return ((f ? f.value : '') + ' ' + (l ? l.value : '')).trim();
		};
		var who = this.group === 'shipping' ? (name('shipping') || name('billing')) : name('billing');
		set('pv-name', who || '\u00a0');
		set('pv-line1', d.address_1, 'Mahalle, cadde/sokak ve kapı numarası');
		set('pv-line2', d.address_2, '');
		set('pv-city', d.city ? d.city + ' / ' + d.il : (d.il || ''), 'İlçe / İl');

		var pk = pv.querySelector('[data-role="pv-pk"]');
		pk.textContent = d.postcode || '·····';
		this.bars(pv.querySelector('[data-role="pv-bars"]'), d.postcode || '');

		var complete = d.done.il && d.done.ilce && d.done.mahalle && d.done.sokak && d.done.kapi && d.done.daire;
		pv.setAttribute('data-complete', complete ? 'true' : 'false');
	};

	Panel.prototype.bars = function (el, code) {
		if (!el || el.getAttribute('data-code') === code) { return; }
		el.setAttribute('data-code', code);
		var seed = 7;
		var src = code || '00000';
		for (var i = 0; i < src.length; i++) { seed = (seed * 31 + src.charCodeAt(i)) % 2147483647; }
		var rnd = function () { seed = (seed * 16807) % 2147483647; return seed / 2147483647; };
		var x = 0, out = '';
		while (x < 118) {
			var w = code ? (rnd() < 0.3 ? 3 : rnd() < 0.6 ? 2 : 1) : 1;
			out += '<rect x="' + x + '" y="0" width="' + w + '" height="34" fill="currentColor" opacity="' + (code ? 1 : 0.25) + '"/>';
			x += w + (rnd() < 0.5 ? 2 : 1.5);
		}
		el.innerHTML = out;
	};

	/* ----- ülke değişimi ----- */

	Panel.prototype.watchCountry = function () {
		var self = this;
		var ce = this.fieldId('country');
		if (ce && !ce.value && ce.tagName === 'SELECT' && ce.querySelector('option[value="TR"]')) {
			ce.value = 'TR';
			$(ce).trigger('change');
		}
		var apply = function () {
			var tr = self.isTR();
			document.body.classList.toggle('wdta-tr-' + self.group, tr);
			if (self.row) { self.row.classList.toggle('is-off', !tr); }
			if (tr) {
				var d = self.lastCompose;
				if (d && d.state) { self.setCore('state', d.state); }
			}
		};
		$(document.body).on('change', '#' + this.group + '_country', function () { apply(); if (self.isTR()) { self.refresh(true); } });
		$(document.body).on('country_to_state_changed', function () { setTimeout(apply, 0); });
		apply();
	};

	/* ----- doğrulama ----- */

	Panel.prototype.validate = function () {
		if (!this.isTR() || !this.row || this.row.offsetParent === null) { return true; }
		var d = this.compose(), c = this.c, first = null;
		var mark = function (field, msg) {
			field.classList.add('is-invalid', 'is-shake');
			var hint = field.querySelector('.wdta-f__hint');
			if (hint) { hint.textContent = msg; }
			setTimeout(function () { field.classList.remove('is-shake'); }, 400);
			if (!first) { first = field; }
		};
		if (!d.done.il) { mark(c.il.field, 'İl seçin.'); }
		else if (!d.done.ilce) { mark(c.ilce.field, 'İlçe seçin.'); }
		else if (!d.done.mahalle) { mark(c.mahalle.field, 'Mahalle veya köy seçin.'); }
		else if (!d.done.sokak) { mark(c.sokak.field, c.sokak.freeText ? 'Cadde veya sokak adını yazın.' : 'Cadde veya sokak seçin.'); }
		if (!d.done.kapi && this.kapi) { mark(this.kapi.closest('.wdta-f'), 'Kapı numarası gerekli.'); }
		if (!d.done.daire && this.daire) { mark(this.daire.closest('.wdta-f'), 'Daire numarası gerekli.'); }

		if (first) {
			first.scrollIntoView({ behavior: 'smooth', block: 'center' });
			var inp = first.querySelector('input:not([type="hidden"])');
			if (inp) { setTimeout(function () { inp.focus({ preventScroll: true }); }, 350); }
			return false;
		}
		return true;
	};

	Panel.prototype.bindValidation = function () {
		if (!C.validate || !this.form) { return; }
		var self = this;
		if (this.isCheckout) {
			$(this.form).on('checkout_place_order', function () {
				if (self.group === 'shipping') {
					var cb = document.getElementById('ship-to-different-address-checkbox');
					if (!cb || !cb.checked) { return undefined; }
				}
				return self.validate() ? undefined : false;
			});
		} else {
			this.form.addEventListener('submit', function (e) {
				if (!self.validate()) { e.preventDefault(); }
			});
		}
	};

	/* ------------------------------------------------------------------ */

	var boot = function () {
		document.querySelectorAll('[data-wdta]').forEach(function (root) {
			if (root.__wdta) { return; }
			root.__wdta = new Panel(root);
		});
	};

	if (document.readyState === 'loading') {
		document.addEventListener('DOMContentLoaded', boot);
	} else {
		boot();
	}
	$(document.body).on('updated_checkout', boot);

})(jQuery);
