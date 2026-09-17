<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\Subject;
use App\Models\Setting;
use App\Models\TeachingAssignment;
use App\Models\AssignmentSubmission;
use App\Models\Assignment;
use Illuminate\Http\Request;

class RaporController extends Controller
{
    public function index(Request $request)
    {
        $user = auth()->user();

        // 1. If user is Siswa, show their own e-Rapor directly
        if ($user->hasRole('Siswa')) {
            $student = $user->student;
            if (!$student) {
                return redirect()->route('student.dashboard')->with('error', 'Profil siswa tidak ditemukan.');
            }
            return $this->showStudentRapor($student->id);
        }

        // 2. Rapor dibuka untuk Superadmin, Developer, dan Admin sekolah.
        //    Guru tetap ditolak: rapor memuat nilai seluruh mapel satu kelas,
        //    bukan hanya mapel yang ia ampu. Yang terlihat di menu dan yang
        //    bisa dibuka lewat URL selalu sama — lihat SiklusUjian::bolehRapor().
        if (! \App\Support\SiklusUjian::bolehRapor($user)) {
            abort(403, 'Akses ditolak. Raport Hasil Ujian hanya untuk Superadmin dan Admin.');
        }

        $classRooms = ClassRoom::all();

        // Tahun ajaran dipilih lebih dulu; daftar kelas lalu mengikuti tahun itu,
        // supaya kelas yang tidak punya anggota pada tahun tersebut tidak ikut
        // muncul di dropdown.
        $academicYears = AcademicYear::orderByDesc('name')->orderBy('semester')->get();

        $selectedYearId = $request->get('academic_year_id');
        if (!$selectedYearId || !$academicYears->contains('id', $selectedYearId)) {
            $selectedYearId = $academicYears->firstWhere('is_active', true)?->id
                ?? $academicYears->first()?->id;
        }

        $classRooms = $classRooms->filter(function ($kelas) use ($selectedYearId) {
            return $kelas->classStudents()->where('academic_year_id', $selectedYearId)->exists();
        })->values();

        // Kelas dari tahun lain (mis. tertinggal di URL) tidak dipakai begitu saja.
        $selectedClassId = $request->get('class_room_id');
        if (!$selectedClassId || !$classRooms->contains('id', $selectedClassId)) {
            $selectedClassId = $classRooms->first()?->id;
        }

        $students = [];

        if ($selectedClassId) {
            // Keanggotaan kelas DIBATASI tahun ajaran. Tanpa ini, siswa yang pernah
            // terdaftar di kelas yang sama pada tahun lain ikut terbawa ke daftar.
            $students = Student::whereHas('classStudents', function ($q) use ($selectedClassId, $selectedYearId) {
                $q->where('class_room_id', $selectedClassId)
                  ->when($selectedYearId, fn ($x) => $x->where('academic_year_id', $selectedYearId));
            })->with('user')->get();
        }

        // Read grading settings
        $gradeA = Setting::get('rapor_grade_a', 86);
        $gradeB = Setting::get('rapor_grade_b', 76);
        $gradeC = Setting::get('rapor_grade_c', 66);
        $gradeD = Setting::get('rapor_grade_d', 56);

        // Nilai kop rapor untuk formulir pengaturan (mentah, apa adanya dari
        // tabel settings — bukan hasil kopRapor() yang sudah diberi cadangan).
        $kopTeks = (string) Setting::get('rapor_kop', '');
        $kopLogo = (string) Setting::get('rapor_logo', '');
        $kopKepsek = (string) Setting::get('rapor_kepsek', '');

        return view('backend.master.rapor.index', compact(
            'kopTeks',
            'kopLogo',
            'kopKepsek',
            'academicYears',
            'selectedYearId',
            'classRooms',
            'selectedClassId',
            'students',
            'gradeA',
            'gradeB',
            'gradeC',
            'gradeD'
        ));
    }

