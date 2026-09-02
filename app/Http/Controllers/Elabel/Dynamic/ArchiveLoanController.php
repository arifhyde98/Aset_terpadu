<?php

namespace App\Http\Controllers\Elabel\Dynamic;

use App\Http\Controllers\Controller;
use App\Models\Elabel\Dynamic\ArchiveItem;
use App\Models\Elabel\Dynamic\ArchiveLoan;
use App\Models\Opd;
use App\Services\Elabel\DynamicArchiveService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controllers\HasMiddleware;
use Illuminate\Routing\Controllers\Middleware;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class ArchiveLoanController extends Controller implements HasMiddleware
{
    protected DynamicArchiveService $archiveService;

    public function __construct(DynamicArchiveService $archiveService)
    {
        $this->archiveService = $archiveService;
    }

    public static function middleware(): array
    {
        return [
            new Middleware('auth'),
        ];
    }

    public function index(Request $request): View
    {
        $status = $request->get('status');
        $query  = trim((string) $request->get('q'));

        $builder = ArchiveLoan::with(['item.archiveType', 'user', 'opd', 'approver'])->orderBy('id', 'desc');

        if (!empty($status)) {
            $builder->where('status_persetujuan', $status);
        }

        if ($query !== '') {
            $builder->where(function ($q) use ($query) {
                $q->where('requester_name', 'LIKE', "%{$query}%")
                  ->orWhere('requester_org', 'LIKE', "%{$query}%")
                  ->orWhere('keperluan', 'LIKE', "%{$query}%")
                  ->orWhereHas('item', function ($iq) use ($query) {
                      $iq->where('nomor_dokumen', 'LIKE', "%{$query}%")
                         ->orWhere('nama_dokumen', 'LIKE', "%{$query}%");
                  });
            });
        }

        $loans = $builder->paginate(20)->withQueryString();
        $opds = Opd::orderBy('nama', 'asc')->get();

        return view('elabel.dynamic.loans.index', [
            'loans'          => $loans,
            'opds'           => $opds,
            'selectedStatus' => $status,
            'searchQuery'    => $query,
            'activeMenu'     => 'dynamic_loans',
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $request->validate([
            'archive_item_id' => 'required|exists:archive_items,id',
            'jenis_layanan'   => 'required|in:scan_digital,pinjam_fisik',
            'requester_name'  => 'required|string|max:150',
            'requester_phone' => 'nullable|string|max:50',
            'requester_email' => 'nullable|email|max:150',
            'opd_id'          => 'nullable|exists:opds,id',
            'requester_org'   => 'nullable|string|max:150',
            'tanggal_pinjam'  => 'nullable|date',
            'tanggal_kembali' => 'nullable|date|after_or_equal:tanggal_pinjam',
            'keperluan'       => 'required|string|max:500',
        ]);

        $item = ArchiveItem::findOrFail($request->archive_item_id);

        $loan = ArchiveLoan::create([
            'archive_item_id'    => $item->id,
            'user_id'            => Auth::id(),
            'opd_id'             => $request->opd_id ?: (Auth::user()->opd_id ?? null),
            'requester_name'     => $request->requester_name,
            'requester_phone'    => $request->requester_phone,
            'requester_email'    => $request->requester_email,
            'requester_org'      => $request->requester_org,
            'jenis_layanan'      => $request->jenis_layanan,
            'tanggal_pinjam'     => $request->tanggal_pinjam ?: now()->toDateString(),
            'tanggal_kembali'    => $request->tanggal_kembali,
            'status_persetujuan' => 'pending',
            'keperluan'          => $request->keperluan,
        ]);

        $this->archiveService->logActivity('create', 'Layanan Peminjaman Arsip', "Pengajuan peminjaman arsip: {$item->nomor_dokumen} oleh {$loan->requester_name}", 'archive_loan', $loan->id);

        return redirect()->back()->with('success', 'Permohonan layanan peminjaman/scan arsip berhasil diajukan!');
    }

    public function approve(Request $request, int $id): RedirectResponse
    {
        $loan = ArchiveLoan::with('item')->findOrFail($id);
        
        $loan->update([
            'status_persetujuan' => 'approved',
            'catatan_admin'      => $request->catatan_admin,
            'approved_by'        => Auth::id(),
            'approved_at'        => now(),
        ]);

        // Jika pinjam fisik, ubah status item menjadi 'Dipinjam'
        if ($loan->jenis_layanan === 'pinjam_fisik') {
            $loan->item->update(['status' => 'Dipinjam']);
        }

        $this->archiveService->logActivity('approve', 'Layanan Peminjaman Arsip', "Menyetujui peminjaman arsip {$loan->item->nomor_dokumen} untuk {$loan->requester_name}", 'archive_loan', $loan->id);

        return redirect()->back()->with('success', 'Permohonan peminjaman berhasil disetujui.');
    }

    public function reject(Request $request, int $id): RedirectResponse
    {
        $loan = ArchiveLoan::with('item')->findOrFail($id);

        $loan->update([
            'status_persetujuan' => 'rejected',
            'catatan_admin'      => $request->catatan_admin,
            'approved_by'        => Auth::id(),
            'approved_at'        => now(),
        ]);

        $this->archiveService->logActivity('reject', 'Layanan Peminjaman Arsip', "Menolak peminjaman arsip {$loan->item->nomor_dokumen} untuk {$loan->requester_name}", 'archive_loan', $loan->id);

        return redirect()->back()->with('warning', 'Permohonan peminjaman telah ditolak.');
    }

    public function markReturned(Request $request, int $id): RedirectResponse
    {
        $loan = ArchiveLoan::with('item')->findOrFail($id);

        $loan->update([
            'status_persetujuan' => 'returned',
            'catatan_admin'      => ($loan->catatan_admin ? $loan->catatan_admin . ' | ' : '') . 'Berkas telah dikembalikan pada ' . now()->translatedFormat('d F Y H:i'),
        ]);

        if ($loan->jenis_layanan === 'pinjam_fisik') {
            $loan->item->update(['status' => 'Tersedia']);
        }

        $this->archiveService->logActivity('update', 'Layanan Peminjaman Arsip', "Berkas arsip {$loan->item->nomor_dokumen} telah dikembalikan oleh {$loan->requester_name}", 'archive_loan', $loan->id);

        return redirect()->back()->with('success', 'Status berkas berhasil diperbarui menjadi Tersedia (Sudah Dikembalikan).');
    }

    public function destroy(int $id): RedirectResponse
    {
        $loan = ArchiveLoan::findOrFail($id);
        $loan->delete();

        $this->archiveService->logActivity('delete', 'Layanan Peminjaman Arsip', "Menghapus data permohonan pinjam ID #{$id}", 'archive_loan', $id);

        return redirect()->back()->with('success', 'Data riwayat peminjaman berhasil dihapus.');
    }
}
