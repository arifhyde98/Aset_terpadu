<?php

namespace App\Http\Controllers\Elabel;

use App\Http\Controllers\Controller;
use App\Models\Elabel\ElabelActivityLog;
use App\Models\Elabel\ElabelBpkb;
use App\Models\Elabel\ElabelBpkbOcrStaging;
use App\Services\Elabel\BpkbOcrExtractionService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;

class ElabelBpkbOcrStagingController extends Controller implements HasMiddleware
{
    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    /**
     * Mengambil ringkasan jumlah saran OCR yang berstatus pending.
     */
    public function summary(): JsonResponse
    {
        $pendingCount = ElabelBpkbOcrStaging::where('status', 'pending')->count();

        return response()->json([
            'success'       => true,
            'pending_count' => $pendingCount,
        ]);
    }

    /**
     * Mengambil detail komparasi data untuk modal review checklist.
     */
    public function review(int $id): JsonResponse
    {
        $staging = ElabelBpkbOcrStaging::with(['bpkb.box', 'bpkb.opd'])->find($id);

        if (!$staging || !$staging->bpkb) {
            return response()->json([
                'success' => false,
                'message' => 'Data rekomendasi scan BPKB tidak ditemukan.',
            ], 404);
        }

        $bpkb = $staging->bpkb;
        $pdfUrl = route('elabel.bpkb.view-pdf', $bpkb->id);

        return response()->json([
            'success'    => true,
            'staging_id' => $staging->id,
            'status'     => $staging->status,
            'bpkb'       => [
                'id'           => $bpkb->id,
                'plate_number' => $bpkb->plate_number,
                'no_bpkb'      => $bpkb->no_bpkb,
                'no_rangka'    => $bpkb->no_rangka,
                'no_mesin'     => $bpkb->no_mesin,
                'merek'        => $bpkb->merek,
                'tipe'         => $bpkb->tipe,
                'year'         => $bpkb->year,
                'isi_silinder' => $bpkb->isi_silinder,
                'warna'        => $bpkb->warna,
                'pengguna'     => $bpkb->pengguna,
                'vehicle_type' => $bpkb->vehicle_type,
                'box_name'     => $bpkb->box?->box_name ?? '-',
                'opd_name'     => $bpkb->opd?->nama_opd ?? '-',
                'pdf_url'      => $pdfUrl,
            ],
            'extracted'  => $staging->raw_extracted_json,
            'diff'       => $staging->diff_fields_json,
            'scanned_at' => $staging->created_at?->format('d/m/Y H:i'),
        ]);
    }

