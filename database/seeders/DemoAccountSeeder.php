<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Teacher;
use App\Models\Student;
use App\Models\School;
use App\Models\Subject;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\TeachingAssignment;
use App\Models\Schedule;
use App\Models\ClassStudent;
use App\Models\LearningModule;
use App\Models\Assignment;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;

class DemoAccountSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Buat Role (Jika belum ada)
        $roleAdmin = Role::firstOrCreate(['name' => 'Admin']);
        $roleGuru = Role::firstOrCreate(['name' => 'Guru']);
        $roleSiswa = Role::firstOrCreate(['name' => 'Siswa']);

        // 2. Buat Data Master Sekolah & Tahun Ajaran
        $school = School::updateOrCreate(
            ['name' => 'LMS Academy International'],
            [
                'address' => 'Jl. Digital No. 101, Jakarta',
                'email' => 'contact@lms-academy.com',
                'phone' => '021-99887766'
            ]
        );

        $academicYear = AcademicYear::updateOrCreate(
            ['name' => '2023/2024'],
            [
                'semester' => 'Ganjil',
                'is_active' => 1
            ]
        );

        // 3. Buat Data Master Pendukung (Ruang & Mapel)
        $classes = [];
        $index = 0;
        foreach(['X-IPA 1', 'X-IPA 2', 'XI-IPS 1'] as $cName) {
            $classes[] = ClassRoom::updateOrCreate(
                ['name' => $cName],
                [
                    'school_id' => $school->id,
                    'level' => ($index < 2) ? '10' : '11'
                ]
            );
            $index++;
        }

        $subjects = [];
        foreach(['Matematika', 'Bahasa Inggris', 'Informatika'] as $sName) {
            $subjects[] = Subject::updateOrCreate(['name' => $sName], ['code' => Str::upper(Str::random(5))]);
        }

        // 4. Buat Akun Guru Demo
        $userGuru = User::updateOrCreate(
            ['email' => 'guru@lms.com'],
            [
                'name' => 'Bpk. Budi Santoso, S.Pd',
                'username' => 'budi_guru',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $userGuru->assignRole($roleGuru);

        $teacher = Teacher::updateOrCreate(
            ['user_id' => $userGuru->id],
            [
                'nip' => '198501012010011001',
                'phone' => '081234567890',
                'gender' => 'L',
                'address' => 'Jl. Pendidikan No. 123, Medan'
            ]
        );

        // 5. Penugasan Guru & Jadwal
        foreach ($subjects as $index => $sub) {
            $class = $classes[$index % count($classes)];
            
            $ta = TeachingAssignment::updateOrCreate(
                [
                    'teacher_id' => $teacher->id,
                    'subject_id' => $sub->id,
                    'class_room_id' => $class->id,
                    'academic_year_id' => $academicYear->id
                ]
            );

            // Buat Jadwal untuk setiap Penugasan
            Schedule::updateOrCreate(
                [
                    'teaching_assignment_id' => $ta->id,
                    'day_of_week' => $index + 1, // Senin, Selasa, dst
                ],
                [
                    'start_time' => '08:00:00',
                    'end_time' => '10:00:00',
                    'is_active' => true
                ]
            );

            // Buat Modul Demo
            LearningModule::updateOrCreate(
                ['title' => 'Modul Pengantar ' . $sub->name],
                [
                    'teaching_assignment_id' => $ta->id,
                    'description' => 'Materi dasar untuk memahami ' . $sub->name,
                    'file_path' => 'demo/module.pdf',
                    'file_name' => 'module.pdf',
                    'file_size' => 1024 * 1024 * 2, // 2MB demo
                    'file_type' => 'pdf'
                ]
            );

            // Buat Penugasan Demo
            Assignment::updateOrCreate(
                ['title' => 'Tugas Mandiri ' . $sub->name],
                [
                    'teaching_assignment_id' => $ta->id,
                    'description' => 'Kerjakan latihan soal halaman 10-15',
                    'due_date' => now()->addDays(7)
                ]
            );
        }

        // 6. Buat Akun Siswa Demo
        $userSiswa = User::updateOrCreate(
            ['email' => 'siswa@lms.com'],
            [
                'name' => 'Rendy Irawan',
                'username' => 'rendy_student',
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
            ]
        );
        $userSiswa->assignRole($roleSiswa);

        $student = Student::updateOrCreate(
            ['user_id' => $userSiswa->id],
            [
                'school_id' => $school->id,
                'nisn' => '0012345678',
                'phone' => '089876543210',
                'gender' => 'L',
                'address' => 'Jl. Pelajar No. 45, Medan',
                'parent_name' => 'Bpk. Heru Irawan',
                'parent_email' => 'heru_parent@gmail.com',
                'parent_phone' => '081234567891'
            ]
        );

        // Ploting Siswa ke Kelas Pertama
        ClassStudent::updateOrCreate(
            [
                'student_id' => $student->id,
                'class_room_id' => $classes[0]->id,
                'academic_year_id' => $academicYear->id
            ]
        );

        // 7. Akun Kepala Sekolah Demo (pemantau read-only, ter-scope ke sekolahnya)
        $roleKepsek = Role::firstOrCreate(['name' => 'Kepala Sekolah', 'guard_name' => 'web']);
        $userKepsek = User::updateOrCreate(
            ['email' => 'kepsek@lms.com'],
            [
                'name' => 'Kepala Sekolah Demo',
                'username' => 'kepsek_demo',
                'school_id' => $school->id,
                'password' => Hash::make('password123'),
                'email_verified_at' => now(),
                'is_active' => 1,
            ]
        );
        $userKepsek->syncRoles([$roleKepsek]);

        $this->command->info('Semua Data Demo Berhasil Dibuat!');
    }
}
