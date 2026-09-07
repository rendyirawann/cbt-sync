<?php

namespace Database\Seeders;

use App\Models\{AcademicYear, ClassRoom, ClassStudent, Exam, ExamSession, Question,
                School, Student, Subject, Teacher, TeachingAssignment, User, Wave};
use App\Support\KartuUjian;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

/**
 * Data CONTOH untuk melihat hasil Kartu Ujian dan Daftar Hadir Peserta.
 *
 * Dijalankan MANUAL saja — sengaja TIDAK didaftarkan di DatabaseSeeder supaya
 * `php artisan db:seed` tidak diam-diam menambah data demo:
 *
 *     php artisan db:seed --class=DemoKartuUjianSeeder --force
 *
 * Yang dibuat: 1 guru + 15 siswa (8 Gelombang 1, 7 Gelombang 2) lengkap dengan
 * ID Proktor, Ruang, tempat/tanggal lahir, username bergaya ANBK, dan password
 * kartu yang sudah diterbitkan — sehingga kartu langsung terisi penuh tanpa
 * perlu menekan cetak lebih dulu. Ditambah 1 ujian terbit dengan 5 soal dan
 * satu jadwal yang sedang berjalan.
 *
 * Idempotent: dijalankan berulang tidak menggandakan apa pun (semuanya berkunci
 * email / nama / NISN).
 *
 * Semua akun demo memakai email berakhiran @demo.cbtsync.test, jadi mudah
 * dihapus kembali:
 *
 *     php artisan tinker --execute="
 *       App\Models\Exam::where('title','ASESMEN NASIONAL SMA/MA')->delete();
 *       \$u = App\Models\User::where('email','like','%@demo.cbtsync.test')->get();
 *       DB::table('model_has_roles')->whereIn('model_id', \$u->pluck('id'))->delete();
 *       App\Models\User::whereIn('id', \$u->pluck('id'))->delete();"
 */
class DemoKartuUjianSeeder extends Seeder
{
    /** Domain penanda supaya data demo mudah dikenali & dihapus. */
    private const DOMAIN = '@demo.cbtsync.test';

    public function run(): void
    {
        $sekolah = School::first();
        if (! $sekolah) {
            $this->command->error('Belum ada data sekolah. Isi Data Master → Data Sekolah dulu.');
            return;
        }

        // Kop Daftar Hadir memerlukan tiga isian ini. Hanya diisi bila MASIH
        // KOSONG — kalau sekolah sudah mengisinya, nilai aslinya dipertahankan.
        $sekolah->fill(array_filter([
            'city' => $sekolah->city ?: 'Kabupaten Langkat',
            'city_code' => $sekolah->city_code ?: '03',
            'school_code' => $sekolah->school_code ?: '0017',
        ]))->save();

        $tahun = AcademicYear::where('is_active', 1)->first() ?? AcademicYear::first();
        if (! $tahun) {
            $this->command->error('Belum ada tahun ajaran. Isi Data Master → Tahun Ajaran dulu.');
            return;
        }

        $kelas = ClassRoom::firstOrCreate(
            ['name' => 'KELAS X-1'],
            ['school_id' => $sekolah->id, 'level' => 'X']
        );
        $mapel = Subject::firstOrCreate(
            ['code' => 'LSK-X'],
            ['name' => 'Literasi & Survei Karakter']
        );

        [$g1, $g2] = $this->gelombang();

        $guru = $this->guru($sekolah);
        $ta = TeachingAssignment::firstOrCreate([
            'class_room_id' => $kelas->id,
            'subject_id' => $mapel->id,
            'teacher_id' => $guru->id,
            'academic_year_id' => $tahun->id,
        ]);

        $peserta = $this->peserta($sekolah, $kelas, $tahun, $g1, $g2);
        $ujian = $this->ujian($ta);
        $this->jadwal($ujian, $kelas);

        $this->command->info('');
        $this->command->info('  Data contoh siap:');
        $this->command->info("    Sekolah   : {$sekolah->name} ({$sekolah->city}, kode {$sekolah->city_code}/{$sekolah->school_code})");
        $this->command->info("    Kelas     : {$kelas->name} — {$peserta->count()} siswa");
        $this->command->info("    Gelombang : {$g1->name} " . $peserta->where('wave_id', $g1->id)->count()
            . " siswa ({$g1->rentang_jam}) · {$g2->name} " . $peserta->where('wave_id', $g2->id)->count()
            . " siswa ({$g2->rentang_jam})");
        $this->command->info("    Ujian     : {$ujian->title} — {$ujian->questions()->count()} soal, status "
            . \App\Support\SiklusUjian::labelStatus($ujian->status));
        $this->command->info('');
        $this->command->info('  Lihat hasilnya:');
        $this->command->info('    Kartu Ujian   → Rombongan Belajar → tombol "Export PDF Kartu Ujian"');
        $this->command->info('    Daftar Hadir  → Ujian / CBT → Kelola → tab Hasil & Nilai → tombol per gelombang');
        $this->command->info('    Kartu siswa   → login sebagai siswa → My Profile → Kartu Ujian');
    }

