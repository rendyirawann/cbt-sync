<?php

namespace App\Http\Controllers\Backend\Dashboard;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Models\School;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\ClassRoom;
use App\Models\TeachingAssignment;
use App\Models\LearningModule;
use App\Models\ClassStudent;

class DashboardAdminController extends Controller
{
    public function index()
    {
        $user = auth()->user();

        // Siswa tidak boleh berada di dashboard admin — arahkan ke portal mereka.
        if ($user->hasRole('Siswa')) {
            return redirect()->route('student.dashboard');
        }

        // 'Kepala Sekolah' ikut cabang ini. Sebelumnya peran itu tidak tertangkap
        // di mana pun, jatuh ke cabang terakhir, lalu RETURN LEBIH AWAL karena tidak
        // punya profil siswa — sehingga ringkasan CBT tidak pernah dihitung dan ia
        // melihat dashboard bergaya siswa berisi angka nol.
        if ($user->hasAnyRole(['Developer', 'Superadmin', 'superadmin', 'Admin', 'admin', 'Kepala Sekolah'])) {
            // Dibatasi per sekolah. Sebelumnya seluruh cacah ini memakai count()
            // tanpa batas apa pun, jadi Admin sekolah A melihat jumlah guru, siswa,
            // dan kelas SELURUH sekolah — angka yang bukan miliknya.
            $sid = \App\Support\SchoolScope::id();

            $stats = [
                'type' => 'admin',
                'schools' => $sid ? 1 : School::count(),
                'teachers' => Teacher::when($sid, fn ($q) => $q->whereHas('user', fn ($u) => $u->where('school_id', $sid)))->count(),
                'students' => Student::when($sid, fn ($q) => $q->where('school_id', $sid))->count(),
                'classes' => ClassRoom::when($sid, fn ($q) => $q->where('school_id', $sid))->count(),

                // Rombel yang BENAR-BENAR berisi siswa pada tahun ajaran aktif.
                //
                // Kartunya dulu berlabel "Rombel Aktif" tetapi angkanya
                // ClassRoom::count() — jumlah BARIS rombel, tanpa melihat ada
                // siswanya atau tidak. Di smamh4babalan itu menampilkan 13
                // padahal plotting siswanya nol, jadi angkanya terbaca sebagai
                // "13 rombel siap dipakai" padahal tidak satu pun berisi siswa.
                'classes_filled' => ClassRoom::when($sid, fn ($q) => $q->where('school_id', $sid))
                    ->whereHas('classStudents', fn ($q) => $q->whereHas('academicYear', fn ($a) => $a->where('is_active', true)))
                    ->count(),
            ];

            // Dulu kartu ini berisi "Penugasan Guru Terbaru" — 5 baris terakhir
            // tabel master teaching_assignments (guru/mapel/kelas). Itu warisan dari
            // lms-sync: tidak memuat satu pun informasi ujian, dan query-nya juga
            // tidak dibatasi per sekolah sehingga nama guru & kelas sekolah lain
            // ikut terlihat. Diganti dengan UJIAN TERBARU, yang memang inti
            // aplikasi ini, lengkap dengan status, jadwal, dan jumlah soal.
            $recentData = \App\Models\Exam::with([
                    'teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user',
                ])
                ->when($sid, fn ($q) => $q->whereHas('teachingAssignment.classRoom',
                    fn ($c) => $c->where('school_id', $sid)))
                ->whereIn('status', \App\Support\SiklusUjian::statusTerlihat($user))
                ->withCount(['questions', 'sessions'])
                ->latest()
                ->take(5)
                ->get();
        } elseif ($user->hasRole('Guru')) {
            if (!$user->teacher) {
                return view('backend.dashboard.index', [
                    'stats' => ['type' => 'guru'],
                    'recentData' => collect(),
                ])->with('error', 'Profil guru belum lengkap. Hubungi administrator.');
            }
            $teacherId = $user->teacher->id;
            $stats = [
                'type' => 'guru',
                'my_classes' => TeachingAssignment::where('teacher_id', $teacherId)->distinct('class_room_id')->count(),
                'my_subjects' => TeachingAssignment::where('teacher_id', $teacherId)->distinct('subject_id')->count(),
                'my_modules' => LearningModule::whereHas('teachingAssignment', function($q) use ($teacherId) {
                    $q->where('teacher_id', $teacherId);
                })->count(),
                'my_students' => ClassStudent::whereIn('class_room_id', function($q) use ($teacherId) {
                    $q->select('class_room_id')->from('teaching_assignments')->where('teacher_id', $teacherId);
                })->count(),
            ];

            // Kartu daftar untuk Guru juga diisi UJIAN yang ia ampu, bukan modul
            // LMS: aplikasi ini aplikasi ujian, dan modul tidak dipakai di sini.
            $recentData = \App\Models\Exam::whereHas('teachingAssignment', fn ($q) => $q->where('teacher_id', $teacherId))
                ->with(['teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user'])
                ->whereIn('status', \App\Support\SiklusUjian::statusTerlihat($user))
                ->withCount(['questions', 'sessions'])
                ->latest()->take(5)->get();
        } else {
            // User tanpa role yang dikenali (fallback aman).
            $student = $user->student;
            if (!$student) {
                return view('backend.dashboard.index', [
                    'stats' => ['type' => 'unknown'],
                    'recentData' => collect(),
                ]);
            }
            $classId = ClassStudent::where('student_id', $student->id)
                ->whereHas('academicYear', function($q) { $q->where('is_active', 1); })
                ->value('class_room_id');

            $stats = [
                'type' => 'siswa',
                'my_subjects' => TeachingAssignment::where('class_room_id', $classId)->count(),
                'new_modules' => LearningModule::whereHas('teachingAssignment', function($q) use ($classId) {
                    $q->where('class_room_id', $classId);
                })->count(),
            ];

            $recentData = LearningModule::whereHas('teachingAssignment', function($q) use ($classId) {
                $q->where('class_room_id', $classId);
            })->with(['teachingAssignment.teacher.user', 'teachingAssignment.subject'])->latest()->take(5)->get();
        }

        // Ringkasan khusus CBT — angka dari database, dibatasi ke sekolah
        // pengguna (dan untuk Guru, ke ujian yang ia ampu). Lihat RingkasanCbt.
        $cbt = null;
        if ($user->hasRole(['Developer', 'Superadmin', 'superadmin', 'Admin', 'admin', 'Kepala Sekolah', 'Guru'])) {
            $cbt = app(\App\Services\RingkasanCbt::class)
                ->untukPengelola($user, \App\Support\SchoolScope::id());
        }

        return view('backend.dashboard.index', compact('stats', 'recentData', 'cbt'));
    }
}