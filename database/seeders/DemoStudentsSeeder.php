<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use App\Models\Student;
use App\Models\School;
use App\Models\ClassRoom;
use App\Models\AcademicYear;
use App\Models\ClassStudent;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

/**
 * 5 akun demo siswa, ditempatkan ke rombel (ruang kelas) acak.
 * Idempoten: aman dijalankan berulang (updateOrCreate berdasarkan email).
 */
class DemoStudentsSeeder extends Seeder
{
    public function run(): void
    {
        $roleSiswa = Role::firstOrCreate(['name' => 'Siswa']);

        $classes = ClassRoom::all();
        if ($classes->isEmpty()) {
            $this->command->warn('Belum ada Ruang Kelas — jalankan DemoAccountSeeder dulu. DemoStudentsSeeder dilewati.');
            return;
        }

        $academicYear = AcademicYear::where('is_active', 1)->first()
            ?? AcademicYear::first()
            ?? AcademicYear::create([
                'name' => date('Y') . '/' . (date('Y') + 1),
                'semester' => 'Ganjil',
                'is_active' => 1,
            ]);

        $defaultSchool = School::first();

        $password = 'siswademo123';
        $names = ['Andi Pratama', 'Bunga Lestari', 'Citra Anggraini', 'Dimas Saputra', 'Elang Nugroho'];
        $created = [];

        foreach ($names as $i => $name) {
            $n = $i + 1;
            $email = "siswademo{$n}@lms.com";
            $class = $classes->random(); // rombel acak
            $schoolId = $class->school_id ?? $defaultSchool?->id;

            $user = User::updateOrCreate(
                ['email' => $email],
                [
                    'name' => $name,
                    'username' => 'siswademo' . $n,
                    'password' => Hash::make($password),
                    'email_verified_at' => now(),
                ]
            );
            if (!$user->hasRole('Siswa')) {
                $user->assignRole($roleSiswa);
            }

            $student = Student::updateOrCreate(
                ['user_id' => $user->id],
                [
                    'school_id' => $schoolId,
                    'nisn' => '99' . str_pad((string) $n, 8, '0', STR_PAD_LEFT),
                    'gender' => $i % 2 === 0 ? 'L' : 'P',
                    'phone' => '08120000000' . $n,
                    'address' => 'Jl. Demo Siswa No. ' . $n,
                    'parent_name' => 'Orang Tua ' . $name,
                    'parent_email' => "ortu.siswademo{$n}@lms.com",
                    'parent_phone' => '08130000000' . $n,
                ]
            );

            // Tempatkan ke satu rombel acak (bersihkan penempatan lama agar idempoten).
            ClassStudent::where('student_id', $student->id)->delete();
            ClassStudent::create([
                'student_id' => $student->id,
                'class_room_id' => $class->id,
                'academic_year_id' => $academicYear->id,
            ]);

            $created[] = ['name' => $name, 'email' => $email, 'class' => $class->name];
        }

        $this->command->info('5 akun demo siswa berhasil dibuat (password semua: ' . $password . '):');
        foreach ($created as $c) {
            $this->command->line("  • {$c['name']} — {$c['email']} — Rombel: {$c['class']}");
        }
    }
}
