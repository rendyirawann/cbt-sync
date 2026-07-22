<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use App\Traits\ValidatesMasterData;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{
    use ValidatesMasterData;

    public function index()
    {
        $classRooms = ClassRoom::with('school')->get();
        $schools = School::all();
        return view('backend.master.class-rooms.index', compact('classRooms', 'schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->idMessages(), $this->labels());
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

    public function destroy($id)
    {
        ClassRoom::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus');
    }
}
