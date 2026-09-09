@php $label = $label ?? 'Data'; $bertahap = $chunk ?? false; @endphp
<a href="{{ route($templateRoute) }}" class="btn btn-sm btn-light-success me-2" title="Unduh template Excel">
    <i class="ki-outline ki-file-down fs-5"></i> Template Excel
</a>
<button type="button" class="btn btn-sm btn-success me-2" data-bs-toggle="modal" data-bs-target="#importExcelModal" title="Impor data dari Excel">
    <i class="ki-outline ki-file-up fs-5"></i> Import Excel
</button>

<div class="modal fade drawer-modal" id="importExcelModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content">
        <form action="{{ route($importRoute) }}" method="POST" enctype="multipart/form-data"
              @if($bertahap) data-bertahap="1" @endif>
            @csrf
            <div class="modal-header"><h3 class="modal-title">Import {{ $label }} (Excel)</h3><div class="btn btn-icon btn-sm" data-bs-dismiss="modal"><i class="ki-outline ki-cross fs-2"></i></div></div>
            <div class="modal-body px-8 py-6">
                <div class="alert alert-light-primary fs-8 py-3 mb-5">
                    Unduh <b>Template Excel</b> dulu, isi pada lembar <b>"Data"</b> (lihat lembar "Contoh" &amp; "Petunjuk"), lalu unggah di sini.
                    Baris yang bermasalah akan dilaporkan lengkap dengan alasannya; data yang valid tetap masuk.
                </div>
                <label class="form-label required">Berkas Excel (.xlsx / .xls)</label>
                <input type="file" name="file" class="form-control" accept=".xlsx,.xls" required>
                <div class="form-text">Maksimal 8 MB.</div>
                @if($bertahap)
                    <div class="alert alert-light-info fs-8 py-2 mt-4 mb-0">
                        Berkas berisi banyak baris diproses <b>bertahap</b>. Biarkan jendela ini
                        terbuka sampai selesai — kemajuannya tampil di bawah.
                    </div>
                    <div class="impor-kabar text-primary fw-semibold fs-7 mt-3"></div>
                @endif
            </div>
            <div class="modal-footer"><button type="submit" class="btn btn-success"><i class="ki-outline ki-file-up fs-5"></i> Import Sekarang</button></div>
        </form>
    </div></div>
</div>

