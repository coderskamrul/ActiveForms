/**
 * RadiusForms Pro — frontend behaviors for advanced fields.
 *
 * Vanilla JS (no build step). Enhances markup rendered by the Pro field classes:
 *   - Signature : <canvas> pad → PNG data URL in a hidden input
 *   - Rich text : contenteditable toolbar mirrored into a <textarea>
 *   - Repeater  : add/remove rows kept as JSON in a hidden input
 *   - Upload    : async upload to the Pro REST endpoint, references in a hidden input
 *
 * Each enhancer is idempotent (guards against double-init) so it is safe to run
 * again if forms are injected dynamically.
 */
(function () {
  'use strict';

  var cfg = window.RadiusFormsProFront || {};

  /* ------------------------------------------------------------------ utils */

  function ready(fn) {
    if (document.readyState !== 'loading') fn();
    else document.addEventListener('DOMContentLoaded', fn);
  }

  function once(el, flag) {
    if (el.dataset[flag]) return false;
    el.dataset[flag] = '1';
    return true;
  }

  function setFieldError(root, message) {
    var field = root.closest('.radiusforms-field');
    if (!field) return;
    var box = field.querySelector('.radiusforms-error');
    if (box) box.textContent = message || '';
    field.classList.toggle('radiusforms-field--invalid', !!message);
  }

  /* -------------------------------------------------------------- signature */

  function initSignature(root) {
    if (!once(root, 'efProSig')) return;
    var canvas = root.querySelector('.radiusforms-signature__pad');
    var input = root.querySelector('[data-radiusforms-signature-input]');
    var clear = root.querySelector('.radiusforms-signature__clear');
    if (!canvas || !input) return;

    var ctx = canvas.getContext('2d');
    var drawing = false;
    var dirty = false;
    var last = null;

    // Scale the backing store for crisp lines on HiDPI screens.
    function resize() {
      var ratio = window.devicePixelRatio || 1;
      var rect = canvas.getBoundingClientRect();
      if (!rect.width) return;
      canvas.width = rect.width * ratio;
      canvas.height = rect.height * ratio;
      ctx.scale(ratio, ratio);
      ctx.lineJoin = 'round';
      ctx.lineCap = 'round';
      ctx.lineWidth = 2;
      ctx.strokeStyle = '#1f2937';
    }
    resize();

    function pos(e) {
      var rect = canvas.getBoundingClientRect();
      var src = e.touches ? e.touches[0] : e;
      return { x: src.clientX - rect.left, y: src.clientY - rect.top };
    }

    function start(e) {
      e.preventDefault();
      // First stroke clears any restored background image so we capture fresh.
      if (root.dataset.efProRestored) {
        canvas.style.backgroundImage = 'none';
        delete root.dataset.efProRestored;
      }
      drawing = true;
      last = pos(e);
    }

    function move(e) {
      if (!drawing) return;
      e.preventDefault();
      var p = pos(e);
      ctx.beginPath();
      ctx.moveTo(last.x, last.y);
      ctx.lineTo(p.x, p.y);
      ctx.stroke();
      last = p;
      dirty = true;
    }

    function end() {
      if (!drawing) return;
      drawing = false;
      if (dirty) {
        input.value = canvas.toDataURL('image/png');
        setFieldError(root, '');
      }
    }

    if (canvas.style.backgroundImage && canvas.style.backgroundImage !== 'none') {
      root.dataset.efProRestored = '1';
    }

    canvas.addEventListener('mousedown', start);
    canvas.addEventListener('mousemove', move);
    window.addEventListener('mouseup', end);
    canvas.addEventListener('touchstart', start, { passive: false });
    canvas.addEventListener('touchmove', move, { passive: false });
    canvas.addEventListener('touchend', end);

    if (clear) {
      clear.addEventListener('click', function () {
        ctx.clearRect(0, 0, canvas.width, canvas.height);
        canvas.style.backgroundImage = 'none';
        delete root.dataset.efProRestored;
        input.value = '';
        dirty = false;
      });
    }
  }

  /* -------------------------------------------------------------- rich text */

  var RT_COMMANDS = [
    { cmd: 'bold', label: 'B', title: 'Bold', style: 'font-weight:700' },
    { cmd: 'italic', label: 'I', title: 'Italic', style: 'font-style:italic' },
    { cmd: 'underline', label: 'U', title: 'Underline', style: 'text-decoration:underline' },
    { cmd: 'insertUnorderedList', label: '••', title: 'Bullet list', style: '' },
    { cmd: 'insertOrderedList', label: '1.', title: 'Numbered list', style: '' },
    { cmd: 'createLink', label: '🔗', title: 'Insert link', style: '' },
  ];

  function initRichText(root) {
    if (!once(root, 'efProRt')) return;
    var textarea = root.querySelector('.radiusforms-richtext__source');
    if (!textarea) return;

    var toolbar = document.createElement('div');
    toolbar.className = 'radiusforms-richtext__toolbar';

    var editor = document.createElement('div');
    editor.className = 'radiusforms-richtext__editor';
    editor.contentEditable = 'true';
    editor.innerHTML = textarea.value || '';

    RT_COMMANDS.forEach(function (item) {
      var btn = document.createElement('button');
      btn.type = 'button';
      btn.className = 'radiusforms-richtext__btn';
      btn.title = item.title;
      btn.innerHTML = item.label;
      if (item.style) btn.setAttribute('style', item.style);
      btn.addEventListener('mousedown', function (e) { e.preventDefault(); });
      btn.addEventListener('click', function () {
        if (item.cmd === 'createLink') {
          var url = window.prompt('Link URL');
          if (url) document.execCommand('createLink', false, url);
        } else {
          document.execCommand(item.cmd, false, null);
        }
        sync();
        editor.focus();
      });
      toolbar.appendChild(btn);
    });

    function sync() {
      textarea.value = editor.innerHTML;
    }
    editor.addEventListener('input', sync);
    editor.addEventListener('blur', sync);

    textarea.style.display = 'none';
    textarea.setAttribute('aria-hidden', 'true');
    root.appendChild(toolbar);
    root.appendChild(editor);
  }

  /* --------------------------------------------------------------- repeater */

  function initRepeater(root) {
    if (!once(root, 'efProRep')) return;
    var body = root.querySelector('.radiusforms-repeater__rows');
    var addBtn = root.querySelector('.radiusforms-repeater__add');
    var input = root.querySelector('[data-radiusforms-repeater-input]');
    if (!body || !input) return;

    var columns = [];
    var max = parseInt(root.getAttribute('data-max'), 10) || 0;
    try { columns = JSON.parse(root.getAttribute('data-columns') || '[]'); } catch (e) { columns = []; }
    if (!columns.length) columns = [{ key: 'col_1', label: 'Item' }];

    var rows = [];
    try { rows = JSON.parse(input.value || '[]'); } catch (e) { rows = []; }
    if (!Array.isArray(rows) || !rows.length) rows = [emptyRow()];

    function emptyRow() {
      var r = {};
      columns.forEach(function (c) { r[c.key] = ''; });
      return r;
    }

    function serialize() {
      input.value = JSON.stringify(rows);
    }

    function render() {
      body.innerHTML = '';
      rows.forEach(function (row, idx) {
        var tr = document.createElement('div');
        tr.className = 'radiusforms-repeater__row';
        columns.forEach(function (col) {
          var cell = document.createElement('input');
          cell.type = 'text';
          cell.className = 'radiusforms-input';
          cell.placeholder = col.label || col.key;
          cell.value = row[col.key] || '';
          cell.addEventListener('input', function () {
            rows[idx][col.key] = cell.value;
            serialize();
          });
          tr.appendChild(cell);
        });
        var rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'radiusforms-repeater__rm';
        rm.setAttribute('aria-label', 'Remove row');
        rm.innerHTML = '&times;';
        rm.addEventListener('click', function () {
          rows.splice(idx, 1);
          if (!rows.length) rows.push(emptyRow());
          serialize();
          render();
        });
        tr.appendChild(rm);
        body.appendChild(tr);
      });
      if (addBtn) addBtn.disabled = max > 0 && rows.length >= max;
    }

    if (addBtn) {
      addBtn.addEventListener('click', function () {
        if (max > 0 && rows.length >= max) return;
        rows.push(emptyRow());
        serialize();
        render();
      });
    }

    serialize();
    render();
  }

  /* ----------------------------------------------------------------- upload */

  function humanSize(bytes) {
    if (bytes > 1048576) return (bytes / 1048576).toFixed(1) + ' MB';
    if (bytes > 1024) return Math.round(bytes / 1024) + ' KB';
    return bytes + ' B';
  }

  function initUpload(root) {
    if (!once(root, 'efProUp')) return;
    var fileInput = root.querySelector('.radiusforms-upload__input');
    var list = root.querySelector('.radiusforms-upload__list');
    var errorBox = root.querySelector('.radiusforms-upload__error');
    var hidden = root.querySelector('[data-radiusforms-upload-input]');
    var drop = root.querySelector('.radiusforms-upload__drop');
    if (!fileInput || !hidden) return;

    var maxFiles = parseInt(root.getAttribute('data-max-files'), 10) || 1;
    var maxSize = parseInt(root.getAttribute('data-max-size'), 10) || 0; // KB.
    var allowed = (root.getAttribute('data-allowed') || '').split(',').map(function (s) { return s.trim().toLowerCase(); }).filter(Boolean);

    var files = [];
    try { files = JSON.parse(hidden.value || '[]'); } catch (e) { files = []; }
    if (!Array.isArray(files)) files = [];

    function persist() {
      hidden.value = JSON.stringify(files);
    }

    function showError(msg) {
      if (errorBox) errorBox.textContent = msg || '';
      setFieldError(root, msg || '');
    }

    function renderList() {
      list.innerHTML = '';
      files.forEach(function (f, idx) {
        var item = document.createElement('div');
        item.className = 'radiusforms-upload__item';
        if (root.classList.contains('radiusforms-upload--image') && f.url) {
          var thumb = document.createElement('span');
          thumb.className = 'radiusforms-upload__thumb';
          thumb.style.backgroundImage = 'url(' + f.url + ')';
          item.appendChild(thumb);
        }
        var name = document.createElement('span');
        name.className = 'radiusforms-upload__name';
        name.textContent = f.name + (f.size ? ' (' + humanSize(f.size) + ')' : '');
        item.appendChild(name);

        var rm = document.createElement('button');
        rm.type = 'button';
        rm.className = 'radiusforms-upload__rm';
        rm.innerHTML = '&times;';
        rm.setAttribute('aria-label', 'Remove file');
        rm.addEventListener('click', function () {
          files.splice(idx, 1);
          persist();
          renderList();
        });
        item.appendChild(rm);
        list.appendChild(item);
      });
    }

    function validateLocal(file) {
      var ext = (file.name.split('.').pop() || '').toLowerCase();
      if (allowed.length && allowed.indexOf(ext) === -1) {
        return 'This file type is not allowed.';
      }
      if (maxSize > 0 && file.size > maxSize * 1024) {
        return 'The file is too large (max ' + maxSize + ' KB).';
      }
      return '';
    }

    function upload(file) {
      var localErr = validateLocal(file);
      if (localErr) { showError(localErr); return; }
      if (!cfg.uploadUrl) { showError('Uploads are not configured.'); return; }

      var pending = document.createElement('div');
      pending.className = 'radiusforms-upload__item is-uploading';
      pending.textContent = file.name + ' …';
      list.appendChild(pending);

      var data = new FormData();
      data.append('file', file);
      data.append('max_size', String(maxSize));
      data.append('allowed', allowed.join(','));

      fetch(cfg.uploadUrl, {
        method: 'POST',
        headers: { 'X-WP-Nonce': cfg.nonce },
        credentials: 'same-origin',
        body: data,
      })
        .then(function (r) { return r.json(); })
        .then(function (res) {
          if (res && res.success && res.file) {
            if (maxFiles === 1) files = [];
            files.push(res.file);
            persist();
            showError('');
          } else {
            showError((res && res.message) || 'Upload failed.');
          }
          renderList();
        })
        .catch(function () {
          showError('Upload failed. Please try again.');
          renderList();
        });
    }

    function handleFiles(fileList) {
      showError('');
      var incoming = Array.prototype.slice.call(fileList);
      for (var i = 0; i < incoming.length; i++) {
        var capacity = maxFiles - files.length;
        if (maxFiles > 1 && capacity <= 0) {
          showError('You can upload at most ' + maxFiles + ' files.');
          break;
        }
        upload(incoming[i]);
        if (maxFiles === 1) break;
      }
    }

    fileInput.addEventListener('change', function () {
      handleFiles(fileInput.files);
      fileInput.value = '';
    });

    if (drop) {
      ['dragenter', 'dragover'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.add('is-dragover'); });
      });
      ['dragleave', 'drop'].forEach(function (ev) {
        drop.addEventListener(ev, function (e) { e.preventDefault(); drop.classList.remove('is-dragover'); });
      });
      drop.addEventListener('drop', function (e) {
        if (e.dataTransfer && e.dataTransfer.files) handleFiles(e.dataTransfer.files);
      });
    }

    persist();
    renderList();
  }

  /* -------------------------------------------------------------------- run */

  function boot(scope) {
    var ctx = scope || document;
    ctx.querySelectorAll('[data-radiusforms-signature]').forEach(initSignature);
    ctx.querySelectorAll('[data-radiusforms-richtext]').forEach(initRichText);
    ctx.querySelectorAll('[data-radiusforms-repeater]').forEach(initRepeater);
    ctx.querySelectorAll('[data-radiusforms-upload]').forEach(initUpload);
  }

  ready(function () { boot(document); });
  // Expose for forms injected after load (e.g. popups).
  window.RadiusFormsProInit = boot;
})();
