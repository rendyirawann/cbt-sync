<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\AcademicYear;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class AcademicYearController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;
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

    public function template()
    {
        return $this->downloadExcelTemplate($this->spec());
    }

    public function import(Request $request)
    {
        $request->validate(['file' => 'required|file|mimes:xlsx,xls|max:8192'], $this->idMessages(), ['file' => 'Berkas Excel']);
        try {
            $rows = $this->readExcelRows($request->file('file'), $this->spec()['columns']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membaca Excel: ' . $e->getMessage());
        }

        $rules = ['name' => 'required|string|max:255', 'semester' => 'required|string|max:50'];
        $labels = ['name' => 'Nama Tahun Ajaran', 'semester' => 'Semester'];
        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $labels);
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }
            if (AcademicYear::where('name', $row['name'])->where('semester', $row['semester'])->exists()) { $skipped++; continue; }
            try {
                AcademicYear::create([
                    'name' => $row['name'],
                    'semester' => $row['semester'],
                    'is_active' => in_array(strtolower($row['is_active'] ?? ''), ['ya', 'aktif', '1', 'true', 'y']),
                ]);
                $imported++;
            } catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA TAHUN AJARAN',
            'file' => 'Template_Tahun_Ajaran.xlsx',
            'guide' => ['Kombinasi Nama + Semester yang sudah ada akan dilewati.'],
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Tahun Ajaran', 'required' => true, 'width' => 26, 'hint' => 'mis. 2025/2026'],
                ['key' => 'semester', 'label' => 'Semester', 'required' => true, 'width' => 18, 'options' => ['Ganjil', 'Genap']],
                ['key' => 'is_active', 'label' => 'Aktif', 'width' => 12, 'options' => ['Ya', 'Tidak'], 'hint' => 'Ya = tahun ajaran berjalan'],
            ],
            'examples' => [
                ['name' => '2025/2026', 'semester' => 'Ganjil', 'is_active' => 'Ya'],
                ['name' => '2025/2026', 'semester' => 'Genap', 'is_active' => 'Tidak'],
            ],
        ];
    }

    public function destroy($id)
    {
        AcademicYear::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Tahun Ajaran berhasil dihapus');
    }
}