    public function show($id)
    {
        $user = auth()->user();

        // Access controls
        if ($user->hasRole('Siswa')) {
            $student = $user->student;
            if (!$student || $student->id !== $id) {
                abort(403, 'Anda hanya dapat mengakses e-Rapor Anda sendiri.');
            }
        } elseif (! \App\Support\SiklusUjian::bolehRapor($user)) {
            // Dulu di sini hanya Guru yang diperiksa, sehingga peran lain
            // (Admin, Kepala Sekolah) lolos TANPA pemeriksaan apa pun dan bisa
            // membuka rapor siswa mana saja lewat URL. Sekarang: selain siswa
            // yang membuka miliknya sendiri, hanya Superadmin yang boleh.
            abort(403, 'Akses ditolak. Raport Hasil Ujian hanya untuk Superadmin dan Admin.');
        }

        return $this->showStudentRapor($id);
    }

    public function generate($id)
    {
        $user = auth()->user();

        // Access controls (Same as show)
        if ($user->hasRole('Siswa')) {
            $student = $user->student;
            if (!$student || $student->id !== $id) {
                abort(403, 'Akses ditolak.');
            }
        } elseif (! \App\Support\SiklusUjian::bolehRapor($user)) {
            // Lubang yang sama seperti di show(): mencetak rapor pun dulu
            // terbuka bagi peran yang tidak diperiksa.
            abort(403, 'Akses ditolak. Raport Hasil Ujian hanya untuk Superadmin dan Admin.');
        }

        $student = Student::with(['user', 'school', 'classStudents.classRoom', 'classStudents.academicYear'])->findOrFail($id);
        $activeClassStudent = $student->classStudents->first();
        if (!$activeClassStudent || !$activeClassStudent->classRoom) {
            return redirect()->back()->with('error', 'Siswa belum terdaftar di kelas manapun.');
        }

        $classRoom = $activeClassStudent->classRoom;
        $academicYear = $activeClassStudent->academicYear;
        // Wali kelas & sekolah dipakai kop tanda tangan; dimuat sekali di sini.
        $classRoom->loadMissing(['school', 'homeroomTeacher.user']);

        // Fetch rapor details & ranking
        $raporData = $this->calculateRaporDetails($student, $classRoom->id);
        $kop = $this->kopRapor($classRoom, $student);

        return view('backend.master.rapor.print', compact('student', 'classRoom', 'academicYear', 'raporData', 'kop'));
    }

    public function saveSettings(Request $request)
    {
        if (! \App\Support\SiklusUjian::pengawas()) {
            abort(403, 'Hanya Superadmin yang dapat mengubah pengaturan rapor.');
        }

        $request->validate([
            'grade_a' => 'required|numeric|min:0|max:100',
            'grade_b' => 'required|numeric|min:0|max:100',
            'grade_c' => 'required|numeric|min:0|max:100',
            'grade_d' => 'required|numeric|min:0|max:100',
        ]);

        Setting::set('rapor_grade_a', $request->grade_a);
        Setting::set('rapor_grade_b', $request->grade_b);
        Setting::set('rapor_grade_c', $request->grade_c);
        Setting::set('rapor_grade_d', $request->grade_d);

        return redirect()->back()->with('success', 'Ketentuan predikat nilai e-Rapor berhasil diperbarui!');
    }

    /**
     * Kop rapor: teks judul atas, logo, nama kepala sekolah, dan nama wali
     * kelas. Semuanya bisa diatur sekolah; yang tidak diisi jatuh ke cadangan
     * yang masuk akal, bukan ke teks yang dipatok mati.
     */
    private function kopRapor($classRoom = null, $student = null): array
    {
        // Logo rapor boleh berbeda dari logo aplikasi; kalau tidak diisi,
        // dipakai logo sekolah yang sudah ada di Pengaturan.
        $berkas = trim((string) Setting::get('rapor_logo', ''))
            ?: trim((string) Setting::get('site_logo', ''));

        $sekolah = $classRoom?->school?->name ?? $student?->school?->name ?? '';

        return [
            'teks' => trim((string) Setting::get('rapor_kop', '')) ?: mb_strtoupper($sekolah),
            'logo' => $berkas ? asset('assets/media/logos/' . $berkas) : null,
            'kepsek' => trim((string) Setting::get('rapor_kepsek', '')),
            // Wali kelas melekat pada kelas (data master), bukan pada mapet.
            'wali' => $classRoom?->homeroomTeacher?->user?->name ?? '',
        ];
    }

