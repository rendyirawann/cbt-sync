<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\ClassRoom;
use App\Models\ClassStudent;
use App\Models\AcademicYear;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use DB;

class StudentController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;

    /** Nama role siswa yang diterima (huruf besar/kecil pernah dipakai keduanya). */
    private const ROLE_SISWA = ['Siswa', 'siswa'];

    public function index()
    {
        $sid = \App\Support\SchoolScope::id();
        $students = Student::with(['user', 'school', 'wave'])
            ->when($sid, fn ($q) => $q->where('school_id', $sid))
            ->get();
        $schools = $sid ? School::where('id', $sid)->get() : School::all();
        // Hanya gelombang aktif yang ditawarkan; gelombang lama yang sudah
        // dinonaktifkan tetap terbaca pada data siswa yang memakainya.
        $waves = \App\Models\Wave::where('is_active', true)->terurut()->get();

        // Akun yang BISA dipakai untuk siswa baru: HANYA yang ber-role Siswa,
        // belum punya profil siswa, dan masih di sekolah yang sama (atau belum
        // bersekolah). Dipakai bila operator sudah membuat data user lebih dulu,
        // baru kemudian mengisi data siswanya.
        //
        // Sebelumnya penyaringnya berupa daftar role yang DIKECUALIKAN, dan itu
        // keliru: akun Admin (dan akun tanpa role sama sekali) tetap ikut muncul
        // karena tidak ada di daftar itu. Sekarang syaratnya positif — harus
        // punya role Siswa — sehingga akun pengelola tidak mungkin lolos.
        $akunTersedia = \App\Models\User::doesntHave('student')
            ->whereHas('roles', fn ($q) => $q->whereIn('name', self::ROLE_SISWA))
            ->when($sid, fn ($q) => $q->where(fn ($w) => $w->where('school_id', $sid)->orWhereNull('school_id')))
            ->orderBy('name')
            ->get(['id', 'name', 'email', 'username']);

        return view('backend.master.students.index', compact('students', 'schools', 'waves', 'akunTersedia'));
    }

    public function store(Request $request)
    {
        // Dua cara menambah siswa:
        //  (a) MEMAKAI AKUN YANG SUDAH ADA — dipilih di kolom "Akun User". Nama,
        //      email, dan username diambil dari akun itu, jadi tidak diminta lagi.
        //  (b) TANPA memilih akun — akun user baru dibuat seperti sebelumnya.
        $pakaiAkun = $request->filled('user_id');

        if (!$pakaiAkun) {
            // Username kosong = pakai NISN (perilaku lama), tapi boleh ditentukan sendiri.
            $request->merge(['username' => $this->rapikanUsername($request->username, $request->nisn)]);
        }

        $aturan = [
            'user_id' => 'nullable|uuid|exists:users,id',
            'nisn' => 'required|unique:students,nisn',
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'proctor_id' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'wave_id' => 'nullable|uuid|exists:waves,id',
            'school_id' => 'required',
        ];

        if (!$pakaiAkun) {
            $aturan += [
                'name' => 'required',
                'email' => 'required|email|unique:users,email',
                'username' => 'required|string|max:50|regex:/^[A-Za-z0-9._-]+$/|unique:users,username',
                'password' => 'nullable|min:6',
            ];
        }

        $request->validate($aturan, $this->pesanUsername(), $this->labelSiswa());

        // Admin sekolah dipaksa ke sekolahnya sendiri (tidak bisa buat data sekolah lain).
        $schoolId = \App\Support\SchoolScope::id() ?: $request->school_id;

        // Password dibuat ACAK bergaya ANBK (mis. 892777*) bila tidak diisi, dan
        // disimpan sebagai password kartu supaya kartu login peserta memuat
        // password yang benar-benar bisa dipakai siswa untuk masuk.
        $sandi = $request->filled('password') ? $request->password : \App\Support\KartuUjian::sandiBaru();

        try {
            DB::beginTransaction();

            if ($pakaiAkun) {
                $user = User::findOrFail($request->user_id);

                // Dijaga di server, bukan hanya di daftar pilihan: id bisa dikirim langsung.
                if ($user->student) {
                    DB::rollBack();

                    return redirect()->back()->withInput()
                        ->with('error', 'Akun "' . $user->name . '" sudah dipakai oleh data siswa lain.');
                }
                // Dijaga di server juga: hanya akun ber-role Siswa yang boleh dipakai,
                // supaya akun Admin/Guru tidak bisa "dijadikan siswa" lewat id yang
                // dikirim langsung.
                if (!$user->hasRole(self::ROLE_SISWA)) {
                    DB::rollBack();

                    return redirect()->back()->withInput()->with('error',
                        'Akun "' . $user->name . '" bukan akun siswa (role: '
                        . ($user->getRoleNames()->implode(', ') ?: 'tanpa role')
                        . '). Hanya akun ber-role Siswa yang bisa dipakai di sini.');
                }

                $sekolahAkun = $user->school_id;
                if ($sekolahAkun && $schoolId && $sekolahAkun !== $schoolId) {
                    DB::rollBack();

                    return redirect()->back()->withInput()
                        ->with('error', 'Akun "' . $user->name . '" terdaftar di sekolah lain, tidak bisa dipakai di sini.');
                }

                // Akun yang belum punya sekolah diikutkan ke sekolah data siswa ini.
                if (!$sekolahAkun) {
                    $user->school_id = $schoolId;
                }
                $user->save();
            } else {
                // Buat User
                $user = User::create([
                    'name' => $request->name,
                    'email' => $request->email,
                    'username' => $request->username,
                    'no_wa' => $request->phone,
                    'phone' => $request->phone,
                    'school_id' => $schoolId,
                    'email_verified_at' => now(),
                    'is_active' => 1,
                    'password' => Hash::make($sandi),
                ]);
            }

            // Set Role — akun lama pun dipastikan punya role Siswa.
            $role = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
            $user->assignRole($role);

            // Buat Profil Siswa
            $student = Student::create([
                'user_id' => $user->id,
                'school_id' => $schoolId,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'proctor_id' => $request->proctor_id,
                'room' => $request->room,
                'wave_id' => $request->wave_id ?: null,
                'address' => $request->address,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_phone' => $request->parent_phone,
            ]);

            \App\Support\KartuUjian::terbitkan($student, $sandi);

            DB::commit();

            return redirect()->back()->with('success',
                ($pakaiAkun ? 'Siswa ditambahkan memakai akun "' . $user->name . '". ' : 'Siswa berhasil ditambahkan. ')
                . 'Password kartu: ' . $sandi
                . ' — password ini juga yang dipakai siswa untuk login, dan tercetak di Kartu Ujian.'
                . ($pakaiAkun ? ' Sandi lama akun itu digantikan oleh password kartu ini.' : ''));
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    
    /**
     * Form edit satu siswa, dikirim sebagai HTML untuk modal.
     *
     * Dulu form ini dirender untuk SETIAP siswa di halaman daftar — 124 siswa
     * berarti 124 form dikirim sekaligus (HTML halaman 422 KB) padahal paling
     * banyak satu yang dibuka. Sekarang diambil saat tombol Edit ditekan.
     */
    /**
     * Detail satu siswa, dikirim sebagai HTML untuk modal.
     *
     * Route::resource sudah mendaftarkan students.show sejak awal, tapi method-nya
     * belum pernah ada — membuka /admin/students/{id} langsung berbalas 500.
     * Method ini menutup lubang itu sekaligus: dipanggil lewat AJAX oleh tombol
     * Detail, dan tetap masuk akal bila URL-nya dibuka biasa (dibalas HTML utuh).
     */
    public function show(Request $request, $id)
    {
        $sid = \App\Support\SchoolScope::id();

        $item = Student::with(['user.roles', 'school', 'wave'])
            ->when($sid, fn ($q) => $q->where('school_id', $sid))   // siswa sekolah lain -> 404
            ->findOrFail($id);

        // Rombel yang diikuti beserta tahun ajarannya, dimuat sekali lewat relasi
        // supaya view tidak menembak query per baris.
        $rombel = ClassStudent::with(['classRoom', 'academicYear'])
            ->where('student_id', $item->id)
            ->get();

        // Riwayat ujian: 10 terakhir saja, dan tanpa menarik jawaban siswa.
        $riwayat = \App\Models\ExamAttempt::with(['session.exam'])
            ->where('student_id', $item->id)
            ->latest('created_at')
            ->limit(10)
            ->get();

        $html = view('backend.master.students._detail', compact('item', 'rombel', 'riwayat'))->render();

        return $request->ajax() || $request->wantsJson()
            ? response()->json(['html' => $html])
            : response($html);
    }

    public function edit($id)
    {
        $sid = \App\Support\SchoolScope::id();

        $item = Student::with(['user', 'school', 'wave'])
            ->when($sid, fn ($q) => $q->where('school_id', $sid))   // siswa sekolah lain -> 404
            ->findOrFail($id);

        $schools = $sid ? School::where('id', $sid)->get() : School::all();
        $waves = \App\Models\Wave::where('is_active', true)->terurut()->get();

        return response()->json([
            'html' => view('backend.master.students._form-edit', compact('item', 'schools', 'waves'))->render(),
        ]);
    }

    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        
        $request->merge(['username' => $this->rapikanUsername($request->username, $request->nisn)]);

        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'username' => 'required|string|max:50|regex:/^[A-Za-z0-9._-]+$/|unique:users,username,' . $student->user_id,
            'nisn' => 'required|unique:students,nisn,' . $student->id,
            'birth_place' => 'nullable|string|max:100',
            'birth_date' => 'nullable|date|before:today',
            'proctor_id' => 'nullable|string|max:50',
            'room' => 'nullable|string|max:100',
            'wave_id' => 'nullable|uuid|exists:waves,id',
            'school_id' => 'required'
        ], $this->pesanUsername(), $this->labelSiswa());

        try {
            \DB::beginTransaction();
            
            $user = $student->user;
            $user->name = $request->name;
            $user->email = $request->email;
            $user->username = $request->username;
            $user->save();

            // Ganti password lewat helper kartu supaya password di Kartu Ujian
            // ikut berubah — kalau tidak, kartu akan mencetak password lama.
            if ($request->filled('password')) {
                \App\Support\KartuUjian::terbitkan($student, $request->password);
            }

            $student->update([
                'school_id' => $request->school_id,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'birth_place' => $request->birth_place,
                'birth_date' => $request->birth_date,
                'proctor_id' => $request->proctor_id,
                'room' => $request->room,
                'wave_id' => $request->wave_id ?: null,
                'address' => $request->address,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_phone' => $request->parent_phone,
            ]);
            
            \DB::commit();
            return redirect()->back()->with('success', 'Data Siswa berhasil diperbarui');
        } catch (\Exception $e) {
            \DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    /**
     * Username kosong => pakai NISN (perilaku lama sebelum kolom ini bisa diisi).
     * Spasi dibuang dan huruf dikecilkan supaya tidak ada dua username yang
     * hanya berbeda kapitalisasi.
     */
    private function rapikanUsername($username, $nisn): string
    {
        $u = trim((string) $username);
        return strtolower($u !== '' ? $u : trim((string) $nisn));
    }

    private function pesanUsername(): array
    {
        return $this->idMessages() + [
            'username.regex' => 'Username hanya boleh berisi huruf, angka, titik, garis bawah, dan tanda hubung (tanpa spasi).',
            'username.unique' => 'Username ini sudah dipakai akun lain.',
            'birth_date.before' => 'Tanggal lahir harus tanggal yang sudah lewat.',
        ];
    }

    private function labelSiswa(): array
    {
        return [
            'name' => 'Nama Lengkap', 'email' => 'Email', 'username' => 'Username',
            'password' => 'Password', 'nisn' => 'NISN', 'school_id' => 'Sekolah',
            'birth_place' => 'Tempat Lahir', 'birth_date' => 'Tanggal Lahir',
            'proctor_id' => 'ID Proktor', 'room' => 'Ruang', 'wave' => 'Gelombang',
        ];
    }

    public function template()
    {
        return $this->downloadExcelTemplate($this->spec());
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:8192'], $this->idMessages(), ['file' => 'Berkas Excel']);
        try {
            $rows = $this->readExcelRows($request->file('file'), $this->spec()['columns']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca Excel: ' . $e->getMessage());
        }

        // Admin sekolah: semua siswa dipaksa ke sekolahnya (kolom Sekolah di file diabaikan).
        $sid = \App\Support\SchoolScope::id();
        // NISN diwajibkan saat impor (permintaan sekolah): tanpa NISN, username
        // jatuh ke email dan kartu ujian kehilangan nomor peserta — itu yang
        // membuat sekelas siswa masuk tanpa NISN pada impor sebelumnya.
        $rules = ['name' => 'required|string|max:255', 'email' => 'required|email', 'school' => ($sid ? 'nullable' : 'required') . '|string', 'gender' => 'nullable|in:L,P', 'nisn' => 'required|string|max:30'];
        $labels = ['name' => 'Nama', 'email' => 'Email', 'school' => 'Nama Sekolah', 'gender' => 'Gender', 'nisn' => 'NISN'];
        $activeYear = AcademicYear::where('is_active', 1)->first() ?? AcademicYear::first();
        // Kolom Gelombang di Excel diisi NAMA gelombang; dipetakan ke id di sini.
        $gelombang = \App\Models\Wave::pluck('id', 'name')
            ->mapWithKeys(fn ($id, $nama) => [strtolower(trim($nama)) => $id])->all();
        $imported = 0; $skipped = 0; $errors = []; $catatan = [];

        // Kolom yang tidak wajib tapi berdampak nyata bila kosong: tanpa ini,
        // barisnya tetap masuk dan pengguna baru sadar ada yang kosong setelah
        // melihat tabel atau mencetak kartu ujian.
        $penting = [
            'birth_place' => 'Tempat Lahir', 'birth_date' => 'Tanggal Lahir',
            'gender' => 'Gender', 'proctor_id' => 'ID Proktor', 'room' => 'Ruang', 'wave' => 'Gelombang',
        ];

        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $labels);
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }

            $kosong = [];
            foreach ($penting as $key => $label) {
                if (trim((string) ($row[$key] ?? '')) === '') { $kosong[] = $label; }
            }
            if ($kosong) {
                $catatan[] = "baris $line (" . implode(', ', $kosong) . ')';
            }
            if (User::where('email', $row['email'])->exists()) { $skipped++; continue; }
            $school = $sid ? School::find($sid) : School::where('name', $row['school'])->first();
            if (!$school) { $errors[] = "Baris $line: Sekolah \"{$row['school']}\" tidak ditemukan."; continue; }
            $nisn = $row['nisn'] ?? '';
            if ($nisn !== '' && Student::where('nisn', $nisn)->exists()) { $errors[] = "Baris $line: NISN \"$nisn\" sudah dipakai."; continue; }
            $unameCek = $this->rapikanUsername($row['username'] ?? '', $nisn !== '' ? $nisn : $row['email']);
            if (User::where('username', $unameCek)->exists()) { $errors[] = "Baris $line: Username \"$unameCek\" sudah dipakai akun lain."; continue; }
            try {
                // Password kosong = acak bergaya ANBK (bukan lagi default seragam
                // "siswa12345"), lalu disimpan sebagai password kartu.
                $sandiBaris = $row['password'] !== '' ? $row['password'] : \App\Support\KartuUjian::sandiBaru();
                // $gelombang WAJIB ikut di daftar use. Tanpa itu variabelnya tidak ada
                // di dalam closure, sehingga $gelombang[...] ?? null selalu bernilai
                // null — dan kolom Gelombang di Excel tidak pernah tersimpan sama
                // sekali, tanpa galat apa pun karena ditelan operator ??.
                DB::transaction(function () use ($row, $school, $nisn, $activeYear, $sandiBaris, $gelombang) {
                    $username = $this->rapikanUsername($row['username'] ?? '', $nisn !== '' ? $nisn : $row['email']);
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'username' => $username,
                        'no_wa' => $row['phone'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'school_id' => $school->id,
                        'email_verified_at' => now(),
                        'is_active' => 1,
                        'password' => Hash::make($sandiBaris),
                    ]);
                    $user->assignRole(Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']));
                    $student = Student::create([
                        'user_id' => $user->id,
                        'school_id' => $school->id,
                        'nisn' => $nisn !== '' ? $nisn : null,
                        'phone' => $row['phone'] ?? null,
                        'gender' => in_array($row['gender'] ?? '', ['L', 'P']) ? $row['gender'] : null,
                        'birth_place' => $row['birth_place'] ?? null,
                        'birth_date' => $this->tanggalExcel($row['birth_date'] ?? null),
                        'proctor_id' => $row['proctor_id'] ?? null,
                        'room' => $row['room'] ?? null,
                        'wave_id' => $gelombang[strtolower(trim($row['wave'] ?? ''))] ?? null,
                        'address' => $row['address'] ?? null,
                        'parent_name' => $row['parent_name'] ?? null,
                        'parent_email' => $row['parent_email'] ?? null,
                        'parent_phone' => $row['parent_phone'] ?? null,
                    ]);
                    \App\Support\KartuUjian::terbitkan($student, $sandiBaris);

                    // Enroll ke kelas bila kolom Kelas diisi & kelas ditemukan.
                    if (!empty($row['class']) && $activeYear) {
                        $class = ClassRoom::where('name', $row['class'])->first();
                        if ($class) {
                            ClassStudent::firstOrCreate([
                                'student_id' => $student->id,
                                'class_room_id' => $class->id,
                                'academic_year_id' => $activeYear->id,
                            ]);
                        }
                    }
                });
                $imported++;
            } catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors, $catatan);
    }

    /**
     * Tanggal dari Excel bisa datang sebagai teks (31/12/2010, 2010-12-31) atau
     * angka serial Excel. Nilai yang tidak bisa dibaca dianggap kosong daripada
     * menggagalkan seluruh baris.
     */
    private function tanggalExcel($nilai)
    {
        $v = trim((string) $nilai);
        if ($v === '') return null;
        if (is_numeric($v)) {
            try { return \PhpOffice\PhpSpreadsheet\Shared\Date::excelToDateTimeObject((float) $v)->format('Y-m-d'); }
            catch (\Throwable $e) { return null; }
        }
        foreach (['d/m/Y', 'd-m-Y', 'Y-m-d', 'd/m/y'] as $format) {
            $d = \DateTime::createFromFormat($format, $v);
            if ($d && $d->format($format) === $v) return $d->format('Y-m-d');
        }
        try { return \Carbon\Carbon::parse($v)->format('Y-m-d'); } catch (\Throwable $e) { return null; }
    }

    private function spec(): array
    {
        // Dropdown diisi dari data yang BENAR-BENAR ada, supaya pengguna tidak
        // perlu mengingat/menebak ejaannya. Nama yang tidak sama persis diabaikan
        // oleh importer, dan itulah salah satu sebab kolom berakhir kosong.
        $sid = \App\Support\SchoolScope::id();
        $pilihanSekolah = School::when($sid, fn ($q) => $q->where('id', $sid))
            ->orderBy('name')->pluck('name')->all();
        $pilihanKelas = ClassRoom::when($sid, fn ($q) => $q->where('school_id', $sid))
            ->orderBy('name')->pluck('name')->unique()->values()->all();
        $pilihanGelombang = \App\Models\Wave::where('is_active', true)->terurut()->pluck('name')->all();

        return [
            'title' => 'DATA SISWA',
            'file' => 'Template_Data_Siswa.xlsx',
            'guide' => [
                'NISN WAJIB diisi dan harus unik. Tanpa NISN, username siswa jatuh ke email dan nomor peserta di Kartu Ujian ikut kosong.',
                'Email jadi akun login siswa dan harus unik. Baris dengan email yang sudah terdaftar DILEWATI (tidak menimpa data lama).',
                'Password boleh dikosongkan — sistem membuat sandi ACAK bergaya ANBK (mis. 892777*), dan sandi itulah yang tercetak di Kartu Ujian serta dipakai siswa untuk login.',
                'Username kosong = otomatis memakai NISN (atau email bila NISN juga kosong). Harus unik antar seluruh akun.',
                'Nama Sekolah harus sudah terdaftar lebih dulu di Data Master > Data Sekolah. Untuk Admin sekolah, kolom ini boleh dikosongkan — otomatis memakai sekolahnya sendiri.',
                'Kelas: isi nama rombel yang sudah ada untuk langsung memasukkan siswa ke rombel tahun ajaran aktif. Nama yang tidak dikenali membuat siswa masuk TANPA rombel.',
                'ID Proktor & Ruang dipakai pada Kartu Ujian dan Daftar Hadir. Kosong berarti kolom itu ikut kosong di lembar cetak.',
                'Gelombang harus SAMA PERSIS dengan nama di Master Gelombang — pakai dropdown yang tersedia. Nama yang tidak dikenali diabaikan (siswa masuk tanpa gelombang).',
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Nama', 'required' => true, 'width' => 28],
                ['key' => 'email', 'label' => 'Email', 'required' => true, 'width' => 26, 'hint' => 'untuk login'],
                ['key' => 'password', 'label' => 'Password', 'width' => 16, 'format' => 'text', 'hint' => 'kosongkan = acak bergaya ANBK'],
                ['key' => 'username', 'label' => 'Username', 'width' => 18, 'format' => 'text', 'hint' => 'kosongkan = memakai NISN'],
                ['key' => 'nisn', 'label' => 'NISN', 'required' => true, 'width' => 18, 'format' => 'text', 'hint' => 'wajib & unik; angka 0 di depan dipertahankan'],
                ['key' => 'school', 'label' => 'Nama Sekolah', 'required' => true, 'width' => 30, 'options' => $pilihanSekolah, 'hint' => 'harus sudah terdaftar'],
                ['key' => 'class', 'label' => 'Kelas', 'width' => 16, 'options' => $pilihanKelas, 'hint' => 'nama rombel yang sudah ada'],
                ['key' => 'gender', 'label' => 'Gender', 'width' => 10, 'options' => ['L', 'P']],
                ['key' => 'birth_place', 'label' => 'Tempat Lahir', 'width' => 20, 'hint' => 'kota/kabupaten kelahiran'],
                ['key' => 'birth_date', 'label' => 'Tanggal Lahir', 'width' => 16, 'format' => 'text', 'hint' => 'tulis dd/mm/yyyy, mis. 17/08/2010'],
                ['key' => 'proctor_id', 'label' => 'ID Proktor', 'width' => 18, 'format' => 'text', 'hint' => 'tercetak di kartu ujian & daftar hadir'],
                ['key' => 'room', 'label' => 'Ruang', 'width' => 18, 'hint' => 'tercetak di kartu ujian & daftar hadir'],
                ['key' => 'wave', 'label' => 'Gelombang', 'width' => 16, 'options' => $pilihanGelombang, 'hint' => 'pilih dari dropdown'],
                ['key' => 'phone', 'label' => 'No. HP/WA', 'width' => 16, 'format' => 'text'],
                ['key' => 'address', 'label' => 'Alamat', 'width' => 26],
                ['key' => 'parent_name', 'label' => 'Nama Ortu', 'width' => 24],
                ['key' => 'parent_email', 'label' => 'Email Ortu', 'width' => 24],
                ['key' => 'parent_phone', 'label' => 'No. HP Ortu', 'width' => 18, 'format' => 'text'],
            ],
            'examples' => [
                ['name' => 'Andi Pratama', 'email' => 'andi@siswa.id', 'password' => '', 'username' => 'andi.pratama', 'nisn' => '0012345678', 'school' => 'SMA Negeri 1 Medan', 'class' => 'X-IPA 1', 'gender' => 'L', 'birth_place' => 'Medan', 'birth_date' => '17/08/2010',
                 'proctor_id' => 'U07030017-AY8U', 'room' => 'ANBK-SMA-1', 'wave' => 'Gelombang 1', 'phone' => '081200001111', 'address' => 'Jl. Kenanga 3', 'parent_name' => 'Bpk. Pratama', 'parent_email' => 'ortu.andi@mail.com', 'parent_phone' => '081211112222'],
            ],
        ];
    }

    /**
     * Hapus BANYAK siswa sekaligus, beserta akun login masing-masing.
     *
     * Menghapus akun user-nya memang disengaja: relasi students.user_id ber-aturan
     * ON DELETE CASCADE, jadi menghapus akun sekaligus membersihkan profil siswa,
     * plotting rombel, pengerjaan ujian, dan jawabannya. Menyisakan akun tanpa
     * profil siswa justru menumpuk sampah — akun itu akan terus muncul di
     * pemilih "Akun User" pada form Tambah Siswa.
     *
     * Yang TIDAK ikut terhapus: entri Bank Soal yang pernah dibuat akun itu
     * (kolom created_by ber-aturan SET NULL), jadi soal tetap bisa dipakai.
     */
    public function massDelete(Request $request)
    {
        // Guru tidak boleh menghapus data siswa secara massal — sekali jalan bisa
        // melenyapkan seluruh kelas. Sama seperti aturan hapus ujian.
        if (!\App\Support\SiklusUjian::bolehHapusUjianDikerjakan()) {
            return redirect()->back()->with('error',
                'Hanya Admin atau Superadmin yang boleh menghapus data siswa secara massal.');
        }

        $request->validate([
            'ids' => 'required|array|min:1',
            'ids.*' => 'uuid',
        ], [], ['ids' => 'siswa yang dipilih']);

        $sid = \App\Support\SchoolScope::id();

        // Disaring ULANG di server: id dikirim dari peramban, jadi siswa sekolah
        // lain tidak boleh ikut terhapus walau id-nya diselipkan.
        $siswa = Student::with('user')
            ->whereIn('id', $request->ids)
            ->when($sid, fn ($q) => $q->where('school_id', $sid))
            ->get();

        $idSaya = auth()->id();
        $terhapus = 0;
        $dilewati = [];

        try {
            DB::beginTransaction();

            foreach ($siswa as $s) {
                // Jangan sampai yang sedang login menghapus akunnya sendiri.
                if ($s->user_id === $idSaya) {
                    $dilewati[] = ($s->user->name ?? 'akun sendiri') . ' (akun Anda sendiri)';
                    continue;
                }

                if ($s->user) {
                    $s->user->delete();   // cascade: profil siswa & data turunannya
                } else {
                    $s->delete();
                }
                $terhapus++;
            }

            DB::commit();
        } catch (\Exception $e) {
            DB::rollBack();

            return redirect()->back()->with('error', 'Gagal menghapus: ' . $e->getMessage());
        }

        $tidakDitemukan = count($request->ids) - $siswa->count();
        $pesan = $terhapus . ' data siswa beserta akun loginnya berhasil dihapus.';
        if ($dilewati) {
            $pesan .= ' Dilewati: ' . implode(', ', $dilewati) . '.';
        }
        if ($tidakDitemukan > 0) {
            $pesan .= ' ' . $tidakDitemukan . ' pilihan diabaikan (bukan siswa di sekolah ini).';
        }

        return redirect()->back()->with($terhapus > 0 ? 'success' : 'error', $pesan);
    }

    public function destroy($id)
    {
        $student = Student::findOrFail($id);
        if ($student->user) {
            $student->user->delete();
        }
        $student->delete();
        return redirect()->back()->with('success', 'Siswa berhasil dihapus');
    }
}
