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

    public function index()
    {
        $sid = \App\Support\SchoolScope::id();
        $students = Student::with(['user', 'school'])
            ->when($sid, fn ($q) => $q->where('school_id', $sid))
            ->get();
        $schools = $sid ? School::where('id', $sid)->get() : School::all();
        return view('backend.master.students.index', compact('students', 'schools'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nisn' => 'required|unique:students,nisn',
            'school_id' => 'required'
        ]);

        try {
            DB::beginTransaction();
            
            // Buat User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->nisn,
                'no_wa' => $request->phone,
                'phone' => $request->phone,
                'email_verified_at' => now(),
                'is_active' => 1,
                'password' => Hash::make($request->password),
            ]);
            
            // Set Role
            $role = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
            $user->assignRole($role);
            
            // Buat Profil Siswa
            Student::create([
                'user_id' => $user->id,
                'school_id' => $request->school_id,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'address' => $request->address,
                'parent_name' => $request->parent_name,
                'parent_email' => $request->parent_email,
                'parent_phone' => $request->parent_phone,
            ]);
            
            DB::commit();
            return redirect()->back()->with('success', 'Siswa berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    
    public function update(Request $request, $id)
    {
        $student = Student::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $student->user_id,
            'nisn' => 'required|unique:students,nisn,' . $student->id,
            'school_id' => 'required'
        ]);

        try {
            \DB::beginTransaction();
            
            $user = $student->user;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = \Hash::make($request->password);
            }
            $user->save();

            $student->update([
                'school_id' => $request->school_id,
                'nisn' => $request->nisn,
                'phone' => $request->phone,
                'gender' => $request->gender,
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

        $rules = ['name' => 'required|string|max:255', 'email' => 'required|email', 'school' => 'required|string', 'gender' => 'nullable|in:L,P'];
        $labels = ['name' => 'Nama', 'email' => 'Email', 'school' => 'Nama Sekolah', 'gender' => 'Gender'];
        $activeYear = AcademicYear::where('is_active', 1)->first() ?? AcademicYear::first();
        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $labels);
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }
            if (User::where('email', $row['email'])->exists()) { $skipped++; continue; }
            $school = School::where('name', $row['school'])->first();
            if (!$school) { $errors[] = "Baris $line: Sekolah \"{$row['school']}\" tidak ditemukan."; continue; }
            $nisn = $row['nisn'] ?? '';
            if ($nisn !== '' && Student::where('nisn', $nisn)->exists()) { $errors[] = "Baris $line: NISN \"$nisn\" sudah dipakai."; continue; }
            try {
                DB::transaction(function () use ($row, $school, $nisn, $activeYear) {
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'username' => $nisn !== '' ? $nisn : $row['email'],
                        'no_wa' => $row['phone'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'email_verified_at' => now(),
                        'is_active' => 1,
                        'password' => Hash::make($row['password'] !== '' ? $row['password'] : 'siswa12345'),
                    ]);
                    $user->assignRole(Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']));
                    $student = Student::create([
                        'user_id' => $user->id,
                        'school_id' => $school->id,
                        'nisn' => $nisn !== '' ? $nisn : null,
                        'phone' => $row['phone'] ?? null,
                        'gender' => in_array($row['gender'] ?? '', ['L', 'P']) ? $row['gender'] : null,
                        'address' => $row['address'] ?? null,
                        'parent_name' => $row['parent_name'] ?? null,
                        'parent_email' => $row['parent_email'] ?? null,
                        'parent_phone' => $row['parent_phone'] ?? null,
                    ]);
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
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA SISWA',
            'file' => 'Template_Data_Siswa.xlsx',
            'guide' => [
                'Email harus unik (jadi akun login siswa). Email yang sudah ada dilewati.',
                'Password kosong = default "siswa12345".',
                'Nama Sekolah harus sudah terdaftar. Kolom Kelas opsional (isi nama kelas untuk langsung memasukkan siswa ke rombel tahun ajaran aktif).',
                'Gender diisi L atau P.',
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Nama', 'required' => true, 'width' => 28],
                ['key' => 'email', 'label' => 'Email', 'required' => true, 'width' => 26, 'hint' => 'untuk login'],
                ['key' => 'password', 'label' => 'Password', 'width' => 16, 'hint' => 'kosong = siswa12345'],
                ['key' => 'nisn', 'label' => 'NISN', 'width' => 18],
                ['key' => 'school', 'label' => 'Nama Sekolah', 'required' => true, 'width' => 30, 'hint' => 'harus sudah ada'],
                ['key' => 'class', 'label' => 'Kelas', 'width' => 16, 'hint' => 'opsional (nama kelas)'],
                ['key' => 'gender', 'label' => 'Gender', 'width' => 10, 'options' => ['L', 'P']],
                ['key' => 'phone', 'label' => 'No. HP/WA', 'width' => 16],
                ['key' => 'address', 'label' => 'Alamat', 'width' => 26],
                ['key' => 'parent_name', 'label' => 'Nama Ortu', 'width' => 24],
                ['key' => 'parent_email', 'label' => 'Email Ortu', 'width' => 24],
                ['key' => 'parent_phone', 'label' => 'No. HP Ortu', 'width' => 18],
            ],
            'examples' => [
                ['name' => 'Andi Pratama', 'email' => 'andi@siswa.id', 'password' => '', 'nisn' => '0012345678', 'school' => 'SMA Negeri 1 Medan', 'class' => 'X-IPA 1', 'gender' => 'L', 'phone' => '081200001111', 'address' => 'Jl. Kenanga 3', 'parent_name' => 'Bpk. Pratama', 'parent_email' => 'ortu.andi@mail.com', 'parent_phone' => '081211112222'],
            ],
        ];
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
