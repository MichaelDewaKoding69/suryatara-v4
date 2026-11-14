/* Roemah Beras - skrip kecil bersama (pelanggan & admin) */
(function () {
    'use strict';

    // Tombol +/- jumlah
    document.querySelectorAll('[data-qty]').forEach(function (wrap) {
        var input = wrap.querySelector('input');
        var max = parseInt(input.getAttribute('max') || '9999', 10);
        var min = parseInt(input.getAttribute('min') || '1', 10);
        wrap.querySelectorAll('button[data-step]').forEach(function (btn) {
            btn.addEventListener('click', function () {
                var v = parseInt(input.value || '1', 10) + parseInt(btn.dataset.step, 10);
                input.value = Math.max(min, Math.min(max, v));
                input.dispatchEvent(new Event('change', { bubbles: true }));
            });
        });
    });

    // Auto-submit
    document.querySelectorAll('[data-autosubmit]').forEach(function (el) {
        el.addEventListener('change', function () { el.form.submit(); });
    });

    // Lightbox (klik gambar dengan data-zoom)
    document.addEventListener('click', function (e) {
        var t = e.target.closest('[data-zoom]');
        if (!t) return;
        var box = document.getElementById('lightbox');
        if (!box) return;
        e.preventDefault();
        box.querySelector('img').src = t.dataset.zoom || t.src;
        box.classList.add('show');
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') {
            document.querySelectorAll('.lightbox.show, .modal-x.show').forEach(function (el) { el.classList.remove('show'); });
        }
    });

    // Upload area + pratinjau di panel samping
    document.querySelectorAll('[data-upload]').forEach(function (area) {
        var input = document.getElementById(area.dataset.upload);
        var label = area.querySelector('[data-filename]');
        var preview = document.querySelector(area.dataset.preview || '#none');
        var placeholder = document.querySelector(area.dataset.placeholder || '#none');
        function show(file) {
            if (!file) return;
            if (label) label.textContent = file.name + ' (' + Math.round(file.size / 1024) + ' KB)';
            area.classList.add('has-file');
            var btn = area.querySelector('[data-upload-btn]');
            if (btn) btn.innerHTML = '<i class="fas fa-rotate"></i> Ganti file';
            if (preview && file.type.indexOf('image') === 0) {
                var r = new FileReader();
                r.onload = function (ev) {
                    preview.src = ev.target.result;
                    preview.dataset.zoom = ev.target.result;
                    preview.style.display = 'block';
                    if (placeholder) placeholder.style.display = 'none';
                };
                r.readAsDataURL(file);
            }
        }
        input.addEventListener('change', function () { show(input.files[0]); });
        ['dragenter', 'dragover'].forEach(function (ev) {
            area.addEventListener(ev, function (e) { e.preventDefault(); area.classList.add('drag'); });
        });
        ['dragleave', 'drop'].forEach(function (ev) {
            area.addEventListener(ev, function (e) { e.preventDefault(); area.classList.remove('drag'); });
        });
        area.addEventListener('drop', function (e) {
            if (e.dataTransfer.files.length) {
                input.files = e.dataTransfer.files;
                show(input.files[0]);
            }
        });
    });

    // Salin teks
    document.querySelectorAll('[data-copy]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var text = btn.dataset.copy;
            if (navigator.clipboard) navigator.clipboard.writeText(text);
            var old = btn.textContent;
            btn.textContent = 'Tersalin';
            setTimeout(function () { btn.textContent = old; }, 1400);
        });
    });

    // Modal sederhana
    document.querySelectorAll('[data-modal-open]').forEach(function (btn) {
        btn.addEventListener('click', function () {
            var m = document.getElementById(btn.dataset.modalOpen);
            if (m) m.classList.add('show');
        });
    });
    document.querySelectorAll('[data-modal-close]').forEach(function (btn) {
        btn.addEventListener('click', function () { btn.closest('.modal-x').classList.remove('show'); });
    });
    document.querySelectorAll('.modal-x').forEach(function (m) {
        m.addEventListener('click', function (e) { if (e.target === m) m.classList.remove('show'); });
    });

    // Konfirmasi
    document.querySelectorAll('form[data-confirm]').forEach(function (f) {
        f.addEventListener('submit', function (e) {
            if (!confirm(f.dataset.confirm)) e.preventDefault();
        });
    });

    // Tampilkan / sembunyikan password
    document.querySelectorAll('.pw-toggle').forEach(function (b) {
        b.addEventListener('click', function () {
            var i = b.parentElement.querySelector('input');
            i.type = i.type === 'password' ? 'text' : 'password';
            b.textContent = i.type === 'password' ? 'tampilkan' : 'sembunyikan';
        });
    });
})();