    /**
     * Simpan kop rapor. Boleh oleh Admin dan Superadmin — berbeda dari ambang
     * predikat nilai yang tetap milik Superadmin.
     */
    public function saveKop(Request $request)
    {
        if (! \App\Support\SiklusUjian::bolehRapor()) {
            abort(403, 'Akses ditolak. Raport Hasil Ujian hanya untuk Superadmin dan Admin.');
        }

        $request->validate([
            'rapor_kop' => 'nullable|string|max:300',
            'rapor_kepsek' => 'nullable|string|max:150',
            'rapor_logo' => 'nullable|image|mimes:png,jpg,jpeg,webp|max:4096',
        ], [
            'rapor_logo.image' => 'Berkas logo harus berupa gambar.',
            'rapor_logo.mimes' => 'Format logo harus PNG, JPG, JPEG, atau WEBP.',
            'rapor_logo.max' => 'Ukuran logo maksimal 4 MB.',
        ], [
            'rapor_kop' => 'Teks kop rapor',
            'rapor_kepsek' => 'Nama kepala sekolah',
            'rapor_logo' => 'Logo rapor',
        ]);

        Setting::set('rapor_kop', trim((string) $request->input('rapor_kop')));
        Setting::set('rapor_kepsek', trim((string) $request->input('rapor_kepsek')));

        if ($request->boolean('hapus_logo')) {
            Setting::set('rapor_logo', '');
        }

        if ($request->hasFile('rapor_logo')) {
            $file = $request->file('rapor_logo');
            $nama = 'rapor-logo-' . time() . '.' . strtolower($file->getClientOriginalExtension());
            $file->move(public_path('assets/media/logos'), $nama);
            Setting::set('rapor_logo', $nama);
        }

        return redirect()->back()->with('success', 'Kop Raport Hasil Ujian berhasil disimpan.');
    }

    /**
     * Internal helper to show specific student Rapor details.
     */
    private function showStudentRapor($studentId)
    {
        $student = Student::with(['user', 'school', 'classStudents.classRoom', 'classStudents.academicYear'])->findOrFail($studentId);
        $activeClassStudent = $student->classStudents->first();
        if (!$activeClassStudent || !$activeClassStudent->classRoom) {
            return view('backend.master.rapor.show_empty', compact('student'));
        }

        $classRoom = $activeClassStudent->classRoom;
        $academicYear = $activeClassStudent->academicYear;

        // Calculate Rapor details (subjects, averages, rankings, grades)
        $raporData = $this->calculateRaporDetails($student, $classRoom->id);

        return view('backend.master.rapor.show', compact('student', 'classRoom', 'academicYear', 'raporData'));
    }

