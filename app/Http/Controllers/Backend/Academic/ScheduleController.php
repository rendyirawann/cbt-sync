<?php

namespace App\Http\Controllers\Backend\Academic;

use App\Http\Controllers\Controller;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class ScheduleController extends Controller
{
    public function index()
    {
        $user = Auth::user();

        if ($user->hasRole('Guru') && !$user->teacher) {
            return redirect()->route('dashboard')->with('error', 'Profil guru tidak ditemukan.');
        }

        $query = Schedule::with(['teachingAssignment.classRoom', 'teachingAssignment.subject', 'teachingAssignment.teacher.user']);

        if ($user->hasRole('Guru')) {
            $teacherId = $user->teacher->id;
            $query->whereHas('teachingAssignment', function($q) use ($teacherId) {
                $q->where('teacher_id', $teacherId);
            });
        }

        $schedules = $query->orderBy('day_of_week')->orderBy('start_time')->get();
        
        // Get available teaching assignments for adding new schedule
        $assignmentsQuery = TeachingAssignment::with(['classRoom', 'subject']);
        if ($user->hasRole('Guru')) {
            $assignmentsQuery->where('teacher_id', $user->teacher->id);
        }
        $assignments = $assignmentsQuery->get();

        return view('backend.academic.schedules.index', compact('schedules', 'assignments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teaching_assignment_id' => 'required|exists:teaching_assignments,id',
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'meeting_url' => 'nullable|url',
        ]);

        Schedule::create($request->all());

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil ditambahkan.');
    }

    public function update(Request $request, Schedule $schedule)
    {
        $request->validate([
            'day_of_week' => 'required|integer|min:1|max:7',
            'start_time' => 'required',
            'end_time' => 'required|after:start_time',
            'meeting_url' => 'nullable|url',
        ]);

        $schedule->update($request->all());

        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil diperbarui.');
    }

    public function destroy(Schedule $schedule)
    {
        $schedule->delete();
        return redirect()->back()->with('success', 'Jadwal pelajaran berhasil dihapus.');
    }
}
