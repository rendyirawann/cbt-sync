<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Indeks untuk kolom FOREIGN KEY.
 *
 * PostgreSQL TIDAK membuat indeks otomatis untuk kolom foreign key — hanya untuk
 * primary key dan unique. Pemeriksaan pada DB nyata menemukan 44 kolom FK tanpa
 * indeks sama sekali, dan itu terasa pada dua hal:
 *
 *  1. Daftar & pencarian. Hampir semua daftar disaring per sekolah
 *     (students.school_id, users.school_id) lalu di-join ke users/kelas. Tanpa
 *     indeks, tiap kali tabel dibaca seluruhnya.
 *
 *  2. Penghapusan berantai. Menghapus akun user memicu ON DELETE CASCADE ke
 *     belasan tabel; tiap tabel anak harus dicari berdasarkan kolom FK-nya.
 *     Tanpa indeks, menghapus 100 siswa berarti seratus kali pembacaan penuh
 *     pada setiap tabel anak.
 *
 * Dibuat CONCURRENTLY? Tidak — ukuran tabel di sini masih kecil dan
 * CONCURRENTLY tidak boleh berjalan di dalam transaksi migrasi Laravel.
 *
 * Aman diulang: memakai CREATE INDEX IF NOT EXISTS, dan kolom yang tidak ada
 * (mis. modul yang belum dipakai sekolah tertentu) dilewati.
 */
return new class extends Migration
{
    /** [tabel, kolom] — hasil pemeriksaan kolom FK tanpa indeks. */
    private array $target = [
        ['book_borrowings', 'book_id'],
        ['book_borrowings', 'student_id'],
        ['chats', 'receiver_id'],
        ['chats', 'sender_id'],
        ['class_rooms', 'school_id'],
        ['class_students', 'academic_year_id'],
        ['class_students', 'class_room_id'],
        ['discussions', 'module_id'],
        ['discussions', 'teaching_assignment_id'],
        ['discussions', 'user_id'],
        ['exam_answers', 'question_id'],
        ['exam_answers', 'selected_option_id'],
        ['exam_attempts', 'student_id'],
        ['exam_session_student', 'student_id'],
        ['exam_sessions', 'class_room_id'],
        ['exam_sessions', 'exam_id'],
        ['exams', 'teaching_assignment_id'],
        ['learning_modules', 'teaching_assignment_id'],
        ['module_comments', 'learning_module_id'],
        ['module_comments', 'parent_id'],
        ['module_comments', 'user_id'],
        ['module_views', 'learning_module_id'],
        ['module_views', 'student_id'],
        ['modules', 'teaching_assignment_id'],
        ['notifications', 'user_id'],
        ['question_bank_options', 'question_bank_id'],
        ['question_banks', 'created_by'],
        ['question_banks', 'source_bank_id'],
        ['question_banks', 'source_school_id'],
        ['question_banks', 'subject_id'],
        ['question_options', 'question_id'],
        ['questions', 'exam_id'],
        ['schedules', 'teaching_assignment_id'],
        ['student_badges', 'badge_id'],
        ['student_badges', 'student_id'],
        ['students', 'school_id'],
        ['students', 'user_id'],
        ['students', 'wave_id'],
        ['teachers', 'user_id'],
        ['teaching_assignments', 'academic_year_id'],
        ['teaching_assignments', 'class_room_id'],
        ['teaching_assignments', 'subject_id'],
        ['teaching_assignments', 'teacher_id'],
        ['users', 'school_id'],
    ];

    public function up(): void
    {
        foreach ($this->target as [$tabel, $kolom]) {
            if (! Schema::hasTable($tabel) || ! Schema::hasColumn($tabel, $kolom)) {
                continue;
            }

            $nama = 'idx_' . $tabel . '_' . $kolom;
            DB::statement("CREATE INDEX IF NOT EXISTS \"{$nama}\" ON \"{$tabel}\" (\"{$kolom}\")");
        }
    }

    public function down(): void
    {
        foreach ($this->target as [$tabel, $kolom]) {
            $nama = 'idx_' . $tabel . '_' . $kolom;
            DB::statement("DROP INDEX IF EXISTS \"{$nama}\"");
        }
    }
};
