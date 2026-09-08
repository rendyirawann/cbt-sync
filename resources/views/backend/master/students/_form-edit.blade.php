{{-- Isi modal edit satu siswa — HANYA bagian dalam .modal-content.

     PENTING: partial ini TIDAK boleh membawa pembungkus .modal / .modal-dialog /
     .modal-content sendiri. Ia disuntikkan ke dalam .modal-content milik modal
     kerangka di halaman daftar; kalau ia membawa pembungkusnya sendiri, hasilnya
     modal bersarang yang display:none — panelnya terbuka tapi isinya kosong
     (persis gejala yang dilaporkan: drawer putih tanpa apa pun).

     Variabel yang diharapkan: $item (Student), $schools, $waves. --}}
<form action="{{ route('students.update', $item->id) }}" method="POST">
    @csrf @method('PUT')
    <div class="modal-header">
        <h2 class="fw-bold">Edit Siswa</h2>
        <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
            <i class="ki-outline ki-cross fs-1 text-dark"></i>
        </div>
    </div>
    <div class="modal-body">
        <h5 class="mb-4 text-primary">Informasi Akun (Login)</h5>
        <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" value="{{ $item->user->name ?? '' }}" required></div>
        <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" value="{{ $item->user->email ?? '' }}" required></div>
        <div class="fv-row mb-5">
            <label class="fs-6 fw-semibold mb-2">Username</label>
            <input type="text" name="username" class="form-control form-control-solid" value="{{ $item->user->username ?? '' }}" placeholder="kosongkan = otomatis memakai NISN">
            <div class="text-muted fs-7 mt-2">Huruf, angka, titik, garis bawah, dan tanda hubung. Harus unik antar seluruh akun.</div>
        </div>
        <div class="fv-row mb-7">
            <label class="fs-6 fw-semibold mb-2">Password (Kosongkan jika tidak diubah)</label>
            <input type="text" name="password" class="form-control form-control-solid" autocomplete="off">
            <div class="text-muted fs-7 mt-2">Mengisi kolom ini juga mengubah password pada <b>Kartu Ujian</b> siswa ini.</div>
        </div>
        
        <h5 class="mb-4 text-primary border-top pt-4">Profil Siswa</h5>
        <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Sekolah Asal</label>
            <select name="school_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal" required>
                @foreach($schools as $s)
                    <option value="{{ $s->id }}" {{ $item->school_id == $s->id ? 'selected' : '' }}>{{ $s->name }}</option>
                @endforeach
            </select>
        </div>
        <div class="fv-row mb-5">
            <label class="required fs-6 fw-semibold mb-2">NISN</label>
            <input type="text" name="nisn" class="form-control form-control-solid" value="{{ $item->nisn }}" required>
        </div>
        <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid" value="{{ $item->phone }}"></div>
        <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
            <select name="gender" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal">
                <option value="L" {{ $item->gender == 'L' ? 'selected' : '' }}>Laki-laki</option>
                <option value="P" {{ $item->gender == 'P' ? 'selected' : '' }}>Perempuan</option>
            </select>
        </div>
        <div class="row mb-5">
            <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">Tempat Lahir</label>
                <input type="text" name="birth_place" class="form-control form-control-solid" value="{{ $item->birth_place }}" placeholder="cth: Medan">
            </div>
            <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">Tanggal Lahir</label>
                <input type="date" name="birth_date" class="form-control form-control-solid" value="{{ $item->birth_date?->format('Y-m-d') }}" max="{{ now()->subDay()->format('Y-m-d') }}">
            </div>
        </div>
        <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid">{{ $item->address }}</textarea></div>

        <h5 class="mb-4 text-primary border-top pt-4">Pelaksanaan Ujian</h5>
        <div class="row mb-5">
            <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">ID Proktor</label>
                <input type="text" name="proctor_id" class="form-control form-control-solid" value="{{ $item->proctor_id }}" placeholder="cth: U07030017-AY8U" maxlength="50">
            </div>
            <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">Ruang</label>
                <input type="text" name="room" class="form-control form-control-solid" value="{{ $item->room }}" placeholder="cth: ANBK-SMA-1" maxlength="100">
            </div>
        </div>
        <div class="fv-row mb-5">
            <label class="fs-6 fw-semibold mb-2">Gelombang</label>
            <select name="wave_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal">
                <option value="">Belum ditentukan</option>
                @foreach($waves as $w)
                    <option value="{{ $w->id }}" @selected($item->wave_id === $w->id)>{{ $w->name }}</option>
                @endforeach
                {{-- Gelombang yang sudah dinonaktifkan tetap ditampilkan bila siswa ini memakainya,
                     supaya menyimpan form tidak diam-diam menghapus gelombangnya. --}}
                @if($item->wave && !$waves->contains('id', $item->wave_id))
                    <option value="{{ $item->wave_id }}" selected>{{ $item->wave->name }} (nonaktif)</option>
                @endif
            </select>
            <div class="text-muted fs-7 mt-2">Dicetak pada kartu login peserta.</div>
        </div>

        <h5 class="mb-4 text-primary border-top pt-4">Data Orang Tua / Wali</h5>
        <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Nama Orang Tua</label><input type="text" name="parent_name" class="form-control form-control-solid" value="{{ $item->parent_name }}"></div>
        <div class="row mb-5">
            <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">Email Orang Tua</label>
                <input type="email" name="parent_email" class="form-control form-control-solid" value="{{ $item->parent_email }}">
            </div>
            <div class="col-md-6">
                <label class="fs-6 fw-semibold mb-2">No. WA Orang Tua</label>
                <input type="text" name="parent_phone" class="form-control form-control-solid" value="{{ $item->parent_phone }}">
            </div>
        </div>
    </div>
    <div class="modal-footer flex-center">
        <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
        <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
    </div>
</form>
