<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\ClassStudent;
use App\Models\ClassRoom;
use App\Models\Student;
use App\Models\AcademicYear;
use Illuminate\Http\Request;

class ClassStudentController extends Controller
{
    public function index(Request $request)
    {
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $activeAY = $academicYears->firstWhere('is_active', true);
        $tahunId = $request->input('academic_year_id') ?: $activeAY?->id;

        // Daftar dikelompokkan per KELAS, bukan daftar siswa memanjang: satu kartu
        // = satu rombel pada tahun ajaran terpilih, isinya dibuka lewat tombol.
        $enrollments = ClassStudent::with(['student.user', 'classRoom.school', 'academicYear'])
            ->when($tahunId, fn ($q) => $q->where('academic_year_id', $tahunId))
            ->get()
            ->sortBy(fn ($cs) => $cs->student->user->name ?? '')
            ->groupBy('class_room_id');

        $classRooms = ClassRoom::with('school')->orderBy('name')->get();

        // Siswa yang BELUM punya rombel di tahun terpilih — calon untuk diplot.
        $students = Student::whereDoesntHave('classStudents', function ($q) use ($tahunId) {
            if ($tahunId) {
                $q->where('academic_year_id', $tahunId);
            }
        })->with('user')->get()->sortBy(fn ($s) => $s->user->name ?? '')->values();

        return view('backend.master.enrollments.index', compact(
            'enrollments', 'classRooms', 'students', 'academicYears', 'activeAY', 'tahunId'
        ));
    }

    public function store(Request $request)
    {
        $request->validate([
            'class_room_id' => 'required',
            'academic_year_id' => 'required',
            'student_ids' => 'required|array|min:1',
        ], [
            'student_ids.required' => 'Centang minimal satu siswa untuk diplot.',
            'class_room_id.required' => 'Pilih kelas tujuan.',
            'academic_year_id.required' => 'Pilih tahun ajaran.',
        ]);

        try {
            foreach ($request->student_ids as $sid) {
                // Gunakan updateOrCreate untuk memindahkan siswa jika dia sudah ada di kelas lain di tahun yang sama
                ClassStudent::updateOrCreate(
                    [
                        'student_id' => $sid,
                        'academic_year_id' => $request->academic_year_id,
                    ],
                    [
                        'class_room_id' => $request->class_room_id
                    ]
                );
            }

            return redirect()->back()->with('success', count($request->student_ids) . ' Siswa berhasil dimasukkan ke dalam kelas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memplot siswa: ' . $e->getMessage());
        }
    }

    /**
     * Alat NAIK KELAS.
     *
     * Menaikkan kelas TIDAK mengganti rombel tahun berjalan: ia MENAMBAH baris
     * baru untuk tahun ajaran tujuan, sementara baris tahun asal dibiarkan utuh.
     * Itulah yang membuat riwayat kelas (dan nilai ujian yang menempel pada
     * penugasan guru tahun itu) tetap bisa ditelusuri.
     *
     * Di dalam SATU tahun ajaran, siswa hanya boleh punya satu rombel — dijaga
     * oleh updateOrCreate berkunci (siswa, tahun) dan batasan unik di basis data.
     */
    public function promoteForm(Request $request)
    {
        $academicYears = AcademicYear::orderByDesc('name')->get();
        $classRooms = ClassRoom::with('school')->orderBy('name')->get();

        $dariTahun = $request->input('from_academic_year_id');
        $dariKelas = $request->input('from_class_room_id');

        $kandidat = ($dariTahun && $dariKelas)
            ? ClassStudent::with('student.user')
                ->where('academic_year_id', $dariTahun)
                ->where('class_room_id', $dariKelas)
                ->get()
                ->sortBy(fn ($cs) => $cs->student->user->name ?? '')
                ->values()
            : collect();

        return view('backend.master.enrollments.promote', compact(
            'academicYears', 'classRooms', 'dariTahun', 'dariKelas', 'kandidat'
        ));
    }

    public function promote(Request $request)
    {
        $data = $request->validate([
            'from_academic_year_id' => 'required|uuid|exists:academic_years,id',
            'from_class_room_id' => 'required|uuid|exists:class_rooms,id',
            'to_academic_year_id' => 'required|uuid|exists:academic_years,id|different:from_academic_year_id',
            'to_class_room_id' => 'required|uuid|exists:class_rooms,id',
            'student_ids' => 'required|array|min:1',
        ], [
            'to_academic_year_id.different' => 'Tahun ajaran tujuan harus berbeda dari tahun asal — '
                . 'kalau hanya ingin memindah kelas di tahun yang sama, pakai Plotting Siswa.',
            'student_ids.required' => 'Pilih minimal satu siswa.',
        ]);

        $jumlah = 0;
        foreach ($data['student_ids'] as $sid) {
            ClassStudent::updateOrCreate(
                ['student_id' => $sid, 'academic_year_id' => $data['to_academic_year_id']],
                ['class_room_id' => $data['to_class_room_id']]
            );
            $jumlah++;
        }

        $kelas = ClassRoom::find($data['to_class_room_id'])?->name;
        $tahun = AcademicYear::find($data['to_academic_year_id'])?->name;

        return redirect()->route('enrollments.index', ['academic_year_id' => $data['to_academic_year_id']])
            ->with('success', "$jumlah siswa dinaikkan ke $kelas tahun $tahun. Rombel tahun sebelumnya tetap tersimpan sebagai riwayat.");
    }

    public function destroy($id)
    {
        try {
            ClassStudent::findOrFail($id)->delete();
            return redirect()->back()->with('success', 'Siswa berhasil dikeluarkan dari kelas.');
        } catch (\Exception $e) {
            return redirect()->back()->with('error', 'Gagal memproses data: ' . $e->getMessage());
        }
    }
}
