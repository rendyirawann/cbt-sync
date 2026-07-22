<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;
    public function index()
    {
        $subjects = Subject::all();
        return view('backend.master.subjects.index', compact('subjects'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());
        Subject::create($data);
        return redirect()->back()->with('success', 'Pelajaran berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $item = Subject::findOrFail($id);
        $data = $request->validate($this->rules($id), $this->idMessages(), $this->labels());
        $item->update($data);
        return redirect()->back()->with('success', 'Pelajaran berhasil diupdate');
    }

    private function rules($ignoreId = null): array
    {
        return [
            'code' => ['required', 'string', 'max:50', Rule::unique('subjects', 'code')->ignore($ignoreId)],
            'name' => 'required|string|max:255',
        ];
    }

    private function labels(): array
    {
        return ['code' => 'Kode Mapel', 'name' => 'Nama Mapel'];
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

        $rules = ['code' => 'required|string|max:50', 'name' => 'required|string|max:255'];
        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $this->labels());
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }
            if (Subject::where('code', $row['code'])->exists()) { $skipped++; continue; }
            try { Subject::create(['code' => $row['code'], 'name' => $row['name']]); $imported++; }
            catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA MATA PELAJARAN',
            'file' => 'Template_Mata_Pelajaran.xlsx',
            'guide' => ['Kode mapel harus unik; kode yang sudah ada akan dilewati.'],
            'columns' => [
                ['key' => 'code', 'label' => 'Kode Mapel', 'required' => true, 'width' => 18, 'hint' => 'mis. MTK, BIG'],
                ['key' => 'name', 'label' => 'Nama Mapel', 'required' => true, 'width' => 40],
            ],
            'examples' => [
                ['code' => 'MTK', 'name' => 'Matematika'],
                ['code' => 'BIG', 'name' => 'Bahasa Inggris'],
            ],
        ];
    }

    public function destroy($id)
    {
        Subject::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Pelajaran berhasil dihapus');
    }
}
