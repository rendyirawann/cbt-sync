<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Traits\ValidatesMasterData;
use Illuminate\Http\Request;

class AcademicYearController extends Controller
{
    use ValidatesMasterData;
    public function index()
    {
        $academicYears = AcademicYear::all();
        return view('backend.master.academic-years.index', compact('academicYears'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        AcademicYear::create($data);
        return redirect()->back()->with('success', 'Tahun Ajaran berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $item = AcademicYear::findOrFail($id);
        $data = $this->validated($request);
        $item->update($data);
        return redirect()->back()->with('success', 'Tahun Ajaran berhasil diupdate');
    }

    private function validated(Request $request): array
    {
        $data = $request->validate([
            'name' => 'required|string|max:255',
            'semester' => 'required|string|max:50',
        ], $this->idMessages(), ['name' => 'Nama Tahun Ajaran', 'semester' => 'Semester']);
        $data['is_active'] = $request->boolean('is_active');
        return $data;
    }

    public function destroy($id)
    {
        AcademicYear::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Tahun Ajaran berhasil dihapus');
    }
}
