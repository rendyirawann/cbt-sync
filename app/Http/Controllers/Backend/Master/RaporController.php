<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
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

        // 2. Determine available classrooms based on role
        if ($user->hasRole('Superadmin')) {
            $classRooms = ClassRoom::all();
        } elseif ($user->hasRole('Guru')) {
            $teacher = $user->teacher;
            if (!$teacher) {
                return redirect()->route('dashboard')->with('error', 'Profil guru tidak ditemukan.');
            }
            // Fetch classrooms where the teacher teaches
            $classRoomIds = TeachingAssignment::where('teacher_id', $teacher->id)
                ->pluck('class_room_id')
                ->unique();
            $classRooms = ClassRoom::whereIn('id', $classRoomIds)->get();
        } else {
            abort(403, 'Akses ditolak.');
        }

        $selectedClassId = $request->get('class_room_id', $classRooms->first()?->id);
        $students = [];

        if ($selectedClassId) {
            // Get all students enrolled in this classroom
            $students = Student::whereHas('classStudents', function($q) use ($selectedClassId) {
                $q->where('class_room_id', $selectedClassId);
            })->with('user')->get();
        }

        // Read grading settings
        $gradeA = Setting::get('rapor_grade_a', 86);
        $gradeB = Setting::get('rapor_grade_b', 76);
        $gradeC = Setting::get('rapor_grade_c', 66);
        $gradeD = Setting::get('rapor_grade_d', 56);

        return view('backend.master.rapor.index', compact(
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
        } elseif ($user->hasRole('Guru')) {
            $teacher = $user->teacher;
            if (!$teacher) abort(403, 'Profil guru tidak ditemukan.');
            // Verify if student is in teacher's classrooms
            $teacherClassIds = TeachingAssignment::where('teacher_id', $teacher->id)
                ->pluck('class_room_id')
                ->toArray();
            
            $isAuthorized = Student::where('id', $id)
                ->whereHas('classStudents', function($q) use ($teacherClassIds) {
                    $q->whereIn('class_room_id', $teacherClassIds);
                })->exists();

            if (!$isAuthorized) {
                abort(403, 'Anda hanya dapat mengakses siswa di kelas Anda.');
            }
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
        } elseif ($user->hasRole('Guru')) {
            $teacher = $user->teacher;
            $teacherClassIds = TeachingAssignment::where('teacher_id', $teacher->id)->pluck('class_room_id')->toArray();
            $isAuthorized = Student::where('id', $id)->whereHas('classStudents', function($q) use ($teacherClassIds) {
                $q->whereIn('class_room_id', $teacherClassIds);
            })->exists();
            if (!$isAuthorized) abort(403, 'Akses ditolak.');
        }

        $student = Student::with(['user', 'school', 'classStudents.classRoom', 'classStudents.academicYear'])->findOrFail($id);
        $activeClassStudent = $student->classStudents->first();
        if (!$activeClassStudent || !$activeClassStudent->classRoom) {
            return redirect()->back()->with('error', 'Siswa belum terdaftar di kelas manapun.');
        }

        $classRoom = $activeClassStudent->classRoom;
        $academicYear = $activeClassStudent->academicYear;

        // Fetch rapor details & ranking
        $raporData = $this->calculateRaporDetails($student, $classRoom->id);

        return view('backend.master.rapor.print', compact('student', 'classRoom', 'academicYear', 'raporData'));
    }

    public function saveSettings(Request $request)
    {
        if (!auth()->user()->hasRole('Superadmin')) {
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
