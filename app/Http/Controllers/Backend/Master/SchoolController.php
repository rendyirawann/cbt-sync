<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class SchoolController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;

    public function index()
    {
        $sid = \App\Support\SchoolScope::id();
        $schools = $sid ? School::where('id', $sid)->get() : School::all();
        return view('backend.master.schools.index', compact('schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());
        School::create($data);
        return redirect()->back()->with('success', 'Sekolah berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $item = School::findOrFail($id);
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());
        $item->update($data);
        return redirect()->back()->with('success', 'Sekolah berhasil diupdate');
    }

    private function rules(): array
    {
        return [
            'name' => 'required|string|max:255',
            'address' => 'nullable|string|max:1000',
            'phone' => 'nullable|string|max:50',
            'email' => 'nullable|email|max:255',
        ];
    }

    private function labels(): array
    {
        return ['name' => 'Nama Sekolah', 'address' => 'Alamat', 'phone' => 'Telepon', 'email' => 'Email'];
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

        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $this->rules(), $this->idMessages(), $this->labels());
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }
            if (School::where('name', $row['name'])->exists()) { $skipped++; continue; }
            try { School::create($v->validated()); $imported++; }
            catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA SEKOLAH',
            'file' => 'Template_Data_Sekolah.xlsx',
            'guide' => ['Nama sekolah yang sudah ada akan dilewati (tidak dobel).'],
            'columns' => [
                ['key' => 'name', 'label' => 'Nama Sekolah', 'required' => true, 'width' => 40],
                ['key' => 'address', 'label' => 'Alamat', 'width' => 40],
                ['key' => 'phone', 'label' => 'Telepon', 'width' => 20],
                ['key' => 'email', 'label' => 'Email', 'width' => 28],
            ],
            'examples' => [
                ['name' => 'SMA Negeri 1 Medan', 'address' => 'Jl. Cik Ditiro No.1', 'phone' => '061-1234567', 'email' => 'sman1@sch.id'],
                ['name' => 'SMK Telkom Medan', 'address' => 'Jl. Gatot Subroto', 'phone' => '061-7654321', 'email' => 'smktelkom@sch.id'],
            ],
        ];
    }

    public function destroy($id)
    {
        School::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Sekolah berhasil dihapus');
    }
}
