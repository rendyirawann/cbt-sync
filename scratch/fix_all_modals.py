import os
import re

base_dir = 'c:/xampp/htdocs/myProject/lms-sync'

def fix_view_modals(file_path, items_var, modal_template):
    with open(file_path, 'r', encoding='utf-8') as f:
        content = f.read()
    
    # 1. Strip out any existing "Edit Modal" blocks
    # Looking for <!-- Edit Modal --> ... final </div> of the modal
    # Since they are drawer-modals, they end with </div>\n    </div>\n</div>
    # Let's use a non-greedy regex
    content = re.sub(r'<!-- Edit Modal -->.*?</div>\s*</div>\s*</div>', '', content, flags=re.DOTALL)
    
    # 2. Re-insert the modals at the end of the content section (before @endsection)
    modal_loop = f"""
@foreach(${items_var} as $item)
{modal_template}
@endforeach
"""
    if "@endsection" in content:
        content = content.replace("@endsection", modal_loop + "\n@endsection")
    
    with open(file_path, 'w', encoding='utf-8') as f:
        f.write(content)
    print(f"Fixed {file_path}")

# Modals templates (extracted from previous turns)
student_modal = """
<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
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
                    <div class="fv-row mb-7"><label class="fs-6 fw-semibold mb-2">Password (Kosongkan jika tidak diubah)</label><input type="password" name="password" class="form-control form-control-solid"></div>
                    
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Siswa</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Sekolah Asal</label>
                        <select name="school_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
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
                        <select name="gender" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}">
                            <option value="L" {{ $item->gender == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $item->gender == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid">{{ $item->address }}</textarea></div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
"""

teacher_modal = """
<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('teachers.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Guru</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <h5 class="mb-4 text-primary">Informasi Akun (Login)</h5>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Nama Lengkap</label><input type="text" name="name" class="form-control form-control-solid" value="{{ $item->user->name ?? '' }}" required></div>
                    <div class="fv-row mb-5"><label class="required fs-6 fw-semibold mb-2">Email</label><input type="email" name="email" class="form-control form-control-solid" value="{{ $item->user->email ?? '' }}" required></div>
                    <div class="fv-row mb-7"><label class="fs-6 fw-semibold mb-2">Password (Kosongkan jika tidak diubah)</label><input type="password" name="password" class="form-control form-control-solid"></div>
                    
                    <h5 class="mb-4 text-primary border-top pt-4">Profil Guru</h5>
                    <div class="fv-row mb-5">
                        <label class="required fs-6 fw-semibold mb-2">NIP</label>
                        <input type="text" name="nip" class="form-control form-control-solid" value="{{ $item->nip }}" required>
                        <div class="text-muted fs-7 mt-2">Perhatian: Mengubah NIP akan mengubah Username login.</div>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Telepon</label><input type="text" name="phone" class="form-control form-control-solid" value="{{ $item->phone }}"></div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Jenis Kelamin</label>
                        <select name="gender" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}">
                            <option value="L" {{ $item->gender == 'L' ? 'selected' : '' }}>Laki-laki</option>
                            <option value="P" {{ $item->gender == 'P' ? 'selected' : '' }}>Perempuan</option>
                        </select>
                    </div>
                    <div class="fv-row mb-5"><label class="fs-6 fw-semibold mb-2">Alamat</label><textarea name="address" class="form-control form-control-solid">{{ $item->address }}</textarea></div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
"""

assignment_modal = """
<!-- Edit Modal -->
<div class="modal fade drawer-modal" id="editModal{{ $item->id }}" tabindex="-1" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <form action="{{ route('teaching-assignments.update', $item->id) }}" method="POST">
                @csrf @method('PUT')
                <div class="modal-header">
                    <h2 class="fw-bold">Edit Penugasan</h2>
                    <div class="btn btn-icon btn-sm btn-active-icon-primary" data-bs-dismiss="modal">
                        <i class="ki-outline ki-cross fs-1 text-dark"></i>
                    </div>
                </div>
                <div class="modal-body">
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Guru</label>
                        <select name="teacher_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($teachers as $t)
                                <option value="{{ $t->id }}" {{ $item->teacher_id == $t->id ? 'selected' : '' }}>{{ $t->user->name ?? '-' }} (NIP: {{ $t->nip }})</option>
                            @endforeach
                        </select>
                    </div>
                    
                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Mata Pelajaran</label>
                        <select name="subject_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($subjects as $s)
                                <option value="{{ $s->id }}" {{ $item->subject_id == $s->id ? 'selected' : '' }}>{{ $s->name }} ({{ $s->code }})</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Pilih Kelas</label>
                        <select name="class_room_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($classRooms as $c)
                                <option value="{{ $c->id }}" {{ $item->class_room_id == $c->id ? 'selected' : '' }}>{{ $c->name }} - {{ $c->school->name ?? '-' }}</option>
                            @endforeach
                        </select>
                    </div>

                    <div class="fv-row mb-7">
                        <label class="required fs-6 fw-semibold mb-2">Tahun Ajaran Aktif</label>
                        <select name="academic_year_id" class="form-select form-select-solid" data-control="select2" data-dropdown-parent="#editModal{{ $item->id }}" required>
                            @foreach($academicYears as $ay)
                                <option value="{{ $ay->id }}" {{ $item->academic_year_id == $ay->id ? 'selected' : '' }}>{{ $ay->name }} (Semester {{ $ay->semester }})</option>
                            @endforeach
                        </select>
                    </div>
                </div>
                <div class="modal-footer flex-center">
                    <button type="button" class="btn btn-light me-3" data-bs-dismiss="modal">Batal</button>
                    <button type="submit" class="btn btn-primary">Simpan Perubahan</button>
                </div>
            </form>
        </div>
    </div>
</div>
"""

fix_view_modals(os.path.join(base_dir, 'resources/views/backend/master/students/index.blade.php'), 'students', student_modal)
fix_view_modals(os.path.join(base_dir, 'resources/views/backend/master/teachers/index.blade.php'), 'teachers', teacher_modal)
fix_view_modals(os.path.join(base_dir, 'resources/views/backend/master/teaching-assignments/index.blade.php'), 'assignments', assignment_modal)
