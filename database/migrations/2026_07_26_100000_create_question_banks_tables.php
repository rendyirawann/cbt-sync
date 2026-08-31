<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('question_banks', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('subject_id')->constrained('subjects')->onDelete('cascade');
            $table->string('level')->nullable();            // tingkat, mis. 10/11/12
            $table->enum('type', ['mc', 'essay'])->default('mc');
            $table->text('question_text')->nullable();
            $table->string('image_path')->nullable();
            $table->decimal('points', 6, 2)->default(1);
            $table->decimal('penalty', 6, 2)->default(0);
            $table->foreignUuid('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
        });

        Schema::create('question_bank_options', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('question_bank_id')->constrained('question_banks')->onDelete('cascade');
            $table->string('label', 5)->nullable();
            $table->text('option_text')->nullable();
            $table->string('image_path')->nullable();
            $table->boolean('is_correct')->default(false);
            $table->integer('order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('question_bank_options');
        Schema::dropIfExists('question_banks');
    }
};
