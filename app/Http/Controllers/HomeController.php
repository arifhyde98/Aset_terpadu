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

    /**
     * Create a new controller instance.
     */
    public function __construct(\App\Services\VehicleService $vehicleService)
    {
        $this->vehicleService = $vehicleService;
    }

    /**
     * Menampilkan halaman dashboard utama admin dengan statistik dan data terbaru.
     * 
     * @return \Illuminate\View\View
     */
    public function index()
    {
        // 1. STATISTIK MODUL SIPAT (Aset Tanah & Progres BPN)
        $sipatTotalTanah = \App\Models\AsetTanah::count();
        $sipatTotalLuas = \App\Models\AsetTanah::sum('luas');
        
        $sipatSertifikatCount = \DB::table('proses_aset')
            ->join('status_proses', 'status_proses.id_status', '=', 'proses_aset.id_status')
            ->whereIn('status_proses.nama_status', ['Bersertifikat', 'Bersertifikat (Duplikat)'])
            ->distinct('id_aset')
            ->count('id_aset');

        if ($sipatSertifikatCount == 0) {
            $sipatSertifikatCount = \App\Models\Elabel\ElabelSertifikat::count();
        }

        // Count Sedang Diproses (Permohonan PERTEK, Terbit PERTEK, Selesai Pengukuran, Bermasalah, dll.)
        $sipatProsesBpnCount = \DB::table('proses_aset')
            ->join('status_proses', 'status_proses.id_status', '=', 'proses_aset.id_status')
            ->whereNotIn('status_proses.nama_status', ['Belum Diproses', 'Bersertifikat', 'Bersertifikat (Duplikat)'])
            ->distinct('id_aset')
            ->count('id_aset');

        $sipatBelumSertifikatCount = max(0, $sipatTotalTanah - $sipatSertifikatCount - $sipatProsesBpnCount);

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
