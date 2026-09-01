<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\User;
use Spatie\Permission\Models\Role;
use Illuminate\Support\Facades\Hash;

/**
 * Akun DEVELOPER — role tertinggi (vendor/kita). Global: kelola semua sekolah &
 * akun. Role Developer disembunyikan dari UI; akun ini HANYA dibuat lewat seeder.
 *
 * Ubah kredensial di bawah sesuai kebutuhan sebelum dijalankan di server.
 */
class DeveloperSeeder extends Seeder
{
    public function run(): void
    {
        $role = Role::firstOrCreate(['name' => 'Developer', 'guard_name' => 'web']);

        $user = User::updateOrCreate(
            ['email' => 'developer@cbtsync.id'],
            [
                'name' => 'Developer',
                'username' => 'developer',
                'school_id' => null,                 // global, tidak terikat sekolah
                'password' => Hash::make('Developer#2026'),
                'email_verified_at' => now(),
                'is_active' => 1,
            ]
        );
        $user->syncRoles([$role]);

        $this->command->info('Akun Developer siap: developer@cbtsync.id / Developer#2026 (ganti di server!).');
    }
}