@if($bertahap)
@push('scripts')
<script>
    // Impor bertahap. Berkasnya dikirim ULANG pada setiap potongan (ukurannya
    // puluhan KB, jadi murah) supaya server tidak perlu menyimpan berkas
    // sementara dan tidak ada sisa yang harus dibersihkan.
    (function () {
        var modal = document.getElementById('importExcelModal');
        if (!modal) return;
        var form = modal.querySelector('form[data-bertahap]');
        if (!form) return;

        var PER = 15;   // TERUKUR: 25 baris = 18 detik, terlalu dekat batas 30 s. 15 baris ~11 s.

        form.addEventListener('submit', function (e) {
            e.preventDefault();

            var berkas = form.querySelector('input[name=file]');
            if (!berkas || !berkas.files.length) return;

            var tombol = form.querySelector('button[type=submit]');
            var kabar = form.querySelector('.impor-kabar');
            var token = form.querySelector('input[name=_token]').value;

            var dari = 0, total = null;
            var akum = { imported: 0, skipped: 0, errors: [], catatan: [], dilewati: [] };

            tombol.disabled = true;
            tombol.setAttribute('data-kt-indicator', 'on');
            kabar.textContent = 'Membaca berkas…';

            function tulisKabar() {
                kabar.textContent = 'Memproses ' + dari + (total !== null ? ' dari ' + total : '')
                    + ' baris… (' + akum.imported + ' masuk'
                    + (akum.skipped ? ', ' + akum.skipped + ' dilewati' : '')
                    + (akum.errors.length ? ', ' + akum.errors.length + ' gagal' : '') + ')';
            }

            function berhenti(pesan) {
                tombol.disabled = false;
                tombol.removeAttribute('data-kt-indicator');
                kabar.textContent = '';
                Swal.fire({
                    icon: 'error', title: 'Impor terhenti',
                    html: 'Sudah masuk <b>' + akum.imported + '</b> baris sebelum berhenti.<br>'
                        + '<span class="text-muted fs-7">' + (pesan || '') + '</span>',
                    buttonsStyling: false, confirmButtonText: 'Tutup',
                    customClass: { confirmButton: 'btn btn-danger' }
                });
            }

            // Satu bagian ringkasan: judul + daftar yang bisa digulir sendiri.
            // Batasnya 300 baris supaya pop-up tidak menggantung di berkas yang
            // sangat besar; kalau terpotong, jumlah yang tidak ditampilkan tetap
            // disebutkan — jangan pernah memotong tanpa memberi tahu.
            function bagianRingkasan(judul, warna, daftar, ikon) {
                if (!daftar.length) return '';
                var tampil = daftar.slice(0, 300);
                var sisa = daftar.length - tampil.length;
                return '<div class="mt-4 text-start">'
                    + '<div class="fw-bold text-' + warna + ' mb-2">'
                    + '<i class="ki-outline ' + ikon + ' fs-5 me-1"></i>'
                    + judul + ' (' + daftar.length + ')</div>'
                    + '<div class="border border-gray-300 rounded p-3 fs-8 text-gray-700"'
                    + ' style="max-height:26vh;overflow:auto">'
                    + tampil.map(function (x) { return '• ' + x; }).join('<br>')
                    + (sisa > 0 ? '<br><span class="text-muted">… dan ' + sisa + ' baris lagi</span>' : '')
                    + '</div></div>';
            }

            function selesai() {
                tombol.removeAttribute('data-kt-indicator');

                var html = '<div class="fs-5 text-start">'
                    + '<span class="badge badge-success fs-6 me-2">' + akum.imported + ' masuk</span>'
                    + (akum.skipped ? '<span class="badge badge-light-primary fs-6 me-2">' + akum.skipped + ' dilewati</span>' : '')
                    + (akum.errors.length ? '<span class="badge badge-danger fs-6 me-2">' + akum.errors.length + ' gagal</span>' : '')
                    + (akum.catatan.length ? '<span class="badge badge-warning fs-6">' + akum.catatan.length + ' perlu dilengkapi</span>' : '')
                    + '</div>';

                html += bagianRingkasan('GAGAL — tidak masuk, perlu diperbaiki', 'danger',
                    akum.errors, 'ki-cross-circle');
                html += bagianRingkasan('DILEWATI — sudah terdaftar sebelumnya', 'primary',
                    akum.dilewati, 'ki-information-5');
                html += bagianRingkasan('PERLU DILENGKAPI — sudah masuk, datanya belum penuh', 'warning',
                    akum.catatan, 'ki-notepad-edit');

                Swal.fire({
                    icon: akum.errors.length ? 'warning' : 'success',
                    title: akum.errors.length ? 'Impor selesai, ada yang gagal' : 'Impor selesai',
                    html: html,
                    width: '860px',
                    buttonsStyling: false,
                    confirmButtonText: 'Muat Ulang Halaman',
                    customClass: { confirmButton: 'btn btn-primary' }
                }).then(function () { window.location.reload(); });
            }

            function lanjut() {
                var fd = new FormData();
                fd.append('_token', token);
                fd.append('file', berkas.files[0]);
                fd.append('dari', dari);
                fd.append('per', PER);

                fetch(form.action, {
                    method: 'POST', body: fd, credentials: 'same-origin',
                    headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' }
                }).then(function (r) {
                    // 419 = token CSRF kedaluwarsa. Memuat ulang halaman memasang
                    // token baru; ini jalur yang sama dengan penangan 419 lainnya.
                    if (r.status === 419) { window.location.reload(); return null; }
                    return r.text().then(function (t) {
                        var j;
                        try { j = JSON.parse(t); } catch (err) {
                            // Balasan bukan JSON berarti request-nya jatuh sebelum
                            // sampai ke aplikasi (mis. worker dibunuh) — tampilkan
                            // apa adanya daripada menelan galatnya.
                            throw new Error('Balasan tidak terduga dari server (' + r.status + ').');
                        }
                        if (!r.ok) throw new Error(j.message || ('Galat ' + r.status));
                        return j;
                    });
                }).then(function (j) {
                    if (!j) return;
                    total = j.total;
                    akum.imported += j.imported;
                    akum.skipped += j.skipped;
                    akum.errors = akum.errors.concat(j.errors || []);
                    akum.catatan = akum.catatan.concat(j.catatan || []);
                    akum.dilewati = akum.dilewati.concat(j.dilewati || []);
                    dari = j.diproses;
                    tulisKabar();
                    if (j.selesai) { selesai(); } else { lanjut(); }
                }).catch(function (err) {
                    berhenti(err && err.message ? err.message : '');
                });
            }

            lanjut();
        });
    })();
</script>
@endpush
@endif