    /**
     * Compute subject list, assignment submissions average, overall score, letter grade, and ranking.
     *
     * Dioptimasi: seluruh data nilai satu kelas diambil dalam ~4 query
     * (teaching assignments, assignments, daftar siswa, submissions) lalu
     * dihitung di memori — menghilangkan N+1 (sebelumnya bisa ratusan query).
     */
    private function calculateRaporDetails(Student $student, $classRoomId)
    {
        $classData = $this->computeClassData($classRoomId);
        $teachingAssignments = $classData['teachingAssignments'];
        $scoresByStudentTa = $classData['scoresByStudentTa'];
        $examScoresByStudentTa = $classData['examScoresByStudentTa'] ?? [];

        $subjectsData = [];
        $totalOverallScore = 0;
        $subjectsCount = 0;

        foreach ($teachingAssignments as $ta) {
            $assignmentsCount = $classData['assignmentCountByTa'][$ta->id] ?? 0;
            $scores = $scoresByStudentTa[$student->id][$ta->id] ?? [];

            $completedCount = count($scores);
            $graded = array_filter($scores, fn($v) => $v !== null);
            $avgScore = count($graded) > 0 ? array_sum($graded) / count($graded) : 0;

            // Nilai ujian CBT (terpisah, agar tetap kelihatan komponennya).
            $exScores = $examScoresByStudentTa[$student->id][$ta->id] ?? [];
            $examAvg = count($exScores) > 0 ? array_sum($exScores) / count($exScores) : null;

            $subjectsData[] = [
                'subject_name' => $ta->subject->name ?? '-',
                'teacher_name' => $ta->teacher?->user?->name ?? '-',
                'average_score' => round($avgScore, 1),
                'exam_average' => $examAvg !== null ? round($examAvg, 1) : null,
                'exam_count' => count($exScores),
                'letter_grade' => $this->getLetterGrade($avgScore),
                'total_assignments' => $assignmentsCount,
                'completed_assignments' => $completedCount,
            ];

            $totalOverallScore += $avgScore;
            $subjectsCount++;
        }

        $overallAverage = $subjectsCount > 0 ? ($totalOverallScore / $subjectsCount) : 0;
        $overallGrade = $this->getLetterGrade($overallAverage);

        // Ranking dihitung dari data kelas yang sama (tanpa query ulang).
        $classroomRanking = $this->buildRanking($classData);
        $studentRank = $classroomRanking[$student->id] ?? '-';
        $totalStudents = count($classroomRanking);

        return [
            'subjects' => $subjectsData,
            'overall_average' => round($overallAverage, 1),
            'overall_grade' => $overallGrade,
            'rank' => $studentRank,
            'total_students' => $totalStudents,
        ];
    }

    /**
     * Map numerical score to letter grade based on admin settings.
     */
    private function getLetterGrade($score)
    {
        $t = $this->gradeThresholds();

        if ($score >= $t['a']) return 'A';
        if ($score >= $t['b']) return 'B';
        if ($score >= $t['c']) return 'C';
        if ($score >= $t['d']) return 'D';
        return 'E';
    }

    /**
     * Ambang batas predikat (dibaca sekali per-request, lalu dimemoisasi).
     */
    private ?array $gradeThresholds = null;

    private function gradeThresholds(): array
    {
        if ($this->gradeThresholds === null) {
            $this->gradeThresholds = [
                'a' => (float) Setting::get('rapor_grade_a', 86),
                'b' => (float) Setting::get('rapor_grade_b', 76),
                'c' => (float) Setting::get('rapor_grade_c', 66),
                'd' => (float) Setting::get('rapor_grade_d', 56),
            ];
        }

        return $this->gradeThresholds;
    }

