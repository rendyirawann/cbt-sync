<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\Wave;
use App\Traits\ValidatesMasterData;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Master Gelombang — giliran waktu pelaksanaan ujian (pagi/siang) yang memakai
 * ruang komputer yang sama. Global seperti Mata Pelajaran, bukan per sekolah.
 */
class WaveController extends Controller
{
    use ValidatesMasterData;

    public function index()
    {
        $waves = Wave::terurut()->withCount('students')->get();
        return view('backend.master.waves.index', compact('waves'));
    }

    public function store(Request $request)
    {
        $data = $request->validate($this->rules(), $this->pesan(), $this->labels());
        $data['is_active'] = $request->has('is_active');
        Wave::create($data);
        return redirect()->back()->with('success', 'Gelombang berhasil ditambahkan.');
    }

    public function update(Request $request, $id)
    {
        $wave = Wave::findOrFail($id);
        $data = $request->validate($this->rules($id), $this->pesan(), $this->labels());
        $data['is_active'] = $request->has('is_active');
        $wave->update($data);
        return redirect()->back()->with('success', 'Gelombang berhasil diperbarui.');
    }

    public function destroy($id)
    {
        $wave = Wave::withCount('students')->findOrFail($id);

        // Gelombang yang masih dipakai tidak dihapus: kartu login peserta
        // mencetak nama gelombang, jadi menghapusnya akan mengosongkan kartu.
        if ($wave->students_count > 0) {
            return redirect()->back()->with('error',
                "Gelombang \"{$wave->name}\" masih dipakai {$wave->students_count} siswa. "
                . 'Pindahkan siswa itu ke gelombang lain dulu, atau nonaktifkan gelombang ini.');
        }

        $wave->delete();
        return redirect()->back()->with('success', 'Gelombang berhasil dihapus.');
    }

    private function rules($ignoreId = null): array
    {
        return [
            'name' => ['required', 'string', 'max:100', Rule::unique('waves', 'name')->ignore($ignoreId)],
            'sort_order' => 'nullable|integer|min:0|max:999',
            'start_time' => 'nullable|date_format:H:i',
            'end_time' => 'nullable|date_format:H:i|after:start_time',
        ];
    }

    private function pesan(): array
    {
        return $this->idMessages() + [
            'end_time.after' => 'Jam Selesai harus lebih besar dari Jam Mulai.',
            'start_time.date_format' => 'Jam Mulai harus berformat jam, mis. 07:30.',
            'end_time.date_format' => 'Jam Selesai harus berformat jam, mis. 09:40.',
        ];
    }

    private function labels(): array
    {
        return [
            'name' => 'Nama Gelombang', 'sort_order' => 'Urutan',
            'start_time' => 'Jam Mulai', 'end_time' => 'Jam Selesai',
        ];
    }
}
