<?php

namespace App\Services;

use App\Models\Exam;
use App\Models\ExamAttempt;
use App\Models\ExamSession;
use App\Models\Student;
use App\Support\SchoolScope;
use App\Support\SiklusUjian;

/**
 * Isi panel Notifikasi Sistem — dihitung dari KEADAAN CBT saat ini.
 *
 * Panel itu sebelumnya membaca tabel notifications (yang kosong, karena tidak
 * ada yang pernah menuliskannya), lalu jatuh ke daftar Modul & Penugasan dari
 * lms-sync — dua hal yang tidak dipakai aplikasi ujian ini. Hasilnya panel
 * selalu berbunyi "Belum ada notifikasi baru" dan tidak berguna sama sekali.
 *
 * Di sini isinya dibangun langsung dari database dan bersifat DAPAT
 * DITINDAKLANJUTI: setiap butir menunjuk satu hal yang perlu dikerjakan, bukan
 * kabar lewat. Tidak ada baris yang ditulis ke database — panel ini cerminan
 * keadaan, jadi begitu pekerjaannya selesai, butirnya hilang sendiri.
 */
class NotifikasiCbt
{
    /** @return array<int, array{ikon:string,warna:string,judul:string,teks:string,waktu:string,url:string}> */
    public function untuk($user): array
    {
        if (! $user) {
            return [];
        }

        return $user->hasRole('Siswa')
            ? $this->untukSiswa($user)
            : $this->untukPengelola($user);
    }

    private function untukPengelola($user): array
    {
        $sid = SchoolScope::id();
        $sekarang = now();

        $ujian = Exam::query()
            ->when($sid, fn ($q) => $q->whereHas('teachingAssignment.classRoom',
                fn ($c) => $c->where('school_id', $sid)))
            ->when($user->hasRole('Guru') && $user->teacher,
                fn ($q) => $q->whereHas('teachingAssignment',
                    fn ($t) => $t->where('teacher_id', $user->teacher->id)));

        $idUjian = (clone $ujian)->pluck('id');
        $idSesi = ExamSession::whereIn('exam_id', $idUjian)->pluck('id');

        $n = [];

        // 1. Yang paling mendesak: jawaban essay menunggu dinilai.
        $menunggu = ExamAttempt::whereIn('exam_session_id', $idSesi)
            ->where('status', 'submitted')->where('essay_graded', false)->count();
        if ($menunggu > 0) {
            $n[] = [
                'ikon' => 'ki-notepad-edit', 'warna' => 'danger',
                'judul' => $menunggu . ' jawaban menunggu dinilai',
                'teks' => 'Siswa sudah mengumpulkan, nilainya belum keluar sampai diperiksa.',
                'waktu' => 'perlu tindakan', 'url' => route('exams.index'),
            ];
        }

        // 2. Ujian yang jadwalnya sedang berjalan.
        $berjalan = ExamSession::whereIn('id', $idSesi)
            ->where('starts_at', '<=', $sekarang)->where('ends_at', '>=', $sekarang)
            ->with('exam')->get();
        foreach ($berjalan as $s) {
            $n[] = [
                'ikon' => 'ki-timer', 'warna' => 'warning',
                'judul' => 'Berlangsung: ' . ($s->exam->title ?? 'Ujian'),
                'teks' => 'Berakhir ' . \Carbon\Carbon::parse($s->ends_at)->translatedFormat('d M Y H:i') . '.',
                'waktu' => 'sedang berjalan', 'url' => route('exam-monitor.index'),
            ];
        }

        // 3. Ujian TERBIT tapi belum punya jadwal — siswa tidak akan bisa masuk.
        $tanpaJadwal = (clone $ujian)->where('status', SiklusUjian::TERSEDIA)
            ->whereDoesntHave('sessions')->get();
        foreach ($tanpaJadwal as $e) {
            $n[] = [
                'ikon' => 'ki-calendar-remove', 'warna' => 'danger',
                'judul' => 'Belum ada jadwal: ' . $e->title,
                'teks' => 'Ujian sudah terbit tetapi belum punya jadwal, jadi siswa belum bisa mengerjakannya.',
                'waktu' => 'perlu tindakan', 'url' => route('exams.show', $e->id),
            ];
        }

        // 4. Ujian DRAFT tanpa soal — tidak akan bisa diterbitkan.
        $tanpaSoal = (clone $ujian)->where('status', SiklusUjian::DRAFT)
            ->whereDoesntHave('questions')->get();
        foreach ($tanpaSoal as $e) {
            $n[] = [
                'ikon' => 'ki-document', 'warna' => 'info',
                'judul' => 'Belum ada soal: ' . $e->title,
                'teks' => 'Masih draft dan belum berisi soal. Tambahkan soal sebelum menerbitkan.',
                'waktu' => 'draft', 'url' => route('exams.show', $e->id),
            ];
        }

        // 5. Jadwal yang akan mulai dalam 24 jam.
        $segera = ExamSession::whereIn('id', $idSesi)
            ->whereBetween('starts_at', [$sekarang, $sekarang->copy()->addDay()])
            ->with('exam')->get();
        foreach ($segera as $s) {
            $n[] = [
                'ikon' => 'ki-calendar-tick', 'warna' => 'primary',
                'judul' => 'Segera mulai: ' . ($s->exam->title ?? 'Ujian'),
                'teks' => 'Mulai ' . \Carbon\Carbon::parse($s->starts_at)->translatedFormat('d M Y H:i') . '.',
                'waktu' => 'dalam 24 jam', 'url' => route('exams.show', $s->exam_id),
            ];
        }

        // 6. Siswa tanpa NISN — kartu ujian & daftar hadirnya akan kosong.
        $tanpaNisn = Student::when($sid, fn ($q) => $q->where('school_id', $sid))
            ->where(fn ($q) => $q->whereNull('nisn')->orWhere('nisn', ''))->count();
        if ($tanpaNisn > 0) {
            $n[] = [
                'ikon' => 'ki-profile-user', 'warna' => 'warning',
                'judul' => $tanpaNisn . ' siswa belum punya NISN',
                'teks' => 'Nomor peserta di Kartu Ujian dan Daftar Hadir akan kosong.',
                'waktu' => 'perlu dilengkapi', 'url' => route('students.index'),
            ];
        }

        return array_slice($n, 0, 15);
    }

