<?php

use Illuminate\Support\Facades\Route;

// Import Controller Dashboard
use App\Http\Controllers\Backend\Dashboard\DashboardAdminController; // Sesuaikan jika nama controllernya beda
// Import Controller PROFILE
use App\Http\Controllers\Backend\MyProfile\AccountController;
use App\Http\Controllers\Backend\MyProfile\ProfileController;
use App\Http\Controllers\Backend\MyProfile\SecurityController;
use App\Http\Controllers\Backend\MyProfile\ActivityController;
use App\Http\Controllers\Backend\MyProfile\LoginSessionController;

// Import Controller USER MANAGEMENT
use App\Http\Controllers\Backend\UserManagement\UserController;
use App\Http\Controllers\Backend\UserManagement\RoleController;

// Import Controller HELP/LOG
use App\Http\Controllers\Backend\Help\LogActivityController;
use App\Http\Controllers\Backend\Settings\SettingController;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

// --- PORTAL SISWA (FRONTEND) ---
Route::get('/login', [\App\Http\Controllers\Frontend\PortalController::class, 'login'])->name('student.login');
Route::post('/login', [\App\Http\Controllers\Frontend\PortalController::class, 'authenticate'])->name('student.authenticate');





Route::get('/', function () {
    return view('frontend.landing.index');
})->name('landing');



// --- TARUH DEBUG DISINI (DI LUAR MIDDLEWARE AUTH) ---
Route::get('/admin/debug-session', function () {
    $user = auth()->user();

    // Cek manual apakah tabel bans error
    $bannedStatus = 'Tidak dicek';
    $error = null;

    if ($user) {
        try {
            // Kita coba panggil paksa relasi banned-nya
            $bannedStatus = $user->isBanned() ? 'YA TER-BANNED' : 'AMAN';
        } catch (\Exception $e) {
            $bannedStatus = 'ERROR SAAT CEK BANNED: ' . $e->getMessage();
        }
    }

    return [
        'status_login' => $user ? 'SUDAH LOGIN' : 'BELUM LOGIN / SESI HILANG',
        'user_id' => $user?->id,
        'user_name' => $user?->name,
        'session_id' => session()->getId(),
        'driver_session' => config('session.driver'),
        'cek_banned' => $bannedStatus,
    ];
});

// NOTE: Route /login POST dihapus dari sini karena sudah ada di auth.php
// agar tidak bentrok "Route [login] defined twice".

