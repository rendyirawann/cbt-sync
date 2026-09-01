<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                $table->foreignUuid('school_id')->nullable()->constrained('schools')->nullOnDelete();
            });
        }

        // Backfill: bila cuma ada 1 sekolah (kondisi saat ini), tautkan semua user ke sekolah itu.
        // Jika multi-sekolah, ambil dari student.school_id bila tersedia.
        $schoolIds = DB::table('schools')->pluck('id');
        if ($schoolIds->count() === 1) {
            DB::table('users')->whereNull('school_id')->update(['school_id' => $schoolIds->first()]);
        } elseif ($schoolIds->count() > 1) {
            foreach (DB::table('students')->whereNotNull('school_id')->whereNotNull('user_id')->get(['user_id', 'school_id']) as $s) {
                DB::table('users')->where('id', $s->user_id)->whereNull('school_id')->update(['school_id' => $s->school_id]);
            }
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('users', 'school_id')) {
            Schema::table('users', function (Blueprint $table) {
                try {
                    $table->dropConstrainedForeignId('school_id');
                } catch (\Throwable $e) {
                    $table->dropColumn('school_id');
                }
            });
        }
    }
};
