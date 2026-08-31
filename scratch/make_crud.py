import os

models = [
    {
        "model": "School",
        "var": "school",
        "title": "Sekolah",
        "route": "schools",
        "fields": [("name", "Nama"), ("address", "Alamat"), ("phone", "Telepon"), ("email", "Email")]
    },
    {
        "model": "AcademicYear",
        "var": "academicYear",
        "title": "Tahun Ajaran",
        "route": "academic-years",
        "fields": [("name", "Tahun Ajaran"), ("semester", "Semester"), ("is_active", "Status Aktif (1/0)")]
    },
    {
        "model": "Subject",
        "var": "subject",
        "title": "Pelajaran",
        "route": "subjects",
        "fields": [("code", "Kode"), ("name", "Nama Pelajaran")]
    },
    {
        "model": "ClassRoom",
        "var": "classRoom",
        "title": "Kelas",
        "route": "class-rooms",
        "fields": [("school_id", "ID Sekolah"), ("name", "Nama Kelas"), ("level", "Tingkat/Level")]
    }
]

controller_template = """<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\{Model};
use Illuminate\Http\Request;

class {Model}Controller extends Controller
{
    public function index()
    {
        ${var}s = {Model}::all();
        return view('backend.master.{route}.index', compact('{var}s'));
    }

    public function store(Request $request)
    {
        {Model}::create($request->all());
        return redirect()->back()->with('success', '{title} berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $item = {Model}::findOrFail($id);
        $item->update($request->all());
        return redirect()->back()->with('success', '{title} berhasil diupdate');
    }

    public function destroy($id)
    {
        {Model}::findOrFail($id)->delete();
        return redirect()->back()->with('success', '{title} berhasil dihapus');
    }
}
"""

view_template = """@extends('backend.layout.app')
@section('title', '{title}')
@section('content')
<div class="app-content flex-column-fluid">
    <div class="app-container container-xxl">
        <div class="card card-flush mt-6 mt-xl-9">
            <div class="card-header mt-5">
                <div class="card-title flex-column">
                    <h3 class="fw-bold mb-1">Manajemen {title}</h3>
                </div>
                <div class="card-toolbar">
                    <button type="button" class="btn btn-sm btn-primary" data-bs-toggle="modal" data-bs-target="#addModal">Tambah Data</button>
                </div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive">
                    <table class="table table-row-bordered table-row-dashed gy-4 align-middle fw-bold">
                        <thead class="fs-7 text-gray-400 text-uppercase">
                            <tr>
                                {th_fields}
                                <th class="text-end">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="fs-6">
                            @foreach(${var}s as $item)
                            <tr>
                                {td_fields}
                                <td class="text-end">
                                    <button class="btn btn-sm btn-light-primary btn-active-primary" data-bs-toggle="modal" data-bs-target="#editModal{{ $item->id }}">Edit</button>
                                    <form action="{{ route('{route}.destroy', $item->id) }}" method="POST" class="d-inline">
                                        @csrf @method('DELETE')
                                        <button type="submit" class="btn btn-sm btn-light-danger btn-active-danger" onclick="return confirm('Hapus data ini?')">Hapus</button>
                                    </form>
                                </td>
                            </tr>
                            
                            <!-- Edit Modal -->
                            <div class="modal fade" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
                                <div class="modal-dialog">
                                    <div class="modal-content">
                                        <form action="{{ route('{route}.update', $item->id) }}" method="POST">
                                            @csrf @method('PUT')
                                            <div class="modal-header"><h2 class="fw-bold">Edit {title}</h2>
                                                <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                                                    <i class="ki-outline ki-cross fs-1 text-dark"></i>
                                                </div>
                                            </div>
                                            <div class="modal-body">
                                                {edit_inputs}
                                            </div>
                                            <div class="modal-footer flex-center">
                                                <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                                                <button type="submit" class="btn btn-primary">Simpan</button>
                                            </div>
                                        </form>
                                    </div>
                                </div>
                            </div>
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
            <form action="{{ route('{route}.store') }}" method="POST">
                @csrf
                <div class="modal-header"><h2 class="fw-bold">Tambah {title}</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    {add_inputs}
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

def generate_crud():
    base_dir = 'c:/xampp/htdocs/myProject/lms-sync'
    os.makedirs(f'{base_dir}/app/Http/Controllers/Backend/Master', exist_ok=True)
    routes_to_add = []
    
    for m in models:
        # Generate Controller
        ctrl_code = controller_template.replace('{Model}', m['model']).replace('{var}', m['var']).replace('{title}', m['title']).replace('{route}', m['route'])
        with open(f"{base_dir}/app/Http/Controllers/Backend/Master/{m['model']}Controller.php", 'w') as f:
            f.write(ctrl_code)
            
        # Generate Views
        os.makedirs(f"{base_dir}/resources/views/backend/master/{m['route']}", exist_ok=True)
        
        th_fields = "\n                                ".join([f"<th>{f[1]}</th>" for f in m['fields']])
        td_fields = "\n                                ".join([f"<td>{{{{ $item->{f[0]} }}}}</td>" for f in m['fields']])
        
        add_inputs = ""
        edit_inputs = ""
        for f in m['fields']:
            add_inputs += f'''<div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">{f[1]}</label><input type="text" name="{f[0]}" class="form-control form-control-solid" required></div>\n                    '''
            edit_inputs += f'''<div class="fv-row mb-7"><label class="required fs-6 fw-semibold mb-2">{f[1]}</label><input type="text" name="{f[0]}" class="form-control form-control-solid" value="{{{{ $item->{f[0]} }}}}" required></div>\n                                                '''
            
        view_code = view_template.replace('{title}', m['title']).replace('{route}', m['route']).replace('{var}', m['var']).replace('{th_fields}', th_fields).replace('{td_fields}', td_fields).replace('{add_inputs}', add_inputs).replace('{edit_inputs}', edit_inputs)
        
        with open(f"{base_dir}/resources/views/backend/master/{m['route']}/index.blade.php", 'w') as f:
            f.write(view_code)
            
        # Add to routes list
        routes_to_add.append(f"Route::resource('/admin/{m['route']}', \\App\\Http\\Controllers\\Backend\\Master\\{m['model']}Controller::class);")
        
    # Append routes to web.php
    with open(f"{base_dir}/routes/web.php", 'r') as f:
        routes_content = f.read()
        
    if "Master Data Routes LMS" not in routes_content:
        routes_content = routes_content.replace(
            "Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');",
            "Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');\n\n    // Master Data Routes LMS\n    " + "\n    ".join(routes_to_add)
        )
        with open(f"{base_dir}/routes/web.php", 'w') as f:
            f.write(routes_content)

    print("Master Data CRUD generated successfully!")

if __name__ == "__main__":
    generate_crud()
