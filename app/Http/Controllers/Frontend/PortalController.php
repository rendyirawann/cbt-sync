<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\ClassStudent;
use App\Models\TeachingAssignment;
use App\Models\LearningModule;
use App\Models\Assignment;
use App\Models\Attendance;
use Carbon\Carbon;
use Illuminate\Support\Facades\Auth;

class PortalController extends Controller
{
    public function login()
    {
        if (Auth::check()) {
            if (Auth::user()->hasRole('Siswa')) {
                return redirect()->route('student.dashboard');
            }
            return redirect()->route('dashboard');
        }
        return view('frontend.auth.login');
    }

    public function authenticate(Request $request)
    {
        $request->validate([
            'email' => 'required|email',
            'password' => 'required',
        ]);

        if (Auth::attempt($request->only('email', 'password'), $request->filled('remember'))) {
            $user = Auth::user();
            
            // JIKA BUKAN SISWA -> TENDANG
            if (!$user->hasRole('Siswa')) {
                Auth::logout();
                $request->session()->invalidate();
                $request->session()->regenerateToken();

                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses Ditolak! Akun ini bukan akun siswa.',
                ], 403);
            }

            $request->session()->regenerate();

            return response()->json([
                'status' => 'success',
                'message' => 'Login Berhasil! Selamat datang, ' . $user->name,
                'redirect' => route('student.dashboard')
            ]);
        }

        return response()->json([
            'status' => 'error',
            'message' => 'Email atau Password salah!',
        ], 401);
    }

    public function dashboard()
    {
        $user = Auth::user();
        if (!$user->hasRole('Siswa')) {
            return redirect()->route('dashboard');
        }

        $student = $user->student;
        $classId = ClassStudent::where('student_id', $student->id)
            ->whereHas('academicYear', function($q) { $q->where('is_active', 1); })
            ->value('class_room_id');

        $stats = [
            'my_subjects' => TeachingAssignment::where('class_room_id', $classId)->count(),
            'new_modules' => LearningModule::whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            })->count(),
            'pending_assignments' => Assignment::whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            })->whereDoesntHave('submissions', function($q) use ($student) {
                $q->where('student_id', $student->id);
            })->count(),
            'attendance_status' => [
                'datang' => Attendance::where('user_id', $user->id)->where('type', 'datang')->whereDate('created_at', Carbon::today())->exists(),
                'pulang' => Attendance::where('user_id', $user->id)->where('type', 'pulang')->whereDate('created_at', Carbon::today())->exists(),
            ]
        ];

        $recentModules = LearningModule::whereHas('teachingAssignment', function($q) use ($classId) {
            $q->where('class_room_id', $classId);
        })->with(['teachingAssignment.teacher.user', 'teachingAssignment.subject'])->latest()->take(5)->get();

        $recentAssignments = Assignment::whereHas('teachingAssignment', function($q) use ($classId) {
            $q->where('class_room_id', $classId);
        })->with(['teachingAssignment.subject'])->latest()->take(5)->get();

        // Gabungkan modul dan tugas sebagai "Pengumuman"
        $announcements = collect();
        foreach ($recentModules as $mod) {
            $announcements->push([
                'title' => 'Modul baru ' . ($mod->teachingAssignment->subject->name ?? '') . ' telah diunggah.',
                'time' => $mod->created_at,
                'color' => 'success',
            ]);
        }
        foreach ($recentAssignments as $task) {
            $announcements->push([
                'title' => 'Tugas baru ' . ($task->teachingAssignment->subject->name ?? '') . ' ditambahkan. Batas: ' . \Carbon\Carbon::parse($task->due_date)->format('d M'),
                'time' => $task->created_at,
                'color' => 'warning',
            ]);
        }

        $announcements = $announcements->sortByDesc('time')->take(5);

        // Cari Kelas Virtual Aktif
        $now = Carbon::now();
        $dayOfWeek = $now->dayOfWeekIso;
        $currentTime = $now->format('H:i:s');

        $activeLiveClass = \App\Models\Schedule::where('day_of_week', $dayOfWeek)
            ->where('start_time', '<=', $currentTime)
            ->where('end_time', '>=', $currentTime)
            ->whereNotNull('meeting_url')
            ->whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            })
            ->with(['teachingAssignment.subject', 'teachingAssignment.teacher.user'])
            ->first();

        return view('frontend.dashboard.index', compact('stats', 'recentModules', 'announcements', 'activeLiveClass'));
    }
}