    /**
     * Menerapkan hanya kolom-kolom yang dicentang oleh pengguna ke database utama.
     */
    public function apply(Request $request, int $id): JsonResponse
    {
        $request->validate([
            'fields'   => 'required|array|min:1',
            'fields.*' => 'string|in:no_bpkb,plate_number,no_rangka,no_mesin,merek,tipe,year,isi_silinder,warna,pengguna,vehicle_type',
            'values'   => 'nullable|array',
        ]);

        $staging = ElabelBpkbOcrStaging::with('bpkb')->find($id);
        if (!$staging || !$staging->bpkb) {
            return response()->json([
                'success' => false,
                'message' => 'Data rekomendasi tidak ditemukan.',
            ], 404);
        }

        $bpkb = $staging->bpkb;
        $extracted = $staging->raw_extracted_json ?? [];
        $customValues = $request->input('values', []);
        $selectedFields = $request->input('fields', []);

        $oldData = $bpkb->toArray();
        $updates = [];

        foreach ($selectedFields as $field) {
            // Gunakan custom value jika diedit user di modal, jika tidak gunakan nilai hasil scan
            $val = array_key_exists($field, $customValues) ? $customValues[$field] : ($extracted[$field] ?? null);

            if ($field === 'year' && $val !== null && is_numeric($val)) {
                $val = (int) $val;
            }

            if ($field === 'vehicle_type' && $val !== null) {
                $val = strtoupper($val) === 'R2' ? 'R2' : 'R4';
            }

            $updates[$field] = $val;
        }

        if (empty($updates)) {
            return response()->json([
                'success' => false,
                'message' => 'Tidak ada kolom yang dipilih untuk diperbarui.',
            ], 422);
        }

        DB::beginTransaction();
        try {
            $bpkb->update($updates);

            $staging->update([
                'status'      => 'applied',
                'reviewed_by' => Auth::id(),
                'reviewed_at' => now(),
            ]);

            // Catat ke log aktivitas eLABEL
            ElabelActivityLog::create([
                'user_id'        => Auth::id(),
                'action'         => 'update',
                'reference_type' => 'BPKB',
                'description'    => 'Menerapkan ' . count($updates) . ' kolom data dari rekomendasi scan OCR untuk BPKB ' . $bpkb->plate_number,
                'module'         => 'bpkb',
                'reference_id'   => $bpkb->id,
                'old_data'       => json_encode($oldData),
                'new_data'       => json_encode($bpkb->fresh()->toArray()),
                'created_at'     => now(),
            ]);

            DB::commit();

            return response()->json([
                'success'       => true,
                'message'       => 'Pembaruan data dari hasil scan BPKB berhasil diterapkan ke database.',
                'updated_fields'=> array_keys($updates),
                'fresh_bpkb'    => $bpkb->fresh(),
            ]);
        } catch (\Throwable $e) {
            DB::rollBack();
            return response()->json([
                'success' => false,
                'message' => 'Gagal menerapkan perubahan: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Menolak / mengabaikan rekomendasi hasil scan untuk record ini.
     * Opsi delete_pdf=true jika pengguna juga ingin menghapus berkas fisik yang salah diupload.
     */
    public function reject(Request $request, int $id): JsonResponse
    {
        $staging = ElabelBpkbOcrStaging::with('bpkb')->find($id);
        if (!$staging) {
            return response()->json([
                'success' => false,
                'message' => 'Data rekomendasi tidak ditemukan.',
            ], 404);
        }

        $staging->update([
            'status'      => 'rejected',
            'reviewed_by' => Auth::id(),
            'reviewed_at' => now(),
        ]);

        $deletedPdf = false;
        if ($request->boolean('delete_pdf') && $staging->bpkb && $staging->bpkb->pdf_path) {
            $bpkb = $staging->bpkb;
            $oldPath = $bpkb->pdf_path;
            if (Storage::disk('local')->exists($oldPath)) {
                Storage::disk('local')->delete($oldPath);
            }
            if (Storage::disk('public')->exists($oldPath)) {
                Storage::disk('public')->delete($oldPath);
            }
            $bpkb->pdf_path = null;
            $bpkb->save();
            $deletedPdf = true;

            ElabelActivityLog::create([
                'user_id'        => Auth::id() ?: 1,
                'action'         => 'update',
                'module'         => 'BPKB',
                'description'    => 'Menghapus berkas scan BPKB ' . $bpkb->plate_number . ' karena salah upload berkas fisik.',
                'reference_type' => 'bpkb',
                'reference_id'   => $bpkb->id,
                'old_data'       => json_encode(['pdf_path' => $oldPath]),
                'new_data'       => json_encode(['pdf_path' => null]),
                'created_at'     => now(),
            ]);
        }

        return response()->json([
            'success' => true,
            'deleted_pdf' => $deletedPdf,
            'message' => $deletedPdf
                ? 'Rekomendasi scan ditolak dan berkas fisik scan BPKB yang salah upload berhasil dihapus.'
                : 'Rekomendasi scan berhasil diabaikan.',
        ]);
    }

    /**
     * Memicu pemindaian langsung (on-demand) untuk sebuah BPKB tertentu.
     */
    public function triggerScan(int $bpkbId, BpkbOcrExtractionService $service): JsonResponse
    {
        $bpkb = ElabelBpkb::find($bpkbId);
        if (!$bpkb) {
            return response()->json([
                'success' => false,
                'message' => 'Data BPKB tidak ditemukan.',
            ], 404);
        }

        if (empty($bpkb->pdf_path)) {
            return response()->json([
                'success' => false,
                'message' => 'BPKB ini belum memiliki berkas scan PDF.',
            ], 422);
        }

        $result = $service->extract($bpkb);
        if (!$result['success']) {
            return response()->json([
                'success' => false,
                'message' => $result['message'],
            ], 500);
        }

        return response()->json([
            'success'    => true,
            'staging_id' => $result['staging_id'],
            'data'       => $result['data'],
            'diff'       => $result['diff'],
            'message'    => 'Pemindaian berhasil ditampung ke staging buffer.',
        ]);
    }
}
