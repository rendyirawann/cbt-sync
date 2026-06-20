<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Perbaikan: hilangkan kolom `id` UUID pada pivot exam_session_student
 * (sync() belongsToMany tidak punya generator UUID), gunakan unique komposit
 * sebagai primary key. Aman untuk DB yang sudah ter-migrasi maupun fresh.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasColumn('exam_session_student', 'id')) {
            Schema::table('exam_session_student', function (Blueprint $table) {
                $table->dropColumn('id');
            });
            // Jadikan pasangan kolom sebagai primary key.
            Schema::table('exam_session_student', function (Blueprint $table) {
                $table->primary(['exam_session_id', 'student_id']);
            });
        }
    }

    public function down(): void
    {
        // Tidak dikembalikan (struktur lama bermasalah).
    }
};
