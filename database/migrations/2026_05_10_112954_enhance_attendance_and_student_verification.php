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
        Schema::table('attendances', function (Blueprint $table) {
            $table->integer('late_minutes')->default(0)->after('status');
            $table->text('notes')->nullable()->after('late_minutes');
        });

        Schema::table('students', function (Blueprint $table) {
            $table->timestamp('parent_email_verified_at')->nullable()->after('parent_email');
            $table->timestamp('parent_phone_verified_at')->nullable()->after('parent_phone');
            $table->string('parent_verification_code')->nullable()->after('parent_phone_verified_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropColumn(['late_minutes', 'notes']);
        });

        Schema::table('students', function (Blueprint $table) {
            $table->dropColumn(['parent_email_verified_at', 'parent_phone_verified_at', 'parent_verification_code']);
        });
    }
};
