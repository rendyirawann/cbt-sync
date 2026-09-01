<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        // 1. Notifications Table
        Schema::create('notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->string('title');
            $table->text('message');
            $table->string('type'); // e.g., 'assignment_deadline', 'announcement', 'badge_earned'
            $table->boolean('is_read')->default(false);
            $table->string('url')->nullable();
            $table->timestamps();
        });

        // 2. Badges Table
        Schema::create('badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->text('description');
            $table->string('icon'); // ki-outline or fontawesome class
            $table->string('color'); // bootstrap/metronic context color like 'success', 'warning', etc.
            $table->timestamps();
        });

        // 3. Student Badges Pivot Table
        Schema::create('student_badges', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('student_id')->constrained('students')->onDelete('cascade');
            $table->foreignUuid('badge_id')->constrained('badges')->onDelete('cascade');
            $table->timestamps();
        });

        // Seed default badges
        $badges = [
            [
                'id' => (string) Str::uuid(),
                'name' => 'Pengumpul Kilat',
                'description' => 'Mengumpulkan tugas kurang dari 1 jam sejak dibuat.',
                'icon' => 'ki-electricity',
                'color' => 'success',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Nilai Sempurna',
                'description' => 'Mendapatkan nilai sempurna (100) pada penugasan.',
                'icon' => 'ki-award',
                'color' => 'warning',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Siswa Rajin',
                'description' => 'Mengumpulkan minimal 3 tugas tepat waktu.',
                'icon' => 'ki-star',
                'color' => 'primary',
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'id' => (string) Str::uuid(),
                'name' => 'Lebih Awal',
                'description' => 'Mengumpulkan tugas minimal 24 jam sebelum tenggat waktu.',
                'icon' => 'ki-time',
                'color' => 'info',
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('badges')->insert($badges);
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_badges');
        Schema::dropIfExists('badges');
        Schema::dropIfExists('notifications');
    }
};
