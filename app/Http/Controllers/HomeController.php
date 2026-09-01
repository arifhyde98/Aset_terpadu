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
    public function __construct(\App\Services\VehicleService $vehicleService, \App\Services\SipatService $sipatService)
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
        $sipatStats = $this->sipatService->getDashboardStats();
        
        $sipatTotalTanah = $sipatStats['totalAset'];
        $sipatTotalLuas = $sipatStats['totalLuas'] ?? 0;
        
        $sipatSertifikatCount = $sipatStats['asetBersertifikat'];

        if ($sipatSertifikatCount == 0) {
            $sipatSertifikatCount = \App\Models\Elabel\ElabelSertifikat::count();
        }

        $sipatProsesBpnCount = $sipatStats['asetProses'];
        $sipatKendalaCount = $sipatStats['asetKendala'];
        $sipatBelumSertifikatCount = $sipatStats['totalBelumBersertifikat'] ?? max(0, $sipatTotalTanah - $sipatSertifikatCount - $sipatKendalaCount - ($sipatStats['asetTargetCount'] ?? 98));

        // 2. STATISTIK MODUL eLABEL (Digital Label & Box Gudang)
        $elabelTotalBpkb = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->count();
        $elabelBpkbR4 = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->whereIn('vehicle_type', ['R4', 'mobil'])->count();
        $elabelBpkbR2 = \App\Models\Elabel\ElabelBpkb::where('status', '!=', 'Dihapus')->whereIn('vehicle_type', ['R2', 'motor'])->count();
        $elabelTotalSertifikat = \App\Models\Elabel\ElabelSertifikat::count();
        $elabelTotalSurat = \App\Models\Elabel\ElabelSuratPenyerahan::count();
        $elabelTotalBoxes = \App\Models\Elabel\ElabelBox::count() + \App\Models\Elabel\ElabelSertifikatBox::count() + \App\Models\Elabel\ElabelSuratPenyerahanBox::count();
        $elabelPeminjamanAktif = \App\Models\Elabel\ElabelLoan::where('status', 'Dipinjam')->count();

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
        $activities = \App\Models\Activity::with('user')->latest()->take(8)->get();
        $elabelLogs = \App\Models\Elabel\ElabelActivityLog::latest()->take(8)->get();

        return view('home', compact(
            'sipatTotalTanah',
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
            'elabelLogs'
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
