import os

controller_teacher = r"""<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Teacher;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use DB;

class TeacherController extends Controller
{
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

    public function destroy($id)
    {
        $teacher = Teacher::findOrFail($id);
        if ($teacher->user) {
            $teacher->user->delete(); // This will cascade to teacher if cascade is set, or we do it explicitly
        }
        $teacher->delete();
        return redirect()->back()->with('success', 'Guru berhasil dihapus');
    }
}
"""

controller_student = r"""<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use DB;

class StudentController extends Controller
{
    public function index()
    {
        $students = Student::with(['user', 'school'])->get();
        $schools = School::all();
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
            ]);
            
            DB::commit();
            return redirect()->back()->with('success', 'Siswa berhasil ditambahkan');
        } catch (\Exception $e) {
            DB::rollBack();
            return redirect()->back()->with('error', 'Terjadi kesalahan: ' . $e->getMessage());
        }
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
"""

view_teacher = r"""@extends('backend.layout.app')
@section('title', 'Data Guru')
@section('content')
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
                    <h3 class="fw-bold mb-1">Manajemen Guru</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Guru</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>NIP</th>
                                <th>Nama Lengkap</th>
                                <th>Email (Akun Login)</th>
                                <th>No. Telepon</th>
                                <th>Gender</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @foreach($teachers as $item)
                            <tr>
                                <td>{{ $item->nip }}</td>
                                <td>{{ $item->user->name ?? '-' }}</td>
                                <td>{{ $item->user->email ?? '-' }}</td>
                                <td>{{ $item->phone }}</td>
                                <td>{{ $item->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('teachers.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger" onclick="return confirm('Menghapus guru ini akan menghapus akun loginnya juga. Lanjutkan?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('teachers.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h2 class="fw-bold">Tambah Guru Baru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <h5 class="mb-4 text-primary">Informasi Akun (Login)</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Password</label><input type="password" name="password" class="form-control form-control-solid" required></div>
                    
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Guru</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">NIP</label><input type="text" name="nip" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid"></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
                        <select name="gender" class="form-control form-control-solid">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid"></textarea></div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
"""

view_student = r"""@extends('backend.layout.app')
@section('title', 'Data Siswa')
@section('content')
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
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Siswa</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                <th>NISN</th>
                                <th>Nama Lengkap</th>
                                <th>Asal Sekolah</th>
                                <th>Email (Akun Login)</th>
                                <th>Gender</th>
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @foreach($students as $item)
                            <tr>
                                <td>{{ $item->nisn }}</td>
                                <td>{{ $item->user->name ?? '-' }}</td>
                                <td>{{ $item->school->name ?? '-' }}</td>
                                <td>{{ $item->user->email ?? '-' }}</td>
                                <td>{{ $item->gender == 'L' ? 'Laki-laki' : 'Perempuan' }}</td>
                                <td class="text-end">
                                    <form action="{{ route('students.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger" onclick="return confirm('Hapus siswa ini beserta akun loginnya?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Add Modal -->
<div class="modal fade" id="addModal" tabindex="-1" aria-hidden="true">
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
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">Password</label><input type="password" name="password" class="form-control form-control-solid" required></div>
                    
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Siswa</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Sekolah Asal</label>
                        <select name="school_id" class="form-control form-control-solid" required>
                            <option value="">Pilih Sekolah...</option>
                            @foreach($schools as $s)
                                <option value="{{ $s->id }}">{{ $s->name }}</option>
                            @endforeach
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">NISN</label><input type="text" name="nisn" class="form-control form-control-solid" required></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid"></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
                        <select name="gender" class="form-control form-control-solid">
                            <option value="L">Laki-laki</option>
                            <option value="P">Perempuan</option>
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid"></textarea></div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan</button>
                </div>
            </form>
        </div>
    </div>
</div>
@endsection
"""

def generate_users():
    base_dir = 'c:/xampp/htdocs/myProject/lms-sync'
    
    # Write Controllers
    with open(f"{base_dir}/app/Http/Controllers/Backend/Master/TeacherController.php", 'w') as f:
        f.write(controller_teacher)
    with open(f"{base_dir}/app/Http/Controllers/Backend/Master/StudentController.php", 'w') as f:
        f.write(controller_student)
        
    # Write Views
    os.makedirs(f"{base_dir}/resources/views/backend/master/teachers", exist_ok=True)
    os.makedirs(f"{base_dir}/resources/views/backend/master/students", exist_ok=True)
    with open(f"{base_dir}/resources/views/backend/master/teachers/index.blade.php", 'w') as f:
        f.write(view_teacher)
    with open(f"{base_dir}/resources/views/backend/master/students/index.blade.php", 'w') as f:
        f.write(view_student)
        
    # Add Routes
    with open(f"{base_dir}/routes/web.php", 'r') as f:
        routes_content = f.read()
        
    if "TeacherController::class" not in routes_content:
        routes_content = routes_content.replace(
            "// Master Data Routes LMS\n",
            "// Master Data Routes LMS\n    Route::resource('/admin/teachers', \\App\\Http\\Controllers\\Backend\\Master\\TeacherController::class);\n    Route::resource('/admin/students', \\App\\Http\\Controllers\\Backend\\Master\\StudentController::class);\n"
        )
        with open(f"{base_dir}/routes/web.php", 'w') as f:
            f.write(routes_content)
            
    print("User Integration generated successfully!")

if __name__ == "__main__":
    generate_users()
