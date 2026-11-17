/* Clinic Portal front-end behaviour. No dependencies. */
(function () {
	'use strict';

	document.documentElement.classList.add('cp-js');

	var $ = function (sel, ctx) { return (ctx || document).querySelector(sel); };
	var $$ = function (sel, ctx) { return Array.prototype.slice.call((ctx || document).querySelectorAll(sel)); };

	/* ------------------------------------------------------------ */
	/* Scroll reveal                                                 */
	/* ------------------------------------------------------------ */
	function initReveal() {
		var items = $$('.cp-reveal');
		if (!items.length) { return; }
		if (!('IntersectionObserver' in window) || window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
			items.forEach(function (el) { el.classList.add('is-visible'); });
			return;
		}
		var io = new IntersectionObserver(function (entries) {
			entries.forEach(function (en) {
				if (en.isIntersecting) {
					en.target.classList.add('is-visible');
					io.unobserve(en.target);
				}
			});
		}, { rootMargin: '0px 0px -8% 0px', threshold: 0.08 });
		items.forEach(function (el, i) {
			el.style.transitionDelay = Math.min(i % 4, 3) * 70 + 'ms';
			io.observe(el);
		});
	}

	/* ------------------------------------------------------------ */
	/* Hero call to action toggle                                    */
	/* ------------------------------------------------------------ */
	function initHeroCta() {
		var box = $('[data-cp-cta-config]');
		if (!box) { return; }
		var cfg;
		try { cfg = JSON.parse(box.getAttribute('data-cp-cta-config')); } catch (e) { return; }
		var primary = $('[data-cp-cta-primary]', box);
		var secondary = $('[data-cp-cta-secondary]', box);
		var text = $('[data-cp-cta-text]');
		var buttons = $$('[data-cp-cta]');
		buttons.forEach(function (btn) {
			btn.addEventListener('click', function () {
				var key = btn.getAttribute('data-cp-cta');
				var c = cfg[key];
				if (!c) { return; }
				buttons.forEach(function (b) {
					var on = b === btn;
					b.classList.toggle('is-active', on);
					b.setAttribute('aria-pressed', on ? 'true' : 'false');
				});
				if (text) { text.textContent = c.text; }
				if (primary) {
					primary.href = c.href;
					if (primary.firstChild && primary.firstChild.nodeType === 3) { primary.firstChild.nodeValue = c.label + ' '; }
				}
				if (secondary) { secondary.href = c.alth; secondary.textContent = c.alt; }
			});
		});
	}

	/* ------------------------------------------------------------ */
	/* Age from date of birth                                        */
	/* ------------------------------------------------------------ */
	function calcAge(value) {
		var p = (value || '').split('-');
		if (p.length !== 3) { return ''; }
		var y = +p[0], m = +p[1] - 1, d = +p[2];
		var b = new Date(y, m, d);
		if (isNaN(b.getTime()) || b.getFullYear() !== y || b.getMonth() !== m || b.getDate() !== d) { return ''; }
		var now = new Date();
		if (b > now) { return ''; }
		var age = now.getFullYear() - y;
		var diff = now.getMonth() - m;
		if (diff < 0 || (diff === 0 && now.getDate() < d)) { age--; }
		return age;
	}

	function initAge() {
		var dob = $('[data-cp-dob]');
		var age = $('[data-cp-age]');
		if (!dob || !age) { return; }
		var update = function () {
			var a = calcAge(dob.value);
			age.value = a === '' ? '' : (a === 0 ? '0 (under 1 year)' : String(a));
		};
		dob.addEventListener('input', update);
		dob.addEventListener('change', update);
		update();
	}

	/* ------------------------------------------------------------ */
	/* Validation                                                    */
	/* ------------------------------------------------------------ */
	function labelFor(el) {
		var lbl = el.id ? document.querySelector('label[for="' + el.id + '"]') : null;
		if (!lbl) { return 'This field'; }
		if (el.type === 'checkbox') { return 'This box'; }
		var t = lbl.firstChild && lbl.firstChild.nodeType === 3 ? lbl.firstChild.nodeValue : lbl.textContent;
		return t.replace(/\(optional\)/i, '').replace(/\*/g, '').trim() || 'This field';
	}

	function fieldError(el) {
		var v = (el.value || '').trim();
		if (el.type === 'checkbox') { return el.required && !el.checked ? 'Please tick this box to continue.' : ''; }
		if (el.required && !v) { return labelFor(el) + ' is required.'; }
		if (!v) { return ''; }
		if (el.type === 'email' && !/^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/.test(v)) { return 'Please enter a valid email address.'; }
		if (el.getAttribute('data-rule') === 'phone') {
			var d = v.replace(/[\s\-().]/g, '');
			if (!/^\+?\d{7,15}$/.test(d)) { return 'Please enter a valid phone number, digits only (7 to 15 digits).'; }
		}
		if (el.type === 'date') {
			if (calcAge(v) === '' && el.hasAttribute('data-cp-dob')) { return 'Please enter a valid date of birth that is not in the future.'; }
			if (el.min && v < el.min) { return el.hasAttribute('data-cp-dob') ? 'Please check the date of birth.' : 'Please choose today or a later date.'; }
			if (el.max && v > el.max) { return el.hasAttribute('data-cp-dob') ? 'Date of birth cannot be in the future.' : 'Appointments can be booked up to 90 days ahead.'; }
		}
		return '';
	}

	function showError(el, msg) {
		var wrap = el.closest('.cp-field');
		var out = document.getElementById(el.id + '-err');
		if (wrap) { wrap.classList.toggle('has-error', !!msg); }
		if (msg) { el.setAttribute('aria-invalid', 'true'); } else { el.removeAttribute('aria-invalid'); }
		if (out) { out.textContent = msg || ''; }
	}

	function initValidation() {
		$$('form[data-cp-validate]').forEach(function (form) {
			form.noValidate = true;
			var fields = function () {
				return $$('input, select, textarea', form).filter(function (el) {
					return el.type !== 'hidden' && el.type !== 'submit' && !el.readOnly && !el.disabled && !el.closest('.cp-hp') && el.id;
				});
			};
			fields().forEach(function (el) {
				var touched = false;
				var run = function () { showError(el, fieldError(el)); };
				el.addEventListener('blur', function () { touched = true; run(); });
				['input', 'change'].forEach(function (evt) {
					el.addEventListener(evt, function () { if (touched || el.getAttribute('aria-invalid')) { run(); } });
				});
			});
			form.addEventListener('submit', function (ev) {
				var first = null;
				fields().forEach(function (el) {
					var msg = fieldError(el);
					showError(el, msg);
					if (msg && !first) { first = el; }
				});
				if (first) {
					ev.preventDefault();
					first.focus();
					if (first.scrollIntoView) { first.scrollIntoView({ behavior: 'smooth', block: 'center' }); }
				}
			});
		});

		// Move focus to server side results so screen readers announce them.
		var note = $('.cp-notice[tabindex="-1"]');
		if (note) { note.focus({ preventScroll: false }); }
	}

	/* ------------------------------------------------------------ */
	/* Booking: department, physician, date and time                 */
	/* ------------------------------------------------------------ */
	function initBooking() {
		var form = $('[data-cp-booking]');
		if (!form) { return; }
		var slots = {};
		try { slots = JSON.parse(form.getAttribute('data-slots')); } catch (e) {}
		var dept = $('[data-cp-dept]', form);
		var phys = $('[data-cp-phys]', form);
		var date = $('[data-cp-date]', form);
		var time = $('[data-cp-time]', form);
		var note = $('[data-cp-slot-note]', form);
		var selected = form.getAttribute('data-selected-time') || '';

		function filterPhysicians() {
			var d = dept.value;
			$$('option', phys).forEach(function (o) {
				var od = o.getAttribute('data-dept');
				if (od === null) { return; }
				var show = !d || od === d;
				o.hidden = !show;
				o.disabled = !show;
			});
			var cur = phys.options[phys.selectedIndex];
			if (cur && cur.disabled) { phys.value = '0'; }
		}

		function setTimes() {
			var v = date.value;
			time.innerHTML = '';
			var add = function (val, label) {
				var o = document.createElement('option');
				o.value = val; o.textContent = label; time.appendChild(o);
			};
			note.textContent = '';
			note.classList.remove('is-warn');
			if (!v || !/^\d{4}-\d{2}-\d{2}$/.test(v)) { add('', 'Choose a date first'); return; }
			var p = v.split('-');
			var d = new Date(+p[0], +p[1] - 1, +p[2]);
			var wd = d.getDay() === 0 ? 7 : d.getDay();
			var list = (slots[wd] || []).slice();
			var today = new Date();
			if (d.toDateString() === today.toDateString()) {
				var nowMin = today.getHours() * 60 + today.getMinutes();
				list = list.filter(function (s) {
					var hm = s.value.split(':');
					return (+hm[0]) * 60 + (+hm[1]) > nowMin;
				});
			}
			if (!list.length) {
				add('', 'No times available');
				note.textContent = 'The clinic has no open slots on this date. Please choose another day.';
				note.classList.add('is-warn');
				return;
			}
			add('', 'Choose a time');
			list.forEach(function (s) { add(s.value, s.label); });
			if (selected) { time.value = selected; selected = ''; }
			note.textContent = list.length + ' time slots available on this date.';
		}

		dept.addEventListener('change', filterPhysicians);
		phys.addEventListener('change', function () {
			var o = phys.options[phys.selectedIndex];
			var od = o ? o.getAttribute('data-dept') : null;
			if (od && od !== '0' && dept.value !== od) { dept.value = od; filterPhysicians(); }
		});
		date.addEventListener('change', setTimes);
		date.addEventListener('input', setTimes);
		filterPhysicians();
		setTimes();
	}

	/* ------------------------------------------------------------ */
	/* Physician directory filter                                    */
	/* ------------------------------------------------------------ */
	function initDoctorFilter() {
		var bar = $('[data-cp-docfilter]');
		var grid = $('[data-cp-doc-grid]');
		if (!bar || !grid) { return; }
		var search = $('[data-cp-doc-search]', bar);
		var dept = $('[data-cp-doc-dept]', bar);
		var count = $('[data-cp-doc-count]', bar);
		var empty = $('[data-cp-doc-empty]');
		var cards = $$('.cp-doc', grid);
		function run() {
			var q = search.value.trim().toLowerCase();
			var d = dept.value;
			var shown = 0;
			cards.forEach(function (c) {
				var ok = (d === '0' || c.getAttribute('data-dept') === d) && (!q || c.getAttribute('data-search').indexOf(q) !== -1);
				c.hidden = !ok;
				if (ok) { shown++; }
			});
			count.textContent = 'Showing ' + shown + ' of ' + cards.length + ' physicians';
			if (empty) { empty.hidden = shown !== 0; }
		}
		search.addEventListener('input', run);
		dept.addEventListener('change', run);
		run();
	}

	/* ------------------------------------------------------------ */
	/* FAQ search                                                    */
	/* ------------------------------------------------------------ */
	function initFaq() {
		var wrap = $('[data-cp-faq]');
		if (!wrap) { return; }
		var input = $('[data-cp-faq-search]', wrap);
		var count = $('[data-cp-faq-count]', wrap);
		var empty = $('[data-cp-faq-empty]', wrap);
		var items = $$('.cp-faq__item', wrap);
		var sections = $$('[data-cp-faq-section]', wrap);
		input.addEventListener('input', function () {
			var q = input.value.trim().toLowerCase();
			var shown = 0;
			items.forEach(function (it) {
				var ok = !q || it.getAttribute('data-search').indexOf(q) !== -1;
				it.hidden = !ok;
				if (q && ok) { it.open = true; } else if (!q) { it.open = false; }
				if (ok) { shown++; }
			});
			sections.forEach(function (s) {
				s.hidden = !$$('.cp-faq__item', s).some(function (it) { return !it.hidden; });
			});
			count.textContent = q ? shown + ' matching question' + (shown === 1 ? '' : 's') : '';
			if (empty) { empty.hidden = shown !== 0; }
		});
	}

	/* ------------------------------------------------------------ */
	/* Small helpers: copy, print, open chat                         */
	/* ------------------------------------------------------------ */
	function initActions() {
		$$('[data-cp-copy]').forEach(function (btn) {
			btn.addEventListener('click', function () {
				var src = $('[data-cp-copy-source]');
				if (!src) { return; }
				var text = src.textContent.trim();
				var done = function () {
					var old = btn.textContent;
					btn.textContent = 'Copied';
					setTimeout(function () { btn.textContent = old; }, 1800);
				};
				if (navigator.clipboard && navigator.clipboard.writeText) {
					navigator.clipboard.writeText(text).then(done, function () {});
				} else {
					var ta = document.createElement('textarea');
					ta.value = text; document.body.appendChild(ta); ta.select();
					try { document.execCommand('copy'); done(); } catch (e) {}
					document.body.removeChild(ta);
				}
			});
		});
		$$('[data-cp-print]').forEach(function (b) { b.addEventListener('click', function () { window.print(); }); });
		$$('[data-cp-open-chat]').forEach(function (b) {
			b.addEventListener('click', function () {
				if (window.$crisp && window.$crisp.push) { window.$crisp.push(['do', 'chat:open']); }
			});
		});
	}

	function ready(fn) {
		if (document.readyState !== 'loading') { fn(); } else { document.addEventListener('DOMContentLoaded', fn); }
	}

	ready(function () {
		initReveal();
		initHeroCta();
		initAge();
		initValidation();
		initBooking();
		initDoctorFilter();
		initFaq();
		initActions();
	});
})();