// Group Middleware untuk User yang sudah Login
// Kita tambahkan 'forbid-banned-user' agar user yang di-banned tidak bisa akses
Route::middleware(['auth', 'forbid-banned-user'])->group(function () {

    // --- SHARED ROLE ROUTES (generate-permissions helper, select) ---
    Route::post('/admin/roles/generate-permissions', [RoleController::class, 'generatePermissions'])->name('roles.generate');
    Route::get('/admin/select/role', [RoleController::class, 'select'])->name('role.select');

    // --- DASHBOARD (accessible by ALL authenticated roles) ---
    Route::get('/admin/dashboard', [DashboardAdminController::class, 'index'])->name('dashboard');

    // --- MY ACCOUNT / PROFILE (accessible by ALL authenticated users) ---
    Route::get('/admin/my-account', [AccountController::class, 'index'])->name('account.index');
    Route::get('/admin/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('avatar-edit');
    Route::post('/admin/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('avatar-update');

    Route::resource('/admin/my-profile', ProfileController::class);
    Route::resource('/admin/my-security', SecurityController::class);
    Route::post('/admin/my-security', [SecurityController::class, 'store'])->name('change.password');
    Route::post('/admin/my-security/logout-other-devices', [SecurityController::class, 'logoutOtherDevices'])->name('security.logout-other-devices');

    Route::get('/admin/my-activity', [ActivityController::class, 'index'])->name('my-activity.index');
    Route::get('/admin/mget-my-activity', [ActivityController::class, 'getActivity'])->name('get-my-activity');

    Route::get('/admin/mmy-login-session', [LoginSessionController::class, 'index'])->name('my-login-session.index');
    Route::get('/admin/mget-my-login-session', [LoginSessionController::class, 'getLoginSession'])->name('get-my-login-session');

    // --- SETTINGS (accessible by ALL authenticated users) ---
    Route::get('/admin/settings', [SettingController::class, 'index'])->name('settings.index');

    // Master Data Routes LMS
    Route::resource('/admin/teachers', \App\Http\Controllers\Backend\Master\TeacherController::class);
    Route::resource('/admin/students', \App\Http\Controllers\Backend\Master\StudentController::class);
    Route::resource('/admin/schools', \App\Http\Controllers\Backend\Master\SchoolController::class);
    Route::resource('/admin/academic-years', \App\Http\Controllers\Backend\Master\AcademicYearController::class);
    Route::resource('/admin/subjects', \App\Http\Controllers\Backend\Master\SubjectController::class);
    Route::resource('/admin/class-rooms', \App\Http\Controllers\Backend\Master\ClassRoomController::class);
    Route::resource('/admin/teaching-assignments', \App\Http\Controllers\Backend\Master\TeachingAssignmentController::class);
    Route::resource('/admin/learning-modules', \App\Http\Controllers\Backend\Master\LearningModuleController::class);
    Route::get('/admin/learning-modules/{id}/download', [\App\Http\Controllers\Backend\Master\LearningModuleController::class, 'download'])->name('learning-modules.download');
    Route::resource('/admin/enrollments', \App\Http\Controllers\Backend\Master\ClassStudentController::class);
    Route::resource('/admin/assignments', \App\Http\Controllers\Backend\Master\AssignmentController::class);
    Route::post('/admin/assignments/submission/{submissionId}/score', [\App\Http\Controllers\Backend\Master\AssignmentController::class, 'score'])->name('assignments.score');
    Route::post('/admin/assignments/{id}/submit', [\App\Http\Controllers\Backend\Master\AssignmentController::class, 'submit'])->name('assignments.submit');
    Route::post('/admin/settings/update', [SettingController::class, 'update'])->name('settings.update');

    // --- DEBUG/CHECK AUTH ---
    Route::get('/admin/check-auth', function () {
        $u = auth()->user();
        return [
            'user' => $u,
            'roles' => $u?->getRoleNames(),
            'permissions' => $u?->getAllPermissions()->pluck('name'),
        ];
    });
    Route::get('/admin/debug-session', function () {
        $user = auth()->user();
        return ['user' => $user?->name, 'roles' => $user?->getRoleNames()];
    });

    // ====================================================
    // RESOURCES (User & Role Mgmt): view_resources — Superadmin only
    // ====================================================
    Route::middleware('can:view_resources')->group(function () {
        Route::resource('/admin/users', UserController::class);
        Route::get('/admin/get-datauser', [UserController::class, 'getDataUsers'])->name('get-users');
        Route::post('/admin/users/mass-delete', [UserController::class, 'massDelete'])->name('users.mass-delete');
        Route::get('/admin/get-user-show-log/{id}', [UserController::class, 'getLoginSession'])->name('get-user-show-log');
        Route::get('/admin/get-user-show-log-activity/{id}', [UserController::class, 'getActivity'])->name('get-user-show-log-activity');
        Route::post('/admin/users/{id}/ban', [UserController::class, 'ban'])->name('users.ban');
        Route::post('/admin/users/{id}/unban', [UserController::class, 'unban'])->name('users.unban');

        Route::resource('/admin/roles', RoleController::class);
        Route::get('/admin/get-datarole', [RoleController::class, 'getDataRoles'])->name('get-datarole');
        Route::post('/admin/roles/mass-delete', [RoleController::class, 'massDelete'])->name('roles.mass-delete');
    });

    // ====================================================
    // HELP (Log Activity): view_help — Superadmin, admin
    // ====================================================
    Route::middleware('can:view_help')->group(function () {
        Route::resource('/admin/log-activity', LogActivityController::class);
        Route::get('/admin/get-datalogactivity', [LogActivityController::class, 'getDataLogActivity'])->name('get-datalogactivity');
    });

    // Akademik
    Route::prefix('admin/academic')->group(function () {
        Route::resource('schedules', \App\Http\Controllers\Backend\Academic\ScheduleController::class);
        Route::resource('attendances', \App\Http\Controllers\Backend\Academic\AttendanceController::class);
        Route::get('attendance-settings', [\App\Http\Controllers\Backend\Academic\AttendanceController::class, 'index'])->name('attendance-settings.index');
        Route::post('attendance-settings', [\App\Http\Controllers\Backend\Academic\AttendanceController::class, 'updateSettings'])->name('attendance-settings.update');
    });
});

// Student Portal Routes
Route::middleware(['auth', 'role:Siswa'])->prefix('portal')->group(function () {
    Route::get('/dashboard', [\App\Http\Controllers\Frontend\PortalController::class, 'dashboard'])->name('student.dashboard');
    Route::get('/attendance', [\App\Http\Controllers\Frontend\AttendancePortalController::class, 'index'])->name('student.attendance');
    Route::post('/attendance/submit', [\App\Http\Controllers\Frontend\AttendancePortalController::class, 'submit'])->name('student.attendance.submit');
    Route::get('/timetable', [\App\Http\Controllers\Frontend\AttendancePortalController::class, 'timetable'])->name('student.timetable');
    
    // Portal Profile
    Route::get('/my-account', [AccountController::class, 'index'])->name('student.account.index');
    Route::post('/my-account/send-otp', [AccountController::class, 'sendOtp'])->name('student.parent.send-otp');
    Route::post('/my-account/verify-otp', [AccountController::class, 'verifyOtp'])->name('student.parent.verify-otp');
    Route::get('/my-account/{id}/avatar', [AccountController::class, 'editAvatar'])->name('student.avatar-edit');
    Route::post('/my-account/{id}/update-avatar', [AccountController::class, 'updateAvatar'])->name('student.avatar-update');
    Route::resource('/my-profile', ProfileController::class, ['as' => 'student']);
    Route::resource('/my-security', SecurityController::class, ['as' => 'student']);

    Route::get('/learning-modules', [\App\Http\Controllers\Backend\Master\LearningModuleController::class, 'index'])->name('student.learning-modules.index');
    Route::get('/assignments', [\App\Http\Controllers\Backend\Master\AssignmentController::class, 'index'])->name('student.assignments.index');
    Route::get('/assignments/{id}', [\App\Http\Controllers\Backend\Master\AssignmentController::class, 'show'])->name('student.assignments.show');
    Route::post('/assignments/{id}/submit', [\App\Http\Controllers\Backend\Master\AssignmentController::class, 'submit'])->name('student.assignments.submit');
});

// Portal Routes for All Authenticated Users (Siswa, Guru, Superadmin)
Route::middleware(['auth'])->prefix('portal')->group(function () {
    // Pesan Internal (Chat)
    Route::get('/chat', [\App\Http\Controllers\Frontend\ChatController::class, 'index'])->name('student.chat.index');
    Route::get('/chat/{receiverId}', [\App\Http\Controllers\Frontend\ChatController::class, 'show'])->name('student.chat.show');
    Route::post('/chat', [\App\Http\Controllers\Frontend\ChatController::class, 'store'])->name('student.chat.store');

    // Detail Modul & Download (diakses oleh Guru/Admin dari backend)
    Route::get('/learning-modules/{id}', [\App\Http\Controllers\Backend\Master\LearningModuleController::class, 'show'])->name('student.learning-modules.show');
    Route::get('/learning-modules/{id}/download', [\App\Http\Controllers\Backend\Master\LearningModuleController::class, 'download'])->name('student.learning-modules.download');

    // Forum Diskusi Modul
    Route::post('/module-comments', [\App\Http\Controllers\Frontend\ModuleCommentController::class, 'store'])->name('module-comments.store');

    // Notifications
    Route::post('/notifications/mark-as-read', [\App\Http\Controllers\Frontend\NotificationController::class, 'markAllAsRead'])->name('notifications.mark-as-read');

    // Leaderboard & Badge (Accessible to Student, Teacher, Admin)
    Route::get('/leaderboard', [\App\Http\Controllers\Frontend\LeaderboardController::class, 'index'])->name('portal.leaderboard');
});

// Load Routes Authentication (Login, Register, Reset Password)
require __DIR__ . '/auth.php';
