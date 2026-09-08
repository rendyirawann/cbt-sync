<?php

namespace App\Http\Controllers\Backend\Master;

use App\Http\Controllers\Controller;
use App\Models\QuestionBank;
use App\Models\Subject;
use Illuminate\Http\Request;

/**
 * Bank Soal Bersama — soal reusable LINTAS SEKOLAH (pengecualian dari batas sekolah).
 * Guru mana pun bisa menambah & menarik soal ke ujiannya.
 */
class QuestionBankController extends Controller
{
    public function index(Request $request)
    {
        // Sekolah akun yang sedang melihat; null untuk Developer (tak discope).
        $sekolahSaya = \App\Support\SchoolScope::id();

        // Filter yang sama dipakai dua kali: untuk daftar kelompok dan untuk isinya.
        // Gerbang kebocoran soal ikut di dalamnya supaya tidak mungkin terlewat
        // di salah satu kueri (lihat QuestionBank::scopeTerlihatOleh).
        $filter = fn ($q) => $q
            ->terlihatOleh($sekolahSaya)
            ->when($request->filled('school_id'), fn ($x) => $x->where('school_id', $request->school_id))
            ->when($request->filled('subject_id'), fn ($x) => $x->where('subject_id', $request->subject_id))
            ->when($request->filled('level'), fn ($x) => $x->where('level', $request->level))
            ->when($request->filled('type'), fn ($x) => $x->where('type', $request->type))
            ->when($request->filled('search'), fn ($x) => $x->where('question_text', 'ilike', '%' . $request->search . '%'));

        // Bank ini memang lintas sekolah (tidak discope): filter sekolah dipakai
        // untuk MELIHAT soal buatan sekolah tertentu, bukan untuk membatasi akses.
        // Yang dipaginasi adalah KELOMPOK ujian, bukan soal satu per satu.
        $groups = QuestionBank::query()
            ->selectRaw('source_exam_id, source_exam_title, count(*) as jumlah, max(created_at) as terakhir')
            ->tap($filter)
            ->groupBy('source_exam_id', 'source_exam_title')
            ->orderByDesc('terakhir')
            ->paginate(10)
            ->withQueryString();

        // Isi seluruh kelompok di halaman ini diambil sekali jalan, lalu dipetakan
        // per ujian agar tampilan tidak melakukan kueri di dalam loop.
        $idUjian = $groups->pluck('source_exam_id');
        $items = QuestionBank::with(['subject', 'options', 'creator', 'school', 'sourceSchool'])
            ->tap($filter)
            ->where(function ($q) use ($idUjian) {
                $q->whereIn('source_exam_id', $idUjian->filter()->values()->all());
                if ($idUjian->contains(null)) {
                    $q->orWhereNull('source_exam_id');
                }
            })
            ->orderBy('created_at')
            ->get()
            ->groupBy(fn ($b) => $b->source_exam_id ?? 'tanpa');

        $subjects = Subject::orderBy('name')->get();
        $levels = QuestionBank::whereNotNull('level')->distinct()->orderBy('level')->pluck('level');
        $schools = \App\Models\School::orderBy('name')->get();

        // Untuk keterangan di layar: apakah tampilan sedang dibatasi gerbang status?
        $adaGerbang = ! \App\Support\SiklusUjian::pengawas();

        return view('backend.master.question-banks.index', compact(
            'groups', 'items', 'subjects', 'levels', 'schools', 'adaGerbang'
        ));
    }

    /**
     * Pratinjau satu soal bank (baca saja) untuk modal.
     *
     * Terbuka untuk semua peran yang boleh membuka Bank Soal — termasuk Kepala
     * Sekolah yang tidak boleh mengubah — tetapi tetap melewati gerbang
     * keterlihatan yang sama, jadi soal sekolah lain yang belum boleh dilihat
     * tidak bisa dibaca dengan menebak id.
     */
    public function pratinjau($id)
    {
        $bank = QuestionBank::with('options')
            ->terlihatOleh(\App\Support\SchoolScope::id())
            ->whereKey($id)
            ->firstOrFail();

        return response()->json([
            'html' => view('backend.master.question-banks._pratinjau', ['bank' => $bank])->render(),
        ]);
    }
}
