<?php

namespace App\Http\Controllers\Frontend;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use App\Models\Schedule;
use App\Models\TeachingAssignment;
use App\Services\ParentNotificationService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class AttendancePortalController extends Controller
{
    public function index()
    {
        $user = Auth::user();
        $today = Carbon::today();
        $now = Carbon::now();
        $dayOfWeek = $now->dayOfWeekIso; // 1-7

        $settings = AttendanceSetting::first();
        if (!$settings) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Pengaturan absensi belum dikonfigurasi oleh administrator.');
        }

        // Get Student and their Class
        $student = $user->student;
        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Profil siswa tidak ditemukan.');
        }
        $classIds = $student->classStudents->pluck('class_room_id');

        // Check Daily Attendance (Datang/Pulang)
        $arrival = Attendance::where('user_id', $user->id)
            ->where('type', 'datang')
            ->whereDate('created_at', $today)
            ->first();

        $departure = Attendance::where('user_id', $user->id)
            ->where('type', 'pulang')
            ->whereDate('created_at', $today)
            ->first();

        // Get Schedules for Today based on Student's Classes
        $schedules = Schedule::with(['teachingAssignment.subject', 'teachingAssignment.teacher.user'])
            ->whereIn('teaching_assignment_id', function($query) use ($classIds) {
                $query->select('id')
                    ->from('teaching_assignments')
                    ->whereIn('class_room_id', $classIds);
            })
            ->where('day_of_week', $dayOfWeek)
            ->where('is_active', true)
            ->orderBy('start_time', 'asc')
            ->get();

        // Preload today's "mapel" attendances once (hindari N+1 di dalam loop).
        $attendedToday = Attendance::where('user_id', $user->id)
            ->where('type', 'mapel')
            ->whereDate('created_at', $today)
            ->get()
            ->keyBy('schedule_id');

        // Check which subjects have been attended
        foreach ($schedules as $key => $s) {
            $s->is_attended = $attendedToday->get($s->id);

            $startTime = Carbon::createFromFormat('H:i:s', $s->start_time);
            $endTime = Carbon::createFromFormat('H:i:s', $s->end_time);

            // Logic: Can attend until next schedule starts
            $nextS = $schedules->get($key + 1);
            $limitTime = $nextS ? Carbon::createFromFormat('H:i:s', $nextS->start_time) : $endTime->copy()->addHours(2);

            $s->can_attend = $now->between($startTime, $limitTime);
            $s->is_late = $now->gt($limitTime);
            $s->is_late_submission = $now->gt($endTime) && $now->lte($limitTime);
        }

        // Arrival logic for view: can attend until departure starts
        $arrivalStart = Carbon::createFromFormat('H:i:s', $settings->arrival_start);
        $departureStart = Carbon::createFromFormat('H:i:s', $settings->departure_start);
        $canAttendArrival = $now->between($arrivalStart, $departureStart);
        $isArrivalLate = $now->gt(Carbon::createFromFormat('H:i:s', $settings->arrival_end)) && $now->lte($departureStart);

        $history = Attendance::with(['schedule.teachingAssignment.subject'])
            ->where('user_id', $user->id)
            ->orderBy('created_at', 'desc')
            ->get();

        return view('frontend.attendance.index', compact('settings', 'arrival', 'departure', 'schedules', 'now', 'canAttendArrival', 'isArrivalLate', 'history'));
    }

    public function submit(Request $request)
    {
        $user = Auth::user();
        $type = $request->type;
        $now = Carbon::now();
        $today = Carbon::today();
        $settings = AttendanceSetting::first();

        if (!$settings) {
            return response()->json(['message' => 'Pengaturan absensi belum dikonfigurasi. Hubungi administrator.'], 422);
        }

        if ($type == 'datang') {
            // Check if already attended
            $existing = Attendance::where('user_id', $user->id)->where('type', 'datang')->whereDate('created_at', $today)->first();
            if ($existing) {
                return response()->json(['message' => 'Anda sudah melakukan absen datang hari ini.'], 422);
            }

            $start = Carbon::createFromFormat('H:i:s', $settings->arrival_start);
            $end = Carbon::createFromFormat('H:i:s', $settings->arrival_end);
            $departureStart = Carbon::createFromFormat('H:i:s', $settings->departure_start);

            if ($now->lt($start)) {
                return response()->json(['message' => 'Absen datang belum dimulai. Silahkan absen mulai jam ' . $settings->arrival_start], 422);
            }

            if ($now->gt($departureStart)) {
                return response()->json(['message' => 'Sudah melewati batas waktu absen datang hari ini.'], 422);
            }

            $status = 'hadir';
            $lateMinutes = 0;

            if ($now->gt($end)) {
                $status = 'terlambat';
                $lateMinutes = $now->diffInMinutes($end);
            }

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'type' => 'datang',
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'attended_at' => $now,
                'notes' => $status == 'terlambat' ? 'Terlambat ' . $lateMinutes . ' menit' : null
            ]);

            // Send notification to parent
            (new ParentNotificationService())->notifyAttendance($attendance);

        } 
        elseif ($type == 'pulang') {
            $existing = Attendance::where('user_id', $user->id)->where('type', 'pulang')->whereDate('created_at', $today)->first();
            if ($existing) {
                return response()->json(['message' => 'Anda sudah melakukan absen pulang hari ini.'], 422);
            }

            $arrival = Attendance::where('user_id', $user->id)->where('type', 'datang')->whereDate('created_at', $today)->first();
            if (!$arrival) {
                return response()->json(['message' => 'Anda belum melakukan absen datang hari ini.'], 422);
            }

            $start = Carbon::createFromFormat('H:i:s', $settings->departure_start);
            if ($now->lt($start)) {
                return response()->json(['message' => 'Absen pulang baru bisa dilakukan setelah jam ' . $settings->departure_start], 422);
            }

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'type' => 'pulang',
                'status' => 'hadir',
                'attended_at' => $now
            ]);

            // Send notification to parent
            (new ParentNotificationService())->notifyAttendance($attendance);

        } 
        elseif ($type == 'mapel') {
            $scheduleId = $request->schedule_id;
            $schedule = Schedule::findOrFail($scheduleId);
            
            // Check if already attended
            $existing = Attendance::where('user_id', $user->id)
                ->where('type', 'mapel')
                ->where('schedule_id', $scheduleId)
                ->whereDate('created_at', $today)
                ->first();
            if ($existing) {
                return response()->json(['message' => 'Anda sudah absen untuk mata pelajaran ini.'], 422);
            }

            $startTime = Carbon::createFromFormat('H:i:s', $schedule->start_time);
            $endTime = Carbon::createFromFormat('H:i:s', $schedule->end_time);

            // Find next schedule to determine limit
            $student = $user->student;
            if (!$student) {
                return response()->json(['message' => 'Profil siswa tidak ditemukan.'], 422);
            }
            $classIds = $student->classStudents->pluck('class_room_id');
            $dayOfWeek = $now->dayOfWeekIso;

            $nextSchedule = Schedule::whereIn('teaching_assignment_id', function($query) use ($classIds) {
                    $query->select('id')
                        ->from('teaching_assignments')
                        ->whereIn('class_room_id', $classIds);
                })
                ->where('day_of_week', $dayOfWeek)
                ->where('start_time', '>', $schedule->start_time)
                ->where('is_active', true)
                ->orderBy('start_time', 'asc')
                ->first();

            $limitTime = $nextSchedule ? Carbon::createFromFormat('H:i:s', $nextSchedule->start_time) : $endTime->copy()->addHours(2); // Fallback limit

            if ($now->lt($startTime)) {
                return response()->json(['message' => 'Jadwal mata pelajaran belum dimulai.'], 422);
            }

            if ($now->gt($limitTime)) {
                return response()->json(['message' => 'Sudah melewati batas waktu absen. Anda terhitung tidak hadir untuk mata pelajaran ini.'], 422);
            }

            $status = 'hadir';
            $lateMinutes = 0;

            if ($now->gt($endTime)) {
                $status = 'terlambat';
                $lateMinutes = $now->diffInMinutes($endTime);
            }

            $attendance = Attendance::create([
                'user_id' => $user->id,
                'type' => 'mapel',
                'schedule_id' => $scheduleId,
                'status' => $status,
                'late_minutes' => $lateMinutes,
                'attended_at' => $now,
                'notes' => $status == 'terlambat' ? 'Terlambat ' . $lateMinutes . ' menit' : null
            ]);

            // Send notification to parent
            (new ParentNotificationService())->notifyAttendance($attendance);
        }

        return response()->json(['message' => 'Absensi berhasil tercatat!']);
    }

    public function timetable()
    {
        $user = Auth::user();
        $student = $user->student;
        if (!$student) {
            return redirect()->route('student.dashboard')
                ->with('error', 'Profil siswa tidak ditemukan.');
        }
        $classIds = $student->classStudents->pluck('class_room_id');

        $schedules = Schedule::with(['teachingAssignment.subject', 'teachingAssignment.teacher.user', 'teachingAssignment.classRoom'])
            ->whereIn('teaching_assignment_id', function($query) use ($classIds) {
                $query->select('id')
                    ->from('teaching_assignments')
                    ->whereIn('class_room_id', $classIds);
            })
            ->where('is_active', true)
            ->orderBy('day_of_week')
            ->orderBy('start_time')
            ->get()
            ->groupBy('day_of_week');

        return view('frontend.attendance.timetable', compact('schedules'));
    }
}
