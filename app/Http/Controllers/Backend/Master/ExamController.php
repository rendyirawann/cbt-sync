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
            ->withCount(['questions', 'sessions'])
            // Dipakai tampilan untuk menentukan boleh-tidaknya tombol hapus. Dihitung
            // sekali lewat subquery; memanggil hasStartedAttempts() per baris akan
            // menghasilkan satu query tambahan untuk setiap ujian di daftar.
            ->withExists(['sessions as sudah_dikerjakan' => fn ($q) => $q->whereHas('attempts')])
            // Berapa soal di Bank Soal yang lahir dari ujian ini — ditampilkan di
            // dialog konfirmasi hapus supaya guru tahu dampaknya sebelum memilih.
            ->withCount('bankQuestions');

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

        // Kelas tujuan untuk "Duplikat ke Kelas Lain". Batasnya sengaja ketat:
        // guru yang sama, mapel yang sama, tahun ajaran yang sama, dan kelas
        // dengan TINGKAT (level) serta sekolah yang sama. Daftar ini hanya untuk
        // tampilan — batas yang sama diperiksa ULANG di duplicate(), karena isi
        // form bisa dipalsukan.
        $ta = $exam->teachingAssignment;
        $targetPenugasan = collect();
        if ($ta && $examClass) {
            $targetPenugasan = TeachingAssignment::with('classRoom')
                ->where('subject_id', $ta->subject_id)
                ->where('teacher_id', $ta->teacher_id)
                ->where('academic_year_id', $ta->academic_year_id)
                ->where('id', '!=', $ta->id)
                ->whereHas('classRoom', fn ($q) => $q
                    ->where('level', $examClass->level)
                    ->where('school_id', $examClass->school_id))
                ->get()
                ->sortBy(fn ($t) => $t->classRoom->name ?? '')
                ->values();

            // Kelas yang sudah punya ujian berjudul sama ditandai, bukan dibuang:
            // guru perlu tahu kenapa kelas itu tidak bisa dipilih.
            $sudahAda = Exam::whereIn('teaching_assignment_id', $targetPenugasan->pluck('id'))
                ->where('title', $exam->title)
                ->pluck('teaching_assignment_id')
                ->all();
            $targetPenugasan->each(function ($t) use ($sudahAda) {
                $t->sudah_ada = in_array($t->id, $sudahAda, true);
            });
        }

        return view('backend.master.exams.show', compact(
            'exam', 'examClass', 'students', 'assignments', 'bankQuestions', 'belumUjian',
            'gelombangUjian', 'pesertaTanpaGelombang', 'targetPenugasan'
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

        // Pengaturan ini HANYA berlaku untuk soal pilihan ganda. Seluruh essay
        // selalu ikut ke paket setiap siswa (lihat ExamPortalController::buildLayout),
        // jadi is_active-nya dipaksa true supaya tidak ada essay yang tertinggal
        // nonaktif dari pengaturan lama.
        $pg = $exam->questions->where('type', 'mc');
        $jumlahEssay = $exam->questions->where('type', 'essay')->count();

        if ($mode === 'manual') {
            $dicentangPg = $pg->filter(fn ($q) => in_array((string) $q->id, $dicentang, true));
            if ($dicentangPg->isEmpty() && $pg->isNotEmpty()) {
                return back()->with('error', 'Pilih minimal satu soal pilihan ganda untuk diujikan.');
            }
            foreach ($pg as $q) {
                $q->update(['is_active' => in_array((string) $q->id, $dicentang, true)]);
            }
        } else {
            $pg->each(fn ($q) => $q->update(['is_active' => true]));
        }

        // Essay tidak pernah dinonaktifkan, mode apa pun.
        $exam->questions()->where('type', 'essay')->update(['is_active' => true]);

        $aktif = $mode === 'manual'
            ? $pg->filter(fn ($q) => in_array((string) $q->id, $dicentang, true))->count()
            : $pg->count();
        $jumlah = $mode === 'auto' ? (int) ($data['active_question_count'] ?? 0) : null;

        if ($mode === 'auto') {
            if ($pg->isEmpty()) {
                return back()->with('error', 'Mode otomatis mengacak soal PILIHAN GANDA, dan ujian ini belum punya soal pilihan ganda. Seluruh essay memang selalu diberikan ke semua siswa.');
            }
            if (!$jumlah) {
                return back()->with('error', 'Isi jumlah soal pilihan ganda yang diberikan ke tiap siswa.');
            }
            if ($jumlah > $aktif) {
                return back()->with('error', "Jumlah soal PG ($jumlah) melebihi soal PG tersedia ($aktif).");
            }
        }

        $exam->update(['question_selection' => $mode, 'active_question_count' => $jumlah]);

        $pesan = 'Pengaturan pemilihan soal PG disimpan.';
        if ($jumlahEssay) {
            $pesan .= " Seluruh $jumlahEssay soal essay tetap diberikan ke semua siswa.";
        }

        return back()->with('success', $pesan);
    }

    public function update(Request $request, $id)
    {
        $exam = Exam::with('sessions.students')->findOrFail($id);
        $this->authorizeExam($exam);

        // Penjaga SEBENARNYA. Sebelumnya hanya tombolnya yang dinonaktifkan di
        // tampilan, sementara judul/mode nilai/KKM tetap bisa diubah lewat POST
        // langsung — termasuk saat ujian sudah berjalan.
        if (!\App\Support\SiklusUjian::bolehUbahPengaturan($exam)) {
            return back()->with('error', \App\Support\SiklusUjian::alasanPengaturanTerkunci($exam));
        }

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

    public function destroy(Request $request, $id)
    {
        $exam = Exam::findOrFail($id);
        $this->authorizeExam($exam);

        // GURU tetap dilarang menghapus ujian yang sudah dikerjakan — itu
        // menghapus jawaban & nilai siswa. Admin, Superadmin, dan Developer
        // boleh (lihat SiklusUjian::bolehHapusUjianDikerjakan).
        $sudahDikerjakan = $exam->hasStartedAttempts();
        if ($sudahDikerjakan && !\App\Support\SiklusUjian::bolehHapusUjianDikerjakan()) {
            return redirect()->back()->with('error',
                'Ujian tidak bisa dihapus karena sudah ada siswa yang memulai/mengerjakan. '
                . 'Hubungi Admin atau Superadmin bila ujian ini memang perlu dihapus.');
        }

        // Dihitung SEBELUM dihapus, untuk dilaporkan ke penghapusnya.
        $jmlPercobaan = $sudahDikerjakan
            ? \App\Models\ExamAttempt::whereIn('exam_session_id', $exam->sessions()->select('id'))->count()
            : 0;

        // Soal di Bank Soal yang lahir dari ujian ini. Bank Soal TIDAK ikut
        // terhapus secara otomatis: isinya sengaja bertahan supaya soal tetap
        // bisa dipakai ujian berikutnya. Penghapusannya hanya bila diminta
        // tegas dari dialog konfirmasi (pilihan "hapus sekalian Bank Soal").
        $bank = \App\Models\QuestionBank::where('source_exam_id', $exam->id);
        $idBank = $bank->pluck('id');
        $jmlBank = $idBank->count();

        // Berapa entri bank sekolah LAIN yang meminjam dari soal-soal ini.
        // Menghapusnya tidak merusak mereka — salinan itu berdiri sendiri dan
        // hanya kehilangan tautan asalnya (source_bank_id ber-aturan SET NULL),
        // begitu pula soal yang sudah ditarik ke ujian (tabel questions tidak
        // punya kaitan apa pun ke Bank Soal).
        $jmlPinjaman = $jmlBank > 0
            ? \App\Models\QuestionBank::whereIn('source_bank_id', $idBank)->count()
            : 0;

        $hapusBank = $request->boolean('hapus_bank');
        if ($hapusBank && $jmlBank > 0) {
            \App\Models\QuestionBank::whereIn('id', $idBank)->get()
                ->each(fn ($b) => $b->delete());   // opsi jawabannya ikut lewat cascade
        }

        $exam->delete();

        $pesan = 'Ujian berhasil dihapus.';
        if ($jmlPercobaan > 0) {
            $pesan = 'Ujian dihapus beserta ' . $jmlPercobaan . ' pengerjaan siswa (jawaban & nilainya ikut hilang).';
        }
        if ($jmlBank > 0) {
            $pesan .= $hapusBank
                ? ' ' . $jmlBank . ' soal di Bank Soal dari ujian ini ikut dihapus.'
                : ' ' . $jmlBank . ' soal tetap tersimpan di Bank Soal dan masih bisa dipakai ujian berikutnya.';
            if ($hapusBank && $jmlPinjaman > 0) {
                $pesan .= ' (' . $jmlPinjaman . ' salinan di sekolah lain tidak terpengaruh.)';
            }
        }

        return redirect()->route('exams.index')->with('success', $pesan);
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

    /**
     * Duplikat ujian ini ke kelas lain: satu baris exams baru per kelas tujuan,
     * dengan SALINAN seluruh soalnya.
     *
     * Kenapa disalin dan bukan dibagi: bobot nilai ditulis ke
     * questions.points/points_set pada saat memeriksa, dan storeGrade menilai
     * ulang attempt lain milik ujian yang sama bila bobotnya berubah. Kalau dua
     * kelas berbagi baris soal, memeriksa X-1 akan mengubah nilai X-2.
     *
     * Salinan TIDAK dicerminkan lagi ke Bank Soal: soalnya sudah ada di sana
     * atas nama ujian sumber, dan mencerminkan ulang akan menggandakan katalog
     * sebanyak kelas yang diduplikat.
     */
    public function duplicate(Request $request, $id)
    {
        $exam = Exam::with(['questions.options', 'teachingAssignment.classRoom'])->findOrFail($id);
        $this->authorizeExam($exam);

        $request->validate(
            ['assignment_ids' => 'required|array|min:1'],
            [],
            ['assignment_ids' => 'Kelas tujuan']
        );

        $ta = $exam->teachingAssignment;
        $kelasAsal = $ta?->classRoom;
        if (! $ta || ! $kelasAsal) {
            return back()->with('error', 'Ujian ini tidak punya penugasan/kelas, jadi tidak bisa diduplikat.');
        }
        if ($exam->questions->isEmpty()) {
            return back()->with('error', 'Ujian ini belum punya soal, jadi tidak ada yang bisa diduplikat.');
        }

        // Batas yang sama seperti daftar di layar, diperiksa ULANG di server.
        $targets = TeachingAssignment::with('classRoom')
            ->whereIn('id', $request->assignment_ids)
            ->where('subject_id', $ta->subject_id)
            ->where('teacher_id', $ta->teacher_id)
            ->where('academic_year_id', $ta->academic_year_id)
            ->where('id', '!=', $ta->id)
            ->whereHas('classRoom', fn ($q) => $q
                ->where('level', $kelasAsal->level)
                ->where('school_id', $kelasAsal->school_id))
            ->get();

        if ($targets->isEmpty()) {
            return back()->with('error', 'Tidak ada kelas tujuan yang sah. Kelas tujuan harus mapel, guru, tahun ajaran, dan tingkat yang sama.');
        }

        $dibuat = [];
        $dilewati = [];

        \Illuminate\Support\Facades\DB::transaction(function () use ($exam, $targets, &$dibuat, &$dilewati) {
            foreach ($targets as $t) {
                $namaKelas = $t->classRoom->name ?? '?';

                if (Exam::where('teaching_assignment_id', $t->id)->where('title', $exam->title)->exists()) {
                    $dilewati[] = $namaKelas;
                    continue;
                }

                $baru = Exam::create([
                    'teaching_assignment_id' => $t->id,
                    'source_exam_id'         => $exam->id,
                    'title'                  => $exam->title,
                    'description'            => $exam->description,
                    'type'                   => $exam->type,
                    'points_mode'            => $exam->points_mode,
                    'question_selection'     => $exam->question_selection,
                    'active_question_count'  => $exam->active_question_count,
                    'wrong_penalty'          => $exam->wrong_penalty,
                    'normalize'              => $exam->normalize,
                    'pass_score'             => $exam->pass_score,
                    // SELALU draft: menerbitkan ujian adalah keputusan sadar per kelas.
                    'status'                 => 'draft',
                ]);

                foreach ($exam->questions->sortBy('order') as $q) {
                    $qBaru = $baru->questions()->create([
                        'type'          => $q->type,
                        'question_text' => $q->question_text,
                        'image_path'    => \App\Support\GambarSoal::salin($q->image_path),
                        'points'        => $q->points,
                        'penalty'       => $q->penalty,
                        'order'         => $q->order,
                        // points_set dibiarkan false: bobot untuk kelas ini
                        // ditentukan saat memeriksa, terpisah dari kelas sumber.
                        'points_set'    => false,
                        'is_active'     => $q->is_active ?? true,
                    ]);

                    if ($q->type === 'mc') {
                        foreach ($q->options->sortBy('order') as $opt) {
                            $qBaru->options()->create([
                                'label'       => $opt->label,
                                'option_text' => $opt->option_text,
                                'image_path'  => \App\Support\GambarSoal::salin($opt->image_path),
                                'is_correct'  => $opt->is_correct,
                                'order'       => $opt->order,
                            ]);
                        }
                    }
                }

                $dibuat[] = $namaKelas;
            }
        });

        if (! $dibuat) {
            return back()->with('error', 'Tidak ada ujian yang dibuat. ' . ($dilewati
                ? 'Kelas ' . implode(', ', $dilewati) . ' sudah punya ujian berjudul sama.'
                : ''));
        }

        $pesan = count($dibuat) . ' ujian dibuat sebagai draft untuk kelas ' . implode(', ', $dibuat)
            . ', lengkap dengan ' . $exam->questions->count() . ' soal.';
        if ($dilewati) {
            $pesan .= ' Kelas ' . implode(', ', $dilewati) . ' dilewati karena sudah punya ujian berjudul sama.';
        }

        return back()->with('success', $pesan);
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
