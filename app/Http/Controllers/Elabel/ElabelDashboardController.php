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
use App\Services\Elabel\ElabelDuplicateService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\JsonResponse;
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
            new Middleware('role:superadmin,admin', only: ['cleanupActivityLogs', 'checkDuplicates', 'resolveDuplicateBpkb', 'resolveDuplicateSertifikat']),
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

    /**
     * Menganalisis database eLABEL untuk data arsip ganda BPKB & Sertifikat Tanah.
     *
     * @param ElabelDuplicateService $service
     * @return JsonResponse
     */
    public function checkDuplicates(ElabelDuplicateService $service): JsonResponse
    {
        try {
            $duplicateBpkbs = $service->getDuplicateBpkbList();
            $duplicateSerts = $service->getDuplicateSertifikatList();

            // Transform data BPKB
            $formattedBpkbs = array_map(function ($item) {
                $orig = $item['original_bpkb'];
                $dup = $item['duplicate_bpkb'];

                return [
                    'duplicate_id'   => $dup->id,
                    'duplicate_code' => $dup->no_bpkb,
                    'duplicate_nama' => $dup->plate_number ?? '-',
                    
                    'original_id'    => $orig->id,
                    'original_code'  => $orig->no_bpkb,
                    'original_nama'  => $orig->plate_number ?? '-',
                    
                    'reason'         => $item['reason'],
                    'differences'    => [
                        ['label' => 'Plat Nomor', 'original_val' => $orig->plate_number ?? '-', 'duplicate_val' => $dup->plate_number ?? '-', 'is_different' => ($orig->plate_number !== $dup->plate_number)],
                        ['label' => 'No Rangka', 'original_val' => $orig->no_rangka ?? '-', 'duplicate_val' => $dup->no_rangka ?? '-', 'is_different' => ($orig->no_rangka !== $dup->no_rangka)],
                        ['label' => 'No Mesin', 'original_val' => $orig->no_mesin ?? '-', 'duplicate_val' => $dup->no_mesin ?? '-', 'is_different' => ($orig->no_mesin !== $dup->no_mesin)],
                        ['label' => 'OPD Sipat', 'original_val' => $orig->opdSipat?->nama ?? '-', 'duplicate_val' => $dup->opdSipat?->nama ?? '-', 'is_different' => ($orig->sipat_opd_id !== $dup->sipat_opd_id)],
                    ]
                ];
            }, $duplicateBpkbs);

            // Transform data Sertifikat
            $formattedSerts = array_map(function ($item) {
                $orig = $item['original_sertifikat'];
                $dup = $item['duplicate_sertifikat'];

                return [
                    'duplicate_id'   => $dup->id,
                    'duplicate_code' => $dup->no_sertipikat,
                    'duplicate_nama' => $dup->nama_pemilik ?? '-',
                    
                    'original_id'    => $orig->id,
                    'original_code'  => $orig->no_sertipikat,
                    'original_nama'  => $orig->nama_pemilik ?? '-',
                    
                    'reason'         => $item['reason'],
                    'differences'    => [
                        ['label' => 'Nama Pemilik', 'original_val' => $orig->nama_pemilik ?? '-', 'duplicate_val' => $dup->nama_pemilik ?? '-', 'is_different' => ($orig->nama_pemilik !== $dup->nama_pemilik)],
                        ['label' => 'NIB / Nibar', 'original_val' => $orig->nibar ?? '-', 'duplicate_val' => $dup->nibar ?? '-', 'is_different' => ($orig->nibar !== $dup->nibar)],
                        ['label' => 'Luas', 'original_val' => $orig->luas ?? '-', 'duplicate_val' => $dup->luas ?? '-', 'is_different' => ($orig->luas !== $dup->luas)],
                        ['label' => 'Alamat', 'original_val' => $orig->alamat ?? '-', 'duplicate_val' => $dup->alamat ?? '-', 'is_different' => ($orig->alamat !== $dup->alamat)],
                    ]
                ];
            }, $duplicateSerts);

            return response()->json([
                'success'     => true,
                'bpkbs'       => $formattedBpkbs,
                'sertifikats' => $formattedSerts
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Gagal memindai duplikasi arsip: ' . $e->getMessage()
            ], 500);
        }
    }

    /**
     * Menyelesaikan duplikasi BPKB eLABEL.
     *
     * @param Request $request
     * @param ElabelDuplicateService $service
     * @return JsonResponse
     */
    public function resolveDuplicateBpkb(Request $request, ElabelDuplicateService $service): JsonResponse
    {
        try {
            $request->validate([
                'original_id'  => 'required|integer|exists:elabel_bpkb,id',
                'duplicate_id' => 'required|integer|exists:elabel_bpkb,id',
                'action'       => 'required|string|in:merge,delete',
            ]);

            $originalId = (int)$request->input('original_id');
            $duplicateId = (int)$request->input('duplicate_id');
            $action = $request->input('action');

            if ($action === 'merge') {
                $success = $service->mergeBpkb($originalId, $duplicateId);
                $msg = 'Arsip BPKB berhasil digabungkan dan duplikat dibersihkan.';
            } else {
                $success = DB::transaction(function () use ($duplicateId) {
                    $dup = ElabelBpkb::find($duplicateId);
                    if ($dup) {
                        ElabelLoan::where('bpkb_id', $duplicateId)->delete();
                        $dup->delete();
                        return true;
                    }
                    return false;
                });
                $msg = 'Arsip BPKB duplikat berhasil dibersihkan dari database.';
            }

            if (!$success) {
                return response()->json(['success' => false, 'message' => 'Gagal memproses data.'], 500);
            }

            ElabelActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'delete',
                'module'      => 'eLABEL BPKB',
                'description' => "Resolusi duplikasi BPKB [Aksi: {$action}, ID Utama: {$originalId}, ID Duplikat: {$duplicateId}]",
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent()
            ]);

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    /**
     * Menyelesaikan duplikasi Sertifikat eLABEL.
     *
     * @param Request $request
     * @param ElabelDuplicateService $service
     * @return JsonResponse
     */
    public function resolveDuplicateSertifikat(Request $request, ElabelDuplicateService $service): JsonResponse
    {
        try {
            $request->validate([
                'original_id'  => 'required|integer|exists:elabel_sertifikat_tanah,id',
                'duplicate_id' => 'required|integer|exists:elabel_sertifikat_tanah,id',
                'action'       => 'required|string|in:merge,delete',
            ]);

            $originalId = (int)$request->input('original_id');
            $duplicateId = (int)$request->input('duplicate_id');
            $action = $request->input('action');

            if ($action === 'merge') {
                $success = $service->mergeSertifikat($originalId, $duplicateId);
                $msg = 'Arsip Sertifikat berhasil digabungkan dan duplikat dibersihkan.';
            } else {
                $success = DB::transaction(function () use ($duplicateId) {
                    $dup = ElabelSertifikat::find($duplicateId);
                    if ($dup) {
                        $dup->delete();
                        return true;
                    }
                    return false;
                });
                $msg = 'Arsip Sertifikat duplikat berhasil dibersihkan dari database.';
            }

            if (!$success) {
                return response()->json(['success' => false, 'message' => 'Gagal memproses data.'], 500);
            }

            ElabelActivityLog::create([
                'user_id'     => Auth::id(),
                'action'      => 'delete',
                'module'      => 'eLABEL Sertifikat',
                'description' => "Resolusi duplikasi Sertifikat [Aksi: {$action}, ID Utama: {$originalId}, ID Duplikat: {$duplicateId}]",
                'ip_address'  => $request->ip(),
                'user_agent'  => $request->userAgent()
            ]);

            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }
}
