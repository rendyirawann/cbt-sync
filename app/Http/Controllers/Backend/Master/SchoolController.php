<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\School;
use App\Traits\ValidatesMasterData;
use Illuminate\Http\Request;

class SchoolController extends Controller
{
    use ValidatesMasterData;

    public function index()
    {
        $schools = School::all();
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

    public function destroy($id)
    {
        School::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Sekolah berhasil dihapus');
    }
}
