<?php

namespace App\Http\Controllers\Backend\Help;


use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use Spatie\Activitylog\Models\Activity;
use Carbon\Carbon;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Yajra\DataTables\Facades\DataTables;

class LogActivityController extends Controller implements HasMiddleware
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    // function __construct()
    // {
    //     // $this->middleware(['auth']);
    //     // $this->middleware('permission:logactivity-list', ['only' => ['index','getDataLogActivity']]);
    //     // $this->middleware('permission:logactivity-show', ['only' => ['show']]);
    // }
    public static function middleware(): array
    {
        return [
            // 'auth', // Tidak perlu ditulis jika sudah ada di web.php

            // Contoh jika mau pakai permission nanti:
            // new Middleware('permission:logactivity-list', only: ['index', 'getDataLogActivity']),
            // new Middleware('permission:logactivity-show', only: ['show']),
        ];
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index(Request $request)
    {
        return view('backend.help.log_activity.index');
    }

    public function getDataLogActivity(Request $request)
    {
        $searchValue = $request->search['value'] ?? null;
        $user = auth()->user(); // Ambil user yang sedang login

        // Query Utama
        $postsQuery = Activity::with('causer')
            ->select('activity_log.*')
            ->orderBy('created_at', 'desc');

        // Cakupan per peran: Developer=semua; Superadmin=semua kecuali Developer;
        // Admin=semua kecuali Superadmin & Developer; Guru & Siswa=hanya dirinya.
        // Lihat App\Services\ActivityScope.
        app(\App\Services\ActivityScope::class)->terapkan($postsQuery, $user);

        // Pencarian Manual
        if (!empty($searchValue)) {
            $postsQuery->where(function ($query) use ($searchValue) {
                $query->where('log_name', 'LIKE', "%{$searchValue}%")
                    ->orWhere('description', 'LIKE', "%{$searchValue}%");
            });
        }

        // Gunakan DataTables
        return DataTables::of($postsQuery)
            ->addIndexColumn()
            ->addColumn('causer_id', function ($data) {
                return $data->causer->name ?? 'System';
            })
            ->addColumn('created_at', function ($data) {
                return Carbon::parse($data->created_at)
                    ->timezone('Asia/Jakarta')
                    ->translatedFormat('d F Y H:i:s');
            })
            // Kolom IP
            ->addColumn('ip', function ($data) {
                $props = $data->properties ?? [];
                return $props['ip'] ?? '-';
            })
            // Kolom OS
            ->addColumn('os', function ($data) {
                $props   = $data->properties ?? [];
                $agent   = $props['agent'] ?? [];
                $os      = $agent['os'] ?? '-';
                $browser = $agent['browser'] ?? '-';
                return $os . ' - ' . $browser;
            })
            // Kolom Device
            ->addColumn('device', function ($data) {
                $props = $data->properties ?? [];
                $agent = $props['agent'] ?? [];

                $isDesktop = $agent['is_desktop'] ?? false;
                $isMobile  = $agent['is_mobile'] ?? false;
                $deviceRaw = $agent['device'] ?? 'Unknown';

                if ($isDesktop) {
                    return '<i class="ki-outline ki-screen text-primary me-2"></i>Desktop';
                }

                if ($isMobile) {
                    return '<i class="ki-outline ki-phone text-warning me-2"></i>Mobile';
                }

                return '<i class="ki-outline ki-question-2 text-danger me-2"></i>' . $deviceRaw;
            })
            ->addColumn('aksi', function ($data) {
                return '<button type="button" class="btn btn-sm btn-light-primary btn-detail-log" data-id="'
                    . $data->id . '"><i class="ki-outline ki-eye fs-5"></i> Detail</button>';
            })
            ->rawColumns(['causer_id', 'log_name', 'created_at', 'ip', 'os', 'device', 'aksi'])
            ->make(true);
    }

    public function show($id)
    {
        $data = Activity::findOrFail($id);
        return view('backend.help.log_activity.show', compact('data'));
    }

    /**
     * Kosongkan seluruh Log Aktivitas.
     *
     * Catatan login/logout TIDAK berada di tabel terpisah — "My Login Session"
     * membaca activity_log yang sama dengan log_name 'login'/'logout'. Jadi satu
     * pembersihan ini mencakup keduanya, dan tidak ada yang tertinggal.
     *
     * Dibatasi ke PENGAWAS (Superadmin & Developer): isi log ini adalah jejak
     * audit, dan kemampuan menghapusnya sendiri termasuk kemampuan menutupi
     * jejak. Dihapus dengan truncate karena jumlahnya bisa ratusan ribu baris
     * dan DELETE baris-per-baris akan lama sekali; tabelnya tidak dirujuk
     * foreign key mana pun sehingga truncate aman.
     */
    public function bersihkan(Request $request)
    {
        if (! \App\Support\SiklusUjian::pengawas()) {
            return redirect()->back()->with('error',
                'Hanya Superadmin atau Developer yang boleh membersihkan Log Aktivitas.');
        }

        $jumlah = Activity::count();
        $rincian = Activity::query()
            ->selectRaw('log_name, count(*) as n')
            ->groupBy('log_name')
            ->pluck('n', 'log_name');

        \Illuminate\Support\Facades\DB::table('activity_log')->truncate();

        // Dicatat SESUDAH truncate supaya jejak pembersihannya sendiri tidak
        // ikut terhapus — kalau tidak, tidak ada bukti siapa yang mengosongkan.
        activity('logactivity')
            ->causedBy($request->user())
            ->withProperties([
                'jumlah_dihapus' => $jumlah,
                'rincian' => $rincian,
                'ip' => $request->ip(),
            ])
            ->log('Log Aktivitas dibersihkan');

        return redirect()->route('log-activity.index')->with('success',
            'Log Aktivitas dibersihkan: ' . number_format($jumlah)
            . ' catatan dihapus (termasuk riwayat login/logout).');
    }

    /**
     * Rincian satu log untuk modal: menyandingkan nilai SEBELUM & SESUDAH dan
     * menandai kolom yang berubah.
     *
     * Cakupan peran ditegakkan ULANG di sini, bukan hanya di daftar: id log bisa
     * dipanggil langsung, jadi tanpa ini seorang Admin bisa membaca rincian log
     * milik Developer hanya dengan menebak id-nya.
     */
    public function detail($id)
    {
        $q = Activity::with('causer')->whereKey($id);
        app(\App\Services\ActivityScope::class)->terapkan($q, auth()->user());

        $data = $q->firstOrFail();

        return response()->json([
            'html' => view('backend.help.log_activity._detail', compact('data'))->render(),
        ]);
    }
}
