<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use App\Traits\ValidatesMasterData;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
    use ValidatesMasterData;
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
}
