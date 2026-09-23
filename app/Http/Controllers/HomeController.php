<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;

/**
 * Controller untuk Halaman Utama (Dashboard) Admin
 */
class HomeController extends Controller implements HasMiddleware
{
    protected $vehicleService;

    /**
     * Mendapatkan middleware yang ditugaskan ke controller ini.
     */
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    protected $sipatService;
    
    /**
     * Create a new controller instance.
     */
    public function __construct(\App\Services\Erandis\VehicleService $vehicleService, \App\Services\Sipat\SipatService $sipatService)
    {
        $this->vehicleService = $vehicleService;
        $this->sipatService = $sipatService;
    }

    /**
     * Menampilkan halaman dashboard utama admin dengan statistik dan data terbaru.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. STATISTIK MODUL SIPAT (Aset Tanah & Progres BPN)
        $sipatStats = $this->sipatService->getDashboardStats(auth()->user());
        
        $sipatTotalTanah = $sipatStats['totalAset'];
        $sipatTanahTercatat = $sipatStats['totalTanahTercatat'] ?? 1188;
        $sipatTanahTakTercatat = $sipatStats['totalTanahTakTercatat'] ?? 1;
        $sipatTotalLuas = $sipatStats['totalLuas'] ?? 0;
        
        $sipatSertifikatCount = $sipatStats['asetBersertifikat'];

        if ($sipatSertifikatCount == 0) {
            $sipatSertifikatCount = \App\Models\Elabel\ElabelSertifikat::count();
        }

        $sipatProsesBpnCount = $sipatStats['asetProses'];
        $sipatKendalaCount = $sipatStats['asetKendala'];
        $sipatBelumSertifikatCount = $sipatStats['totalBelumBersertifikat'] ?? max(0, $sipatTotalTanah - $sipatSertifikatCount - $sipatKendalaCount - ($sipatStats['asetTargetCount'] ?? 89));

        // 2. STATISTIK MODUL eLABEL (Khusus Superadmin & Admin Aset)
        $user = auth()->user();
        $roleValue = $user ? (is_object($user->role) ? ($user->role->value ?? (string)$user->role) : (string)$user->role) : '';
        $isElabelAdmin = in_array($roleValue, ['superadmin', 'admin']);

        if ($isElabelAdmin) {
            $elabelTotalBpkb = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->count();
            $elabelBpkbR4 = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->whereIn('vehicle_type', ['R4', 'mobil'])->count();
            $elabelBpkbR2 = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->whereIn('vehicle_type', ['R2', 'motor'])->count();
            $elabelTotalSertifikat = \App\Models\Elabel\ElabelSertifikat::count();
            $elabelTotalSurat = \App\Models\Elabel\ElabelSuratPenyerahan::count();
            $elabelTotalBoxes = \App\Models\Elabel\ElabelBox::count() + \App\Models\Elabel\ElabelSertifikatBox::count() + \App\Models\Elabel\ElabelSuratPenyerahanBox::count();
            $elabelPeminjamanAktif = \App\Models\Elabel\ElabelLoan::where('status', 'Dipinjam')->count();
            $elabelLogs = \App\Models\Elabel\ElabelActivityLog::latest()->take(8)->get();
        } else {
            $elabelTotalBpkb = 0;
            $elabelBpkbR4 = 0;
            $elabelBpkbR2 = 0;
            $elabelTotalSertifikat = 0;
            $elabelTotalSurat = 0;
            $elabelTotalBoxes = 0;
            $elabelPeminjamanAktif = 0;
            $elabelLogs = collect();
        }

        // 3. STATISTIK MODUL eRANDIS (Kendaraan Dinas & Servis)
        $erandisStats = $this->vehicleService->getDashboardStats();
        $latestVehicles = \App\Models\Vehicle::with(['user', 'vehicleType'])->latest()->take(5)->get();

        // 4. TOP OPD INTEGRATED STATS (Top 5 OPD dengan Aset Terbanyak)
        $topOpds = \DB::table('vehicles')
            ->select('opd as name', \DB::raw('count(*) as count'))
            ->whereNotNull('opd')
            ->where('opd', '!=', '')
            ->where('opd', '!=', '-')
            ->groupBy('opd')
            ->orderBy('count', 'desc')
            ->limit(5)
            ->get();

        // 5. LIVE ACTIVITIES & ALERTS
        $activitiesQuery = \App\Models\Activity::with('user');

        if ($user && !in_array($roleValue, ['superadmin', 'admin'])) {
            if (($roleValue === 'opd' || $roleValue === \App\Enums\UserRole::OPD->value) && $user->opd_id) {
                $activitiesQuery->whereHas('user', function ($q) use ($user) {
                    $q->where('opd_id', $user->opd_id);
                });
            } elseif (($roleValue === 'kpb' || $roleValue === \App\Enums\UserRole::KPB->value) && $user->sub_opd_id) {
                $activitiesQuery->whereHas('user', function ($q) use ($user) {
                    $q->where('sub_opd_id', $user->sub_opd_id);
                });
            } else {
                $activitiesQuery->where('user_id', $user->id);
            }
        }

        $activities = $activitiesQuery->latest()->take(8)->get();

        return view('home', compact(
            'sipatTotalTanah',
            'sipatTanahTercatat',
            'sipatTanahTakTercatat',
            'sipatTotalLuas',
            'sipatSertifikatCount',
            'sipatBelumSertifikatCount',
            'sipatProsesBpnCount',
            'sipatKendalaCount',
            'elabelTotalBpkb',
            'elabelBpkbR4',
            'elabelBpkbR2',
            'elabelTotalSertifikat',
            'elabelTotalSurat',
            'elabelTotalBoxes',
            'elabelPeminjamanAktif',
            'erandisStats',
            'latestVehicles',
            'topOpds',
            'activities',
            'elabelLogs',
            'isElabelAdmin'
        ));
    }

    /**
     * Dashboard Khusus Modul eRANDIS (Monitoring Kendaraan Dinas)
     */
    public function erandisDashboard()
    {
        $stats = $this->vehicleService->getDashboardStats();
        $latestVehicles = \App\Models\Vehicle::with(['user', 'vehicleType'])->latest()->take(10)->get();
        $activities = auth()->user()->role === \App\Enums\UserRole::SUPERADMIN 
            ? \App\Models\Activity::with('user')->latest()->take(10)->get()
            : collect();

        return view('erandis.dashboard', compact('stats', 'latestVehicles', 'activities'));
    }
}