    /** Gelombang bawaan; dibuat bila WaveSeeder belum pernah jalan. */
    private function gelombang(): array
    {
        $bawaan = [['Gelombang 1', 1, '07:30', '09:40'], ['Gelombang 2', 2, '10:30', '12:40']];
        $hasil = [];
        foreach ($bawaan as [$nama, $urutan, $mulai, $selesai]) {
            $w = Wave::firstOrCreate(['name' => $nama], [
                'sort_order' => $urutan, 'is_active' => true,
                'start_time' => $mulai, 'end_time' => $selesai,
            ]);
            // Gelombang yang sudah ada tapi jamnya kosong ikut dilengkapi,
            // karena kolom PUKUL pada Daftar Hadir mengambil dari sini.
            if (blank($w->start_time) || blank($w->end_time)) {
                $w->update(['start_time' => $mulai, 'end_time' => $selesai]);
            }
            $hasil[] = $w->refresh();
        }

        return $hasil;
    }

    private function guru(School $sekolah): Teacher
    {
        $u = User::firstOrNew(['email' => 'edy.matematika' . self::DOMAIN]);
        $u->forceFill([
            'name' => 'Edy Syahputra, S.Pd',
            'username' => 'edy.demo',
            'password' => Hash::make('gurudemo123'),
            'email_verified_at' => now(),
            'is_active' => true,
            'school_id' => $sekolah->id,
        ])->save();
        $u->syncRoles([Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web'])->name]);

        return Teacher::firstOrCreate(['user_id' => $u->id], ['nip' => '197805122006041008']);
    }

    /**
     * 15 peserta. Username mengikuti pola kartu ANBK (U07030017000xx) dan
     * ID Proktor / Ruang dibuat sama untuk satu ruang, supaya baris
     * "LINK UJIAN" pada Daftar Hadir terisi (baris itu hanya terisi bila
     * seluruh peserta di gelombang tersebut memakai nilai yang sama).
     */
    private function peserta(School $sekolah, ClassRoom $kelas, AcademicYear $tahun, Wave $g1, Wave $g2)
    {
        $daftar = [
            ['MHD RADIT TRIANDI NUGROHO', 'L', 'Binjai',          '2007-01-18'],
            ['Adelia Ningsih',            'P', 'Pangkalan Brandan','2007-03-02'],
            ['MEI ANGEL BR SIGALINGGING', 'P', 'Pematang Siantar','2007-05-21'],
            ['Indah Tambunan',            'P', 'Medan',           '2007-07-09'],
            ['Dian Dwi Cahyani',          'P', 'Babalan',         '2007-08-30'],
            ['Bagus Jiwangga Prasetya Lubis', 'L', 'Stabat',      '2007-02-14'],
            ['Niken Salsabila',           'P', 'Binjai',          '2007-11-25'],
            ['AISKA MAHARANI',            'P', 'Langkat',         '2007-04-06'],
            ['ISNANI GRIP ANGELIANI',     'P', 'Tanjung Pura',    '2007-06-17'],
            ['ERLIANA AIRIN',             'P', 'Babalan',         '2007-09-12'],
            ['KEMALA DEWI',               'P', 'Medan',           '2007-10-03'],
            ['M. Septyo Wardhana Lubis',  'L', 'Pangkalan Brandan','2007-12-28'],
            ["As'ad Fauziah Ningrum Nst", 'P', 'Binjai',          '2007-01-31'],
            ['INTAN ADIYANTI',            'P', 'Stabat',          '2007-03-19'],
            ['KHAIRUN AKBAR',             'L', 'Babalan',         '2007-05-08'],
        ];

        $hasil = collect();
        foreach ($daftar as $i => [$nama, $jk, $lahirDi, $lahirTgl]) {
            $n = $i + 1;
            $urut = str_pad((string) ($n * 7 + 40), 3, '0', STR_PAD_LEFT);   // 054, 061, …
            $username = 'U0703001700' . $urut;

            $u = User::firstOrNew(['email' => 'peserta' . $n . self::DOMAIN]);
            $u->forceFill([
                'name' => $nama,
                'username' => $username,
                'password' => Hash::make('sementara'),   // segera diganti password kartu
                'email_verified_at' => now(),
                'is_active' => true,
                'school_id' => $sekolah->id,
            ])->save();
            $u->syncRoles([Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web'])->name]);

            $s = Student::firstOrCreate(['user_id' => $u->id], ['school_id' => $sekolah->id]);
            $s->update([
                'nisn' => '00788' . str_pad((string) (76300 + $n), 5, '0', STR_PAD_LEFT),
                'gender' => $jk,
                'birth_place' => $lahirDi,
                'birth_date' => $lahirTgl,
                'proctor_id' => 'U07030017-AY8U',
                'room' => 'ANBK-SMA-1',
                // 8 peserta gelombang 1, 7 peserta gelombang 2
                'wave_id' => $n <= 8 ? $g1->id : $g2->id,
            ]);

            // Password kartu diterbitkan sekarang, bukan menunggu tombol cetak,
            // supaya baris Password pada kartu contoh langsung terisi.
            KartuUjian::terbitkan($s);

            ClassStudent::updateOrCreate(
                ['student_id' => $s->id, 'academic_year_id' => $tahun->id],
                ['class_room_id' => $kelas->id]
            );

            $hasil->push($s->refresh());
        }

        return $hasil;
    }

    private function ujian(TeachingAssignment $ta): Exam
    {
        $e = Exam::firstOrNew(['title' => 'ASESMEN NASIONAL SMA/MA']);
        $e->forceFill([
            'teaching_assignment_id' => $ta->id,
            'description' => 'Contoh data untuk melihat Kartu Ujian dan Daftar Hadir Peserta.',
            'type' => 'mc',
            'points_mode' => 'auto',
            'pass_score' => 70,
            'status' => \App\Support\SiklusUjian::TERSEDIA,
            'finished_at' => null,
            'archived_at' => null,
        ])->save();

        if ($e->questions()->count() === 0) {
            $soal = [
                ['Ibu kota Provinsi Sumatera Utara adalah…', ['Medan', 'Binjai', 'Stabat', 'Langkat'], 0],
                ['Hasil dari 12 × 8 adalah…', ['86', '96', '108', '116'], 1],
                ['Kalimat berikut yang memakai ejaan baku adalah…',
                    ['Dia pergi kesekolah', 'Dia pergi ke sekolah', 'Dia pergi kesekolahan', 'Dia pergi ke-sekolah'], 1],
                ['Air mendidih pada suhu…', ['50 °C', '75 °C', '100 °C', '150 °C'], 2],
                ['Lambang negara Republik Indonesia adalah…',
                    ['Garuda Pancasila', 'Bendera Merah Putih', 'Bhinneka Tunggal Ika', 'Tut Wuri Handayani'], 0],
            ];
            foreach ($soal as $urutan => [$teks, $opsi, $benar]) {
                $q = Question::create([
                    'exam_id' => $e->id,
                    'type' => 'mc',
                    'question_text' => $teks,
                    'points' => 0,          // mode auto: poin dibagi rata sistem
                    'order' => $urutan + 1,
                ]);
                foreach ($opsi as $k => $isi) {
                    // label A/B/C/D mengikuti cara ExamQuestionController membuat opsi.
                    $q->options()->create([
                        'label' => chr(65 + $k),
                        'option_text' => $isi,
                        'is_correct' => $k === $benar,
                        'order' => $k + 1,
                    ]);
                }
            }
        }

        return $e->refresh();
    }

    /** Satu jadwal (rentang tanggal, tanpa jam) yang sedang berjalan. */
    private function jadwal(Exam $ujian, ClassRoom $kelas): void
    {
        $s = ExamSession::firstOrNew(['exam_id' => $ujian->id, 'name' => 'Asesmen Nasional ' . now()->year]);
        $s->forceFill([
            'is_makeup' => false,
            'class_room_id' => $kelas->id,
            'starts_at' => now()->startOfDay(),
            'ends_at' => now()->addDays(6)->endOfDay(),
            'duration_minutes' => 120,
            'max_capacity' => null,
            'shuffle_questions' => true,
            'shuffle_options' => true,
            'show_result' => true,
            'status' => 'scheduled',
            'is_active' => true,
            'resume_pin' => $s->resume_pin ?: str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT),
        ])->save();
    }
}
