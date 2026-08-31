<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class ClassStudentController extends Controller
{
    public function index()
    {
        $enrollments = ClassStudent::with(['student.user', 'classRoom.school', 'academicYear'])->latest()->get();
        $classRooms = ClassRoom::with('school')->get();
        
        // Cari Tahun Ajaran Aktif
        $activeAY = AcademicYear::where('is_active', 1)->first();
        
        // Siswa yang belum memiliki kelas di tahun ajaran aktif
        $students = Student::whereDoesntHave('classStudents', function($q) use ($activeAY) {
            if($activeAY) $q->where('academic_year_id', $activeAY->id);
        })->with('user')->get();

        $academicYears = AcademicYear::all();
        
        return view('backend.master.enrollments.index', compact('enrollments', 'classRooms', 'students', 'academicYears', 'activeAY'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_room_id' => 'required',
            'academic_year_id' => 'required',
            'student_ids' => 'required|array',
        ]);

        try {
            foreach ($request->student_ids as $sid) {
                // Gunakan updateOrCreate untuk memindahkan siswa jika dia sudah ada di kelas lain di tahun yang sama
                ClassStudent::updateOrCreate(
                    [
                        'student_id' => $sid,
                        'academic_year_id' => $request->academic_year_id,
                    ],
                    [
                        'class_room_id' => $request->class_room_id
                    ]
                );
            }

            return redirect()->back()->with('success', count($request->student_ids) . ' Siswa berhasil dimasukkan ke dalam kelas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memplot siswa: ' . $e->getMessage());
        }
    }

    public function destroy($id)
    {
        try {
            ClassStudent::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Siswa berhasil dikeluarkan dari kelas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses data: ' . $e->getMessage());
        }
    }
}
