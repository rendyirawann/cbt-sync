<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class TeachingAssignmentController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;
    public function index()
    {
        $assignments = TeachingAssignment::with(['classRoom.school', 'subject', 'teacher.user', 'academicYear'])->get();
        $classRooms = ClassRoom::with('school')->get();
        $subjects = Subject::all();
        $teachers = Teacher::with('user')->get();
        $academicYears = AcademicYear::where('is_active', 1)->get();
        
        return view('backend.master.teaching-assignments.index', compact('assignments', 'classRooms', 'subjects', 'teachers', 'academicYears'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());

        // Cek duplikasi
        $exists = TeachingAssignment::where($data)->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Penugasan ini sudah ada sebelumnya!');
        }

        TeachingAssignment::create($data);
        return redirect()->back()->with('success', 'Penugasan guru berhasil ditambahkan');
    }

    
    public function update(Request $request, $id)
    {
        $assignment = TeachingAssignment::findOrFail($id);

        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());

        // Cek duplikasi
        $exists = TeachingAssignment::where($data)->where('id', '!=', $id)->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Penugasan ini sudah ada sebelumnya!');
        }

        $assignment->update($data);
        return redirect()->back()->with('success', 'Penugasan guru berhasil diperbarui');
    }

    public function destroy($id)
    {
        TeachingAssignment::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Penugasan berhasil dihapus');
    }

    private function rules(): array
    {
        return [
            'class_room_id' => 'required|uuid|exists:class_rooms,id',
            'subject_id' => 'required|uuid|exists:subjects,id',
            'teacher_id' => 'required|uuid|exists:teachers,id',
            'academic_year_id' => 'required|uuid|exists:academic_years,id',
        ];
    }

    private function labels(): array
    {
        return [
            'class_room_id' => 'Kelas',
            'subject_id' => 'Mata Pelajaran',
            'teacher_id' => 'Guru',
            'academic_year_id' => 'Tahun Ajaran',
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

        $rules = ['teacher' => 'required|string', 'subject' => 'required|string', 'class' => 'required|string', 'year' => 'required|string'];
        $labels = ['teacher' => 'Guru', 'subject' => 'Mata Pelajaran', 'class' => 'Kelas', 'year' => 'Tahun Ajaran'];
        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $labels);
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }

            $teacher = Teacher::whereHas('user', fn ($q) => $q->where('email', $row['teacher'])->orWhere('name', $row['teacher']))->first();
            $subject = Subject::where('code', $row['subject'])->orWhere('name', $row['subject'])->first();
            $class = ClassRoom::where('name', $row['class'])->first();
            $year = AcademicYear::where('name', $row['year'])->orderByDesc('is_active')->first();

            $miss = [];
            if (!$teacher) $miss[] = "Guru \"{$row['teacher']}\"";
            if (!$subject) $miss[] = "Mapel \"{$row['subject']}\"";
            if (!$class) $miss[] = "Kelas \"{$row['class']}\"";
            if (!$year) $miss[] = "Tahun Ajaran \"{$row['year']}\"";
            if ($miss) { $errors[] = "Baris $line: " . implode(', ', $miss) . ' tidak ditemukan.'; continue; }

            $data = ['teacher_id' => $teacher->id, 'subject_id' => $subject->id, 'class_room_id' => $class->id, 'academic_year_id' => $year->id];
            if (TeachingAssignment::where($data)->exists()) { $skipped++; continue; }
            try { TeachingAssignment::create($data); $imported++; }
            catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA PENUGASAN GURU',
            'file' => 'Template_Penugasan_Guru.xlsx',
            'guide' => [
                'Guru, Mapel, Kelas, dan Tahun Ajaran HARUS sudah terdaftar (tulis persis).',
                'Guru: isi email atau nama guru. Mapel: isi kode atau nama mapel.',
                'Penugasan yang sudah ada akan dilewati.',
            ],
            'columns' => [
                ['key' => 'teacher', 'label' => 'Guru', 'required' => true, 'width' => 30, 'hint' => 'email atau nama guru'],
                ['key' => 'subject', 'label' => 'Mata Pelajaran', 'required' => true, 'width' => 26, 'hint' => 'kode atau nama mapel'],
                ['key' => 'class', 'label' => 'Kelas', 'required' => true, 'width' => 20, 'hint' => 'nama kelas'],
                ['key' => 'year', 'label' => 'Tahun Ajaran', 'required' => true, 'width' => 20, 'hint' => 'mis. 2025/2026'],
            ],
            'examples' => [
                ['teacher' => 'guru@lms.com', 'subject' => 'MTK', 'class' => 'X-IPA 1', 'year' => '2025/2026'],
                ['teacher' => 'Bpk. Budi Santoso, S.Pd', 'subject' => 'Matematika', 'class' => 'XI-IPS 1', 'year' => '2025/2026'],
            ],
        ];
    }
}
