<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Spatie\Permission\Models\Role;
use DB;

class TeacherController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;

    public function index()
    {
        $teachers = Teacher::with('user')->get();
        return view('backend.master.teachers.index', compact('teachers'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'nip' => 'required|unique:teachers,nip',
        ]);

        try {
            DB::beginTransaction();
            
            // Buat User
            $user = User::create([
                'name' => $request->name,
                'email' => $request->email,
                'username' => $request->nip,
                'no_wa' => $request->phone,
                'phone' => $request->phone,
                'email_verified_at' => now(),
                'is_active' => 1,
                'password' => Hash::make($request->password),
            ]);
            
            // Set Role (Pastikan role Guru sudah ada)
            $role = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
            $user->assignRole($role);
            
            // Buat Profil Guru
            Teacher::create([
                'user_id' => $user->id,
                'nip' => $request->nip,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);
            
            DB::commit();
            return redirect()->back()->with('success', 'Guru berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
    }

    
    public function update(Request $request, $id)
    {
        $teacher = Teacher::findOrFail($id);
        
        $request->validate([
            'name' => 'required',
            'email' => 'required|email|unique:users,email,' . $teacher->user_id,
            'nip' => 'required|unique:teachers,nip,' . $teacher->id,
        ]);

        try {
            \DB::beginTransaction();
            
            $user = $teacher->user;
            $user->name = $request->name;
            $user->email = $request->email;
            if ($request->filled('password')) {
                $user->password = \Hash::make($request->password);
            }
            $user->save();

            $teacher->update([
                'nip' => $request->nip,
                'phone' => $request->phone,
                'gender' => $request->gender,
                'address' => $request->address,
            ]);
            
            \DB::commit();
            return redirect()->back()->with('success', 'Data Guru berhasil diperbarui');
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

        $rules = ['name' => 'required|string|max:255', 'email' => 'required|email', 'gender' => 'nullable|in:L,P'];
        $labels = ['name' => 'Nama', 'email' => 'Email', 'gender' => 'Gender'];
        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $labels);
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }
            if (User::where('email', $row['email'])->exists()) { $skipped++; continue; }
            $nip = $row['nip'] ?? '';
            if ($nip !== '' && Teacher::where('nip', $nip)->exists()) { $errors[] = "Baris $line: NIP \"$nip\" sudah dipakai."; continue; }
            try {
                DB::transaction(function () use ($row, $nip) {
                    $user = User::create([
                        'name' => $row['name'],
                        'email' => $row['email'],
                        'username' => $nip !== '' ? $nip : $row['email'],
                        'no_wa' => $row['phone'] ?? null,
                        'phone' => $row['phone'] ?? null,
                        'email_verified_at' => now(),
                        'is_active' => 1,
                        'password' => Hash::make($row['password'] !== '' ? $row['password'] : 'guru12345'),
                    ]);
                    $user->assignRole(Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']));
                    Teacher::create([
                        'user_id' => $user->id,
                        'nip' => $nip !== '' ? $nip : null,
                        'phone' => $row['phone'] ?? null,
                        'gender' => in_array($row['gender'] ?? '', ['L', 'P']) ? $row['gender'] : null,
                        'address' => $row['address'] ?? null,
                    ]);
                });
                $imported++;
            } catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA GURU',
            'file' => 'Template_Data_Guru.xlsx',
            'guide' => [
                'Email harus unik (jadi akun login guru). Email yang sudah ada dilewati.',
                'Password kosong = default "guru12345" (guru bisa ganti nanti).',
                'Gender diisi L atau P.',
            ],
            'columns' => [
                ['key' => 'name', 'label' => 'Nama', 'required' => true, 'width' => 30],
                ['key' => 'email', 'label' => 'Email', 'required' => true, 'width' => 28, 'hint' => 'untuk login'],
                ['key' => 'password', 'label' => 'Password', 'width' => 18, 'hint' => 'kosong = guru12345'],
                ['key' => 'nip', 'label' => 'NIP', 'width' => 22],
                ['key' => 'phone', 'label' => 'No. HP/WA', 'width' => 18],
                ['key' => 'gender', 'label' => 'Gender', 'width' => 12, 'options' => ['L', 'P']],
                ['key' => 'address', 'label' => 'Alamat', 'width' => 30],
            ],
            'examples' => [
                ['name' => 'Budi Santoso, S.Pd', 'email' => 'budi@sekolah.id', 'password' => '', 'nip' => '198501012010011001', 'phone' => '081234567890', 'gender' => 'L', 'address' => 'Jl. Merdeka 1'],
                ['name' => 'Siti Aminah, S.Pd', 'email' => 'siti@sekolah.id', 'password' => 'rahasia123', 'nip' => '198702022011012002', 'phone' => '081298765432', 'gender' => 'P', 'address' => 'Jl. Melati 2'],
            ],
        ];
    }

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);

        // Proteksi Hapus: Cek apakah guru sudah di-assign ke kelas
        if (\App\Models\TeachingAssignment::where('teacher_id', $id)->exists()) {
            return redirect()->back()->with('error', 'Gagal: Guru ini tidak dapat dihapus karena sudah memiliki Penugasan Mengajar. Silakan hapus penugasannya terlebih dahulu.');
        }

        if ($teacher->user) {
            $teacher->user->delete(); 
        }
        $teacher->delete();
        return redirect()->back()->with('success', 'Guru berhasil dihapus');
    }
}
