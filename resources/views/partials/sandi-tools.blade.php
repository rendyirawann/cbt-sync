{{-- Tombol "lihat sandi" + indikator kekuatan sandi.

     Cara pakai: beri atribut pada input sandinya —
       data-sandi          -> hanya tombol mata (mis. kolom Konfirmasi)
       data-sandi="kuat"   -> tombol mata + indikator kekuatan

     Dipasang lewat JS, bukan dengan mengubah markup tiap form, supaya kolom
     sandi di form mana pun cukup ditambahi satu atribut.

     PENTING: form Edit User dimuat lewat AJAX dan disuntikkan dengan innerHTML,
     sehingga <script> di dalamnya TIDAK akan pernah dijalankan peramban. Karena
     itu berkas ini dipasang di halaman induk dan memakai MutationObserver —
     kolom sandi yang baru muncul ikut dipasangi tanpa perlu dipanggil ulang. --}}
<style>
    .sandi-bungkus { position: relative; }
    .sandi-bungkus > input { padding-right: 42px; }
    .sandi-lihat {
        position: absolute; top: 0; right: 0; height: 100%; width: 40px;
        display: flex; align-items: center; justify-content: center;
        background: none; border: 0; padding: 0; cursor: pointer; color: #99a1b7;
    }
    .sandi-lihat:hover { color: #4F46E5; }
    /* Ikon dipakai bergantian; yang tidak aktif disembunyikan. */
    .sandi-lihat .ki-eye-slash { display: none; }
    .sandi-lihat.terbuka .ki-eye { display: none; }
    .sandi-lihat.terbuka .ki-eye-slash { display: inline-block; }
    .sandi-meter { margin-top: 8px; }
    .sandi-bar { height: 5px; border-radius: 999px; background: #eef0f5; overflow: hidden; }
    .sandi-bar > span { display: block; height: 100%; width: 0; border-radius: 999px; transition: width .18s ease, background-color .18s ease; }
    .sandi-label { font-size: .8rem; font-weight: 600; margin-top: 5px; }
    .sandi-syarat { font-size: .74rem; color: #99a1b7; margin-top: 3px; line-height: 1.5; }
    .sandi-syarat b { font-weight: 600; }
    .sandi-syarat .ok { color: #17c653; }
</style>
<script>
(function () {
    var TINGKAT = [
        { label: 'Sangat lemah', warna: '#f8285a', lebar: 15 },
        { label: 'Lemah',        warna: '#f6c000', lebar: 35 },
        { label: 'Sedang',       warna: '#f6c000', lebar: 60 },
        { label: 'Kuat',         warna: '#17c653', lebar: 82 },
        { label: 'Sangat kuat',  warna: '#17c653', lebar: 100 }
    ];

    // Penilaian sengaja sederhana dan bisa dijelaskan ke pengguna: tiap syarat
    // yang terpenuhi menambah satu tingkat. Tidak memakai kamus kata sandi
    // umum — itu perlu daftar besar yang tidak sepadan untuk form ini.
    function syarat(v) {
        return {
            panjang: v.length >= 8,
            kecil: /[a-z]/.test(v),
            besar: /[A-Z]/.test(v),
            angka: /[0-9]/.test(v),
            simbol: /[^A-Za-z0-9]/.test(v)
        };
    }

    function pasang(inp) {
        if (inp.dataset.sandiSiap === '1') return;
        inp.dataset.sandiSiap = '1';

        // --- tombol mata
        var bungkus = document.createElement('div');
        bungkus.className = 'sandi-bungkus';
        inp.parentNode.insertBefore(bungkus, inp);
        bungkus.appendChild(inp);

        var tombol = document.createElement('button');
        tombol.type = 'button';
        tombol.className = 'sandi-lihat';
        tombol.setAttribute('aria-label', 'Tampilkan sandi');
        tombol.title = 'Tampilkan / sembunyikan sandi';
        tombol.innerHTML = '<i class="ki-outline ki-eye fs-3"></i><i class="ki-outline ki-eye-slash fs-3"></i>';
        tombol.addEventListener('click', function () {
            var tampil = inp.type === 'password';
            inp.type = tampil ? 'text' : 'password';
            tombol.classList.toggle('terbuka', tampil);
            tombol.setAttribute('aria-label', tampil ? 'Sembunyikan sandi' : 'Tampilkan sandi');
        });
        bungkus.appendChild(tombol);

        if (inp.dataset.sandi !== 'kuat') return;

        // --- indikator kekuatan
        var meter = document.createElement('div');
        meter.className = 'sandi-meter';
        meter.innerHTML =
            '<div class="sandi-bar"><span></span></div>' +
            '<div class="sandi-label text-muted">Kekuatan sandi: &mdash;</div>' +
            '<div class="sandi-syarat"></div>';
        bungkus.parentNode.insertBefore(meter, bungkus.nextSibling);

        var bar = meter.querySelector('.sandi-bar > span');
        var label = meter.querySelector('.sandi-label');
        var daftar = meter.querySelector('.sandi-syarat');

        function nilai() {
            var v = inp.value || '';
            var s = syarat(v);
            var n = 0;
            for (var k in s) { if (s[k]) n++; }

            if (!v.length) {
                bar.style.width = '0';
                label.textContent = 'Kekuatan sandi: —';
                label.className = 'sandi-label text-muted';
                daftar.innerHTML = 'Minimal 8 karakter, sebaiknya gabungkan huruf besar, huruf kecil, angka, dan simbol.';
                return;
            }

            var t = TINGKAT[Math.max(0, n - 1)];
            bar.style.width = t.lebar + '%';
            bar.style.backgroundColor = t.warna;
            label.textContent = 'Kekuatan sandi: ' + t.label;
            label.className = 'sandi-label';
            label.style.color = t.warna;

            var tanda = function (ok, teks) {
                return '<b class="' + (ok ? 'ok' : '') + '">' + (ok ? '✓' : '•') + ' ' + teks + '</b>';
            };
            daftar.innerHTML = [
                tanda(s.panjang, '8+ karakter'),
                tanda(s.kecil, 'huruf kecil'),
                tanda(s.besar, 'huruf besar'),
                tanda(s.angka, 'angka'),
                tanda(s.simbol, 'simbol')
            ].join(' &nbsp; ');
        }

        inp.addEventListener('input', nilai);
        nilai();
    }

    function sisir(akar) {
        (akar || document).querySelectorAll('input[type="password"][data-sandi]').forEach(pasang);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', function () { sisir(); });
    } else {
        sisir();
    }

    // Form yang datang belakangan (modal AJAX) ikut dipasangi.
    new MutationObserver(function (rekaman) {
        rekaman.forEach(function (r) {
            r.addedNodes.forEach(function (n) {
                if (n.nodeType !== 1) return;
                if (n.matches && n.matches('input[type="password"][data-sandi]')) { pasang(n); return; }
                sisir(n);
            });
        });
    }).observe(document.body, { childList: true, subtree: true });
})();
</script>