    /**
     * Ambil seluruh data nilai satu kelas secara massal (bulk) untuk
     * menghindari query per-siswa/per-mapel.
     *
     * @return array{
     *   teachingAssignments: \Illuminate\Support\Collection,
     *   assignmentCountByTa: array<string,int>,
     *   studentIds: \Illuminate\Support\Collection,
     *   scoresByStudentTa: array<string,array<string,array>>
     * }
     */
    private function computeClassData($classRoomId): array
    {
        $teachingAssignments = TeachingAssignment::where('class_room_id', $classRoomId)
            ->with(['subject', 'teacher.user'])
            ->get();

        $assignments = Assignment::whereIn('teaching_assignment_id', $teachingAssignments->pluck('id'))
            ->get(['id', 'teaching_assignment_id']);

        $taByAssignment = [];      // assignment_id => teaching_assignment_id
        $assignmentCountByTa = []; // teaching_assignment_id => jumlah tugas
        foreach ($assignments as $a) {
            $taByAssignment[$a->id] = $a->teaching_assignment_id;
            $assignmentCountByTa[$a->teaching_assignment_id]
                = ($assignmentCountByTa[$a->teaching_assignment_id] ?? 0) + 1;
        }

        $studentIds = Student::whereHas('classStudents', function ($q) use ($classRoomId) {
            $q->where('class_room_id', $classRoomId);
        })->pluck('id');

        // [student_id][teaching_assignment_id] => [score, score, ...]
        $scoresByStudentTa = [];
        if ($assignments->isNotEmpty() && $studentIds->isNotEmpty()) {
            $submissions = AssignmentSubmission::whereIn('student_id', $studentIds)
                ->whereIn('assignment_id', $assignments->pluck('id'))
                ->get(['student_id', 'assignment_id', 'score']);

            foreach ($submissions as $sub) {
                $taId = $taByAssignment[$sub->assignment_id] ?? null;
                if ($taId === null) continue;
                $scoresByStudentTa[$sub->student_id][$taId][] = $sub->score;
            }
        }

        // ==== Nilai Ujian CBT: digabung ke rata-rata + disimpan terpisah ====
        $examScoresByStudentTa = [];
        $exams = \App\Models\Exam::whereIn('teaching_assignment_id', $teachingAssignments->pluck('id'))
            ->get(['id', 'teaching_assignment_id']);
        if ($exams->isNotEmpty() && $studentIds->isNotEmpty()) {
            $taByExam = $exams->pluck('teaching_assignment_id', 'id');     // exam_id => ta_id
            $sessions = \App\Models\ExamSession::whereIn('exam_id', $exams->pluck('id'))->get(['id', 'exam_id']);
            $examBySession = $sessions->pluck('exam_id', 'id');            // session_id => exam_id

            if ($sessions->isNotEmpty()) {
                $attempts = \App\Models\ExamAttempt::whereIn('student_id', $studentIds)
                    ->whereIn('exam_session_id', $sessions->pluck('id'))
                    ->where('status', 'graded')
                    ->whereNotNull('final_score')
                    ->get(['student_id', 'exam_session_id', 'final_score']);

                foreach ($attempts as $att) {
                    $examId = $examBySession[$att->exam_session_id] ?? null;
                    $taId = $examId ? ($taByExam[$examId] ?? null) : null;
                    if ($taId === null) continue;
                    $score = (float) $att->final_score;
                    $scoresByStudentTa[$att->student_id][$taId][] = $score;       // ikut rata-rata mapel
                    $examScoresByStudentTa[$att->student_id][$taId][] = $score;   // tetap terlihat terpisah
                }
            }
        }

        return compact('teachingAssignments', 'assignmentCountByTa', 'studentIds', 'scoresByStudentTa', 'examScoresByStudentTa');
    }

    /**
     * Hitung ranking 1-indexed seluruh siswa dari data kelas yang sudah dimuat.
     */
    private function buildRanking(array $classData): array
    {
        $teachingAssignments = $classData['teachingAssignments'];
        $scoresByStudentTa = $classData['scoresByStudentTa'];
        $totalSubjects = $teachingAssignments->count();

        $averages = [];
        foreach ($classData['studentIds'] as $studentId) {
            $studentTotal = 0;
            foreach ($teachingAssignments as $ta) {
                $scores = $scoresByStudentTa[$studentId][$ta->id] ?? [];
                $graded = array_filter($scores, fn($v) => $v !== null);
                $studentTotal += count($graded) > 0 ? array_sum($graded) / count($graded) : 0;
            }
            $averages[$studentId] = $totalSubjects > 0 ? $studentTotal / $totalSubjects : 0;
        }

        arsort($averages);

        $rankings = [];
        $rank = 1;
        foreach ($averages as $studentId => $avg) {
            $rankings[$studentId] = $rank++;
        }

        return $rankings;
    }
}
