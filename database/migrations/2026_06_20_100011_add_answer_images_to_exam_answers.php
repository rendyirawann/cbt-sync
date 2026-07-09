<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Jawaban essay bisa disertai foto (kerjaan tangan / rumus / diagram).
 * Disimpan sebagai JSON array path gambar.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            if (!Schema::hasColumn('exam_answers', 'answer_images')) {
                $table->text('answer_images')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('exam_answers', function (Blueprint $table) {
            $table->dropColumn('answer_images');
        });
    }
};
