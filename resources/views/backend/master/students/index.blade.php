@extends('backend.layout.app')
@section('title', 'Data Siswa')
@section('content')
@include('partials.kop-halaman', [
    'judul' => 'Data Siswa',
    'jejak' => [
        ['label' => 'Data Master'],
        ['label' => 'Data Siswa', 'route' => 'students.index'],
    ],
])
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        @if(session('success'))
            <div class="alert alert-success">{{ session('success') }}</div>
        @endif
        @if(session('error'))
            <div class="alert alert-danger">{{ session('error') }}</div>
        @endif
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Manajemen Siswa</h3>
                </div>
                <div class="card-toolbar">
                    {{-- Muncul hanya saat ada baris tercentang; jumlahnya ikut di label. --}}
                    @if(\App\Support\SiklusUjian::bolehHapusUjianDikerjakan())
                        <button type="button" class="btn btn-sm btn-light-danger me-2 d-none" id="btnHapusTerpilih">
                            <i class="ki-outline ki-trash fs-5"></i> Hapus Terpilih (<span id="jmlTerpilih">0</span>)
                        </button>
                    @endif
                    @include('backend.master._import_tools', ['templateRoute' => 'students.template', 'importRoute' => 'students.import', 'label' => 'Siswa'])
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Siswa</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table id="tabelSiswa" class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                @if(\App\Support\SiklusUjian::bolehHapusUjianDikerjakan())
                                    <th class="w-30px"><input class="form-check-input" type="checkbox" id="centangSemua" title="Pilih semua"></th>
                                @endif
                                <th>NISN</th>
                                <th>Nama Lengkap</th>
                                <th>Username</th>
                                <th>Tempat &amp; Tgl. Lahir</th>
                                <th>ID Proktor / Ruang</th>
                                <th>Gelombang</th>
                                <th>Asal Sekolah</th>
                                <th>Email (Akun Login)</th>
                                <th>Gender</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @forelse($students as $item)
                            <tr>
                                @if(\App\Support\SiklusUjian::bolehHapusUjianDikerjakan())
                                    <td><input class="form-check-input centang-siswa" type="checkbox"
                                               value="{{ $item->id }}" data-nama="{{ $item->user->name ?? $item->nisn }}"></td>
                                @endif
                                <td>{{ $item->nisn }}</td>
                                <td>{{ $item->user->name ?? '-' }}</td>
                                <td>{{ $item->user->username ?? '-' }}</td>
                                <td>
                                    {{ $item->birth_place ?: '-' }}
                                    <div class="text-muted fs-8 fw-semibold">{{ $item->birth_date ? $item->birth_date->translatedFormat('d F Y') : 'tanggal lahir belum diisi' }}</div>
                                </td>
                                <td>
                                    {{ $item->proctor_id ?: '-' }}
                                    <div class="text-muted fs-8 fw-semibold">{{ $item->room ?: 'ruang belum diisi' }}</div>
                                </td>
                                <td>
                                    @if($item->wave)
                                        <span class="badge badge-light-info">{{ $item->wave->name }}</span>
                                    @else
                                        <span class="text-muted fw-semibold">-</span>
                                    @endif
                                </td>
                                <td>{{ $item->school->name ?? '-' }}</td>
                                <td>{{ $item->user->email ?? '-' }}</td>
                                <td>{{ $item->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                <td class="text-end">
                                    <a href="#" class="btn btn-sm btn-light-primary btn-active-primary btn-edit-siswa"
                                       data-id="{{ $item->id }}">Edit</a>
                                    <form action="{{ route('students.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger confirm-delete" >Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @empty
                            <tr>
                                <td colspan="{{ \App\Support\SiklusUjian::bolehHapusUjianDikerjakan() ? 11 : 10 }}">
                                    <div class="text-center px-4 py-15">
                                        <img src="{{ asset('assets/media/illustrations/sigma-1/5.png') }}" alt="" class="mw-100 mh-200px mb-7">
                                        <h3 class="fw-bold text-gray-900 mb-2">Belum ada data siswa</h3>
                                        <p class="text-gray-400 fs-6 fw-semibold">Daftar peserta didik belum tersedia di sistem. Silakan tambahkan akun siswa baru.</p>
                                    </div>
                                </td>
                            </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade drawer-modal" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('students.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h2 class="fw-bold">Tambah Siswa Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                    <div class="modal-body">
                        <h5 class="mb-4 text-primary">Informasi Akun (Login)</h5>

                        {{-- Bila akun user-nya sudah dibuat lebih dulu, cukup pilih di sini —
                             nama, email, dan username diambil dari akun itu. Dikosongkan =
                             akun baru dibuat seperti biasa. --}}
                        <div class="fv-row mb-5">
                            <label class="fs-6 fw-semibold mb-2">Akun User <span class="text-muted fs-7">(opsional)</span></label>
                            <select name="user_id" id="pilihAkun" class="form-select form-select-solid">
                                <option value="">— Buat akun baru —</option>
                                @foreach($akunTersedia as $ak)
                                    <option value="{{ $ak->id }}"
                                        data-nama="{{ $ak->name }}" data-email="{{ $ak->email }}" data-username="{{ $ak->username }}">
                                        {{ $ak->name }} — {{ $ak->username ?: $ak->email }}
                                    </option>
                                @endforeach
                            </select>
                            <div class="text-muted fs-7 mt-2">
                                Pilih bila akun user siswa ini <b>sudah dibuat lebih dulu</b> di User Management.
                                Yang muncul hanya akun ber-role <b>Siswa</b> yang belum dipakai data siswa lain —
                                akun admin, guru, dan kepala sekolah tidak ikut ditawarkan.
                                @if($akunTersedia->isEmpty())<span class="text-warning d-block mt-1">Belum ada akun Siswa yang bisa dipakai — biarkan kosong agar akun baru dibuat.</span>@endif
                            </div>
                        </div>

                        <div id="kolomAkunBaru">
                        <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold mb-2">Username</label>
                        <input type="text" name="username" class="form-control form-control-solid" placeholder="kosongkan = otomatis memakai NISN">
                        <div class="text-muted fs-7 mt-2">Huruf, angka, titik, garis bawah, dan tanda hubung. Harus unik antar seluruh akun.</div>
                    </div>
                    <div class="fv-row mb-7">
                        <label class="fs-6 fw-semibold mb-2">Password</label>
                        <div class="input-group">
                            <input type="text" name="password" id="sandiBaru" class="form-control form-control-solid" placeholder="kosongkan = digenerate otomatis" autocomplete="off">
                            <button type="button" class="btn btn-light-primary" id="btnSandiAcak">Generate</button>
                        </div>
                        <div class="text-muted fs-7 mt-2">Kosongkan saja: sistem membuat password acak bergaya ANBK (mis. <b>892777*</b>).
                            Password ini yang dipakai siswa untuk login dan yang tercetak di <b>Kartu Ujian</b>.</div>
                    </div>
                    </div>{{-- /kolomAkunBaru --}}

                        <div id="ringkasAkun" class="alert alert-light-primary d-none">
                            <div class="fw-bold mb-1">Memakai akun yang sudah ada</div>
                            <div class="fs-7">Nama: <b id="raNama">-</b> &nbsp;•&nbsp; Email: <b id="raEmail">-</b> &nbsp;•&nbsp; Username: <b id="raUsername">-</b></div>
                            <div class="fs-8 text-muted mt-2">Kartu ujian tetap diterbitkan, dan <b>sandi akun ini digantikan</b> oleh
                                password kartu supaya siswa bisa masuk memakai kartunya.</div>
                        </div>
                        
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Siswa</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Sekolah Asal</label>
                        <select name="school_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal" required>
                            <option value="">Pilih Sekolah...</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>

                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-5">
                        <label class="required fs-6 fw-semibold mb-2">NISN</label>
                        <input type="text" name="nisn" class="form-control form-control-solid" required>
                        <div class="text-muted fs-7 mt-2">Bila kolom <b>Username</b> di atas dikosongkan, NISN ini yang dipakai sebagai username. Login siswa sendiri memakai <b>email</b>.</div>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid"></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
                        <select name="gender" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">Tempat Lahir</label>
                            <input type="text" name="birth_place" class="form-control form-control-solid" placeholder="cth: Medan">
                        </div>
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">Tanggal Lahir</label>
                            <input type="date" name="birth_date" class="form-control form-control-solid" max="{{ now()->subDay()->format('Y-m-d') }}">
                        </div>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid"></textarea></div>

                    <h5 class="mb-4 text-primary border-top pt-4">Pelaksanaan Ujian</h5>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">ID Proktor</label>
                            <input type="text" name="proctor_id" class="form-control form-control-solid" placeholder="cth: U07030017-AY8U" maxlength="50">
                        </div>
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">Ruang</label>
                            <input type="text" name="room" class="form-control form-control-solid" placeholder="cth: ANBK-SMA-1" maxlength="100">
                        </div>
                    </div>
                    <div class="fv-row mb-5">
                        <label class="fs-6 fw-semibold mb-2">Gelombang</label>
                        <select name="wave_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#addModal">
                            <option value="">Belum ditentukan</option>
                            @foreach($waves as $w)
                                <option value="{{ $w->id }}">{{ $w->name }}</option>
                            @endforeach
                        </select>
                        <div class="text-muted fs-7 mt-2">Pilihan diambil dari <b>Data Master &rarr; Master Gelombang</b>. Ketiga data ini dicetak pada kartu login peserta.</div>
                    </div>

                    <h5 class="mb-4 text-primary border-top pt-4">Data Orang Tua / Wali</h5>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Nama Orang Tua</label><input type="text" name="parent_name" class="form-control form-control-solid" placeholder="Contoh: Bpk. Heru"></div>
                    <div class="row mb-5">
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">Email Orang Tua</label>
                            <input type="email" name="parent_email" class="form-control form-control-solid" placeholder="email@orangtua.com">
                        </div>
                        <div class="col-md-6">
                            <label class="fs-6 fw-semibold mb-2">No. WA Orang Tua</label>
                            <input type="text" name="parent_phone" class="form-control form-control-solid" placeholder="0812xxxx">
                        </div>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>

{{-- Kerangka modal edit: isinya diambil lewat AJAX dari students.edit.
     Satu kerangka untuk semua baris, bukan satu modal per siswa. --}}
<div class="modal fade drawer-modal" id="editModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog"><div class="modal-content" id="editModalWadah">
        <div class="modal-body py-15 text-center">
            <span class="spinner-border text-primary"></span>
            <div class="text-muted mt-3">Memuat data siswa…</div>
        </div>
    </div></div>
</div>


@push('scripts')
<script>
    // Tombol Generate hanya mengisi kolomnya di layar. Kalau dibiarkan kosong,
    // server tetap membuatkan password acak dan menampilkannya setelah simpan.
    (function () {
        var btn = document.getElementById('btnSandiAcak');
        if (!btn) return;
        btn.addEventListener('click', function () {
            var n = String(Math.floor(Math.random() * 1000000)).padStart(6, '0');
            document.getElementById('sandiBaru').value = n + '*';
        });
    })();

    document.querySelectorAll('.confirm-delete').forEach(button => {
        button.addEventListener('click', function(e) {
            e.preventDefault();
            const form = this.closest('form');
            Swal.fire({
                title: 'Apakah Anda yakin?',
                text: "Data yang dihapus tidak dapat dikembalikan!",
                icon: 'warning',
                showCancelButton: true,
                confirmButtonColor: '#d33',
                cancelButtonColor: '#3085d6',
                confirmButtonText: 'Ya, Hapus!',
                cancelButtonText: 'Batal'
            }).then((result) => {
                if (result.isConfirmed) {
                    form.submit();
                }
            });
        });
    });
</script>
@endpush

@endsection

@push('scripts')
<script>
    // Memilih akun yang sudah ada menyembunyikan kolom Nama/Email/Username/Password
    // dan MELEPAS atribut required-nya — kalau tidak, peramban menolak submit pada
    // kolom tersembunyi ("An invalid form control is not focusable").
    (function () {
        var sel = document.getElementById('pilihAkun');
        if (!sel) return;
        var kolom = document.getElementById('kolomAkunBaru');
        var ringkas = document.getElementById('ringkasAkun');
        var wajib = kolom ? kolom.querySelectorAll('[required]') : [];
        var daftarWajib = Array.prototype.slice.call(wajib);

        function terapkan() {
            var o = sel.options[sel.selectedIndex];
            var pakai = !!sel.value;

            if (kolom) kolom.classList.toggle('d-none', pakai);
            if (ringkas) ringkas.classList.toggle('d-none', !pakai);
            daftarWajib.forEach(function (el) {
                if (pakai) { el.removeAttribute('required'); } else { el.setAttribute('required', 'required'); }
            });

            if (pakai && o) {
                document.getElementById('raNama').textContent = o.dataset.nama || '-';
                document.getElementById('raEmail').textContent = o.dataset.email || '-';
                document.getElementById('raUsername').textContent = o.dataset.username || '-';
            }
        }

        sel.addEventListener('change', terapkan);
        terapkan();
    })();
</script>
@endpush

@if(\App\Support\SiklusUjian::bolehHapusUjianDikerjakan())
{{-- Form pengirim penghapusan massal. Id-nya disuntikkan saat konfirmasi disetujui,
     dan tetap disaring ulang di server (StudentController::massDelete) — id dari
     peramban tidak dipercaya. --}}
<form action="{{ route('students.mass-delete') }}" method="POST" id="formHapusMassal" class="d-none">
    @csrf
    <div id="wadahIdTerpilih"></div>
</form>

@push('scripts')
{{-- DataTables TIDAK ikut di plugins.bundle.js (sudah diperiksa: 0 kemunculan),
     jadi halaman yang memakainya harus memuat bundelnya sendiri — pola yang
     sama dipakai User Management, Role, dan Log Aktivitas.

     Tanpa baris ini, $().DataTable bernilai undefined, pemanggilannya melempar
     galat, dan SELURUH skrip di bawahnya mati — itulah sebabnya tabel tampil
     memanjang tanpa halaman DAN centang "pilih semua" tidak bereaksi. --}}
<script src="{{ URL::to('assets/plugins/custom/datatables/datatables.bundle.js') }}"></script>
<script>
    $(function () {
        var bolehHapus = @json(\App\Support\SiklusUjian::bolehHapusUjianDikerjakan());

        // Kolom yang TIDAK boleh diurutkan: kolom centang (bila ada) dan kolom Aksi.
        var takUrut = bolehHapus ? [0, -1] : [-1];

        var tabel = $('#tabelSiswa').DataTable({
            // Sisi-klien: seluruh baris sudah ada di halaman, jadi pencarian dan
            // pindah halaman terjadi tanpa permintaan ke server. Cukup untuk
            // ukuran satu sekolah; kalau nanti datanya sampai puluhan ribu,
            // barulah pindah ke serverSide seperti User Management.
            pageLength: 10,
            lengthMenu: [[10, 25, 50, 100, -1], [10, 25, 50, 100, 'Semua']],
            order: bolehHapus ? [[2, 'asc']] : [[1, 'asc']],   // urut nama
            columnDefs: [{ orderable: false, searchable: false, targets: takUrut }],
            language: {
                search: 'Cari siswa:',
                searchPlaceholder: 'nama / NISN / username / email',
                lengthMenu: 'Tampilkan _MENU_ baris',
                info: 'Menampilkan _START_–_END_ dari _TOTAL_ siswa',
                infoEmpty: 'Tidak ada siswa',
                infoFiltered: '(disaring dari _MAX_ total)',
                zeroRecords: 'Tidak ada siswa yang cocok dengan pencarian',
                emptyTable: 'Belum ada data siswa',
                paginate: { first: 'Awal', last: 'Akhir', next: 'Berikutnya', previous: 'Sebelumnya' }
            }
        });

        if (!bolehHapus) return;

        var semua = document.getElementById('centangSemua');
        var tombol = document.getElementById('btnHapusTerpilih');
        var badge = document.getElementById('jmlTerpilih');
        var form = document.getElementById('formHapusMassal');
        var wadah = document.getElementById('wadahIdTerpilih');
        if (!tombol || !form) return;

        // Pilihan disimpan di Set, BUKAN dibaca dari DOM.
        //
        // DataTables mengeluarkan baris halaman lain dari DOM, jadi
        // querySelectorAll('.centang-siswa') hanya melihat baris yang sedang
        // tampil — mencentang 30 siswa lalu pindah halaman akan menghapus
        // pilihannya tanpa terlihat. Set ini membuat pilihan bertahan.
        var dipilih = new Set();
        var namaDipilih = new Map();

        function barisTersaring() {
            // nodes() memuat SELURUH baris yang cocok dengan pencarian, termasuk
            // yang tidak sedang tampil di halaman aktif.
            return $(tabel.rows({ search: 'applied' }).nodes()).find('.centang-siswa').toArray();
        }

        function segarkan() {
            badge.textContent = dipilih.size;
            tombol.classList.toggle('d-none', dipilih.size === 0);

            var kotak = barisTersaring();
            var tercentang = kotak.filter(function (c) { return dipilih.has(c.value); }).length;
            if (semua) {
                semua.checked = kotak.length > 0 && tercentang === kotak.length;
                semua.indeterminate = tercentang > 0 && tercentang < kotak.length;
            }
        }

        // Setiap kali tabel digambar ulang (cari / pindah halaman / urut),
        // keadaan centang baris yang muncul dipulihkan dari Set.
        tabel.on('draw', function () {
            document.querySelectorAll('.centang-siswa').forEach(function (c) {
                c.checked = dipilih.has(c.value);
            });
            segarkan();
        });

        document.addEventListener('change', function (e) {
            var c = e.target;
            if (!c || !c.classList || !c.classList.contains('centang-siswa')) return;
            if (c.checked) { dipilih.add(c.value); namaDipilih.set(c.value, c.dataset.nama); }
            else { dipilih.delete(c.value); }
            segarkan();
        });

        if (semua) {
            semua.addEventListener('change', function () {
                barisTersaring().forEach(function (c) {
                    if (semua.checked) { dipilih.add(c.value); namaDipilih.set(c.value, c.dataset.nama); }
                    else { dipilih.delete(c.value); }
                    c.checked = semua.checked;
                });
                segarkan();
            });
        }

        tombol.addEventListener('click', function () {
            if (!dipilih.size) return;
            var ids = Array.from(dipilih);
            var nama = ids.slice(0, 5).map(function (id) { return namaDipilih.get(id) || id; });
            var sisa = ids.length - nama.length;

            Swal.fire({
                title: 'Hapus ' + ids.length + ' data siswa?',
                html: '<div class="text-start fs-7">'
                    + '<div class="mb-3">' + nama.map(function (n) { return '&bull; ' + n; }).join('<br>')
                    + (sisa > 0 ? '<br>&bull; <i>dan ' + sisa + ' siswa lainnya</i>' : '') + '</div>'
                    + '<b>Akun login mereka ikut terhapus</b>, beserta plotting rombel, pengerjaan ujian, '
                    + 'dan nilainya. Tindakan ini tidak bisa dibatalkan.'
                    + '<div class="text-muted mt-2">Soal di Bank Soal yang pernah mereka buat tidak terpengaruh.</div>'
                    + '</div>',
                icon: 'warning',
                showCancelButton: true,
                confirmButtonText: 'Ya, hapus ' + ids.length + ' siswa',
                cancelButtonText: 'Batal',
                confirmButtonColor: '#d33',
                reverseButtons: true
            }).then(function (r) {
                if (!r.isConfirmed) return;
                wadah.innerHTML = '';
                ids.forEach(function (id) {
                    var i = document.createElement('input');
                    i.type = 'hidden'; i.name = 'ids[]'; i.value = id;
                    wadah.appendChild(i);
                });
                form.submit();
            });
        });

        segarkan();
    });
</script>
@endpush
@endif

@push('scripts')
<script>
    // Isi form edit diambil saat dibutuhkan. Kerangka modalnya dibuka dulu supaya
    // pengguna melihat pemuatan berjalan, bukan tombol yang seolah tidak bereaksi.
    $(function () {
        var modal = document.getElementById('editModal');
        var wadah = document.getElementById('editModalWadah');
        if (!modal || !wadah) return;

        var bs = null;
        var kosong = wadah.innerHTML;

        $(document).on('click', '.btn-edit-siswa', function (e) {
            e.preventDefault();
            var id = this.dataset.id;
            wadah.innerHTML = kosong;
            bs = bs || new bootstrap.Modal(modal);
            bs.show();

            $.get('{{ url('admin/students') }}/' + id + '/edit')
                .done(function (res) {
                    wadah.innerHTML = res.html || '';
                    // Modal ini juga memuat kolom sandi; alat "lihat sandi" dipasang
                    // ulang oleh MutationObserver di partials/sandi-tools.
                })
                .fail(function (x) {
                    wadah.innerHTML = '<div class="modal-body py-15 text-center text-danger">'
                        + 'Gagal memuat data siswa (' + (x.status || 'jaringan') + ').</div>';
                });
        });
    });
</script>
@endpush
