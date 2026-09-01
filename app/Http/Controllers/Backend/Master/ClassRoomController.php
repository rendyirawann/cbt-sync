<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Traits\ValidatesMasterData;
use App\Traits\ExcelMasterTemplate;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class ClassRoomController extends Controller
{
    use ValidatesMasterData, ExcelMasterTemplate;

    public function index()
    {
        $sid = \App\Support\SchoolScope::id();
        $classRooms = ClassRoom::with('school')
            ->when($sid, fn ($q) => $q->where('school_id', $sid))
            ->get();
        $schools = $sid ? School::where('id', $sid)->get() : School::all();
        return view('backend.master.class-rooms.index', compact('classRooms', 'schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());
        if ($sid = \App\Support\SchoolScope::id()) {
            $data['school_id'] = $sid;   // Admin sekolah tidak bisa buat kelas untuk sekolah lain
        }
        ClassRoom::create($data);
        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());
        $item = ClassRoom::findOrFail($id);
        $item->update($data);
        return redirect()->back()->with('success', 'Kelas berhasil diupdate');
    }

    private function rules(): array
    {
        return [
            'school_id' => 'required|uuid|exists:schools,id',
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:50',
        ];
    }

    private function labels(): array
    {
        return ['school_id' => 'Sekolah', 'name' => 'Nama Kelas', 'level' => 'Tingkat/Level'];
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

        // Admin sekolah: semua kelas dipaksa ke sekolahnya (kolom Sekolah di file diabaikan).
        $sid = \App\Support\SchoolScope::id();
        $rules = ['school' => ($sid ? 'nullable' : 'required') . '|string', 'name' => 'required|string|max:255', 'level' => 'required|string|max:50'];
        $labels = ['school' => 'Nama Sekolah', 'name' => 'Nama Kelas', 'level' => 'Tingkat/Level'];
        $imported = 0; $skipped = 0; $errors = [];
        foreach ($rows as $row) {
            $line = $row['_row']; unset($row['_row']);
            $v = Validator::make($row, $rules, $this->idMessages(), $labels);
            if ($v->fails()) { $errors[] = "Baris $line: " . $v->errors()->first(); continue; }
            $school = $sid ? School::find($sid) : School::where('name', $row['school'])->first();
            if (!$school) { $errors[] = "Baris $line: Sekolah \"{$row['school']}\" tidak ditemukan."; continue; }
            if (ClassRoom::where('school_id', $school->id)->where('name', $row['name'])->exists()) { $skipped++; continue; }
            try { ClassRoom::create(['school_id' => $school->id, 'name' => $row['name'], 'level' => $row['level']]); $imported++; }
            catch (\Throwable $e) { $errors[] = "Baris $line: gagal disimpan."; }
        }
        return $this->importSummary($imported, $skipped, $errors);
    }

    private function spec(): array
    {
        return [
            'title' => 'DATA RUANG KELAS',
            'file' => 'Template_Ruang_Kelas.xlsx',
            'guide' => ['Nama Sekolah harus sudah terdaftar (persis sama). Kelas yang sudah ada di sekolah itu dilewati.'],
            'columns' => [
                ['key' => 'school', 'label' => 'Nama Sekolah', 'required' => true, 'width' => 36, 'hint' => 'harus sudah ada di Data Sekolah'],
                ['key' => 'name', 'label' => 'Nama Kelas', 'required' => true, 'width' => 22, 'hint' => 'mis. X-IPA 1'],
                ['key' => 'level', 'label' => 'Tingkat/Level', 'required' => true, 'width' => 16, 'hint' => 'mis. 10'],
            ],
            'examples' => [
                ['school' => 'SMA Negeri 1 Medan', 'name' => 'X-IPA 1', 'level' => '10'],
                ['school' => 'SMA Negeri 1 Medan', 'name' => 'XI-IPS 2', 'level' => '11'],
            ],
        ];
    }

    public function destroy($id)
    {
        ClassRoom::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus');
    }
}
