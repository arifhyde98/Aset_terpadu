<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelBox;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbDelete;
use App\Models\Elabel\ElabelLoan;
use App\Models\Elabel\ElabelSertifikat;
use App\Models\Elabel\ElabelSertifikatBox;
use App\Models\Elabel\ElabelSuratPenyerahan;
use App\Models\Elabel\ElabelSuratPenyerahanBox;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class ElabelDashboardController extends Controller implements HasMiddleware
{
    private const ACTIVITY_RETENTION_DAYS = 180;

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(): View
    {
        $bpkbBoxCount = ElabelBox::count();
        $sertifikatBoxCount = ElabelSertifikatBox::count();
        $suratPenyerahanBoxCount = ElabelSuratPenyerahanBox::count();

        $boxMobilCount = ElabelBox::where('vehicle_type', 'R4')->orWhere('vehicle_type', 'mobil')->count();
        $boxMotorCount = ElabelBox::where('vehicle_type', 'R2')->orWhere('vehicle_type', 'motor')->count();

        $bpkbCount = ElabelBpkb::where('status', '!=', 'Dihapus')->count();
        $bpkbMobilCount = ElabelBpkb::where('status', '!=', 'Dihapus')
            ->where(function ($q) {
                $q->where('vehicle_type', 'R4')->orWhere('vehicle_type', 'mobil');
            })->count();

        $bpkbMotorCount = ElabelBpkb::where('status', '!=', 'Dihapus')
            ->where(function ($q) {
                $q->where('vehicle_type', 'R2')->orWhere('vehicle_type', 'motor');
            })->count();

        $bpkbAvailableCount = ElabelBpkb::where('status', 'Tersedia')->count();
        $bpkbDeletedCount = ElabelBpkbDelete::count();

        $filledBoxCount = DB::table('elabel_boxes')
            ->join('elabel_bpkb', function ($join) {
                $join->on('elabel_bpkb.box_id', '=', 'elabel_boxes.id')
                    ->where('elabel_bpkb.status', '!=', 'Dihapus');
            })
            ->distinct('elabel_boxes.id')
            ->count('elabel_boxes.id');

        $loanCount = ElabelLoan::count();
        $loanApprovedCount = ElabelLoan::where('status', 'Disetujui')->count();

        $sertifikatCount = ElabelSertifikat::count();
        $suratPenyerahanCount = ElabelSuratPenyerahan::count();

        $boxCount = $bpkbBoxCount + $sertifikatBoxCount + $suratPenyerahanBoxCount;

        $boxFilledPercent = $boxCount > 0 ? (int) round(($filledBoxCount / $boxCount) * 100) : 0;
        $bpkbActivePercent = $bpkbCount > 0 ? (int) round(($bpkbAvailableCount / $bpkbCount) * 100) : 0;
        $loanPercent = $bpkbCount > 0 ? min(100, (int) round(($loanCount / $bpkbCount) * 100)) : 0;

        $oldActivity180Count = ElabelActivityLog::where('created_at', '<', now()->subDays(self::ACTIVITY_RETENTION_DAYS))->count();

        $activityLogs = ElabelActivityLog::with('user')
            ->orderBy('created_at', 'desc')
            ->limit(10)
            ->get();

        return view('elabel.dashboard', compact(
            'boxCount',
            'bpkbBoxCount',
            'sertifikatBoxCount',
            'suratPenyerahanBoxCount',
            'boxMobilCount',
            'boxMotorCount',
            'bpkbCount',
            'bpkbMobilCount',
            'bpkbMotorCount',
            'bpkbDeletedCount',
            'filledBoxCount',
            'bpkbAvailableCount',
            'loanCount',
            'loanApprovedCount',
            'sertifikatCount',
            'suratPenyerahanCount',
            'boxFilledPercent',
            'bpkbActivePercent',
            'loanPercent',
            'activityLogs',
            'oldActivity180Count'
        ));
    }

    public function cleanupActivityLogs(): RedirectResponse
    {
        $user = Auth::user();
        if (!$user || !in_array($user->role, ['superadmin', 'super_admin', 'admin'])) {
            return redirect()->route('elabel.dashboard')->with('error', 'Hanya administrator yang dapat membersihkan riwayat aktifitas.');
        }

        $cutoff = now()->subDays(self::ACTIVITY_RETENTION_DAYS);
        $deletedCount = ElabelActivityLog::where('created_at', '<', $cutoff)->delete();

        ElabelActivityLog::create([
            'user_id' => $user->id,
            'action' => 'delete',
            'module' => 'Riwayat Aktifitas',
            'description' => 'Membersihkan ' . $deletedCount . ' riwayat aktifitas lebih lama dari ' . self::ACTIVITY_RETENTION_DAYS . ' hari.',
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'created_at' => now(),
        ]);

        return redirect()->route('elabel.dashboard')->with('success', $deletedCount . ' riwayat aktifitas lama berhasil dibersihkan.');
    }
}
