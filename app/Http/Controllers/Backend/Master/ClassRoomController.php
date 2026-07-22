<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\ClassRoom;
use App\Models\School;
use Illuminate\Http\Request;

class ClassRoomController extends Controller
{
    public function index()
    {
        $classRooms = ClassRoom::with('school')->get();
        $schools = School::all();
        return view('backend.master.class-rooms.index', compact('classRooms', 'schools'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'school_id' => 'required|uuid|exists:schools,id',
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:50',
        ]);
        ClassRoom::create($data);
        return redirect()->back()->with('success', 'Kelas berhasil ditambahkan');
    }

    public function update(Request $request, $id)
    {
        $data = $request->validate([
            'school_id' => 'required|uuid|exists:schools,id',
            'name' => 'required|string|max:255',
            'level' => 'required|string|max:50',
        ]);
        $item = ClassRoom::findOrFail($id);
        $item->update($data);
        return redirect()->back()->with('success', 'Kelas berhasil diupdate');
    }

    public function destroy($id)
    {
        ClassRoom::findOrFail($id)->delete();
        return redirect()->back()->with('success', 'Kelas berhasil dihapus');
    }
}
