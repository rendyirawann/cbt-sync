import os

base_dir = 'c:/xampp/htdocs/myProject/lms-sync'

# 1. Update Route
route_path = os.path.join(base_dir, 'routes', 'web.php')
with open(route_path, 'r', encoding='utf-8') as f:
    routes = f.read()

if 'TeachingAssignmentController' not in routes:
    routes = routes.replace("use App\\Http\\Controllers\\Backend\\Master\\ClassRoomController;", "use App\\Http\\Controllers\\Backend\\Master\\ClassRoomController;\nuse App\\Http\\Controllers\\Backend\\Master\\TeachingAssignmentController;")
    
    resource_line = "Route::resource('class-rooms', ClassRoomController::class);"
    new_resource = "Route::resource('class-rooms', ClassRoomController::class);\n            Route::resource('teaching-assignments', TeachingAssignmentController::class);"
    routes = routes.replace(resource_line, new_resource)
    
    with open(route_path, 'w', encoding='utf-8') as f:
        f.write(routes)

# 2. Update Model Relationships
model_path = os.path.join(base_dir, 'app', 'Models', 'TeachingAssignment.php')
with open(model_path, 'w', encoding='utf-8') as f:
    f.write('''<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUuids;

class TeachingAssignment extends Model
{
    use HasUuids;
    protected $guarded = [];

    public function classRoom()
    {
        return $this->belongsTo(ClassRoom::class, 'class_room_id');
    }

    public function subject()
    {
        return $this->belongsTo(Subject::class, 'subject_id');
    }

    public function teacher()
    {
        return $this->belongsTo(Teacher::class, 'teacher_id');
    }

    public function academicYear()
    {
        return $this->belongsTo(AcademicYear::class, 'academic_year_id');
    }
}
''')

# 3. Create Controller
controller_path = os.path.join(base_dir, 'app', 'Http', 'Controllers', 'Backend', 'Master', 'TeachingAssignmentController.php')
with open(controller_path, 'w', encoding='utf-8') as f:
    f.write('''<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\TeachingAssignment;
use App\Models\ClassRoom;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class TeachingAssignmentController extends Controller
{
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
        $request->validate([
            'class_room_id' => 'required',
            'subject_id' => 'required',
            'teacher_id' => 'required',
            'academic_year_id' => 'required',
        ]);

        // Cek duplikasi
        $exists = TeachingAssignment::where([
            'class_room_id' => $request->class_room_id,
            'subject_id' => $request->subject_id,
            'teacher_id' => $request->teacher_id,
            'academic_year_id' => $request->academic_year_id,
        ])->exists();

        if ($exists) {
            return redirect()->back()->with('error', 'Penugasan ini sudah ada sebelumnya!');
        }

        TeachingAssignment::create($request->all());
        return redirect()->back()->with('success', 'Penugasan guru berhasil ditambahkan');
    }

    public function destroy($id)
    {
        TeachingAssignment::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Penugasan berhasil dihapus');
    }
}
''')

print("Created Routes, Model, and Controller for TeachingAssignment")
