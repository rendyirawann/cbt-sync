<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\School;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\TeachingAssignment;
use App\Models\ClassStudent;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

/**
 * Data multi-sekolah untuk CBT-SYNC (idempoten).
 * Tiap sekolah punya: Admin sendiri + Kepala Sekolah + guru + siswa + kelas +
 * penugasan. Semua user diberi school_id sehingga otomatis ter-scope ke sekolahnya.
 * Password semua akun: password123
 */
class MultiSchoolSeeder extends Seeder
{
    public function run(): void
    {
        $roleAdmin  = Role::firstOrCreate(['name' => 'Admin', 'guard_name' => 'web']);
        $roleGuru   = Role::firstOrCreate(['name' => 'Guru', 'guard_name' => 'web']);
        $roleSiswa  = Role::firstOrCreate(['name' => 'Siswa', 'guard_name' => 'web']);
        $roleKepsek = Role::firstOrCreate(['name' => 'Kepala Sekolah', 'guard_name' => 'web']);

        // Tahun ajaran & mapel dipakai bersama semua sekolah.
        $year = AcademicYear::updateOrCreate(['name' => '2025/2026'], ['semester' => 'Ganjil', 'is_active' => 1]);
        $subjects = [];
        foreach (['MTK' => 'Matematika', 'BIN' => 'Bahasa Indonesia', 'BIG' => 'Bahasa Inggris', 'INF' => 'Informatika'] as $code => $name) {
            $subjects[] = Subject::updateOrCreate(['code' => $code], ['name' => $name]);
        }

        $schools = [
            ['name' => 'SMA Negeri 1 Medan',  'slug' => 'sman1',  'classes' => ['X-IPA 1' => '10', 'XI-IPA 1' => '11']],
            ['name' => 'SMK Telkom Medan',    'slug' => 'smktel', 'classes' => ['X-RPL 1' => '10', 'XI-RPL 1' => '11']],
            ['name' => 'SMA Harapan Bangsa',  'slug' => 'smahb',  'classes' => ['X-1' => '10', 'X-2' => '10']],
        ];

        $ringkas = [];
        foreach ($schools as $si => $s) {
            $school = School::updateOrCreate(['name' => $s['name']], [
                'address' => 'Jl. Pendidikan No. ' . ($si + 1) . ', Medan',
                'email' => $s['slug'] . '@sekolah.id',
                'phone' => '061-5000' . $si,
            ]);

            // Admin sekolah + Kepala Sekolah (khusus sekolah ini)
            $this->user("Admin " . $s['name'], "admin.{$s['slug']}@cbt.id", $roleAdmin, $school->id);
            $this->user("Kepala " . $s['name'], "kepsek.{$s['slug']}@cbt.id", $roleKepsek, $school->id);

            // Kelas
            $classes = [];
            foreach ($s['classes'] as $cn => $lv) {
                $classes[] = ClassRoom::updateOrCreate(['school_id' => $school->id, 'name' => $cn], ['level' => $lv]);
            }

            // Guru (2) + penugasan ke tiap kelas
            $teachers = [];
            for ($t = 1; $t <= 2; $t++) {
                $u = $this->user("Guru $t - {$s['name']}", "guru{$t}.{$s['slug']}@cbt.id", $roleGuru, $school->id);
                $teachers[] = Teacher::updateOrCreate(['user_id' => $u->id], [
                    'nip' => 'NIP-' . strtoupper($s['slug']) . $t,
                    'gender' => $t % 2 ? 'L' : 'P',
                    'phone' => '0812' . $si . $t . '000000',
                ]);
            }
            foreach ($classes as $class) {
                foreach ($teachers as $ti => $teacher) {
                    TeachingAssignment::updateOrCreate([
                        'teacher_id' => $teacher->id,
                        'subject_id' => $subjects[$ti % count($subjects)]->id,
                        'class_room_id' => $class->id,
                        'academic_year_id' => $year->id,
                    ]);
                }
            }

            // Siswa (4) + plotting ke kelas
            for ($st = 1; $st <= 4; $st++) {
                $u = $this->user("Siswa $st - {$s['name']}", "siswa{$st}.{$s['slug']}@cbt.id", $roleSiswa, $school->id);
                $student = Student::updateOrCreate(['user_id' => $u->id], [
                    'school_id' => $school->id,
                    'nisn' => 'NISN-' . strtoupper($s['slug']) . $st,
                    'gender' => $st % 2 ? 'L' : 'P',
                    'phone' => '0813' . $si . $st . '000000',
                    'parent_name' => 'Orang Tua Siswa ' . $st,
                    'parent_phone' => '0814' . $si . $st . '000000',
                ]);
                $class = $classes[($st - 1) % count($classes)];
                ClassStudent::updateOrCreate([
                    'student_id' => $student->id,
                    'class_room_id' => $class->id,
                    'academic_year_id' => $year->id,
                ]);
            }

            $ringkas[] = "{$s['name']}  →  Admin: admin.{$s['slug']}@cbt.id";
        }

        $this->command->info('Data multi-sekolah selesai (password semua akun: password123):');
        foreach ($ringkas as $r) {
            $this->command->line('  • ' . $r);
        }
        $this->command->line('  Guru: guru1/2.<slug>@cbt.id  |  Siswa: siswa1..4.<slug>@cbt.id  |  Kepsek: kepsek.<slug>@cbt.id');
    }

    private function user(string $name, string $email, Role $role, string $schoolId): User
    {
        $u = User::updateOrCreate(['email' => $email], [
            'name' => $name,
            'username' => $email,
            'school_id' => $schoolId,
            'password' => Hash::make('password123'),
            'email_verified_at' => now(),
            'is_active' => 1,
        ]);
        $u->syncRoles([$role]);
        return $u;
    }
}
