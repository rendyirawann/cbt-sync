<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Subject;
use App\Traits\ValidatesMasterData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class SubjectController extends Controller
{
    use ValidatesMasterData;
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

    public function destroy($id)
    {
        Subject::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Pelajaran berhasil dihapus');
    }
}
