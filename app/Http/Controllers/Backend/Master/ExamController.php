<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Exam;
use App\Models\TeachingAssignment;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class ExamController extends Controller
{
    public function index()
    {
        $user = auth()->user();
        $query = Exam::with(['teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user'])
            ->withCount(['questions', 'sessions']);

        $sid = \App\Support\SchoolScope::id();

        if ($user->hasRole('Guru')) {
            if (!$user->teacher) {
                return redirect()->route('dashboard')->with('error', 'Profil guru tidak ditemukan.');
            }
            $teacherId = $user->teacher->id;
            $query->whereHas('teachingAssignment', fn ($q) => $q->where('teacher_id', $teacherId));
            $assignments = TeachingAssignment::with(['subject', 'classRoom'])
                ->where('teacher_id', $teacherId)->get();
        } else {
            // Kepala Sekolah / user yang discope → hanya ujian di sekolahnya (via kelas).
            if ($sid) {
                $query->whereHas('teachingAssignment.classRoom', fn ($c) => $c->where('school_id', $sid));
            }
            $assignments = TeachingAssignment::with(['subject', 'classRoom', 'teacher.user'])
                ->when($sid, fn ($q) => $q->whereHas('classRoom', fn ($c) => $c->where('school_id', $sid)))
                ->get();
        }

        // Tutup dulu ujian yang sudah tuntas, lalu saring menurut peran: ujian
        // berstatus SELESAI hilang dari Admin/Guru dan hanya terlihat oleh
        // Superadmin/Developer. Pemeriksaan status dilakukan SETELAH penutupan
        // supaya ujian yang baru tuntas langsung ikut tersembunyi.
        \App\Support\SiklusUjian::segarkan(
            (clone $query)->where('status', \App\Support\SiklusUjian::TERSEDIA)
                ->with(['sessions.students', 'sessions.attempts'])->get()
        );

        $exams = $query->whereIn('status', \App\Support\SiklusUjian::statusTerlihat($user))
            ->latest()->get();

        return view('backend.master.exams.index', compact('exams', 'assignments'));
    }

    public function store(Request $request)
    {
        $request->validate([
            'teaching_assignment_id' => 'required|uuid|exists:teaching_assignments,id',
            'title' => 'required|string|max:255',
            'type' => 'required|in:mixed,mc,essay',
            'points_mode' => 'required|in:manual,auto',
            'wrong_penalty' => 'nullable|numeric|min:0',
            'pass_score' => 'nullable|numeric|min:0|max:100',
        ]);

        $exam = Exam::create([
            'teaching_assignment_id' => $request->teaching_assignment_id,
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'points_mode' => $request->points_mode,
            'wrong_penalty' => $request->wrong_penalty ?: 0,
            'normalize' => $request->has('normalize'),
            'pass_score' => $request->pass_score ?: 75,
            'status' => 'draft',
        ]);

        return redirect()->route('exams.show', $exam->id)
            ->with('success', 'Ujian berhasil dibuat. Silakan tambahkan soal.');
    }

    public function show($id)
    {
        $exam = Exam::with([
            'teachingAssignment.subject', 'teachingAssignment.classRoom', 'teachingAssignment.teacher.user',
            'questions.options',
            'sessions.classRoom', 'sessions.attempts', 'sessions.students',
        ])->findOrFail($id);

        $this->authorizeExam($exam);

        // Ujian yang sudah tuntas ditutup di sini juga, supaya membuka halamannya
        // langsung memindahkan statusnya (bukan menunggu daftar ujian dibuka).
        \App\Support\SiklusUjian::segarkan($exam);

        // Status SELESAI hanya boleh dibuka Superadmin/Developer.
        abort_unless(\App\Support\SiklusUjian::bolehLihat($exam), 404);

        // Sesi ujian mengikuti kelas ujian (dari penugasan). Peserta = siswa kelas tsb;
        // "Pilih Siswa" hanya untuk memilih SEBAGIAN siswa kelas yang sama.
        $examClass = $exam->teachingAssignment?->classRoom;
        // Peserta = siswa kelas itu PADA TAHUN AJARAN ujian ini.
        $tahunUjian = $exam->teachingAssignment?->academic_year_id;
        $classStudentIds = $examClass
            ? \App\Models\ClassStudent::where('class_room_id', $examClass->id)
                ->when($tahunUjian, fn ($q) => $q->where('academic_year_id', $tahunUjian))
                ->pluck('student_id')
            : collect();
        $students = \App\Models\Student::with('user')->whereIn('id', $classStudentIds)->get()
            ->sortBy(fn ($s) => $s->user->name ?? '')->values();

        // Penugasan untuk mengganti kelas/mapel ujian (Guru: hanya miliknya).
        $user = auth()->user();
        $taQuery = TeachingAssignment::with(['subject', 'classRoom', 'teacher.user']);
        if ($user->hasRole('Guru')) {
            $taQuery->where('teacher_id', $user->teacher?->id);
        }
        $assignments = $taQuery->get();

        // Bank Soal Bersama untuk mapel ujian ini (lintas sekolah) — untuk fitur "Tarik dari Bank Soal".
        $subjectId = $exam->teachingAssignment?->subject_id;
        // Soal bank hanya ditawarkan bila MAPEL dan TINGKAT-nya sama dengan ujian
        // ini — lintas sekolah tetap boleh (sekolah A boleh memakai soal sekolah B),
        // dan soal yang ditarik disalin menjadi milik ujian ini.
        $bankLevel = \App\Support\BankSoal::tingkat($exam);
        // Soal ujian yang masih berjalan di sekolah lain TIDAK ditawarkan di sini
        // (lihat QuestionBank::scopeTerlihatOleh). Sekolah pembanding adalah
        // sekolah ujian ini, bukan sekolah akun yang membuka halaman.
        $sekolahUjian = $exam->teachingAssignment?->classRoom?->school_id;
        $bankQuestions = $subjectId
            ? \App\Models\QuestionBank::with(['options', 'subject', 'school', 'sourceSchool'])
                ->terlihatOleh($sekolahUjian)
                ->where('subject_id', $subjectId)
                ->when($bankLevel, fn ($q) => $q->where('level', $bankLevel))
                ->when(!$exam->hasMc(), fn ($q) => $q->where('type', 'essay'))
                ->when(!$exam->hasEssay(), fn ($q) => $q->where('type', 'mc'))
                ->latest()->limit(300)->get()
            : collect();

        // Calon peserta SUSULAN: siswa yang sampai sekarang belum tercatat mengikuti
        // ujian ini di sesi mana pun (tidak punya attempt sama sekali). Termasuk siswa
        // yang dulu dilampirkan manual ke sesi lain tapi tetap tidak hadir.
        $sudahUjian = $exam->sessions->flatMap->attempts->pluck('student_id')->unique();
        $belumUjian = $students
            ->concat($exam->sessions->flatMap->students)
            ->unique('id')
            ->reject(fn ($s) => $sudahUjian->contains($s->id))
            ->sortBy(fn ($s) => $s->user->name ?? '')
            ->values();

        // Gelombang yang benar-benar dipakai peserta ujian ini — jadi tombol
        // Daftar Hadir hanya muncul untuk gelombang yang ada isinya.
        $pesertaUjian = $this->pesertaUjian($exam);
        $gelombangUjian = \App\Models\Wave::whereIn('id', $pesertaUjian->pluck('wave_id')->filter()->unique())
            ->terurut()->get()
            ->map(function ($w) use ($pesertaUjian) {
                $w->jumlah_peserta = $pesertaUjian->where('wave_id', $w->id)->count();
                return $w;
            });
        $pesertaTanpaGelombang = $pesertaUjian->whereNull('wave_id')->count();

        return view('backend.master.exams.show', compact(
            'exam', 'examClass', 'students', 'assignments', 'bankQuestions', 'belumUjian',
            'gelombangUjian', 'pesertaTanpaGelombang'
        ));
    }


    /**
     * DAFTAR HADIR PESERTA (PDF) untuk satu ujian, per GELOMBANG.
     *
     * Kolom PUKUL diambil dari jam gelombang (Master Gelombang) karena jadwal
     * ujian kini hanya rentang tanggal. Peserta = seluruh siswa yang berhak ikut
     * ujian ini (gabungan semua jadwal), disaring menurut gelombangnya.
     *
     * GET dan tanpa efek samping: hanya membaca lalu mencetak.
     */
    public function attendance(Request $request, $id)
    {
        $exam = Exam::with([
            'teachingAssignment.subject', 'teachingAssignment.classRoom.school',
            'sessions.students.user', 'sessions.students.wave',
        ])->findOrFail($id);

        $this->authorizeExam($exam);

        $gelombang = \App\Models\Wave::findOrFail($request->input('wave_id'));

        $peserta = $this->pesertaUjian($exam)
            ->where('wave_id', $gelombang->id)
            ->sortBy(fn ($s) => $s->user->username ?? '')
            ->values();

        $tahun = $exam->teachingAssignment?->academicYear
            ?? \App\Models\AcademicYear::where('is_active', 1)->first();

        $jadwal = $exam->sessions->where('is_makeup', false)->first() ?? $exam->sessions->first();
        $rentang = $jadwal
            ? \Carbon\Carbon::parse($jadwal->starts_at)->translatedFormat('d M Y')
                . ' – ' . \Carbon\Carbon::parse($jadwal->ends_at)->translatedFormat('d M Y')
            : '';

        $html = view('backend.master.exams.attendance', [
            'exam' => $exam,
            'gelombang' => $gelombang,
            'peserta' => $peserta,
            'tahun' => $tahun,
            'rentangTanggal' => $rentang,
            'logo' => public_path('assets/media/logos/tut-wuri-handayani.png'),
        ])->render();

        $options = new \Dompdf\Options();
        $options->set('chroot', public_path());
        $options->set('isRemoteEnabled', false);
        $options->set('defaultFont', 'Helvetica');
        $options->set('dpi', 96);

        $dompdf = new \Dompdf\Dompdf($options);
        $dompdf->loadHtml($html, 'UTF-8');
        $dompdf->setPaper('A4', 'portrait');
        $dompdf->render();

        $nama = 'Daftar-Hadir-' . \Illuminate\Support\Str::slug($exam->title)
            . '-' . \Illuminate\Support\Str::slug($gelombang->name) . '.pdf';

        return response($dompdf->output(), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'attachment; filename="' . $nama . '"',
        ]);
    }

    /**
     * Semua siswa yang berhak mengikuti ujian ini: peserta yang dilampirkan
     * manual pada jadwal mana pun, ditambah anggota kelas untuk jadwal yang
     * memakai mode kelas. Dipakai bersama oleh Daftar Hadir dan penghitung
     * gelombang di halaman Hasil.
     */
    private function pesertaUjian(Exam $exam)
    {
        $manual = $exam->sessions->flatMap->students;

        $kelasIds = $exam->sessions->pluck('class_room_id')->filter()->unique();
        $tahunId = $exam->teachingAssignment?->academic_year_id;
        $dariKelas = $kelasIds->isEmpty() ? collect() : \App\Models\Student::with(['user', 'wave'])
            ->whereIn('id', \App\Models\ClassStudent::whereIn('class_room_id', $kelasIds)
                ->when($tahunId, fn ($q) => $q->where('academic_year_id', $tahunId))
                ->pluck('student_id'))
            ->get();

        return $manual->concat($dariKelas)->unique('id')->values();
    }

    /**
     * Pengaturan pemilihan soal untuk ujian ini.
     *
     *  all    — semua soal diberikan ke setiap siswa.
     *  manual — hanya soal yang dicentang guru (questions.is_active).
     *  auto   — tiap siswa menerima sejumlah soal ACAK dari kolam yang aktif,
     *           jadi paket antar siswa berbeda. Penilaian mengikuti paket itu
     *           lewat CbtScoringService::paketSoal().
     *
     * Soal tidak pernah dihapus di sini, hanya ditandai aktif/tidak.
     */
    public function updateQuestionSelection(Request $request, $id)
    {
        $exam = Exam::with('questions')->findOrFail($id);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return back()->with('error', 'Pengaturan soal tidak bisa diubah karena sudah ada siswa yang memulai ujian.');
        }

        $data = $request->validate([
            'question_selection' => 'required|in:all,manual,auto',
            'active_question_count' => 'nullable|integer|min:1',
            'active' => 'nullable|array',
        ], [
            'question_selection.required' => 'Cara pemilihan soal wajib dipilih',
            'active_question_count.min' => 'Jumlah soal minimal 1',
        ]);

        $mode = $data['question_selection'];
        $dicentang = collect($request->input('active', []))->map(fn ($v) => (string) $v)->all();

        // Mode manual: yang dicentang jadi aktif, sisanya nonaktif. Mode lain:
        // semua soal dikembalikan aktif agar kolamnya utuh.
        if ($mode === 'manual') {
            if (empty($dicentang)) {
                return back()->with('error', 'Pilih minimal satu soal untuk diujikan.');
            }
            foreach ($exam->questions as $q) {
                $q->update(['is_active' => in_array((string) $q->id, $dicentang, true)]);
            }
        } else {
            $exam->questions()->update(['is_active' => true]);
        }

        $aktif = $mode === 'manual' ? count($dicentang) : $exam->questions->count();
        $jumlah = $mode === 'auto' ? (int) ($data['active_question_count'] ?? 0) : null;

        if ($mode === 'auto') {
            if (!$jumlah) {
                return back()->with('error', 'Isi jumlah soal yang diberikan ke tiap siswa.');
            }
            if ($jumlah > $aktif) {
                return back()->with('error', "Jumlah soal ($jumlah) melebihi soal tersedia ($aktif).");
            }
        }

        $exam->update(['question_selection' => $mode, 'active_question_count' => $jumlah]);

        return back()->with('success', 'Pengaturan pemilihan soal disimpan.');
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::with('sessions.students')->findOrFail($id);
        $this->authorizeExam($exam);

        $request->validate([
            'title' => 'required|string|max:255',
            'type' => 'required|in:mixed,mc,essay',
            'points_mode' => 'required|in:manual,auto',
            'wrong_penalty' => 'nullable|numeric|min:0',
            'pass_score' => 'nullable|numeric|min:0|max:100',
            'teaching_assignment_id' => 'nullable|uuid|exists:teaching_assignments,id',
        ]);

        $data = [
            'title' => $request->title,
            'description' => $request->description,
            'type' => $request->type,
            'points_mode' => $request->points_mode,
            'wrong_penalty' => $request->wrong_penalty ?: 0,
            'normalize' => $request->has('normalize'),
            'pass_score' => $request->pass_score ?: 75,
        ];

        // Ganti kategori ujian: soal yang tak lagi sesuai tipe baru ikut terhapus
        // (mis. jadi "PG saja" → soal essay dihapus). Hanya selama belum ada yang memulai.
        if (!$exam->hasStartedAttempts() && $request->type !== $exam->type) {
            $this->pruneQuestionsForType($exam, $request->type);
        }

        // Kelas/mapel (penugasan) hanya boleh diganti selama belum ada yang memulai.
        if (!$exam->hasStartedAttempts() && $request->filled('teaching_assignment_id')
            && $request->teaching_assignment_id !== $exam->teaching_assignment_id) {
            $ta = TeachingAssignment::find($request->teaching_assignment_id);
            $user = auth()->user();
            if ($ta && $user->hasRole('Guru') && $ta->teacher_id !== $user->teacher?->id) {
                abort(403, 'Anda hanya dapat memilih penugasan milik Anda.');
            }
            if ($ta) {
                $oldClass = $exam->teachingAssignment?->class_room_id;
                $data['teaching_assignment_id'] = $ta->id;
                $this->moveSessionsToClass($exam, $oldClass, $ta->class_room_id);
            }
        }

        $exam->update($data);

        return redirect()->back()->with('success', 'Pengaturan ujian berhasil diperbarui.');
    }

    /**
     * Hapus soal yang tidak sesuai kategori ujian yang baru dipilih
     * (mixed→mc: hapus essay; mixed/mc→essay: hapus PG), termasuk file gambarnya.
     */
    private function pruneQuestionsForType(Exam $exam, string $newType): void
    {
        $remove = match ($newType) {
            'mc' => 'essay',
            'essay' => 'mc',
            default => null, // 'mixed' → simpan semua
        };
        if (!$remove) {
            return;
        }

        foreach ($exam->questions()->where('type', $remove)->with('options')->get() as $q) {
            if ($q->image_path) {
                Storage::disk('public')->delete($q->image_path);
            }
            foreach ($q->options as $opt) {
                if ($opt->image_path) {
                    Storage::disk('public')->delete($opt->image_path);
                }
            }
            $q->delete(); // opsi ikut terhapus (cascade)
        }
    }

    /** Saat kelas ujian berpindah, sesi ikut menyesuaikan kelas barunya. */
    private function moveSessionsToClass(Exam $exam, ?string $oldClass, ?string $newClass): void
    {
        if ($oldClass === $newClass || !$newClass) {
            return;
        }
        $tahunUjian = $exam->teachingAssignment?->academic_year_id;
        $validStudentIds = \App\Models\ClassStudent::where('class_room_id', $newClass)
            ->when($tahunUjian, fn ($q) => $q->where('academic_year_id', $tahunUjian))
            ->pluck('student_id')->all();
        foreach ($exam->sessions as $sess) {
            if ($sess->class_room_id) {
                // Sesi mode "Satu Kelas" → pindah ke kelas baru.
                $sess->update(['class_room_id' => $newClass]);
            } else {
                // Sesi mode "Pilih Siswa" → sisakan hanya siswa yang ada di kelas baru.
                $keep = array_values(array_intersect($sess->students->pluck('id')->all(), $validStudentIds));
                $sess->students()->sync($keep);
            }
        }
    }

    public function destroy($id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeExam($exam);

        if ($exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Ujian tidak bisa dihapus karena sudah ada siswa yang memulai/mengerjakan.');
        }

        $exam->delete();

        return redirect()->route('exams.index')->with('success', 'Ujian berhasil dihapus.');
    }

    public function publish($id)
    {
        $exam = Exam::with('questions')->findOrFail($id);
        $this->authorizeExam($exam);

        // Terbit/tarik-draft hanya berlaku pada dua status awal; ujian yang sudah
        // Selesai/History dipindahkan lewat aksi arsip di bawah.
        if (! in_array($exam->status, [\App\Support\SiklusUjian::DRAFT, \App\Support\SiklusUjian::TERSEDIA], true)) {
            return redirect()->back()->with('error',
                'Ujian berstatus ' . \App\Support\SiklusUjian::labelStatus($exam->status)
                . ' tidak bisa diterbitkan/ditarik. Gunakan aksi arsip (khusus Superadmin & Developer).');
        }

        if ($exam->status === 'draft' && $exam->questions->count() === 0) {
            return redirect()->back()->with('error', 'Tidak bisa menerbitkan ujian tanpa soal.');
        }

        // Sudah ada peserta yang memulai → ujian terkunci, tidak boleh ditarik ke draft.
        if ($exam->status === 'published' && $exam->hasStartedAttempts()) {
            return redirect()->back()->with('error', 'Ujian tidak bisa ditarik ke draft karena sudah ada siswa yang memulai ujian.');
        }

        $exam->update(['status' => $exam->status === 'draft' ? 'published' : 'draft']);

        return redirect()->back()->with('success',
            $exam->status === 'published' ? 'Ujian diterbitkan.' : 'Ujian dikembalikan ke draft.');
    }

    /** Guru hanya boleh mengelola ujian miliknya. */
    /**
     * Perpindahan status arsip, KHUSUS Superadmin & Developer:
     *
     *   finished  ujian Available ditutup manual → dipakai bila ada peserta yang
     *             tidak akan pernah mengerjakan sehingga penutupan otomatis
     *             (yang mensyaratkan semua peserta selesai) tak pernah terjadi.
     *   history   ujian Selesai diarsipkan → muncul kembali di Admin & Guru,
     *             tetapi tanpa tab Hasil dan Jadwal.
     *   available ujian Selesai/History dibuka lagi → dipakai bila masih ada
     *             siswa yang perlu menyusul setelah ujian sempat tertutup.
     *
     * Data ujian tidak pernah dihapus; yang berubah hanya siapa yang melihatnya.
     */
    public function archive(Request $request, $id)
    {
        abort_unless(\App\Support\SiklusUjian::pengawas(), 403,
            'Hanya Superadmin dan Developer yang boleh mengubah status arsip ujian.');

        $exam = Exam::findOrFail($id);
        $tujuan = $request->input('ke');

        if ($tujuan === 'finished') {
            // Penutupan MANUAL. Perlu karena penutupan otomatis mensyaratkan semua
            // peserta sudah mengerjakan; siswa yang tidak akan pernah mengerjakan
            // (pindah/berhenti) akan membuat ujian menggantung Available selamanya.
            abort_unless($exam->isTersedia(), 422);
            $exam->update([
                'status' => \App\Support\SiklusUjian::SELESAI,
                'finished_at' => now(),
            ]);
            $belum = \App\Support\SiklusUjian::belumMengerjakan($exam)->count();
            $pesan = 'Ujian ditandai Selesai secara manual'
                . ($belum > 0 ? " walau masih ada $belum peserta yang belum mengerjakan" : '')
                . '. Ujian ini kini hilang dari Admin, Guru, dan Siswa — hanya Superadmin & Developer '
                . 'yang bisa membukanya.';
        } elseif ($tujuan === 'history') {
            abort_unless($exam->isSelesai() || $exam->isRiwayat(), 422);
            $exam->update([
                'status' => \App\Support\SiklusUjian::RIWAYAT,
                'archived_at' => $exam->archived_at ?? now(),
            ]);
            $pesan = 'Ujian dipindahkan ke History. Admin & Guru kembali melihat ujian ini, '
                . 'tanpa tab Hasil dan Jadwal.';
        } elseif ($tujuan === 'available') {
            abort_unless($exam->isSelesai() || $exam->isRiwayat(), 422);
            $exam->update([
                'status' => \App\Support\SiklusUjian::TERSEDIA,
                'finished_at' => null,
                'archived_at' => null,
            ]);
            $pesan = 'Ujian dibuka kembali (Available). Ujian akan menutup sendiri lagi '
                . 'setelah semua peserta mengerjakan dan tenggatnya terlewat.';
        } else {
            abort(422, 'Tujuan status tidak dikenal.');
        }

        return redirect()->back()->with('success', $pesan);
    }

    private function authorizeExam(Exam $exam): void
    {
        $user = auth()->user();
        if ($user->hasRole('Guru')) {
            $teacherId = $user->teacher?->id;
            if (!$teacherId || $exam->teachingAssignment?->teacher_id !== $teacherId) {
                abort(403, 'Anda hanya dapat mengelola ujian milik Anda.');
            }
        }
    }
}
