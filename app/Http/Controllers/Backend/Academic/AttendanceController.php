<?php

namespace App\Http\Controllers\Backend\Academic;

use App\Http\Controllers\Controller;
use App\Models\Attendance;
use App\Models\AttendanceSetting;
use Illuminate\Http\Request;

class AttendanceController extends Controller
{
    public function index()
    {
        $settings = AttendanceSetting::first();
        if (!$settings) {
            $settings = AttendanceSetting::create([
                'arrival_start' => '06:00',
                'arrival_end' => '08:00',
                'departure_start' => '16:00'
            ]);
        }

        $attendances = Attendance::with(['user', 'schedule.teachingAssignment.subject'])
            ->orderBy('created_at', 'desc')
            ->paginate(20);

        return view('backend.academic.attendances.index', compact('settings', 'attendances'));
    }

    public function updateSettings(Request $request)
    {
        $request->validate([
            'arrival_start' => 'required',
            'arrival_end' => 'required|after:arrival_start',
            'departure_start' => 'required',
        ]);

        $settings = AttendanceSetting::first();
        $settings->update($request->all());

        return redirect()->back()->with('success', 'Pengaturan absensi berhasil diperbarui.');
    }
}
