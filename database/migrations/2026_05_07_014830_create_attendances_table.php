<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('user_id')->constrained('users')->onDelete('cascade');
            $table->enum('type', ['datang', 'pulang', 'mapel']);
            $table->unsignedBigInteger('schedule_id')->nullable(); // Reference to schedules (standard bigint id)
            $table->enum('status', ['hadir', 'terlambat', 'alfa', 'izin', 'sakit'])->default('hadir');
            $table->text('note')->nullable();
            $table->timestamp('attended_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
