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
        Schema::create('module_comments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('learning_module_id');
            $table->uuid('user_id');
            $table->uuid('parent_id')->nullable();
            $table->text('comment');
            $table->timestamps();

            $table->foreign('learning_module_id')->references('id')->on('learning_modules')->onDelete('cascade');
            $table->foreign('user_id')->references('id')->on('users')->onDelete('cascade');
        });

        Schema::table('module_comments', function (Blueprint $table) {
            $table->foreign('parent_id')->references('id')->on('module_comments')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('module_comments');
    }
};
