/**
 * EasyForms frontend submit handler.
 *
 * Progressive enhancement: intercepts form submit, posts to the REST endpoint,
 * and renders inline validation errors / confirmation. Forms still validate
 * server-side if JS is unavailable.
 */
import './form.scss';

(function () {
  'use strict';

  var cfg = window.EasyFormsFront || {};

  function serialize(form) {
    var data = {};
    var elements = form.querySelectorAll('input, select, textarea');
    elements.forEach(function (el) {
      if (!el.name || el.disabled) return;
      var name = el.name;
      if (el.type === 'checkbox') {
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
    form.querySelectorAll('.easyforms-error').forEach(function (e) { e.textContent = ''; });
    form.querySelectorAll('.easyforms-field--invalid').forEach(function (e) { e.classList.remove('easyforms-field--invalid'); });
  }

  function showErrors(form, errors) {
    Object.keys(errors || {}).forEach(function (key) {
      var field = form.querySelector('[data-field="' + key + '"]');
      if (field) {
        field.classList.add('easyforms-field--invalid');
        var err = field.querySelector('.easyforms-error');
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
      payload.easyforms_source_url = window.location.href;

      fetch(cfg.restUrl, {
        method: 'POST',
        headers: { 'Content-Type': 'application/json', 'X-WP-Nonce': cfg.nonce },
        credentials: 'same-origin',
        body: JSON.stringify(payload),
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Submit'; }
          var msg = form.querySelector('.easyforms-form-message');
          if (res.success) {
            if (res.confirmation && res.confirmation.type === 'redirect' && res.confirmation.url) {
              window.location.href = res.confirmation.url;
              return;
            }
            var html = (res.confirmation && res.confirmation.message) || 'Thank you!';
            form.querySelector('.easyforms-fields').style.display = 'none';
            if (msg) { msg.className = 'easyforms-form-message easyforms-form-message--success'; msg.innerHTML = html; }
          } else {
            if (res.errors) showErrors(form, res.errors);
            if (msg) { msg.className = 'easyforms-form-message easyforms-form-message--error'; msg.textContent = res.message || 'Please check the form.'; }
          }
        })
        .catch(function () {
          if (btn) { btn.disabled = false; btn.textContent = btn.dataset.label || 'Submit'; }
        });
    });
  }

  // Apply a simple pattern mask to an input. Tokens: 9=digit, a=letter, *=any.
  function applyMask(el) {
    var mask = el.getAttribute('data-easyforms-mask');
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

  // Turn a <select data-easyforms-searchable> into a searchable dropdown while
  // keeping the native select in the DOM so its value still submits.
  function searchableSelect(select) {
    if (select.dataset.efSearchable) return;
    select.dataset.efSearchable = '1';

    var options = Array.prototype.map.call(select.options, function (o) {
      return { value: o.value, label: o.textContent };
    });

    var wrap = document.createElement('div');
    wrap.className = 'easyforms-ss';
    select.parentNode.insertBefore(wrap, select);
    wrap.appendChild(select);
    select.classList.add('easyforms-ss__native');

    var control = document.createElement('button');
    control.type = 'button';
    control.className = 'easyforms-ss__control';

    function selectedLabel() {
      var o = select.options[select.selectedIndex];
      return o ? o.textContent : '';
    }
    control.textContent = selectedLabel();

    var panel = document.createElement('div');
    panel.className = 'easyforms-ss__panel';
    var search = document.createElement('input');
    search.type = 'text';
    search.className = 'easyforms-ss__search';
    search.placeholder = 'Search…';
    var listEl = document.createElement('div');
    listEl.className = 'easyforms-ss__list';
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
        item.className = 'easyforms-ss__item' + (opt.value === select.value ? ' is-sel' : '');
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

  document.addEventListener('DOMContentLoaded', function () {
    document.querySelectorAll('.easyforms-form').forEach(handle);
    document.querySelectorAll('[data-easyforms-mask]').forEach(applyMask);
    document.querySelectorAll('[data-easyforms-autoresize]').forEach(autoResize);
    document.querySelectorAll('select[data-easyforms-searchable]').forEach(searchableSelect);
  });
})();