    private function untukSiswa($user): array
    {
        $siswa = $user->student;
        if (! $siswa) {
            return [];
        }

        $ringkas = app(RingkasanCbt::class)->untukSiswa($siswa);
        $n = [];

        foreach ($ringkas['sedang_dikerjakan'] as $s) {
            $n[] = [
                'ikon' => 'ki-timer', 'warna' => 'danger',
                'judul' => 'Ujian belum selesai: ' . ($s->exam->title ?? '-'),
                'teks' => 'Lanjutkan sebelum ' . \Carbon\Carbon::parse($s->ends_at)->translatedFormat('d M H:i') . '.',
                'waktu' => 'segera', 'url' => route('student.exams.attempt', $s->id),
            ];
        }

        foreach ($ringkas['siap_dikerjakan'] as $s) {
            $n[] = [
                'ikon' => 'ki-rocket', 'warna' => 'primary',
                'judul' => 'Siap dikerjakan: ' . ($s->exam->title ?? '-'),
                'teks' => 'Sampai ' . \Carbon\Carbon::parse($s->ends_at)->translatedFormat('d M H:i') . '.',
                'waktu' => 'tersedia', 'url' => route('student.exams.index'),
            ];
        }

        if (($ringkas['menunggu_nilai'] ?? 0) > 0) {
            $n[] = [
                'ikon' => 'ki-hourglass', 'warna' => 'warning',
                'judul' => $ringkas['menunggu_nilai'] . ' ujian menunggu dinilai',
                'teks' => 'Jawabanmu sudah masuk, nilainya belum keluar.',
                'waktu' => 'menunggu guru', 'url' => route('student.exams.index'),
            ];
        }

        foreach ($ringkas['hasil_terakhir']->take(3) as $a) {
            $kkm = (float) ($a->session->exam->pass_score ?? 0);
            $lulus = (float) $a->final_score >= $kkm;
            $n[] = [
                'ikon' => $lulus ? 'ki-verify' : 'ki-information-5',
                'warna' => $lulus ? 'success' : 'danger',
                'judul' => 'Nilai keluar: ' . ($a->session->exam->title ?? '-'),
                'teks' => 'Nilai ' . rtrim(rtrim(number_format((float) $a->final_score, 2, ',', ''), '0'), ',')
                    . ' — ' . ($lulus ? 'lulus KKM' : 'di bawah KKM') . '.',
                'waktu' => $a->submitted_at ? \Carbon\Carbon::parse($a->submitted_at)->diffForHumans() : '-',
                'url' => route('student.exams.index'),
            ];
        }

        foreach ($ringkas['akan_datang']->take(3) as $s) {
            $n[] = [
                'ikon' => 'ki-calendar-tick', 'warna' => 'info',
                'judul' => 'Jadwal berikutnya: ' . ($s->exam->title ?? '-'),
                'teks' => 'Mulai ' . \Carbon\Carbon::parse($s->starts_at)->translatedFormat('d M Y H:i') . '.',
                'waktu' => 'akan datang', 'url' => route('student.exams.index'),
            ];
        }

        return array_slice($n, 0, 15);
    }
}
