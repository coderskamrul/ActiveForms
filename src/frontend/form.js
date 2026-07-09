/**
 * ActiveForms frontend submit handler.
 *
 * Progressive enhancement: intercepts form submit, posts to the REST endpoint,
 * and renders inline validation errors / confirmation. Forms still validate
 * server-side if JS is unavailable.
 */
import './form.scss';

(function () {
  'use strict';

  var cfg = window.ActiveFormsFront || {};

  function serialize(form) {
    var data = {};
    var elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(function (el) {
      if (!el.name || el.disabled) return;
      var name = el.name;
      if (el.type === 'select-multiple') {
        var msKey = name.slice(-2) === '[]' ? name.slice(0, -2) : name;
        data[msKey] = Array.prototype.map.call(el.selectedOptions, function (o) { return o.value; });
      } else if (el.type === 'checkbox') {
        if (name.slice(-2) === '[]') {
          var key = name.slice(0, -2);
          data[key] = data[key] || [];
          if (el.checked) data[key].push(el.value);
        } else {
          data[name] = el.checked ? 1 : 0;
        }
      } else if (el.type === 'radio') {
        if (el.checked) data[name] = el.value;
      } else if (name.indexOf('[') > -1) {
        // Composite sub-field: name="key[sub]".
        var match = name.match(/^([^\[]+)\[([^\]]+)\]$/);
        if (match) {
          data[match[1]] = data[match[1]] || {};
          data[match[1]][match[2]] = el.value;
        }
      } else {
        data[name] = el.value;
      }
    });
    return data;
  }

  function clearErrors(form) {
    form.querySelectorAll('.activeforms-error').forEach(function (e) { e.textContent = ''; });
    form.querySelectorAll('.activeforms-field--invalid').forEach(function (e) { e.classList.remove('activeforms-field--invalid'); });
  }

  function showErrors(form, errors) {
    Object.keys(errors || {}).forEach(function (key) {
      var field = form.querySelector('[data-field="' + key + '"]');
      if (field) {
        field.classList.add('activeforms-field--invalid');
        var err = field.querySelector('.activeforms-error');
        if (err) err.textContent = errors[key];
      }
    });
  }

  function handle(form) {
    form.addEventListener('submit', function (event) {
      event.preventDefault();
      clearErrors(form);

      var btn = form.querySelector('button[type="submit"]');
      if (btn) { btn.disabled = true; btn.dataset.label = btn.textContent; btn.textContent = '…'; }

      var payload = serialize(form);
      payload.activeforms_source_url = window.location.href;

      fetch(cfg.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Submit'; }
          var msg = form.querySelector('.activeforms-form-message');
          if (res.success) {
            if (res.confirmation && res.confirmation.type === 'redirect' && res.confirmation.url) {
              window.location.href = res.confirmation.url;
              return;
            }
            var html = (res.confirmation && res.confirmation.message) || 'Thank you!';
            form.querySelector('.activeforms-fields').style.display = 'none';
            if (msg) { msg.className = 'activeforms-form-message activeforms-form-message--success'; msg.innerHTML = html; }
          } else {
            if (res.errors) showErrors(form, res.errors);
            if (msg) { msg.className = 'activeforms-form-message activeforms-form-message--error'; msg.textContent = res.message || 'Please check the form.'; }
          }
        })
        .catch(function () {
          if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Submit'; }
        });
    });
  }

  // Apply a simple pattern mask to an input. Tokens: 9=digit, a=letter, *=any.
  function applyMask(el) {
    var mask = el.getAttribute('data-activeforms-mask');
    if (!mask) return;
    function format(value) {
      var out = '';
      var vi = 0;
      for (var mi = 0; mi < mask.length && vi < value.length; mi++) {
        var token = mask[mi];
        var ch = value[vi];
        if (token === '9') {
          if (/[0-9]/.test(ch)) { out += ch; vi++; } else { vi++; mi--; }
        } else if (token === 'a') {
          if (/[a-zA-Z]/.test(ch)) { out += ch; vi++; } else { vi++; mi--; }
        } else if (token === '*') {
          out += ch; vi++;
        } else {
          out += token;
          if (ch === token) vi++;
        }
      }
      return out;
    }
    el.addEventListener('input', function () {
      el.value = format(el.value);
    });
  }

  // Grow a textarea to fit its content.
  function autoResize(el) {
    function fit() { el.style.height = 'auto'; el.style.height = el.scrollHeight + 'px'; }
    el.style.overflow = 'hidden';
    el.addEventListener('input', fit);
    fit();
  }

  // Turn a <select data-activeforms-searchable> into a searchable dropdown while
  // keeping the native select in the DOM so its value still submits.
  function searchableSelect(select) {
    if (select.dataset.efSearchable) return;
    select.dataset.efSearchable = '1';

    var options = Array.prototype.map.call(select.options, function (o) {
      return { value: o.value, label: o.textContent };
    });

    var wrap = document.createElement('div');
    wrap.className = 'activeforms-ss';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('activeforms-ss__native');

    var control = document.createElement('button');
    control.type = 'button';
    control.className = 'activeforms-ss__control';

    function selectedLabel() {
      var o = select.options[select.selectedIndex];
      return o ? o.textContent : '';
    }
    control.textContent = selectedLabel();

    var panel = document.createElement('div');
    panel.className = 'activeforms-ss__panel';
    var search = document.createElement('input');
    search.type = 'text';
    search.className = 'activeforms-ss__search';
    search.placeholder = 'Search…';
    var listEl = document.createElement('div');
    listEl.className = 'activeforms-ss__list';
    panel.appendChild(search);
    panel.appendChild(listEl);
    wrap.appendChild(control);
    wrap.appendChild(panel);

    function close() { wrap.classList.remove('is-open'); }
    function open() {
      wrap.classList.add('is-open');
      search.value = '';
      renderList('');
      setTimeout(function () { search.focus(); }, 0);
    }

    function renderList(q) {
      listEl.innerHTML = '';
      var ql = (q || '').toLowerCase();
      options.forEach(function (opt) {
        if (!opt.value && ql) return; // hide the placeholder while searching
        if (ql && opt.label.toLowerCase().indexOf(ql) === -1) return;
        var item = document.createElement('div');
        item.className = 'activeforms-ss__item' + (opt.value === select.value ? ' is-sel' : '');
        item.textContent = opt.label;
        item.addEventListener('mousedown', function (e) {
          e.preventDefault();
          select.value = opt.value;
          select.dispatchEvent(new Event('change', { bubbles: true }));
          control.textContent = opt.label;
          close();
        });
        listEl.appendChild(item);
      });
    }

    control.addEventListener('click', function () {
      wrap.classList.contains('is-open') ? close() : open();
    });
    search.addEventListener('input', function () { renderList(search.value); });
    search.addEventListener('keydown', function (e) {
      if (e.key === 'Escape') { close(); control.focus(); }
    });
    document.addEventListener('click', function (e) {
      if (!wrap.contains(e.target)) close();
    });
  }

  // Enhance a native multiple <select> into a tag-style picker. The native
  // select stays in the DOM (visually hidden) so its selected options still
  // submit as key[]; the widget just mirrors its state.
  function multiSelect(select) {
    if (select.dataset.efMulti) return;
    select.dataset.efMulti = '1';

    var placeholder = select.getAttribute('data-placeholder') || 'Select…';
    var options = Array.prototype.map.call(select.options, function (o) {
      return { value: o.value, label: o.textContent };
    });

    var wrap = document.createElement('div');
    wrap.className = 'activeforms-ms';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('activeforms-ms__native');

    var control = document.createElement('div');
    control.className = 'activeforms-ms__control';
    control.tabIndex = 0;
    wrap.appendChild(control);

    var panel = document.createElement('div');
    panel.className = 'activeforms-ms__panel';
    wrap.appendChild(panel);

    function isSel(v) {
      return Array.prototype.some.call(select.options, function (o) { return o.value === v && o.selected; });
    }
    function setSel(v, on) {
      Array.prototype.forEach.call(select.options, function (o) { if (o.value === v) o.selected = on; });
      select.dispatchEvent(new Event('change', { bubbles: true }));
      render();
    }
    function render() {
      control.innerHTML = '';
      var chosen = options.filter(function (o) { return isSel(o.value); });
      if (!chosen.length) {
        var ph = document.createElement('span');
        ph.className = 'activeforms-ms__ph';
        ph.textContent = placeholder;
        control.appendChild(ph);
      } else {
        chosen.forEach(function (o) {
          var tag = document.createElement('span');
          tag.className = 'activeforms-ms__tag';
          tag.textContent = o.label;
          var x = document.createElement('button');
          x.type = 'button';
          x.className = 'activeforms-ms__remove';
          x.setAttribute('aria-label', 'Remove ' + o.label);
          x.innerHTML = '&times;';
          x.addEventListener('click', function (e) { e.stopPropagation(); setSel(o.value, false); });
          tag.appendChild(x);
          control.appendChild(tag);
        });
      }
      panel.innerHTML = '';
      options.forEach(function (o) {
        var item = document.createElement('div');
        var on = isSel(o.value);
        item.className = 'activeforms-ms__item' + (on ? ' is-sel' : '');
        var check = document.createElement('span');
        check.className = 'activeforms-ms__check';
        check.textContent = on ? '✓' : '';
        var lbl = document.createElement('span');
        lbl.textContent = o.label;
        item.appendChild(check);
        item.appendChild(lbl);
        item.addEventListener('click', function () { setSel(o.value, !isSel(o.value)); });
        panel.appendChild(item);
      });
    }

    control.addEventListener('click', function () { wrap.classList.toggle('is-open'); });
    // mousedown (not click): toggling an option rebuilds the panel, so a bubbled
    // click would see the detached option and close the picker after one pick.
    document.addEventListener('mousedown', function (e) { if (!wrap.contains(e.target)) wrap.classList.remove('is-open'); });
    render();
  }

  // ---- Custom date / time / range picker --------------------------------
  // A dependency-free picker driven entirely by the field's data-attributes.
  // Because it lives in form.js it runs identically on the front end and the
  // form preview page. Format tokens follow the flatpickr vocabulary the field
  // settings expose (Y y m n d j M H h i K).
  var EF_MONTHS = ['January', 'February', 'March', 'April', 'May', 'June', 'July', 'August', 'September', 'October', 'November', 'December'];
  var EF_SHORT = ['Jan', 'Feb', 'Mar', 'Apr', 'May', 'Jun', 'Jul', 'Aug', 'Sep', 'Oct', 'Nov', 'Dec'];
  var EF_DOW = ['Su', 'Mo', 'Tu', 'We', 'Th', 'Fr', 'Sa'];

  function efPad(n) { return (n < 10 ? '0' : '') + n; }

  function efFormat(d, format) {
    var H = d.getHours();
    var h12 = ((H + 11) % 12) + 1;
    var map = {
      Y: d.getFullYear(), y: efPad(d.getFullYear() % 100),
      m: efPad(d.getMonth() + 1), n: d.getMonth() + 1,
      d: efPad(d.getDate()), j: d.getDate(), M: EF_SHORT[d.getMonth()],
      H: efPad(H), h: efPad(h12), i: efPad(d.getMinutes()), K: H < 12 ? 'AM' : 'PM',
    };
    var out = '';
    for (var i = 0; i < format.length; i++) {
      var ch = format.charAt(i);
      out += Object.prototype.hasOwnProperty.call(map, ch) ? map[ch] : ch;
    }
    return out;
  }

  function datePicker(input) {
    if (input.dataset.efPicker) return;
    input.dataset.efPicker = '1';

    var mode = input.getAttribute('data-ef-mode') || 'date';
    var format = input.getAttribute('data-ef-format') || 'm/d/Y';
    var hasCal = mode !== 'time';
    var hasTime = mode === 'time' || mode === 'datetime';
    var isRange = mode === 'range';
    var is24 = format.indexOf('H') > -1;

    var today = new Date();
    var view = new Date(today.getFullYear(), today.getMonth(), 1);
    var start = null, end = null;

    // Time state — seeded with the current time for a sensible default.
    var hour, minute = today.getMinutes(), ampm = today.getHours() < 12 ? 'AM' : 'PM';
    hour = is24 ? today.getHours() : (((today.getHours() + 11) % 12) + 1);

    var field = input.closest('.activeforms-dp-field') || input.parentNode;
    var pop = document.createElement('div');
    pop.className = 'activeforms-dp';
    field.appendChild(pop);

    function stripTime(d) { return new Date(d.getFullYear(), d.getMonth(), d.getDate()); }
    function sameDay(a, b) { return !!(a && b && a.getFullYear() === b.getFullYear() && a.getMonth() === b.getMonth() && a.getDate() === b.getDate()); }
    function inRange(d) { return !!(isRange && start && end && d > stripTime(start) && d < stripTime(end)); }

    function withTime(base) {
      var d = base ? new Date(base) : new Date();
      var H = is24 ? hour : ((hour % 12) + (ampm === 'PM' ? 12 : 0));
      d.setHours(H, minute, 0, 0);
      return d;
    }

    function commit(close) {
      var val = '';
      if (mode === 'time') {
        val = efFormat(withTime(today), format);
      } else if (isRange) {
        if (start) { val = efFormat(start, format); }
        if (start && end) { val += ' to ' + efFormat(end, format); }
      } else if (mode === 'datetime') {
        if (start) { val = efFormat(withTime(start), format); }
      } else if (start) {
        val = efFormat(start, format);
      }
      input.value = val;
      if (close) { hide(); }
    }

    function pickDay(d) {
      if (isRange) {
        if (!start || (start && end)) { start = d; end = null; } else if (d < start) { end = start; start = d; } else { end = d; }
        build();
        commit(!!(start && end));
      } else {
        start = d;
        build();
        commit(mode !== 'datetime');
      }
    }

    function el(tag, cls, txt) { var e = document.createElement(tag); if (cls) { e.className = cls; } if (txt != null) { e.textContent = txt; } return e; }
    function nav(txt, fn) { var b = el('button', 'activeforms-dp__nav', txt); b.type = 'button'; b.addEventListener('click', fn); return b; }

    function mkSelect(min, max, val, onCh) {
      var s = el('select', 'activeforms-dp__sel');
      for (var i = min; i <= max; i++) { var o = el('option', '', efPad(i)); o.value = i; s.appendChild(o); }
      s.value = val;
      s.addEventListener('change', function () { onCh(parseInt(s.value, 10)); });
      return s;
    }

    function buildCalendar() {
      var cal = el('div', 'activeforms-dp__cal');
      var head = el('div', 'activeforms-dp__head');
      
      // Previous month button
      head.appendChild(nav('‹', function () { view.setMonth(view.getMonth() - 1); build(); }));
      
      // Month dropdown
      var monthSel = el('select', 'activeforms-dp__month-sel');
      EF_MONTHS.forEach(function (month, idx) {
        var o = el('option', '', month);
        o.value = idx;
        monthSel.appendChild(o);
      });
      monthSel.value = view.getMonth();
      monthSel.addEventListener('change', function () {
        view.setMonth(parseInt(monthSel.value, 10));
        build();
      });
      head.appendChild(monthSel);
      
      // Year spinner input
      var yearWrap = el('div', 'activeforms-dp__year-wrap');
      var yearInput = el('input', 'activeforms-dp__year-input');
      yearInput.type = 'number';
      yearInput.value = view.getFullYear();
      yearInput.min = '1900';
      yearInput.max = '2100';
      yearInput.addEventListener('change', function () {
        var yr = parseInt(yearInput.value, 10);
        if (yr >= 1900 && yr <= 2100) {
          view.setFullYear(yr);
          build();
        } else {
          yearInput.value = view.getFullYear();
        }
      });
      yearWrap.appendChild(yearInput);
      head.appendChild(yearWrap);
      
      // Next month button
      head.appendChild(nav('›', function () { view.setMonth(view.getMonth() + 1); build(); }));
      
      cal.appendChild(head);

      var dow = el('div', 'activeforms-dp__dow');
      EF_DOW.forEach(function (w) { dow.appendChild(el('span', '', w)); });
      cal.appendChild(dow);

      var grid = el('div', 'activeforms-dp__grid');
      var first = new Date(view.getFullYear(), view.getMonth(), 1);
      var gridStart = new Date(view.getFullYear(), view.getMonth(), 1 - first.getDay());
      for (var i = 0; i < 42; i++) {
        var d = new Date(gridStart.getFullYear(), gridStart.getMonth(), gridStart.getDate() + i);
        var cell = el('button', 'activeforms-dp__day', String(d.getDate()));
        cell.type = 'button';
        if (d.getMonth() !== view.getMonth()) { cell.classList.add('is-out'); }
        if (sameDay(d, today)) { cell.classList.add('is-today'); }
        if (sameDay(d, start) || sameDay(d, end)) { cell.classList.add('is-sel'); }
        if (inRange(d)) { cell.classList.add('is-in'); }
        (function (dd) { cell.addEventListener('click', function () { pickDay(stripTime(dd)); }); })(d);
        grid.appendChild(cell);
      }
      cal.appendChild(grid);
      return cal;
    }

    function buildTime() {
      var t = el('div', 'activeforms-dp__time');
      t.appendChild(mkSelect(is24 ? 0 : 1, is24 ? 23 : 12, hour, function (v) { hour = v; commit(false); }));
      t.appendChild(el('span', 'activeforms-dp__colon', ':'));
      t.appendChild(mkSelect(0, 59, minute, function (v) { minute = v; commit(false); }));
      if (!is24) {
        var ap = el('div', 'activeforms-dp__ampm');
        ['AM', 'PM'].forEach(function (p) {
          var b = el('button', 'activeforms-dp__ap' + (ampm === p ? ' is-active' : ''), p);
          b.type = 'button';
          b.addEventListener('click', function () { ampm = p; build(); commit(false); });
          ap.appendChild(b);
        });
        t.appendChild(ap);
      }
      return t;
    }

    function buildFooter() {
      var f = el('div', 'activeforms-dp__foot');
      var clear = el('button', 'activeforms-dp__btn', 'Clear');
      clear.type = 'button';
      clear.addEventListener('click', function () { start = null; end = null; input.value = ''; build(); });
      var done = el('button', 'activeforms-dp__btn activeforms-dp__btn--primary', 'Done');
      done.type = 'button';
      done.addEventListener('click', function () { commit(true); });
      f.appendChild(clear);
      f.appendChild(done);
      return f;
    }

    function build() {
      pop.innerHTML = '';
      if (hasCal) { pop.appendChild(buildCalendar()); }
      if (hasTime) { pop.appendChild(buildTime()); }
      pop.appendChild(buildFooter());
    }

    function show() { build(); field.classList.add('is-open'); }
    function hide() { field.classList.remove('is-open'); }

    input.addEventListener('focus', show);
    input.addEventListener('click', show);
    input.addEventListener('keydown', function (e) { if (e.key === 'Escape') { hide(); } });
    // Use mousedown, not click: selecting a day rebuilds the popup (detaching the
    // clicked node), so a bubbled click would see a node no longer inside `field`
    // and wrongly close the popup after the first pick (breaking range/datetime).
    document.addEventListener('mousedown', function (e) { if (!field.contains(e.target)) { hide(); } });
  }

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.activeforms-form').forEach(handle);
    document.querySelectorAll('[data-activeforms-mask]').forEach(applyMask);
    document.querySelectorAll('[data-activeforms-autoresize]').forEach(autoResize);
    document.querySelectorAll('select[data-activeforms-searchable]').forEach(searchableSelect);
    document.querySelectorAll('select[data-activeforms-multiselect]').forEach(multiSelect);
    document.querySelectorAll('[data-activeforms-datepicker]').forEach(datePicker);
  });
})();
